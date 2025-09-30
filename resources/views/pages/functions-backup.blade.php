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

    <div class="card" style="max-width: 640px;">
        <div class="card-body">
            <h5 class="card-title">Test BackupWebsite Job</h5>
            <p class="card-text">Enter a website URL and dispatch a backup job to the queue.</p>
            <form method="POST" action="{{ route('functions.backup.dispatch') }}">
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
@endsection
