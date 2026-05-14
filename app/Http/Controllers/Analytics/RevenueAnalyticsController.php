<?php

namespace App\Http\Controllers\Analytics;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Models\Payments\{Transaction, PaymentPlan};
use Carbon\Carbon;

class RevenueAnalyticsController extends Controller
{
    public function index(Request $request)
    {
        [$from, $to] = $this->getDateRange($request);
        $currentRange = $request->get('range', '30d');

        // ── Revenue KPIs ──────────────────────────────────────────────────────
        $kpis = [
            'total_revenue'    => (float) Transaction::where('status', 'successful')->sum('net_amount'),
            'today_revenue'    => (float) Transaction::where('status', 'successful')->whereDate('created_at', today())->sum('net_amount'),
            'week_revenue'     => (float) Transaction::where('status', 'successful')->where('created_at', '>=', now()->startOfWeek())->sum('net_amount'),
            'month_revenue'    => (float) Transaction::where('status', 'successful')->whereMonth('created_at', now()->month)->whereYear('created_at', now()->year)->sum('net_amount'),
            'period_revenue'   => (float) $this->applyRange(Transaction::where('status', 'successful'), $from, $to)->sum('net_amount'),
            'total_fees'       => (float) Transaction::where('status', 'successful')->sum('gateway_fee'),
            'avg_transaction'  => (float) Transaction::where('status', 'successful')->avg('net_amount'),
            'total_count'      => Transaction::count(),
            'successful'       => Transaction::where('status', 'successful')->count(),
            'pending'          => Transaction::whereIn('status', ['pending', 'processing'])->count(),
            'failed'           => Transaction::where('status', 'failed')->count(),
            'refunded'         => Transaction::where('status', 'refunded')->count(),
            'disputed'         => Transaction::where('status', 'disputed')->count(),
            'cancelled'        => Transaction::where('status', 'cancelled')->count(),
            'flagged'          => Transaction::where('is_flagged', true)->count(),
        ];

        $kpis['success_rate'] = $kpis['total_count'] > 0
            ? round($kpis['successful'] / $kpis['total_count'] * 100, 1) : 0;

        // ── Revenue over time ─────────────────────────────────────────────────
        $revTrendRaw = $this->applyRange(
            Transaction::where('status', 'successful')
                ->selectRaw('DATE(created_at) as date, SUM(net_amount) as total')
                ->groupBy('date')->orderBy('date'),
            $from, $to
        )->pluck('total', 'date')->toArray();

        [$revTrendDates, $revTrendValues] = $this->fillDateSeries($from ?? now()->subDays(30), $to, $revTrendRaw);

        // ── Transaction volume over time ───────────────────────────────────────
        $txTrendRaw = $this->applyRange(
            Transaction::selectRaw('DATE(created_at) as date, COUNT(*) as cnt')->groupBy('date')->orderBy('date'),
            $from, $to
        )->pluck('cnt', 'date')->toArray();

        [$txTrendDates, $txTrendValues] = $this->fillDateSeries($from ?? now()->subDays(30), $to, $txTrendRaw);

        // ── Revenue by plan type ──────────────────────────────────────────────
        $revenueByPlanType = Transaction::where('status', 'successful')
            ->join('payment_plans', 'transactions.plan_id', '=', 'payment_plans.id')
            ->selectRaw('payment_plans.type, SUM(transactions.net_amount) as total, COUNT(*) as cnt')
            ->groupBy('payment_plans.type')
            ->get()
            ->map(fn($r) => ['type' => $r->type, 'total' => (float)$r->total, 'count' => $r->cnt]);

        // ── Revenue by gateway ────────────────────────────────────────────────
        $revenueByGateway = Transaction::where('status', 'successful')
            ->whereNotNull('payment_gateway')
            ->selectRaw('payment_gateway, SUM(net_amount) as total, COUNT(*) as cnt')
            ->groupBy('payment_gateway')
            ->get()
            ->map(fn($r) => ['gateway' => $r->payment_gateway, 'total' => (float)$r->total, 'count' => $r->cnt]);

        // ── Revenue by payment method ──────────────────────────────────────────
        $revenueByMethod = Transaction::where('status', 'successful')
            ->whereNotNull('payment_method')
            ->selectRaw('payment_method, SUM(net_amount) as total, COUNT(*) as cnt')
            ->groupBy('payment_method')
            ->orderByDesc('total')
            ->get()
            ->map(fn($r) => ['method' => $r->payment_method, 'total' => (float)$r->total, 'count' => $r->cnt]);

        // ── Transaction status breakdown ──────────────────────────────────────
        $statusBreakdown = Transaction::selectRaw('status, COUNT(*) as cnt')
            ->groupBy('status')
            ->pluck('cnt', 'status')
            ->toArray();

        // ── Top payment plans by revenue ───────────────────────────────────────
        $topPlans = PaymentPlan::withSum(['transactions as revenue' => fn($q) =>
                $q->where('status', 'successful')], 'net_amount')
            ->withCount(['transactions as txn_count' => fn($q) =>
                $q->where('status', 'successful')])
            ->orderByDesc('revenue')
            ->get(['id', 'name', 'type', 'amount', 'currency', 'duration_days']);

        // ── Failed transaction analysis ────────────────────────────────────────
        $failureReasons = Transaction::where('status', 'failed')
            ->whereNotNull('gateway_message')
            ->selectRaw('gateway_message, COUNT(*) as cnt')
            ->groupBy('gateway_message')
            ->orderByDesc('cnt')
            ->limit(10)
            ->pluck('cnt', 'gateway_message')
            ->toArray();

        // ── Flagged transactions ───────────────────────────────────────────────
        $flaggedTransactions = Transaction::where('is_flagged', true)
            ->latest()
            ->limit(15)
            ->get(['id', 'reference', 'amount', 'currency', 'status',
                'customer_name', 'customer_email', 'flag_reason', 'created_at']);

        // ── Monthly revenue comparison (last 6 months) ─────────────────────────
        $monthlyRevenue = Transaction::where('status', 'successful')
            ->where('created_at', '>=', now()->subMonths(6)->startOfMonth())
            ->selectRaw("DATE_FORMAT(created_at, '%b %Y') as month, DATE_FORMAT(created_at, '%Y-%m-01') as month_start, SUM(net_amount) as total")
            ->groupBy('month', 'month_start')
            ->orderBy('month_start')
            ->get();

        $monthlyLabels = $monthlyRevenue->pluck('month')->toArray();
        $monthlyValues = $monthlyRevenue->map(fn($r) => round((float)$r->total, 2))->toArray();

        // ── Currency breakdown ────────────────────────────────────────────────
        $byCurrency = Transaction::where('status', 'successful')
            ->selectRaw('currency, SUM(net_amount) as total, COUNT(*) as cnt')
            ->groupBy('currency')
            ->get()
            ->map(fn($r) => ['currency' => $r->currency, 'total' => (float)$r->total, 'count' => $r->cnt]);

        return view('analytics.revenue', compact(
            'kpis', 'currentRange',
            'revTrendDates', 'revTrendValues',
            'txTrendDates', 'txTrendValues',
            'revenueByPlanType', 'revenueByGateway', 'revenueByMethod',
            'statusBreakdown', 'topPlans', 'failureReasons',
            'flaggedTransactions', 'monthlyLabels', 'monthlyValues', 'byCurrency'
        ));
    }

