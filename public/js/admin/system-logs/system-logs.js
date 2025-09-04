document.addEventListener('DOMContentLoaded', () => {
    const root = document.getElementById('system-logs-root');
    const logsContainer = document.getElementById('logs-container');
    const channelSelector = document.getElementById('channel-selector');
    const dateSelector = document.getElementById('date-selector');
    const logViewer = document.getElementById('log-viewer');
    const loadingIndicator = document.getElementById('loading-indicator');

    // Guard: required elements
    if (!root || !logsContainer || !channelSelector || !dateSelector || !logViewer || !loadingIndicator) {
        console.error('System Logs: missing required DOM elements.');
        return;
    }

    // Pull config from data-attributes
    const FETCH_URL = root.dataset.fetchUrl;
    let availableLogs;
    try {
        availableLogs = JSON.parse(root.dataset.availableLogs || '{}');
    } catch {
        availableLogs = {};
    }
    if (!FETCH_URL) {
        console.error('System Logs: fetch URL not provided.');
        return;
    }

    let currentChannel = channelSelector.value;
    let currentDate = dateSelector.value;
    let currentPage = 1;
    let hasMoreLogs = true;
    let isLoading = false;
    const logsPerPage = 20;

    function updateDateSelector() {
        // keep the "Latest" option
        while (dateSelector.options.length > 1) dateSelector.remove(1);
        const dates = availableLogs[currentChannel] || [];
        dates.forEach((date) => {
            const opt = document.createElement('option');
            opt.value = date;
            opt.textContent = date;
            dateSelector.appendChild(opt);
        });
    }

    function buildUrl() {
        const params = new URLSearchParams({
            channel: currentChannel,
            page: String(currentPage),
            limit: String(logsPerPage),
        });
        if (currentDate) params.set('date', currentDate);
        return `${FETCH_URL}?${params.toString()}`;
    }

    function renderLogs(logs) {
        const frag = document.createDocumentFragment();
        logs.forEach((log) => {
            const entry = document.createElement('div');
            entry.className = 'log-entry';

            if (log.raw) {
                entry.textContent = log.raw;
            } else {
                const timestamp = document.createElement('span');
                timestamp.className = 'log-timestamp';
                timestamp.textContent = log.timestamp ?? '';

                const levelText = (log.level ?? 'INFO');
                const level = document.createElement('span');
                level.className = `log-level log-level-${String(levelText).toLowerCase()}`;
                level.textContent = levelText;

                const message = document.createElement('span');
                message.className = 'log-message';
                message.textContent = log.message ?? '';

                entry.appendChild(timestamp);
                entry.appendChild(level);
                entry.appendChild(message);
            }

            frag.appendChild(entry);
        });
        logsContainer.appendChild(frag);
    }

    function loadLogs() {
        if (isLoading || !hasMoreLogs) return;
        isLoading = true;
        loadingIndicator.classList.add('visible');

        fetch(buildUrl())
            .then((r) => r.json())
            .then((data) => {
                const logs = Array.isArray(data.entries) ? data.entries : [];
                if (logs.length === 0 && currentPage === 1) {
                    logsContainer.innerHTML = '<div class="no-logs-message">No logs found for this channel</div>';
                } else {
                    renderLogs(logs);
                }
                hasMoreLogs = Boolean(data.hasMore);
                currentPage++;
            })
            .catch((e) => {
                console.error('Error fetching logs:', e);
                logsContainer.insertAdjacentHTML('beforeend',
                    '<div class="log-entry">Error loading logs. Please try again.</div>');
            })
            .finally(() => {
                isLoading = false;
                loadingIndicator.classList.remove('visible');
            });
    }

    // init
    updateDateSelector();
    loadLogs();

    // events
    channelSelector.addEventListener('change', function () {
        currentChannel = this.value;
        updateDateSelector();
        currentDate = dateSelector.value; // empty = Latest
        currentPage = 1;
        hasMoreLogs = true;
        logsContainer.innerHTML = '';
        loadLogs();
    });

    dateSelector.addEventListener('change', function () {
        currentDate = this.value;
        currentPage = 1;
        hasMoreLogs = true;
        logsContainer.innerHTML = '';
        loadLogs();
    });

    logViewer.addEventListener('scroll', function () {
        if (isLoading || !hasMoreLogs) return;
        const remaining = logViewer.scrollHeight - logViewer.scrollTop - logViewer.clientHeight;
        if (remaining < 200) loadLogs();
    });
});
