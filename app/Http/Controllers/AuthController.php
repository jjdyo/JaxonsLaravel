<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Password;
use App\Notifications\ResetPasswordEmail;
use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Session;
use App\Http\Requests\LoginRequest;
use App\Http\Requests\RegisterRequest;
use App\Http\Requests\UpdateProfileRequest;
use App\Http\Requests\ForgotPasswordRequest;
use App\Http\Requests\ResetPasswordRequest;
use App\Http\Requests\ChangePasswordRequest;

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
     * Show change password form
     */
    public function showChangePasswordForm(): \Illuminate\View\View
    {
        return view('pages.profile_password');
    }

    /**
     * Update the user's profile information
     *
     * @param \App\Http\Requests\UpdateProfileRequest $request The HTTP request containing profile data
     * @return \Illuminate\Http\RedirectResponse Redirect to profile or verification notice
     */
    public function updateProfile(UpdateProfileRequest $request): \Illuminate\Http\RedirectResponse
    {

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

        // Apply changes
        if ($emailChanged) {
            $user->email = $request->email;
            $user->email_verified_at = null;
        }

        $user->save();

        // Send notifications after saving
        if ($emailChanged) {
            $user->sendEmailVerificationNotification();
        }

        if ($emailChanged) {
            return redirect()->route('verification.notice')
                ->with('success', 'Profile updated successfully! Please verify your new email address.');
        }

        return redirect()->route('profile')->with('success', 'Profile updated successfully!');
    }
    /**
     * Process the login request
     *
     * @param \App\Http\Requests\LoginRequest $request The HTTP request containing login credentials
     * @return \Illuminate\Http\RedirectResponse Redirect to intended page or back with errors
     */
    public function processLogin(LoginRequest $request): \Illuminate\Http\RedirectResponse
    {
        /** @var string|null $email */
        // @phpstan-ignore-next-line
        $email = $request->input('email', '');

        if (RateLimiter::tooManyAttempts('login:'.$email, 5)) {
            return back()->withErrors(['email' => 'Too many login attempts. Try again later.']);
        }
        $credentials = $request->validated();

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
     * @param \App\Http\Requests\RegisterRequest $request The HTTP request containing registration data
     * @return \Illuminate\Http\RedirectResponse Redirect to email verification notice
     */
    public function processRegister(RegisterRequest $request): \Illuminate\Http\RedirectResponse
    {
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
     * @param \App\Http\Requests\ForgotPasswordRequest $request The HTTP request containing the user's email
     * @return \Illuminate\Http\RedirectResponse Redirect back with status or errors
     */
    public function sendResetLinkEmail(ForgotPasswordRequest $request): \Illuminate\Http\RedirectResponse
    {
        /** @var array<string, string> $emailData */
        // @phpstan-ignore-next-line
        $emailData = $request->only('email');

        // Capture the generated URL for logging using our custom notification hook
        $generatedUrl = null;
        ResetPasswordEmail::createUrlUsing(function ($user, string $token) use (&$generatedUrl) {
            $generatedUrl = route('password.reset', [
                'token' => $token,
            ]);

            Log::channel('web')->info('Password reset URL generated', [
                'email' => $user->email,
                'url'   => $generatedUrl,
            ]);

            return $generatedUrl;
        });

        $status = Password::sendResetLink($emailData);


        // Define the constant value if PHPStan can't find it
        $resetLinkSent = defined('Illuminate\\Support\\Facades\\Password::RESET_LINK_SENT')
            ? Password::RESET_LINK_SENT
            : 'passwords.sent';

        // Log outcome
        Log::channel('web')->info('Password reset link dispatch status', [
            'email'  => $emailData['email'] ?? null,
            'status' => $status,
            'url'    => $generatedUrl,
        ]);

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
    public function showResetPasswordForm(Request $request, string $token): \Illuminate\View\View
    {
        return view('auth.reset-password', [
            'token' => $token,
            'email' => $request->query('email'),
        ]);
    }

    /**
     * Reset the user's password
     *
     * @param \App\Http\Requests\ResetPasswordRequest $request The HTTP request containing reset credentials
     * @return \Illuminate\Http\RedirectResponse Redirect to login page or back with errors
     */
    public function resetPassword(ResetPasswordRequest $request): \Illuminate\Http\RedirectResponse
    {
        $status = Password::reset(
            $request->only('email', 'password', 'password_confirmation', 'token'),
            function ($user, $password) {
                $user->forceFill([
                    'password' => Hash::make($password)
                ])->setRememberToken(Str::random(60));

                $user->save();

                event(new PasswordReset($user));
            }
        );

        return $status === Password::PASSWORD_RESET
            ? redirect()->route('login')->with('status', __($status))
            : back()->withErrors(['email' => [__($status)]]);
    }

    /**
     * Verify and apply a pending password change.
     */
    /**
     * Handle the password change request (separate from profile update).
     */
    public function processChangePassword(ChangePasswordRequest $request): \Illuminate\Http\RedirectResponse
    {
        /** @var \App\Models\User $user */
        $user = Auth::user();

        // Use Laravel's standard password reset broker to send a reset link
        $status = Password::sendResetLink(['email' => $user->email]);

        // Define the constant value if PHPStan can't find it
        $resetLinkSent = defined('Illuminate\\Support\\Facades\\Password::RESET_LINK_SENT')
            ? Password::RESET_LINK_SENT
            : 'passwords.sent';

        if ($status === $resetLinkSent) {
            return redirect()->route('profile')->with('success', 'We have emailed you a link to reset your password. Please check your inbox.');
        }

        return redirect()->route('profile')->with('error', __($status));
    }
}
