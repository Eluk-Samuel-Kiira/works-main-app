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

// ── Blog Sitemap generation (daily) ───────────────────────────────────────────
Schedule::command('sitemap:blog:generate')
    ->daily()
    ->name('generate-blog-sitemap')
    ->withoutOverlapping();

// ── IndexNow ping — hourly for new/failed jobs ────────────────────────────────
Schedule::call(function () {
    app(\App\Services\SitemapPingService::class)->pingNewJobs();
})
->hourly()
->name('indexnow-ping-new-jobs')
->withoutOverlapping();

// ── Blog IndexNow ping — hourly for new/failed blogs ──────────────────────────
Schedule::call(function () {
    app(\App\Services\Blog\BlogSitemapPingService::class)->pingNewBlogs();
})
->hourly()
->name('blog-indexnow-ping-new-blogs')
->withoutOverlapping();

// ── Job cleanup ───────────────────────────────────────────────────────────────
Schedule::call(function () {
    $expired = \DB::table('job_posts')
        ->where('is_featured', true)
        ->where('featured_until', '<', now())
        ->update(['is_featured' => false, 'featured_until' => null]);
    if ($expired > 0) \Log::info("Cleaned {$expired} expired featured jobs");
})->hourly()->name('clean-expired-featured');

// ── Soft delete old jobs ──────────────────────────────────────────────────────
Schedule::call(function () {
    \DB::table('job_posts')
        ->where('created_at', '<', now()->subDays(45))
        ->whereNull('deleted_at')
        ->where('is_featured', false)
        ->update(['deleted_at' => now()]);
})->dailyAt('03:00')->name('soft-delete-old-jobs');

// ── Cleanup temporary images ──────────────────────────────────────────────────
Schedule::call(function () {
    \App\Helpers\ImageHelper::cleanupTempImages();
})->daily();

// ── Force delete old jobs ─────────────────────────────────────────────────────
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

Artisan::command('blog:seo:ping-failed', function () {
    $this->info('Pinging failed/unpigged blogs via IndexNow...');
    $result = app(\App\Services\Blog\BlogSitemapPingService::class)->pingFailedBlogs();
    $this->table(['Metric', 'Value'], [
        ['Total blogs', $result['total']],
        ['Successful', $result['success']],
        ['Failed',     $result['failed']],
        ['HTTP Status', $result['status']],
    ]);
})->purpose('Manually ping failed/unpigged blogs via IndexNow');

Artisan::command('seo:stats', function () {
    $ping   = app(\App\Services\SitemapPingService::class)->getStats();
    $google = app(\App\Services\GoogleIndexingService::class)->getStats();
    $blogPing = app(\App\Services\Blog\BlogSitemapPingService::class)->getStats();
    
    $this->info('─── Job IndexNow Ping Stats ───────────────────');
    $this->table(['Metric', 'Count'], [
        ['Total Active Jobs', $ping['total_active']],
        ['Jobs Pinged',       $ping['pinged']],
        ['Jobs Failed',       $ping['failed']],
        ['Jobs Not Pinged',   $ping['not_pinged']],
    ]);
    
    $this->info('─── Blog IndexNow Ping Stats ───────────────────');
    $this->table(['Metric', 'Count'], [
        ['Total Active Blogs', $blogPing['total_active']],
        ['Blogs Pinged',       $blogPing['pinged']],
        ['Blogs Failed',       $blogPing['failed']],
        ['Blogs Not Pinged',   $blogPing['not_pinged']],
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