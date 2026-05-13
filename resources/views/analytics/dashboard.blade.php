@extends('layouts.app')
@section('title', 'Analytics Overview – Stardena Works')

@section('app-content')
<div class="body-wrapper">
<div class="container-fluid">

    {{-- Breadcrumb --}}
    <div class="font-weight-medium shadow-none position-relative overflow-hidden mb-7">
        <div class="card-body px-0">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <h4 class="font-weight-medium mb-0">Analytics Overview</h4>
                    <nav aria-label="breadcrumb">
                        <ol class="breadcrumb">
                            <li class="breadcrumb-item"><a class="text-muted text-decoration-none" href="{{ route('dashboard') }}">Home</a></li>
                            <li class="breadcrumb-item text-muted" aria-current="page">Analytics</li>
                        </ol>
                    </nav>
                </div>
                <div class="d-flex gap-2">
                    <a href="{{ route('analytics.jobs') }}" class="btn btn-sm btn-outline-primary">Jobs</a>
                    <a href="{{ route('analytics.users') }}" class="btn btn-sm btn-outline-primary">Users</a>
                    @if(auth()->user()->isAdmin())
                        <a href="{{ route('analytics.revenue') }}" class="btn btn-sm btn-outline-success">Revenue</a>
                    @endif
                </div>
            </div>
        </div>
    </div>

    {{-- ── Row 1: Core KPI Cards ───────────────────────────────────────────── --}}
    <div class="row g-4 mb-4">
        {{-- Total Jobs --}}
        <div class="col-lg-3 col-md-6">
            <div class="card">
                <div class="card-body">
                    <div class="hstack gap-6">
                        <div class="bg-primary-subtle round-48 rounded-circle d-flex align-items-center justify-content-center" style="width:48px;height:48px;">
                            <iconify-icon icon="solar:briefcase-linear" class="fs-7 text-primary"></iconify-icon>
                        </div>
                        <div>
                            <h3 class="mb-0 fw-bold">{{ number_format($jobStats['total']) }}</h3>
                            <p class="mb-0 text-muted fs-3">Total Jobs</p>
                        </div>
                    </div>
                    <div class="mt-3 d-flex gap-3">
                        <span class="badge bg-success-subtle text-success">{{ $jobStats['active'] }} active</span>
                        <span class="badge bg-warning-subtle text-warning">{{ $jobStats['featured'] }} featured</span>
                    </div>
                </div>
            </div>
        </div>
        {{-- Total Users --}}
        <div class="col-lg-3 col-md-6">
            <div class="card">
                <div class="card-body">
                    <div class="hstack gap-6">
                        <div class="bg-secondary-subtle round-48 rounded-circle d-flex align-items-center justify-content-center" style="width:48px;height:48px;">
                            <iconify-icon icon="solar:users-group-rounded-linear" class="fs-7 text-secondary"></iconify-icon>
                        </div>
                        <div>
                            <h3 class="mb-0 fw-bold">{{ number_format($totalUsers) }}</h3>
                            <p class="mb-0 text-muted fs-3">Total Users</p>
                        </div>
                    </div>
                    <div class="mt-3 d-flex gap-3">
                        <span class="badge bg-primary-subtle text-primary">{{ $newUsers['this_month'] }} this month</span>
                        <span class="badge bg-info-subtle text-info">{{ $newUsers['today'] }} today</span>
                    </div>
                </div>
            </div>
        </div>
        {{-- Total Companies --}}
        <div class="col-lg-3 col-md-6">
            <div class="card">
                <div class="card-body">
                    <div class="hstack gap-6">
                        <div class="bg-success-subtle round-48 rounded-circle d-flex align-items-center justify-content-center" style="width:48px;height:48px;">
                            <iconify-icon icon="solar:buildings-2-linear" class="fs-7 text-success"></iconify-icon>
                        </div>
                        <div>
                            <h3 class="mb-0 fw-bold">{{ number_format($companyStats['total']) }}</h3>
                            <p class="mb-0 text-muted fs-3">Total Companies</p>
                        </div>
                    </div>
                    <div class="mt-3 d-flex gap-3">
                        <span class="badge bg-success-subtle text-success">{{ $companyStats['verified'] }} verified</span>
                    </div>
                </div>
            </div>
        </div>
        {{-- Total Revenue --}}
        @if(auth()->user()->isAdmin())
        <div class="col-lg-3 col-md-6">
            <div class="card">
                <div class="card-body">
                    <div class="hstack gap-6">
                        <div class="bg-warning-subtle round-48 rounded-circle d-flex align-items-center justify-content-center" style="width:48px;height:48px;">
                            <iconify-icon icon="solar:wallet-money-linear" class="fs-7 text-warning"></iconify-icon>
                        </div>
                        <div>
                            <h3 class="mb-0 fw-bold">UGX {{ number_format($revenueStats['total']) }}</h3>
                            <p class="mb-0 text-muted fs-3">Total Revenue</p>
                        </div>
                    </div>
                    <div class="mt-3 d-flex gap-3">
                        <span class="badge bg-warning-subtle text-warning">UGX {{ number_format($revenueStats['this_month']) }} this month</span>
                    </div>
                </div>
            </div>
        </div>
        @else
        <div class="col-lg-3 col-md-6">
            <div class="card">
                <div class="card-body">
                    <div class="hstack gap-6">
                        <div class="bg-info-subtle round-48 rounded-circle d-flex align-items-center justify-content-center" style="width:48px;height:48px;">
                            <iconify-icon icon="solar:document-text-linear" class="fs-7 text-info"></iconify-icon>
                        </div>
                        <div>
                            <h3 class="mb-0 fw-bold">{{ number_format($blogStats['total']) }}</h3>
                            <p class="mb-0 text-muted fs-3">Blog Posts</p>
                        </div>
                    </div>
                    <div class="mt-3 d-flex gap-3">
                        <span class="badge bg-success-subtle text-success">{{ $blogStats['published'] }} published</span>
                    </div>
                </div>
            </div>
        </div>
        @endif
    </div>

    {{-- ── Row 2: Secondary KPI Tiles ─────────────────────────────────────── --}}
    <div class="row g-3 mb-4">
        @foreach([
            ['label'=>'Active Jobs','value'=>$jobStats['active'],'icon'=>'solar:check-circle-linear','color'=>'success'],
            ['label'=>'New Jobs Today','value'=>$newJobs['today'],'icon'=>'solar:add-circle-linear','color'=>'primary'],
            ['label'=>'New Users Today','value'=>$newUsers['today'],'icon'=>'solar:user-plus-rounded-linear','color'=>'info'],
            ['label'=>'Total Views','value'=>number_format($engagementStats['total_views']),'icon'=>'solar:eye-linear','color'=>'secondary'],
            ['label'=>'Total Applications','value'=>number_format($engagementStats['total_applications']),'icon'=>'solar:inbox-linear','color'=>'warning'],
            ['label'=>'Notifications','value'=>$notificationStats['unread'].' unread','icon'=>'solar:bell-linear','color'=>($notificationStats['urgent']>0?'danger':'primary')],
        ] as $tile)
        <div class="col-lg-2 col-md-4 col-6">
            <div class="card text-center">
                <div class="card-body py-3">
                    <iconify-icon icon="{{ $tile['icon'] }}" class="fs-6 text-{{ $tile['color'] }} mb-1"></iconify-icon>
                    <h5 class="mb-0 fw-bold">{{ $tile['value'] }}</h5>
                    <p class="mb-0 text-muted fs-2">{{ $tile['label'] }}</p>
                </div>
            </div>
        </div>
        @endforeach
    </div>

    {{-- ── Row 3: Trend Charts ──────────────────────────────────────────────── --}}
    <div class="row g-4 mb-4">
        <div class="col-lg-8">
            <div class="card h-100">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <div>
                            <h4 class="card-title mb-0">Platform Trends</h4>
                            <p class="card-subtitle text-muted">Jobs posted & users registered (last 30 days)</p>
                        </div>
                    </div>
                    <div id="chart-platform-trends"></div>
                </div>
            </div>
        </div>
        <div class="col-lg-4">
            <div class="card h-100">
                <div class="card-body">
                    <h4 class="card-title mb-0">User Roles</h4>
                    <p class="card-subtitle text-muted">Distribution across roles</p>
                    <div id="chart-user-roles" class="mt-2"></div>
                    <div class="mt-3">
                        @foreach($usersByRole as $role => $count)
                        <div class="d-flex justify-content-between align-items-center mb-1">
                            <span class="fs-3 text-capitalize">{{ str_replace('_', ' ', $role) }}</span>
                            <span class="badge bg-primary-subtle text-primary">{{ $count }}</span>
                        </div>
                        @endforeach
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- ── Row 4: Job Status + Engagement ──────────────────────────────────── --}}
    <div class="row g-4 mb-4">
        <div class="col-lg-4">
            <div class="card h-100">
                <div class="card-body">
                    <h4 class="card-title mb-0">Job Status</h4>
                    <p class="card-subtitle text-muted">Current distribution</p>
                    <div id="chart-job-status" class="mt-2"></div>
                    <div class="row text-center mt-2">
                        <div class="col-4">
                            <h5 class="mb-0 text-success">{{ $jobStats['active'] }}</h5>
                            <small class="text-muted">Active</small>
                        </div>
                        <div class="col-4">
                            <h5 class="mb-0 text-warning">{{ $jobStats['featured'] }}</h5>
                            <small class="text-muted">Featured</small>
                        </div>
                        <div class="col-4">
                            <h5 class="mb-0 text-danger">{{ $jobStats['expired'] }}</h5>
                            <small class="text-muted">Expired</small>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-lg-4">
            <div class="card h-100">
                <div class="card-body">
                    <h4 class="card-title mb-0">Engagement</h4>
                    <p class="card-subtitle text-muted">Views, clicks & applications</p>
                    <div id="chart-engagement" class="mt-2"></div>
                </div>
            </div>
        </div>
        <div class="col-lg-4">
            <div class="card h-100">
                <div class="card-body">
                    <h4 class="card-title mb-0">Quick Stats</h4>
                    <p class="card-subtitle text-muted">Platform at a glance</p>
                    <div class="mt-3">
                        @php
                        $quickStats = [
                            ['label'=>'Verified Jobs',   'value'=>$jobStats['verified'],  'pct'=> $jobStats['total']>0 ? round($jobStats['verified']/$jobStats['total']*100) : 0, 'color'=>'success'],
                            ['label'=>'Verified Co.',    'value'=>$companyStats['verified'],'pct'=> $companyStats['total']>0 ? round($companyStats['verified']/$companyStats['total']*100) : 0, 'color'=>'info'],
                            ['label'=>'Urgent Jobs',     'value'=>$jobStats['urgent'],    'pct'=> $jobStats['total']>0 ? round($jobStats['urgent']/$jobStats['total']*100) : 0, 'color'=>'danger'],
                        ];
                        @endphp
                        @foreach($quickStats as $stat)
                        <div class="mb-3">
                            <div class="d-flex justify-content-between mb-1">
                                <span class="fs-3">{{ $stat['label'] }}</span>
                                <span class="fs-3 fw-semibold">{{ $stat['value'] }} ({{ $stat['pct'] }}%)</span>
                            </div>
                            <div class="progress" style="height:6px;">
                                <div class="progress-bar bg-{{ $stat['color'] }}" style="width:{{ $stat['pct'] }}%"></div>
                            </div>
                        </div>
                        @endforeach
                        @if(auth()->user()->isAdmin())
                        <div class="border-top pt-3 mt-2">
                            <div class="d-flex justify-content-between">
                                <span class="fs-3 text-muted">TX Success Rate</span>
                                <span class="badge bg-success-subtle text-success">
                                    {{ $revenueStats['total_count'] > 0 ? round($revenueStats['successful']/$revenueStats['total_count']*100,1) : 0 }}%
                                </span>
                            </div>
                            <div class="d-flex justify-content-between mt-1">
                                <span class="fs-3 text-muted">Pending TX</span>
                                <span class="badge bg-warning-subtle text-warning">{{ $revenueStats['pending'] }}</span>
                            </div>
                        </div>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- ── Row 5: Recent Activity ───────────────────────────────────────────── --}}
    <div class="row g-4">
        {{-- Recent Jobs --}}
        <div class="col-lg-4">
            <div class="card h-100">
                <div class="card-body pb-0">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <h4 class="card-title mb-0">Recent Jobs</h4>
                        <a href="{{ route('analytics.jobs') }}" class="btn btn-sm btn-outline-primary">View All</a>
                    </div>
                </div>
                <ul class="list-group list-group-flush">
                    @forelse($recentJobs as $job)
                    <li class="list-group-item px-4 py-3">
                        <div class="d-flex justify-content-between align-items-start">
                            <div class="text-truncate" style="max-width:200px;">
                                <p class="mb-0 fw-medium fs-3 text-truncate">{{ $job->job_title }}</p>
                                <small class="text-muted">{{ $job->company?->name ?? '–' }}</small>
                            </div>
                            <span class="badge {{ $job->is_active ? 'bg-success-subtle text-success' : 'bg-danger-subtle text-danger' }} ms-2">
                                {{ $job->is_active ? 'Active' : 'Inactive' }}
                            </span>
                        </div>
                        <small class="text-muted">{{ $job->created_at->diffForHumans() }}</small>
                    </li>
                    @empty
                    <li class="list-group-item text-center text-muted py-4">No jobs yet</li>
                    @endforelse
                </ul>
            </div>
        </div>
        {{-- Recent Users --}}
        <div class="col-lg-4">
            <div class="card h-100">
                <div class="card-body pb-0">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <h4 class="card-title mb-0">Recent Users</h4>
                        <a href="{{ route('analytics.users') }}" class="btn btn-sm btn-outline-primary">View All</a>
                    </div>
                </div>
                <ul class="list-group list-group-flush">
                    @forelse($recentUsers as $user)
                    <li class="list-group-item px-4 py-3">
                        <div class="d-flex justify-content-between align-items-start">
                            <div>
                                <p class="mb-0 fw-medium fs-3">{{ $user->first_name }} {{ $user->last_name }}</p>
                                <small class="text-muted">{{ $user->email }}</small>
                            </div>
                            <span class="badge bg-primary-subtle text-primary ms-2 text-capitalize">
                                {{ str_replace('_',' ',$user->role?->name ?? '–') }}
                            </span>
                        </div>
                        <small class="text-muted">{{ $user->created_at->diffForHumans() }}</small>
                    </li>
                    @empty
                    <li class="list-group-item text-center text-muted py-4">No users yet</li>
                    @endforelse
                </ul>
            </div>
        </div>
        {{-- Recent Transactions --}}
        @if(auth()->user()->isAdmin())
        <div class="col-lg-4">
            <div class="card h-100">
                <div class="card-body pb-0">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <h4 class="card-title mb-0">Recent Transactions</h4>
                        <a href="{{ route('analytics.revenue') }}" class="btn btn-sm btn-outline-primary">View All</a>
                    </div>
                </div>
                <ul class="list-group list-group-flush">
                    @forelse($recentTransactions as $tx)
                    <li class="list-group-item px-4 py-3">
                        <div class="d-flex justify-content-between align-items-start">
                            <div>
                                <p class="mb-0 fw-medium fs-3">{{ $tx->customer_name ?? $tx->reference }}</p>
                                <small class="text-muted">{{ $tx->currency }} {{ number_format($tx->amount) }}</small>
                            </div>
                            @php
                            $txColors = ['successful'=>'success','pending'=>'warning','failed'=>'danger','processing'=>'info','refunded'=>'secondary','disputed'=>'dark','cancelled'=>'light'];
                            @endphp
                            <span class="badge bg-{{ ($txColors[$tx->status] ?? 'secondary') }}-subtle text-{{ ($txColors[$tx->status] ?? 'secondary') }} ms-2">
                                {{ ucfirst($tx->status) }}
                            </span>
                        </div>
                        <small class="text-muted">{{ $tx->created_at->diffForHumans() }}</small>
                    </li>
                    @empty
                    <li class="list-group-item text-center text-muted py-4">No transactions yet</li>
                    @endforelse
                </ul>
            </div>
        </div>
        @else
        <div class="col-lg-4">
            <div class="card h-100">
                <div class="card-body">
                    <h4 class="card-title mb-0">Blogs</h4>
                    <p class="card-subtitle text-muted">Content overview</p>
                    <div class="row text-center mt-3">
                        <div class="col-6">
                            <h3 class="mb-0 text-primary">{{ $blogStats['total'] }}</h3>
                            <small class="text-muted">Total</small>
                        </div>
                        <div class="col-6">
                            <h3 class="mb-0 text-success">{{ $blogStats['published'] }}</h3>
                            <small class="text-muted">Published</small>
                        </div>
                        <div class="col-6 mt-3">
                            <h3 class="mb-0 text-warning">{{ $blogStats['draft'] }}</h3>
                            <small class="text-muted">Drafts</small>
                        </div>
                        <div class="col-6 mt-3">
                            <h3 class="mb-0 text-info">{{ number_format($blogStats['total_views']) }}</h3>
                            <small class="text-muted">Views</small>
                        </div>
                    </div>
                    <div class="mt-3">
                        <a href="{{ route('analytics.seo') }}" class="btn btn-outline-primary btn-sm w-100">View SEO Analytics</a>
                    </div>
                </div>
            </div>
        </div>
        @endif
    </div>

