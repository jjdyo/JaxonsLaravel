@extends('layouts.app')

@section('title', 'Welcome')

@section('content')
    <div class="profile-dashboard container-shadow">
        @include('partials.alerts')

        <h2>Welcome, {{ Auth::user()->name ?? Auth::user()->email }}!</h2>
        <p>This is your dashboard. Here you can view personalized info, upcoming stuff, etc.</p>

        <p><strong>Email:</strong> {{ Auth::user()->email }}</p>
        <p><strong>Account creation date: {{ Auth::user()->created_at ? Auth::user()->created_at->format('F j, Y') : 'Unknown' }}
            </strong></p>

        <div class="mt-3">
            <p><strong>Permissions:</strong>
                @php($myPerms = Auth::user()->getPermissionNames())
                @if($myPerms->count() > 0)
                    @foreach($myPerms as $perm)
                        <span class="role-badge">{{ $perm }}</span>
                    @endforeach
                @else
                    <span class="role-badge role-none">None</span>
                @endif
            </p>
        </div>

        <div class="mt-4">
            <a href="{{ route('profile.edit') }}" class="button button-warning link-hover" role="button">Edit Profile</a>
            <a href="{{ route('profile.password.edit') }}" class="button button-warning link-hover" role="button">Change Password</a>
        </div>
    </div>
@endsection
