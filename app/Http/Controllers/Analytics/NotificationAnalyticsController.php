<?php

namespace App\Http\Controllers\Analytics;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class NotificationAnalyticsController extends Controller
{
    public function index(Request $request)
    {
        [$from, $to] = $this->getDateRange($request);
        $currentRange = $request->get('range', '30d');

        // ── Notification KPIs ─────────────────────────────────────────────────
        $kpis = [
            'total'           => DB::table('notifications')->whereNull('deleted_at')->count(),
            'unread'          => DB::table('notifications')->where('status', 'unread')->whereNull('deleted_at')->count(),
            'read'            => DB::table('notifications')->where('status', 'read')->whereNull('deleted_at')->count(),
            'resolved'        => DB::table('notifications')->where('status', 'resolved')->whereNull('deleted_at')->count(),
            'archived'        => DB::table('notifications')->where('status', 'archived')->whereNull('deleted_at')->count(),
            'urgent'          => DB::table('notifications')->where('priority', 'urgent')->whereNull('deleted_at')->count(),
            'high'            => DB::table('notifications')->where('priority', 'high')->whereNull('deleted_at')->count(),
            'unresolved_high' => DB::table('notifications')->where('priority', 'high')
                                    ->whereNotIn('status', ['resolved', 'archived'])->whereNull('deleted_at')->count(),
            'unresolved_urgent'=> DB::table('notifications')->where('priority', 'urgent')
                                    ->whereNotIn('status', ['resolved', 'archived'])->whereNull('deleted_at')->count(),
            'in_period'       => $this->applyRange(DB::table('notifications')->whereNull('deleted_at'), $from, $to)->count(),
        ];

        // Average resolution time (hours)
        $avgResolution = DB::table('notifications')
            ->whereNotNull('resolved_at')
            ->whereNotNull('created_at')
            ->selectRaw("AVG(TIMESTAMPDIFF(SECOND, created_at, resolved_at) / 3600) as avg_hours")
            ->value('avg_hours');
        $kpis['avg_resolution_hours'] = round((float) $avgResolution, 1);

        // ── Notification volume over time ─────────────────────────────────────
        $notifTrendRaw = $this->applyRange(
            DB::table('notifications')->whereNull('deleted_at')
                ->selectRaw('DATE(created_at) as date, COUNT(*) as cnt')->groupBy('date')->orderBy('date'),
            $from, $to
        )->pluck('cnt', 'date')->toArray();

        [$notifDates, $notifValues] = $this->fillDateSeries($from ?? now()->subDays(30), $to, $notifTrendRaw);

        // ── Status breakdown ──────────────────────────────────────────────────
        $statusBreakdown = DB::table('notifications')
            ->whereNull('deleted_at')
            ->selectRaw('status, COUNT(*) as cnt')
            ->groupBy('status')
            ->pluck('cnt', 'status')
            ->toArray();

        // ── Priority breakdown ────────────────────────────────────────────────
        $priorityBreakdown = DB::table('notifications')
            ->whereNull('deleted_at')
            ->selectRaw('priority, COUNT(*) as cnt')
            ->groupBy('priority')
            ->pluck('cnt', 'priority')
            ->toArray();

        // ── Notification type breakdown ───────────────────────────────────────
        $typeBreakdown = DB::table('notifications')
            ->whereNull('deleted_at')
            ->selectRaw('type, COUNT(*) as cnt')
            ->groupBy('type')
            ->orderByDesc('cnt')
            ->limit(10)
            ->pluck('cnt', 'type')
            ->toArray();

        // ── Unresolved high-priority notifications ────────────────────────────
        $unresolvedUrgent = DB::table('notifications')
            ->whereNull('deleted_at')
            ->whereIn('priority', ['urgent', 'high'])
            ->whereNotIn('status', ['resolved', 'archived'])
            ->orderByRaw("CASE priority WHEN 'urgent' THEN 0 WHEN 'high' THEN 1 ELSE 2 END")
            ->orderBy('created_at')
            ->limit(20)
            ->get();

        // ── Resolution trend ──────────────────────────────────────────────────
        $resolvedTrendRaw = $this->applyRange(
            DB::table('notifications')->whereNotNull('resolved_at')
                ->selectRaw('DATE(resolved_at) as date, COUNT(*) as cnt')->groupBy('date')->orderBy('date'),
            $from, $to, 'resolved_at'
        )->pluck('cnt', 'date')->toArray();

        [$resolvedDates, $resolvedValues] = $this->fillDateSeries($from ?? now()->subDays(30), $to, $resolvedTrendRaw);

        // ── Audit log stats ───────────────────────────────────────────────────
        $auditKpis = [
            'total'       => DB::table('job_audit_logs')->count(),
            'in_period'   => $this->applyRange(DB::table('job_audit_logs'), $from, $to)->count(),
        ];

        // Actions breakdown
        $auditByAction = DB::table('job_audit_logs')
            ->selectRaw('action, COUNT(*) as cnt')
            ->groupBy('action')
            ->orderByDesc('cnt')
            ->pluck('cnt', 'action')
            ->toArray();

        // Source breakdown
        $auditBySource = DB::table('job_audit_logs')
            ->selectRaw('source, COUNT(*) as cnt')
            ->groupBy('source')
            ->pluck('cnt', 'source')
            ->toArray();

        // Most active users in audit log
        $auditTopUsers = DB::table('job_audit_logs')
            ->join('users', 'job_audit_logs.user_id', '=', 'users.id')
            ->selectRaw('users.id, users.first_name, users.last_name, users.email, COUNT(*) as cnt')
            ->groupBy('users.id', 'users.first_name', 'users.last_name', 'users.email')
            ->orderByDesc('cnt')
            ->limit(10)
            ->get();

        // Audit trend
        $auditTrendRaw = $this->applyRange(
            DB::table('job_audit_logs')
                ->selectRaw('DATE(created_at) as date, COUNT(*) as cnt')->groupBy('date')->orderBy('date'),
            $from, $to
        )->pluck('cnt', 'date')->toArray();

        [, $auditValues] = $this->fillDateSeries($from ?? now()->subDays(30), $to, $auditTrendRaw);

        return view('analytics.notifications', compact(
            'kpis', 'currentRange',
            'notifDates', 'notifValues',
            'statusBreakdown', 'priorityBreakdown', 'typeBreakdown',
            'unresolvedUrgent',
            'resolvedDates', 'resolvedValues',
            'auditKpis', 'auditByAction', 'auditBySource',
            'auditTopUsers', 'auditValues'
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
