<?php

namespace App\Http\Controllers\Analytics;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Models\Job\{JobPost, Company};
use Carbon\Carbon;

class EmployerAnalyticsController extends Controller
{
    public function index(Request $request)
    {
        /** @var \App\Models\Auth\User $user */
        $user = $request->user();
        [$from, $to] = $this->getDateRange($request);
        $currentRange = $request->get('range', '30d');

        // Admin/moderator can view any employer; employer sees own data
        $employerId = $request->get('employer_id');
        if ($user->isAdmin() || $user->isModerator()) {
            $companyQuery = Company::query();
            if ($employerId) {
                $companyQuery->where('created_by', $employerId);
            }
        } else {
            $companyQuery = Company::where('created_by', $user->id);
        }

        $companies = $companyQuery->get(['id', 'name', 'is_verified', 'is_active', 'created_at']);
        $companyIds = $companies->pluck('id')->toArray();

        // ── KPIs ──────────────────────────────────────────────────────────────
        $kpis = [
            'companies'          => count($companyIds),
            'verified_companies' => $companies->where('is_verified', true)->count(),
            'total_jobs'         => JobPost::whereIn('company_id', $companyIds)->count(),
            'active_jobs'        => JobPost::whereIn('company_id', $companyIds)
                                        ->where('is_active', true)->where('deadline', '>=', now())->count(),
            'expired_jobs'       => JobPost::whereIn('company_id', $companyIds)
                                        ->where('deadline', '<', now())->count(),
            'featured_jobs'      => JobPost::whereIn('company_id', $companyIds)->where('is_featured', true)->count(),
            'total_views'        => (int) JobPost::whereIn('company_id', $companyIds)->sum('view_count'),
            'total_applications' => (int) JobPost::whereIn('company_id', $companyIds)->sum('application_count'),
            'total_clicks'       => (int) JobPost::whereIn('company_id', $companyIds)->sum('click_count'),
            'avg_seo'            => round((float) JobPost::whereIn('company_id', $companyIds)->avg('seo_score'), 1),
        ];

        if ($kpis['total_jobs'] > 0) {
            $kpis['avg_views_per_job'] = round($kpis['total_views'] / $kpis['total_jobs'], 1);
            $kpis['avg_apps_per_job']  = round($kpis['total_applications'] / $kpis['total_jobs'], 1);
        } else {
            $kpis['avg_views_per_job'] = 0;
            $kpis['avg_apps_per_job']  = 0;
        }

        // ── Jobs posted over time ─────────────────────────────────────────────
        $trendRaw = $this->applyRange(
            JobPost::whereIn('company_id', $companyIds)
                ->selectRaw('DATE(created_at) as date, COUNT(*) as cnt')->groupBy('date')->orderBy('date'),
            $from, $to
        )->pluck('cnt', 'date')->toArray();

        [$trendDates, $trendValues] = $this->fillDateSeries($from ?? now()->subDays(30), $to, $trendRaw);

        // ── Job status breakdown ──────────────────────────────────────────────
        $jobStatus = [
            'Active'   => $kpis['active_jobs'],
            'Expired'  => $kpis['expired_jobs'],
            'Inactive' => JobPost::whereIn('company_id', $companyIds)->where('is_active', false)->count(),
            'Featured' => $kpis['featured_jobs'],
        ];

        // ── Top performing jobs (own) ─────────────────────────────────────────
        $topJobs = JobPost::whereIn('company_id', $companyIds)
            ->with('company')
            ->orderByDesc('view_count')
            ->limit(15)
            ->get(['id', 'job_title', 'company_id', 'view_count', 'click_count',
                'application_count', 'is_active', 'deadline', 'seo_score', 'created_at']);

        // ── Jobs expiring soon ────────────────────────────────────────────────
        $expiringSoon = JobPost::whereIn('company_id', $companyIds)
            ->where('is_active', true)
            ->whereBetween('deadline', [now(), now()->addDays(14)])
            ->orderBy('deadline')
            ->limit(10)
            ->get(['id', 'job_title', 'company_id', 'deadline', 'application_count']);

        // ── Company performance comparison ────────────────────────────────────
        $companyPerformance = Company::whereIn('id', $companyIds)
            ->withSum('jobPosts as total_views', 'view_count')
            ->withSum('jobPosts as total_applications', 'application_count')
            ->withCount('jobPosts')
            ->get(['id', 'name']);

        // ── Views over time (sum across all own jobs) ─────────────────────────
        $viewsOverTime = $this->applyRange(
            JobPost::whereIn('company_id', $companyIds)
                ->selectRaw('DATE(created_at) as date, SUM(view_count) as views')
                ->groupBy('date')->orderBy('date'),
            $from, $to
        )->pluck('views', 'date')->toArray();

        [, $viewsValues] = $this->fillDateSeries($from ?? now()->subDays(30), $to, $viewsOverTime);

        return view('analytics.employer', compact(
            'kpis', 'currentRange', 'companies',
            'trendDates', 'trendValues', 'viewsValues',
            'jobStatus', 'topJobs', 'expiringSoon', 'companyPerformance'
        ));
    }

    public function data(Request $request)
    {
        return response()->json(['success' => true]);
    }

    public function export(Request $request)
    {
        /** @var \App\Models\Auth\User $user */
        $user       = $request->user();
        $companyIds = Company::where('created_by', $user->isAdmin() ? '!=' : $user->id)->pluck('id')->toArray();
        if ($user->isEmployer()) {
            $companyIds = Company::where('created_by', $user->id)->pluck('id')->toArray();
        }

        [$from, $to] = $this->getDateRange($request);
        $jobs = $this->applyRange(
            JobPost::whereIn('company_id', $companyIds)->with('company'),
            $from, $to
        )->get();

        $filename = 'employer-analytics-' . now()->format('Y-m-d') . '.csv';
        $headers  = ['Content-Type' => 'text/csv', 'Content-Disposition' => "attachment; filename=\"{$filename}\""];

        $callback = function () use ($jobs) {
            $handle = fopen('php://output', 'w');
            fputcsv($handle, ['ID', 'Title', 'Company', 'Active', 'Featured',
                'Views', 'Clicks', 'Applications', 'SEO Score', 'Deadline', 'Created']);
            foreach ($jobs as $j) {
                fputcsv($handle, [
                    $j->id, $j->job_title, $j->company?->name ?? '',
                    $j->is_active ? 'Yes' : 'No',
                    $j->is_featured ? 'Yes' : 'No',
                    $j->view_count, $j->click_count, $j->application_count,
                    $j->seo_score, $j->deadline?->format('Y-m-d') ?? '',
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
            $values[] = $data[$key] ?? 0;
        }
        return [$dates, $values];
    }
}
