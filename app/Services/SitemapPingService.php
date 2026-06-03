<?php

namespace App\Services;

use App\Models\Job\JobPost;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Cache;

/**
 * SitemapPingService
 * ─────────────────────────────────────────────────────────────────
 * PURPOSE : Notify search engines (via IndexNow) about new/updated
 *           job URLs, INCLUDING country-specific URLs (/ke/jobs/...)
 * 
 * IndexNow is supported by Bing, Yandex, and others.
 * Google does NOT support IndexNow — they discover via sitemaps.
 * ─────────────────────────────────────────────────────────────────
 */
class SitemapPingService
{
    private const INDEXNOW_KEY      = 'b433024ea88249dfa1cae5e8cfacacf9';
    private const INDEXNOW_ENDPOINT = 'https://api.indexnow.org/IndexNow';
    private const BATCH_SIZE        = 100;
    private const COOLDOWN_MINUTES  = 55;

    // Supported countries for URL generation
    private const SUPPORTED_COUNTRIES = [
        'ke' => 'KE',
        'tz' => 'TZ', 
        'rw' => 'RW',
        'ug' => 'UG',
        'ng' => 'NG',
        'za' => 'ZA',
        'bi' => 'BI',
        'ss' => 'SS',
    ];

    private string $webUrl;
    private string $keyLocation;

    public function __construct()
    {
        $this->webUrl      = rtrim(config('api.web_app.url', env('WEB_APP_URL', 'https://stardenaworks.com')), '/');
        $this->keyLocation = $this->webUrl . '/' . self::INDEXNOW_KEY . '.txt';
    }

    // =========================================================================
    // SCHEDULED — runs every hour
    // =========================================================================
    public function pingNewJobs(): array
    {
        $jobs = JobPost::where('is_active', true)
            ->where('deadline', '>=', now())
            ->whereNull('last_pinged_at')
            ->whereNotNull('slug')
            ->where('slug', '!=', '')
            ->select(['id', 'job_title', 'slug', 'job_location_id', 'created_at'])
            ->with('jobLocation:id,country')
            ->orderBy('created_at', 'desc')
            ->limit(self::BATCH_SIZE)
            ->get();

        if ($jobs->isEmpty()) {
            Log::info('PING: No new unpigged jobs found.');
            return ['total' => 0, 'success' => 0, 'failed' => 0, 'jobs' => []];
        }

        Log::info("PING: Found {$jobs->count()} unpigged job(s) — submitting via IndexNow.");

        // Regenerate sitemap before pinging
        \Artisan::call('sitemap:generate');

        // ⭐ Generate ALL URLs (default + country-specific)
        $allUrls = $this->generateAllJobUrls($jobs);
        
        $result = $this->submitToIndexNow($allUrls);

        // Update DB based on result
        foreach ($jobs as $job) {
            if ($result['success']) {
                JobPost::where('id', $job->id)->update([
                    'is_pinged'      => true,
                    'last_pinged_at' => now(),
                    'published_at'   => now(),
                ]);
            } else {
                JobPost::where('id', $job->id)->update([
                    'is_pinged'      => false,
                    'last_pinged_at' => now(),
                ]);
            }
        }

        $report = [
            'total'   => $jobs->count(),
            'success' => $result['success'] ? $jobs->count() : 0,
            'failed'  => $result['success'] ? 0 : $jobs->count(),
            'status'  => $result['status'],
            'urls_submitted' => count($allUrls),
            'jobs'    => $jobs->map(fn($j) => [
                'id'      => $j->id,
                'title'   => $j->job_title,
                'default_url' => $this->webUrl . '/jobs/' . $j->slug,
                'country_urls' => $this->getCountryUrlsForJob($j),
                'success' => $result['success'],
            ])->toArray(),
        ];

        $this->sendPingReport($report);

        return $report;
    }

    // =========================================================================
    // MANUAL — triggered from admin button for FAILED jobs
    // =========================================================================
    public function pingFailedJobs(?array $jobIds = null): array
    {
        $query = JobPost::where('is_active', true)
            ->where('deadline', '>=', now())
            ->whereNotNull('slug')
            ->where('slug', '!=', '');

        if ($jobIds) {
            $query->whereIn('id', $jobIds);
        } else {
            $query->where(function ($q) {
                $q->whereNull('last_pinged_at')
                  ->orWhere('is_pinged', false);
            });
        }

        $jobs = $query->select(['id', 'job_title', 'slug', 'job_location_id', 'is_pinged', 'last_pinged_at', 'published_at'])
                      ->with('jobLocation:id,country')
                      ->limit(self::BATCH_SIZE)
                      ->get();

        if ($jobs->isEmpty()) {
            return ['total' => 0, 'success' => 0, 'failed' => 0, 'jobs' => []];
        }

        \Artisan::call('sitemap:generate');

        $allUrls = $this->generateAllJobUrls($jobs);
        $result = $this->submitToIndexNow($allUrls);

        foreach ($jobs as $job) {
            if ($result['success']) {
                $updateData = [
                    'is_pinged'      => true,
                    'last_pinged_at' => now(),
                ];
                if (is_null($job->published_at)) {
                    $updateData['published_at'] = now();
                }
                JobPost::where('id', $job->id)->update($updateData);
            } else {
                JobPost::where('id', $job->id)->update([
                    'is_pinged'      => false,
                    'last_pinged_at' => now(),
                ]);
            }
        }

        return [
            'total'   => $jobs->count(),
            'success' => $result['success'] ? $jobs->count() : 0,
            'failed'  => $result['success'] ? 0 : $jobs->count(),
            'status'  => $result['status'],
            'urls_submitted' => count($allUrls),
            'message' => $result['message'],
        ];
    }

