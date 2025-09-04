@extends('layouts.app')

@section('title', 'Admin Dashboard')

@section('styles')
    <link rel="stylesheet" href="{{ asset('css/admin/dashboard.css') }}">
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
            <a href="{{ route('admin.users.index') }}" class="btn">View Users</a>
        </div>

        <div class="card">
            <h2>System Logs</h2>
            <p>View application logs</p>
            <a href="{{ route('admin.system-logs.index') }}" class="btn">View Logs</a>
        </div>

        <div class="card">
            <h2>Settings</h2>
            <p>Configure application settings</p>
            <a href="#" class="btn">View Settings</a>
        </div>
    </div>
</div>
@endsection
