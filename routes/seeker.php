<?php
// routes/api.php

use App\Http\Controllers\Api\Payments\PaymentController;

use App\Http\Controllers\Api\Seeker\{ 
    SeekerCVController,
    RecommendationController,
};

// Protected routes (require authentication)
Route::middleware(['auth:sanctum'])->group(function () {
    
    Route::prefix('seeker')->group(function () {
        Route::get('/cv', [SeekerCVController::class, 'show']);
        Route::post('/cv', [SeekerCVController::class, 'store']);
        Route::post('/cv/parse', [SeekerCVController::class, 'parseCV']);  // NEW
        Route::delete('/cv', [SeekerCVController::class, 'destroy']);
        Route::post('/cv/upload', [SeekerCVController::class, 'uploadFile']);

        Route::get( '/recommendations',         [RecommendationController::class, 'getRecommendations']);
        Route::post('/recommendations/refresh', [RecommendationController::class, 'refresh']);
    });


    
});


// ── IPN — NO auth, Pesapal servers call this directly ────────────────────
Route::prefix('v1/payments')->group(function () {
    Route::match(['get', 'post'], '/ipn', [PaymentController::class, 'ipn']);
});

Route::middleware(['auth:sanctum'])->prefix('v1')->group(function () {

    Route::prefix('payments')->group(function () {
        Route::post('/initiate',             [PaymentController::class, 'initiate']);
        Route::get('/callback',              [PaymentController::class, 'callback']);
        Route::get('/status/{reference}',    [PaymentController::class, 'status']);
    });
});




use App\Http\Controllers\Api\CV\CVEnhancementController;

Route::middleware(['auth:sanctum'])->prefix('v1/cv-enhancement')->group(function () {
    Route::post('/review', [CVEnhancementController::class, 'review']);
    Route::post('/rewrite', [CVEnhancementController::class, 'rewrite']);
    Route::post('/cover-letter', [CVEnhancementController::class, 'coverLetter']);
    Route::get('/history', [CVEnhancementController::class, 'history']);
    Route::get('/download/{id}', [CVEnhancementController::class, 'download']);
});


use App\Http\Controllers\Api\Payments\PaymentPlanWebController;
Route::prefix('v1')->group(function () {
    Route::get('/payment-plans', [PaymentPlanWebController::class, 'getPlans']);
});

Route::middleware(['auth:sanctum'])->prefix('v1/subscription')->group(function () {
    Route::get('/status', [PaymentPlanWebController::class, 'status']);
});