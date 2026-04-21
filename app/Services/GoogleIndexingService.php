<?php

namespace App\Services;

use App\Models\Job\JobPost;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class GoogleIndexingService
{
    private const DAILY_LIMIT    = 200;
    private const QUOTA_CACHE_KEY = 'google_indexing_daily_quota';
    private const TOKEN_CACHE_KEY = 'google_indexing_access_token';
    private const API_ENDPOINT   = 'https://indexing.googleapis.com/v3/urlNotifications:publish';

    private string $webUrl;

    public function __construct()
    {
        $this->webUrl = rtrim(config('api.web_app.url', env('WEB_APP_URL', 'https://stardenaworks.com')), '/');
    }

    // =========================================================================
    // PUBLIC: Submit a single job by ID (called from status modal button)
    // =========================================================================
    public function submitJob(int $jobId): array
    {
        $job = JobPost::find($jobId);
        if (!$job) {
            return ['success' => false, 'message' => 'Job not found'];
        }

        if (!$job->is_active) {
            return ['success' => false, 'message' => 'Job is not active — only active jobs should be indexed'];
        }

        $url = $this->webUrl . '/jobs/' . $job->slug;
        return $this->submitUrl($url, $job);
    }

    // =========================================================================
    // PUBLIC: Bulk submit from admin (respects daily quota)
    // =========================================================================
    public function submitBatch(array $jobIds): array
    {
        $remaining = $this->getRemainingQuota();

        if ($remaining <= 0) {
            return [
                'success'    => false,
                'submitted'  => 0,
                'skipped'    => count($jobIds),
                'message'    => 'Daily Google quota of ' . self::DAILY_LIMIT . ' URLs reached. Resets at midnight UTC.',
                'quota_used' => $this->getQuotaUsed(),
                'results'    => [],
            ];
        }

        $jobIds    = array_slice($jobIds, 0, $remaining);
        $jobs      = JobPost::whereIn('id', $jobIds)->where('is_active', true)->get();
        $results   = [];
        $submitted = 0;
        $failed    = 0;

        $token = $this->getAccessToken();
        if (!$token) {
            return [
                'success'   => false,
                'submitted' => 0,
                'message'   => 'Google Indexing API not configured. Upload service account JSON to storage/app/google-service-account.json',
                'results'   => [],
            ];
        }

        foreach ($jobs as $job) {
            $url    = $this->webUrl . '/jobs/' . $job->slug;
            $result = $this->callGoogleApi($url, $token);
            $this->updateJobIndexingStatus($job, $result);

            $results[] = [
                'job_id'  => $job->id,
                'title'   => $job->job_title,
                'url'     => $url,
                'success' => $result['success'],
                'status'  => $result['status'],
                'message' => $result['message'],
            ];

            if ($result['success']) {
                $submitted++;
                $this->incrementQuota();
            } else {
                $failed++;
            }

            usleep(200000);
        }

        $report = [
            'success'    => $submitted > 0,
            'submitted'  => $submitted,
            'failed'     => $failed,
            'quota_used' => $this->getQuotaUsed(),
            'quota_left' => $this->getRemainingQuota(),
            'results'    => $results,
        ];

        if ($submitted > 0) {
            $this->sendIndexingReport($report);
        }

        return $report;
    }

    // =========================================================================
    // PUBLIC: Stats for admin modal
    // =========================================================================
    public function getStats(): array
    {
        $base = JobPost::where('is_active', true)->where('deadline', '>=', now());

        return [
            'quota_used'      => $this->getQuotaUsed(),
            'quota_remaining' => $this->getRemainingQuota(),
            'quota_limit'     => self::DAILY_LIMIT,
            'not_submitted'   => (clone $base)->where('submitted_to_indexing', false)->orWhereNull('submitted_to_indexing')->count(),
            'submitted'       => (clone $base)->where('submitted_to_indexing', true)->count(),
            'indexed'         => (clone $base)->where('is_indexed', true)->count(),
            'api_configured'  => file_exists(storage_path('app/google-service-account.json')),
        ];
    }

    // =========================================================================
    // PUBLIC: Verify indexing status for a single job
    // =========================================================================
    public function verifyIndexingStatus(int $jobId): array
    {
        $job = JobPost::find($jobId);
        if (!$job) {
            return [
                'success' => false,
                'indexed' => false,
                'message' => 'Job not found',
                'error' => 'Job not found'
            ];
        }

        $url = $this->webUrl . '/jobs/' . $job->slug;
        $token = $this->getSearchConsoleAccessToken();

        if (!$token) {
            return [
                'success' => false,
                'indexed' => false,
                'message' => 'Search Console API not configured. Make sure service account is added as owner in Search Console.',
                'error' => 'Search Console API not configured'
            ];
        }

        try {
            $response = Http::withToken($token)
                ->timeout(15)
                ->post('https://searchconsole.googleapis.com/v1/urlInspection/index:inspect', [
                    'inspectionUrl' => $url,
                    'siteUrl'       => $this->webUrl,
                ]);

            if (!$response->successful()) {
                $errorMsg = $response->json('error.message') ?? "HTTP {$response->status()}";
                return [
                    'success' => false,
                    'indexed' => false,
                    'message' => $errorMsg,
                    'error' => $errorMsg,
                    'status' => $response->status()
                ];
            }

            $data = $response->json();
            $verdict = $data['inspectionResult']['indexStatusResult']['verdict'] ?? 'unknown';
            $indexed = in_array($verdict, ['PASS', 'NEUTRAL']);

            // Update job record
            JobPost::where('id', $job->id)->update([
                'is_indexed' => $indexed,
                'last_indexed_check' => now(),
                'index_verified_at' => $indexed ? now() : null,
                'indexing_response' => json_encode(array_merge(
                    json_decode($job->indexing_response ?? '{}', true) ?: [],
                    [
                        'verified_at' => now()->toISOString(),
                        'verdict' => $verdict,
                        'coverage' => $data['inspectionResult']['indexStatusResult']['coverage'] ?? null,
                        'crawled_at' => $data['inspectionResult']['indexStatusResult']['lastCrawlTime'] ?? null
                    ]
                )),
            ]);

            Log::info("INDEX VERIFIED: {$url} — Verdict: {$verdict} — " . ($indexed ? '✅ Indexed' : '❌ Not indexed'));

            return [
                'success' => true,
                'indexed' => $indexed,
                'verdict' => $verdict,
                'message' => $indexed ? 'Job is indexed by Google' : 'Job is not yet indexed',
                'details' => $data['inspectionResult']['indexStatusResult'] ?? []
            ];

        } catch (\Exception $e) {
            Log::error("Index verification failed for {$url}: " . $e->getMessage());
            return [
                'success' => false,
                'indexed' => false,
                'message' => $e->getMessage(),
                'error' => $e->getMessage()
            ];
        }
    }

    // =========================================================================
    // PRIVATE: Call Google Indexing API
    // =========================================================================
    private function submitUrl(string $url, JobPost $job): array
    {
        if ($this->getRemainingQuota() <= 0) {
            return [
                'success' => false,
                'message' => 'Daily quota of ' . self::DAILY_LIMIT . ' reached. Resets at midnight UTC.',
            ];
        }

        $token = $this->getAccessToken();
        if (!$token) {
            return ['success' => false, 'message' => 'Google service account not configured'];
        }

        $result = $this->callGoogleApi($url, $token);
        $this->updateJobIndexingStatus($job, $result);

        if ($result['success']) $this->incrementQuota();

        return $result;
    }

    private function callGoogleApi(string $url, string $token): array
    {
        try {
            $response = Http::withToken($token)
                ->timeout(15)
                ->post(self::API_ENDPOINT, [
                    'url'  => $url,
                    'type' => 'URL_UPDATED',
                ]);

            $status  = $response->status();
            $body    = $response->json();
            $success = $response->successful();

            $message = match($status) {
                200     => 'URL submitted to Google index queue',
                400     => 'Bad request — ' . ($body['error']['message'] ?? 'invalid format'),
                401     => 'Unauthorized — check service account permissions',
                403     => 'Forbidden — add service account as Owner in Search Console',
                429     => 'Quota exceeded — too many requests',
                default => "HTTP {$status}: " . ($body['error']['message'] ?? 'Unknown'),
            };

            Log::info("GOOGLE INDEXING: HTTP {$status} — {$url}");

            return [
                'success'  => $success,
                'status'   => $status,
                'message'  => $message,
                'response' => $body,
            ];

        } catch (\Exception $e) {
            Log::error("GOOGLE INDEXING exception for {$url}: " . $e->getMessage());
            return [
                'success'  => false,
                'status'   => 0,
                'message'  => $e->getMessage(),
                'response' => [],
            ];
        }
    }

    private function updateJobIndexingStatus(JobPost $job, array $result): void
    {
        $status = $result['success'] ? 'submitted' : 'failed';

        if ($result['status'] === 403) $status = 'forbidden';
        if ($result['status'] === 429) $status = 'quota_exceeded';
        if ($result['status'] === 401) $status = 'unauthorized';

        JobPost::where('id', $job->id)->update([
            'submitted_to_indexing' => $result['success'],
            'indexing_submitted_at' => $result['success'] ? now() : $job->indexing_submitted_at,
            'indexing_status'       => $status,
            'indexing_response'     => json_encode([
                'submitted_at' => now()->toISOString(),
                'status'       => $result['status'],
                'message'      => $result['message'],
                'response'     => $result['response'] ?? [],
            ]),
        ]);
    }

    // =========================================================================
    // PRIVATE: Google JWT auth
    // =========================================================================
    private function getAccessToken(): ?string
    {
        if ($cached = Cache::get(self::TOKEN_CACHE_KEY)) {
            return $cached;
        }

        $possiblePaths = [
            storage_path('app/google-service-account.json'),
            storage_path('google-service-account.json'),
            base_path('google-service-account.json'),
        ];
        
        $keyPath = null;
        foreach ($possiblePaths as $path) {
            if (file_exists($path)) {
                $keyPath = $path;
                break;
            }
        }
        
        if (!$keyPath) {
            Log::warning('Service account file not found for Indexing API');
            return null;
        }

        try {
            $key = json_decode(file_get_contents($keyPath), true);
            $now = time();

            $header = $this->base64UrlEncode(json_encode(['alg' => 'RS256', 'typ' => 'JWT']));
            $payload = $this->base64UrlEncode(json_encode([
                'iss'   => $key['client_email'],
                'scope' => 'https://www.googleapis.com/auth/indexing',
                'aud'   => 'https://oauth2.googleapis.com/token',
                'exp'   => $now + 3600,
                'iat'   => $now,
            ]));

            $signingInput = "{$header}.{$payload}";
            $signature = '';
            openssl_sign($signingInput, $signature, $key['private_key'], 'SHA256');
            $jwt = $signingInput . '.' . $this->base64UrlEncode($signature);

            $response = Http::asForm()->post('https://oauth2.googleapis.com/token', [
                'grant_type' => 'urn:ietf:params:oauth:grant-type:jwt-bearer',
                'assertion'  => $jwt,
            ]);

            if (!$response->successful()) {
                Log::error('Indexing API token request failed: ' . $response->body());
                return null;
            }

            $token = $response->json('access_token');
            Cache::put(self::TOKEN_CACHE_KEY, $token, now()->addSeconds(3500));
            return $token;

        } catch (\Exception $e) {
            Log::error('Indexing API token failed: ' . $e->getMessage());
            return null;
        }
    }

    private function getSearchConsoleAccessToken(): ?string
    {
        $cacheKey = 'google_search_console_token';
        
        if ($cached = Cache::get($cacheKey)) {
            return $cached;
        }

        $possiblePaths = [
            storage_path('app/google-service-account.json'),
            storage_path('google-service-account.json'),
            base_path('google-service-account.json'),
        ];
        
        $keyPath = null;
        foreach ($possiblePaths as $path) {
            if (file_exists($path)) {
                $keyPath = $path;
                break;
            }
        }
        
        if (!$keyPath) {
            Log::warning('Service account file not found for Search Console API');
            return null;
        }

        try {
            $key = json_decode(file_get_contents($keyPath), true);
            $now = time();

            $header = $this->base64UrlEncode(json_encode(['alg' => 'RS256', 'typ' => 'JWT']));
            $payload = $this->base64UrlEncode(json_encode([
                'iss'   => $key['client_email'],
                'scope' => 'https://www.googleapis.com/auth/webmasters.readonly',
                'aud'   => 'https://oauth2.googleapis.com/token',
                'exp'   => $now + 3600,
                'iat'   => $now,
            ]));

            $signingInput = "{$header}.{$payload}";
            $signature = '';
            openssl_sign($signingInput, $signature, $key['private_key'], 'SHA256');
            $jwt = $signingInput . '.' . $this->base64UrlEncode($signature);

            $response = Http::asForm()->post('https://oauth2.googleapis.com/token', [
                'grant_type' => 'urn:ietf:params:oauth:grant-type:jwt-bearer',
                'assertion'  => $jwt,
            ]);

            if (!$response->successful()) {
                Log::error('Search Console token request failed: ' . $response->body());
                return null;
            }

            $token = $response->json('access_token');
            Cache::put($cacheKey, $token, now()->addSeconds(3500));
            return $token;

        } catch (\Exception $e) {
            Log::error('Search Console token failed: ' . $e->getMessage());
            return null;
        }
    }

    private function base64UrlEncode(string $data): string
    {
        return rtrim(strtr(base64_encode($data), '+/', '-_'), '=');
    }

    private function getQuotaUsed(): int
    {
        return (int) Cache::get(self::QUOTA_CACHE_KEY, 0);
    }

    private function getRemainingQuota(): int
    {
        return max(0, self::DAILY_LIMIT - $this->getQuotaUsed());
    }

    private function incrementQuota(): void
    {
        $secondsUntilMidnight = strtotime('tomorrow midnight UTC') - time();

        if (Cache::has(self::QUOTA_CACHE_KEY)) {
            Cache::increment(self::QUOTA_CACHE_KEY);
        } else {
            Cache::put(self::QUOTA_CACHE_KEY, 1, $secondsUntilMidnight);
        }
    }

    private function sendIndexingReport(array $report): void
    {
        $adminEmails = array_filter(array_map('trim', explode(',', env('ADMIN_EMAILS', ''))));
        if (empty($adminEmails)) return;

        $icon    = $report['submitted'] > 0 ? '✅' : '❌';
        $subject = "{$icon} Google Indexing — {$report['submitted']} submitted — quota {$report['quota_used']}/200 — " . now()->format('d M Y H:i');

        $html = '<!DOCTYPE html><html><head><meta charset="UTF-8"><style>';
        $html .= 'body{font-family:-apple-system,sans-serif;max-width:600px;margin:0 auto;padding:0;background:#f3f4f6;color:#1f2937}';
        $html .= '.hd{background:linear-gradient(135deg,#ea4335,#4285f4);color:#fff;padding:28px;text-align:center;border-radius:12px 12px 0 0}';
        $html .= '.bd{background:#fff;padding:24px;border-radius:0 0 12px 12px}';
        $html .= '.stats{display:flex;gap:12px;margin:16px 0}';
        $html .= '.s{flex:1;background:#f9fafb;border:1px solid #e5e7eb;border-radius:8px;padding:14px;text-align:center}';
        $html .= '.s .n{font-size:26px;font-weight:800} .s .l{font-size:11px;color:#6b7280;margin-top:3px}';
        $html .= '.quota{background:#fffbeb;border:1px solid #fde68a;border-radius:8px;padding:14px;margin:16px 0;font-size:13px}';
        $html .= '.qbar{background:#e5e7eb;border-radius:99px;height:10px;margin:8px 0}';
        $html .= '.qfill{background:linear-gradient(90deg,#10b981,#3b82f6);border-radius:99px;height:10px}';
        $html .= 'table{width:100%;border-collapse:collapse;font-size:13px}';
        $html .= 'th{background:#f9fafb;padding:9px 12px;text-align:left;font-size:11px;text-transform:uppercase;color:#6b7280;border-bottom:2px solid #e5e7eb}';
        $html .= 'td{padding:9px 12px;border-bottom:1px solid #f3f4f6}';
        $html .= '.ok{color:#10b981;font-weight:600} .fail{color:#ef4444;font-weight:600}';
        $html .= '.ft{text-align:center;padding:16px;font-size:12px;color:#9ca3af;border-top:1px solid #e5e7eb;margin-top:20px}';
        $html .= '</style></head><body>';

        $html .= '<div class="hd"><h2 style="margin:0">🔍 Google Indexing Report</h2>';
        $html .= '<p style="margin:6px 0 0;opacity:.85;font-size:13px">' . now()->format('l, F j, Y g:i A T') . '</p></div>';
        $html .= '<div class="bd">';
        $html .= '<div class="stats">';
        $html .= '<div class="s"><div class="n ok">' . $report['submitted'] . '</div><div class="l">Submitted</div></div>';
        $html .= '<div class="s"><div class="n fail">' . $report['failed'] . '</div><div class="l">Failed</div></div>';
        $html .= '<div class="s"><div class="n">' . $report['quota_used'] . '/200</div><div class="l">Daily Quota</div></div>';
        $html .= '<div class="s"><div class="n">' . $report['quota_left'] . '</div><div class="l">URLs Remaining</div></div>';
        $html .= '</div>';
        $html .= '<div class="quota"><strong>Daily Quota: ' . $report['quota_used'] . ' / ' . self::DAILY_LIMIT . ' URLs used (' . round(($report['quota_used'] / self::DAILY_LIMIT) * 100) . '%)</strong>';
        $html .= '<div class="qbar"><div class="qfill" style="width:' . round(($report['quota_used'] / self::DAILY_LIMIT) * 100) . '%"></div></div>';
        $html .= '<small style="color:#6b7280">Quota resets at midnight UTC. Only submit high-priority jobs.</small></div>';
        $html .= '<h3 style="margin-top:0">📋 Submission Results</h3>';
        $html .= '<table><thead><tr><th>Job</th><th>Result</th><th>Detail</th></tr></thead><tbody>';
        foreach ($report['results'] as $r) {
            $s = $r['success'] ? '<span class="ok">✅ Submitted</span>' : '<span class="fail">❌ Failed</span>';
            $html .= '<tr><td><strong>' . htmlspecialchars($r['title']) . '</strong><br><a href="' . $r['url'] . '" style="color:#4285f4;font-size:12px">' . basename($r['url']) . '</a></td>';
            $html .= '<td>' . $s . '</td>';
            $html .= '<td style="font-size:12px;color:#6b7280">' . htmlspecialchars($r['message']) . '</td></tr>';
        }
        $html .= '</tbody></table>';
        $html .= '<div style="text-align:center;margin:20px 0"><a href="https://search.google.com/search-console" style="display:inline-block;background:#4285f4;color:#fff;padding:9px 20px;text-decoration:none;border-radius:7px;font-size:13px;font-weight:600;margin:4px">Open Search Console</a></div>';
        $html .= '<div class="ft">Stardena Works — Google Indexing API • Max 200 URLs/day</div>';
        $html .= '</div></body></html>';

        foreach ($adminEmails as $email) {
            try {
                Mail::html($html, fn($m) => $m->to($email)->subject($subject)->from(env('MAIL_FROM_ADDRESS', 'noreply@stardenaworks.com'), 'Stardena Works SEO'));
            } catch (\Exception $e) {
                Log::error("Indexing report email failed for {$email}: " . $e->getMessage());
            }
        }
    }
}