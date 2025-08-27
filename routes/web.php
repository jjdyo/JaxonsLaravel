<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Foundation\Auth\EmailVerificationRequest;
use Illuminate\Http\Request;
use App\Http\Controllers\PageController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\DocsController;
use App\Http\Controllers\UserManagementController;
use App\Http\Controllers\ApiKeyController;

/*
|--------------------------------------------------------------------------
| Public Routes
|--------------------------------------------------------------------------
|
| These routes are publicly accessible without authentication
|
*/

// Basic page routes
Route::get('/', [PageController::class, 'home'])->name('home');
Route::get('/about', [PageController::class, 'about'])->name('about');


// Documentation routes
Route::get('/docs', [DocsController::class, 'index'])->name('docs.index');
Route::get('/docs/{filename}', [DocsController::class, 'show'])
    ->name('docs.show')
    ->where('filename', '[a-zA-Z0-9\-_\/\s%.]+');

/*
|--------------------------------------------------------------------------
| Authentication Routes
|--------------------------------------------------------------------------
|
| Routes for login, registration, and password management
|
*/

// Login routes
Route::get('/user', [AuthController::class, 'showLoginForm'])->name('login');
Route::post('/user', [AuthController::class, 'processLogin'])->name('login.process');

// Registration routes
Route::get('/register', [AuthController::class, 'showRegisterForm'])->name('register');
Route::post('/register', [AuthController::class, 'processRegister'])->name('register.process');

// Password reset routes (guest only)
Route::middleware('guest')->group(function () {
    Route::get('/forgot-password', [AuthController::class, 'showForgotPasswordForm'])->name('password.request');
    Route::post('/forgot-password', [AuthController::class, 'sendResetLinkEmail'])->name('password.email');

    Route::middleware('throttle:5,1')->group(function () {
        Route::get('/reset-password/{token}', [AuthController::class, 'showResetPasswordForm'])->name('password.reset');
        Route::post('/reset-password', [AuthController::class, 'resetPassword'])->name('password.update');
    });
});

/*
|--------------------------------------------------------------------------
| Authenticated User Routes
|--------------------------------------------------------------------------
|
| Routes that require user authentication
|
*/

Route::middleware('auth')->group(function () {
    // Logout route
    Route::post('/logout', [AuthController::class, 'logout'])->name('logout');

    // Email verification routes
    Route::get('/email/verify', function () {
        return view('auth.verify-email');
    })->name('verification.notice');

    Route::get('/email/verify/{id}/{hash}', function (EmailVerificationRequest $request) {
        $request->fulfill();
        return redirect()->route('profile');
    })->middleware('signed')->name('verification.verify');

    Route::post('/email/verification-notification', function (Request $request) {
        $request->user()->sendEmailVerificationNotification();
        return back()->with('message', 'Verification link sent!');
    })->middleware('throttle:5,1')->name('verification.send');

    // Routes requiring verified email
    Route::middleware('verified')->group(function () {
        // Profile routes
        Route::get('/profile', [AuthController::class, 'profile'])->name('profile');
        Route::get('/profile/edit', [AuthController::class, 'editProfile'])->name('profile.edit');
        Route::put('/profile/edit', [AuthController::class, 'updateProfile'])->name('profile.update');

        // API token management routes
        Route::prefix('api-tokens')->name('api-tokens.')->group(function () {
            Route::get('/', [ApiKeyController::class, 'userIndex'])->name('index');
            Route::get('/create', [ApiKeyController::class, 'userCreate'])->name('create');
            Route::post('/', [ApiKeyController::class, 'userStore'])->name('store');
            Route::get('/{token}', [ApiKeyController::class, 'userShow'])->name('show');
            Route::delete('/{token}', [ApiKeyController::class, 'userDestroy'])->name('destroy');
        });
    });

    // Permission-protected routes
    Route::middleware('permission:view contact page url')->group(function () {
        Route::get('/contact', [PageController::class, 'contact'])->name('contact');
    });
});

/*
|--------------------------------------------------------------------------
| Admin Routes
|--------------------------------------------------------------------------
|
| Routes for administrative functions (requires admin role)
|
*/

Route::middleware(['auth', 'verified', 'role:admin'])
    ->prefix('admin')
    ->name('admin.')
    ->group(function () {
        // User management routes
        Route::get('/users', [UserManagementController::class, 'listUsers'])->name('users.index');
        Route::get('/users/{user}', [UserManagementController::class, 'showUser'])->name('users.show');
        Route::get('/users/{user}/edit', [UserManagementController::class, 'editUser'])->name('users.edit');
        Route::put('/users/{user}', [UserManagementController::class, 'updateUser'])->name('users.update');
        Route::delete('/users/{user}', [UserManagementController::class, 'deleteUser'])->name('users.destroy');

        // User verification management
        Route::post('/users/{user}/verify', [UserManagementController::class, 'verifyUser'])->name('users.verify');
        Route::post('/users/{user}/unverify', [UserManagementController::class, 'unverifyUser'])->name('users.unverify');

        // User role management
        Route::post('/users/{user}/roles', [UserManagementController::class, 'updateRoles'])->name('users.roles.update');

        // API key management
        Route::prefix('users/{user}/api-keys')->name('users.api-keys.')->group(function () {
            Route::get('/', [ApiKeyController::class, 'index'])->name('index');
            //Route::get('/create', [ApiKeyController::class, 'create'])->name('create');
            //Route::post('/', [ApiKeyController::class, 'store'])->name('store');
            //Route::delete('/{token}', [ApiKeyController::class, 'destroy'])->name('destroy');
        });
    });
