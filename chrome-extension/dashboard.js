// DOM Elements
const startBtn = document.getElementById('startBtn');
const stopBtn = document.getElementById('stopBtn');
const statusBadge = document.getElementById('statusBadge');
const progressBar = document.getElementById('progressBar');
const currentAction = document.getElementById('currentAction');
const progressPercent = document.getElementById('progressPercent');
const baseUrlInput = document.getElementById('baseUrlInput');
const syncKeyInput = document.getElementById('syncKeyInput');
const saveSettingsBtn = document.getElementById('saveSettingsBtn');
const clearHistoryBtn = document.getElementById('clearHistoryBtn');
const clearLogsBtn = document.getElementById('clearLogsBtn');
const logList = document.getElementById('logList');
const historyList = document.getElementById('historyList');
const navItems = document.querySelectorAll('.nav-item');
const contentAreas = document.querySelectorAll('.content-area');
const viewTitle = document.getElementById('viewTitle');

// Stats Elements
const statTotalSynced = document.getElementById('statTotalSynced');
const statBatchProgress = document.getElementById('statBatchProgress');
const statSuccessRate = document.getElementById('statSuccessRate');

// Tab Switching
navItems.forEach(item => {
    item.addEventListener('click', () => {
        const tabId = item.getAttribute('data-tab');

        navItems.forEach(nav => nav.classList.remove('active'));
        contentAreas.forEach(area => area.classList.remove('active'));

        item.classList.add('active');
        document.getElementById(`${tabId}Tab`).classList.add('active');
        viewTitle.textContent = item.textContent.trim();
    });
});

// Initialize UI
function init() {
    chrome.storage.local.get([
        'syncStatus',
        'baseUrl',
        'syncKey',
        'processedScales',
        'logs',
        'localHistory',
        'currentScaleIndex',
        'syncQueue'
    ], (result) => {
        if (result.baseUrl) baseUrlInput.value = result.baseUrl;
        if (result.syncKey) syncKeyInput.value = result.syncKey;

        const processedCount = result.processedScales ? result.processedScales.length : 0;
        const current = result.currentScaleIndex || 0;
        const queueSize = (result.syncQueue || []).length;

        updateStatusUI(result.syncStatus || 'idle', current, queueSize, processedCount);
        renderLogs(result.logs || []);
        renderHistory(result.localHistory || []);
    });
}

// Start Sync
startBtn.addEventListener('click', () => {
    let baseUrl = baseUrlInput.value.trim();
    if (!baseUrl) return;

    if (!/^https?:\/\//i.test(baseUrl)) {
        baseUrl = 'https://' + baseUrl;
    }
    baseUrl = baseUrl.replace(/\/+$/, '');
    baseUrlInput.value = baseUrl;

    chrome.storage.local.set({ baseUrl }, () => {
        chrome.runtime.sendMessage({ action: 'startSync' });
    });
});

// Stop Sync
stopBtn.addEventListener('click', () => {
    chrome.runtime.sendMessage({ action: 'stopSync' });
});

// Save Settings
saveSettingsBtn.addEventListener('click', () => {
    let baseUrl = baseUrlInput.value.trim();
    let syncKey = syncKeyInput.value.trim();
    if (!baseUrl) return;

    if (!/^https?:\/\//i.test(baseUrl)) {
        baseUrl = 'https://' + baseUrl;
    }
    baseUrl = baseUrl.replace(/\/+$/, '');
    baseUrlInput.value = baseUrl;
    syncKeyInput.value = syncKey;

    chrome.storage.local.set({ baseUrl, syncKey }, () => {
        alert('Settings saved successfully!');
    });
});

// Clear Data
clearHistoryBtn.addEventListener('click', () => {
    if (confirm('Are you sure you want to clear your local submission history?')) {
        chrome.storage.local.set({ localHistory: [], processedScales: [] }, () => {
            renderHistory([]);
            statTotalSynced.textContent = '0';
        });
    }
});

clearLogsBtn.addEventListener('click', () => {
    chrome.storage.local.set({ logs: [] }, () => {
        renderLogs([]);
    });
});

// Storage Listener
chrome.storage.onChanged.addListener((changes) => {
    chrome.storage.local.get([
        'syncStatus',
        'currentScaleIndex',
        'syncQueue',
        'processedScales',
        'logs',
        'localHistory'
    ], (result) => {
        const processedCount = result.processedScales ? result.processedScales.length : 0;
        const current = result.currentScaleIndex || 0;
        const queueSize = (result.syncQueue || []).length;

        updateStatusUI(result.syncStatus, current, queueSize, processedCount);

        if (changes.logs) renderLogs(result.logs || []);
        if (changes.localHistory) renderHistory(result.localHistory || []);
    });
});

function updateStatusUI(status, current, total, totalSynced) {
    const safeStatus = status || 'idle';
    statusBadge.className = `status-pill status-${safeStatus}`;
    statusBadge.textContent = `System ${safeStatus.charAt(0).toUpperCase() + safeStatus.slice(1)}`;

    if (safeStatus === 'syncing') {
        startBtn.style.display = 'none';
        stopBtn.style.display = 'flex';
        currentAction.textContent = 'Syncing scales to Grokipedia...';
    } else {
        startBtn.style.display = 'flex';
        stopBtn.style.display = 'none';
        currentAction.textContent = safeStatus === 'completed' ? 'Synchronization completed!' : 'Ready to start';
    }

    // Update Stats
    statTotalSynced.textContent = totalSynced || 0;
    statBatchProgress.textContent = `${current}/${total}`;

    if (total > 0) {
        const percent = Math.round((current / total) * 100);
        progressBar.style.width = `${percent}%`;
        progressPercent.textContent = `${percent}%`;
    } else {
        progressBar.style.width = '0%';
        progressPercent.textContent = '0%';
    }
}

function renderLogs(logs) {
    logList.innerHTML = '';
    logs.forEach(log => {
        const entry = document.createElement('div');
        entry.className = `log-entry log-${log.type}`;

        const time = new Date(log.timestamp).toLocaleTimeString();

        entry.innerHTML = `
            <span class="log-time">[${time}]</span>
            <span class="log-msg">${log.message}</span>
        `;
        logList.appendChild(entry);
    });
    // Auto-scroll logic for logs view could go here
}

function renderHistory(history) {
    historyList.innerHTML = '';

    if (!history || history.length === 0) {
        historyList.innerHTML = '<div style="text-align:center; padding: 60px; color: var(--text-muted);">No submission history found</div>';
        return;
    }

    history.forEach(item => {
        const entry = document.createElement('div');
        entry.className = 'history-item';

        const date = new Date(item.syncedAt).toLocaleString();

        entry.innerHTML = `
            <div class="history-main-info">
                <span class="history-item-title" title="${item.title}">${item.title}</span>
                <span class="history-item-id">Scale ID: ${item.id}</span>
            </div>
            <span class="history-timestamp">${date}</span>
        `;
        historyList.appendChild(entry);
    });
}

// Start
init();
