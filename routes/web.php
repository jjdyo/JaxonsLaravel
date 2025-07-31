<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Foundation\Auth\EmailVerificationRequest;
use Illuminate\Http\Request;
use App\Http\Controllers\PageController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\DocsController;
use App\Http\Controllers\UserManagementController;

/**
 * Public Page Routes
 * These routes handle basic page navigation for the application
 */
// Home page route - Publicly accessible
Route::get('/', [PageController::class, 'home'])->name('home');

// About page route - Publicly accessible
Route::get('/about', [PageController::class, 'about'])->name('about');

/**
 * Protected Page Routes
 * These routes require authentication and specific permissions
 * Mostly for testing purposes
 */
// Contact page - Protected by 'auth' middleware and requires 'view contact page url' permission
Route::middleware(['auth', 'permission:view contact page url'])->group(function () {
    Route::get('/contact', [PageController::class, 'contact'])->name('contact');
});

/**
 * Authentication Routes
 * These routes handle user authentication, registration, and profile management
 */
// Login page - Shows login form (GET) and processes login (POST)
Route::get('/user', [AuthController::class, 'showLoginForm'])->name('login');
Route::post('/user', [AuthController::class, 'processLogin'])->name('login.process');

// Logout route - Protected by 'auth' middleware
Route::post('/logout', [AuthController::class, 'logout'])->name('logout')->middleware('auth');

// Registration routes - Shows registration form (GET) and processes registration (POST)
Route::get('/register', [AuthController::class, 'showRegisterForm'])->name('register');
Route::post('/register', [AuthController::class, 'processRegister'])->name('register.process');

// Profile routes - All protected by 'auth' and 'verified' middleware
// View profile page
Route::get('/profile', [AuthController::class, 'profile'])->middleware(['auth', 'verified'])->name('profile');
// Edit profile page (GET) and update profile (PUT)
Route::get('/profile/edit', [AuthController::class, 'editProfile'])->middleware(['auth', 'verified'])->name('profile.edit');
Route::put('/profile/edit', [AuthController::class, 'updateProfile'])->middleware(['auth', 'verified'])->name('profile.update');

/**
 * Admin Routes
 * These routes handle user administration and require admin role
 */
Route::middleware(['auth', 'verified', 'role:admin'])->prefix('admin')->name('admin.')->group(function () {
    // User management routes
    Route::get('/users', [UserManagementController::class, 'listUsers'])->name('users.index');
    Route::get('/users/{user}', [UserManagementController::class, 'showUser'])->name('users.show');
    Route::get('/users/{user}/edit', [UserManagementController::class, 'editUser'])->name('users.edit');
    Route::put('/users/{user}', [UserManagementController::class, 'updateUser'])->name('users.update');
    Route::delete('/users/{user}', [UserManagementController::class, 'deleteUser'])->name('users.destroy');

    // Optional: User verification management
    Route::post('/users/{user}/verify', [UserManagementController::class, 'verifyUser'])->name('users.verify');
    Route::post('/users/{user}/unverify', [UserManagementController::class, 'unverifyUser'])->name('users.unverify');

    // Optional: User role management
    Route::post('/users/{user}/roles', [UserManagementController::class, 'updateRoles'])->name('users.roles.update');
});

/**
 * Email Verification Routes
 * These routes handle email verification for new user accounts
 */
// Show email verification notice - Protected by 'auth' middleware
Route::get('/email/verify', function () {
    return view('auth.verify-email');
})->middleware('auth')->name('verification.notice');

// Process email verification - Protected by 'auth' and 'signed' middleware
Route::get('/email/verify/{id}/{hash}', function (EmailVerificationRequest $request) {
    $request->fulfill();
    return redirect()->route('profile');
})->middleware(['auth', 'signed'])->name('verification.verify');

// Resend verification email - Protected by 'auth' and 'throttle' middleware
Route::post('/email/verification-notification', function (Request $request) {
    $request->user()->sendEmailVerificationNotification();
    return back()->with('message', 'Verification link sent!');
})->middleware(['auth', 'throttle:5,1'])->name('verification.send');

/**
 * Password Reset Routes
 * These routes handle the password reset flow
 */
// Show forgot password form - Protected by 'guest' middleware (only non-authenticated users)
Route::get('/forgot-password', [AuthController::class, 'showForgotPasswordForm'])->middleware('guest')->name('password.request');

// Process forgot password request - Protected by 'guest' middleware
Route::post('/forgot-password', [AuthController::class, 'sendResetLinkEmail'])->middleware('guest')->name('password.email');

// Show password reset form - Protected by 'guest' and 'throttle' middleware
Route::get('/reset-password/{token}', [AuthController::class, 'showResetPasswordForm'])
    ->middleware(['guest', 'throttle:5,1'])
    ->name('password.reset');

// Process password reset - Protected by 'guest' and 'throttle' middleware
Route::post('/reset-password', [AuthController::class, 'resetPassword'])
    ->middleware(['guest', 'throttle:5,1'])
    ->name('password.update');

/**
 * Documentation Routes
 * These routes handle the documentation system
 */
// Documentation index page - Publicly accessible
Route::get('/docs', [DocsController::class, 'index'])->name('docs.index');

// Documentation detail page - Publicly accessible
// The 'where' constraint restricts to alphanumeric characters, dashes, underscores, forward slashes, spaces, periods, and percent signs
Route::get('/docs/{filename}', [DocsController::class, 'show'])->name('docs.show')->where('filename', '[a-zA-Z0-9\-_\/\s%.]+');
