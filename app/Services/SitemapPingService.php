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
 *           job URLs. Runs hourly via scheduler AND can be triggered
 *           manually from the admin button.
 *
 * IndexNow is a protocol supported by Bing, Yandex, and others.
 * One POST → multiple search engines notified simultaneously.
 * Google does NOT support IndexNow — use GoogleIndexingService for that.
 *
 * API KEY  : b433024ea88249dfa1cae5e8cfacacf9
 * KEY FILE : https://stardenaworks.com/b433024ea88249df1cae5e8cfacacf9.txt
 * 
 * IMPORTANT: published_at remains NULL until ping is successful.
 * Only after successful ping does the job become visible to the frontend API.
 * ─────────────────────────────────────────────────────────────────
 */
class SitemapPingService
{
    private const INDEXNOW_KEY      = 'b433024ea88249dfa1cae5e8cfacacf9';
    private const INDEXNOW_ENDPOINT = 'https://api.indexnow.org/IndexNow';
    private const BATCH_SIZE        = 100; // IndexNow supports up to 10,000 per request
    private const COOLDOWN_MINUTES  = 55;  // Just under 1 hour

    private string $webUrl;
    private string $keyLocation;

    public function __construct()
    {
        $this->webUrl      = rtrim(config('api.web_app.url', env('WEB_APP_URL', 'https://stardenaworks.com')), '/');
        $this->keyLocation = $this->webUrl . '/' . self::INDEXNOW_KEY . '.txt';
    }

