@extends('layouts.app')

@push('styles')
    <link rel="stylesheet" href="{{ asset('css/auth.css') }}">
@endpush

@section('title', 'Verify Email')

@section('content')
    <div class="login-container">
        <h2>Verify Your Email Address</h2>

        @if (session('message'))
            <div class="alert alert-success">
                {{ session('message') }}
            </div>
        @endif

        <p>
            Thanks for signing up! Before getting started, could you verify your email address by clicking on the link we just emailed to you?
            If you didn't receive the email, we will gladly send you another.
        </p>

        <form method="POST" action="{{ route('verification.send') }}">
            @csrf

            <button type="submit">Resend Verification Email</button>
        </form>

        <div class="login-links">
            <a href="{{ route('home') }}">Back to Home</a>
        </div>
    </div>
@endsection
