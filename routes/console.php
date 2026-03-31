<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// ============================================================
// SEO & SITEMAP TASKS
// ============================================================

// Generate sitemap every 6 hours (instead of hourly to reduce load)
// Since you have 128 jobs, hourly is fine, but 6 hours is more efficient
Schedule::command('sitemap:generate')->everySixHours()->name('generate-sitemap');

// Ping search engines hourly if new jobs
Schedule::call(function () {
    app(\App\Services\SearchEnginePingService::class)->pingIfNewJobs();
})->hourly()->name('ping-search-engines');

// ============================================================
// JOB CLEANUP TASKS
// ============================================================

// 1. Clean up expired featured jobs hourly
Schedule::call(function () {
    $expired = \DB::table('job_posts')
        ->where('is_featured', true)
        ->where('featured_until', '<', now())
        ->update(['is_featured' => false, 'featured_until' => null]);
    
if ($expired > 0) {
    \Log::info("Cleaned up {$expired} expired featured jobs");
}
})->hourly()->name('clean-expired-featured');

// 2. Force delete jobs older than 2 months (60 days) - run daily at 2 AM
Schedule::call(function () {
    $cutoffDate = now()->subMonths(2);
    
    $jobsToDelete = \DB::table('job_posts')
        ->where('created_at', '<', $cutoffDate)
        ->where(function ($query) {
            $query->where('is_featured', false)
                  ->orWhere('featured_until', '<', now());
        })
        ->select('id', 'slug', 'job_title')
        ->get();
    
    if ($jobsToDelete->count() > 0) {
        $ids = $jobsToDelete->pluck('id')->toArray();
        
        \Log::info("Deleting {$jobsToDelete->count()} jobs older than 2 months", [
            'job_ids' => $ids,
            'job_titles' => $jobsToDelete->pluck('job_title')->toArray()
        ]);
        
        // Delete related records
        \DB::table('job_applications')->whereIn('job_post_id', $ids)->delete();
        \DB::table('job_audit_logs')->whereIn('job_post_id', $ids)->delete();
        \DB::table('job_views')->whereIn('job_post_id', $ids)->delete();
        
        // Delete the jobs
        \DB::table('job_posts')->whereIn('id', $ids)->delete();
        
        // Clear cache
        foreach ($jobsToDelete as $job) {
            \Cache::forget("job_{$job->slug}");
            \Cache::forget("featured:{$job->slug}");
        }
        
        \Log::info("Successfully deleted {$jobsToDelete->count()} old jobs");
    }
})->dailyAt('02:00')->name('delete-old-jobs'); // Run at 2 AM

// 3. Soft delete jobs that are 1.5 months old (45 days) - run daily at 3 AM
Schedule::call(function () {
    $cutoffDate = now()->subDays(45);
    
    $updated = \DB::table('job_posts')
        ->where('created_at', '<', $cutoffDate)
        ->whereNull('deleted_at')
        ->where(function ($query) {
            $query->where('is_featured', false)
                  ->orWhere('featured_until', '<', now());
        })
        ->update(['deleted_at' => now()]);
    
    if ($updated > 0) {
        \Log::info("Soft deleted {$updated} jobs older than 45 days");
    }
})->dailyAt('03:00')->name('soft-delete-old-jobs');


// ============================================================
// OPTIONAL: Send daily summary email (if you have mail configured)
// ============================================================
/*
Schedule::call(function () {
    $newJobsToday = \DB::table('job_posts')
        ->whereDate('created_at', today())
        ->count();
    
    $activeJobs = \DB::table('job_posts')
        ->where('is_active', true)
        ->where('deadline', '>=', now())
        ->count();
    
    $expiringSoon = \DB::table('job_posts')
        ->where('is_active', true)
        ->where('deadline', '<=', now()->addDays(3))
        ->count();
    
    \Log::info("Daily Stats - New: {$newJobsToday}, Active: {$activeJobs}, Expiring Soon: {$expiringSoon}");
    
    // Send email to admin
    // Mail::to('admin@example.com')->send(new DailyStatsReport($newJobsToday, $activeJobs, $expiringSoon));
})->dailyAt('23:59')->name('daily-stats');
*/