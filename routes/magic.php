<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Auth\LoginTokenController;
use App\Http\Controllers\Main\{ DashboardController };



// Authentication Routes
Route::middleware('guest')->group(function () {
    // Login Routes
    Route::get('/login', [LoginTokenController::class, 'showLogin'])->name('login');
    Route::post('/login', [LoginTokenController::class, 'sendLoginLink'])->name('auth.send-login-link');
    
    // Register Routes
    // Route::get('/register', [LoginTokenController::class, 'showRegister'])->name('auth.register');
    // Route::post('/register', [LoginTokenController::class, 'register'])->name('auth.register.submit');
    
    // Magic Link Authentication
    Route::get('/login/{token}', [LoginTokenController::class, 'authenticate'])->name('auth.authenticate');
});

// Invalid Token Route (Public)
Route::get('/invalid-token', [LoginTokenController::class, 'invalidToken'])->name('auth.invalid-token');

// Logout Route (Accessible to both authenticated and guest users)
Route::post('/logout', [LoginTokenController::class, 'logout'])->name('auth.logout');



