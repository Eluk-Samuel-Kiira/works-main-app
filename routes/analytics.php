<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Analytics\{
    AnalyticsDashboardController,
    JobAnalyticsController,
    UserAnalyticsController,
    CompanyAnalyticsController,
    RevenueAnalyticsController,
    SeoAnalyticsController,
    ApiAnalyticsController,
    NotificationAnalyticsController,
    EmployerAnalyticsController,
};

// ─── Admin / Moderator Analytics ────────────────────────────────────────────
Route::middleware(['auth', 'analytics.admin'])
    ->prefix('analytics')
    ->name('analytics.')
    ->group(function () {

        Route::get('/',             [AnalyticsDashboardController::class, 'index'])->name('dashboard');
        Route::get('/jobs',         [JobAnalyticsController::class,       'index'])->name('jobs');
        Route::get('/users',        [UserAnalyticsController::class,      'index'])->name('users');
        Route::get('/companies',    [CompanyAnalyticsController::class,   'index'])->name('companies');
        Route::get('/seo',          [SeoAnalyticsController::class,       'index'])->name('seo');
        Route::get('/notifications',[NotificationAnalyticsController::class, 'index'])->name('notifications');

        // Revenue & API — admin only
        Route::middleware('analytics.revenue')->group(function () {
            Route::get('/revenue',   [RevenueAnalyticsController::class, 'index'])->name('revenue');
            Route::get('/api-usage', [ApiAnalyticsController::class,     'index'])->name('api');
        });

        // ─── JSON data endpoints (AJAX refresh) ─────────────────────────────
        Route::prefix('data')->name('data.')->group(function () {
            Route::get('/overview',       [AnalyticsDashboardController::class,   'data'])->name('overview');
            Route::get('/jobs',           [JobAnalyticsController::class,          'data'])->name('jobs');
            Route::get('/users',          [UserAnalyticsController::class,         'data'])->name('users');
            Route::get('/companies',      [CompanyAnalyticsController::class,      'data'])->name('companies');
            Route::get('/seo',            [SeoAnalyticsController::class,          'data'])->name('seo');
            Route::get('/notifications',  [NotificationAnalyticsController::class, 'data'])->name('notifications');
            Route::get('/revenue',        [RevenueAnalyticsController::class,      'data'])->name('revenue');
            Route::get('/api',            [ApiAnalyticsController::class,          'data'])->name('api');
        });

        // ─── CSV Export endpoints ────────────────────────────────────────────
        Route::prefix('export')->name('export.')->group(function () {
            Route::get('/jobs',      [JobAnalyticsController::class,      'export'])->name('jobs');
            Route::get('/users',     [UserAnalyticsController::class,     'export'])->name('users');
            Route::get('/companies', [CompanyAnalyticsController::class,  'export'])->name('companies');
            Route::get('/seo',       [SeoAnalyticsController::class,      'export'])->name('seo');
            Route::get('/revenue',   [RevenueAnalyticsController::class,  'export'])->name('revenue');
        });
    });

// ─── Employer Analytics (scoped to own data) ────────────────────────────────
Route::middleware(['auth', 'analytics.employer'])
    ->prefix('analytics/employer')
    ->name('analytics.employer.')
    ->group(function () {
        Route::get('/',        [EmployerAnalyticsController::class, 'index'])->name('dashboard');
        Route::get('/data',    [EmployerAnalyticsController::class, 'data'])->name('data');
        Route::get('/export',  [EmployerAnalyticsController::class, 'export'])->name('export');
    });
