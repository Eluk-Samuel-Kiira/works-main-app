<?php

namespace App\Http\Controllers\Analytics;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Models\Job\{JobPost, JobCategory, JobLocation, Industry, JobType, ExperienceLevel, EducationLevel};
use Carbon\Carbon;

class JobAnalyticsController extends Controller
{
    public function index(Request $request)
    {
        [$from, $to] = $this->getDateRange($request);
        $currentRange = $request->get('range', '30d');

        // ── KPIs ─────────────────────────────────────────────────────────────
        $kpis = [
            'total'        => JobPost::withTrashed()->count(),
            'active'       => JobPost::where('is_active', true)->where('deadline', '>=', now())->count(),
            'expired'      => JobPost::where('deadline', '<', now())->count(),
            'featured'     => JobPost::where('is_featured', true)->count(),
            'urgent'       => JobPost::where('is_urgent', true)->count(),
            'verified'     => JobPost::where('is_verified', true)->count(),
            'in_period'    => $this->applyRange(JobPost::query(), $from, $to)->count(),
            'total_views'  => (int) JobPost::sum('view_count'),
            'total_apps'   => (int) JobPost::sum('application_count'),
            'avg_seo'      => round((float) JobPost::whereNotNull('seo_score')->avg('seo_score'), 1),
        ];

        // ── Jobs posted over time ────────────────────────────────────────────
        $trendRaw = $this->applyRange(
            JobPost::selectRaw('DATE(created_at) as date, COUNT(*) as cnt')->groupBy('date')->orderBy('date'),
            $from, $to
        )->pluck('cnt', 'date')->toArray();

        [$trendDates, $trendValues] = $this->fillDateSeries($from ?? now()->subDays(30), $to, $trendRaw);

        // ── Jobs by status ────────────────────────────────────────────────────
        $byStatus = [
            'Active'    => $kpis['active'],
            'Expired'   => $kpis['expired'],
            'Inactive'  => JobPost::where('is_active', false)->count(),
            'Featured'  => $kpis['featured'],
            'Urgent'    => $kpis['urgent'],
        ];

        // ── Jobs by category (top 10) ────────────────────────────────────────
        $byCategory = JobCategory::withCount(['jobPosts' => fn($q) => $this->applyRange($q, $from, $to)])
            ->orderByDesc('job_posts_count')
            ->limit(10)
            ->get(['name', 'job_posts_count'])
            ->map(fn($r) => ['name' => $r->name, 'count' => $r->job_posts_count]);

        // ── Jobs by location (top 10) ─────────────────────────────────────────
        $byLocation = JobLocation::withCount(['jobPosts' => fn($q) => $this->applyRange($q, $from, $to)])
            ->orderByDesc('job_posts_count')
            ->limit(10)
            ->get(['district', 'job_posts_count'])
            ->map(fn($r) => ['name' => $r->district ?? 'Unknown', 'count' => $r->job_posts_count]);

        // ── Jobs by industry (top 10) ─────────────────────────────────────────
        $byIndustry = Industry::withCount(['jobPosts' => fn($q) => $this->applyRange($q, $from, $to)])
            ->orderByDesc('job_posts_count')
            ->limit(10)
            ->get(['name', 'job_posts_count'])
            ->map(fn($r) => ['name' => $r->name, 'count' => $r->job_posts_count]);

        // ── Jobs by type ──────────────────────────────────────────────────────
        $byType = JobType::withCount(['jobPosts' => fn($q) => $this->applyRange($q, $from, $to)])
            ->orderByDesc('job_posts_count')
            ->get(['name', 'job_posts_count'])
            ->map(fn($r) => ['name' => $r->name, 'count' => $r->job_posts_count]);

        // ── Jobs by location type ─────────────────────────────────────────────
        $byLocationType = JobPost::selectRaw('location_type, COUNT(*) as cnt')
            ->groupBy('location_type')
            ->pluck('cnt', 'location_type')
            ->toArray();

        // ── Jobs by employment type ───────────────────────────────────────────
        $byEmploymentType = JobPost::selectRaw('employment_type, COUNT(*) as cnt')
            ->groupBy('employment_type')
            ->pluck('cnt', 'employment_type')
            ->toArray();

        // ── Jobs by experience level ─────────────────────────────────────────
        $byExperience = ExperienceLevel::withCount('jobPosts')
            ->orderBy('sort_order')
            ->get(['name', 'job_posts_count'])
            ->map(fn($r) => ['name' => $r->name, 'count' => $r->job_posts_count]);

        // ── Jobs by education level ───────────────────────────────────────────
        $byEducation = EducationLevel::withCount('jobPosts')
            ->orderBy('sort_order')
            ->get(['name', 'job_posts_count'])
            ->map(fn($r) => ['name' => $r->name, 'count' => $r->job_posts_count]);

        // ── Top performing jobs ───────────────────────────────────────────────
        $topByViews = JobPost::with('company')
            ->orderByDesc('view_count')
            ->limit(10)
            ->get(['id', 'job_title', 'company_id', 'view_count', 'click_count', 'application_count', 'is_active', 'deadline', 'seo_score']);

        // ── Featured vs non-featured comparison ──────────────────────────────
        $featuredAvg = JobPost::where('is_featured', true)
            ->selectRaw('AVG(view_count) as avg_views, AVG(click_count) as avg_clicks, AVG(application_count) as avg_apps')
            ->first();
        $nonFeaturedAvg = JobPost::where('is_featured', false)
            ->selectRaw('AVG(view_count) as avg_views, AVG(click_count) as avg_clicks, AVG(application_count) as avg_apps')
            ->first();

        $featuredComparison = [
            'categories' => ['Avg Views', 'Avg Clicks', 'Avg Applications'],
            'featured'   => [
                round((float)($featuredAvg->avg_views ?? 0), 1),
                round((float)($featuredAvg->avg_clicks ?? 0), 1),
                round((float)($featuredAvg->avg_apps ?? 0), 1),
            ],
            'standard'   => [
                round((float)($nonFeaturedAvg->avg_views ?? 0), 1),
                round((float)($nonFeaturedAvg->avg_clicks ?? 0), 1),
                round((float)($nonFeaturedAvg->avg_apps ?? 0), 1),
            ],
        ];

        // ── SEO score distribution ────────────────────────────────────────────
        $seoDistribution = [
            '0–20'   => JobPost::whereBetween('seo_score', [0, 20])->count(),
            '21–40'  => JobPost::whereBetween('seo_score', [21, 40])->count(),
            '41–60'  => JobPost::whereBetween('seo_score', [41, 60])->count(),
            '61–80'  => JobPost::whereBetween('seo_score', [61, 80])->count(),
            '81–100' => JobPost::whereBetween('seo_score', [81, 100])->count(),
        ];

        // ── Jobs approaching deadline (next 14 days) ─────────────────────────
        $approachingDeadline = JobPost::with('company')
            ->where('is_active', true)
            ->whereBetween('deadline', [now(), now()->addDays(14)])
            ->orderBy('deadline')
            ->limit(20)
            ->get(['id', 'job_title', 'company_id', 'deadline', 'application_count']);

        // ── Verification rate over time ───────────────────────────────────────
        $verifiedCount = JobPost::where('is_verified', true)->count();
        $unverifiedCount = JobPost::where('is_verified', false)->count();

        return view('analytics.jobs', compact(
            'kpis', 'currentRange',
            'trendDates', 'trendValues',
            'byStatus', 'byCategory', 'byLocation', 'byIndustry',
            'byType', 'byLocationType', 'byEmploymentType',
            'byExperience', 'byEducation',
            'topByViews', 'featuredComparison', 'seoDistribution',
            'approachingDeadline', 'verifiedCount', 'unverifiedCount'
        ));
    }

