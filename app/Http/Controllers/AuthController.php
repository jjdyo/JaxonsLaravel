<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AuthController extends Controller
{
    public function showLoginForm()
    {
        return view('auth.login');
    }

    public function processLogin(Request $request)
    {
        $email = $request->input('email');

        if (RateLimiter::tooManyAttempts('login:'.$email, 5)) {
            return back()->withErrors(['email' => 'Too many login attempts. Try again later.']);
        }
        // Validate user input
        $credentials = $request->validate([
            'email' => 'required|email',
            'password' => 'required'
        ]);

        // Attempt to log the user in securely
        if (Auth::attempt($credentials)) {
            $request->session()->regenerate(); // Prevent session fixation attacks
            return redirect()->intended('/'); // Redirect after login
        }

        return back()->withErrors([
            'email' => 'Invalid email or password'
        ])->onlyInput('email'); // Keep email input but clear password
    }
}
