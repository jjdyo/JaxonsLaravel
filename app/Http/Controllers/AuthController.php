<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\RateLimiter;

class AuthController extends Controller
{
    public function showLoginForm()
    {
        return view('auth.login');
    }
    // Show the registration form
    public function showRegisterForm()
    {
        return view('auth.register');
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
    // Handle registration form submission
    public function processRegister(Request $request)
    {
        // Validate user input
        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users|max:255',
            'password' => 'required|min:8|confirmed',
        ]);

        // Create user in the database
        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => Hash::make($request->password),
        ]);

        $user->assignRole('user');

        // Log the user in automatically after registering
        Auth::login($user);

        return redirect()->route('home')->with('success', 'Account created successfully!');
    }
}