    public function data(Request $request)
    {
        [$from, $to] = $this->getDateRange($request);

        $trendRaw = $this->applyRange(
            JobPost::selectRaw('DATE(created_at) as date, COUNT(*) as cnt')->groupBy('date')->orderBy('date'),
            $from, $to
        )->pluck('cnt', 'date')->toArray();

        [$dates, $values] = $this->fillDateSeries($from ?? now()->subDays(30), $to, $trendRaw);

        return response()->json([
            'trend' => ['dates' => $dates, 'values' => $values],
            'kpis'  => [
                'total'  => JobPost::withTrashed()->count(),
                'active' => JobPost::where('is_active', true)->where('deadline', '>=', now())->count(),
            ],
        ]);
    }

    public function export(Request $request)
    {
        [$from, $to] = $this->getDateRange($request);

        $jobs = $this->applyRange(
            JobPost::with(['company', 'jobCategory', 'jobLocation', 'industry'])
                ->select(['id', 'job_title', 'company_id', 'job_category_id', 'job_location_id',
                    'industry_id', 'is_active', 'is_featured', 'is_urgent', 'is_verified',
                    'view_count', 'click_count', 'application_count', 'seo_score',
                    'content_quality_score', 'deadline', 'published_at', 'created_at']),
            $from, $to
        )->get();

        $filename = 'jobs-analytics-' . now()->format('Y-m-d') . '.csv';
        $headers  = ['Content-Type' => 'text/csv', 'Content-Disposition' => "attachment; filename=\"{$filename}\""];

        $callback = function () use ($jobs) {
            $handle = fopen('php://output', 'w');
            fputcsv($handle, ['ID', 'Title', 'Company', 'Category', 'Location', 'Industry',
                'Active', 'Featured', 'Urgent', 'Verified', 'Views', 'Clicks',
                'Applications', 'SEO Score', 'Quality Score', 'Deadline', 'Published', 'Created']);
            foreach ($jobs as $job) {
                fputcsv($handle, [
                    $job->id, $job->job_title,
                    $job->company?->name ?? '',
                    $job->jobCategory?->name ?? '',
                    $job->jobLocation?->district ?? '',
                    $job->industry?->name ?? '',
                    $job->is_active ? 'Yes' : 'No',
                    $job->is_featured ? 'Yes' : 'No',
                    $job->is_urgent ? 'Yes' : 'No',
                    $job->is_verified ? 'Yes' : 'No',
                    $job->view_count, $job->click_count, $job->application_count,
                    $job->seo_score, $job->content_quality_score,
                    $job->deadline?->format('Y-m-d') ?? '',
                    $job->published_at?->format('Y-m-d') ?? '',
                    $job->created_at->format('Y-m-d'),
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
            '24h'   => [$now->copy()->subDay(),       $now],
            '7d'    => [$now->copy()->subDays(7),     $now],
            '30d'   => [$now->copy()->subDays(30),    $now],
            'all'   => [null,                          $now],
            'custom'=> [
                $request->filled('from') ? Carbon::parse($request->get('from'))->startOfDay() : $now->copy()->subDays(30),
                $request->filled('to')   ? Carbon::parse($request->get('to'))->endOfDay()     : $now,
            ],
            default => [$now->copy()->subDays(30), $now],
        };
    }

    private function applyRange($query, ?Carbon $from, Carbon $to, string $col = 'created_at')
    {
        if ($from) {
            $query->whereBetween($col, [$from, $to]);
        } else {
            $query->where($col, '<=', $to);
        }
        return $query;
    }

    private function fillDateSeries(Carbon $from, Carbon $to, array $data): array
    {
        $dates  = [];
        $values = [];
        $days   = (int) $from->diffInDays($to);

        for ($i = $days; $i >= 0; $i--) {
            $d        = $to->copy()->subDays($i);
            $key      = $d->format('Y-m-d');
            $dates[]  = $d->format('M d');
            $values[] = $data[$key] ?? 0;
        }
        return [$dates, $values];
    }
}
