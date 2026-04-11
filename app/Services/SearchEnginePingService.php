<?php

namespace App\Services;

use App\Models\Job\JobPost;
use Google\Client as GoogleClient;
use Google\Service\Indexing;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class SearchEnginePingService
{
    public const PING_COOLDOWN_MINUTES = 60;

    private array $engines = [
        'google' => 'https://www.google.com/ping?sitemap=',
        'bing'   => 'https://www.bing.com/ping?sitemap=',
        'yandex' => 'https://www.yandex.com/ping?sitemap=',
    ];

    private $indexingService;

    public function __construct()
    {
        // Only initialize on production/live environment
        if (!app()->environment('local')) {
            $this->initGoogleIndexing();
        }
    }

    /**
     * Initialize Google Indexing API
     */
    private function initGoogleIndexing(): void
    {
        try {
            $client = new GoogleClient();
            $client->setAuthConfig(storage_path('app/google-service-account.json'));
            $client->setScopes(['https://www.googleapis.com/auth/indexing']);
            
            $this->indexingService = new Indexing($client);
            Log::info('Google Indexing API initialized successfully');
        } catch (\Exception $e) {
            Log::error('Failed to initialize Google Indexing API: ' . $e->getMessage());
        }
    }

    /**
     * Submit URL to Google Indexing API and update job status
     * Only called AFTER successful ping
     */
    public function submitUrlToIndexing(string $url, JobPost $job): bool
    {
        // Skip on local environment
        if (app()->environment('local')) {
            Log::info("Local env - Simulated indexing for: {$url}");
            $job->submitted_to_indexing = true;
            $job->indexing_submitted_at = now();
            $job->is_indexed = false; // Not actually indexed yet
            $job->save();
            return true;
        }

        if (!$this->indexingService) {
            Log::warning('Google Indexing API not available');
            return false;
        }

        try {
            $postBody = new Indexing\UrlNotification();
            $postBody->setUrl($url);
            $postBody->setType('URL_UPDATED');
            
            $response = $this->indexingService->urlNotifications->publish($postBody);
            
            // Update job with indexing status
            $job->submitted_to_indexing = true;
            $job->indexing_submitted_at = now();
            $job->save();
            
            Log::info("Submitted to Google Indexing API: {$url}");
            return true;
        } catch (\Exception $e) {
            Log::error("Google Indexing API error for {$url}: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Submit all eligible jobs to Google Indexing API
     * Only submits jobs that have been successfully pinged
     */
    public function submitNewJobsToIndexing(): void
    {
        // Only proceed on production
        if (app()->environment('local')) {
            Log::info('Local environment - Skipping Google Indexing API submission');
            return;
        }

        // Get jobs that are:
        // 1. Active and not expired
        // 2. Have been pinged successfully
        // 3. Not yet submitted to indexing
        $jobsToSubmit = JobPost::where('is_active', true)
            ->where('deadline', '>=', now())
            ->where('is_pinged', true)  // Only jobs that were successfully pinged
            ->where(function($q) {
                $q->whereNull('submitted_to_indexing')
                  ->orWhere('submitted_to_indexing', false);
            })
            ->limit(200) // Google API limit per day
            ->get();

        $webUrl = rtrim(config('api.web_app.url'), '/');
        $submitted = 0;

        foreach ($jobsToSubmit as $job) {
            $jobUrl = $webUrl . '/jobs/' . $job->slug;
            
            if ($this->submitUrlToIndexing($jobUrl, $job)) {
                $submitted++;
                sleep(1); // Rate limiting
            }
        }

        Log::info("Submitted {$submitted} new jobs to Google Indexing API (only jobs that were pinged)");
    }

    /**
     * Check if a URL is indexed by Google
     */
    private function isUrlIndexed(string $url): bool
    {
        try {
            if ($this->indexingService) {
                $response = $this->indexingService->urlNotifications->getMetadata($url);
                if (isset($response['latestUpdate']['notifyTime'])) {
                    return true;
                }
            }
            
            // Fallback check
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, $url);
            curl_setopt($ch, CURLOPT_NOBODY, true);
            curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
            curl_setopt($ch, CURLOPT_TIMEOUT, 10);
            curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            curl_close($ch);
            
            return $httpCode === 200;
        } catch (\Exception $e) {
            Log::warning("Failed to check indexing status for {$url}: " . $e->getMessage());
            return false;
        }
    }

    public function maybePing(): void
    {
        $cacheKey = 'sitemap_last_pinged';

        if (Cache::has($cacheKey)) {
            $timeLeft = Cache::get($cacheKey)->diffInMinutes(now());
            Log::info('Sitemap ping skipped — cooldown active. Next ping in ' . $timeLeft . ' minutes');
            return;
        }

        $webUrl     = rtrim(config('api.web_app.url'), '/');
        $sitemapUrl = $webUrl . '/sitemap.xml';
        $results    = [];
        $allSuccess = true;

        foreach ($this->engines as $name => $endpoint) {
            try {
                $fullUrl  = $endpoint . urlencode($sitemapUrl);
                $response = Http::timeout(10)->get($fullUrl);

                $results[$name] = [
                    'status'  => $response->status(),
                    'success' => $response->successful(),
                    'message' => $response->successful()
                        ? 'Pinged successfully'
                        : 'HTTP ' . $response->status(),
                ];

                if (!$response->successful()) $allSuccess = false;

                Log::info("Ping {$name}: HTTP {$response->status()}");
            } catch (\Exception $e) {
                $results[$name] = [
                    'status'  => 0,
                    'success' => false,
                    'message' => $e->getMessage(),
                ];
                $allSuccess = false;
                Log::warning("Ping {$name} failed: " . $e->getMessage());
            }
        }

        Cache::put($cacheKey, now(), now()->addMinutes(self::PING_COOLDOWN_MINUTES));

        // Get new jobs from cache
        $newJobs = Cache::get('last_pinged_jobs', []);
        
        // Update job statuses for pinged jobs
        $this->updateJobPingStatus($newJobs, $allSuccess);
        
        // Send email report
        $this->sendPingReport($sitemapUrl, $results, $allSuccess);
    }

    /**
     * Update job ping status based on successful ping
     */
    private function updateJobPingStatus(array $newJobs, bool $pingSuccess): void
    {
        foreach ($newJobs as $jobData) {
            $job = JobPost::find($jobData['id']);
            if ($job) {
                $job->is_pinged = $pingSuccess;
                $job->save();
                Log::info("Updated job #{$job->id} is_pinged = " . ($pingSuccess ? 'true' : 'false'));
            }
        }
    }

    /**
     * Check and update indexing status for submitted jobs
     * Runs separately from ping
     */
    public function checkAndUpdateIndexingStatus(): void
    {
        // Get jobs submitted to indexing but not confirmed indexed
        $jobsToCheck = JobPost::where('submitted_to_indexing', true)
            ->where(function($q) {
                $q->whereNull('is_indexed')
                  ->orWhere('is_indexed', false);
            })
            ->where('indexing_submitted_at', '>=', now()->subDays(7))
            ->limit(50)
            ->get();

        $webUrl = rtrim(config('api.web_app.url'), '/');
        $indexedCount = 0;

        foreach ($jobsToCheck as $job) {
            $jobUrl = $webUrl . '/jobs/' . $job->slug;
            
            if ($this->isUrlIndexed($jobUrl)) {
                $job->is_indexed = true;
                $job->save();
                $indexedCount++;
                Log::info("Job #{$job->id} is now indexed: {$jobUrl}");
            }
        }

        if ($indexedCount > 0) {
            Log::info("Updated {$indexedCount} jobs as indexed");
        }
    }

    public function pingIfNewJobs(): void
    {
        $newJobs = JobPost::where('is_active', true)
            ->where('created_at', '>=', now()->subHour())
            ->select(['id', 'job_title', 'slug', 'created_at'])
            ->get();

        if ($newJobs->count() > 0) {
            Log::info("Found {$newJobs->count()} new job(s) — regenerating sitemap then pinging.");

            // Regenerate sitemap first
            \Artisan::call('sitemap:generate');

            // Store new jobs in cache so email can reference them
            Cache::put('last_pinged_jobs', $newJobs->toArray(), now()->addHours(2));

            // First, ping search engines
            $this->maybePing();
            
            // After successful ping, submit to indexing API
            // This runs in the next schedule cycle (every 6 hours)
            // Or you can call it immediately:
            // $this->submitNewJobsToIndexing();
            
        } else {
            Log::info("No new jobs in the last hour — skipping ping.");
        }
    }

    public function forcePing(): void
    {
        Cache::forget('sitemap_last_pinged');
        $this->maybePing();
    }

    private function sendPingReport(string $sitemapUrl, array $results, bool $allSuccess): void
    {
        $adminEmails = array_filter(
            array_map('trim', explode(',', env('ADMIN_EMAILS', '')))
        );

        if (empty($adminEmails)) {
            Log::warning('No admin emails configured');
            return;
        }

        $newJobs   = Cache::get('last_pinged_jobs', []);
        $jobCount  = count($newJobs);
        
        // Get stats
        $submittedCount = JobPost::where('submitted_to_indexing', true)
            ->where('created_at', '>=', now()->subDays(7))
            ->count();
            
        $indexedCount = JobPost::where('is_indexed', true)
            ->where('created_at', '>=', now()->subDays(7))
            ->count();

        $pingedCount = JobPost::where('is_pinged', true)
            ->where('created_at', '>=', now()->subDays(7))
            ->count();

        $statusIcon = $allSuccess ? '✅' : '⚠️';
        $subject   = "{$statusIcon} Sitemap Ping Report — {$jobCount} new job(s) — " . now()->format('d M Y H:i');

        $webUrl = rtrim(config('api.web_app.url'), '/');

        $html = $this->buildEmailHtml($sitemapUrl, $results, $allSuccess, $newJobs, $jobCount, $webUrl, $submittedCount, $indexedCount, $pingedCount);

        foreach ($adminEmails as $email) {
            try {
                Mail::html($html, function ($message) use ($email, $subject) {
                    $message->to($email)
                            ->subject($subject)
                            ->from(
                                env('MAIL_FROM_ADDRESS', 'noreply@stardenaworks.com'),
                                env('MAIL_FROM_NAME', 'Stardena Works')
                            );
                });
                Log::info("Ping report sent to: {$email}");
            } catch (\Exception $e) {
                Log::error("Failed to send ping report to {$email}: " . $e->getMessage());
            }
        }
    }

    private function buildEmailHtml(string $sitemapUrl, array $results, bool $allSuccess, array $newJobs, int $jobCount, string $webUrl, int $submittedCount, int $indexedCount, int $pingedCount): string
    {
        $html = '<!DOCTYPE html><html><head><meta charset="UTF-8">';
        $html .= '<style>
            body { font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Helvetica, Arial, sans-serif; line-height: 1.6; color: #1f2937; max-width: 600px; margin: 0 auto; padding: 20px; background: #f9fafb; }
            .header { background: linear-gradient(135deg, #4f46e5 0%, #7c3aed 100%); color: white; padding: 30px; text-align: center; border-radius: 12px 12px 0 0; }
            .content { background: white; padding: 30px; border-radius: 0 0 12px 12px; box-shadow: 0 4px 6px -1px rgba(0,0,0,0.1); }
            .stats-grid { display: grid; grid-template-columns: repeat(4, 1fr); gap: 15px; margin: 20px 0; }
            .stat-card { background: #f3f4f6; padding: 15px; border-radius: 8px; text-align: center; }
            .stat-card .number { font-size: 24px; font-weight: bold; color: #4f46e5; }
            .stat-card .label { font-size: 12px; color: #6b7280; margin-top: 5px; }
            table { width: 100%; border-collapse: collapse; margin: 15px 0; }
            th, td { padding: 12px; text-align: left; border-bottom: 1px solid #e5e7eb; }
            th { background: #f9fafb; font-weight: 600; }
            .success { color: #10b981; font-weight: 600; }
            .failed { color: #ef4444; font-weight: 600; }
            .job-item { padding: 12px; border-bottom: 1px solid #e5e7eb; }
            .footer { text-align: center; padding: 20px; font-size: 12px; color: #6b7280; border-top: 1px solid #e5e7eb; margin-top: 30px; }
            .btn { display: inline-block; background: #4f46e5; color: white; padding: 10px 20px; text-decoration: none; border-radius: 6px; margin: 10px 5px; }
        </style></head>';
        
        $html .= '<body>';
        $html .= '<div class="header">';
        $html .= '<h1 style="margin: 0;">🗺️ Sitemap & Indexing Report</h1>';
        $html .= '<p style="margin: 10px 0 0; opacity: 0.9;">' . now()->format('l, F j, Y g:i A T') . '</p>';
        $html .= '</div>';
        
        $html .= '<div class="content">';
        
        // Stats grid
        $html .= '<div class="stats-grid">';
        $html .= '<div class="stat-card"><div class="number">' . $jobCount . '</div><div class="label">New Jobs</div></div>';
        $html .= '<div class="stat-card"><div class="number">' . $pingedCount . '</div><div class="label">Successfully Pinged</div></div>';
        $html .= '<div class="stat-card"><div class="number">' . $submittedCount . '</div><div class="label">Submitted to Indexing</div></div>';
        $html .= '<div class="stat-card"><div class="number">' . $indexedCount . '</div><div class="label">Confirmed Indexed</div></div>';
        $html .= '</div>';
        
        // Ping results
        $html .= '<h3 style="margin-top: 0;">🔔 Search Engine Ping Results</h3>';
        $html .= '<table>';
        $html .= '<tr><th>Engine</th><th>Status</th><th>Result</th></tr>';
        
        foreach ($results as $engine => $result) {
            $icon = $result['success'] ? '✅' : '❌';
            $statusClass = $result['success'] ? 'success' : 'failed';
            $html .= '<tr>';
            $html .= '<td><strong>' . ucfirst($engine) . '</strong></td>';
            $html .= '<td class="' . $statusClass . '">' . $icon . ' HTTP ' . $result['status'] . '</td>';
            $html .= '<td>' . htmlspecialchars($result['message']) . '</td>';
            $html .= '</tr>';
        }
        $html .= '</table>';
        
        // New jobs list
        if ($jobCount > 0) {
            $html .= '<h3>📋 New Jobs Processed</h3>';
            foreach ($newJobs as $job) {
                $jobUrl = $webUrl . '/jobs/' . ($job['slug'] ?? '');
                $html .= '<div class="job-item">';
                $html .= '<strong>' . htmlspecialchars($job['job_title'] ?? '') . '</strong><br>';
                $html .= '<span style="font-size: 12px; color: #6b7280;">Posted: ' . \Carbon\Carbon::parse($job['created_at'])->format('M d, Y g:i A') . '</span><br>';
                $html .= '<a href="' . $jobUrl . '" style="color: #4f46e5; font-size: 12px;">View Job →</a>';
                $html .= '</div>';
            }
        }
        
        // Flow explanation
        $html .= '<div style="background: #f0fdf4; padding: 15px; border-radius: 8px; margin: 20px 0;">';
        $html .= '<strong>📊 SEO Flow:</strong><br>';
        $html .= '1️⃣ New job posted → is_pinged = false, submitted_to_indexing = false<br>';
        $html .= '2️⃣ Hourly ping runs → If successful, is_pinged = true<br>';
        $html .= '3️⃣ Indexing API runs (every 6 hours) → Only submits jobs with is_pinged = true<br>';
        $html .= '4️⃣ Daily check confirms indexing → is_indexed = true';
        $html .= '</div>';
        
        // Sitemap link
        $html .= '<h3>🔗 Sitemap</h3>';
        $html .= '<p><a href="' . $sitemapUrl . '" style="color: #4f46e5;">' . $sitemapUrl . '</a></p>';
        
        // Action buttons
        $html .= '<div style="text-align: center; margin: 30px 0;">';
        $html .= '<a href="' . $webUrl . '/sitemap.xml" class="btn">View Sitemap</a>';
        $html .= '<a href="https://search.google.com/search-console" class="btn">Open Search Console</a>';
        $html .= '</div>';
        
        $html .= '<div class="footer">';
        $html .= '<p>Stardena Works — Automated SEO Report</p>';
        $html .= '<p style="margin: 0;">Jobs are only submitted to Indexing API AFTER successful ping confirmation</p>';
        $html .= '</div>';
        
        $html .= '</div></body></html>';
        
        return $html;
    }
}