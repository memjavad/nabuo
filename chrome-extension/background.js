// Synchronization state is now primarily managed in chrome.storage.local 
// to ensure persistence across Manifest V3 service worker restarts.

chrome.runtime.onInstalled.addListener(() => {
  chrome.storage.local.get(['baseUrl', 'processedScales', 'localHistory', 'logs'], (result) => {
    const defaults = {
      syncStatus: 'idle',
      processedScales: result.processedScales || [],
      baseUrl: result.baseUrl || 'https://arabpsychology.com',
      logs: result.logs || [],
      localHistory: result.localHistory || [],
      syncQueue: [],
      currentScaleIndex: 0,
      grokipediaTabId: null
    };
    chrome.storage.local.set(defaults);
  });
});

async function addLog(message, type = 'info') {
  const { logs } = await chrome.storage.local.get(['logs']);
  const newLog = {
    timestamp: new Date().toISOString(),
    message,
    type
  };
  const updatedLogs = [newLog, ...(logs || [])].slice(0, 100); // Keep last 100 logs
  await chrome.storage.local.set({ logs: updatedLogs });
}

chrome.runtime.onMessage.addListener((message, sender, sendResponse) => {
  if (message.action === 'startSync') {
    startSync();
  } else if (message.action === 'stopSync') {
    chrome.storage.local.set({
      syncStatus: 'idle',
      grokipediaTabId: null,
      syncQueue: [],
      currentScaleIndex: 0
    });
  } else if (message.action === 'submissionComplete') {
    handleSubmissionComplete(message.success, message.error);
  } else if (message.action === 'getStatus') {
    chrome.storage.local.get(['syncStatus', 'currentScaleIndex', 'syncQueue'], (result) => {
      sendResponse({
        isSyncing: result.syncStatus === 'syncing',
        currentScaleIndex: result.currentScaleIndex || 0,
        totalScales: (result.syncQueue || []).length
      });
    });
    return true; // Keep message channel open for async response
  }
});

async function startSync() {
  const state = await chrome.storage.local.get(['syncStatus', 'baseUrl', 'processedScales']);

  if (state.syncStatus === 'syncing') return;

  await chrome.storage.local.set({ syncStatus: 'syncing', currentScaleIndex: 0 });
  await addLog('Starting synchronization process...');

  const baseUrl = state.baseUrl;
  const processedScales = state.processedScales || [];

  try {
    if (!baseUrl) {
      throw new Error('Naboo Database URL is not configured. Please check Settings.');
    }

    const cleanBaseUrl = baseUrl.replace(/\/+$/, '');
    const fetchUrl = `${cleanBaseUrl}/wp-json/naboodatabase/v1/scales?posts_per_page=100`;
    await addLog(`Phase 1: Fetching scales from ${cleanBaseUrl}...`);

    const response = await fetch(fetchUrl);
    if (!response.ok) {
      throw new Error(`Connection failed (${response.status} ${response.statusText})`);
    }

    const allScales = await response.json();

    if (!Array.isArray(allScales)) {
      console.error('API Response is not an array:', allScales);
      throw new Error(`Invalid data format received from ${baseUrl}. Check if Naboo Database is active.`);
    }

    let totalFound = allScales.length;
    let localSkipped = 0;
    let serverSkipped = 0;

    // STRICT FILTERING: Ensure no scale is posted twice
    const syncQueue = allScales.filter(scale => {
      if (!scale || !scale.id) return false;

      const isLocallyProcessed = processedScales.includes(parseInt(scale.id));
      if (isLocallyProcessed) localSkipped++;

      const isServerSynced = scale.meta && (scale.meta.synced === true || scale.meta.synced === "1");
      if (isServerSynced) serverSkipped++;

      return !isLocallyProcessed && !isServerSynced;
    });

    await addLog(`Phase 1 Diagnostics: Found ${totalFound} total scales.`);
    if (localSkipped > 0) await addLog(`- Found in local history: ${localSkipped}`);
    if (serverSkipped > 0) await addLog(`- Marked as synced on server: ${serverSkipped}`);

    if (syncQueue.length === 0) {
      await chrome.storage.local.set({ syncStatus: 'completed', syncQueue: [] });
      await addLog('Phase 1 Complete: No new scales to process.', 'success');
      return;
    }

    await chrome.storage.local.set({ syncQueue, currentScaleIndex: 0 });
    await addLog(`Phase 1 Complete: ${syncQueue.length} new scales ready for Phase 2.`);

    await addLog(`Phase 2: Starting Grokipedia submissions in 2 seconds...`);

    setTimeout(async () => {
      const stateCheck = await chrome.storage.local.get(['syncStatus']);
      if (stateCheck.syncStatus === 'syncing') {
        await addLog(`Phase 2 Started: Automated posting initiated.`);
        processNextScale();
      }
    }, 2000);

  } catch (error) {
    console.error('Sync Error:', error);
    await chrome.storage.local.set({ syncStatus: 'error', lastError: error.message });
    await addLog(`Sync aborted: ${error.message}`, 'error');
  }
}

