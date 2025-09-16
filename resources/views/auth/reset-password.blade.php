@extends('layouts.app')

@push('styles')
    <link rel="stylesheet" href="{{ asset('css/auth.css') }}">
@endpush

@section('title', 'Reset Password')

@section('content')
    <div class="login-container">
        <h2>Reset Password</h2>

        @if ($errors->any())
            <div class="error-messages">
                @foreach ($errors->all() as $error)
                    <p>{{ $error }}</p>
                @endforeach
            </div>
        @endif

        <form method="POST" action="{{ route('password.update') }}">
            @csrf

            <!-- Password Reset Token -->
            <input type="hidden" name="token" value="{{ $password_callback }}">

            <div class="form-group">
                <label for="email">Email Address</label>
                <input type="email" id="email" name="email" value="{{ old('email', $email) }}" required autofocus>
                @error('email')
                    <div class="error-messages">
                        <p>{{ $message }}</p>
                    </div>
                @enderror
            </div>

            <div class="form-group">
                <label for="password">New Password</label>
                <input type="password" id="password" name="password" required>
            </div>

            <div class="form-group">
                <label for="password_confirmation">Confirm New Password</label>
                <input type="password" id="password_confirmation" name="password_confirmation" required>
            </div>

            <button type="submit">Reset Password</button>
        </form>

        <div class="login-links">
            <a href="{{ route('login') }}">Back to Login</a>
        </div>
    </div>
@endsection
