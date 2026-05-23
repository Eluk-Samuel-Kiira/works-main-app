<?php


use App\Http\Controllers\Api\Seeker\{SeekerCVController, RecommendationController};
use App\Http\Controllers\Api\Payments\PaymentController;

// ── Protected routes ──────────────────────────────────────────────────────
Route::middleware(['auth:sanctum'])->prefix('v1')->group(function () {

    // Seeker
    Route::prefix('seeker')->group(function () {
        Route::get('/cv',                    [SeekerCVController::class, 'show']);
        Route::post('/cv',                   [SeekerCVController::class, 'store']);
        Route::post('/cv/parse',             [SeekerCVController::class, 'parseCV']);
        Route::delete('/cv',                 [SeekerCVController::class, 'destroy']);
        Route::post('/cv/upload',            [SeekerCVController::class, 'uploadFile']);
        Route::get('/recommendations',       [RecommendationController::class, 'getRecommendations']);
        Route::post('/recommendations/refresh', [RecommendationController::class, 'refresh']);
    });

    // Payments (authenticated)
    Route::prefix('payments')->group(function () {
        Route::post('/initiate',             [PaymentController::class, 'initiate']);
        Route::get('/callback',              [PaymentController::class, 'callback']);
        Route::get('/status/{reference}',    [PaymentController::class, 'status']);
    });
});

// ── IPN — NO auth, Pesapal servers call this directly ────────────────────
Route::prefix('v1/payments')->group(function () {
    Route::match(['get', 'post'], '/ipn', [PaymentController::class, 'ipn']);
});