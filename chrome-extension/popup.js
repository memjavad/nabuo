const startBtn = document.getElementById('startBtn');
const stopBtn = document.getElementById('stopBtn');
const statusBadge = document.getElementById('statusBadge');
const progressBar = document.getElementById('progressBar');
const progressLabel = document.getElementById('progressLabel');
const progressCount = document.getElementById('progressCount');
const baseUrlInput = document.getElementById('baseUrlInput');
const clearLogsBtn = document.getElementById('clearLogsBtn');
const logsContainer = document.getElementById('logsContainer');
const historyContainer = document.getElementById('historyContainer');
const clearHistoryBtn = document.getElementById('clearHistoryBtn');
const tabBtns = document.querySelectorAll('.tab-btn');
const tabContents = document.querySelectorAll('.tab-content');
const openDashboardBtn = document.getElementById('openDashboardBtn');

// Open Dashboard
openDashboardBtn.addEventListener('click', () => {
    chrome.tabs.create({ url: chrome.runtime.getURL('dashboard.html') });
});

// Tab switching
tabBtns.forEach(btn => {
    btn.addEventListener('click', () => {
        const tabId = btn.getAttribute('data-tab');
        tabBtns.forEach(b => b.classList.remove('active'));
        tabContents.forEach(c => c.classList.remove('active'));
        btn.classList.add('active');
        document.getElementById(`${tabId}Tab`).classList.add('active');
    });
});

// Initialize UI
chrome.storage.local.get(['syncStatus', 'baseUrl', 'processedScales', 'logs', 'localHistory'], (result) => {
    if (result.baseUrl) baseUrlInput.value = result.baseUrl;
    updateUI(result.syncStatus, 0, result.processedScales ? result.processedScales.length : 0);
    renderLogs(result.logs || []);
    renderHistory(result.localHistory || []);

    // Request current status from background to get accurate progress
    chrome.runtime.sendMessage({ action: 'getStatus' }, (response) => {
        if (response) {
            updateUI(result.syncStatus, response.currentScaleIndex, response.totalScales);
        }
    });
});

startBtn.addEventListener('click', () => {
    let baseUrl = baseUrlInput.value.trim();
    if (!baseUrl) return;

    // Sanitize URL
    if (!/^https?:\/\//i.test(baseUrl)) {
        baseUrl = 'https://' + baseUrl;
    }
    baseUrl = baseUrl.replace(/\/+$/, '');
    baseUrlInput.value = baseUrl;

    chrome.storage.local.set({ baseUrl }, () => {
        chrome.runtime.sendMessage({ action: 'startSync' });
        updateUI('syncing', 0, 0);
    });
});

stopBtn.addEventListener('click', () => {
    chrome.runtime.sendMessage({ action: 'stopSync' });
    updateUI('idle', 0, 0);
});

clearLogsBtn.addEventListener('click', () => {
    chrome.storage.local.set({ logs: [] }, () => {
        renderLogs([]);
    });
});

clearHistoryBtn.addEventListener('click', () => {
    if (confirm('Clear local history?')) {
        chrome.storage.local.set({ localHistory: [] }, () => {
            renderHistory([]);
        });
    }
});

// Watch for storage changes to update UI
chrome.storage.onChanged.addListener((changes) => {
    if (changes.syncStatus || changes.processedScales || changes.currentScaleIndex) {
        chrome.runtime.sendMessage({ action: 'getStatus' }, (response) => {
            chrome.storage.local.get(['syncStatus'], (result) => {
                if (response) {
                    updateUI(result.syncStatus, response.currentScaleIndex, response.totalScales);
                } else {
                    updateUI(result.syncStatus, 0, 0);
                }
            });
        });
    }
    if (changes.logs) {
        renderLogs(changes.logs.newValue || []);
    }
    if (changes.localHistory) {
        renderHistory(changes.localHistory.newValue || []);
    }
});

function updateUI(status = 'idle', current = 0, total = 0) {
    const safeStatus = status || 'idle';
    statusBadge.className = `status-badge status-${safeStatus}`;
    statusBadge.textContent = safeStatus.charAt(0).toUpperCase() + safeStatus.slice(1);

    if (safeStatus === 'syncing') {
        startBtn.style.display = 'none';
        stopBtn.style.display = 'block';
        progressLabel.textContent = 'Syncing...';
    } else {
        startBtn.style.display = 'block';
        stopBtn.style.display = 'none';
        progressLabel.textContent = safeStatus === 'completed' ? 'All scales synced!' : (safeStatus === 'error' ? 'Error occurred' : 'Ready to start');
    }

    if (total > 0) {
        const percent = Math.max(0, Math.min(100, Math.round((current / total) * 100)));
        progressBar.style.width = `${percent}%`;
        progressCount.textContent = `${current}/${total}`;
    } else {
        progressBar.style.width = '0%';
        progressCount.textContent = '0/0';
    }
}

function renderLogs(logs) {
    logsContainer.innerHTML = '';
    logs.forEach(log => {
        const entry = document.createElement('div');
        entry.className = `log-entry log-${log.type}`;

        const time = document.createElement('span');
        time.className = 'log-time';
        time.textContent = new Date(log.timestamp).toLocaleTimeString();

        const msg = document.createElement('span');
        msg.textContent = log.message;

        entry.appendChild(time);
        entry.appendChild(msg);
        logsContainer.appendChild(entry);
    });
    // Auto-scroll to bottom
    logsContainer.scrollTop = logsContainer.scrollHeight;
}

function renderHistory(history) {
    historyContainer.innerHTML = '';

    if (!history || history.length === 0) {
        historyContainer.innerHTML = '<div style="text-align:center; padding: 40px; color: var(--text-muted); font-size: 12px;">No submissions found</div>';
        return;
    }

    history.forEach(item => {
        const entry = document.createElement('div');
        entry.className = 'history-item';

        const date = new Date(item.syncedAt).toLocaleString();

        entry.innerHTML = `
            <div class="history-title" title="${item.title}">${item.title}</div>
            <div class="history-meta">
                <span>ID: ${item.id}</span>
                <span>${date}</span>
            </div>
        `;
        historyContainer.appendChild(entry);
    });
}
