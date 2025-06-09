@extends('layouts.app')

@section('styles')
    <link rel="stylesheet" href="{{ asset('css/auth.css') }}">
@endsection

@section('title', 'Forgot Password')

@section('content')
    <div class="login-container">
        <h2>Forgot Password</h2>

        @if (session('status'))
            <div class="alert alert-success">
                {!! session('status') !!}
            </div>
        @endif

        <p>
            Forgot your password? No problem. Just let us know your email address and we will email you a password reset link.
        </p>

        <form method="POST" action="{{ route('password.email') }}">
            @csrf

            <div class="form-group">
                <label for="email">Email Address</label>
                <input type="email" id="email" name="email" value="{{ old('email') }}" required autofocus>
                @error('email')
                    <div class="error-messages">
                        <p>{{ $message }}</p>
                    </div>
                @enderror
            </div>

            <button type="submit">Send Password Reset Link</button>
        </form>

        <div class="login-links">
            <a href="{{ route('login') }}">Back to Login</a>
        </div>
    </div>
@endsection
