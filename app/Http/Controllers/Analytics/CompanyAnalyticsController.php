<?php

namespace App\Http\Controllers\Analytics;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Models\Job\{Company, Industry, JobLocation, JobPost};
use Carbon\Carbon;

class CompanyAnalyticsController extends Controller
{
    public function index(Request $request)
    {
        [$from, $to] = $this->getDateRange($request);
        $currentRange = $request->get('range', '30d');

        // ── KPIs ──────────────────────────────────────────────────────────────
        $kpis = [
            'total'      => Company::count(),
            'verified'   => Company::where('is_verified', true)->count(),
            'unverified' => Company::where('is_verified', false)->count(),
            'active'     => Company::where('is_active', true)->count(),
            'inactive'   => Company::where('is_active', false)->count(),
            'in_period'  => $this->applyRange(Company::query(), $from, $to)->count(),
            'avg_jobs'   => round((float) DB::select("
                SELECT COALESCE(AVG(cnt), 0) as avg FROM (
                    SELECT COUNT(jp.id) as cnt
                    FROM companies c
                    LEFT JOIN job_posts jp ON c.id = jp.company_id AND jp.deleted_at IS NULL
                    GROUP BY c.id
                ) sub
            ")[0]->avg ?? 0, 1),
        ];
        $kpis['verification_rate'] = $kpis['total'] > 0
            ? round($kpis['verified'] / $kpis['total'] * 100, 1) : 0;

        // ── Registration trend ────────────────────────────────────────────────
        $trendRaw = $this->applyRange(
            Company::selectRaw('DATE(created_at) as date, COUNT(*) as cnt')->groupBy('date')->orderBy('date'),
            $from, $to
        )->pluck('cnt', 'date')->toArray();

        [$trendDates, $trendValues] = $this->fillDateSeries($from ?? now()->subDays(30), $to, $trendRaw);

        // ── Companies by industry ─────────────────────────────────────────────
        $byIndustry = Industry::withCount('companies')
            ->orderByDesc('companies_count')
            ->limit(10)
            ->get(['name', 'companies_count'])
            ->map(fn($r) => ['name' => $r->name, 'count' => $r->companies_count]);

        // ── Companies by location ─────────────────────────────────────────────
        $byLocation = JobLocation::withCount('companies')
            ->orderByDesc('companies_count')
            ->limit(10)
            ->get(['district', 'companies_count'])
            ->map(fn($r) => ['name' => $r->district ?? 'Unknown', 'count' => $r->companies_count]);

        // ── Most active companies (by job posts) ──────────────────────────────
        $mostActive = Company::withCount(['jobPosts', 'jobPosts as active_jobs_count' => fn($q) =>
                $q->where('is_active', true)->where('deadline', '>=', now())])
            ->with('industry')
            ->orderByDesc('job_posts_count')
            ->limit(15)
            ->get();

        // ── Verification trend ────────────────────────────────────────────────
        $verifiedTrend = Company::where('is_verified', true)
            ->where('created_at', '>=', now()->subDays(29))
            ->selectRaw('DATE(created_at) as date, COUNT(*) as cnt')
            ->groupBy('date')
            ->pluck('cnt', 'date')
            ->toArray();

        $vtDates  = [];
        $vtValues = [];
        for ($i = 29; $i >= 0; $i--) {
            $d = now()->subDays($i)->format('Y-m-d');
            $vtDates[]  = now()->subDays($i)->format('M d');
            $vtValues[] = $verifiedTrend[$d] ?? 0;
        }

        // ── Companies with no active jobs ─────────────────────────────────────
        $noActiveJobs = Company::whereDoesntHave('jobPosts', fn($q) =>
                $q->where('is_active', true)->where('deadline', '>=', now()))
            ->where('is_active', true)
            ->limit(15)
            ->get(['id', 'name', 'is_verified', 'created_at']);

        // ── Job posts per company distribution ───────────────────────────────
        $jobsPerCompanyDist = DB::table('companies')
            ->leftJoin('job_posts', function ($j) {
                $j->on('companies.id', '=', 'job_posts.company_id')
                  ->whereNull('job_posts.deleted_at');
            })
            ->selectRaw('companies.id, COUNT(job_posts.id) as job_count')
            ->groupBy('companies.id')
            ->get()
            ->groupBy(fn($r) => match (true) {
                $r->job_count === 0       => '0 jobs',
                $r->job_count <= 5        => '1–5 jobs',
                $r->job_count <= 20       => '6–20 jobs',
                $r->job_count <= 50       => '21–50 jobs',
                default                   => '50+ jobs',
            })
            ->map->count()
            ->toArray();

        return view('analytics.companies', compact(
            'kpis', 'currentRange',
            'trendDates', 'trendValues',
            'byIndustry', 'byLocation',
            'mostActive', 'vtDates', 'vtValues',
            'noActiveJobs', 'jobsPerCompanyDist'
        ));
    }

    public function data(Request $request)
    {
        return response()->json(['success' => true]);
    }

    public function export(Request $request)
    {
        [$from, $to] = $this->getDateRange($request);
        $companies = $this->applyRange(
            Company::with(['industry', 'location'])->withCount('jobPosts'),
            $from, $to
        )->get();

        $filename = 'companies-analytics-' . now()->format('Y-m-d') . '.csv';
        $headers  = ['Content-Type' => 'text/csv', 'Content-Disposition' => "attachment; filename=\"{$filename}\""];

        $callback = function () use ($companies) {
            $handle = fopen('php://output', 'w');
            fputcsv($handle, ['ID', 'Name', 'Industry', 'Location', 'Active',
                'Verified', 'Total Jobs', 'Website', 'Company Size', 'Registered']);
            foreach ($companies as $c) {
                fputcsv($handle, [
                    $c->id, $c->name,
                    $c->industry?->name ?? '',
                    $c->location?->district ?? '',
                    $c->is_active ? 'Yes' : 'No',
                    $c->is_verified ? 'Yes' : 'No',
                    $c->job_posts_count,
                    $c->website ?? '',
                    $c->company_size ?? '',
                    $c->created_at->format('Y-m-d'),
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
