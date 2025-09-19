@extends('layouts.app')

@section('title', 'API Token Details')

@push('styles')
    <link rel="stylesheet" href="{{ asset('css/admin/users/api-keys.css') }}">
@endpush

@section('content')
<div class="api-tokens-container container-shadow">
    <h1>API Token Details</h1>

    <div class="token-details">
        <div class="token-detail-row">
            <div class="token-detail-label">Name:</div>
            <div class="token-detail-value">{{ $token->name }}</div>
        </div>

        <div class="token-detail-row">
            <div class="token-detail-label">Created:</div>
            <div class="token-detail-value">{{ $token->created_at->format('M d, Y H:i') }}</div>
        </div>

        <div class="token-detail-row">
            <div class="token-detail-label">Last Used:</div>
            <div class="token-detail-value">
                @if($token->last_used_at)
                    {{ $token->last_used_at->format('M d, Y H:i') }}
                @else
                    Never
                @endif
            </div>
        </div>

        <div class="token-detail-row">
            <div class="token-detail-label">Expires:</div>
            <div class="token-detail-value">
                @if($token->expires_at)
                    {{ $token->expires_at->format('M d, Y') }}
                @else
                    Never
                @endif
            </div>
        </div>

        <div class="token-detail-row">
            <div class="token-detail-label">Status:</div>
            <div class="token-detail-value">
                @if($token->expires_at && $token->expires_at->isPast())
                    <span class="api-key-badge api-key-expired">Expired</span>
                @else
                    <span class="api-key-badge api-key-active">Active</span>
                @endif
            </div>
        </div>

        <div class="token-detail-row">
            <div class="token-detail-label">Scopes:</div>
            <div class="token-detail-value">
                @if(empty($token->abilities) || (count($token->abilities) === 1 && $token->abilities[0] === '*'))
                    <span class="api-key-badge">All Permissions</span>
                @else
                    <div class="scope-badges-container">
                        @foreach($token->abilities as $scope)
                            <span class="api-key-badge scope-badge">
                                {{ config('api-scopes.scopes.' . $scope, $scope) }}
                            </span>
                        @endforeach
                    </div>
                @endif
            </div>
        </div>
    </div>

    <div class="token-actions">
        <a href="{{ route('api-tokens.index') }}" class="btn btn-primary">Back to List</a>

        <form action="{{ route('api-tokens.destroy', $token) }}" method="POST">
            @csrf
            @method('DELETE')
            <button type="submit" class="btn btn-danger" onclick="return confirm('Are you sure you want to revoke this API token? This action cannot be undone.')">Revoke Token</button>
        </form>
    </div>
</div>
@endsection
