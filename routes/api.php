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

use App\Services\SitemapPingService;
use App\Services\GoogleIndexingService;
use App\Models\Job\JobPost;
 
// ── Ping stats
Route::get('/v1/seo/ping-stats', function () {
    return response()->json([
        'data' => app(SitemapPingService::class)->getStats()
    ]);
});
 
// ── Google indexing stats
Route::get('/v1/seo/indexing-stats', function () {
    return response()->json([
        'data' => app(GoogleIndexingService::class)->getStats()
    ]);
});
 
// ── Ping a single job by slug
Route::post('/v1/seo/ping-job/{slug}', function (string $slug) {
    $job = JobPost::where('slug', $slug)->firstOrFail();
    $result = app(SitemapPingService::class)->pingFailedJobs([$job->id]);
    return response()->json([
        'success' => $result['success'] > 0,
        'message' => $result['message'] ?? ($result['success'] > 0 ? 'Pinged successfully' : 'Ping failed'),
        'status'  => $result['status'] ?? 0,
    ]);
});
 
// ── Google index a single job by slug
Route::post('/v1/seo/index-job/{slug}', function (string $slug) {
    $job    = JobPost::where('slug', $slug)->firstOrFail();
    $result = app(GoogleIndexingService::class)->submitJob($job->id);
    $stats  = app(GoogleIndexingService::class)->getStats();
    return response()->json([
        'success'    => $result['success'],
        'message'    => $result['message'],
        'status'     => $result['status']  ?? 0,
        'quota_used' => $stats['quota_used'],
        'quota_left' => $stats['quota_remaining'],
    ]);
});
 
// ── Bulk ping (failed or all)
Route::post('/v1/seo/bulk-ping', function (Request $request) {
    $mode   = $request->input('mode', 'failed'); // 'failed' | 'all'
    $result = app(SitemapPingService::class)->pingFailedJobs();
    return response()->json(['data' => $result]);
});
 
// ── Bulk Google index
Route::post('/v1/seo/bulk-index', function (Request $request) {
    $mode = $request->input('mode', 'new'); // 'new' | 'priority'
 
    $query = JobPost::where('is_active', true)->where('deadline', '>=', now());
 
    if ($mode === 'new') {
        $query->where(function($q) {
            $q->whereNull('submitted_to_indexing')
              ->orWhere('submitted_to_indexing', false);
        });
    } elseif ($mode === 'priority') {
        $query->where('is_featured', true)->where(function($q) {
            $q->whereNull('submitted_to_indexing')
              ->orWhere('submitted_to_indexing', false);
        });
    }
 
    $jobIds = $query->limit(200)->pluck('id')->toArray();
    $result = app(GoogleIndexingService::class)->submitBatch($jobIds);
    return response()->json(['data' => $result]);
});