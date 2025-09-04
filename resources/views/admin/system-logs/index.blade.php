@extends('layouts.app')

@section('title', 'System Logs')

@section('styles')
    <link rel="stylesheet" href="{{ asset('css/admin/system-logs/system-logs.css') }}">
@endsection

@section('content')
    <div id="system-logs-root">
        <form id="log-form" method="GET" action="{{ route('admin.system-logs.index') }}" class="log-selector">
            <select id="channel-selector" name="channel" onchange="this.form.submit()">
                @foreach ($channels as $ch)
                    <option value="{{ $ch }}" @selected($selectedChannel === $ch)>{{ $ch }}</option>
                @endforeach
            </select>

            <select id="date-selector" name="date" onchange="this.form.submit()">
                <option value="" @selected($selectedDate==='')>Latest</option>
                @foreach (($availableLogs[$selectedChannel] ?? []) as $date)
                    <option value="{{ $date }}" @selected($selectedDate === $date)>{{ $date }}</option>
                @endforeach
            </select>
        </form>

        <div id="log-viewer" class="log-viewer">
            <div id="logs-container" class="logs-container">
                @if(empty($content))
                    <div class="no-logs-message">No logs found for this selection.</div>
                @else
                    <pre class="log-pre">{{ $content }}</pre>
                    {{-- If you prefer line-by-line divs instead of <pre>, we can switch. --}}
                @endif
            </div>
        </div>
    </div>
@endsection


