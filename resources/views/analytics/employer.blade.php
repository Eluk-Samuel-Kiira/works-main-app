@extends('layouts.app')
@section('title', 'My Analytics – Stardena Works')

@section('app-content')
<div class="body-wrapper">
<div class="container-fluid">

    <div class="font-weight-medium shadow-none position-relative overflow-hidden mb-4">
        <div class="card-body px-0">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <h4 class="font-weight-medium mb-0">My Analytics</h4>
                    <nav aria-label="breadcrumb"><ol class="breadcrumb">
                        <li class="breadcrumb-item"><a class="text-muted text-decoration-none" href="{{ route('dashboard') }}">Home</a></li>
                        <li class="breadcrumb-item text-muted" aria-current="page">My Analytics</li>
                    </ol></nav>
                </div>
                <a href="{{ route('analytics.employer.export', request()->query()) }}" class="btn btn-sm btn-outline-secondary d-flex align-items-center gap-1">
                    <iconify-icon icon="solar:download-linear"></iconify-icon> Export CSV
                </a>
            </div>
        </div>
    </div>

    @include('analytics.partials.date-range-filter')

    {{-- KPI Cards --}}
    <div class="row g-3 mb-4">
        @foreach([
            ['label'=>'My Companies',       'value'=>number_format($kpis['companies']),          'icon'=>'solar:buildings-2-linear',    'color'=>'primary'],
            ['label'=>'Verified Companies', 'value'=>number_format($kpis['verified_companies']), 'icon'=>'solar:shield-check-linear',   'color'=>'success'],
            ['label'=>'Total Jobs',         'value'=>number_format($kpis['total_jobs']),          'icon'=>'solar:briefcase-linear',      'color'=>'info'],
            ['label'=>'Active Jobs',        'value'=>number_format($kpis['active_jobs']),         'icon'=>'solar:check-circle-linear',   'color'=>'success'],
            ['label'=>'Expired Jobs',       'value'=>number_format($kpis['expired_jobs']),        'icon'=>'solar:clock-circle-linear',   'color'=>'danger'],
            ['label'=>'Featured Jobs',      'value'=>number_format($kpis['featured_jobs']),       'icon'=>'solar:star-linear',           'color'=>'warning'],
            ['label'=>'Total Views',        'value'=>number_format($kpis['total_views']),         'icon'=>'solar:eye-linear',            'color'=>'secondary'],
            ['label'=>'Total Applications', 'value'=>number_format($kpis['total_applications']), 'icon'=>'solar:inbox-linear',          'color'=>'primary'],
            ['label'=>'Avg Views/Job',      'value'=>$kpis['avg_views_per_job'],                 'icon'=>'solar:chart-2-linear',        'color'=>'info'],
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

    {{-- Jobs Trend + Status --}}
    <div class="row g-4 mb-4">
        <div class="col-lg-8">
            <div class="card h-100">
                <div class="card-body">
                    <h4 class="card-title mb-0">Jobs Posted & Views Over Time</h4>
                    <p class="card-subtitle text-muted">Your posting activity in selected period</p>
                    <div id="chart-employer-trend" class="mt-3"></div>
                </div>
            </div>
        </div>
        <div class="col-lg-4">
            <div class="card h-100">
                <div class="card-body">
                    <h4 class="card-title mb-0">Job Status</h4>
                    <div id="chart-job-status" class="mt-3"></div>
                    <div class="row text-center mt-2">
                        @foreach($jobStatus as $status => $count)
                        <div class="col-6 mb-2">
                            <h5 class="mb-0">{{ $count }}</h5>
                            <small class="text-muted">{{ $status }}</small>
                        </div>
                        @endforeach
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- My Companies + Expiring Soon --}}
    <div class="row g-4 mb-4">
        <div class="col-lg-5">
            <div class="card h-100">
                <div class="card-body">
                    <h4 class="card-title mb-3">My Companies</h4>
                    @forelse($companies as $co)
                    <div class="d-flex justify-content-between align-items-center mb-3 pb-3 border-bottom">
                        <div>
                            <p class="mb-0 fw-medium">{{ $co->name }}</p>
                            <small class="text-muted">Since {{ \Carbon\Carbon::parse($co->created_at)->format('M Y') }}</small>
                        </div>
                        <div class="d-flex gap-2">
                            @if($co->is_verified)
                                <span class="badge bg-success-subtle text-success">Verified</span>
                            @else
                                <span class="badge bg-warning-subtle text-warning">Unverified</span>
                            @endif
                            <span class="badge {{ $co->is_active ? 'bg-primary-subtle text-primary' : 'bg-secondary-subtle text-secondary' }}">
                                {{ $co->is_active ? 'Active' : 'Inactive' }}
                            </span>
                        </div>
                    </div>
                    @empty
                    <p class="text-muted text-center">No companies yet</p>
                    @endforelse
                </div>
            </div>
        </div>
        <div class="col-lg-7">
            <div class="card h-100">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <h4 class="card-title mb-0">Jobs Expiring Soon</h4>
                        <span class="badge bg-warning-subtle text-warning">Next 14 days</span>
                    </div>
                    <div class="table-responsive">
                        <table class="table table-sm table-hover">
                            <thead><tr><th>Job Title</th><th>Company</th><th>Deadline</th><th>Applications</th></tr></thead>
                            <tbody>
                                @forelse($expiringSoon as $job)
                                <tr>
                                    <td class="fw-medium fs-3">{{ Str::limit($job->job_title, 35) }}</td>
                                    <td class="text-muted fs-3">{{ $job->company?->name ?? '–' }}</td>
                                    <td>
                                        <span class="{{ $job->deadline->diffInDays() <= 3 ? 'text-danger fw-semibold' : 'text-warning' }}">
                                            {{ $job->deadline->format('M d') }}
                                        </span>
                                        <br><small class="text-muted">{{ $job->deadline->diffForHumans() }}</small>
                                    </td>
                                    <td><span class="badge bg-primary-subtle text-primary">{{ $job->application_count }}</span></td>
                                </tr>
                                @empty
                                <tr><td colspan="4" class="text-center text-success">No jobs expiring soon!</td></tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- Company Performance --}}
    @if($companyPerformance->count() > 1)
    <div class="row g-4 mb-4">
        <div class="col-12">
            <div class="card">
                <div class="card-body">
                    <h4 class="card-title mb-3">Company Performance Comparison</h4>
                    <div id="chart-company-performance" class="mt-2"></div>
                </div>
            </div>
        </div>
    </div>
    @endif

    {{-- Top Performing Jobs --}}
    <div class="row g-4">
        <div class="col-12">
            <div class="card">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <h4 class="card-title mb-0">Top Performing Jobs</h4>
                        <a href="{{ route('analytics.employer.export', request()->query()) }}" class="btn btn-sm btn-outline-secondary">
                            <iconify-icon icon="solar:download-linear"></iconify-icon> Export
                        </a>
                    </div>
                    <div class="table-responsive">
                        <table class="table table-hover align-middle">
                            <thead class="table-light">
                                <tr><th>Job Title</th><th>Company</th><th>Views</th><th>Clicks</th><th>Applications</th><th>SEO</th><th>Status</th><th>Posted</th></tr>
                            </thead>
                            <tbody>
                                @forelse($topJobs as $job)
                                <tr>
                                    <td class="fw-medium">{{ Str::limit($job->job_title, 40) }}</td>
                                    <td class="text-muted fs-3">{{ $job->company?->name ?? '–' }}</td>
                                    <td><span class="badge bg-primary-subtle text-primary">{{ number_format($job->view_count) }}</span></td>
                                    <td><span class="badge bg-info-subtle text-info">{{ number_format($job->click_count) }}</span></td>
                                    <td><span class="badge bg-success-subtle text-success">{{ number_format($job->application_count) }}</span></td>
                                    <td>
                                        <div class="progress" style="height:5px;width:50px;">
                                            <div class="progress-bar {{ $job->seo_score >= 75 ? 'bg-success' : ($job->seo_score >= 50 ? 'bg-warning' : 'bg-danger') }}"
                                                 style="width:{{ $job->seo_score ?? 0 }}%"></div>
                                        </div>
                                        <small>{{ $job->seo_score ?? 0 }}%</small>
                                    </td>
                                    <td><span class="badge {{ $job->is_active ? 'bg-success-subtle text-success' : 'bg-danger-subtle text-danger' }}">{{ $job->is_active ? 'Active' : 'Inactive' }}</span></td>
                                    <td class="text-muted fs-3">{{ $job->created_at->format('M d, Y') }}</td>
                                </tr>
                                @empty
                                <tr><td colspan="8" class="text-center text-muted py-4">No jobs found</td></tr>
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

    new ApexCharts(document.querySelector('#chart-employer-trend'), {
        series: [
            { name: 'Jobs Posted', data: @json($trendValues) },
            { name: 'Views',       data: @json($viewsValues) },
        ],
        chart: { type: 'area', height: 280, toolbar: { show: false }, fontFamily: 'inherit' },
        colors: ['#5d87ff','#13deb9'],
        fill: { type: 'gradient', gradient: { opacityFrom: 0.3, opacityTo: 0.05 } },
        stroke: { curve: 'smooth', width: 2 },
        xaxis: { categories: @json($trendDates), labels: { style: { colors: textColor } }, tickAmount: 7, axisBorder: { show: false } },
        yaxis: { labels: { style: { colors: textColor } } },
        grid: { borderColor: gridColor, strokeDashArray: 4 },
        dataLabels: { enabled: false },
        legend: { labels: { colors: textColor } },
        tooltip: { theme: isDark ? 'dark' : 'light' },
    }).render();

    var statusData = @json($jobStatus);
    new ApexCharts(document.querySelector('#chart-job-status'), {
        series: Object.values(statusData),
        labels: Object.keys(statusData),
        chart: { type: 'donut', height: 220, fontFamily: 'inherit' },
        colors: ['#13deb9','#fa896b','#adb5bd','#ffae1f'],
        legend: { show: false },
        plotOptions: { pie: { donut: { size: '65%' } } },
        dataLabels: { enabled: false },
        tooltip: { theme: isDark ? 'dark' : 'light' },
    }).render();

    @if($companyPerformance->count() > 1)
    var cpData = @json($companyPerformance);
    new ApexCharts(document.querySelector('#chart-company-performance'), {
        series: [
            { name: 'Total Jobs',        data: cpData.map(function(c){ return c.job_posts_count || 0; }) },
            { name: 'Total Views',       data: cpData.map(function(c){ return c.total_views || 0; }) },
            { name: 'Total Applications',data: cpData.map(function(c){ return c.total_applications || 0; }) },
        ],
        chart: { type: 'bar', height: 280, toolbar: { show: false }, fontFamily: 'inherit' },
        colors: ['#5d87ff','#13deb9','#ffae1f'],
        xaxis: { categories: cpData.map(function(c){ return c.name; }), labels: { style: { colors: textColor } } },
        yaxis: { labels: { style: { colors: textColor } } },
        grid: { borderColor: gridColor, strokeDashArray: 4 },
        dataLabels: { enabled: false },
        plotOptions: { bar: { borderRadius: 4 } },
        legend: { labels: { colors: textColor } },
        tooltip: { theme: isDark ? 'dark' : 'light' },
    }).render();
    @endif

});
</script>
@endpush
