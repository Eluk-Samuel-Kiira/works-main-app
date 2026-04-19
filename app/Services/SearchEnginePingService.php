<?php
namespace App\Services;

use App\Models\Job\JobPost;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Collection;

class SearchEnginePingService
{
    public const PING_COOLDOWN_MINUTES = 60;

    // ── Google Indexing API ───────────────────────────────────────────────────
    private function getGoogleAccessToken(): ?string
    {
        $keyPath = storage_path('app/google-service-account.json');
        if (!file_exists($keyPath)) {
            Log::warning('Google service account key not found at: ' . $keyPath);
            return null;
        }

        try {
            $key = json_decode(file_get_contents($keyPath), true);
            $now = time();
            $claim = [
                'iss'   => $key['client_email'],
                'scope' => 'https://www.googleapis.com/auth/indexing',
                'aud'   => 'https://oauth2.googleapis.com/token',
                'exp'   => $now + 3600,
                'iat'   => $now,
            ];

            // URL-safe base64 helper
            $base64Url = fn($data) => rtrim(strtr(base64_encode(json_encode($data)), '+/', '-_'), '=');
            $signBase64Url = fn($data) => rtrim(strtr(base64_encode($data), '+/', '-_'), '=');

            $header    = $base64Url(['alg' => 'RS256', 'typ' => 'JWT']);
            $payload   = $base64Url($claim);
            $signature = '';
            
            openssl_sign("{$header}.{$payload}", $signature, $key['private_key'], 'SHA256');
            
            $jwt = "{$header}.{$payload}." . $signBase64Url($signature);

            $response = Http::asForm()->post('https://oauth2.googleapis.com/token', [
                'grant_type' => 'urn:ietf:params:oauth:grant-type:jwt-bearer',
                'assertion'  => $jwt,
            ]);

            return $response->json('access_token');
        } catch (\Exception $e) {
            Log::error('Google OAuth failed: ' . $e->getMessage());
            return null;
        }
    }

    private function submitToGoogleIndexingApi(string $url, string $accessToken): array
    {
        try {
            $response = Http::withToken($accessToken)
                ->timeout(15)
                ->post('https://indexing.googleapis.com/v3/urlNotifications:publish', [
                    'url'  => $url,
                    'type' => 'URL_UPDATED',
                ]);

            $success = $response->successful();
            $status = $response->status();
            $responseData = $response->json();
            
            // Extract error message if any
            $error = null;
            if (!$success && isset($responseData['error'])) {
                $error = $responseData['error']['message'] ?? json_encode($responseData['error']);
            }

            return [
                'success' => $success,
                'status' => $status,
                'response' => $error ?: $responseData,
                'engine' => 'google',
            ];
        } catch (\Exception $e) {
            return [
                'success' => false,
                'status' => 0,
                'response' => ['error' => $e->getMessage()],
                'engine' => 'google',
            ];
        }
    }


    private function submitToBingIndexingApi(string $url): array
    {
        $apiKey = config('services.bing.indexing_api_key');
        $siteUrl = rtrim(config('api.web_app.url'), '/');

        if (empty($apiKey) || empty($siteUrl)) {
            return [
                'success' => false,
                'status' => 0,
                'response' => [
                    'error' => 'Missing configuration: BING_INDEXING_API_KEY or site URL not set.'
                ],
                'engine' => 'bing',
            ];
        }

        $endpoint = 'https://ssl.bing.com/webmaster/api.svc/json/SubmitUrlBatch';

        try {
            $response = Http::timeout(15)
                ->acceptJson()
                ->asJson()
                ->post($endpoint . '?apikey=' . urlencode($apiKey), [
                    'siteUrl' => $siteUrl,
                    'urlList' => [$url],
                ]);

            $status = $response->status();
            $body = $response->json();

            // Default result
            $result = [
                'success' => false,
                'status' => $status,
                'response' => $body,
                'engine' => 'bing',
            ];

            // HTTP-level failure
            if (!$response->successful()) {
                $result['response'] = [
                    'error' => $body['error']['message']
                        ?? $body['message']
                        ?? "HTTP error {$status}",
                ];
                return $result;
            }

            // Bing-specific error handling (important)
            if (isset($body['d']['ErrorCode']) && $body['d']['ErrorCode'] != 0) {
                return [
                    'success' => false,
                    'status' => $status,
                    'response' => [
                        'error' => $body['d']['Message'] ?? 'Unknown Bing API error',
                        'code' => $body['d']['ErrorCode'],
                    ],
                    'engine' => 'bing',
                ];
            }

            // Success case
            return [
                'success' => true,
                'status' => $status,
                'response' => $body['d'] ?? $body,
                'engine' => 'bing',
            ];

        } catch (\Illuminate\Http\Client\ConnectionException $e) {
            return [
                'success' => false,
                'status' => 0,
                'response' => ['error' => 'Connection timeout or network issue'],
                'engine' => 'bing',
            ];
        } catch (\Throwable $e) {
            return [
                'success' => false,
                'status' => 0,
                'response' => ['error' => $e->getMessage()],
                'engine' => 'bing',
            ];
        }
    }

