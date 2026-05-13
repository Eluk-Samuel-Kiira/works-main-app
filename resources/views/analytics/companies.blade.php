@extends('layouts.app')
@section('title', 'Company Analytics – Stardena Works')

@section('app-content')
<div class="body-wrapper">
<div class="container-fluid">

    <div class="font-weight-medium shadow-none position-relative overflow-hidden mb-4">
        <div class="card-body px-0">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <h4 class="font-weight-medium mb-0">Company Analytics</h4>
                    <nav aria-label="breadcrumb"><ol class="breadcrumb">
                        <li class="breadcrumb-item"><a class="text-muted text-decoration-none" href="{{ route('dashboard') }}">Home</a></li>
                        <li class="breadcrumb-item"><a class="text-muted text-decoration-none" href="{{ route('analytics.dashboard') }}">Analytics</a></li>
                        <li class="breadcrumb-item text-muted" aria-current="page">Companies</li>
                    </ol></nav>
                </div>
                <a href="{{ route('analytics.export.companies', request()->query()) }}" class="btn btn-sm btn-outline-secondary d-flex align-items-center gap-1">
                    <iconify-icon icon="solar:download-linear"></iconify-icon> Export CSV
                </a>
            </div>
        </div>
    </div>

    @include('analytics.partials.date-range-filter')

    <div class="row g-3 mb-4">
        @foreach([
            ['label'=>'Total Companies',   'value'=>number_format($kpis['total']),             'icon'=>'solar:buildings-2-linear',    'color'=>'primary'],
            ['label'=>'Verified',          'value'=>number_format($kpis['verified']),           'icon'=>'solar:shield-check-linear',   'color'=>'success'],
            ['label'=>'Unverified',        'value'=>number_format($kpis['unverified']),         'icon'=>'solar:shield-warning-linear', 'color'=>'warning'],
            ['label'=>'Active',            'value'=>number_format($kpis['active']),             'icon'=>'solar:check-circle-linear',   'color'=>'info'],
            ['label'=>'In Period',         'value'=>number_format($kpis['in_period']),          'icon'=>'solar:add-circle-linear',     'color'=>'secondary'],
            ['label'=>'Verification Rate', 'value'=>$kpis['verification_rate'].'%',            'icon'=>'solar:chart-2-linear',        'color'=>'success'],
            ['label'=>'Avg Jobs/Company',  'value'=>$kpis['avg_jobs'],                         'icon'=>'solar:briefcase-linear',      'color'=>'primary'],
        ] as $kpi)
        <div class="col">
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

    <div class="row g-4 mb-4">
        <div class="col-lg-8">
            <div class="card h-100">
                <div class="card-body">
                    <h4 class="card-title mb-0">Companies Registered Over Time</h4>
                    <p class="card-subtitle text-muted">New companies in selected period</p>
                    <div id="chart-company-trend" class="mt-3"></div>
                </div>
            </div>
        </div>
        <div class="col-lg-4">
            <div class="card h-100">
                <div class="card-body">
                    <h4 class="card-title mb-0">Verification Status</h4>
                    <div id="chart-verification-status" class="mt-3"></div>
                    <div class="text-center mt-2">
                        <div class="row">
                            <div class="col-6">
                                <h5 class="mb-0 text-success">{{ $kpis['verified'] }}</h5>
                                <small class="text-muted">Verified</small>
                            </div>
                            <div class="col-6">
                                <h5 class="mb-0 text-warning">{{ $kpis['unverified'] }}</h5>
                                <small class="text-muted">Unverified</small>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="row g-4 mb-4">
        <div class="col-lg-6">
            <div class="card h-100">
                <div class="card-body">
                    <h4 class="card-title mb-0">Companies by Industry</h4>
                    <div id="chart-by-industry" class="mt-3"></div>
                </div>
            </div>
        </div>
        <div class="col-lg-6">
            <div class="card h-100">
                <div class="card-body">
                    <h4 class="card-title mb-0">Companies by Location</h4>
                    <div id="chart-by-location" class="mt-3"></div>
                </div>
            </div>
        </div>
    </div>

    <div class="row g-4 mb-4">
        <div class="col-lg-6">
            <div class="card h-100">
                <div class="card-body">
                    <h4 class="card-title mb-0">Jobs per Company Distribution</h4>
                    <div id="chart-jobs-dist" class="mt-3"></div>
                </div>
            </div>
        </div>
        <div class="col-lg-6">
            <div class="card h-100">
                <div class="card-body">
                    <h4 class="card-title mb-0">Verification Trend (Last 30 days)</h4>
                    <div id="chart-verified-trend" class="mt-3"></div>
                </div>
            </div>
        </div>
    </div>

    <div class="row g-4">
        <div class="col-lg-8">
            <div class="card">
                <div class="card-body">
                    <h4 class="card-title mb-3">Most Active Companies</h4>
                    <div class="table-responsive">
                        <table class="table table-hover align-middle">
                            <thead class="table-light">
                                <tr><th>Company</th><th>Industry</th><th>Total Jobs</th><th>Active Jobs</th><th>Verified</th></tr>
                            </thead>
                            <tbody>
                                @forelse($mostActive as $co)
                                <tr>
                                    <td>
                                        <p class="mb-0 fw-medium">{{ $co->name }}</p>
                                    </td>
                                    <td class="text-muted fs-3">{{ $co->industry?->name ?? '–' }}</td>
                                    <td><span class="badge bg-primary-subtle text-primary">{{ $co->job_posts_count }}</span></td>
                                    <td><span class="badge bg-success-subtle text-success">{{ $co->active_jobs_count }}</span></td>
                                    <td>
                                        @if($co->is_verified)
                                            <iconify-icon icon="solar:shield-check-bold" class="text-success fs-5"></iconify-icon>
                                        @else
                                            <iconify-icon icon="solar:shield-warning-linear" class="text-warning fs-5"></iconify-icon>
                                        @endif
                                    </td>
                                </tr>
                                @empty
                                <tr><td colspan="5" class="text-center text-muted">No companies found</td></tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-lg-4">
            <div class="card">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <h4 class="card-title mb-0">Companies with No Active Jobs</h4>
                        <span class="badge bg-warning-subtle text-warning">{{ $noActiveJobs->count() }}</span>
                    </div>
                    <div class="table-responsive">
                        <table class="table table-sm table-hover">
                            <thead><tr><th>Company</th><th>Verified</th><th>Since</th></tr></thead>
                            <tbody>
                                @forelse($noActiveJobs as $co)
                                <tr>
                                    <td class="fw-medium fs-3">{{ Str::limit($co->name, 25) }}</td>
                                    <td>
                                        <span class="badge {{ $co->is_verified ? 'bg-success-subtle text-success' : 'bg-warning-subtle text-warning' }}">
                                            {{ $co->is_verified ? 'Yes' : 'No' }}
                                        </span>
                                    </td>
                                    <td class="text-muted fs-3">{{ \Carbon\Carbon::parse($co->created_at)->format('M Y') }}</td>
                                </tr>
                                @empty
                                <tr><td colspan="3" class="text-center text-muted">All companies have active jobs!</td></tr>
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

    new ApexCharts(document.querySelector('#chart-company-trend'), {
        series: [{ name: 'Companies', data: @json($trendValues) }],
        chart: { type: 'area', height: 280, toolbar: { show: false }, fontFamily: 'inherit' },
        colors: ['#5d87ff'],
        fill: { type: 'gradient', gradient: { opacityFrom: 0.4, opacityTo: 0.05 } },
        stroke: { curve: 'smooth', width: 2 },
        xaxis: { categories: @json($trendDates), labels: { style: { colors: textColor } }, tickAmount: 7, axisBorder: { show: false } },
        yaxis: { labels: { style: { colors: textColor } } },
        grid: { borderColor: gridColor, strokeDashArray: 4 },
        dataLabels: { enabled: false },
        tooltip: { theme: isDark ? 'dark' : 'light' },
    }).render();

    new ApexCharts(document.querySelector('#chart-verification-status'), {
        series: [{{ $kpis['verified'] }}, {{ $kpis['unverified'] }}],
        labels: ['Verified', 'Unverified'],
        chart: { type: 'donut', height: 200, fontFamily: 'inherit' },
        colors: ['#13deb9','#ffae1f'],
        legend: { show: false },
        plotOptions: { pie: { donut: { size: '70%' } } },
        dataLabels: { enabled: false },
        tooltip: { theme: isDark ? 'dark' : 'light' },
    }).render();

    var indData = @json($byIndustry);
    new ApexCharts(document.querySelector('#chart-by-industry'), {
        series: [{ name: 'Companies', data: indData.map(function(i){ return i.count; }) }],
        chart: { type: 'bar', height: 300, toolbar: { show: false }, fontFamily: 'inherit' },
        colors: ['#13deb9'],
        plotOptions: { bar: { horizontal: true, borderRadius: 4 } },
        xaxis: { categories: indData.map(function(i){ return i.name; }), labels: { style: { colors: textColor } } },
        yaxis: { labels: { style: { colors: textColor } } },
        grid: { borderColor: gridColor, strokeDashArray: 4 },
        dataLabels: { enabled: false },
        tooltip: { theme: isDark ? 'dark' : 'light' },
    }).render();

    var locData = @json($byLocation);
    new ApexCharts(document.querySelector('#chart-by-location'), {
        series: [{ name: 'Companies', data: locData.map(function(l){ return l.count; }) }],
        chart: { type: 'bar', height: 300, toolbar: { show: false }, fontFamily: 'inherit' },
        colors: ['#49beff'],
        plotOptions: { bar: { horizontal: true, borderRadius: 4 } },
        xaxis: { categories: locData.map(function(l){ return l.name; }), labels: { style: { colors: textColor } } },
        yaxis: { labels: { style: { colors: textColor } } },
        grid: { borderColor: gridColor, strokeDashArray: 4 },
        dataLabels: { enabled: false },
        tooltip: { theme: isDark ? 'dark' : 'light' },
    }).render();

    var distData = @json($jobsPerCompanyDist);
    var order = ['0 jobs','1–5 jobs','6–20 jobs','21–50 jobs','50+ jobs'];
    new ApexCharts(document.querySelector('#chart-jobs-dist'), {
        series: [{ name: 'Companies', data: order.map(function(k){ return distData[k]||0; }) }],
        chart: { type: 'bar', height: 280, toolbar: { show: false }, fontFamily: 'inherit' },
        colors: ['#fa896b'],
        xaxis: { categories: order, labels: { style: { colors: textColor } } },
        yaxis: { labels: { style: { colors: textColor } } },
        grid: { borderColor: gridColor, strokeDashArray: 4 },
        dataLabels: { enabled: false },
        plotOptions: { bar: { borderRadius: 4 } },
        tooltip: { theme: isDark ? 'dark' : 'light' },
    }).render();

    new ApexCharts(document.querySelector('#chart-verified-trend'), {
        series: [{ name: 'Verified', data: @json($vtValues) }],
        chart: { type: 'line', height: 280, toolbar: { show: false }, fontFamily: 'inherit' },
        colors: ['#13deb9'],
        stroke: { curve: 'smooth', width: 2 },
        xaxis: { categories: @json($vtDates), labels: { style: { colors: textColor } }, tickAmount: 7, axisBorder: { show: false } },
        yaxis: { labels: { style: { colors: textColor } } },
        grid: { borderColor: gridColor, strokeDashArray: 4 },
        dataLabels: { enabled: false },
        tooltip: { theme: isDark ? 'dark' : 'light' },
        markers: { size: 3 },
    }).render();

});
</script>
@endpush
