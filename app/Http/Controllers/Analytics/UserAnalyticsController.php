<?php

namespace App\Http\Controllers\Analytics;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Models\Auth\{User, LoginToken};
use Carbon\Carbon;

class UserAnalyticsController extends Controller
{
    public function index(Request $request)
    {
        [$from, $to] = $this->getDateRange($request);
        $currentRange = $request->get('range', '30d');

        // ── KPIs ──────────────────────────────────────────────────────────────
        $kpis = [
            'total'           => User::count(),
            'active'          => User::where('is_active', true)->count(),
            'inactive'        => User::where('is_active', false)->count(),
            'verified'        => User::whereNotNull('email_verified_at')->count(),
            'unverified'      => User::whereNull('email_verified_at')->count(),
            'deleted'         => User::onlyTrashed()->count(),
            'in_period'       => $this->applyRange(User::query(), $from, $to)->count(),
            'active_7d'       => User::where('last_login_at', '>=', now()->subDays(7))->count(),
            'active_30d'      => User::where('last_login_at', '>=', now()->subDays(30))->count(),
            'never_logged_in' => User::whereNull('last_login_at')->count(),
        ];

        $kpis['verification_rate'] = $kpis['total'] > 0
            ? round($kpis['verified'] / $kpis['total'] * 100, 1) : 0;

        // ── Registration trend ────────────────────────────────────────────────
        $trendRaw = $this->applyRange(
            User::selectRaw('DATE(created_at) as date, COUNT(*) as cnt')->groupBy('date')->orderBy('date'),
            $from, $to
        )->pluck('cnt', 'date')->toArray();

        [$trendDates, $trendValues] = $this->fillDateSeries($from ?? now()->subDays(30), $to, $trendRaw);

        // ── Users by role ─────────────────────────────────────────────────────
        $byRole = DB::table('users')
            ->join('model_has_roles', function ($j) {
                $j->on('users.id', '=', 'model_has_roles.model_id')
                  ->where('model_has_roles.model_type', 'App\\Models\\Auth\\User');
            })
            ->join('roles', 'model_has_roles.role_id', '=', 'roles.id')
            ->whereNull('users.deleted_at')
            ->select('roles.name as role', DB::raw('count(*) as cnt'))
            ->groupBy('roles.name')
            ->pluck('cnt', 'role')
            ->toArray();

        // ── Users by country ──────────────────────────────────────────────────
        $byCountry = User::selectRaw('country_code, COUNT(*) as cnt')
            ->groupBy('country_code')
            ->orderByDesc('cnt')
            ->limit(15)
            ->pluck('cnt', 'country_code')
            ->toArray();

        // ── Registration trend by role (employer vs job_seeker, last 30 days) ─
        $rolesTrend = DB::table('users')
            ->join('model_has_roles', function ($j) {
                $j->on('users.id', '=', 'model_has_roles.model_id')
                  ->where('model_has_roles.model_type', 'App\\Models\\Auth\\User');
            })
            ->join('roles', 'model_has_roles.role_id', '=', 'roles.id')
            ->whereNull('users.deleted_at')
            ->where('users.created_at', '>=', now()->subDays(29)->startOfDay())
            ->whereIn('roles.name', ['employer', 'job_seeker'])
            ->selectRaw("DATE(users.created_at) as date, roles.name as role, COUNT(*) as cnt")
            ->groupBy('date', 'roles.name')
            ->orderBy('date')
            ->get();

        $rolesTrendDates    = [];
        $employerValues     = [];
        $jobSeekerValues    = [];
        $rolesTrendMap      = [];

        foreach ($rolesTrend as $row) {
            $rolesTrendMap[$row->date][$row->role] = $row->cnt;
        }
        for ($i = 29; $i >= 0; $i--) {
            $d = now()->subDays($i)->format('Y-m-d');
            $rolesTrendDates[]  = now()->subDays($i)->format('M d');
            $employerValues[]   = $rolesTrendMap[$d]['employer']   ?? 0;
            $jobSeekerValues[]  = $rolesTrendMap[$d]['job_seeker'] ?? 0;
        }

        // ── Login activity heatmap data ───────────────────────────────────────
        $loginActivity = User::whereNotNull('last_login_at')
            ->selectRaw('DATE(last_login_at) as date, COUNT(*) as cnt')
            ->where('last_login_at', '>=', now()->subDays(89))
            ->groupBy('date')
            ->orderBy('date')
            ->pluck('cnt', 'date')
            ->toArray();

        $loginDates  = [];
        $loginValues = [];
        for ($i = 89; $i >= 0; $i--) {
            $d = now()->subDays($i)->format('Y-m-d');
            $loginDates[]  = now()->subDays($i)->format('M d');
            $loginValues[] = $loginActivity[$d] ?? 0;
        }

        // ── Magic link usage (login tokens created) ───────────────────────────
        $magicLinkUsage = DB::table('login_tokens')
            ->where('created_at', '>=', now()->subDays(29))
            ->selectRaw('DATE(created_at) as date, COUNT(*) as total, SUM(CASE WHEN used_at IS NOT NULL THEN 1 ELSE 0 END) as used')
            ->groupBy('date')
            ->orderBy('date')
            ->get();

        $mlDates  = [];
        $mlSent   = [];
        $mlUsed   = [];
        $mlMap    = $magicLinkUsage->keyBy('date');
        for ($i = 29; $i >= 0; $i--) {
            $d = now()->subDays($i)->format('Y-m-d');
            $mlDates[] = now()->subDays($i)->format('M d');
            $row = $mlMap[$d] ?? null;
            $mlSent[] = $row ? (int) $row->total : 0;
            $mlUsed[] = $row ? (int) $row->used  : 0;
        }

        // ── Churned users (registered but never logged in, >30 days ago) ──────
        $churnedUsers = User::whereNull('last_login_at')
            ->where('created_at', '<', now()->subDays(30))
            ->latest()
            ->limit(20)
            ->get(['id', 'first_name', 'last_name', 'email', 'created_at', 'is_active', 'role_id']);

        // ── Most recently active users ────────────────────────────────────────
        $recentlyActive = User::whereNotNull('last_login_at')
            ->with('role')
            ->orderByDesc('last_login_at')
            ->limit(10)
            ->get(['id', 'first_name', 'last_name', 'email', 'last_login_at', 'role_id', 'country_code']);

        return view('analytics.users', compact(
            'kpis', 'currentRange',
            'trendDates', 'trendValues',
            'byRole', 'byCountry',
            'rolesTrendDates', 'employerValues', 'jobSeekerValues',
            'loginDates', 'loginValues',
            'mlDates', 'mlSent', 'mlUsed',
            'churnedUsers', 'recentlyActive'
        ));
    }