    public function data(Request $request)
    {
        [$from, $to] = $this->getDateRange($request);
        $revenue = (float) $this->applyRange(
            Transaction::where('status', 'successful'), $from, $to
        )->sum('net_amount');
        return response()->json(['revenue' => $revenue]);
    }

    public function export(Request $request)
    {
        [$from, $to] = $this->getDateRange($request);
        $transactions = $this->applyRange(Transaction::query(), $from, $to)->get();

        $filename = 'revenue-analytics-' . now()->format('Y-m-d') . '.csv';
        $headers  = ['Content-Type' => 'text/csv', 'Content-Disposition' => "attachment; filename=\"{$filename}\""];

        $callback = function () use ($transactions) {
            $handle = fopen('php://output', 'w');
            fputcsv($handle, ['Reference', 'Customer', 'Amount', 'Fee', 'Net Amount', 'Currency',
                'Status', 'Gateway', 'Method', 'Channel', 'Flagged', 'Date']);
            foreach ($transactions as $t) {
                fputcsv($handle, [
                    $t->reference, $t->customer_name ?? '',
                    $t->amount, $t->gateway_fee, $t->net_amount, $t->currency,
                    $t->status, $t->payment_gateway ?? '',
                    $t->payment_method ?? '', $t->payment_channel ?? '',
                    $t->is_flagged ? 'Yes' : 'No',
                    $t->created_at->format('Y-m-d H:i'),
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
            $values[] = isset($data[$key]) ? round((float)$data[$key], 2) : 0;
        }
        return [$dates, $values];
    }
}