    // ── Submit sitemap to Yandex (still works) ────────────────────────────────
    private function pingYandex(string $sitemapUrl): array
    {
        try {
            $response = Http::timeout(10)
                ->get('https://webmaster.yandex.com/ping', ['sitemap' => $sitemapUrl]);

            return [
                'success' => $response->successful(),
                'status'  => $response->status(),
                'engine'  => 'yandex',
            ];
        } catch (\Exception $e) {
            return ['success' => false, 'status' => 0, 'engine' => 'yandex'];
        }
    }

    // ── Main: submit new jobs ─────────────────────────────────────────────────
    public function pingIfNewJobs(): void
    {
        $newJobs = JobPost::where('is_active', true)
            ->where('created_at', '>=', now()->subHour())
            ->whereNull('indexing_submitted_at')
            ->select(['id', 'job_title', 'slug', 'created_at'])
            ->get();

        if ($newJobs->isEmpty()) {
            Log::info('SEO: No new unsubmitted jobs in last hour.');
            return;
        }

        Log::info("SEO: Found {$newJobs->count()} new job(s) — processing.");

        // Regenerate sitemap first
        \Artisan::call('sitemap:generate');
        Log::info('SEO: Sitemap regenerated.');

        // Get Google access token once for all URLs
        $googleToken = $this->getGoogleAccessToken();
        if (!$googleToken) {
            Log::warning('SEO: Google Indexing API token unavailable — skipping Google submissions.');
        }

        $webUrl  = rtrim(config('api.web_app.url'), '/');
        $results = [];

        foreach ($newJobs as $job) {
            $url    = $webUrl . '/jobs/' . $job->slug;
            $result = ['job_id' => $job->id, 'url' => $url, 'google' => null, 'bing' => null];

            // Google Indexing API
            if ($googleToken) {
                $result['google'] = $this->submitToGoogleIndexingApi($url, $googleToken);
                usleep(200000);
            }

            // Bing Webmaster API
            $result['bing'] = $this->submitToBingIndexingApi($url);

            // Update job record
            $googleSuccess = $result['google']['success'] ?? false;
            $bingSuccess   = $result['bing']['success']   ?? false;

            JobPost::where('id', $job->id)->update([
                'is_pinged'              => $googleSuccess || $bingSuccess,
                'last_pinged_at'         => now(),
                'submitted_to_indexing'  => $googleSuccess,
                'indexing_submitted_at'  => $googleSuccess ? now() : null,
                'indexing_status'        => $this->deriveStatus($googleSuccess, $bingSuccess),
                'indexing_response'      => json_encode([
                    'google' => $result['google'],
                    'bing'   => $result['bing'],
                ]),
            ]);

            $results[] = $result;

            // FIXED: Safely build log messages without array to string conversion
            $googleStatusText = '⏭ skipped';
            if ($googleToken) {
                if ($googleSuccess) {
                    $googleStatusText = '✅ ' . ($result['google']['status'] ?? 'success');
                } else {
                    $errorData = $result['google']['response']['error'] ?? null;
                    if (is_array($errorData)) {
                        $errorData = json_encode($errorData);
                    }
                    $googleStatusText = '❌ ' . ($errorData ?? ($result['google']['status'] ?? 'unknown error'));
                }
            }
            
            $bingErrorData = $result['bing']['response']['error'] ?? null;
            if (is_array($bingErrorData)) {
                $bingErrorData = json_encode($bingErrorData);
            }
            $bingStatusText = $bingSuccess 
                ? '✅ ' . ($result['bing']['status'] ?? 'success') 
                : '❌ ' . ($bingErrorData ?? ($result['bing']['status'] ?? 'unknown error'));

            Log::info("SEO: Job #{$job->id} ({$job->job_title})", [
                'google' => $googleStatusText,
                'bing'   => $bingStatusText,
            ]);
        }

        // Ping Yandex with sitemap
        $yandex = $this->pingYandex($webUrl . '/sitemap.xml');

        // Send report
        $this->sendReport($newJobs, $results, $yandex, $googleToken !== null);
    }

