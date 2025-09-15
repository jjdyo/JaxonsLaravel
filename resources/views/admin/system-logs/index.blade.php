@extends('layouts.app')

@section('title', 'System Logs')

@section('styles')
    <link rel="stylesheet" href="{{ asset('css/admin/system-logs/system-logs.css') }}">
@endsection

@section('content')
    <div id="system-logs-root" class="system-logs-container"
         data-fetch-url="{{ route('admin.system-logs.fetch', [], false) }}"
         data-selected-channel="{{ $selectedChannel }}"
         data-selected-date="{{ $selectedDate }}"
         data-latest-date="{{ $selectedChannel ? ($latestDates[$selectedChannel] ?? '') : '' }}"
         data-default-limit="65536">
        <form id="log-form" method="GET" action="{{ route('admin.system-logs.index') }}" class="log-selector">

            {{-- Channel selector: starts blank so no log is loaded initially --}}
            <select id="channel-selector" name="channel">
                <option value="">— Select channel —</option>
                @foreach ($channels as $ch)
                    <option value="{{ $ch }}" @selected($selectedChannel === $ch)>{{ $ch }}</option>
                @endforeach
            </select>

            {{-- Date selector: disabled until a channel is chosen; shows Latest (YYYY-MM-DD) --}}
            @php
                $latest = $selectedChannel ? ($latestDates[$selectedChannel] ?? null) : null;
            @endphp
            <select id="date-selector" name="date" @disabled(!$selectedChannel)>
                @if(!$selectedChannel)
                    <option value="">— Select date —</option>
                @else
                    <option value="" @selected($selectedDate==='')>
                        Latest ({{ $latest ?? '—' }})
                    </option>
                    @foreach (($availableLogs[$selectedChannel] ?? []) as $date)
                        <option value="{{ $date }}" @selected($selectedDate === $date)>{{ $date }}</option>
                    @endforeach
                @endif
            </select>
        </form>

        <div id="log-viewer" class="log-viewer">
            <div id="logs-container" class="logs-container">
                @if ($content === '')
                    <div class="no-logs-message">
                        @if(!$selectedChannel)
                            Choose a channel to view logs.
                        @else
                            No logs found for this selection.
                        @endif
                    </div>
                @else
                    <pre class="log-pre">{{ $content }}</pre>
                @endif
            </div>
        </div>
    </div>
@endsection

@section('scripts')
    <script src="{{ asset('js/admin/system-logs.js') }}" defer></script>
@endsection


