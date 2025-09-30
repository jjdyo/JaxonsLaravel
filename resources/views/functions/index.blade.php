@extends('layouts.app')

@section('title', 'Functions')

@section('content')
    <h2>Functions</h2>

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
            <h5 class="card-title">Website Backup Tool</h5>
            <p class="card-text">Open the backup tool to enter a website URL and dispatch a backup job to the queue.</p>
            <a href="{{ route('functions.backup') }}" class="btn btn-primary">Open Backup Tool</a>
        </div>
    </div>
@endsection
