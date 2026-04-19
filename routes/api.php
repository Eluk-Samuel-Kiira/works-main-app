<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\{ UserController };
use App\Http\Controllers\Api\Jobs\{
    CompanyController,
    EducationLevelController,
    ExperienceLevelController,
    IndustryController,
    JobCategoryController,
    JobLocationController,
    JobTypeController,
    SalaryRangeController,
    SocialMediaController,
    JobPostController  // Add this import
};

// ─── Existing read-only data routes (consumed by works-web app) ─────────────
use App\Http\Controllers\Web\{ DashboardController, JobsController, JobsCategoryController };

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');

Route::get('/test', function () {
    return response()->json(['message' => 'API is working!']);
});

Route::prefix('v2')->name('api.v1.')->group(function () {
    Route::get('/user-data',                         [DashboardController::class, 'getUserData']);
    Route::get('/jobs-data-from-main',               [JobsController::class, 'index']);
    Route::get('/jobs-data-from-main/featured',      [JobsController::class, 'featured']);
    Route::get('/jobs-data-from-main/urgent',        [JobsController::class, 'urgent']);
    Route::get('/popular-searches',                  [JobsController::class, 'popularSearches']);
    Route::get('/company-jobs',                      [JobsCategoryController::class, 'companyJobs']);
    Route::get('/job-by-category',                   [JobsController::class, 'jobCategory']);
    Route::get('/jobs-data-from-main/{job}',         [JobsController::class, 'show']);
    Route::get('/jobs-data-from-main/id/{id}',       [JobsController::class, 'showById']);
    Route::post('/report-missing-link',              [JobsController::class, 'reportMissingLink'])->name('report.missing.link');
    Route::post('/jobs/{job}/increment-share',       [JobsController::class, 'incrementShare'])->name('jobs.increment.share');
    Route::post('/jobs/{job}/increment-application', [JobsController::class, 'incrementApplication'])->name('jobs.increment.application');

    Route::get('/social-media', [SocialMediaController::class, 'indexPublic']);
});

// ─── v1 CRUD API ─────────────────────────────────────────────────────────────
Route::prefix('v1')->name('api.v1.')->group(function () {

    // Users    
    Route::get('users/list', [UserController::class, 'list']);
    Route::apiResource('users', UserController::class);

    // Job-related lookups
    Route::apiResource('companies',        CompanyController::class);
    Route::apiResource('industries',       IndustryController::class);
    Route::apiResource('job-categories',   JobCategoryController::class);
    Route::apiResource('job-types',        JobTypeController::class);
    Route::apiResource('job-locations',    JobLocationController::class);
    Route::apiResource('experience-levels', ExperienceLevelController::class);
    Route::apiResource('education-levels', EducationLevelController::class);
    Route::apiResource('salary-ranges',    SalaryRangeController::class);

    // ── Static endpoints FIRST (before the resource so slugs don't clash) ──
    Route::get('social-media/platforms',               [SocialMediaController::class, 'platforms']);
    Route::get('social-media/by-location/{locationId}',[SocialMediaController::class, 'byLocation']);
    Route::apiResource('social-media', SocialMediaController::class)
         ->parameters(['social-media' => 'social_media_platform']);
});

// =============================================================================
// JOB POSTS ROUTES - IMPORTANT: Static routes MUST come BEFORE apiResource
// =============================================================================
Route::prefix('v1')->group(function () {
    
    // -------------------------------------------------------------------------
    // STATIC ROUTES FIRST - These must be defined BEFORE the resource route
    // to avoid being captured as {jobPost} parameters
    // -------------------------------------------------------------------------
    
    // Indexing stats (static)
    Route::get('/job-posts/indexing-stats', [JobPostController::class, 'indexingStats']);
    
    // Manual index (static)
    Route::post('/job-posts/manual-index', [JobPostController::class, 'manualIndex']);
    
    // Check duplicate (static)
    Route::post('/job-posts/check-duplicate', [JobPostController::class, 'checkDuplicate']);
    
    // -------------------------------------------------------------------------
    // RESOURCE ROUTE - Dynamic routes go AFTER static ones
    // -------------------------------------------------------------------------
    Route::apiResource('job-posts', JobPostController::class)->parameters([
        'job-posts' => 'jobPost'
    ]);
    
    // -------------------------------------------------------------------------
    // Job Posts — Status / Action endpoints (these use the {jobPost} parameter)
    // -------------------------------------------------------------------------
    Route::prefix('job-posts/{jobPost}')->group(function () {
        Route::patch('activate',   [JobPostController::class, 'activate']);
        Route::patch('deactivate', [JobPostController::class, 'deactivate']);
        Route::patch('verify',     [JobPostController::class, 'verify']);
        Route::patch('feature',    [JobPostController::class, 'feature']);
        Route::patch('urgent',     [JobPostController::class, 'markUrgent']);
    });

    Route::get('users/list', [UserController::class, 'list']);
})->middleware('auth:sanctum');