    /**
     * Check if we have Google quota available
     */
    public function getGoogleQuotaStatus(): array
    {
        $googleToken = $this->getGoogleAccessToken();
        if (!$googleToken) {
            return ['available' => false, 'error' => 'No token', 'remaining' => 0];
        }
        
        try {
            // Google doesn't provide a quota endpoint, so we track in cache
            $today = now()->toDateString();
            $submittedToday = Cache::get('google_indexing_today_' . $today, 0);
            $remaining = max(0, 200 - $submittedToday);
            
            return [
                'available' => $remaining > 0,
                'remaining' => $remaining,
                'submitted_today' => $submittedToday,
                'limit' => 200,
            ];
        } catch (\Exception $e) {
            return ['available' => false, 'error' => $e->getMessage(), 'remaining' => 0];
        }
    }


    /**
     * Manually ping jobs to Google/Bing indexing APIs
     * @param array $jobIds Array of job post IDs
     * @return array { submitted: int, results: array, message: string }
     */
    public function manualPingJobs(array $jobIds): array
    {
        $results = [];
        $submitted = 0;
        $webUrl = rtrim(config('api.web_app.url', config('app.url')), '/');

        $today = now()->toDateString();
        $googleSubmittedToday = Cache::get("google_indexing_today_{$today}", 0);
        $googleQuotaRemaining = max(0, 200 - $googleSubmittedToday);

        // ── Google Token ─────────────────────────────────────────────
        $googleToken = null;
        if ($googleQuotaRemaining > 0) {
            try {
                $googleToken = $this->getGoogleAccessToken();
            } catch (\Throwable $e) {
                Log::error('Google token error: ' . $e->getMessage());
            }
        }

        $googleAvailable = !empty($googleToken) && $googleQuotaRemaining > 0;

        // Limit jobs correctly
        $limit = $googleAvailable ? $googleQuotaRemaining : min(count($jobIds), 10);

        $jobs = JobPost::whereIn('id', $jobIds)
            ->where('is_active', true)
            ->where('deadline', '>=', now())
            ->limit($limit)
            ->get(['id', 'job_title', 'slug']);

        foreach ($jobs as $job) {
            $url = "{$webUrl}/jobs/{$job->slug}";

            // ── GOOGLE ────────────────────────────────────────────────
            $googleResult = [
                'success' => false,
                'status' => null,
                'error' => null,
            ];

            if ($googleAvailable && $googleSubmittedToday < 200) {
                try {
                    $response = Http::timeout(10)
                        ->withToken($googleToken)
                        ->post('https://indexing.googleapis.com/v3/urlNotifications:publish', [
                            'url' => $url,
                            'type' => 'URL_UPDATED',
                        ]);

                    $status = $response->status();

                    if ($response->successful()) {
                        $googleResult['success'] = true;
                        $submitted++;
                        $googleSubmittedToday++;

                        Cache::put(
                            "google_indexing_today_{$today}",
                            $googleSubmittedToday,
                            now()->endOfDay()
                        );
                    } else {
                        $body = $response->json();
                        $googleResult['error'] =
                            $body['error']['message']
                            ?? $body['error']['code']
                            ?? "HTTP {$status}";
                    }

                    $googleResult['status'] = $status;

                } catch (\Throwable $e) {
                    $googleResult['error'] = $e->getMessage();
                    $googleResult['status'] = 0;
                }

                usleep(200000); // 200ms
            } else {
                $googleResult['error'] = $googleQuotaRemaining <= 0
                    ? 'Google quota exceeded (200/day)'
                    : 'Google unavailable';
            }

            // ── BING (FIXED) ─────────────────────────────────────────
            $bingResult = [
                'success' => false,
                'status' => null,
                'error' => null,
            ];

            try {
                $bing = $this->submitToBingIndexingApi($url); // reuse your helper

                $bingResult = [
                    'success' => $bing['success'],
                    'status' => $bing['status'],
                    'error' => $bing['success'] ? null : ($bing['response']['error'] ?? 'Unknown error'),
                ];

            } catch (\Throwable $e) {
                $bingResult['error'] = $e->getMessage();
                $bingResult['status'] = 0;
            }

            // ── STATUS LOGIC ─────────────────────────────────────────
            $googleSuccess = $googleResult['success'];
            $bingSuccess = $bingResult['success'];

            $status = match (true) {
                $googleSuccess && $bingSuccess => 'submitted',
                $googleSuccess => 'google_only',
                $bingSuccess => 'bing_only',
                default => 'failed',
            };

            // ── UPDATE DB ────────────────────────────────────────────
            JobPost::where('id', $job->id)->update([
                'submitted_to_indexing' => $googleSuccess,
                'indexing_submitted_at' => $googleSuccess ? now() : null,
                'is_pinged' => $googleSuccess || $bingSuccess,
                'last_pinged_at' => now(),
                'indexing_status' => $status,
                'indexing_response' => json_encode([
                    'google' => $googleResult,
                    'bing' => $bingResult,
                    'timestamp' => now()->toIso8601String(),
                ]),
            ]);

            $results[] = [
                'job_id' => $job->id,
                'title' => $job->job_title,
                'url' => $url,
                'google' => $googleResult,
                'bing' => $bingResult,
                'fully_submitted' => $googleSuccess && $bingSuccess,
            ];
        }

        return [
            'submitted' => $submitted,
            'total' => count($jobs),
            'message' => "{$submitted}/" . count($jobs) .
                " submitted to Google (used {$googleSubmittedToday}/200)",
            'results' => $results,
            'google_available' => $googleAvailable,
            'quota_remaining' => max(0, 200 - $googleSubmittedToday),
        ];
    }

