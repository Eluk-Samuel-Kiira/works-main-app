<?php

namespace App\Http\Controllers\Analytics;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Models\System\{ApiKey, ApiLog};
use Carbon\Carbon;

class ApiAnalyticsController extends Controller
{
    public function index(Request $request)
    {
        [$from, $to] = $this->getDateRange($request);
        $currentRange = $request->get('range', '30d');

        // ── KPIs ──────────────────────────────────────────────────────────────
        $kpis = [
            'total_calls'    => $this->applyRange(DB::table('api_logs'), $from, $to)->count(),
            'successful'     => $this->applyRange(DB::table('api_logs')->where('is_success', true), $from, $to)->count(),
            'failed'         => $this->applyRange(DB::table('api_logs')->where('is_success', false), $from, $to)->count(),
            'avg_duration'   => round((float) $this->applyRange(DB::table('api_logs'), $from, $to)->avg('duration_ms'), 1),
            'max_duration'   => (int) $this->applyRange(DB::table('api_logs'), $from, $to)->max('duration_ms'),
            'total_keys'     => DB::table('api_keys')->whereNull('deleted_at')->count(),
            'active_keys'    => DB::table('api_keys')->where('is_active', true)->whereNull('deleted_at')->count(),
        ];

        $kpis['success_rate'] = $kpis['total_calls'] > 0
            ? round($kpis['successful'] / $kpis['total_calls'] * 100, 1) : 0;

        // ── API calls by service ──────────────────────────────────────────────
        $byService = $this->applyRange(DB::table('api_logs'), $from, $to)
            ->selectRaw('service, COUNT(*) as cnt, AVG(duration_ms) as avg_dur, SUM(CASE WHEN is_success THEN 1 ELSE 0 END) as success_cnt')
            ->groupBy('service')
            ->orderByDesc('cnt')
            ->get()
            ->map(fn($r) => [
                'service'      => $r->service,
                'count'        => (int)$r->cnt,
                'avg_duration' => round((float)$r->avg_dur, 1),
                'success_rate' => $r->cnt > 0 ? round($r->success_cnt / $r->cnt * 100, 1) : 0,
            ]);

        // ── Call volume over time ─────────────────────────────────────────────
        $callsTrendRaw = $this->applyRange(
            DB::table('api_logs')->selectRaw('DATE(created_at) as date, COUNT(*) as cnt')->groupBy('date')->orderBy('date'),
            $from, $to
        )->pluck('cnt', 'date')->toArray();

        [$callsDates, $callsValues] = $this->fillDateSeries($from ?? now()->subDays(30), $to, $callsTrendRaw);

        // ── Top endpoints by call count ───────────────────────────────────────
        $topEndpoints = $this->applyRange(DB::table('api_logs'), $from, $to)
            ->selectRaw('endpoint, method, COUNT(*) as cnt, AVG(duration_ms) as avg_dur, SUM(CASE WHEN is_success THEN 0 ELSE 1 END) as error_cnt')
            ->groupBy('endpoint', 'method')
            ->orderByDesc('cnt')
            ->limit(15)
            ->get();

        // ── Slowest endpoints ─────────────────────────────────────────────────
        $slowestEndpoints = $this->applyRange(DB::table('api_logs'), $from, $to)
            ->selectRaw('endpoint, method, AVG(duration_ms) as avg_dur, COUNT(*) as cnt')
            ->groupBy('endpoint', 'method')
            ->havingRaw('COUNT(*) >= 3')
            ->orderByDesc('avg_dur')
            ->limit(10)
            ->get();

        // ── Error breakdown ───────────────────────────────────────────────────
        $errorBreakdown = $this->applyRange(
            DB::table('api_logs')->where('is_success', false)->whereNotNull('error_message'),
            $from, $to
        )
            ->selectRaw('error_message, COUNT(*) as cnt')
            ->groupBy('error_message')
            ->orderByDesc('cnt')
            ->limit(10)
            ->get();

        // ── Response code breakdown ───────────────────────────────────────────
        $responseCodes = $this->applyRange(DB::table('api_logs'), $from, $to)
            ->selectRaw('response_code, COUNT(*) as cnt')
            ->groupBy('response_code')
            ->orderByDesc('cnt')
            ->pluck('cnt', 'response_code')
            ->toArray();

        // ── API key usage ─────────────────────────────────────────────────────
        $apiKeyUsage = DB::table('api_keys')
            ->whereNull('deleted_at')
            ->where('is_active', true)
            ->select(['id', 'name', 'service', 'provider', 'usage_count',
                'usage_quota', 'last_used_at', 'rate_limits', 'expires_at', 'environment'])
            ->orderByDesc('usage_count')
            ->get();

        // ── Success/failure rate per service ──────────────────────────────────
        $serviceRates = $byService->map(fn($s) => [
            'name'         => $s['service'],
            'success_rate' => $s['success_rate'],
            'error_rate'   => 100 - $s['success_rate'],
        ]);

        return view('analytics.api', compact(
            'kpis', 'currentRange',
            'byService', 'callsDates', 'callsValues',
            'topEndpoints', 'slowestEndpoints',
            'errorBreakdown', 'responseCodes',
            'apiKeyUsage', 'serviceRates'
        ));
    }

    public function data(Request $request)
    {
        return response()->json(['success' => true]);
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
