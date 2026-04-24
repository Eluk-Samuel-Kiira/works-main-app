<?php

namespace App\Services\Blog;

use App\Models\Blog;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

/**
 * BlogSitemapPingService
 * ─────────────────────────────────────────────────────────────────
 * PURPOSE : Notify search engines via IndexNow about new/updated
 *           blog/article URLs.
 *
 * Mirrors SitemapPingService (jobs) — same protocol, same key.
 * ─────────────────────────────────────────────────────────────────
 */
class BlogSitemapPingService
{
    private const INDEXNOW_KEY      = 'b433024ea88249dfa1cae5e8cfacacf9';
    private const INDEXNOW_ENDPOINT = 'https://api.indexnow.org/IndexNow';
    private const BATCH_SIZE        = 100;

    private string $webUrl;
    private string $keyLocation;

    public function __construct()
    {
        $this->webUrl      = rtrim(config('api.web_app.url', env('WEB_APP_URL', 'https://stardenaworks.com')), '/');
        $this->keyLocation = $this->webUrl . '/' . self::INDEXNOW_KEY . '.txt';
    }

    // =========================================================================
    // SCHEDULED — runs hourly, picks up unpigged blogs
    // =========================================================================
    public function pingNewBlogs(): array
    {
        $blogs = Blog::where('is_active', true)
            ->where('is_published', true)
            ->whereNotNull('published_at')
            ->whereNull('last_pinged_at')
            ->whereNotNull('slug')
            ->select(['id', 'title', 'slug', 'created_at'])
            ->orderByDesc('published_at')
            ->limit(self::BATCH_SIZE)
            ->get();

        if ($blogs->isEmpty()) {
            Log::info('BLOG PING: No unpigged blogs found.');
            return ['total' => 0, 'success' => 0, 'failed' => 0, 'blogs' => []];
        }

        Log::info("BLOG PING: Found {$blogs->count()} blog(s) — submitting via IndexNow.");

        \Artisan::call('sitemap:generate');

        $result = $this->submitToIndexNow($blogs->pluck('slug')->toArray(), 'blog');

        foreach ($blogs as $blog) {
            Blog::where('id', $blog->id)->update([
                'is_pinged'      => $result['success'],
                'last_pinged_at' => now(),
            ]);
        }

        $report = [
            'total'   => $blogs->count(),
            'success' => $result['success'] ? $blogs->count() : 0,
            'failed'  => $result['success'] ? 0 : $blogs->count(),
            'status'  => $result['status'],
            'blogs'   => $blogs->map(fn($b) => [
                'id'      => $b->id,
                'title'   => $b->title,
                'url'     => $this->webUrl . '/blog/' . $b->slug,
                'success' => $result['success'],
            ])->toArray(),
        ];

        $this->sendPingReport($report);
        return $report;
    }

    // =========================================================================
    // MANUAL — for failed/unpigged blogs
    // =========================================================================
    public function pingFailedBlogs(?array $blogIds = null): array
    {
        $query = Blog::where('is_active', true)
            ->where('is_published', true)
            ->whereNotNull('slug');

        if ($blogIds) {
            $query->whereIn('id', $blogIds);
        } else {
            $query->where(fn($q) =>
                $q->whereNull('last_pinged_at')->orWhere('is_pinged', false));
        }

        $blogs = $query->select(['id', 'title', 'slug', 'is_pinged', 'last_pinged_at'])
                       ->limit(self::BATCH_SIZE)
                       ->get();

        if ($blogs->isEmpty()) {
            return ['total' => 0, 'success' => 0, 'failed' => 0, 'blogs' => []];
        }

        \Artisan::call('sitemap:generate');

        $result = $this->submitToIndexNow($blogs->pluck('slug')->toArray(), 'blog');

        foreach ($blogs as $blog) {
            Blog::where('id', $blog->id)->update([
                'is_pinged'      => $result['success'],
                'last_pinged_at' => now(),
            ]);
        }

        return [
            'total'   => $blogs->count(),
            'success' => $result['success'] ? $blogs->count() : 0,
            'failed'  => $result['success'] ? 0 : $blogs->count(),
            'status'  => $result['status'],
            'message' => $result['message'],
            'blogs'   => $blogs->map(fn($b) => [
                'id'      => $b->id,
                'title'   => $b->title,
                'url'     => $this->webUrl . '/blog/' . $b->slug,
                'success' => $result['success'],
            ])->toArray(),
        ];
    }

    // =========================================================================
    // MANUAL PING FOR SPECIFIC BLOGS
    // =========================================================================
    public function manualPingBlogs(array $blogIds): array
    {
        $blogs = Blog::whereIn('id', $blogIds)
            ->where('is_active', true)
            ->where('is_published', true)
            ->get();

        if ($blogs->isEmpty()) return ['submitted' => 0, 'results' => []];

        \Artisan::call('sitemap:generate');

        $result    = $this->submitToIndexNow($blogs->pluck('slug')->toArray(), 'blog');
        $submitted = 0;
        $results   = [];

        foreach ($blogs as $blog) {
            Blog::where('id', $blog->id)->update([
                'is_pinged'      => $result['success'],
                'last_pinged_at' => now(),
            ]);
            if ($result['success']) $submitted++;
            $results[] = [
                'blog_id' => $blog->id,
                'title'   => $blog->title,
                'url'     => $this->webUrl . '/blog/' . $blog->slug,
                'success' => $result['success'],
            ];
        }

        return [
            'submitted' => $submitted,
            'total'     => $blogs->count(),
            'results'   => $results,
            'status'    => $result['status'],
        ];
    }

