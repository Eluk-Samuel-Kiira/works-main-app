<?php
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;
 
Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote')->daily();
 
// ── Sitemap regeneration (every 6 hours) ──────────────────────────────────────
Schedule::command('sitemap:generate')
    ->everySixHours()
    ->name('generate-sitemap')
    ->withoutOverlapping();
 
// ── IndexNow ping — hourly for new/failed jobs ────────────────────────────────
// Completely independent from Google indexing
// No quota limits — safe to run every hour
Schedule::call(function () {
    app(\App\Services\SitemapPingService::class)->pingNewJobs();
})
->hourly()
->name('indexnow-ping-new-jobs')
->withoutOverlapping();
 
// ── Job cleanup ───────────────────────────────────────────────────────────────
Schedule::call(function () {
    $expired = \DB::table('job_posts')
        ->where('is_featured', true)
        ->where('featured_until', '<', now())
        ->update(['is_featured' => false, 'featured_until' => null]);
    if ($expired > 0) \Log::info("Cleaned {$expired} expired featured jobs");
})->hourly()->name('clean-expired-featured');
 
Schedule::call(function () {
    \DB::table('job_posts')
        ->where('created_at', '<', now()->subDays(45))
        ->whereNull('deleted_at')
        ->where('is_featured', false)
        ->update(['deleted_at' => now()]);
})->dailyAt('03:00')->name('soft-delete-old-jobs');
 
Schedule::call(function () {
    $jobs = \DB::table('job_posts')
        ->where('created_at', '<', now()->subMonths(2))
        ->where('is_featured', false)
        ->get();
    if ($jobs->isNotEmpty()) {
        $ids = $jobs->pluck('id')->toArray();
        \DB::table('job_applications')->whereIn('job_post_id', $ids)->delete();
        \DB::table('job_posts')->whereIn('id', $ids)->delete();
        foreach ($jobs as $j) \Cache::forget("job_{$j->slug}");
        \Log::info("Force deleted {$jobs->count()} old jobs");
    }
})->dailyAt('02:00')->name('force-delete-old-jobs');



// Verify indexing status for jobs submitted in last 7 days (once daily)
Schedule::call(function () {
    $service = app(\App\Services\GoogleIndexingService::class);
    
    // Check jobs that were submitted but not yet confirmed indexed
    $jobs = JobPost::where('submitted_to_indexing', true)
        ->where(function ($q) {
            $q->whereNull('is_indexed')
              ->orWhere('is_indexed', false);
        })
        ->where('indexing_submitted_at', '>=', now()->subDays(7))
        ->limit(50) // Respect API rate limits: ~1 request/sec
        ->get();

    $verified = 0;
    $indexed  = 0;

    foreach ($jobs as $job) {
        $result = $service->verifyIndexingStatus($job->id);
        if ($result['success']) {
            $verified++;
            if ($result['indexed']) $indexed++;
        }
        usleep(1000000); // 1 second between requests — API rate limit
    }

    if ($verified > 0) {
        Log::info("Index verification: checked {$verified} jobs, {$indexed} confirmed indexed");
    }
})->dailyAt('04:30')->name('verify-google-indexing');


 
// ── Artisan helpers ───────────────────────────────────────────────────────────
Artisan::command('seo:ping-failed', function () {
    $this->info('Pinging failed/unpigged jobs via IndexNow...');
    $result = app(\App\Services\SitemapPingService::class)->pingFailedJobs();
    $this->table(['Metric', 'Value'], [
        ['Total jobs', $result['total']],
        ['Successful', $result['success']],
        ['Failed',     $result['failed']],
        ['HTTP Status', $result['status']],
    ]);
})->purpose('Manually ping failed/unpigged jobs via IndexNow');
 
Artisan::command('seo:stats', function () {
    $ping   = app(\App\Services\SitemapPingService::class)->getStats();
    $google = app(\App\Services\GoogleIndexingService::class)->getStats();
    $this->info('─── IndexNow Ping Stats ───────────────────');
    $this->table(['Metric', 'Count'], [
        ['Total Active', $ping['total_active']],
        ['Pinged',       $ping['pinged']],
        ['Failed',       $ping['failed']],
        ['Not Pinged',   $ping['not_pinged']],
    ]);
    $this->info('─── Google Indexing Stats ─────────────────');
    $this->table(['Metric', 'Value'], [
        ['Daily Quota Used',    $google['quota_used'] . ' / 200'],
        ['Quota Remaining',     $google['quota_remaining']],
        ['Not Submitted',       $google['not_submitted']],
        ['Submitted to Google', $google['submitted']],
        ['Confirmed Indexed',   $google['indexed']],
        ['API Configured',      $google['api_configured'] ? 'YES ✅' : 'NO ❌'],
    ]);
})->purpose('Show SEO ping and indexing stats');
 