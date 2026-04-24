<?php

namespace App\Http\Controllers\Api\Blog;

use App\Http\Controllers\Controller;
use App\Models\Blog;
use App\Services\Blog\BlogSitemapPingService;
use App\Services\Blog\BlogSearchEngineIndexingService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

class BlogSeoController extends Controller
{
    public function __construct(
        private BlogSitemapPingService         $pingService,
        private BlogSearchEngineIndexingService $indexService,
    ) {}

    // ── GET /api/v1/blog-seo/ping-stats ──────────────────────────────────────
    public function pingStats(): JsonResponse
    {
        return response()->json(['data' => $this->pingService->getStats()]);
    }

    // ── GET /api/v1/blog-seo/indexing-stats ──────────────────────────────────
    public function indexingStats(): JsonResponse
    {
        $today  = now()->toDateString();
        $used   = Cache::get("google_blog_indexing_today_{$today}", 0);
        $base   = Blog::where('is_active', true)->where('is_published', true);

        return response()->json(['data' => [
            'not_submitted'   => (clone $base)->where('submitted_to_indexing', false)->count(),
            'submitted'       => (clone $base)->where('submitted_to_indexing', true)->count(),
            'indexed'         => (clone $base)->where('is_indexed', true)->count(),
            'quota_used'      => $used,
            'quota_remaining' => max(0, 200 - $used),
            'api_configured'  => file_exists(storage_path('app/google-service-account.json')),
        ]]);
    }

    // ── POST /api/v1/blog-seo/ping-blog/{slug} ───────────────────────────────
    public function pingBlog(string $slug): JsonResponse
    {
        $blog   = Blog::where('slug', $slug)->firstOrFail();
        $result = $this->pingService->manualPingBlogs([$blog->id]);
        $ok     = ($result['submitted'] ?? 0) > 0;

        return response()->json([
            'success' => $ok,
            'message' => $ok ? 'Blog pinged via IndexNow.' : 'Ping failed.',
            'data'    => $result,
        ]);
    }

    // ── POST /api/v1/blog-seo/index-blog/{slug} ──────────────────────────────
    public function indexBlog(string $slug): JsonResponse
    {
        $blog   = Blog::where('slug', $slug)->firstOrFail();
        $result = $this->indexService->manualIndexBlogs([$blog->id]);

        $today     = now()->toDateString();
        $remaining = max(0, 200 - Cache::get("google_blog_indexing_today_{$today}", 0));

        return response()->json([
            'success'    => ($result['submitted'] ?? 0) > 0,
            'message'    => $result['message'] ?? '',
            'quota_left' => $remaining,
            'data'       => $result,
        ]);
    }

    // ── POST /api/v1/blog-seo/bulk-ping ──────────────────────────────────────
    public function bulkPing(Request $request): JsonResponse
    {
        $mode = $request->input('mode', 'failed'); // failed | all

        $result = match($mode) {
            'all'   => $this->pingService->pingNewBlogs(),
            default => $this->pingService->pingFailedBlogs(),
        };

        return response()->json(['data' => $result]);
    }

    // ── POST /api/v1/blog-seo/bulk-index ─────────────────────────────────────
    public function bulkIndex(Request $request): JsonResponse
    {
        $result = $this->indexService->pingIfNewBlogs();
        return response()->json(['data' => $result]);
    }
}