    /**
     * Send detailed report for manual ping
     */
    private function sendManualPingReport(array $results, bool $googleAvailable): void
    {
        $adminEmails = array_filter(array_map('trim', explode(',', env('ADMIN_EMAILS', ''))));
        if (empty($adminEmails)) return;
        
        $total = count($results);
        $successful = count(array_filter($results, fn($r) => $r['fully_submitted']));
        $failed = $total - $successful;
        
        $subject = "📊 Manual Indexing Report - {$successful}/{$total} successful - " . now()->format('Y-m-d H:i');
        
        $html = '<!DOCTYPE html><html><head><meta charset="UTF-8"><style>';
        $html .= 'body{font-family:Arial,sans-serif;margin:0;padding:20px;background:#f4f4f4}';
        $html .= '.container{max-width:800px;margin:0 auto;background:#fff;border-radius:8px;padding:20px;box-shadow:0 2px 10px rgba(0,0,0,0.1)}';
        $html .= 'h1{color:#333;border-bottom:2px solid #4f46e5;padding-bottom:10px}';
        $html .= '.summary{background:#f0fdf4;border-left:4px solid #22c55e;padding:15px;margin:20px 0}';
        $html .= '.summary-error{background:#fef2f2;border-left:4px solid #ef4444}';
        $html .= 'table{width:100%;border-collapse:collapse;margin-top:20px}';
        $html .= 'th,td{padding:12px;text-align:left;border-bottom:1px solid #ddd}';
        $html .= 'th{background:#f8f9fa;font-weight:600}';
        $html .= '.success{color:#22c55e;font-weight:bold}';
        $html .= '.error{color:#ef4444;font-weight:bold}';
        $html .= '.pending{color:#eab308;font-weight:bold}';
        $html .= 'pre{background:#f8f9fa;padding:10px;border-radius:4px;overflow-x:auto;font-size:11px;margin:5px 0 0 0}';
        $html .= '</style></head><body>';
        $html .= '<div class="container">';
        $html .= '<h1>🔍 Manual Indexing Report</h1>';
        $html .= '<p><strong>Generated:</strong> ' . now()->format('Y-m-d H:i:s') . '</p>';
        $html .= '<p><strong>Google API:</strong> ' . ($googleAvailable ? '✅ Available' : '❌ Not Available') . '</p>';
        
        $html .= '<div class="' . ($failed > 0 ? 'summary-error' : 'summary') . '">';
        $html .= '<strong>📊 Summary:</strong><br>';
        $html .= "Total Jobs: {$total}<br>";
        $html .= "✅ Fully Submitted (Both Google & Bing): {$successful}<br>";
        $html .= "❌ Failed/Partial: {$failed}<br>";
        $html .= '</div>';
        
        if ($failed > 0) {
            $html .= '<h3>❌ Failed/Partial Jobs</h3>';
            $html .= '<table><thead><tr><th>Job</th><th>Google</th><th>Bing</th><th>Error Details</th></tr></thead><tbody>';
            foreach ($results as $r) {
                if (!$r['fully_submitted']) {
                    $html .= '<tr>';
                    $html .= '<td><strong>' . htmlspecialchars(substr($r['title'], 0, 50)) . '</strong><br><small>' . htmlspecialchars($r['url']) . '</small></td>';
                    $html .= '<td class="' . ($r['google']['success'] ? 'success' : 'error') . '">' . ($r['google']['success'] ? '✅ Success' : '❌ Failed') . '<pre>' . htmlspecialchars(substr($r['google']['error'] ?? '', 0, 200)) . '</pre></td>';
                    $html .= '<td class="' . ($r['bing']['success'] ? 'success' : 'error') . '">' . ($r['bing']['success'] ? '✅ Success' : '❌ Failed') . '<pre>' . htmlspecialchars(substr($r['bing']['error'] ?? '', 0, 200)) . '</pre></td>';
                    $html .= '<td>' . ($r['google']['error'] && $r['bing']['error'] ? 'Both APIs failed' : ($r['google']['error'] ? 'Google failed' : 'Bing failed')) . '</td>';
                    $html .= '</tr>';
                }
            }
            $html .= '</tbody></table>';
        }
        
        $html .= '<p style="margin-top:30px;font-size:12px;color:#666;text-align:center">Stardena Works - Automated Indexing Report</p>';
        $html .= '</div></body></html>';
        
        foreach ($adminEmails as $email) {
            try {
                Mail::html($html, fn($m) => $m->to($email)->subject($subject)->from(env('MAIL_FROM_ADDRESS'), 'Stardena Works SEO'));
            } catch (\Exception $e) {
                Log::error("Failed to send manual ping report to {$email}: " . $e->getMessage());
            }
        }
    }

