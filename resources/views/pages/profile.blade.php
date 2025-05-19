@extends('layout')

@section('title', 'Welcome')

@section('content')
    <div class="profile-dashboard">
        <h2>Welcome, {{ Auth::user()->name ?? Auth::user()->email }}!</h2>
        <p>This is your dashboard. Here you can view personalized info, upcoming stuff, etc.</p>

        {{-- Example: Show user email --}}
        <p><strong>Email:</strong> {{ Auth::user()->email }}</p>
        <p><strong>Account creation date: {{ Auth::user()->created_at ? Auth::user()->created_at->format('F j, Y') : 'Unknown' }}
            </strong></p>

        {{-- Add more personalized content here --}}
    </div>
@endsection
