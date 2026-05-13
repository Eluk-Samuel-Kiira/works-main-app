@extends('layouts.app')
@section('title', 'Job Analytics – Stardena Works')

@section('app-content')
<div class="body-wrapper">
<div class="container-fluid">

    {{-- Breadcrumb --}}
    <div class="font-weight-medium shadow-none position-relative overflow-hidden mb-4">
        <div class="card-body px-0">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <h4 class="font-weight-medium mb-0">Job Analytics</h4>
                    <nav aria-label="breadcrumb">
                        <ol class="breadcrumb">
                            <li class="breadcrumb-item"><a class="text-muted text-decoration-none" href="{{ route('dashboard') }}">Home</a></li>
                            <li class="breadcrumb-item"><a class="text-muted text-decoration-none" href="{{ route('analytics.dashboard') }}">Analytics</a></li>
                            <li class="breadcrumb-item text-muted" aria-current="page">Jobs</li>
                        </ol>
                    </nav>
                </div>
                <a href="{{ route('analytics.export.jobs', request()->query()) }}" class="btn btn-sm btn-outline-secondary d-flex align-items-center gap-1">
                    <iconify-icon icon="solar:download-linear"></iconify-icon> Export CSV
                </a>
            </div>
        </div>
    </div>

    @include('analytics.partials.date-range-filter')

    {{-- KPI Row --}}
    <div class="row g-3 mb-4">
        @foreach([
            ['label'=>'Total Jobs',   'value'=>number_format($kpis['total']),   'icon'=>'solar:briefcase-linear',       'color'=>'primary'],
            ['label'=>'Active',        'value'=>number_format($kpis['active']),  'icon'=>'solar:check-circle-linear',    'color'=>'success'],
            ['label'=>'Expired',       'value'=>number_format($kpis['expired']), 'icon'=>'solar:clock-circle-linear',    'color'=>'danger'],
            ['label'=>'Featured',      'value'=>number_format($kpis['featured']),'icon'=>'solar:star-linear',             'color'=>'warning'],
            ['label'=>'Urgent',        'value'=>number_format($kpis['urgent']),  'icon'=>'solar:fire-linear',             'color'=>'danger'],
            ['label'=>'Verified',      'value'=>number_format($kpis['verified']),'icon'=>'solar:shield-check-linear',    'color'=>'info'],
            ['label'=>'Total Views',   'value'=>number_format($kpis['total_views']),'icon'=>'solar:eye-linear',           'color'=>'secondary'],
            ['label'=>'Applications',  'value'=>number_format($kpis['total_apps']),'icon'=>'solar:inbox-linear',          'color'=>'primary'],
            ['label'=>'Avg SEO Score', 'value'=>$kpis['avg_seo'].'%',           'icon'=>'solar:chart-2-linear',          'color'=>'success'],
        ] as $kpi)
        <div class="col-lg col-md-4 col-6">
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

    {{-- Trend Chart + Status Donut --}}
    <div class="row g-4 mb-4">
        <div class="col-lg-8">
            <div class="card h-100">
                <div class="card-body">
                    <h4 class="card-title mb-0">Jobs Posted Over Time</h4>
                    <p class="card-subtitle text-muted">New job posts in selected period</p>
                    <div id="chart-jobs-trend" class="mt-3"></div>
                </div>
            </div>
        </div>
        <div class="col-lg-4">
            <div class="card h-100">
                <div class="card-body">
                    <h4 class="card-title mb-0">Job Status</h4>
                    <p class="card-subtitle text-muted">Current breakdown</p>
                    <div id="chart-job-status-donut" class="mt-2"></div>
                </div>
            </div>
        </div>
    </div>

    {{-- Category + Location --}}
    <div class="row g-4 mb-4">
        <div class="col-lg-6">
            <div class="card h-100">
                <div class="card-body">
                    <h4 class="card-title mb-0">Jobs by Category</h4>
                    <p class="card-subtitle text-muted">Top 10 categories</p>
                    <div id="chart-by-category" class="mt-3"></div>
                </div>
            </div>
        </div>
        <div class="col-lg-6">
            <div class="card h-100">
                <div class="card-body">
                    <h4 class="card-title mb-0">Jobs by Location</h4>
                    <p class="card-subtitle text-muted">Top 10 districts</p>
                    <div id="chart-by-location" class="mt-3"></div>
                </div>
            </div>
        </div>
    </div>

    {{-- Industry + Type + Location Type --}}
    <div class="row g-4 mb-4">
        <div class="col-lg-4">
            <div class="card h-100">
                <div class="card-body">
                    <h4 class="card-title mb-0">By Industry</h4>
                    <div id="chart-by-industry" class="mt-3"></div>
                </div>
            </div>
        </div>
        <div class="col-lg-4">
            <div class="card h-100">
                <div class="card-body">
                    <h4 class="card-title mb-0">By Job Type</h4>
                    <div id="chart-by-type" class="mt-3"></div>
                </div>
            </div>
        </div>
        <div class="col-lg-4">
            <div class="card h-100">
                <div class="card-body">
                    <h4 class="card-title mb-0">Location Type</h4>
                    <p class="card-subtitle text-muted">On-site / Remote / Hybrid</p>
                    <div id="chart-location-type" class="mt-2"></div>
                </div>
            </div>
        </div>
    </div>

    {{-- Experience + Education --}}
    <div class="row g-4 mb-4">
        <div class="col-lg-6">
            <div class="card">
                <div class="card-body">
                    <h4 class="card-title mb-0">By Experience Level</h4>
                    <div id="chart-by-experience" class="mt-3"></div>
                </div>
            </div>
        </div>
        <div class="col-lg-6">
            <div class="card">
                <div class="card-body">
                    <h4 class="card-title mb-0">SEO Score Distribution</h4>
                    <p class="card-subtitle text-muted">Jobs grouped by score band</p>
                    <div id="chart-seo-dist" class="mt-3"></div>
                </div>
            </div>
        </div>
    </div>

    {{-- Featured vs Non-Featured Comparison --}}
    <div class="row g-4 mb-4">
        <div class="col-lg-6">
            <div class="card">
                <div class="card-body">
                    <h4 class="card-title mb-0">Featured vs Standard Performance</h4>
                    <p class="card-subtitle text-muted">Average engagement metrics</p>
                    <div id="chart-featured-comparison" class="mt-3"></div>
                </div>
            </div>
        </div>
        <div class="col-lg-6">
            <div class="card">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <div>
                            <h4 class="card-title mb-0">Jobs Approaching Deadline</h4>
                            <p class="card-subtitle text-muted">Expiring within 14 days</p>
                        </div>
                        <span class="badge bg-warning-subtle text-warning">{{ $approachingDeadline->count() }} jobs</span>
                    </div>
                    <div class="table-responsive">
                        <table class="table table-sm table-hover">
                            <thead><tr><th>Job</th><th>Deadline</th><th>Apps</th></tr></thead>
                            <tbody>
                                @forelse($approachingDeadline as $job)
                                <tr>
                                    <td class="text-truncate" style="max-width:200px;">
                                        <span class="fw-medium">{{ $job->job_title }}</span><br>
                                        <small class="text-muted">{{ $job->company?->name ?? '–' }}</small>
                                    </td>
                                    <td>
                                        <span class="{{ $job->deadline->diffInDays() <= 3 ? 'text-danger fw-semibold' : 'text-warning' }}">
                                            {{ $job->deadline->format('M d') }}
                                        </span>
                                        <br><small class="text-muted">{{ $job->deadline->diffForHumans() }}</small>
                                    </td>
                                    <td><span class="badge bg-primary-subtle text-primary">{{ $job->application_count }}</span></td>
                                </tr>
                                @empty
                                <tr><td colspan="3" class="text-center text-muted">No jobs approaching deadline</td></tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- Top Performing Jobs Table --}}
    <div class="row g-4">
        <div class="col-12">
            <div class="card">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <div>
                            <h4 class="card-title mb-0">Top Performing Jobs</h4>
                            <p class="card-subtitle text-muted">Sorted by view count</p>
                        </div>
                        <a href="{{ route('analytics.export.jobs', request()->query()) }}" class="btn btn-sm btn-outline-secondary">
                            <iconify-icon icon="solar:download-linear"></iconify-icon> Export CSV
                        </a>
                    </div>
                    <div class="table-responsive">
                        <table class="table table-hover align-middle">
                            <thead class="table-light">
                                <tr>
                                    <th>Job Title</th><th>Company</th><th>Views</th>
                                    <th>Clicks</th><th>Applications</th><th>SEO</th>
                                    <th>Status</th><th>Deadline</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($topByViews as $job)
                                <tr>
                                    <td>
                                        <p class="mb-0 fw-medium">{{ Str::limit($job->job_title, 40) }}</p>
                                    </td>
                                    <td class="text-muted fs-3">{{ $job->company?->name ?? '–' }}</td>
                                    <td><span class="badge bg-primary-subtle text-primary">{{ number_format($job->view_count) }}</span></td>
                                    <td><span class="badge bg-info-subtle text-info">{{ number_format($job->click_count) }}</span></td>
                                    <td><span class="badge bg-success-subtle text-success">{{ number_format($job->application_count) }}</span></td>
                                    <td>
                                        <div class="progress" style="height:6px;width:60px;">
                                            <div class="progress-bar {{ $job->seo_score >= 75 ? 'bg-success' : ($job->seo_score >= 50 ? 'bg-warning' : 'bg-danger') }}"
                                                 style="width:{{ $job->seo_score ?? 0 }}%"></div>
                                        </div>
                                        <small class="text-muted">{{ $job->seo_score ?? 0 }}%</small>
                                    </td>
                                    <td>
                                        <span class="badge {{ $job->is_active ? 'bg-success-subtle text-success' : 'bg-danger-subtle text-danger' }}">
                                            {{ $job->is_active ? 'Active' : 'Inactive' }}
                                        </span>
                                    </td>
                                    <td class="text-muted fs-3">
                                        {{ $job->deadline ? $job->deadline->format('M d, Y') : '–' }}
                                    </td>
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

    var trendDates  = @json($trendDates);
    var trendValues = @json($trendValues);

    // Jobs trend
    new ApexCharts(document.querySelector('#chart-jobs-trend'), {
        series: [{ name: 'Jobs Posted', data: trendValues }],
        chart: { type: 'area', height: 280, toolbar: { show: false }, fontFamily: 'inherit' },
        colors: ['#5d87ff'],
        fill: { type: 'gradient', gradient: { opacityFrom: 0.4, opacityTo: 0.05 } },
        stroke: { curve: 'smooth', width: 2 },
        xaxis: { categories: trendDates, labels: { style: { colors: textColor } }, tickAmount: 7, axisBorder: { show: false } },
        yaxis: { labels: { style: { colors: textColor } } },
        grid: { borderColor: gridColor, strokeDashArray: 4 },
        dataLabels: { enabled: false },
        tooltip: { theme: isDark ? 'dark' : 'light' },
    }).render();

    // Status donut
    var byStatus = @json($byStatus);
    new ApexCharts(document.querySelector('#chart-job-status-donut'), {
        series: Object.values(byStatus),
        labels: Object.keys(byStatus),
        chart: { type: 'donut', height: 250, fontFamily: 'inherit' },
        colors: ['#13deb9','#fa896b','#adb5bd','#ffae1f','#ff6692'],
        legend: { position: 'bottom', labels: { colors: textColor } },
        plotOptions: { pie: { donut: { size: '65%' } } },
        dataLabels: { enabled: true, style: { colors: ['#fff'] } },
        tooltip: { theme: isDark ? 'dark' : 'light' },
    }).render();

    // By Category
    var catData = @json($byCategory);
    new ApexCharts(document.querySelector('#chart-by-category'), {
        series: [{ name: 'Jobs', data: catData.map(function(c){ return c.count; }) }],
        chart: { type: 'bar', height: 300, toolbar: { show: false }, fontFamily: 'inherit' },
        colors: ['#5d87ff'],
        xaxis: { categories: catData.map(function(c){ return c.name; }), labels: { style: { colors: textColor } } },
        yaxis: { labels: { style: { colors: textColor } } },
        grid: { borderColor: gridColor, strokeDashArray: 4 },
        dataLabels: { enabled: false },
        plotOptions: { bar: { borderRadius: 4 } },
        tooltip: { theme: isDark ? 'dark' : 'light' },
    }).render();

    // By Location
    var locData = @json($byLocation);
    new ApexCharts(document.querySelector('#chart-by-location'), {
        series: [{ name: 'Jobs', data: locData.map(function(l){ return l.count; }) }],
        chart: { type: 'bar', height: 300, toolbar: { show: false }, fontFamily: 'inherit' },
        colors: ['#49beff'],
        plotOptions: { bar: { horizontal: true, borderRadius: 4 } },
        xaxis: { categories: locData.map(function(l){ return l.name; }), labels: { style: { colors: textColor } } },
        yaxis: { labels: { style: { colors: textColor } } },
        grid: { borderColor: gridColor, strokeDashArray: 4 },
        dataLabels: { enabled: false },
        tooltip: { theme: isDark ? 'dark' : 'light' },
    }).render();

    // By Industry
    var indData = @json($byIndustry);
    new ApexCharts(document.querySelector('#chart-by-industry'), {
        series: [{ name: 'Jobs', data: indData.map(function(i){ return i.count; }) }],
        chart: { type: 'bar', height: 280, toolbar: { show: false }, fontFamily: 'inherit' },
        colors: ['#13deb9'],
        plotOptions: { bar: { horizontal: true, borderRadius: 4 } },
        xaxis: { categories: indData.map(function(i){ return i.name; }), labels: { style: { colors: textColor } } },
        yaxis: { labels: { style: { colors: textColor } } },
        grid: { borderColor: gridColor, strokeDashArray: 4 },
        dataLabels: { enabled: false },
        tooltip: { theme: isDark ? 'dark' : 'light' },
    }).render();

    // By Job Type
    var typeData = @json($byType);
    new ApexCharts(document.querySelector('#chart-by-type'), {
        series: typeData.map(function(t){ return t.count; }),
        labels: typeData.map(function(t){ return t.name; }),
        chart: { type: 'pie', height: 280, fontFamily: 'inherit' },
        colors: ['#5d87ff','#13deb9','#ffae1f','#fa896b','#49beff'],
        legend: { position: 'bottom', labels: { colors: textColor } },
        dataLabels: { enabled: true, style: { colors: ['#fff'] } },
        tooltip: { theme: isDark ? 'dark' : 'light' },
    }).render();

    // Location type
    var locType = @json($byLocationType);
    new ApexCharts(document.querySelector('#chart-location-type'), {
        series: Object.values(locType),
        labels: Object.keys(locType).map(function(k){ return k.charAt(0).toUpperCase()+k.slice(1); }),
        chart: { type: 'donut', height: 250, fontFamily: 'inherit' },
        colors: ['#5d87ff','#13deb9','#ffae1f'],
        legend: { position: 'bottom', labels: { colors: textColor } },
        dataLabels: { enabled: true, style: { colors: ['#fff'] } },
        tooltip: { theme: isDark ? 'dark' : 'light' },
    }).render();

    // By Experience
    var expData = @json($byExperience);
    new ApexCharts(document.querySelector('#chart-by-experience'), {
        series: [{ name: 'Jobs', data: expData.map(function(e){ return e.count; }) }],
        chart: { type: 'bar', height: 250, toolbar: { show: false }, fontFamily: 'inherit' },
        colors: ['#fa896b'],
        plotOptions: { bar: { borderRadius: 4 } },
        xaxis: { categories: expData.map(function(e){ return e.name; }), labels: { style: { colors: textColor }, rotate: -30 } },
        yaxis: { labels: { style: { colors: textColor } } },
        grid: { borderColor: gridColor, strokeDashArray: 4 },
        dataLabels: { enabled: false },
        tooltip: { theme: isDark ? 'dark' : 'light' },
    }).render();

    // SEO distribution
    var seoDist = @json($seoDistribution);
    new ApexCharts(document.querySelector('#chart-seo-dist'), {
        series: [{ name: 'Jobs', data: Object.values(seoDist) }],
        chart: { type: 'bar', height: 250, toolbar: { show: false }, fontFamily: 'inherit' },
        colors: ['#5d87ff'],
        xaxis: { categories: Object.keys(seoDist), labels: { style: { colors: textColor } } },
        yaxis: { labels: { style: { colors: textColor } } },
        grid: { borderColor: gridColor, strokeDashArray: 4 },
        dataLabels: { enabled: false },
        plotOptions: { bar: { borderRadius: 4, columnWidth: '50%' } },
        tooltip: { theme: isDark ? 'dark' : 'light' },
    }).render();

    // Featured vs Standard
    var fc = @json($featuredComparison);
    new ApexCharts(document.querySelector('#chart-featured-comparison'), {
        series: [
            { name: 'Featured', data: fc.featured },
            { name: 'Standard', data: fc.standard },
        ],
        chart: { type: 'bar', height: 280, toolbar: { show: false }, fontFamily: 'inherit' },
        colors: ['#ffae1f','#adb5bd'],
        xaxis: { categories: fc.categories, labels: { style: { colors: textColor } } },
        yaxis: { labels: { style: { colors: textColor } } },
        grid: { borderColor: gridColor, strokeDashArray: 4 },
        dataLabels: { enabled: false },
        plotOptions: { bar: { borderRadius: 4 } },
        legend: { labels: { colors: textColor } },
        tooltip: { theme: isDark ? 'dark' : 'light' },
    }).render();

});
</script>
@endpush