    public function data(Request $request)
    {
        [$from, $to] = $this->getDateRange($request);
        $trendRaw = $this->applyRange(
            User::selectRaw('DATE(created_at) as date, COUNT(*) as cnt')->groupBy('date')->orderBy('date'),
            $from, $to
        )->pluck('cnt', 'date')->toArray();

        [$dates, $values] = $this->fillDateSeries($from ?? now()->subDays(30), $to, $trendRaw);
        return response()->json(['trend' => compact('dates', 'values')]);
    }

    public function export(Request $request)
    {
        [$from, $to] = $this->getDateRange($request);

        $users = $this->applyRange(User::with('role'), $from, $to)->get();

        $filename = 'users-analytics-' . now()->format('Y-m-d') . '.csv';
        $headers  = ['Content-Type' => 'text/csv', 'Content-Disposition' => "attachment; filename=\"{$filename}\""];

        $callback = function () use ($users) {
            $handle = fopen('php://output', 'w');
            fputcsv($handle, ['ID', 'First Name', 'Last Name', 'Email', 'Role',
                'Country', 'Active', 'Email Verified', 'Last Login', 'Registered']);
            foreach ($users as $u) {
                fputcsv($handle, [
                    $u->id, $u->first_name, $u->last_name, $u->email,
                    $u->role?->name ?? '',
                    $u->country_code,
                    $u->is_active ? 'Yes' : 'No',
                    $u->email_verified_at ? 'Yes' : 'No',
                    $u->last_login_at?->format('Y-m-d H:i') ?? 'Never',
                    $u->created_at->format('Y-m-d H:i'),
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
