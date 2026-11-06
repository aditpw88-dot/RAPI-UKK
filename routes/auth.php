<?php

use App\Http\Controllers\Auth\AuthenticatedSessionController;
use App\Http\Controllers\Auth\ConfirmablePasswordController;
use App\Http\Controllers\Auth\CustomPasswordController; // <-- Ditambahkan di sini
use App\Http\Controllers\Auth\NewPasswordController;
use App\Http\Controllers\Auth\PasswordController;
use App\Http\Controllers\Auth\RegisteredUserController;
use App\Http\Controllers\EventController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\UserController;
use Illuminate\Support\Facades\Route;

Route::get('login', [AuthenticatedSessionController::class, 'create'])
    ->name('login');

Route::middleware('guest')->group(function () {
    Route::get('register', [RegisteredUserController::class, 'create'])
        ->name('register');

    Route::post('register', [RegisteredUserController::class, 'store']);

    Route::post('login', [AuthenticatedSessionController::class, 'store']);

    Route::get('forgot-password', fn () => redirect()->route('password.reset'))
        ->name('password.request');

    // Diubah untuk mengirim OTP via CustomPasswordController
    Route::post('forgot-password', [CustomPasswordController::class, 'sendOtp'])
        ->middleware('throttle:6,1')
        ->name('password.email');

    Route::get('reset-password', [CustomPasswordController::class, 'showResetForm'])
        ->name('password.reset');

    Route::post('reset-password/verify-otp', [CustomPasswordController::class, 'verifyOtp'])
        ->name('password.verify-otp');

    Route::post('reset-password', [CustomPasswordController::class, 'resetPassword'])
        ->name('password.store');
});

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');

    Route::get('confirm-password', [ConfirmablePasswordController::class, 'show'])
        ->name('password.confirm');

    Route::post('confirm-password', [ConfirmablePasswordController::class, 'store']);

    Route::put('password', [PasswordController::class, 'update'])->name('password.update');

    Route::post('logout', [AuthenticatedSessionController::class, 'destroy'])
        ->name('logout');

    Route::get('events/create', [EventController::class, 'create'])->name('events.create');
    Route::post('events', [EventController::class, 'store'])->name('events.store');
});

// Admin/Waka User Management Routes
Route::middleware(['auth', 'admin'])->group(function () {
    Route::get('users/import-template', [UserController::class, 'downloadImportTemplate'])
        ->name('users.import-template');
    Route::post('users/import', [UserController::class, 'import'])
        ->name('users.import');
    Route::resource('users', UserController::class);
});