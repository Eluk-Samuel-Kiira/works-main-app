<?php

namespace App\Http\Controllers\Analytics;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Models\Job\JobPost;
use App\Models\Blog;
use Carbon\Carbon;

class SeoAnalyticsController extends Controller
{
    public function index(Request $request)
    {
        [$from, $to] = $this->getDateRange($request);
        $currentRange = $request->get('range', '30d');

        // ── Job SEO KPIs ──────────────────────────────────────────────────────
        $kpis = [
            'avg_seo_score'     => round((float) JobPost::whereNotNull('seo_score')->avg('seo_score'), 1),
            'avg_quality_score' => round((float) JobPost::whereNotNull('content_quality_score')->avg('content_quality_score'), 1),
            'indexed'           => JobPost::where('is_indexed', true)->count(),
            'not_indexed'       => JobPost::where('is_indexed', false)->count(),
            'pinged'            => JobPost::where('is_pinged', true)->count(),
            'submitted'         => JobPost::where('submitted_to_indexing', true)->count(),
            'high_seo'          => JobPost::where('seo_score', '>=', 80)->count(),
            'low_seo'           => JobPost::where('seo_score', '<', 50)->count(),
            'total_impressions' => (int) JobPost::sum('search_impressions'),
            'total_clicks'      => (int) JobPost::sum('search_clicks'),
            'avg_ctr'           => round((float) JobPost::whereNotNull('click_through_rate')->avg('click_through_rate'), 2),
        ];

        // ── Indexing status breakdown ────────────────────────────────────────
        $indexingStatus = [
            'Indexed'       => $kpis['indexed'],
            'Not Indexed'   => $kpis['not_indexed'],
            'Submitted'     => JobPost::where('submitted_to_indexing', true)->where('is_indexed', false)->count(),
            'Pending'       => JobPost::whereNull('indexing_status')->count(),
        ];

        // ── SEO score distribution ────────────────────────────────────────────
        $seoDistribution = [
            '0–20'   => JobPost::whereBetween('seo_score', [0, 20])->count(),
            '21–40'  => JobPost::whereBetween('seo_score', [21, 40])->count(),
            '41–60'  => JobPost::whereBetween('seo_score', [41, 60])->count(),
            '61–80'  => JobPost::whereBetween('seo_score', [61, 80])->count(),
            '81–100' => JobPost::whereBetween('seo_score', [81, 100])->count(),
        ];

        // ── Content quality distribution ──────────────────────────────────────
        $qualityDistribution = [
            '0–20'   => JobPost::whereBetween('content_quality_score', [0, 20])->count(),
            '21–40'  => JobPost::whereBetween('content_quality_score', [21, 40])->count(),
            '41–60'  => JobPost::whereBetween('content_quality_score', [41, 60])->count(),
            '61–80'  => JobPost::whereBetween('content_quality_score', [61, 80])->count(),
            '81–100' => JobPost::whereBetween('content_quality_score', [81, 100])->count(),
        ];

        // ── Jobs needing SEO attention ────────────────────────────────────────
        $lowSeoJobs = JobPost::with('company')
            ->where('seo_score', '<', 50)
            ->where('is_active', true)
            ->orderBy('seo_score')
            ->limit(20)
            ->get(['id', 'job_title', 'company_id', 'seo_score', 'content_quality_score',
                'is_indexed', 'is_pinged', 'created_at']);

        // ── Indexed over time (last 30 days) ──────────────────────────────────
        $indexedTrendRaw = JobPost::where('last_indexed_at', '>=', now()->subDays(29))
            ->selectRaw('DATE(last_indexed_at) as date, COUNT(*) as cnt')
            ->groupBy('date')
            ->pluck('cnt', 'date')
            ->toArray();

        $indexedDates  = [];
        $indexedValues = [];
        for ($i = 29; $i >= 0; $i--) {
            $d = now()->subDays($i)->format('Y-m-d');
            $indexedDates[]  = now()->subDays($i)->format('M d');
            $indexedValues[] = $indexedTrendRaw[$d] ?? 0;
        }

        // ── Search impressions & clicks trend ────────────────────────────────
        // (aggregated since we don't have per-day tracking — use recent jobs by created_at)
        $impressionsTrend = $this->applyRange(
            JobPost::where('search_impressions', '>', 0)
                ->selectRaw('DATE(created_at) as date, SUM(search_impressions) as impressions, SUM(search_clicks) as clicks')
                ->groupBy('date')->orderBy('date'),
            $from, $to
        )->pluck('impressions', 'date')->toArray();

        $clicksTrend = $this->applyRange(
            JobPost::selectRaw('DATE(created_at) as date, SUM(search_clicks) as clicks')
                ->groupBy('date')->orderBy('date'),
            $from, $to
        )->pluck('clicks', 'date')->toArray();

        [$impDates, $impValues] = $this->fillDateSeries($from ?? now()->subDays(30), $to, $impressionsTrend);
        [, $clickValues]        = $this->fillDateSeries($from ?? now()->subDays(30), $to, $clicksTrend);

        // ── Top SEO performers ────────────────────────────────────────────────
        $topSeoJobs = JobPost::with('company')
            ->orderByDesc('seo_score')
            ->limit(10)
            ->get(['id', 'job_title', 'company_id', 'seo_score', 'content_quality_score',
                'search_impressions', 'search_clicks', 'click_through_rate', 'is_indexed']);

        // ── Blog SEO KPIs ─────────────────────────────────────────────────────
        $blogKpis = [
            'total'         => Blog::count(),
            'published'     => Blog::where('is_published', true)->count(),
            'indexed'       => Blog::where('is_indexed', true)->count(),
            'avg_seo'       => round((float) Blog::whereNotNull('seo_score')->avg('seo_score'), 1),
            'avg_quality'   => round((float) Blog::whereNotNull('content_quality_score')->avg('content_quality_score'), 1),
            'total_views'   => (int) Blog::sum('view_count'),
            'total_shares'  => (int) Blog::sum('share_count'),
        ];

        // ── Top blog posts ────────────────────────────────────────────────────
        $topBlogs = Blog::where('is_published', true)
            ->orderByDesc('view_count')
            ->limit(10)
            ->get(['id', 'title', 'view_count', 'share_count', 'like_count',
                'seo_score', 'content_quality_score', 'published_at', 'is_indexed']);

        // ── Blog SEO score distribution ───────────────────────────────────────
        $blogSeoDistribution = [
            '0–20'   => Blog::whereBetween('seo_score', [0, 20])->count(),
            '21–40'  => Blog::whereBetween('seo_score', [21, 40])->count(),
            '41–60'  => Blog::whereBetween('seo_score', [41, 60])->count(),
            '61–80'  => Blog::whereBetween('seo_score', [61, 80])->count(),
            '81–100' => Blog::whereBetween('seo_score', [81, 100])->count(),
        ];

        // ── Indexing status by job type ───────────────────────────────────────
        $indexingByStatus = DB::table('job_posts')
            ->whereNull('deleted_at')
            ->selectRaw("indexing_status, COUNT(*) as cnt")
            ->groupBy('indexing_status')
            ->pluck('cnt', 'indexing_status')
            ->toArray();

        return view('analytics.seo', compact(
            'kpis', 'currentRange',
            'indexingStatus', 'seoDistribution', 'qualityDistribution',
            'lowSeoJobs', 'indexedDates', 'indexedValues',
            'impDates', 'impValues', 'clickValues',
            'topSeoJobs', 'blogKpis', 'topBlogs',
            'blogSeoDistribution', 'indexingByStatus'
        ));
    }

