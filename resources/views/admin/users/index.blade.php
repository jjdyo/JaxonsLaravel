@extends('layouts.app')

@section('title', 'User Management')

@section('styles')
    <link rel="stylesheet" href="{{ asset('css/admin/users/useradministration.css') }}">
@endsection

@section('content')
<div class="user-management">
    <h1>User Management</h1>

    @if(session('success'))
        <div class="alert alert-success">
            {{ session('success') }}
        </div>
    @endif

    <div class="table-responsive">
        <table class="user-table">
            <thead>
                <tr>
                    <th>Name</th>
                    <th>Email</th>
                    <th>Roles</th>
                    <th>Verified</th>
                    <th>Created</th>
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
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
    {{ $users->links('admin.users.pagination') }}
</div>
@endsection
