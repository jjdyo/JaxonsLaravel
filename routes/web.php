<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Foundation\Auth\EmailVerificationRequest;
use Illuminate\Http\Request;
use App\Http\Controllers\PageController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\DocsController;

Route::get('/', [PageController::class, 'home'])->name('home');
Route::get('/about', [PageController::class, 'about'])->name('about');
Route::middleware(['auth', 'permission:view contact page url'])->group(function () {
    Route::get('/contact', [PageController::class, 'contact'])->name('contact');
});
Route::get('/user', [AuthController::class, 'showLoginForm'])->name('login');
Route::post('/user', [AuthController::class, 'processLogin'])->name('login.process');
Route::post('/logout', [AuthController::class, 'logout'])->name('logout')->middleware('auth');
Route::get('/register', [AuthController::class, 'showRegisterForm'])->name('register');
Route::post('/register', [AuthController::class, 'processRegister'])->name('register.process');
Route::get('/profile', [AuthController::class, 'profile'])->middleware(['auth', 'verified'])->name('profile');
Route::get('/profile/edit', [AuthController::class, 'editProfile'])->middleware(['auth', 'verified'])->name('profile.edit');
Route::put('/profile/edit', [AuthController::class, 'updateProfile'])->middleware(['auth', 'verified'])->name('profile.update');
Route::get('/email/verify', function () {
    return view('auth.verify-email');
})->middleware('auth')->name('verification.notice');
Route::get('/email/verify/{id}/{hash}', function (EmailVerificationRequest $request) {
    $request->fulfill();
    return redirect('/home');
})->middleware(['auth', 'signed'])->name('verification.verify');

Route::post('/email/verification-notification', function (Request $request) {
    $request->user()->sendEmailVerificationNotification();
    return back()->with('message', 'Verification link sent!');
})->middleware(['auth', 'throttle:6,1'])->name('verification.send');

// Documentation routes
Route::get('/docs', [DocsController::class, 'index'])->name('docs.index');
Route::get('/docs/{filename}', [DocsController::class, 'show'])->name('docs.show')->where('filename', '.*');
