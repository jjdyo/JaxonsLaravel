<?php

use Illuminate\Support\Facades\Route;
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
Route::get('/profile', [AuthController::class, 'profile'])->middleware('auth')->name('profile');
Route::get('/profile/edit', [AuthController::class, 'editProfile'])->middleware('auth')->name('profile.edit');
Route::put('/profile/edit', [AuthController::class, 'updateProfile'])->middleware('auth')->name('profile.update');

// Documentation routes
Route::get('/docs', [DocsController::class, 'index'])->name('docs.index');
Route::get('/docs/{filename}', [DocsController::class, 'show'])->name('docs.show')->where('filename', '.*');
