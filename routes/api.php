<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Web\{ DashboardController, JobsController };  // Using Web namespace

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');



// Test route
Route::get('/test', function() {
    return response()->json(['message' => 'API is working!']);
});

// Your user-data route
Route::get('/user-data', [DashboardController::class, 'getUserData']);

// Jobs API routes
Route::get('/jobs-data-from-main', [JobsController::class, 'index']);
Route::get('/jobs-data-from-main/featured', [JobsController::class, 'featured']);
Route::get('/jobs-data-from-main/urgent', [JobsController::class, 'urgent']);
Route::get('/popular-searches', [JobsController::class, 'popularSearches']);

// This will now use slug for model binding automatically
Route::get('/jobs-data-from-main/{job}', [JobsController::class, 'show']);
Route::get('/jobs-data-from-main/id/{id}', [JobsController::class, 'showById']);



