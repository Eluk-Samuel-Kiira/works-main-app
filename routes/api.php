<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

// ─── Existing read-only data routes (consumed by works-web app) ─────────────
use App\Http\Controllers\Web\{ DashboardController, JobsController };

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');

Route::get('/test', function () {
    return response()->json(['message' => 'API is working!']);
});

Route::get('/user-data',                         [DashboardController::class, 'getUserData']);
Route::get('/jobs-data-from-main',               [JobsController::class, 'index']);
Route::get('/jobs-data-from-main/featured',      [JobsController::class, 'featured']);
Route::get('/jobs-data-from-main/urgent',        [JobsController::class, 'urgent']);
Route::get('/popular-searches',                  [JobsController::class, 'popularSearches']);
Route::get('/jobs-data-from-main/{job}',         [JobsController::class, 'show']);
Route::get('/jobs-data-from-main/id/{id}',       [JobsController::class, 'showById']);


// ─── v1 CRUD API ─────────────────────────────────────────────────────────────
use App\Http\Controllers\Api\UserController;
use App\Http\Controllers\Api\Jobs\{
    CompanyController,
    EducationLevelController,
    ExperienceLevelController,
    IndustryController,
    JobCategoryController,
    JobLocationController,
    JobTypeController,
    SalaryRangeController,
};

Route::prefix('v1')->name('api.v1.')->group(function () {

    // Users
    Route::apiResource('users',            UserController::class);

    // Job-related lookups
    Route::apiResource('companies',        CompanyController::class);
    Route::apiResource('industries',       IndustryController::class);
    Route::apiResource('job-categories',   JobCategoryController::class);
    Route::apiResource('job-types',        JobTypeController::class);
    Route::apiResource('job-locations',    JobLocationController::class);
    Route::apiResource('experience-levels', ExperienceLevelController::class);
    Route::apiResource('education-levels', EducationLevelController::class);
    Route::apiResource('salary-ranges',    SalaryRangeController::class);
});
