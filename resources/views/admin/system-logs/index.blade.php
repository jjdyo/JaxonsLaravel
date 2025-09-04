@extends('layouts.app')

@section('title', 'System Logs')

@section('styles')
    <link rel="stylesheet" href="{{ asset('css/admin/system-logs/system-logs.css') }}">
@endsection

@section('content')
    <div id="system-logs-root"
         data-fetch-url="{{ route('admin.system-logs.fetch') }}"
         data-available-logs='@json($availableLogs ?? [])'>
        <div class="log-selector">
            <select id="channel-selector">
                @foreach ($channels as $ch)
                    <option value="{{ $ch }}">{{ $ch }}</option>
                @endforeach
            </select>

            <select id="date-selector">
                <option value="">Latest</option>
            </select>
        </div>

        <div id="log-viewer" class="log-viewer">
            <div id="logs-container"></div>
            <div id="loading-indicator" class="loading-indicator">Loading…</div>
        </div>
    </div>

@endsection

@section('scripts')
    <script src="{{ asset('js/admin/system-logs/system-logs.js') }}" defer></script>
@endsection

