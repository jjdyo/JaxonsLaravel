<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Password;
use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Session;

class AuthController extends Controller
{
    /**
     * Display the login form
     *
     * @return \Illuminate\View\View The login form view
     */
    public function showLoginForm(): \Illuminate\View\View
    {
        return view('auth.login');
    }

    /**
     * Display the registration form
     *
     * @return \Illuminate\View\View The registration form view
     */
    public function showRegisterForm(): \Illuminate\View\View
    {
        return view('auth.register');
    }

    /**
     * Display the user profile page
     *
     * @return \Illuminate\View\View The profile page view
     */
    public function profile(): \Illuminate\View\View
    {
        return view('pages.profile');
    }

    /**
     * Display the profile edit form
     *
     * @return \Illuminate\View\View The profile edit form view with user data
     */
    public function editProfile(): \Illuminate\View\View
    {
        $user = Auth::user();
        return view('pages.profile_edit', compact('user'));
    }

    /**
     * Update the user's profile information
     *
     * @param \Illuminate\Http\Request $request The HTTP request containing profile data
     * @return \Illuminate\Http\RedirectResponse Redirect to profile or verification notice
     */
    public function updateProfile(Request $request): \Illuminate\Http\RedirectResponse
    {
        $request->validate([
            'name' => 'required|string|max:20|regex:/^[\p{L}\s\'\-]+$/u',
            'email' => 'required|string|email|max:255|unique:users,email,' . Auth::id(),
        ], [
            'name.regex' => 'The name may only contain letters, spaces, apostrophes, and hyphens.',
            'name.max' => 'The name may not be greater than 20 characters.',
            'email.unique' => 'The email address is already taken by another user.',
        ]);

        /** @var \App\Models\User|null $user */
        $user = Auth::user();

        if (!$user) {
            return redirect()->route('login')
                ->with('error', 'You must be logged in to update your profile.');
        }

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
    /**
     * Process the login request
     *
     * @param \Illuminate\Http\Request $request The HTTP request containing login credentials
     * @return \Illuminate\Http\RedirectResponse Redirect to intended page or back with errors
     */
    public function processLogin(Request $request): \Illuminate\Http\RedirectResponse
    {
        /** @var string|null $email */
        // @phpstan-ignore-next-line
        $email = $request->input('email', '');

        if (RateLimiter::tooManyAttempts('login:'.$email, 5)) {
            return back()->withErrors(['email' => 'Too many login attempts. Try again later.']);
        }
        $credentials = $request->validate([
            'email' => 'required|email',
            'password' => 'required'
        ]);

        // Attempt to log the user in securely
        if (Auth::attempt($credentials)) {
            /** @var \Illuminate\Session\Store $session */
            // @phpstan-ignore-next-line
            $session = $request->session();
            $session->regenerate(); // Prevent session fixation attacks
            return redirect()->intended('/');
        }

        return back()->withErrors([
            'email' => 'Invalid email or password'
        ])->onlyInput('email');
    }
    /**
     * Log the user out of the application
     *
     * @param \Illuminate\Http\Request $request The HTTP request
     * @return \Illuminate\Http\RedirectResponse Redirect to home page
     */
    public function logout(Request $request): \Illuminate\Http\RedirectResponse
    {
        auth()->logout();

        /** @var \Illuminate\Session\Store $session */
        // @phpstan-ignore-next-line
        $session = $request->session();
        $session->invalidate();
        $session->regenerateToken();

        return redirect()->route('home');
    }

    /**
     * Process the registration form submission
     *
     * @param \Illuminate\Http\Request $request The HTTP request containing registration data
     * @return \Illuminate\Http\RedirectResponse Redirect to email verification notice
     */
    public function processRegister(Request $request): \Illuminate\Http\RedirectResponse
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

        // Only log to Slack if the webhook URL is configured
        if (config('logging.channels.slack.url')) {
            Log::channel('slack')->info('New user registered', [
                'name' => $user->name,
                'email' => $user->email
            ]);
        }

        // Log the user in automatically after registering
        Auth::login($user);

        // Regenerate session to prevent session fixation attacks
        session()->regenerate();

        // Send email verification notification
        $user->sendEmailVerificationNotification();

        return redirect()->route('verification.notice');
    }

    /**
     * Display the forgot password form
     *
     * @return \Illuminate\View\View The forgot password form view
     */
    public function showForgotPasswordForm(): \Illuminate\View\View
    {
        return view('auth.forgot-password');
    }

    /**
     * Send a password reset link to the given user
     *
     * @param \Illuminate\Http\Request $request The HTTP request containing the user's email
     * @return \Illuminate\Http\RedirectResponse Redirect back with status or errors
     */
    public function sendResetLinkEmail(Request $request): \Illuminate\Http\RedirectResponse
    {
        $request->validate([
            'email' => 'required|email',
        ]);

        /** @var array<string, string> $emailData */
        // @phpstan-ignore-next-line
        $emailData = $request->only('email');

        $status = Password::sendResetLink($emailData);

        // Define the constant value if PHPStan can't find it
        $resetLinkSent = defined('Illuminate\Support\Facades\Password::RESET_LINK_SENT')
            ? Password::RESET_LINK_SENT
            : 'passwords.sent';

        return $status === $resetLinkSent
            ? back()->with(['status' => __($status)])
            : back()->withErrors(['email' => __($status)]);
    }

    /**
     * Display the password reset form
     *
     * @param \Illuminate\Http\Request $request The HTTP request containing the reset token
     * @return \Illuminate\View\View The password reset form view
     */
    public function showResetPasswordForm(Request $request): \Illuminate\View\View
    {
        return view('auth.reset-password', ['request' => $request]);
    }

    /**
     * Reset the user's password
     *
     * @param \Illuminate\Http\Request $request The HTTP request containing reset credentials
     * @return \Illuminate\Http\RedirectResponse Redirect to login page or back with errors
     */
    public function resetPassword(Request $request): \Illuminate\Http\RedirectResponse
    {
        $request->validate([
            'token' => 'required',
            'email' => 'required|email',
            'password' => 'required|min:8|confirmed',
        ]);

        /** @var array<string, string> $credentials */
        // @phpstan-ignore-next-line
        $credentials = $request->only('email', 'password', 'password_confirmation', 'token');

        $status = Password::reset(
            $credentials,
            function ($user, $password) {
                $user->forceFill([
                    'password' => Hash::make($password)
                ])->setRememberToken(Str::random(60));

                $user->save();

                event(new PasswordReset($user));
            }
        );

        // Define the constant value if PHPStan can't find it
        $passwordReset = defined('Illuminate\Support\Facades\Password::PASSWORD_RESET')
            ? Password::PASSWORD_RESET
            : 'passwords.reset';

        // Ensure $status is a string before using it with the __ function
        if (!is_string($status)) {
            $status = '';
        }

        return $status === $passwordReset
            ? redirect()->route('login')->with('status', __($status))
            : back()->withErrors(['email' => [__($status)]]);
    }
}