    public function data(Request $request)
    {
        return response()->json(['success' => true]);
    }

    public function export(Request $request)
    {
        [$from, $to] = $this->getDateRange($request);
        $jobs = $this->applyRange(
            JobPost::with('company')
                ->select(['id', 'job_title', 'company_id', 'seo_score', 'content_quality_score',
                    'is_indexed', 'is_pinged', 'submitted_to_indexing', 'indexing_status',
                    'search_impressions', 'search_clicks', 'click_through_rate', 'created_at']),
            $from, $to
        )->get();

        $filename = 'seo-analytics-' . now()->format('Y-m-d') . '.csv';
        $headers  = ['Content-Type' => 'text/csv', 'Content-Disposition' => "attachment; filename=\"{$filename}\""];

        $callback = function () use ($jobs) {
            $handle = fopen('php://output', 'w');
            fputcsv($handle, ['ID', 'Title', 'Company', 'SEO Score', 'Quality Score',
                'Indexed', 'Pinged', 'Submitted', 'Indexing Status',
                'Impressions', 'Clicks', 'CTR', 'Created']);
            foreach ($jobs as $j) {
                fputcsv($handle, [
                    $j->id, $j->job_title, $j->company?->name ?? '',
                    $j->seo_score, $j->content_quality_score,
                    $j->is_indexed ? 'Yes' : 'No',
                    $j->is_pinged  ? 'Yes' : 'No',
                    $j->submitted_to_indexing ? 'Yes' : 'No',
                    $j->indexing_status ?? '',
                    $j->search_impressions, $j->search_clicks, $j->click_through_rate,
                    $j->created_at->format('Y-m-d'),
                ]);
            }
            fclose($handle);
        };

        return response()->stream($callback, 200, $headers);
    }

    private function getDateRange(Request $request): array
    {
        $range = $request->get('range', '30d');
        $now   = Carbon::now();
        return match ($range) {
            '24h'    => [$now->copy()->subDay(),    $now],
            '7d'     => [$now->copy()->subDays(7),  $now],
            '30d'    => [$now->copy()->subDays(30), $now],
            'all'    => [null,                       $now],
            'custom' => [
                $request->filled('from') ? Carbon::parse($request->get('from'))->startOfDay() : $now->copy()->subDays(30),
                $request->filled('to')   ? Carbon::parse($request->get('to'))->endOfDay()     : $now,
            ],
            default  => [$now->copy()->subDays(30), $now],
        };
    }

    private function applyRange($query, ?Carbon $from, Carbon $to, string $col = 'created_at')
    {
        return $from
            ? $query->whereBetween($col, [$from, $to])
            : $query->where($col, '<=', $to);
    }

    private function fillDateSeries(Carbon $from, Carbon $to, array $data): array
    {
        $dates = $values = [];
        $days  = (int) $from->diffInDays($to);
        for ($i = $days; $i >= 0; $i--) {
            $d        = $to->copy()->subDays($i);
            $key      = $d->format('Y-m-d');
            $dates[]  = $d->format('M d');
            $values[] = isset($data[$key]) ? (int)$data[$key] : 0;
        }
        return [$dates, $values];
    }
}
