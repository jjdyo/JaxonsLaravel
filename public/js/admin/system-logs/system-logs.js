document.addEventListener('DOMContentLoaded', () => {
    const root = document.getElementById('system-logs-root');
    const logsContainer = document.getElementById('logs-container');
    const channelSelector = document.getElementById('channel-selector');
    const dateSelector = document.getElementById('date-selector');
    const logViewer = document.getElementById('log-viewer');
    const loadingIndicator = document.getElementById('loading-indicator');

    if (!root || !logsContainer || !channelSelector || !dateSelector || !logViewer || !loadingIndicator) {
        console.error('System Logs: missing required DOM elements.');
        return;
    }

    const FETCH_URL = root.dataset.fetchUrl;
    let availableLogs;
    try { availableLogs = JSON.parse(root.dataset.availableLogs || '{}'); } catch { availableLogs = {}; }
    if (!FETCH_URL) {
        console.error('System Logs: fetch URL not provided.');
        return;
    }

    let currentChannel = channelSelector.value;
    let currentDate = dateSelector.value;

    function updateDateSelector() {
        while (dateSelector.options.length > 1) dateSelector.remove(1); // keep "Latest"
        const dates = availableLogs[currentChannel] || [];
        dates.forEach((date) => {
            const opt = document.createElement('option');
            opt.value = date;
            opt.textContent = date;
            dateSelector.appendChild(opt);
        });
    }

    function buildUrl() {
        const params = new URLSearchParams({ channel: currentChannel });
        if (currentDate) params.set('date', currentDate); // empty => latest
        return `${FETCH_URL}?${params.toString()}`;
    }

    function renderRaw(content) {
        // Clear and render plain text as lines for consistent styling
        logsContainer.innerHTML = '';
        const frag = document.createDocumentFragment();

        // handle empty content
        if (!content) {
            const empty = document.createElement('div');
            empty.className = 'no-logs-message';
            empty.textContent = 'No logs found for this selection.';
            frag.appendChild(empty);
            logsContainer.appendChild(frag);
            return;
        }

        content.split('\n').forEach((line) => {
            const row = document.createElement('div');
            row.className = 'log-entry';
            // preserve spacing for monospaced look
            row.textContent = line;
            frag.appendChild(row);
        });

        logsContainer.appendChild(frag);
        // Scroll to top on reload so users see newest/top first (optional)
        logViewer.scrollTop = 0;
    }

    function loadWholeLog() {
        loadingIndicator.classList.add('visible');
        fetch(buildUrl(), { headers: { 'X-Requested-With': 'XMLHttpRequest' } })
            .then((r) => r.json())
            .then((data) => {
                if (typeof data.content === 'string') {
                    renderRaw(data.content);
                } else {
                    console.error('Unexpected response shape', data);
                    logsContainer.innerHTML = '<div class="log-entry">Error loading logs. Please try again.</div>';
                }
            })
            .catch((e) => {
                console.error('Error fetching logs:', e);
                logsContainer.innerHTML = '<div class="log-entry">Error loading logs. Please try again.</div>';
            })
            .finally(() => loadingIndicator.classList.remove('visible'));
    }

    // init
    updateDateSelector();
    loadWholeLog();

    // events
    channelSelector.addEventListener('change', function () {
        currentChannel = this.value;
        updateDateSelector();
        currentDate = dateSelector.value; // empty = Latest
        loadWholeLog();
    });

    dateSelector.addEventListener('change', function () {
        currentDate = this.value;        // '' or YYYY-MM-DD
        loadWholeLog();
    });

    // infinite scroll removed while we load the full file
});
