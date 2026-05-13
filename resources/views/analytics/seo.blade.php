@extends('layouts.app')
@section('title', 'SEO Analytics – Stardena Works')

@section('app-content')
<div class="body-wrapper">
<div class="container-fluid">

    <div class="font-weight-medium shadow-none position-relative overflow-hidden mb-4">
        <div class="card-body px-0">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <h4 class="font-weight-medium mb-0">SEO & Content Analytics</h4>
                    <nav aria-label="breadcrumb"><ol class="breadcrumb">
                        <li class="breadcrumb-item"><a class="text-muted text-decoration-none" href="{{ route('dashboard') }}">Home</a></li>
                        <li class="breadcrumb-item"><a class="text-muted text-decoration-none" href="{{ route('analytics.dashboard') }}">Analytics</a></li>
                        <li class="breadcrumb-item text-muted" aria-current="page">SEO</li>
                    </ol></nav>
                </div>
                <a href="{{ route('analytics.export.seo', request()->query()) }}" class="btn btn-sm btn-outline-secondary d-flex align-items-center gap-1">
                    <iconify-icon icon="solar:download-linear"></iconify-icon> Export CSV
                </a>
            </div>
        </div>
    </div>

    @include('analytics.partials.date-range-filter')

    {{-- Job SEO KPIs --}}
    <div class="row g-3 mb-4">
        @foreach([
            ['label'=>'Avg SEO Score',    'value'=>$kpis['avg_seo_score'].'%',      'icon'=>'solar:chart-2-linear',       'color'=>'primary'],
            ['label'=>'Avg Quality',      'value'=>$kpis['avg_quality_score'].'%',  'icon'=>'solar:star-linear',           'color'=>'warning'],
            ['label'=>'Indexed Jobs',     'value'=>number_format($kpis['indexed']), 'icon'=>'solar:global-linear',        'color'=>'success'],
            ['label'=>'Not Indexed',      'value'=>number_format($kpis['not_indexed']),'icon'=>'solar:eye-closed-linear',  'color'=>'danger'],
            ['label'=>'High SEO (≥80)',   'value'=>number_format($kpis['high_seo']),'icon'=>'solar:medal-ribbons-star-linear','color'=>'success'],
            ['label'=>'Low SEO (<50)',    'value'=>number_format($kpis['low_seo']), 'icon'=>'solar:danger-triangle-linear','color'=>'danger'],
            ['label'=>'Total Impressions','value'=>number_format($kpis['total_impressions']),'icon'=>'solar:eye-linear',  'color'=>'info'],
            ['label'=>'Search Clicks',    'value'=>number_format($kpis['total_clicks']),'icon'=>'solar:cursor-linear',    'color'=>'secondary'],
            ['label'=>'Avg CTR',          'value'=>$kpis['avg_ctr'].'%',            'icon'=>'solar:arrow-right-up-linear','color'=>'primary'],
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

    {{-- Indexing Status + SEO Score Distribution --}}
    <div class="row g-4 mb-4">
        <div class="col-lg-4">
            <div class="card h-100">
                <div class="card-body">
                    <h4 class="card-title mb-0">Indexing Status</h4>
                    <p class="card-subtitle text-muted">Google indexing state</p>
                    <div id="chart-indexing-status" class="mt-3"></div>
                </div>
            </div>
        </div>
        <div class="col-lg-4">
            <div class="card h-100">
                <div class="card-body">
                    <h4 class="card-title mb-0">SEO Score Distribution</h4>
                    <p class="card-subtitle text-muted">Jobs by score band</p>
                    <div id="chart-seo-dist" class="mt-3"></div>
                </div>
            </div>
        </div>
        <div class="col-lg-4">
            <div class="card h-100">
                <div class="card-body">
                    <h4 class="card-title mb-0">Content Quality Distribution</h4>
                    <p class="card-subtitle text-muted">Jobs by quality band</p>
                    <div id="chart-quality-dist" class="mt-3"></div>
                </div>
            </div>
        </div>
    </div>

    {{-- Indexed over time + Impressions/Clicks --}}
    <div class="row g-4 mb-4">
        <div class="col-lg-6">
            <div class="card h-100">
                <div class="card-body">
                    <h4 class="card-title mb-0">Jobs Indexed Over Time</h4>
                    <p class="card-subtitle text-muted">Last 30 days</p>
                    <div id="chart-indexed-trend" class="mt-3"></div>
                </div>
            </div>
        </div>
        <div class="col-lg-6">
            <div class="card h-100">
                <div class="card-body">
                    <h4 class="card-title mb-0">Search Impressions & Clicks</h4>
                    <p class="card-subtitle text-muted">Aggregated from job posts</p>
                    <div id="chart-impressions-clicks" class="mt-3"></div>
                </div>
            </div>
        </div>
    </div>

    {{-- Blog KPIs --}}
    <div class="row g-3 mb-4">
        <div class="col-12"><h5 class="text-muted fw-semibold">Blog Performance</h5></div>
        @foreach([
            ['label'=>'Total Blogs',  'value'=>number_format($blogKpis['total']),        'icon'=>'solar:document-text-linear','color'=>'primary'],
            ['label'=>'Published',    'value'=>number_format($blogKpis['published']),     'icon'=>'solar:check-circle-linear', 'color'=>'success'],
            ['label'=>'Indexed',      'value'=>number_format($blogKpis['indexed']),       'icon'=>'solar:global-linear',       'color'=>'info'],
            ['label'=>'Avg SEO',      'value'=>$blogKpis['avg_seo'].'%',                 'icon'=>'solar:chart-2-linear',      'color'=>'warning'],
            ['label'=>'Avg Quality',  'value'=>$blogKpis['avg_quality'].'%',             'icon'=>'solar:star-linear',          'color'=>'secondary'],
            ['label'=>'Total Views',  'value'=>number_format($blogKpis['total_views']),  'icon'=>'solar:eye-linear',           'color'=>'primary'],
            ['label'=>'Total Shares', 'value'=>number_format($blogKpis['total_shares']), 'icon'=>'solar:share-linear',         'color'=>'success'],
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

    {{-- Blog SEO dist + Top blogs --}}
    <div class="row g-4 mb-4">
        <div class="col-lg-5">
            <div class="card h-100">
                <div class="card-body">
                    <h4 class="card-title mb-0">Blog SEO Score Distribution</h4>
                    <div id="chart-blog-seo-dist" class="mt-3"></div>
                </div>
            </div>
        </div>
        <div class="col-lg-7">
            <div class="card h-100">
                <div class="card-body">
                    <h4 class="card-title mb-0">Top Blog Posts</h4>
                    <p class="card-subtitle text-muted mb-3">By view count</p>
                    <div class="table-responsive">
                        <table class="table table-sm table-hover">
                            <thead><tr><th>Title</th><th>Views</th><th>Shares</th><th>SEO</th><th>Indexed</th></tr></thead>
                            <tbody>
                                @forelse($topBlogs as $blog)
                                <tr>
                                    <td class="text-truncate fw-medium fs-3" style="max-width:200px;">{{ $blog->title }}</td>
                                    <td><span class="badge bg-primary-subtle text-primary">{{ number_format($blog->view_count) }}</span></td>
                                    <td><span class="badge bg-info-subtle text-info">{{ $blog->share_count }}</span></td>
                                    <td>
                                        <div class="progress" style="height:5px;width:50px;">
                                            <div class="progress-bar {{ $blog->seo_score >= 75 ? 'bg-success' : ($blog->seo_score >= 50 ? 'bg-warning' : 'bg-danger') }}"
                                                 style="width:{{ $blog->seo_score ?? 0 }}%"></div>
                                        </div>
                                        <small class="text-muted">{{ $blog->seo_score ?? 0 }}%</small>
                                    </td>
                                    <td>
                                        @if($blog->is_indexed)
                                            <iconify-icon icon="solar:check-circle-bold" class="text-success"></iconify-icon>
                                        @else
                                            <iconify-icon icon="solar:close-circle-linear" class="text-danger"></iconify-icon>
                                        @endif
                                    </td>
                                </tr>
                                @empty
                                <tr><td colspan="5" class="text-center text-muted">No blogs found</td></tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- Jobs needing SEO + Top SEO performers --}}
    <div class="row g-4">
        <div class="col-lg-6">
            <div class="card">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <h4 class="card-title mb-0">Jobs Needing SEO Attention</h4>
                        <span class="badge bg-danger-subtle text-danger">Score &lt; 50</span>
                    </div>
                    <div class="table-responsive">
                        <table class="table table-sm table-hover">
                            <thead><tr><th>Job</th><th>SEO</th><th>Quality</th><th>Indexed</th></tr></thead>
                            <tbody>
                                @forelse($lowSeoJobs as $job)
                                <tr>
                                    <td>
                                        <p class="mb-0 fw-medium fs-3 text-truncate" style="max-width:180px;">{{ $job->job_title }}</p>
                                        <small class="text-muted">{{ $job->company?->name ?? '–' }}</small>
                                    </td>
                                    <td><span class="badge bg-danger-subtle text-danger">{{ $job->seo_score ?? 0 }}%</span></td>
                                    <td><span class="badge bg-warning-subtle text-warning">{{ $job->content_quality_score ?? 0 }}%</span></td>
                                    <td>
                                        @if($job->is_indexed)
                                            <iconify-icon icon="solar:check-circle-bold" class="text-success"></iconify-icon>
                                        @else
                                            <iconify-icon icon="solar:close-circle-linear" class="text-muted"></iconify-icon>
                                        @endif
                                    </td>
                                </tr>
                                @empty
                                <tr><td colspan="4" class="text-center text-success">All jobs have good SEO scores!</td></tr>
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
                    <h4 class="card-title mb-3">Top SEO Performers</h4>
                    <div class="table-responsive">
                        <table class="table table-sm table-hover">
                            <thead><tr><th>Job</th><th>SEO</th><th>Impressions</th><th>Clicks</th></tr></thead>
                            <tbody>
                                @forelse($topSeoJobs as $job)
                                <tr>
                                    <td>
                                        <p class="mb-0 fw-medium fs-3 text-truncate" style="max-width:180px;">{{ $job->job_title }}</p>
                                        <small class="text-muted">{{ $job->company?->name ?? '–' }}</small>
                                    </td>
                                    <td><span class="badge bg-success-subtle text-success">{{ $job->seo_score ?? 0 }}%</span></td>
                                    <td class="text-muted fs-3">{{ number_format($job->search_impressions) }}</td>
                                    <td class="text-muted fs-3">{{ number_format($job->search_clicks) }}</td>
                                </tr>
                                @empty
                                <tr><td colspan="4" class="text-center text-muted">No data</td></tr>
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

    var indexingData = @json($indexingStatus);
    new ApexCharts(document.querySelector('#chart-indexing-status'), {
        series: Object.values(indexingData),
        labels: Object.keys(indexingData),
        chart: { type: 'donut', height: 250, fontFamily: 'inherit' },
        colors: ['#13deb9','#fa896b','#ffae1f','#adb5bd'],
        legend: { position: 'bottom', labels: { colors: textColor } },
        plotOptions: { pie: { donut: { size: '65%' } } },
        dataLabels: { enabled: false },
        tooltip: { theme: isDark ? 'dark' : 'light' },
    }).render();

    var seoDist = @json($seoDistribution);
    new ApexCharts(document.querySelector('#chart-seo-dist'), {
        series: [{ name: 'Jobs', data: Object.values(seoDist) }],
        chart: { type: 'bar', height: 250, toolbar: { show: false }, fontFamily: 'inherit' },
        colors: ['#5d87ff'],
        xaxis: { categories: Object.keys(seoDist), labels: { style: { colors: textColor } } },
        yaxis: { labels: { style: { colors: textColor } } },
        grid: { borderColor: gridColor, strokeDashArray: 4 },
        dataLabels: { enabled: false },
        plotOptions: { bar: { borderRadius: 4 } },
        tooltip: { theme: isDark ? 'dark' : 'light' },
    }).render();

    var qualityDist = @json($qualityDistribution);
    new ApexCharts(document.querySelector('#chart-quality-dist'), {
        series: [{ name: 'Jobs', data: Object.values(qualityDist) }],
        chart: { type: 'bar', height: 250, toolbar: { show: false }, fontFamily: 'inherit' },
        colors: ['#ffae1f'],
        xaxis: { categories: Object.keys(qualityDist), labels: { style: { colors: textColor } } },
        yaxis: { labels: { style: { colors: textColor } } },
        grid: { borderColor: gridColor, strokeDashArray: 4 },
        dataLabels: { enabled: false },
        plotOptions: { bar: { borderRadius: 4 } },
        tooltip: { theme: isDark ? 'dark' : 'light' },
    }).render();

    new ApexCharts(document.querySelector('#chart-indexed-trend'), {
        series: [{ name: 'Indexed', data: @json($indexedValues) }],
        chart: { type: 'line', height: 250, toolbar: { show: false }, fontFamily: 'inherit' },
        colors: ['#13deb9'],
        stroke: { curve: 'smooth', width: 2 },
        xaxis: { categories: @json($indexedDates), labels: { style: { colors: textColor } }, tickAmount: 7, axisBorder: { show: false } },
        yaxis: { labels: { style: { colors: textColor } } },
        grid: { borderColor: gridColor, strokeDashArray: 4 },
        dataLabels: { enabled: false },
        markers: { size: 3 },
        tooltip: { theme: isDark ? 'dark' : 'light' },
    }).render();

    new ApexCharts(document.querySelector('#chart-impressions-clicks'), {
        series: [
            { name: 'Impressions', data: @json($impValues) },
            { name: 'Clicks',      data: @json($clickValues) },
        ],
        chart: { type: 'area', height: 250, toolbar: { show: false }, fontFamily: 'inherit' },
        colors: ['#5d87ff','#13deb9'],
        fill: { type: 'gradient', gradient: { opacityFrom: 0.3, opacityTo: 0.05 } },
        stroke: { curve: 'smooth', width: 2 },
        xaxis: { categories: @json($impDates), labels: { style: { colors: textColor } }, tickAmount: 7, axisBorder: { show: false } },
        yaxis: { labels: { style: { colors: textColor } } },
        grid: { borderColor: gridColor, strokeDashArray: 4 },
        dataLabels: { enabled: false },
        legend: { labels: { colors: textColor } },
        tooltip: { theme: isDark ? 'dark' : 'light' },
    }).render();

    var blogSeo = @json($blogSeoDistribution);
    new ApexCharts(document.querySelector('#chart-blog-seo-dist'), {
        series: [{ name: 'Blogs', data: Object.values(blogSeo) }],
        chart: { type: 'bar', height: 280, toolbar: { show: false }, fontFamily: 'inherit' },
        colors: ['#49beff'],
        xaxis: { categories: Object.keys(blogSeo), labels: { style: { colors: textColor } } },
        yaxis: { labels: { style: { colors: textColor } } },
        grid: { borderColor: gridColor, strokeDashArray: 4 },
        dataLabels: { enabled: false },
        plotOptions: { bar: { borderRadius: 4 } },
        tooltip: { theme: isDark ? 'dark' : 'light' },
    }).render();

});
</script>
@endpush
