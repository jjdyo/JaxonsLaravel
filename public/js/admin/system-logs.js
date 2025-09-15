(function() {
    const root = document.getElementById('system-logs-root');
    if (!root) return;

    const fetchUrl = root.dataset.fetchUrl;
    const selectedChannel = root.dataset.selectedChannel || '';
    let selectedDate = root.dataset.selectedDate || '';
    const latestDate = root.dataset.latestDate || '';
    const defaultLimit = parseInt(root.dataset.defaultLimit || '65536', 10);

    // Elements
    const viewer = document.getElementById('log-viewer');
    const container = document.getElementById('logs-container');
    const form = document.getElementById('log-form');
    const channelSelector = document.getElementById('channel-selector');
    const dateSelector = document.getElementById('date-selector');

    // Attach change listeners to avoid inline onchange attributes
    if (channelSelector && form) {
        channelSelector.addEventListener('change', function() {
            form.submit();
        });
    }
    if (dateSelector && form) {
        dateSelector.addEventListener('change', function() {
            form.submit();
        });
    }

    // Ensure there's a single <pre> element to write into
    let pre = container ? container.querySelector('pre.log-pre') : null;
    if (container && !pre) {
        pre = document.createElement('pre');
        pre.className = 'log-pre';
        container.innerHTML = '';
        container.appendChild(pre);
    }

    // Only activate infinite scroll when a channel is selected
    if (!selectedChannel) return;

    // Resolve date: when the form left date blank, we use the latest date for the channel
    if (!selectedDate) {
        selectedDate = latestDate;
    }
    if (!selectedDate) return; // nothing to load

    // State
    let offset = 0;      // start from beginning for consistent forward scrolling
    let limit = defaultLimit;
    let loading = false;
    let eof = false;

    function buildUrl(params) {
        const u = new URL(fetchUrl, window.location.origin);
        Object.entries(params).forEach(([k, v]) => {
            if (v !== undefined && v !== null && v !== '') u.searchParams.set(k, String(v));
        });
        return u.toString();
    }

    async function loadNextChunk() {
        if (loading || eof) return;
        loading = true;
        root.classList.add('is-loading');
        try {
            const url = buildUrl({ channel: selectedChannel, date: selectedDate, offset, limit });
            const res = await fetch(url, { headers: { 'Accept': 'application/json' } });
            if (!res.ok) {
                throw new Error('Failed to fetch logs: HTTP ' + res.status);
            }
            const data = await res.json();
            const chunk = typeof data.chunk === 'string' ? data.chunk : '';
            if (pre && chunk.length > 0) {
                pre.textContent += chunk;
            }
            eof = !!data.eof;
            if (typeof data.next_offset === 'number') {
                offset = data.next_offset;
            } else {
                offset = data.file_size || offset;
            }
        } catch (e) {
            console.error(e);
            if (container) {
                const err = document.createElement('div');
                err.className = 'log-error';
                err.textContent = 'Error loading logs. Please try again.';
                container.appendChild(err);
            }
            eof = true;
        } finally {
            root.classList.remove('is-loading');
            loading = false;
        }
    }

    // Kick off with the first chunk, replacing any server-rendered preview to keep offsets consistent
    if (pre) pre.textContent = '';
    loadNextChunk();

    // If content height is initially smaller than viewport, load more to fill
    const tryFill = () => {
        const scroller = viewer || container;
        if (!scroller) return;
        const nearFilled = scroller.scrollHeight <= scroller.clientHeight * 1.1;
        if (nearFilled && !eof) {
            loadNextChunk().then(() => {
                setTimeout(() => {
                    if (scroller.scrollHeight <= scroller.clientHeight * 1.1 && !eof) {
                        loadNextChunk();
                    }
                }, 50);
            });
        }
    };
    setTimeout(tryFill, 50);

    // Infinite scroll: when near bottom of the scroll area, load next
    const scroller = viewer || container;
    if (scroller) {
        scroller.addEventListener('scroll', () => {
            const threshold = 100; // px from bottom
            const atBottom = scroller.scrollTop + scroller.clientHeight >= scroller.scrollHeight - threshold;
            if (atBottom) {
                loadNextChunk();
            }
        });
    }
})();