async function processNextScale() {
  const state = await chrome.storage.local.get(['syncStatus', 'syncQueue', 'currentScaleIndex', 'grokipediaTabId']);

  const syncQueue = state.syncQueue || [];
  const currentScaleIndex = state.currentScaleIndex || 0;
  const grokipediaTabId = state.grokipediaTabId;

  if (state.syncStatus !== 'syncing' || currentScaleIndex >= syncQueue.length) {
    if (currentScaleIndex >= syncQueue.length && syncQueue.length > 0) {
      await chrome.storage.local.set({ syncStatus: 'completed' });
      await addLog('All tasks finished.', 'success');
    }
    return;
  }

  const scale = syncQueue[currentScaleIndex];
  if (!scale) {
    await addLog('Error: Scale data missing in queue.', 'error');
    await chrome.storage.local.set({ syncStatus: 'error' });
    return;
  }

  // LAST-MINUTE DUPLICATE CHECK: Verify it wasn't synced in another session/tab
  const { processedScales } = await chrome.storage.local.get(['processedScales']);
  if ((processedScales || []).includes(parseInt(scale.id))) {
    await addLog(`Skip (already synced): ${scale.title}`);
    await chrome.storage.local.set({ currentScaleIndex: currentScaleIndex + 1 });
    processNextScale();
    return;
  }

  let displayTitle = scale.title;
  if (displayTitle.length > 50) {
    displayTitle = displayTitle.substring(0, 47) + '...';
    await addLog(`Title too long, truncated to: ${displayTitle}`);
  }
  await addLog(`Processing (${currentScaleIndex + 1}/${syncQueue.length}): ${displayTitle}`);

  const grokUrl = 'https://grokipedia.com/';

  if (grokipediaTabId) {
    chrome.tabs.get(grokipediaTabId, (tab) => {
      if (chrome.runtime.lastError || !tab) {
        createNewGrokTab(grokUrl, displayTitle, scale.link);
      } else {
        chrome.tabs.update(grokipediaTabId, { url: grokUrl, active: true }, () => {
          sendMessageToTab(grokipediaTabId, displayTitle, scale.link);
        });
      }
    });
  } else {
    createNewGrokTab(grokUrl, displayTitle, scale.link);
  }
}

function createNewGrokTab(url, title, link) {
  chrome.tabs.create({ url, active: true }, (tab) => {
    chrome.storage.local.set({ grokipediaTabId: tab.id });
    sendMessageToTab(tab.id, title, link);
  });
}

function sendMessageToTab(tabId, title, link) {
  // Wait for the page to load and the content script to be ready
  setTimeout(async () => {
    try {
      await chrome.tabs.sendMessage(tabId, {
        action: 'fillForm',
        title: title,
        link: link
      });
    } catch (error) {
      console.error('Failed to send message to tab', error);
    }
  }, 3000); // Give it some time to load
}

async function handleSubmissionComplete(success, error) {
  const state = await chrome.storage.local.get(['baseUrl', 'syncKey', 'syncQueue', 'currentScaleIndex', 'processedScales', 'localHistory']);
  const syncQueue = state.syncQueue || [];
  const currentScaleIndex = state.currentScaleIndex || 0;

  if (currentScaleIndex >= syncQueue.length) return;
  const scale = syncQueue[currentScaleIndex];

  if (success) {
    await addLog(`Successfully suggested scale: ${scale.title}`, 'success');

    // Update server-side sync status
    try {
      await fetch(`${state.baseUrl}/wp-json/naboodatabase/v1/scales/${scale.id}/sync-status`, {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
          'X-Naboo-Sync-Key': state.syncKey || ''
        },
        body: JSON.stringify({ synced: true })
      });
      await addLog(`Updated sync status on server for: ${scale.title}`);
    } catch (apiError) {
      await addLog(`Failed to update server status: ${apiError.message}`, 'error');
    }

    const processed = state.processedScales || [];
    const history = state.localHistory || [];

    const scaleIdInt = parseInt(scale.id);
    if (!processed.includes(scaleIdInt)) {
      processed.push(scaleIdInt);
      history.unshift({
        id: scaleIdInt,
        title: scale.title,
        syncedAt: new Date().toISOString()
      });
    }

    await chrome.storage.local.set({
      processedScales: processed,
      localHistory: history.slice(0, 100),
      currentScaleIndex: currentScaleIndex + 1
    });

    const nextIndex = currentScaleIndex + 1;

    // 5-second delay before the next scale
    await addLog('Waiting 5 seconds before next scale...');
    setTimeout(async () => {
      const updatedState = await chrome.storage.local.get(['syncStatus', 'syncQueue']);
      if (updatedState.syncStatus === 'syncing' && nextIndex < (updatedState.syncQueue || []).length) {
        processNextScale();
      } else {
        await chrome.storage.local.set({ syncStatus: 'completed' });
      }
    }, 5000);

  } else {
    console.error('Submission failed:', error);
    await chrome.storage.local.set({
      syncStatus: 'error',
      lastError: error,
      grokipediaTabId: null
    });
    await addLog(`Submission failed: ${error}`, 'error');
  }
}

// Global error handling for service worker
self.onerror = function (message, source, lineno, colno, error) {
  addLog(`System Error: ${message}`, 'error');
  console.error('Extension Error:', error);
};

// Handle unhandled rejections
self.onunhandledrejection = function (event) {
  addLog(`Async Error: ${event.reason}`, 'error');
};