    private function deriveStatus(bool $google, bool $bing): string
    {
        if ($google && $bing) return 'submitted_all';
        if ($google) return 'submitted_google';
        if ($bing) return 'submitted_bing';
        return 'failed';
    }


    public function forcePing(): void
    {
        Cache::forget('sitemap_last_pinged');
        JobPost::where('is_active', true)
            ->where('deadline', '>=', now())
            ->whereNull('indexing_submitted_at')
            ->update(['indexing_submitted_at' => null]);
        $this->pingIfNewJobs();
    }

    // ── Email report ──────────────────────────────────────────────────────────
    private function sendReport(Collection $jobs, array $results, array $yandex, bool $googleAvailable): void
    {
        $adminEmails = array_filter(
            array_map('trim', explode(',', env('ADMIN_EMAILS', '')))
        );
        if (empty($adminEmails)) return;

        $successCount = 0;
        foreach ($results as $r) {
            $googleOk = isset($r['google']['success']) && $r['google']['success'] === true;
            $bingOk = isset($r['bing']['success']) && $r['bing']['success'] === true;
            if ($googleOk || $bingOk) $successCount++;
        }
        
        $failCount = count($results) - $successCount;
        $icon = $failCount === 0 ? '✅' : ($successCount > 0 ? '⚠️' : '❌');
        $subject = "{$icon} SEO Indexing — {$jobs->count()} jobs — {$successCount} submitted — " . now()->format('d M Y H:i');

        $webUrl = rtrim(config('api.web_app.url'), '/');

        $html = '<!DOCTYPE html><html><head><meta charset="UTF-8"><style>';
        $html .= 'body{font-family:-apple-system,BlinkMacSystemFont,"Segoe UI",Roboto,sans-serif;line-height:1.6;color:#1f2937;max-width:650px;margin:0 auto;padding:0;background:#f3f4f6}';
        $html .= '.header{background:linear-gradient(135deg,#4f46e5,#7c3aed);color:#fff;padding:32px;text-align:center}';
        $html .= '.header h1{margin:0;font-size:22px;font-weight:700}';
        $html .= '.header p{margin:8px 0 0;opacity:.85;font-size:14px}';
        $html .= '.body{background:#fff;padding:28px;border-radius:0 0 12px 12px}';
        $html .= '.stat-row{display:flex;gap:12px;margin:20px 0}';
        $html .= '.stat{flex:1;background:#f9fafb;border:1px solid #e5e7eb;border-radius:10px;padding:16px;text-align:center}';
        $html .= '.stat .n{font-size:28px;font-weight:800;color:#4f46e5}';
        $html .= '.stat .l{font-size:11px;text-transform:uppercase;color:#6b7280;margin-top:4px;letter-spacing:.05em}';
        $html .= '.ok{color:#10b981;font-weight:600} .fail{color:#ef4444;font-weight:600} .skip{color:#9ca3af}';
        $html .= 'table{width:100%;border-collapse:collapse;font-size:13px}';
        $html .= 'th{background:#f9fafb;padding:10px 12px;text-align:left;font-size:11px;text-transform:uppercase;letter-spacing:.05em;color:#6b7280;border-bottom:2px solid #e5e7eb}';
        $html .= 'td{padding:10px 12px;border-bottom:1px solid #f3f4f6;vertical-align:top}';
        $html .= '.tag{display:inline-block;padding:2px 8px;border-radius:4px;font-size:11px;font-weight:600}';
        $html .= '.tag-ok{background:#d1fae5;color:#065f46} .tag-fail{background:#fee2e2;color:#991b1b} .tag-skip{background:#f3f4f6;color:#6b7280}';
        $html .= '.note{background:#eff6ff;border-left:4px solid #3b82f6;padding:14px 16px;border-radius:4px;font-size:13px;margin:20px 0}';
        $html .= '.btn{display:inline-block;background:#4f46e5;color:#fff;padding:10px 22px;text-decoration:none;border-radius:8px;font-weight:600;font-size:13px;margin:4px}';
        $html .= '.btn-outline{background:#fff;color:#4f46e5;border:2px solid #4f46e5}';
        $html .= '.footer{text-align:center;padding:20px;font-size:12px;color:#9ca3af}';
        $html .= '</style></head><body>';

        $html .= '<div class="header"><h1>🔍 SEO Indexing Report</h1>';
        $html .= '<p>' . now()->format('l, F j, Y g:i A T') . '</p></div>';
        $html .= '<div class="body">';

        // Stats
        $googleOk = 0;
        $bingOk = 0;
        foreach ($results as $r) {
            if (isset($r['google']['success']) && $r['google']['success']) $googleOk++;
            if (isset($r['bing']['success']) && $r['bing']['success']) $bingOk++;
        }

        $html .= '<div class="stat-row">';
        $html .= '<div class="stat"><div class="n">' . $jobs->count() . '</div><div class="l">Jobs found</div></div>';
        $html .= '<div class="stat"><div class="n" style="color:' . ($googleOk > 0 ? '#10b981' : '#ef4444') . '">' . $googleOk . '</div><div class="l">Google submitted</div></div>';
        $html .= '<div class="stat"><div class="n" style="color:' . ($bingOk > 0 ? '#10b981' : '#ef4444') . '">' . $bingOk . '</div><div class="l">Bing submitted</div></div>';
        $html .= '<div class="stat"><div class="n" style="color:' . ($yandex['success'] ? '#10b981' : '#ef4444') . '">' . ($yandex['success'] ? '✅' : '❌') . '</div><div class="l">Yandex pinged</div></div>';
        $html .= '</div>';

        // Google API status notice
        if (!$googleAvailable) {
            $html .= '<div class="note">⚠️ <strong>Google Indexing API not configured.</strong> Upload your service account JSON to <code>storage/app/google-service-account.json</code> to enable direct Google indexing. Jobs were submitted to Bing only.</div>';
        }

        // Job results table
        $html .= '<h3 style="margin-top:0">📋 Job Results</h3>';
        $html .= '<table><thead><tr><th>Job</th><th>Google</th><th>Bing</th><th>Status</th></tr></thead><tbody>';

        foreach ($results as $r) {
            $jobTitle = $jobs->firstWhere('id', $r['job_id'])?->job_title ?? 'Unknown';
            
            // FIXED: Safely get error message, converting arrays to strings
            $googleError = '';
            if (isset($r['google']['response']['error'])) {
                $googleError = is_array($r['google']['response']['error']) 
                    ? json_encode($r['google']['response']['error']) 
                    : $r['google']['response']['error'];
            }
            
            $bingError = '';
            if (isset($r['bing']['response']['error'])) {
                $bingError = is_array($r['bing']['response']['error']) 
                    ? json_encode($r['bing']['response']['error']) 
                    : $r['bing']['response']['error'];
            }
            
            $googleStatus = isset($r['google']) 
                ? ($r['google']['success'] 
                    ? '<span class="tag tag-ok">✅ ' . ($r['google']['status'] ?? 'ok') . '</span>'
                    : '<span class="tag tag-fail">❌ ' . ($googleError ?: ($r['google']['status'] ?? 'failed')) . '</span>')
                : '<span class="tag tag-skip">⏭ Skipped</span>';
            
            $bingStatus = isset($r['bing']) 
                ? ($r['bing']['success']
                    ? '<span class="tag tag-ok">✅ ' . ($r['bing']['status'] ?? 'ok') . '</span>'
                    : '<span class="tag tag-fail">❌ ' . ($bingError ?: ($r['bing']['status'] ?? 'failed')) . '</span>')
                : '<span class="tag tag-skip">⏭ Skipped</span>';
            
            $overall = (isset($r['google']['success']) && $r['google']['success']) || (isset($r['bing']['success']) && $r['bing']['success'])
                ? '<span class="tag tag-ok">✅ Submitted</span>'
                : '<span class="tag tag-fail">❌ Failed</span>';

            $html .= '<tr>';
            $html .= '<td><strong>' . htmlspecialchars(substr($jobTitle, 0, 50)) . '</strong><br>';
            $html .= '<a href="' . htmlspecialchars($r['url']) . '" style="color:#4f46e5;font-size:12px">View Job →</a></td>';
            $html .= '<td>' . $googleStatus . '</td>';
            $html .= '<td>' . $bingStatus . '</td>';
            $html .= '<td>' . $overall . '</td>';
            $html .= '</tr>';
        }
        $html .= '</tbody></table>';

        // Best practices note
        $html .= '<div class="note">
            <strong>📌 Best Practice Reminders:</strong><br>
            • Google Indexing API: max 200 URLs/day — we submit each job once only<br>
            • Bing Webmaster API: submit only changed/new URLs, not all URLs each run<br>
            • Yandex sitemap ping: valid and works — HTTP 200 means sitemap accepted<br>
            • Google/Bing HTTP ping endpoints were <strong>deprecated in 2023</strong> — never use them again
        </div>';

        // Action buttons
        $html .= '<div style="text-align:center;margin:24px 0">';
        $html .= '<a href="' . $webUrl . '/sitemap.xml" class="btn">View Sitemap</a>';
        $html .= '<a href="https://search.google.com/search-console" class="btn btn-outline">Search Console</a>';
        $html .= '</div>';

        $html .= '<div class="footer">Stardena Works • Automated SEO Report • ' . now()->format('Y') . '</div>';
        $html .= '</div></body></html>';

        foreach ($adminEmails as $email) {
            try {
                Mail::html($html, fn($m) => $m
                    ->to($email)
                    ->subject($subject)
                    ->from(env('MAIL_FROM_ADDRESS', 'noreply@stardenaworks.com'), 'Stardena Works SEO')
                );
            } catch (\Exception $e) {
                Log::error("Report email failed for {$email}: " . $e->getMessage());
            }
        }
    }

}