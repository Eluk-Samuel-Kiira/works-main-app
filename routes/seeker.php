<?php
// routes/api.php

use App\Http\Controllers\Api\Payments\PaymentController;

use App\Http\Controllers\Api\Seeker\{ 
    SeekerCVController,
    SeekerCVPDFController,
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

    
    // PDF Routes
    // Route::post('/seeker/cv/generate-pdf', [SeekerCVPDFController::class, 'generatePDF']);
    // Route::post('/seeker/cv/preview-pdf', [SeekerCVPDFController::class, 'previewPDF']);
    
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
