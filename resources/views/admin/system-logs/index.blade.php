@extends('layouts.app')

@section('title', 'System Logs')

@section('styles')
<style>
    .system-logs-container {
        max-width: 1200px;
        margin: 0 auto;
        padding: 20px;
    }

    .log-selector {
        margin-bottom: 20px;
    }

    .log-selector select {
        padding: 8px 12px;
        border-radius: 4px;
        border: 1px solid #ccc;
        background-color: #fff;
        font-size: 16px;
        min-width: 200px;
    }

    .log-viewer {
        background-color: #1e1e1e;
        color: #f8f8f8;
        border-radius: 6px;
        padding: 15px;
        height: 70vh;
        overflow-y: auto;
        font-family: 'Courier New', monospace;
        font-size: 14px;
        line-height: 1.5;
    }

    .log-entry {
        margin-bottom: 10px;
        padding-bottom: 10px;
        border-bottom: 1px solid #333;
    }

    .log-timestamp {
        color: #6a9955;
        font-weight: bold;
    }

    .log-level {
        display: inline-block;
        padding: 2px 6px;
        border-radius: 3px;
        margin: 0 8px;
        font-size: 12px;
        font-weight: bold;
    }

    .log-level-info {
        background-color: #3498db;
        color: white;
    }

    .log-level-error {
        background-color: #e74c3c;
        color: white;
    }

    .log-level-warning {
        background-color: #f39c12;
        color: white;
    }

    .log-level-debug {
        background-color: #95a5a6;
        color: white;
    }

    .log-level-critical {
        background-color: #c0392b;
        color: white;
    }

    .log-message {
        display: block;
        margin-top: 5px;
        white-space: pre-wrap;
        word-break: break-word;
    }

    .loading-indicator {
        text-align: center;
        padding: 20px;
        display: none;
    }

    .loading-indicator.visible {
        display: block;
    }

    .no-logs-message {
        text-align: center;
        padding: 20px;
        color: #999;
        font-style: italic;
    }
</style>
@endsection

@section('content')
<div class="system-logs-container">
    <h1>System Logs</h1>
    <p>View application logs from different channels</p>

    <div class="log-selector">
        <label for="channel-selector">Select Log Channel:</label>
        <select id="channel-selector">
            @foreach($availableChannels as $channel)
                <option value="{{ $channel }}">{{ ucfirst($channel) }}</option>
            @endforeach
        </select>
    </div>

    <div class="log-viewer" id="log-viewer">
        <div id="logs-container"></div>
        <div id="loading-indicator" class="loading-indicator">
            Loading more logs...
        </div>
    </div>
</div>
@endsection

@section('scripts')
<script>
    document.addEventListener('DOMContentLoaded', function() {
        const logViewer = document.getElementById('log-viewer');
        const logsContainer = document.getElementById('logs-container');
        const channelSelector = document.getElementById('channel-selector');
        const loadingIndicator = document.getElementById('loading-indicator');

        let currentChannel = channelSelector.value;
        let currentPage = 1;
        let hasMoreLogs = true;
        let isLoading = false;
        const logsPerPage = 20;

        // Initial load
        loadLogs();

        // Handle channel change
        channelSelector.addEventListener('change', function() {
            currentChannel = this.value;
            currentPage = 1;
            hasMoreLogs = true;
            logsContainer.innerHTML = '';
            loadLogs();
        });

        // Handle scroll for infinite loading
        logViewer.addEventListener('scroll', function() {
            if (isLoading || !hasMoreLogs) return;

            // Check if we're near the bottom
            const scrollPosition = logViewer.scrollHeight - logViewer.scrollTop - logViewer.clientHeight;
            if (scrollPosition < 200) {
                loadLogs();
            }
        });

        // Function to load logs
        function loadLogs() {
            if (isLoading || !hasMoreLogs) return;

            isLoading = true;
            loadingIndicator.classList.add('visible');

            fetch(`{{ route('admin.system-logs.fetch') }}?channel=${currentChannel}&page=${currentPage}&limit=${logsPerPage}`)
                .then(response => response.json())
                .then(data => {
                    if (data.logs.length === 0 && currentPage === 1) {
                        logsContainer.innerHTML = '<div class="no-logs-message">No logs found for this channel</div>';
                    } else {
                        renderLogs(data.logs);
                    }

                    hasMoreLogs = data.hasMore;
                    currentPage++;
                })
                .catch(error => {
                    console.error('Error fetching logs:', error);
                    logsContainer.innerHTML += '<div class="log-entry">Error loading logs. Please try again.</div>';
                })
                .finally(() => {
                    isLoading = false;
                    loadingIndicator.classList.remove('visible');
                });
        }

        // Function to render logs
        function renderLogs(logs) {
            const fragment = document.createDocumentFragment();

            logs.forEach(log => {
                const logEntry = document.createElement('div');
                logEntry.className = 'log-entry';

                if (log.raw) {
                    // Handle raw log format
                    logEntry.textContent = log.raw;
                } else {
                    // Handle structured log format
                    const timestamp = document.createElement('span');
                    timestamp.className = 'log-timestamp';
                    timestamp.textContent = log.timestamp;

                    const level = document.createElement('span');
                    level.className = `log-level log-level-${log.level.toLowerCase()}`;
                    level.textContent = log.level;

                    const message = document.createElement('span');
                    message.className = 'log-message';
                    message.textContent = log.message;

                    logEntry.appendChild(timestamp);
                    logEntry.appendChild(level);
                    logEntry.appendChild(message);
                }

                fragment.appendChild(logEntry);
            });

            logsContainer.appendChild(fragment);
        }
    });
</script>
@endsection
