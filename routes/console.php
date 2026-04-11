<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote')->daily();

// ============================================================
// SITEMAP GENERATION
// ============================================================
Schedule::command('sitemap:generate')
    ->everySixHours()
    ->name('generate-sitemap')
    ->withoutOverlapping();

// ============================================================
// SEO FLOW:
// 1. Check for new jobs and ping search engines (Hourly)
// 2. Submit ONLY pinged jobs to Indexing API (Every 6 hours)
// 3. Check indexing status (Daily)
// ============================================================

// Step 1: Ping search engines when new jobs exist
Schedule::call(fn() => app(\App\Services\SearchEnginePingService::class)->pingIfNewJobs())
    ->hourly()
    ->name('ping-search-engines')
    ->withoutOverlapping();

// Step 2: Submit ONLY jobs that were successfully pinged to Google Indexing API
Schedule::call(fn() => app(\App\Services\SearchEnginePingService::class)->submitNewJobsToIndexing())
    ->everySixHours()
    ->name('submit-to-indexing')
    ->withoutOverlapping();

// Step 3: Check indexing status for submitted jobs
Schedule::call(fn() => app(\App\Services\SearchEnginePingService::class)->checkAndUpdateIndexingStatus())
    ->dailyAt('04:00')
    ->name('check-indexing-status')
    ->withoutOverlapping();

// ============================================================
// JOB CLEANUP TASKS
// ============================================================

// Clean up expired featured jobs
Schedule::call(function () {
    $expired = \DB::table('job_posts')
        ->where('is_featured', true)
        ->where('featured_until', '<', now())
        ->update(['is_featured' => false, 'featured_until' => null]);
    
    if ($expired > 0) {
        \Log::info("Cleaned up {$expired} expired featured jobs");
    }
})->hourly()->name('clean-expired-featured');

// Soft delete jobs older than 45 days
Schedule::call(function () {
    $updated = \DB::table('job_posts')
        ->where('created_at', '<', now()->subDays(45))
        ->whereNull('deleted_at')
        ->where('is_featured', false)
        ->update(['deleted_at' => now()]);
    
    if ($updated > 0) {
        \Log::info("Soft deleted {$updated} jobs older than 45 days");
    }
})->dailyAt('03:00')->name('soft-delete-old-jobs');

// Force delete jobs older than 60 days
Schedule::call(function () {
    $jobsToDelete = \DB::table('job_posts')
        ->where('created_at', '<', now()->subMonths(2))
        ->where(function ($query) {
            $query->where('is_featured', false)
                  ->orWhere('featured_until', '<', now());
        })
        ->get();
    
    if ($jobsToDelete->isNotEmpty()) {
        $ids = $jobsToDelete->pluck('id')->toArray();
        
        \DB::table('job_applications')->whereIn('job_post_id', $ids)->delete();
        \DB::table('job_audit_logs')->whereIn('job_post_id', $ids)->delete();
        \DB::table('job_views')->whereIn('job_post_id', $ids)->delete();
        \DB::table('job_posts')->whereIn('id', $ids)->delete();
        
        foreach ($jobsToDelete as $job) {
            \Cache::forget("job_{$job->slug}");
            \Cache::forget("featured:{$job->slug}");
        }
        
        \Log::info("Permanently deleted {$jobsToDelete->count()} jobs older than 60 days");
    }
})->dailyAt('02:00')->name('force-delete-old-jobs');

// ============================================================
// ARTISAN COMMANDS
// ============================================================

Artisan::command('seo:force-ping', function () {
    $this->info('Forcing sitemap ping...');
    app(\App\Services\SearchEnginePingService::class)->forcePing();
    $this->info('✓ Sitemap ping completed');
})->purpose('Force sitemap ping to search engines');

Artisan::command('seo:stats', function () {
    $stats = [
        ['Active Jobs', \App\Models\Job\JobPost::where('is_active', true)->where('deadline', '>=', now())->count()],
        ['Pinged Jobs', \App\Models\Job\JobPost::where('is_pinged', true)->count()],
        ['Submitted to Indexing', \App\Models\Job\JobPost::where('submitted_to_indexing', true)->count()],
        ['Confirmed Indexed', \App\Models\Job\JobPost::where('is_indexed', true)->count()],
    ];
    $this->table(['Metric', 'Count'], $stats);
})->purpose('Display SEO statistics');