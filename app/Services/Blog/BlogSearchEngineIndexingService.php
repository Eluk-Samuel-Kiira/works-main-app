<?php

namespace App\Services\Blog;

use App\Models\Blog;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class BlogSearchEngineIndexingService
{
    private const DAILY_LIMIT = 200;
    private const QUOTA_CACHE_KEY = 'google_blog_indexing_today_';
    private const TOKEN_CACHE_KEY = 'google_blog_indexing_access_token';
    private const API_ENDPOINT = 'https://indexing.googleapis.com/v3/urlNotifications:publish';

    private string $webUrl;

    public function __construct()
    {
        $this->webUrl = rtrim(config('api.web_app.url', env('WEB_APP_URL', 'https://stardenaworks.com')), '/');
    }

    public function manualIndexBlogs(array $blogIds): array
    {
        if (empty($blogIds)) {
            return ['submitted' => 0, 'message' => 'No blog IDs provided', 'results' => []];
        }

        $remaining = $this->getRemainingQuota();
        if ($remaining <= 0) {
            return [
                'success'   => false,
                'submitted' => 0,
                'message'   => 'Daily Google quota of ' . self::DAILY_LIMIT . ' URLs reached. Resets at midnight UTC.',
                'quota_used' => $this->getQuotaUsed(),
                'results'   => [],
            ];
        }

        $blogIds = array_slice($blogIds, 0, $remaining);
        $blogs = Blog::whereIn('id', $blogIds)
            ->where('is_active', true)
            ->where('is_published', true)
            ->get();

        if ($blogs->isEmpty()) {
            return ['submitted' => 0, 'message' => 'No active published blogs found', 'results' => []];
        }

        $token = $this->getAccessToken();
        if (!$token) {
            return [
                'success'   => false,
                'submitted' => 0,
                'message'   => 'Google Indexing API not configured. Upload service account JSON to storage/app/google-service-account.json',
                'results'   => [],
            ];
        }

        $results = [];
        $submitted = 0;

        foreach ($blogs as $blog) {
            $url = $this->webUrl . '/blog/' . $blog->slug;
            $result = $this->callGoogleApi($url, $token);

            $this->updateBlogIndexingStatus($blog, $result);

            $results[] = [
                'blog_id' => $blog->id,
                'title'   => $blog->title,
                'slug'    => $blog->slug,
                'url'     => $url,
                'success' => $result['success'],
                'message' => $result['message'],
            ];

            if ($result['success']) {
                $submitted++;
                $this->incrementQuota();
            }

            usleep(200000);
        }

        return [
            'success'    => $submitted > 0,
            'submitted'  => $submitted,
            'total'      => $blogs->count(),
            'message'    => $submitted > 0 ? "{$submitted} blogs submitted to Google" : "No blogs submitted",
            'results'    => $results,
            'quota_used' => $this->getQuotaUsed(),
        ];
    }

    public function pingIfNewBlogs(): array
    {
        $blogs = Blog::where('is_active', true)
            ->where('is_published', true)
            ->where('submitted_to_indexing', false)
            ->limit(10)
            ->get();

        if ($blogs->isEmpty()) {
            return ['submitted' => 0, 'message' => 'No new blogs to index'];
        }

        return $this->manualIndexBlogs($blogs->pluck('id')->toArray());
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

            $status = $response->status();
            $success = $response->successful();

            $message = match($status) {
                200, 202 => 'URL submitted to Google index queue',
                400 => 'Bad request — invalid format',
                401 => 'Unauthorized — check service account permissions',
                403 => 'Forbidden — add service account as Owner in Search Console',
                429 => 'Quota exceeded — too many requests',
                default => "HTTP {$status}: " . ($response->json('error.message') ?? 'Unknown'),
            };

            Log::info("BLOG GOOGLE INDEXING: HTTP {$status} — {$url}");

            return [
                'success' => $success && ($status === 200 || $status === 202),
                'status'  => $status,
                'message' => $message,
            ];

        } catch (\Exception $e) {
            Log::error("BLOG GOOGLE INDEXING exception: " . $e->getMessage());
            return [
                'success' => false,
                'status'  => 0,
                'message' => $e->getMessage(),
            ];
        }
    }

    private function updateBlogIndexingStatus(Blog $blog, array $result): void
    {
        $status = $result['success'] ? 'submitted' : 'failed';
        
        if ($result['status'] === 403) $status = 'forbidden';
        if ($result['status'] === 429) $status = 'quota_exceeded';
        if ($result['status'] === 401) $status = 'unauthorized';

        Blog::where('id', $blog->id)->update([
            'submitted_to_indexing' => $result['success'],
            'indexing_submitted_at' => $result['success'] ? now() : $blog->indexing_submitted_at,
            'indexing_status'       => $status,
            'indexing_response'     => json_encode([
                'submitted_at' => now()->toISOString(),
                'status'       => $result['status'],
                'message'      => $result['message'],
            ]),
        ]);
    }

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
            Log::warning('BLOG GOOGLE INDEXING: Service account file not found');
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
                Log::error('BLOG GOOGLE INDEXING: OAuth failed');
                return null;
            }

            $token = $response->json('access_token');
            $expiresIn = $response->json('expires_in', 3500);
            Cache::put(self::TOKEN_CACHE_KEY, $token, now()->addSeconds($expiresIn - 60));

            return $token;

        } catch (\Exception $e) {
            Log::error('BLOG GOOGLE INDEXING: JWT auth failed - ' . $e->getMessage());
            return null;
        }
    }

    private function base64UrlEncode(string $data): string
    {
        return rtrim(strtr(base64_encode($data), '+/', '-_'), '=');
    }

    private function getQuotaUsed(): int
    {
        $today = now()->toDateString();
        return (int) Cache::get(self::QUOTA_CACHE_KEY . $today, 0);
    }

    private function getRemainingQuota(): int
    {
        return max(0, self::DAILY_LIMIT - $this->getQuotaUsed());
    }

    private function incrementQuota(): void
    {
        $today = now()->toDateString();
        $secondsUntilMidnight = strtotime('tomorrow midnight UTC') - time();
        
        Cache::increment(self::QUOTA_CACHE_KEY . $today);
        Cache::put(self::QUOTA_CACHE_KEY . $today, $this->getQuotaUsed(), $secondsUntilMidnight);
    }
}