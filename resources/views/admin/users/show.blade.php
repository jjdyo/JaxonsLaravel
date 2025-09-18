@extends('layouts.app')

@section('title', 'User Details: ' . $user->name)

@push('styles')
    <link rel="stylesheet" href="{{ asset('css/admin/users/useradministration.css') }}">
    <link rel="stylesheet" href="{{ asset('css/admin/users/api-keys.css') }}">
@endpush

@section('content')
<div class="user-management">
    <h1>User Details</h1>

    <div class="breadcrumbs">
        <a href="{{ route('admin.users.index') }}">User Management</a> &raquo;
        <span>{{ $user->name }}</span>
    </div>

    @if(session('success'))
        <div class="alert alert-success">
            {{ session('success') }}
        </div>
    @endif

    <div class="user-detail-card">
        <div class="user-detail-header">
            <h2 class="user-detail-title">{{ $user->name }}</h2>
            <div class="user-actions">
                <a href="{{ route('admin.users.edit', $user) }}" class="action-btn">Edit User</a>
                <a href="{{ route('admin.users.api-keys.index', $user) }}" class="action-btn">Manage API Keys</a>
            </div>
        </div>

        <div class="user-detail-grid">
            <div class="user-detail-item">
                <span class="user-detail-label">Email</span>
                <span class="user-detail-value">{{ $user->email }}</span>
            </div>

            <div class="user-detail-item">
                <span class="user-detail-label">Email Verified</span>
                <span class="user-detail-value">
                    @if($user->email_verified_at)
                        <span class="verified-status verified-yes">Yes ({{ $user->email_verified_at->format('M d, Y') }})</span>
                    @else
                        <span class="verified-status verified-no">No</span>
                    @endif
                </span>
            </div>

            <div class="user-detail-item">
                <span class="user-detail-label">Roles</span>
                <span class="user-detail-value">
                    @if($user->getRoleNames()->count() > 0)
                        @foreach($user->getRoleNames() as $role)
                            <span class="role-badge">{{ $role }}</span>
                        @endforeach
                    @else
                        <span class="role-badge role-none">None</span>
                    @endif
                </span>
            </div>

            <div class="user-detail-item">
                <span class="user-detail-label">Created</span>
                <span class="user-detail-value">{{ $user->created_at->format('M d, Y H:i') }}</span>
            </div>

            <div class="user-detail-item">
                <span class="user-detail-label">Last Updated</span>
                <span class="user-detail-value">{{ $user->updated_at->format('M d, Y H:i') }}</span>
            </div>

            <div class="user-detail-item">
                <span class="user-detail-label">API Keys</span>
                <span class="user-detail-value">
                    {{ $user->tokens()->count() }} keys
                    <a href="{{ route('admin.users.api-keys.index', $user) }}" class="action-btn action-btn-secondary">View</a>
                </span>
            </div>
        </div>

        <div class="user-actions">
            @if($user->email_verified_at)
                <form action="{{ route('admin.users.unverify', $user) }}" method="POST">
                    @csrf
                    <button type="submit" class="action-btn action-btn-secondary">Unverify Email</button>
                </form>
            @else
                <form action="{{ route('admin.users.verify', $user) }}" method="POST">
                    @csrf
                    <button type="submit" class="action-btn">Verify Email</button>
                </form>
            @endif

            <form action="{{ route('admin.users.destroy', $user) }}" method="POST" onsubmit="return confirm('Are you sure you want to delete this user?');">
                @csrf
                @method('DELETE')
                <button type="submit" class="action-btn action-btn-danger">Delete User</button>
            </form>
        </div>
    </div>
</div>
@endsection