    // =========================================================================
    // SCHEDULED — runs every hour via console.php
    // Only picks up jobs that haven't been pinged yet
    // =========================================================================
    public function pingNewJobs(): array
    {
        $jobs = JobPost::where('is_active', true)
            ->where('deadline', '>=', now())
            ->whereNull('last_pinged_at')   // never pinged
            ->whereNotNull('slug')
            ->where('slug', '!=', '')
            ->select(['id', 'job_title', 'slug', 'created_at'])
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

        $result = $this->submitToIndexNow($jobs->pluck('slug')->toArray());

        // Update DB based on result
        foreach ($jobs as $job) {
            if ($result['success']) {
                // ✅ SUCCESS: Update is_pinged, last_pinged_at, AND published_at
                JobPost::where('id', $job->id)->update([
                    'is_pinged'      => true,
                    'last_pinged_at' => now(),
                    'published_at'   => now(), // ← CRITICAL: Job becomes visible to frontend
                ]);
            } else {
                // ❌ FAILED: Only update is_pinged as false, published_at remains NULL
                JobPost::where('id', $job->id)->update([
                    'is_pinged'      => false,
                    'last_pinged_at' => now(),
                    // published_at stays NULL — job NOT visible to frontend
                ]);
            }
        }

        $report = [
            'total'   => $jobs->count(),
            'success' => $result['success'] ? $jobs->count() : 0,
            'failed'  => $result['success'] ? 0 : $jobs->count(),
            'status'  => $result['status'],
            'jobs'    => $jobs->map(fn($j) => [
                'id'      => $j->id,
                'title'   => $j->job_title,
                'url'     => $this->webUrl . '/jobs/' . $j->slug,
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
            // Ping jobs that either failed (is_pinged=false with last_pinged_at set)
            // or have never been pinged
            $query->where(function ($q) {
                $q->whereNull('last_pinged_at')
                  ->orWhere('is_pinged', false);
            });
        }

        $jobs = $query->select(['id', 'job_title', 'slug', 'is_pinged', 'last_pinged_at', 'published_at'])
                      ->limit(self::BATCH_SIZE)
                      ->get();

        if ($jobs->isEmpty()) {
            return ['total' => 0, 'success' => 0, 'failed' => 0, 'jobs' => []];
        }

        \Artisan::call('sitemap:generate');

        $result = $this->submitToIndexNow($jobs->pluck('slug')->toArray());

        foreach ($jobs as $job) {
            if ($result['success']) {
                // ✅ SUCCESS: Update is_pinged, last_pinged_at, AND published_at if still NULL
                $updateData = [
                    'is_pinged'      => true,
                    'last_pinged_at' => now(),
                ];
                // Only set published_at if it's still NULL (first successful ping)
                if (is_null($job->published_at)) {
                    $updateData['published_at'] = now();
                }
                JobPost::where('id', $job->id)->update($updateData);
            } else {
                // ❌ FAILED: Only update is_pinged, published_at remains unchanged
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
            'message' => $result['message'],
            'jobs'    => $jobs->map(fn($j) => [
                'id'      => $j->id,
                'title'   => $j->job_title,
                'url'     => $this->webUrl . '/jobs/' . $j->slug,
                'success' => $result['success'],
            ])->toArray(),
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
            ->get();

        if ($jobs->isEmpty()) {
            return ['submitted' => 0, 'results' => []];
        }

        \Artisan::call('sitemap:generate');

        $result = $this->submitToIndexNow($jobs->pluck('slug')->toArray());
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
                'url' => $this->webUrl . '/jobs/' . $job->slug,
                'success' => $success,
            ];
        }

        return [
            'submitted' => $submitted,
            'total' => $jobs->count(),
            'results' => $results,
            'status' => $result['status'],
        ];
    }

    // =========================================================================
    // CORE — submit slugs to IndexNow
    // =========================================================================
    private function submitToIndexNow(array $slugs): array
    {
        if (empty($slugs)) {
            return ['success' => false, 'status' => 0, 'message' => 'No slugs provided'];
        }

        $urls = array_map(
            fn($slug) => $this->webUrl . '/jobs/' . $slug,
            $slugs
        );

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
            ->timeout(20)
            ->post(self::INDEXNOW_ENDPOINT, $payload);

            $status  = $response->status();
            $success = in_array($status, [200, 202]); // 202 = accepted

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
        $subject = "{$icon} IndexNow Ping — {$report['total']} jobs — " . now()->format('d M Y H:i');

        $html  = '<!DOCTYPE html><html><head><meta charset="UTF-8"><style>';
        $html .= 'body{font-family:-apple-system,sans-serif;max-width:600px;margin:0 auto;padding:0;background:#f3f4f6;color:#1f2937}';
        $html .= '.hd{background:linear-gradient(135deg,#0ea5e9,#6366f1);color:#fff;padding:28px;text-align:center;border-radius:12px 12px 0 0}';
        $html .= '.bd{background:#fff;padding:24px;border-radius:0 0 12px 12px}';
        $html .= '.stats{display:flex;gap:12px;margin:16px 0}';
        $html .= '.s{flex:1;background:#f9fafb;border:1px solid #e5e7eb;border-radius:8px;padding:14px;text-align:center}';
        $html .= '.s .n{font-size:26px;font-weight:800} .s .l{font-size:11px;color:#6b7280;margin-top:3px}';
        $html .= 'table{width:100%;border-collapse:collapse;font-size:13px;margin-top:12px}';
        $html .= 'th{background:#f9fafb;padding:9px 12px;text-align:left;font-size:11px;text-transform:uppercase;color:#6b7280;border-bottom:2px solid #e5e7eb}';
        $html .= 'td{padding:9px 12px;border-bottom:1px solid #f3f4f6}';
        $html .= '.ok{color:#10b981;font-weight:600} .fail{color:#ef4444;font-weight:600}';
        $html .= '.note{background:#f0fdf4;border-left:3px solid #22c55e;padding:12px 14px;border-radius:4px;font-size:13px;margin:16px 0}';
        $html .= '.ft{text-align:center;padding:16px;font-size:12px;color:#9ca3af;border-top:1px solid #e5e7eb;margin-top:20px}';
        $html .= '</style></head><body>';

        $html .= '<div class="hd"><h2 style="margin:0">🔔 IndexNow Ping Report</h2>';
        $html .= '<p style="margin:6px 0 0;opacity:.85;font-size:13px">' . now()->format('l, F j, Y g:i A T') . '</p></div>';
        $html .= '<div class="bd">';

        $html .= '<div class="stats">';
        $html .= '<div class="s"><div class="n">' . $report['total'] . '</div><div class="l">Jobs Pinged</div></div>';
        $html .= '<div class="s"><div class="n ok">' . $report['success'] . '</div><div class="l">Successful</div></div>';
        $html .= '<div class="s"><div class="n fail">' . $report['failed'] . '</div><div class="l">Failed</div></div>';
        $html .= '</div>';

        // IndexNow status
        $statusOk = in_array($report['status'], [200, 202]);
        $html .= '<div class="note" style="background:' . ($statusOk ? '#f0fdf4' : '#fef2f2') . ';border-color:' . ($statusOk ? '#22c55e' : '#ef4444') . '">';
        $html .= '<strong>IndexNow API: HTTP ' . $report['status'] . '</strong> — ' . ($report['status'] === 200 ? 'URLs submitted successfully' : ($report['status'] === 202 ? 'URLs accepted and queued' : 'Submission issue — check below'));
        $html .= '</div>';

        // Job list
        if (!empty($report['jobs'])) {
            $html .= 'table<thead><tr><th>Job</th><th>Status</th><th>Published</th></tr></thead><tbody>';
            foreach ($report['jobs'] as $j) {
                $s = $j['success'] ? '<span class="ok">✅ Pinged</span>' : '<span class="fail">❌ Failed</span>';
                $html .= '<tr>';
                $html .= '<td><strong>' . htmlspecialchars($j['title']) . '</strong><br>';
                $html .= '<a href="' . $j['url'] . '" style="color:#6366f1;font-size:12px">' . $j['url'] . '</a></td>';
                $html .= '<td>' . $s . '</td>';
                $html .= '<td>' . ($j['success'] ? '<span class="ok">✅ Visible</span>' : '<span class="fail">⏳ Not Published</span>') . '</td>';
                $html .= '</tr>';
            }
            $html .= '</tbody></table>';
        }

        $html .= '<div style="text-align:center;margin:20px 0">';
        $html .= '<a href="https://www.bing.com/webmasters" style="display:inline-block;background:#6366f1;color:#fff;padding:9px 20px;text-decoration:none;border-radius:7px;font-size:13px;font-weight:600;margin:4px">Bing Webmaster Tools</a>';
        $html .= '<a href="' . $this->webUrl . '/sitemap.xml" style="display:inline-block;background:#fff;color:#6366f1;border:2px solid #6366f1;padding:9px 20px;text-decoration:none;border-radius:7px;font-size:13px;font-weight:600;margin:4px">View Sitemap</a>';
        $html .= '</div>';

        $html .= '<div class="ft">Stardena Works — IndexNow ping via api.indexnow.org<br>';
        $html .= '<strong>Note:</strong> Jobs become visible on the frontend ONLY after successful ping (published_at is set).</div>';
        $html .= '</div></body></html>';

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