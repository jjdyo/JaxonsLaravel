@extends('layouts.app')

@section('title', 'User Management')

@push('styles')
    <link rel="stylesheet" href="{{ asset('css/admin/users/useradministration.css') }}">
    <link rel="stylesheet" href="{{ asset('css/admin/users/api-keys.css') }}">
@endpush

@section('content')
<div class="user-management">
    <h1>User Management</h1>

    @if(session('success'))
        <div class="alert alert-success">
            {{ session('success') }}
        </div>
    @endif

    <form method="GET" action="{{ route('admin.users.index') }}" class="user-filter-form">
        <label for="filter">View:</label>
        <select id="filter" name="filter">
            <option value="new" {{ (isset($filter) ? $filter : request('filter', 'new')) === 'new' ? 'selected' : '' }}>Newly Created</option>
            <option value="unverified" {{ (isset($filter) ? $filter : request('filter')) === 'unverified' ? 'selected' : '' }}>Unverified</option>
            <option value="az" {{ (isset($filter) ? $filter : request('filter')) === 'az' ? 'selected' : '' }}>A-Z</option>
            <option value="za" {{ (isset($filter) ? $filter : request('filter')) === 'za' ? 'selected' : '' }}>Z-A</option>
            <option value="search" {{ (isset($filter) ? $filter : request('filter')) === 'search' ? 'selected' : '' }}>Search</option>
        </select>

        <input type="text" name="q" id="search-input" class="search-input {{ ((isset($filter) ? $filter : request('filter')) === 'search') ? '' : 'hidden' }}" placeholder="Search name or email..." value="{{ isset($q) ? $q : request('q') }}" />
        <noscript>
            <button type="submit" class="btn">Apply</button>
        </noscript>
    </form>

    <div class="table-responsive">
        <table class="user-table">
            <thead>
                <tr>
                    <th>Name</th>
                    <th>Email</th>
                    <th>Roles</th>
                    <th>Verified</th>
                    <th>Created</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                @foreach($users as $user)
                    <tr>
                        <td>{{ $user->name }}</td>
                        <td>{{ $user->email }}</td>
                        <td>
                            @if($user->getRoleNames()->count() > 0)
                                @foreach($user->getRoleNames() as $role)
                                    <span class="role-badge">{{ $role }}</span>
                                @endforeach
                            @else
                                <span class="role-badge role-none">None</span>
                            @endif
                        </td>
                        <td>
                            @if($user->email_verified_at)
                                <span class="verified-status verified-yes">Yes</span>
                            @else
                                <span class="verified-status verified-no">No</span>
                            @endif
                        </td>
                        <td>{{ $user->created_at->format('M d, Y') }}</td>
                        <td>
                            <div class="user-actions">
                                <a href="{{ route('admin.users.show', $user) }}" class="action-btn">View</a>
                                <a href="{{ route('admin.users.api-keys.index', $user) }}" class="action-btn">API Keys</a>
                            </div>
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
    {{ $users->links('admin.users.pagination') }}
</div>

@push('scripts')
    <script src="{{ asset('js/admin/users/index.js') }}"></script>
@endpush
@endsection