    // =========================================================================
    // MANUAL PING FOR SPECIFIC JOBS (from bulk modal)
    // =========================================================================
    public function manualPingJobs(array $jobIds): array
    {
        $jobs = JobPost::whereIn('id', $jobIds)
            ->where('is_active', true)
            ->where('deadline', '>=', now())
            ->with('jobLocation:id,country')
            ->get();

        if ($jobs->isEmpty()) {
            return ['submitted' => 0, 'results' => []];
        }

        \Artisan::call('sitemap:generate');

        $allUrls = $this->generateAllJobUrls($jobs);
        $result = $this->submitToIndexNow($allUrls);
        $submitted = 0;
        $results = [];

        foreach ($jobs as $job) {
            $success = $result['success'];
            if ($success) {
                $updateData = [
                    'is_pinged'      => true,
                    'last_pinged_at' => now(),
                ];
                if (is_null($job->published_at)) {
                    $updateData['published_at'] = now();
                }
                JobPost::where('id', $job->id)->update($updateData);
                $submitted++;
            } else {
                JobPost::where('id', $job->id)->update([
                    'is_pinged'      => false,
                    'last_pinged_at' => now(),
                ]);
            }

            $results[] = [
                'job_id' => $job->id,
                'title' => $job->job_title,
                'default_url' => $this->webUrl . '/jobs/' . $job->slug,
                'country_urls' => $this->getCountryUrlsForJob($job),
                'success' => $success,
            ];
        }

        return [
            'submitted' => $submitted,
            'total' => $jobs->count(),
            'urls_submitted' => count($allUrls),
            'results' => $results,
            'status' => $result['status'],
        ];
    }

    // =========================================================================
    // ⭐ GENERATE ALL JOB URLS (default + country-specific)
    // =========================================================================
    private function generateAllJobUrls($jobs): array
    {
        $urls = [];
        
        foreach ($jobs as $job) {
            // Add default URL
            $urls[] = $this->webUrl . '/jobs/' . $job->slug;
            
            // Add country-specific URLs based on job location
            $countryUrls = $this->getCountryUrlsForJob($job);
            $urls = array_merge($urls, $countryUrls);
        }
        
        // Remove duplicates and sort
        $urls = array_unique($urls);
        sort($urls);
        
        Log::info("Generated " . count($urls) . " URLs for " . $jobs->count() . " jobs");
        
        return $urls;
    }
    
    // =========================================================================
    // ⭐ GET COUNTRY-SPECIFIC URLS FOR A SINGLE JOB
    // =========================================================================
    private function getCountryUrlsForJob($job): array
    {
        $urls = [];
        $slug = $job->slug;
        $jobCountry = null;
        
        // Get job's country from location
        if ($job->jobLocation && $job->jobLocation->country) {
            $jobCountry = strtolower($job->jobLocation->country);
        }
        
        // Add URL for the job's own country (primary)
        if ($jobCountry && isset(self::SUPPORTED_COUNTRIES[$jobCountry])) {
            // Ensure slug has country suffix
            $suffix = '-' . $jobCountry;
            if (!str_ends_with($slug, $suffix)) {
                $countrySlug = $slug . $suffix;
            } else {
                $countrySlug = $slug;
            }
            $urls[] = $this->webUrl . '/' . $jobCountry . '/jobs/' . $countrySlug;
        }
        
        // Also add URLs for other countries where this job might be relevant
        // (e.g., remote jobs that can be done from anywhere)
        $isRemote = $job->location_type === 'remote';
        if ($isRemote) {
            foreach (self::SUPPORTED_COUNTRIES as $countryCode => $countryName) {
                if ($countryCode !== $jobCountry) {
                    $suffix = '-' . $countryCode;
                    if (!str_ends_with($slug, $suffix)) {
                        $countrySlug = $slug . $suffix;
                    } else {
                        $countrySlug = $slug;
                    }
                    $urls[] = $this->webUrl . '/' . $countryCode . '/jobs/' . $countrySlug;
                }
            }
        }
        
        return $urls;
    }