    // =========================================================================
    // CORE — IndexNow submission
    // =========================================================================
    private function submitToIndexNow(array $slugs, string $prefix = 'blog'): array
    {
        if (empty($slugs)) return ['success' => false, 'status' => 0, 'message' => 'No slugs'];

        $urls    = array_map(fn($s) => $this->webUrl . '/' . $prefix . '/' . $s, $slugs);
        $host    = parse_url($this->webUrl, PHP_URL_HOST);
        $payload = [
            'host'        => $host,
            'key'         => self::INDEXNOW_KEY,
            'keyLocation' => $this->keyLocation,
            'urlList'     => $urls,
        ];

        try {
            $response = Http::withHeaders(['Content-Type' => 'application/json; charset=utf-8'])
                ->timeout(20)
                ->post(self::INDEXNOW_ENDPOINT, $payload);

            $status  = $response->status();
            $success = in_array($status, [200, 202]);

            $message = match($status) {
                200     => 'URLs submitted successfully',
                202     => 'URLs accepted and queued',
                400     => 'Bad request',
                403     => 'Forbidden — key invalid or file not accessible',
                422     => 'Unprocessable — URL/host mismatch',
                429     => 'Too Many Requests',
                default => "HTTP {$status}",
            };

            Log::info("BLOG PING IndexNow: HTTP {$status} — {$message} — " . count($urls) . " URLs");

            return ['success' => $success, 'status' => $status, 'message' => $message];

        } catch (\Exception $e) {
            Log::error('BLOG PING IndexNow exception: ' . $e->getMessage());
            return ['success' => false, 'status' => 0, 'message' => $e->getMessage()];
        }
    }

    // =========================================================================
    // STATS
    // =========================================================================
    public function getStats(): array
    {
        $base = Blog::where('is_active', true)->where('is_published', true);

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
        $adminEmails = array_filter(array_map('trim', explode(',', env('ADMIN_EMAILS', ''))));
        if (empty($adminEmails)) return;

        $icon    = $report['success'] > 0 ? '✅' : '❌';
        $subject = "{$icon} Blog IndexNow Ping — {$report['total']} articles — " . now()->format('d M Y H:i');

        $html  = '<!DOCTYPE html><html><head><meta charset="UTF-8"><style>';
        $html .= 'body{font-family:-apple-system,sans-serif;max-width:600px;margin:0 auto;background:#f3f4f6;color:#1f2937}';
        $html .= '.hd{background:linear-gradient(135deg,#f59e0b,#ef4444);color:#fff;padding:28px;text-align:center;border-radius:12px 12px 0 0}';
        $html .= '.bd{background:#fff;padding:24px;border-radius:0 0 12px 12px}';
        $html .= 'table{width:100%;border-collapse:collapse;font-size:13px}';
        $html .= 'th{background:#f9fafb;padding:9px 12px;text-align:left;font-size:11px;text-transform:uppercase;color:#6b7280;border-bottom:2px solid #e5e7eb}';
        $html .= 'td{padding:9px 12px;border-bottom:1px solid #f3f4f6}';
        $html .= '.ok{color:#10b981;font-weight:600} .fail{color:#ef4444;font-weight:600}';
        $html .= '.ft{text-align:center;padding:16px;font-size:12px;color:#9ca3af}';
        $html .= '</style></head><body>';

        $html .= '<div class="hd"><h2 style="margin:0">📝 Blog IndexNow Ping Report</h2>';
        $html .= '<p style="margin:6px 0 0;opacity:.85;font-size:13px">' . now()->format('l, F j, Y g:i A') . '</p></div>';
        $html .= '<div class="bd">';
        $html .= '<p><strong>Total:</strong> ' . $report['total'] . ' | <strong class="ok">Success:</strong> ' . $report['success'] . ' | <strong class="fail">Failed:</strong> ' . $report['failed'] . '</p>';

        if (!empty($report['blogs'])) {
            $html .= '<table><thead><tr><th>Article</th><th>Status</th></tr></thead><tbody>';
            foreach ($report['blogs'] as $b) {
                $s = $b['success'] ? '<span class="ok">✅ Pinged</span>' : '<span class="fail">❌ Failed</span>';
                $html .= '<tr><td><strong>' . htmlspecialchars($b['title']) . '</strong><br>';
                $html .= '<a href="' . $b['url'] . '" style="color:#6366f1;font-size:12px">' . $b['url'] . '</a></td>';
                $html .= '<td>' . $s . '</td></tr>';
            }
            $html .= '</tbody></table>';
        }

        $html .= '<div class="ft">Stardena Works — Blog IndexNow ping</div></div></body></html>';

        foreach ($adminEmails as $email) {
            try {
                Mail::html($html, fn($m) => $m
                    ->to($email)->subject($subject)
                    ->from(env('MAIL_FROM_ADDRESS', 'noreply@stardenaworks.com'), 'Stardena Works Blog'));
            } catch (\Exception $e) {
                Log::error("Blog ping report email failed: " . $e->getMessage());
            }
        }
    }
}