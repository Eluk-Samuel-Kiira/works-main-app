@extends('layouts.app')
@section('title', 'User Analytics – Stardena Works')

@section('app-content')
<div class="body-wrapper">
<div class="container-fluid">

    <div class="font-weight-medium shadow-none position-relative overflow-hidden mb-4">
        <div class="card-body px-0">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <h4 class="font-weight-medium mb-0">User Analytics</h4>
                    <nav aria-label="breadcrumb"><ol class="breadcrumb">
                        <li class="breadcrumb-item"><a class="text-muted text-decoration-none" href="{{ route('dashboard') }}">Home</a></li>
                        <li class="breadcrumb-item"><a class="text-muted text-decoration-none" href="{{ route('analytics.dashboard') }}">Analytics</a></li>
                        <li class="breadcrumb-item text-muted" aria-current="page">Users</li>
                    </ol></nav>
                </div>
                <a href="{{ route('analytics.export.users', request()->query()) }}" class="btn btn-sm btn-outline-secondary d-flex align-items-center gap-1">
                    <iconify-icon icon="solar:download-linear"></iconify-icon> Export CSV
                </a>
            </div>
        </div>
    </div>

    @include('analytics.partials.date-range-filter')

    {{-- KPI Row --}}
    <div class="row g-3 mb-4">
        @foreach([
            ['label'=>'Total Users',      'value'=>number_format($kpis['total']),          'icon'=>'solar:users-group-rounded-linear','color'=>'primary'],
            ['label'=>'Active Users',     'value'=>number_format($kpis['active']),         'icon'=>'solar:user-check-rounded-linear', 'color'=>'success'],
            ['label'=>'Inactive',          'value'=>number_format($kpis['inactive']),       'icon'=>'solar:user-cross-rounded-linear', 'color'=>'danger'],
            ['label'=>'Email Verified',   'value'=>$kpis['verification_rate'].'%',         'icon'=>'solar:letter-opened-linear',     'color'=>'info'],
            ['label'=>'In Period',         'value'=>number_format($kpis['in_period']),      'icon'=>'solar:user-plus-rounded-linear', 'color'=>'secondary'],
            ['label'=>'Active 7d',         'value'=>number_format($kpis['active_7d']),      'icon'=>'solar:calendar-linear',          'color'=>'warning'],
            ['label'=>'Active 30d',        'value'=>number_format($kpis['active_30d']),     'icon'=>'solar:calendar-date-linear',     'color'=>'primary'],
            ['label'=>'Never Logged In',  'value'=>number_format($kpis['never_logged_in']),'icon'=>'solar:user-block-rounded-linear','color'=>'danger'],
        ] as $kpi)
        <div class="col-lg col-md-3 col-6">
            <div class="card text-center h-100">
                <div class="card-body py-3">
                    <iconify-icon icon="{{ $kpi['icon'] }}" class="fs-6 text-{{ $kpi['color'] }} mb-1"></iconify-icon>
                    <h5 class="mb-0 fw-bold">{{ $kpi['value'] }}</h5>
                    <p class="mb-0 text-muted fs-2">{{ $kpi['label'] }}</p>
                </div>
            </div>
        </div>
        @endforeach
    </div>

    {{-- Registration Trend + Role Distribution --}}
    <div class="row g-4 mb-4">
        <div class="col-lg-8">
            <div class="card h-100">
                <div class="card-body">
                    <h4 class="card-title mb-0">Registration Trend</h4>
                    <p class="card-subtitle text-muted">New users in selected period</p>
                    <div id="chart-reg-trend" class="mt-3"></div>
                </div>
            </div>
        </div>
        <div class="col-lg-4">
            <div class="card h-100">
                <div class="card-body">
                    <h4 class="card-title mb-0">Users by Role</h4>
                    <p class="card-subtitle text-muted">Role distribution</p>
                    <div id="chart-roles-pie" class="mt-2"></div>
                    <div class="mt-2">
                        @foreach($byRole as $role => $count)
                        <div class="d-flex justify-content-between mb-1">
                            <span class="fs-3 text-capitalize">{{ str_replace('_',' ',$role) }}</span>
                            <span class="badge bg-primary-subtle text-primary">{{ number_format($count) }}</span>
                        </div>
                        @endforeach
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- Employer vs Job Seeker trend + Login Activity --}}
    <div class="row g-4 mb-4">
        <div class="col-lg-6">
            <div class="card h-100">
                <div class="card-body">
                    <h4 class="card-title mb-0">Employer vs Job Seeker Registrations</h4>
                    <p class="card-subtitle text-muted">Last 30 days by role</p>
                    <div id="chart-roles-trend" class="mt-3"></div>
                </div>
            </div>
        </div>
        <div class="col-lg-6">
            <div class="card h-100">
                <div class="card-body">
                    <h4 class="card-title mb-0">Login Activity</h4>
                    <p class="card-subtitle text-muted">Daily logins over last 90 days</p>
                    <div id="chart-login-activity" class="mt-3"></div>
                </div>
            </div>
        </div>
    </div>

    {{-- Magic Link Usage + Country Distribution --}}
    <div class="row g-4 mb-4">
        <div class="col-lg-6">
            <div class="card h-100">
                <div class="card-body">
                    <h4 class="card-title mb-0">Magic Link Usage</h4>
                    <p class="card-subtitle text-muted">Sent vs used (last 30 days)</p>
                    <div id="chart-magic-link" class="mt-3"></div>
                </div>
            </div>
        </div>
        <div class="col-lg-6">
            <div class="card h-100">
                <div class="card-body">
                    <h4 class="card-title mb-0">Users by Country</h4>
                    <p class="card-subtitle text-muted">Top country codes</p>
                    <div id="chart-by-country" class="mt-3"></div>
                </div>
            </div>
        </div>
    </div>

    {{-- Active / Inactive + Verification --}}
    <div class="row g-4 mb-4">
        <div class="col-lg-4">
            <div class="card h-100">
                <div class="card-body">
                    <h4 class="card-title mb-0">Account Status</h4>
                    <div id="chart-account-status" class="mt-3"></div>
                </div>
            </div>
        </div>
        <div class="col-lg-4">
            <div class="card h-100">
                <div class="card-body">
                    <h4 class="card-title mb-0">Email Verification</h4>
                    <div id="chart-email-verification" class="mt-3"></div>
                    <div class="text-center mt-2">
                        <h3 class="mb-0 text-success">{{ $kpis['verification_rate'] }}%</h3>
                        <p class="text-muted fs-3 mb-0">Verification Rate</p>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-lg-4">
            <div class="card h-100">
                <div class="card-body">
                    <h4 class="card-title mb-0">Login Stats Summary</h4>
                    <div class="mt-3">
                        @foreach([
                            ['label'=>'Active in last 7 days',  'value'=> $kpis['active_7d'],         'color'=>'success'],
                            ['label'=>'Active in last 30 days', 'value'=> $kpis['active_30d'],        'color'=>'primary'],
                            ['label'=>'Never logged in',        'value'=> $kpis['never_logged_in'],   'color'=>'danger'],
                            ['label'=>'Deleted accounts',       'value'=> $kpis['deleted'],            'color'=>'secondary'],
                        ] as $stat)
                        <div class="d-flex justify-content-between align-items-center mb-3">
                            <span class="fs-3 text-muted">{{ $stat['label'] }}</span>
                            <span class="badge bg-{{ $stat['color'] }}-subtle text-{{ $stat['color'] }} fw-semibold">{{ number_format($stat['value']) }}</span>
                        </div>
                        @endforeach
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- Recently Active + Churned Users tables --}}
    <div class="row g-4">
        <div class="col-lg-6">
            <div class="card">
                <div class="card-body">
                    <h4 class="card-title mb-0">Recently Active Users</h4>
                    <p class="card-subtitle text-muted">Last 10 logins</p>
                    <div class="table-responsive mt-3">
                        <table class="table table-sm table-hover">
                            <thead><tr><th>Name</th><th>Role</th><th>Country</th><th>Last Login</th></tr></thead>
                            <tbody>
                                @forelse($recentlyActive as $u)
                                <tr>
                                    <td>
                                        <p class="mb-0 fw-medium fs-3">{{ $u->first_name }} {{ $u->last_name }}</p>
                                        <small class="text-muted">{{ $u->email }}</small>
                                    </td>
                                    <td><span class="badge bg-primary-subtle text-primary text-capitalize">{{ str_replace('_',' ',$u->role?->name ?? '–') }}</span></td>
                                    <td class="text-muted">{{ $u->country_code }}</td>
                                    <td class="text-muted fs-3">{{ $u->last_login_at?->diffForHumans() }}</td>
                                </tr>
                                @empty
                                <tr><td colspan="4" class="text-center text-muted">No login data</td></tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-lg-6">
            <div class="card">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center mb-1">
                        <h4 class="card-title mb-0">Churned Users</h4>
                        <span class="badge bg-danger-subtle text-danger">Registered >30d, never logged in</span>
                    </div>
                    <p class="card-subtitle text-muted mb-3">May need re-engagement</p>
                    <div class="table-responsive">
                        <table class="table table-sm table-hover">
                            <thead><tr><th>Name</th><th>Email</th><th>Status</th><th>Registered</th></tr></thead>
                            <tbody>
                                @forelse($churnedUsers as $u)
                                <tr>
                                    <td class="fw-medium fs-3">{{ $u->first_name }} {{ $u->last_name }}</td>
                                    <td class="text-muted fs-3">{{ $u->email }}</td>
                                    <td><span class="badge {{ $u->is_active ? 'bg-success-subtle text-success' : 'bg-danger-subtle text-danger' }}">{{ $u->is_active ? 'Active' : 'Inactive' }}</span></td>
                                    <td class="text-muted fs-3">{{ $u->created_at->format('M d, Y') }}</td>
                                </tr>
                                @empty
                                <tr><td colspan="4" class="text-center text-muted">No churned users found</td></tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
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

    var trendDates  = @json($trendDates);
    var trendValues = @json($trendValues);

    new ApexCharts(document.querySelector('#chart-reg-trend'), {
        series: [{ name: 'New Users', data: trendValues }],
        chart: { type: 'area', height: 270, toolbar: { show: false }, fontFamily: 'inherit' },
        colors: ['#13deb9'],
        fill: { type: 'gradient', gradient: { opacityFrom: 0.4, opacityTo: 0.05 } },
        stroke: { curve: 'smooth', width: 2 },
        xaxis: { categories: trendDates, labels: { style: { colors: textColor } }, tickAmount: 7, axisBorder: { show: false } },
        yaxis: { labels: { style: { colors: textColor } } },
        grid: { borderColor: gridColor, strokeDashArray: 4 },
        dataLabels: { enabled: false },
        tooltip: { theme: isDark ? 'dark' : 'light' },
    }).render();

    var roleLabels = @json(array_keys($byRole));
    var roleValues = @json(array_values($byRole));
    roleLabels = roleLabels.map(function(l){ return l.replace(/_/g,' ').replace(/\b\w/g,function(c){ return c.toUpperCase(); }); });

    new ApexCharts(document.querySelector('#chart-roles-pie'), {
        series: roleValues, labels: roleLabels,
        chart: { type: 'donut', height: 180, fontFamily: 'inherit' },
        colors: ['#5d87ff','#49beff','#13deb9','#ffae1f','#fa896b'],
        legend: { show: false }, plotOptions: { pie: { donut: { size: '65%' } } },
        dataLabels: { enabled: false }, tooltip: { theme: isDark ? 'dark' : 'light' },
    }).render();

    var rTrendDates = @json($rolesTrendDates);
    var empValues   = @json($employerValues);
    var jskValues   = @json($jobSeekerValues);

    new ApexCharts(document.querySelector('#chart-roles-trend'), {
        series: [{ name: 'Employers', data: empValues }, { name: 'Job Seekers', data: jskValues }],
        chart: { type: 'line', height: 250, toolbar: { show: false }, fontFamily: 'inherit' },
        colors: ['#5d87ff','#13deb9'],
        stroke: { curve: 'smooth', width: 2 },
        xaxis: { categories: rTrendDates, labels: { style: { colors: textColor } }, tickAmount: 7, axisBorder: { show: false } },
        yaxis: { labels: { style: { colors: textColor } } },
        grid: { borderColor: gridColor, strokeDashArray: 4 },
        dataLabels: { enabled: false },
        legend: { labels: { colors: textColor } },
        tooltip: { theme: isDark ? 'dark' : 'light' },
    }).render();

    var loginDates  = @json($loginDates);
    var loginValues = @json($loginValues);

    new ApexCharts(document.querySelector('#chart-login-activity'), {
        series: [{ name: 'Logins', data: loginValues }],
        chart: { type: 'bar', height: 250, toolbar: { show: false }, fontFamily: 'inherit' },
        colors: ['#49beff'],
        xaxis: { categories: loginDates, labels: { show: false }, axisBorder: { show: false } },
        yaxis: { labels: { style: { colors: textColor } } },
        grid: { borderColor: gridColor, strokeDashArray: 4 },
        dataLabels: { enabled: false },
        plotOptions: { bar: { columnWidth: '80%' } },
        tooltip: { theme: isDark ? 'dark' : 'light', x: { formatter: function(v,o){ return loginDates[o.dataPointIndex]||''; } } },
    }).render();

    var mlDates = @json($mlDates);
    var mlSent  = @json($mlSent);
    var mlUsed  = @json($mlUsed);

    new ApexCharts(document.querySelector('#chart-magic-link'), {
        series: [{ name: 'Sent', data: mlSent }, { name: 'Used', data: mlUsed }],
        chart: { type: 'bar', height: 250, toolbar: { show: false }, fontFamily: 'inherit' },
        colors: ['#5d87ff','#13deb9'],
        xaxis: { categories: mlDates, labels: { style: { colors: textColor } }, tickAmount: 7, axisBorder: { show: false } },
        yaxis: { labels: { style: { colors: textColor } } },
        grid: { borderColor: gridColor, strokeDashArray: 4 },
        dataLabels: { enabled: false },
        plotOptions: { bar: { borderRadius: 3 } },
        legend: { labels: { colors: textColor } },
        tooltip: { theme: isDark ? 'dark' : 'light' },
    }).render();

    var countryLabels = @json(array_keys($byCountry));
    var countryValues = @json(array_values($byCountry));

    new ApexCharts(document.querySelector('#chart-by-country'), {
        series: [{ name: 'Users', data: countryValues }],
        chart: { type: 'bar', height: 250, toolbar: { show: false }, fontFamily: 'inherit' },
        colors: ['#ffae1f'],
        plotOptions: { bar: { horizontal: true, borderRadius: 4 } },
        xaxis: { categories: countryLabels, labels: { style: { colors: textColor } } },
        yaxis: { labels: { style: { colors: textColor } } },
        grid: { borderColor: gridColor, strokeDashArray: 4 },
        dataLabels: { enabled: false },
        tooltip: { theme: isDark ? 'dark' : 'light' },
    }).render();

    new ApexCharts(document.querySelector('#chart-account-status'), {
        series: [{{ $kpis['active'] }}, {{ $kpis['inactive'] }}],
        labels: ['Active', 'Inactive'],
        chart: { type: 'donut', height: 220, fontFamily: 'inherit' },
        colors: ['#13deb9','#fa896b'],
        legend: { position: 'bottom', labels: { colors: textColor } },
        dataLabels: { enabled: true, style: { colors: ['#fff'] } },
        tooltip: { theme: isDark ? 'dark' : 'light' },
    }).render();

    new ApexCharts(document.querySelector('#chart-email-verification'), {
        series: [{{ $kpis['verified'] }}, {{ $kpis['unverified'] }}],
        labels: ['Verified', 'Unverified'],
        chart: { type: 'donut', height: 160, fontFamily: 'inherit' },
        colors: ['#5d87ff','#e8eaec'],
        legend: { show: false },
        dataLabels: { enabled: false },
        plotOptions: { pie: { donut: { size: '80%', labels: { show: true, total: { show: true, label: 'Rate', formatter: function(){ return '{{ $kpis['verification_rate'] }}%'; } } } } } },
        tooltip: { theme: isDark ? 'dark' : 'light' },
    }).render();

});
</script>
@endpush
