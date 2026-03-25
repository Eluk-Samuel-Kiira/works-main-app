<?php

namespace App\Services;

use App\Models\Job\JobPost;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class SearchEnginePingService
{
    // Ping engines max once per hour — not per job
    public const PING_COOLDOWN_MINUTES = 60;

    // Search engine sitemap ping endpoints
    private array $engines = [
        'google' => 'https://www.google.com/ping?sitemap=',
        'bing'   => 'https://www.bing.com/ping?sitemap=',
        'yandex' => 'https://www.yandex.com/ping?sitemap=',
        'baidu'  => 'http://data.zz.baidu.com/urls?site=',
    ];

    public function maybePing(): void
    {
        $cacheKey = 'sitemap_last_pinged';

        if (Cache::has($cacheKey)) {
            Log::info('Sitemap ping skipped — cooldown active (last ping less than ' . self::PING_COOLDOWN_MINUTES . ' minutes ago).');
            return;
        }

        // Use the web_app URL from config/api.php
        $webUrl = rtrim(config('api.web_app.url'), '/');
        $sitemapUrl = $webUrl . '/sitemap.xml';
        
        Log::info("Pinging search engines with sitemap: {$sitemapUrl}");

        foreach ($this->engines as $name => $endpoint) {
            try {
                $fullUrl = $endpoint . urlencode($sitemapUrl);
                $response = Http::timeout(10)->get($fullUrl);
                
                if ($response->successful()) {
                    Log::info("✓ Successfully pinged {$name} (HTTP {$response->status()})");
                } else {
                    Log::warning("⚠️ Ping to {$name} returned HTTP {$response->status()}");
                }
            } catch (\Exception $e) {
                Log::warning("✗ Failed to ping {$name}: " . $e->getMessage());
            }
        }

        // Lock for cooldown period
        Cache::put($cacheKey, now(), now()->addMinutes(self::PING_COOLDOWN_MINUTES));
        Log::info("Sitemap ping completed. Next ping allowed after " . now()->addMinutes(self::PING_COOLDOWN_MINUTES)->format('H:i:s'));
    }

    public function pingIfNewJobs(): void
    {
        $newJobsCount = JobPost::where('is_active', true)
            ->where('created_at', '>=', now()->subHour())
            ->count();

        if ($newJobsCount > 0) {
            Log::info("Found {$newJobsCount} new job(s) in the last hour — triggering sitemap ping.");
            $this->maybePing();
        } else {
            Log::info("No new jobs in the last hour — skipping sitemap ping.");
        }
    }
    
    public function forcePing(): void
    {
        Log::info("Force ping triggered — clearing cooldown cache.");
        Cache::forget('sitemap_last_pinged');
        $this->maybePing();
    }
}