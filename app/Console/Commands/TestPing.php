<?php

namespace App\Console\Commands;

use App\Services\SearchEnginePingService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;

class TestPing extends Command
{
    protected $signature = 'test:ping {--force : Force ping even without new jobs}';
    protected $description = 'Test search engine ping service';

    public function handle(SearchEnginePingService $pingService)
    {
        $this->info('🧪 Testing Search Engine Ping Service...');
        $this->newLine();
        
        // Get URLs from config
        $webUrl = config('api.web_app.url');
        $mainUrl = config('api.main_app.url');
        
        // Show configuration
        $this->line('📋 Configuration:');
        $this->line('   WEB_APP_URL (Frontend): ' . $webUrl);
        $this->line('   MAIN_APP_URL (Backend): ' . $mainUrl);
        $this->line('   Cooldown: ' . SearchEnginePingService::PING_COOLDOWN_MINUTES . ' minutes');
        $this->newLine();
        
        // Check if sitemap exists
        $sitemapPath = public_path('sitemap.xml');
        $this->line('📁 Sitemap Status:');
        if (file_exists($sitemapPath)) {
            $this->line('   ✓ Sitemap exists: ' . $sitemapPath);
            $sitemapSize = round(filesize($sitemapPath) / 1024, 2);
            $this->line('   ✓ Size: ' . $sitemapSize . ' KB');
            $this->line('   ✓ URL: ' . $webUrl . '/sitemap.xml');
        } else {
            $this->warn('   ✗ Sitemap not found at: ' . $sitemapPath);
            $this->warn('   Run: php artisan sitemap:generate first');
        }
        
        $this->newLine();
        
        if ($this->option('force')) {
            $this->warn('⚠️  Force pinging (ignoring cooldown and new jobs check)...');
            $pingService->forcePing();
        } else {
            $this->info('🔍 Checking for new jobs...');
            
            $newJobsCount = \App\Models\Job\JobPost::where('is_active', true)
                ->where('created_at', '>=', now()->subHour())
                ->count();
            
            $this->line("   Found {$newJobsCount} new job(s) in the last hour");
            
            if ($newJobsCount > 0) {
                $this->info("✅ New jobs found! Will ping search engines.");
            } else {
                $this->warn("⚠️  No new jobs found. Use --force to ping anyway.");
            }
            
            $this->newLine();
            
            $pingService->pingIfNewJobs();
        }
        
        $this->newLine();
        
        // Show cache status
        $lastPing = Cache::get('sitemap_last_pinged');
        if ($lastPing) {
            $this->info('⏰ Last ping was: ' . $lastPing->format('Y-m-d H:i:s'));
            $nextPing = $lastPing->copy()->addMinutes(SearchEnginePingService::PING_COOLDOWN_MINUTES);
            if ($nextPing > now()) {
                $this->warn('   Next ping allowed: ' . $nextPing->format('H:i:s'));
            } else {
                $this->info('   ✅ Ready to ping again');
            }
        } else {
            $this->info('⏰ No previous ping recorded');
        }
        
        $this->newLine();
        $this->info('✅ Test completed. Check logs for details.');
        $this->line('📝 View logs: tail -f storage/logs/laravel.log');
    }
}