</div>
</div>
@endsection

@push('rich-editor-scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {

    var isDark = document.documentElement.getAttribute('data-bs-theme') === 'dark';
    var gridColor = isDark ? 'rgba(255,255,255,0.06)' : 'rgba(0,0,0,0.06)';
    var textColor = isDark ? '#adb5bd' : '#6c757d';

    // ── Platform Trends ─────────────────────────────────────────────────────
    var trendDates  = @json($jobsTrendDates);
    var jobsValues  = @json($jobsTrendValues);
    var usersValues = @json($usersTrendValues);

    new ApexCharts(document.querySelector('#chart-platform-trends'), {
        series: [
            { name: 'Jobs Posted', data: jobsValues },
            { name: 'New Users',   data: usersValues },
        ],
        chart: { type: 'area', height: 300, toolbar: { show: false }, fontFamily: 'inherit' },
        colors: ['#5d87ff', '#49beff'],
        fill: { type: 'gradient', gradient: { opacityFrom: 0.4, opacityTo: 0.05 } },
        stroke: { curve: 'smooth', width: 2 },
        xaxis: { categories: trendDates, labels: { style: { colors: textColor } },
                 axisBorder: { show: false }, axisTicks: { show: false }, tickAmount: 6 },
        yaxis: { labels: { style: { colors: textColor } } },
        grid: { borderColor: gridColor, strokeDashArray: 4 },
        tooltip: { theme: isDark ? 'dark' : 'light' },
        legend: { position: 'top', labels: { colors: textColor } },
        dataLabels: { enabled: false },
    }).render();

    // ── User Roles Donut ─────────────────────────────────────────────────────
    var roleLabels = @json(array_keys($usersByRole));
    var roleValues = @json(array_values($usersByRole));

    roleLabels = roleLabels.map(function(l){ return l.replace(/_/g,' ').replace(/\b\w/g,function(c){ return c.toUpperCase(); }); });

    new ApexCharts(document.querySelector('#chart-user-roles'), {
        series: roleValues,
        labels: roleLabels,
        chart: { type: 'donut', height: 180, toolbar: { show: false }, fontFamily: 'inherit' },
        colors: ['#5d87ff','#49beff','#13deb9','#ffae1f','#fa896b'],
        legend: { show: false },
        plotOptions: { pie: { donut: { size: '70%' } } },
        dataLabels: { enabled: false },
        tooltip: { theme: isDark ? 'dark' : 'light' },
    }).render();

    // ── Job Status Donut ────────────────────────────────────────────────────
    new ApexCharts(document.querySelector('#chart-job-status'), {
        series: [{{ $jobStats['active'] }}, {{ $jobStats['expired'] }}, {{ $jobStats['inactive'] }}, {{ $jobStats['featured'] }}, {{ $jobStats['urgent'] }}],
        labels: ['Active', 'Expired', 'Inactive', 'Featured', 'Urgent'],
        chart: { type: 'donut', height: 200, toolbar: { show: false }, fontFamily: 'inherit' },
        colors: ['#13deb9','#fa896b','#adb5bd','#ffae1f','#ff6692'],
        legend: { show: false },
        plotOptions: { pie: { donut: { size: '70%' } } },
        dataLabels: { enabled: false },
        tooltip: { theme: isDark ? 'dark' : 'light' },
    }).render();

    // ── Engagement Radial ───────────────────────────────────────────────────
    var engagementData = [
        { name: 'Views',        value: {{ $engagementStats['total_views'] }} },
        { name: 'Clicks',       value: {{ $engagementStats['total_clicks'] }} },
        { name: 'Applications', value: {{ $engagementStats['total_applications'] }} },
    ];
    var maxVal = Math.max.apply(null, engagementData.map(function(e){ return e.value; })) || 1;
    new ApexCharts(document.querySelector('#chart-engagement'), {
        series: engagementData.map(function(e){ return Math.round(e.value / maxVal * 100); }),
        labels: engagementData.map(function(e){ return e.name; }),
        chart: { type: 'radialBar', height: 220, fontFamily: 'inherit' },
        colors: ['#5d87ff','#49beff','#13deb9'],
        plotOptions: { radialBar: { hollow: { size: '40%' }, dataLabels: { name: { fontSize: '11px' }, value: { fontSize: '10px' } } } },
        legend: { show: true, position: 'bottom', labels: { colors: textColor } },
        tooltip: { enabled: false },
    }).render();

});
</script>
@endpush
