@extends('layouts.app')

@section('title', 'Your API Tokens')

@section('styles')
    <link rel="stylesheet" href="{{ asset('css/admin/users/api-keys.css') }}">
@endsection

@section('content')
<div class="api-tokens-container">
    <div class="api-token-header">
        <h1>Your API Tokens</h1>
        @if(!$apiKeys->isEmpty())
            <a href="{{ route('api-tokens.create') }}" class="btn btn-primary">Create New Token</a>
        @endif
    </div>

    @if(session('success'))
        <div class="api-key-alert api-key-alert-success">
            {{ session('success') }}

            @if(session('plainTextApiKey'))
                <p><strong>Important:</strong> This API token will only be shown once. Please copy it now:</p>
                <div class="api-key-value">{{ session('plainTextApiKey') }}</div>
            @endif
        </div>
    @endif

    @if($apiKeys->isEmpty())
        <div class="no-tokens-message">
            <p>You don't have any API tokens yet.</p>
            <a href="{{ route('api-tokens.create') }}" class="btn btn-primary">Create a Token</a>
        </div>
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
                                    <a href="{{ route('api-tokens.show', $token) }}" class="btn btn-secondary btn-sm">Details</a>
                                    <form action="{{ route('api-tokens.destroy', $token) }}" method="POST" class="inline-form">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="btn btn-danger btn-sm" onclick="return confirm('Are you sure you want to revoke this API token?')">Revoke</button>
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
