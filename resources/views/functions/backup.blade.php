@extends('layouts.app')

@section('title', 'Backup Website')

@section('content')
    <h2>Backup Website</h2>

    @if(session('success'))
        <div class="alert alert-success" role="alert">
            {{ session('success') }}
        </div>
    @endif

    @if ($errors->any())
        <div class="alert alert-danger" role="alert">
            <ul class="mb-0">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <div class="card" style="max-width: 720px;">
        <div class="card-body">
            <h5 class="card-title">Test BackupWebsite Job</h5>
            <p class="card-text">Enter a website URL and dispatch a backup job to the queue.</p>
            <form method="POST" action="{{ route('functions.backup.dispatch') }}" class="mb-3">
                @csrf
                <div class="mb-3">
                    <label for="url" class="form-label">Website URL</label>
                    <input type="url" name="url" id="url" class="form-control @error('url') is-invalid @enderror" placeholder="https://example.com" value="{{ old('url') }}" required>
                    @error('url')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>
                <button type="submit" class="btn btn-primary">Dispatch Backup Job</button>
                <a href="{{ route('functions') }}" class="btn btn-link">Back to Functions</a>
            </form>

        </div>
    </div>

    <div class="mt-4">
        <h4>Pending Backup Jobs</h4>
        @if(!empty($pendingJobs) && count($pendingJobs) > 0)
            <div class="table-responsive">
                <table class="table table-sm table-striped align-middle">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Queue</th>
                            <th>Job</th>
                            <th>URL</th>
                            <th>Dispatched By</th>
                            <th>Available At</th>
                            <th>Created At</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($pendingJobs as $job)
                            <tr>
                                <td>{{ $job['id'] }}</td>
                                <td>{{ $job['queue'] }}</td>
                                <td>{{ $job['display'] }}</td>
                                <td>{{ $job['url'] ?? 'n/a' }}</td>
                                <td>{{ $job['dispatched_by_display'] ?? 'n/a' }}</td>
                                <td>{{ $job['available_at'] ?? 'n/a' }}</td>
                                <td>{{ $job['created_at'] ?? 'n/a' }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @else
            <p class="text-muted">No pending BackupWebsite jobs.</p>
        @endif
    </div>

    <div class="mt-4">
        <h4>Current Website Backups</h4>
        @if(!empty($backups) && count($backups) > 0)
            <div class="table-responsive">
                <table class="table table-sm table-striped align-middle">
                    <thead>
                        <tr>
                            <th>Site</th>
                            <th>Latest Archive</th>
                            <th>Updated</th>
                            <th>Sets</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($backups as $b)
                            <tr>
                                <td>{{ $b['site'] }}</td>
                                <td>
                                    @if($b['archive_exists'])
                                        <a href="{{ route('functions.backup.download', ['site' => $b['site']]) }}">{{ $b['site'] }}.tar.gz</a>
                                        @if($b['archive_size'])
                                            <small class="text-muted">({{ number_format($b['archive_size'] / 1024, 0) }} KB)</small>
                                        @endif
                                    @else
                                        <span class="text-muted">n/a</span>
                                    @endif
                                </td>
                                <td>
                                    @if($b['archive_mtime'])
                                        {{ date('Y-m-d H:i:s', $b['archive_mtime']) }}
                                    @else
                                        <span class="text-muted">n/a</span>
                                    @endif
                                </td>
                                <td>{{ $b['count'] }}</td>
                                <td>
                                    @if($b['archive_exists'])
                                        <a class="btn btn-sm btn-outline-secondary" href="{{ route('functions.backup.download', ['site' => $b['site']]) }}">Download</a>
                                    @else
                                        <span class="text-muted">—</span>
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @else
            <p class="text-muted">No backups found yet.</p>
        @endif
    </div>
@endsection
