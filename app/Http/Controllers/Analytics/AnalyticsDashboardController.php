<?php

namespace App\Http\Controllers\Analytics;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Models\Auth\User;
use App\Models\Job\{JobPost, Company};
use App\Models\Payments\Transaction;
use App\Models\Blog;
use Carbon\Carbon;

class AnalyticsDashboardController extends Controller
{
    public function index(Request $request)
    {
        // ── User role breakdown ──────────────────────────────────────────────
        $usersByRole = DB::table('users')
            ->join('model_has_roles', function ($j) {
                $j->on('users.id', '=', 'model_has_roles.model_id')
                  ->where('model_has_roles.model_type', 'App\\Models\\Auth\\User');
            })
            ->join('roles', 'model_has_roles.role_id', '=', 'roles.id')
            ->whereNull('users.deleted_at')
            ->select('roles.name as role', DB::raw('count(*) as cnt'))
            ->groupBy('roles.name')
            ->get()
            ->pluck('cnt', 'role')
            ->toArray();

        $totalUsers = User::count();

        // ── Job post stats ───────────────────────────────────────────────────
        $jobStats = [
            'total'    => JobPost::withTrashed()->count(),
            'active'   => JobPost::where('is_active', true)->where('deadline', '>=', now())->count(),
            'inactive' => JobPost::where('is_active', false)->count(),
            'expired'  => JobPost::where('deadline', '<', now())->count(),
            'featured' => JobPost::where('is_featured', true)->count(),
            'urgent'   => JobPost::where('is_urgent', true)->count(),
            'verified' => JobPost::where('is_verified', true)->count(),
        ];

        // ── Company stats ────────────────────────────────────────────────────
        $companyStats = [
            'total'      => Company::count(),
            'verified'   => Company::where('is_verified', true)->count(),
            'unverified' => Company::where('is_verified', false)->count(),
            'active'     => Company::where('is_active', true)->count(),
        ];

        // ── Revenue stats ────────────────────────────────────────────────────
        $revenueStats = [
            'total'             => (float) Transaction::where('status', 'successful')->sum('net_amount'),
            'this_month'        => (float) Transaction::where('status', 'successful')
                                       ->whereMonth('created_at', now()->month)
                                       ->whereYear('created_at', now()->year)
                                       ->sum('net_amount'),
            'today'             => (float) Transaction::where('status', 'successful')
                                       ->whereDate('created_at', today())
                                       ->sum('net_amount'),
            'total_count'       => Transaction::count(),
            'successful'        => Transaction::where('status', 'successful')->count(),
            'pending'           => Transaction::whereIn('status', ['pending', 'processing'])->count(),
            'failed'            => Transaction::where('status', 'failed')->count(),
        ];

        // ── New registrations ────────────────────────────────────────────────
        $newUsers = [
            'today'      => User::whereDate('created_at', today())->count(),
            'this_week'  => User::where('created_at', '>=', now()->startOfWeek())->count(),
            'this_month' => User::whereMonth('created_at', now()->month)
                               ->whereYear('created_at', now()->year)->count(),
        ];

        // ── New jobs ─────────────────────────────────────────────────────────
        $newJobs = [
            'today'      => JobPost::whereDate('created_at', today())->count(),
            'this_week'  => JobPost::where('created_at', '>=', now()->startOfWeek())->count(),
            'this_month' => JobPost::whereMonth('created_at', now()->month)
                               ->whereYear('created_at', now()->year)->count(),
        ];

        // ── Engagement totals ────────────────────────────────────────────────
        $engagementStats = [
            'total_views'        => (int) JobPost::sum('view_count'),
            'total_clicks'       => (int) JobPost::sum('click_count'),
            'total_applications' => (int) JobPost::sum('application_count'),
            'avg_views'          => round((float) JobPost::avg('view_count'), 1),
        ];

        // ── Jobs trend (last 30 days) ────────────────────────────────────────
        $rawJobsTrend = JobPost::where('created_at', '>=', now()->subDays(29)->startOfDay())
            ->selectRaw('DATE(created_at) as date, COUNT(*) as cnt')
            ->groupBy('date')
            ->orderBy('date')
            ->pluck('cnt', 'date')
            ->toArray();

        $jobsTrendDates  = [];
        $jobsTrendValues = [];
        for ($i = 29; $i >= 0; $i--) {
            $d = now()->subDays($i)->format('Y-m-d');
            $jobsTrendDates[]  = now()->subDays($i)->format('M d');
            $jobsTrendValues[] = $rawJobsTrend[$d] ?? 0;
        }

        // ── Users trend (last 30 days) ───────────────────────────────────────
        $rawUsersTrend = User::where('created_at', '>=', now()->subDays(29)->startOfDay())
            ->selectRaw('DATE(created_at) as date, COUNT(*) as cnt')
            ->groupBy('date')
            ->orderBy('date')
            ->pluck('cnt', 'date')
            ->toArray();

        $usersTrendDates  = [];
        $usersTrendValues = [];
        for ($i = 29; $i >= 0; $i--) {
            $d = now()->subDays($i)->format('Y-m-d');
            $usersTrendDates[]  = now()->subDays($i)->format('M d');
            $usersTrendValues[] = $rawUsersTrend[$d] ?? 0;
        }

        // ── Recent activity ──────────────────────────────────────────────────
        $recentJobs = JobPost::with('company')
            ->latest()->limit(5)
            ->get(['id', 'job_title', 'company_id', 'is_active', 'created_at', 'deadline']);

        $recentUsers = User::with('role')
            ->latest()->limit(5)
            ->get(['id', 'first_name', 'last_name', 'email', 'created_at', 'is_active', 'role_id']);

        $recentTransactions = Transaction::latest()->limit(5)
            ->get(['id', 'reference', 'amount', 'currency', 'status', 'payment_gateway', 'customer_name', 'created_at']);

        // ── Blog stats ───────────────────────────────────────────────────────
        $blogStats = [
            'total'       => Blog::withTrashed()->count(),
            'published'   => Blog::where('is_published', true)->count(),
            'draft'       => Blog::where('is_published', false)->count(),
            'total_views' => (int) Blog::sum('view_count'),
        ];

        // ── Notification summary ─────────────────────────────────────────────
        $notificationStats = [
            'total'  => DB::table('notifications')->whereNull('deleted_at')->count(),
            'unread' => DB::table('notifications')->where('status', 'unread')->whereNull('deleted_at')->count(),
            'urgent' => DB::table('notifications')->where('priority', 'urgent')
                          ->whereNotIn('status', ['resolved', 'archived'])->whereNull('deleted_at')->count(),
        ];

        return view('analytics.dashboard', compact(
            'usersByRole', 'totalUsers',
            'jobStats', 'companyStats', 'revenueStats',
            'newUsers', 'newJobs', 'engagementStats',
            'jobsTrendDates', 'jobsTrendValues',
            'usersTrendDates', 'usersTrendValues',
            'recentJobs', 'recentUsers', 'recentTransactions',
            'blogStats', 'notificationStats'
        ));
    }

    public function data(Request $request)
    {
        return response()->json(['success' => true]);
    }
}
