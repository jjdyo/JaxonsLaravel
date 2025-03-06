@extends('layouts.app')

@section('styles')
    <link rel="stylesheet" href="{{ asset('css/auth.css') }}">
@endsection

@section('title', 'Login')

@section('content')
    <div class="login-container">
        <h2>Login</h2>

        <form method="POST" action="#">

            <div class="form-group">
                <label for="email">Email Address</label>
                <input type="email" id="email" name="email" required>
            </div>

            <div class="form-group">
                <label for="password">Password</label>
                <input type="password" id="password" name="password" required>
            </div>

            <button type="submit">Login</button>
        </form>

        <div class="login-links">
            <a href="#">Forgot Password?</a>
            <a href="#">Create an Account</a>
        </div>
    </div>
@endsection
