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

    public function profile()
    {
        return view('pages.profile');
    }

    public function editProfile()
    {
        $user = Auth::user();
        return view('pages.profile_edit', compact('user'));
    }

    public function updateProfile(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:20|regex:/^[\p{L}\s\'\-]+$/u',
            'email' => 'required|string|email|max:255|unique:users,email,' . Auth::id(),
        ], [
            'name.regex' => 'The name may only contain letters, spaces, apostrophes, and hyphens.',
            'name.max' => 'The name may not be greater than 20 characters.',
            'email.unique' => 'The email address is already taken by another user.',
        ]);

        $user = Auth::user();

        // Sanitize the name: trim whitespace and normalize spaces
        $sanitizedName = trim(preg_replace('/\s+/', ' ', $request->name));

        $user->name = $sanitizedName;

        // Check if email has changed
        $emailChanged = $request->email !== $user->email;

        if ($emailChanged) {
            // Update email and reset verification status
            $user->email = $request->email;
            $user->email_verified_at = null;
            $user->save();

            // Send verification email
            $user->sendEmailVerificationNotification();

            return redirect()->route('verification.notice')
                ->with('success', 'Profile updated successfully! Please verify your new email address.');
        } else {
            $user->save();
            return redirect()->route('profile')->with('success', 'Profile updated successfully!');
        }
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
    //Logout function
    public function logout(Request $request)
    {
        auth()->logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('home');
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

        // Send email verification notification
        $user->sendEmailVerificationNotification();

        return redirect()->route('verification.notice');
    }
}