    // =========================================================================
    // CORE — submit URLs to IndexNow
    // =========================================================================
    private function submitToIndexNow(array $urls): array
    {
        if (empty($urls)) {
            return ['success' => false, 'status' => 0, 'message' => 'No URLs provided'];
        }

        $host = parse_url($this->webUrl, PHP_URL_HOST);

        $payload = [
            'host'        => $host,
            'key'         => self::INDEXNOW_KEY,
            'keyLocation' => $this->keyLocation,
            'urlList'     => $urls,
        ];

        try {
            $response = Http::withHeaders([
                'Content-Type' => 'application/json; charset=utf-8',
            ])
            ->timeout(45)
            ->retry(2, 200)
            ->post(self::INDEXNOW_ENDPOINT, $payload);

            $status  = $response->status();
            $success = in_array($status, [200, 202]);

            $message = match($status) {
                200     => 'URLs submitted successfully',
                202     => 'URLs accepted and queued',
                400     => 'Bad request — invalid format',
                403     => 'Forbidden — key not valid or key file not accessible',
                422     => 'Unprocessable — URLs don\'t match host or key schema mismatch',
                429     => 'Too Many Requests — slow down submissions',
                default => "Unexpected status: {$status}",
            };

            Log::info("PING IndexNow: HTTP {$status} — {$message} — " . count($urls) . " URLs");
            Log::debug("URLs submitted: " . json_encode(array_slice($urls, 0, 10)) . (count($urls) > 10 ? " (+" . (count($urls) - 10) . " more)" : ""));

            return [
                'success' => $success,
                'status'  => $status,
                'message' => $message,
            ];

        } catch (\Exception $e) {
            Log::error('PING IndexNow exception: ' . $e->getMessage());
            return [
                'success' => false,
                'status'  => 0,
                'message' => $e->getMessage(),
            ];
        }
    }

    // =========================================================================
    // STATS — for admin modal badge
    // =========================================================================
    public function getStats(): array
    {
        $base = JobPost::where('is_active', true)->where('deadline', '>=', now());

        return [
            'total_active' => (clone $base)->count(),
            'pinged'       => (clone $base)->where('is_pinged', true)->count(),
            'not_pinged'   => (clone $base)->whereNull('last_pinged_at')->count(),
            'failed'       => (clone $base)->whereNotNull('last_pinged_at')->where('is_pinged', false)->count(),
        ];
    }

