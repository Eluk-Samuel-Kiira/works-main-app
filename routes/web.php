<?php

use App\Http\Controllers\ProfileController;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Main\{ DashboardController };
use App\Http\Controllers\Settings\{ ArtisanCommandController };


Route::middleware('guest')->group(function () {
    Route::get('/', [DashboardController::class, 'index'])->name('home.welcome');
});

// Protected Routes (Require Authentication)
Route::middleware('auth')->group(function () {
    // Dashboard
    Route::get('/dashboard', [DashboardController::class, 'dashboard'])->name('dashboard');
    Route::get('/ai-posting', [DashboardController::class, 'aiPosting'])->name('ai-posting');
    Route::get('/social-media-platforms', [DashboardController::class, 'socialMediaPlatform'])->name('social-media');
});

Route::middleware(['auth'])->prefix('admin')->group(function () {
    Route::get('/artisan',       [ArtisanCommandController::class, 'index'])->name('artisan.index');
    Route::post('/artisan/run',  [ArtisanCommandController::class, 'run'])->name('artisan.run');
});

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

use App\Http\Controllers\Main\{ AiJobController };

Route::middleware(['auth'])->group(function () {
    Route::post('/ai/extract-job', [AiJobController::class, 'extractJobData'])->name('ai.extract-job');
    Route::post('/ai/extract-image', [AiJobController::class, 'extractFromImage'])->name('ai.extract-image');
    Route::post('/ai/enhance-field', [AiJobController::class, 'enhanceField'])->name('ai.enhance-field');
    Route::post('/ai/generate-from-title', [AiJobController::class, 'generateFromTitle'])->name('ai.generate-from-title');
});

Route::get('/clear-cache-temp', function () {
    Artisan::call('optimize:clear');
    return 'Done';
});


// Fallback Route (404)
Route::fallback(function () {
    return response()->view('errors.404', [], 404);
});


require __DIR__.'/auth.php';
require __DIR__.'/magic.php';
require __DIR__.'/jobs.php';
