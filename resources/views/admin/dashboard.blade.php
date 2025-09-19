@extends('layouts.app')

@section('title', 'Admin Dashboard')

@section('styles')
@endsection

@section('content')
<div class="admin-dashboard">
    <h1>Admin Dashboard</h1>

    @if(session('success'))
        <div class="alert alert-success">
            {{ session('success') }}
        </div>
    @endif

    <div class="dashboard-cards">
        <div class="card">
            <h2>User Management</h2>
            <p>Manage users, roles, and permissions</p>
            <a href="{{ route('admin.users.index') }}" class="button button-warning link-hover">View Users</a>
        </div>

        <div class="card">
            <h2>System Logs</h2>
            <p>View application logs</p>
            <a href="{{ route('admin.system-logs.index') }}" class="button button-warning link-hover">View Logs</a>
        </div>

        <div class="card">
            <h2>Settings</h2>
            <p>Configure application settings</p>
            <a href="#" class="button button-warning link-hover">View Settings</a>
        </div>
    </div>
</div>
@endsection