    // =========================================================================
    // EMAIL REPORT
    // =========================================================================
    private function sendPingReport(array $report): void
    {
        $adminEmails = array_filter(
            array_map('trim', explode(',', env('ADMIN_EMAILS', '')))
        );
        if (empty($adminEmails)) return;

        $icon    = $report['success'] > 0 ? '✅' : '❌';
        $subject = "{$icon} IndexNow Ping — {$report['total']} jobs, {$report['urls_submitted']} URLs — " . now()->format('d M Y H:i');

        $html  = '<!DOCTYPE html><html><head><meta charset="UTF-8"><style>';
        $html .= 'body{font-family:-apple-system,sans-serif;max-width:700px;margin:0 auto;padding:0;background:#f3f4f6;color:#1f2937}';
        $html .= '.hd{background:linear-gradient(135deg,#0ea5e9,#6366f1);color:#fff;padding:28px;text-align:center;border-radius:12px 12px 0 0}';
        $html .= '.bd{background:#fff;padding:24px;border-radius:0 0 12px 12px}';
        $html .= '.stats{display:flex;gap:12px;margin:16px 0;flex-wrap:wrap}';
        $html .= '.s{flex:1;min-width:100px;background:#f9fafb;border:1px solid #e5e7eb;border-radius:8px;padding:14px;text-align:center}';
        $html .= '.s .n{font-size:26px;font-weight:800} .s .l{font-size:11px;color:#6b7280;margin-top:3px}';
        $html .= '.note{background:#f0fdf4;border-left:3px solid #22c55e;padding:12px 14px;border-radius:4px;font-size:13px;margin:16px 0}';
        $html .= '.warning{background:#fef3c7;border-left:3px solid #f59e0b;padding:12px 14px;border-radius:4px;font-size:13px;margin:16px 0}';
        $html .= '.ft{text-align:center;padding:16px;font-size:12px;color:#9ca3af;border-top:1px solid #e5e7eb;margin-top:20px}';
        $html .= 'table{width:100%;border-collapse:collapse;font-size:12px}';
        $html .= 'th{background:#f9fafb;padding:8px 12px;text-align:left;font-size:11px;color:#6b7280}';
        $html .= 'td{padding:8px 12px;border-bottom:1px solid #f3f4f6}';
        $html .= '.ok{color:#10b981;font-weight:600} .fail{color:#ef4444;font-weight:600}';
        $html .= 'code{background:#f3f4f6;padding:2px 6px;border-radius:4px;font-size:11px}';
        $html .= '</style></head><body>';

        $html .= '<div class="hd"><h2 style="margin:0">🌍 IndexNow Ping Report</h2>';
        $html .= '<p style="margin:6px 0 0;opacity:.85;font-size:13px">' . now()->format('l, F j, Y g:i A T') . '</p></div>';
        $html .= '<div class="bd">';

        $html .= '<div class="stats">';
        $html .= '<div class="s"><div class="n">' . $report['total'] . '</div><div class="l">Jobs Processed</div></div>';
        $html .= '<div class="s"><div class="n">' . $report['urls_submitted'] . '</div><div class="l">URLs Submitted</div></div>';
        $html .= '<div class="s"><div class="n ok">' . $report['success'] . '</div><div class="l">Successful</div></div>';
        $html .= '<div class="s"><div class="n fail">' . $report['failed'] . '</div><div class="l">Failed</div></div>';
        $html .= '</div>';

        // IndexNow status
        $statusOk = in_array($report['status'], [200, 202]);
        $noteClass = $statusOk ? 'note' : 'warning';
        $html .= '<div class="' . $noteClass . '">';
        $html .= '<strong>IndexNow API: HTTP ' . $report['status'] . '</strong> — ' . ($report['status'] === 200 ? 'URLs submitted successfully' : ($report['status'] === 202 ? 'URLs accepted and queued' : 'Submission issue — check below'));
        $html .= '</div>';

        // Country-specific info
        $html .= '<div class="note" style="background:#e8f0fe;border-color:#3b82f6">';
        $html .= '<strong>🌍 Country-Specific URLs</strong><br>';
        $html .= 'Each job submits multiple URLs: default URL + country-prefixed URLs for each location.<br>';
        $html .= 'Example: <code>/ke/jobs/plant-operator-job-at-316-barber-shop-in-nairobi-ke</code>';
        $html .= '</div>';

        // Job list summary
        if (!empty($report['jobs'])) {
            $html .= '<h4 style="margin:16px 0 8px">📋 Jobs Processed</h4>';
            $html .= '<table><thead><tr><th>Job Title</th><th>Default URL</th><th>Country URLs</th><th>Status</th></tr></thead><tbody>';
            foreach (array_slice($report['jobs'], 0, 20) as $j) {
                $s = $j['success'] ? '<span class="ok">✅ Success</span>' : '<span class="fail">❌ Failed</span>';
                $countryCount = count($j['country_urls'] ?? []);
                $html .= '<tr>';
                $html .= '<td><strong>' . htmlspecialchars(substr($j['title'], 0, 40)) . '</strong></td>';
                $html .= '<td><a href="' . $j['default_url'] . '" style="color:#6366f1">View</a></td>';
                $html .= '<td>' . $countryCount . ' URL(s)</td>';
                $html .= '<td>' . $s . '</td>';
                $html .= '</tr>';
            }
            if (count($report['jobs']) > 20) {
                $html .= '<tr><td colspan="4" style="text-align:center">... and ' . (count($report['jobs']) - 20) . ' more jobs</td></tr>';
            }
            $html .= '</tbody></table>';
        }

        $html .= '<div style="text-align:center;margin:20px 0">';
        $html .= '<a href="https://www.bing.com/webmasters" style="display:inline-block;background:#6366f1;color:#fff;padding:9px 20px;text-decoration:none;border-radius:7px;font-size:13px;font-weight:600;margin:4px">Bing Webmaster Tools</a>';
        $html .= '<a href="' . $this->webUrl . '/sitemap_index.xml" style="display:inline-block;background:#fff;color:#6366f1;border:2px solid #6366f1;padding:9px 20px;text-decoration:none;border-radius:7px;font-size:13px;font-weight:600;margin:4px">View Sitemap Index</a>';
        $html .= '</div>';

        $html .= '<div class="ft">';
        $html .= 'Stardena Works — IndexNow ping via api.indexnow.org<br>';
        $html .= '<strong>Note:</strong> Both default and country-specific URLs are submitted to help Google and Bing understand geo-targeting.<br>';
        $html .= 'Jobs become visible on the frontend ONLY after successful ping (published_at is set).';
        $html .= '</div></div></body></html>';

        foreach ($adminEmails as $email) {
            try {
                Mail::html($html, fn($m) => $m
                    ->to($email)
                    ->subject($subject)
                    ->from(env('MAIL_FROM_ADDRESS', 'noreply@stardenaworks.com'), 'Stardena Works SEO')
                );
            } catch (\Exception $e) {
                Log::error("Ping report email failed for {$email}: " . $e->getMessage());
            }
        }
    }
}