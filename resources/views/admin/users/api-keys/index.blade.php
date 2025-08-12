@extends('layouts.app')

@section('title', 'API Keys for ' . $user->name)

@section('styles')
    <link rel="stylesheet" href="{{ asset('css/admin/users/useradministration.css') }}">
    <link rel="stylesheet" href="{{ asset('css/admin/users/api-keys.css') }}">
@endsection

@section('content')
<div class="user-management">
    <h1>API Keys for {{ $user->name }}</h1>

    <div class="breadcrumbs">
        <a href="{{ route('admin.users.index') }}">Users</a> &raquo;
        <a href="{{ route('admin.users.show', $user) }}">{{ $user->name }}</a> &raquo;
        <span>API Keys</span>
    </div>

    @if(session('success'))
        <div class="api-key-alert api-key-alert-success">
            {{ session('success') }}

            @if(session('plainTextApiKey'))
                <p><strong>Important:</strong> This API key will only be shown once. Please copy it now:</p>
                <div class="api-key-value">{{ session('plainTextApiKey') }}</div>
            @endif
        </div>
    @endif

    <div class="create-api-key-btn">
        <a href="{{ route('admin.users.api-keys.create', $user) }}" class="btn btn-primary">Create New API Key</a>
    </div>

    @if($apiKeys->isEmpty())
        <p class="no-api-keys-message">No API keys found for this user.</p>
    @else
        <div class="table-responsive">
            <table class="api-key-table">
                <thead>
                    <tr>
                        <th>Name</th>
                        <th>Last Used</th>
                        <th>Created</th>
                        <th>Expires</th>
                        <th>Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($apiKeys as $token)
                        <tr>
                            <td>{{ $token->name }}</td>
                            <td>
                                @if($token->last_used_at)
                                    {{ $token->last_used_at->format('M d, Y H:i') }}
                                @else
                                    <span class="api-key-badge api-key-never">Never</span>
                                @endif
                            </td>
                            <td>{{ $token->created_at->format('M d, Y') }}</td>
                            <td>
                                @if($token->expires_at)
                                    {{ $token->expires_at->format('M d, Y') }}
                                @else
                                    <span class="api-key-badge">Never</span>
                                @endif
                            </td>
                            <td>
                                @if($token->expires_at && $token->expires_at->isPast())
                                    <span class="api-key-badge api-key-expired">Expired</span>
                                @else
                                    <span class="api-key-badge api-key-active">Active</span>
                                @endif
                            </td>
                            <td>
                                <div class="api-key-actions">
                                    <form action="{{ route('admin.users.api-keys.destroy', [$user, $token]) }}" method="POST">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="btn btn-danger btn-sm" onclick="return confirm('Are you sure you want to revoke this API key?')">Revoke</button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @endif
</div>
@endsection
