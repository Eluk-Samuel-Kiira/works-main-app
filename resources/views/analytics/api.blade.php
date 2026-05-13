@extends('layouts.app')
@section('title', 'API Analytics – Stardena Works')

@section('app-content')
<div class="body-wrapper">
<div class="container-fluid">

    <div class="font-weight-medium shadow-none position-relative overflow-hidden mb-4">
        <div class="card-body px-0">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <h4 class="font-weight-medium mb-0">API Usage Analytics</h4>
                    <nav aria-label="breadcrumb"><ol class="breadcrumb">
                        <li class="breadcrumb-item"><a class="text-muted text-decoration-none" href="{{ route('dashboard') }}">Home</a></li>
                        <li class="breadcrumb-item"><a class="text-muted text-decoration-none" href="{{ route('analytics.dashboard') }}">Analytics</a></li>
                        <li class="breadcrumb-item text-muted" aria-current="page">API Usage</li>
                    </ol></nav>
                </div>
            </div>
        </div>
    </div>

    @include('analytics.partials.date-range-filter')

    <div class="row g-3 mb-4">
        @foreach([
            ['label'=>'Total Calls',    'value'=>number_format($kpis['total_calls']),  'icon'=>'solar:server-square-linear', 'color'=>'primary'],
            ['label'=>'Successful',     'value'=>number_format($kpis['successful']),   'icon'=>'solar:check-circle-linear',  'color'=>'success'],
            ['label'=>'Failed',         'value'=>number_format($kpis['failed']),       'icon'=>'solar:close-circle-linear',  'color'=>'danger'],
            ['label'=>'Success Rate',   'value'=>$kpis['success_rate'].'%',           'icon'=>'solar:chart-2-linear',       'color'=>'info'],
            ['label'=>'Avg Duration',   'value'=>$kpis['avg_duration'].'ms',          'icon'=>'solar:stopwatch-linear',     'color'=>'warning'],
            ['label'=>'Max Duration',   'value'=>$kpis['max_duration'].'ms',          'icon'=>'solar:alarm-linear',         'color'=>'danger'],
            ['label'=>'Total API Keys', 'value'=>number_format($kpis['total_keys']),  'icon'=>'solar:key-linear',           'color'=>'secondary'],
            ['label'=>'Active Keys',    'value'=>number_format($kpis['active_keys']), 'icon'=>'solar:key-bold',             'color'=>'success'],
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
                    <h4 class="card-title mb-0">API Call Volume Over Time</h4>
                    <div id="chart-calls-trend" class="mt-3"></div>
                </div>
            </div>
        </div>
        <div class="col-lg-4">
            <div class="card h-100">
                <div class="card-body">
                    <h4 class="card-title mb-0">Calls by Service</h4>
                    <div id="chart-by-service" class="mt-3"></div>
                </div>
            </div>
        </div>
    </div>

    <div class="row g-4 mb-4">
        <div class="col-lg-6">
            <div class="card">
                <div class="card-body">
                    <h4 class="card-title mb-3">Service Performance</h4>
                    <div class="table-responsive">
                        <table class="table table-hover align-middle">
                            <thead class="table-light"><tr><th>Service</th><th>Calls</th><th>Success Rate</th><th>Avg Duration</th></tr></thead>
                            <tbody>
                                @forelse($byService as $svc)
                                <tr>
                                    <td class="fw-medium text-capitalize">{{ $svc['service'] }}</td>
                                    <td><span class="badge bg-primary-subtle text-primary">{{ number_format($svc['count']) }}</span></td>
                                    <td>
                                        <div class="d-flex align-items-center gap-2">
                                            <div class="progress flex-grow-1" style="height:5px;">
                                                <div class="progress-bar {{ $svc['success_rate'] >= 90 ? 'bg-success' : ($svc['success_rate'] >= 70 ? 'bg-warning' : 'bg-danger') }}"
                                                     style="width:{{ $svc['success_rate'] }}%"></div>
                                            </div>
                                            <small class="text-muted">{{ $svc['success_rate'] }}%</small>
                                        </div>
                                    </td>
                                    <td class="text-muted fs-3">{{ $svc['avg_duration'] }}ms</td>
                                </tr>
                                @empty
                                <tr><td colspan="4" class="text-center text-muted">No API logs found</td></tr>
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
                    <h4 class="card-title mb-3">Top Endpoints by Call Count</h4>
                    <div class="table-responsive">
                        <table class="table table-sm table-hover">
                            <thead><tr><th>Endpoint</th><th>Method</th><th>Calls</th><th>Errors</th><th>Avg ms</th></tr></thead>
                            <tbody>
                                @forelse($topEndpoints as $ep)
                                <tr>
                                    <td class="fs-3 text-truncate" style="max-width:180px;">{{ $ep->endpoint }}</td>
                                    <td><span class="badge bg-info-subtle text-info">{{ $ep->method }}</span></td>
                                    <td class="fw-medium">{{ number_format($ep->cnt) }}</td>
                                    <td>
                                        @if($ep->error_cnt > 0)
                                            <span class="badge bg-danger-subtle text-danger">{{ $ep->error_cnt }}</span>
                                        @else
                                            <span class="text-muted fs-3">0</span>
                                        @endif
                                    </td>
                                    <td class="text-muted fs-3">{{ round($ep->avg_dur, 0) }}</td>
                                </tr>
                                @empty
                                <tr><td colspan="5" class="text-center text-muted">No data</td></tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="row g-4 mb-4">
        <div class="col-lg-4">
            <div class="card">
                <div class="card-body">
                    <h4 class="card-title mb-3">Slowest Endpoints</h4>
                    <div class="table-responsive">
                        <table class="table table-sm">
                            <thead><tr><th>Endpoint</th><th>Avg ms</th></tr></thead>
                            <tbody>
                                @forelse($slowestEndpoints as $ep)
                                <tr>
                                    <td class="fs-3 text-truncate" style="max-width:200px;">{{ $ep->endpoint }}</td>
                                    <td><span class="badge bg-{{ round($ep->avg_dur)>2000?'danger':($ep->avg_dur>1000?'warning':'success') }}-subtle text-{{ round($ep->avg_dur)>2000?'danger':($ep->avg_dur>1000?'warning':'success') }}">{{ round($ep->avg_dur) }}ms</span></td>
                                </tr>
                                @empty
                                <tr><td colspan="2" class="text-center text-muted">No slow endpoints</td></tr>
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
                    <h4 class="card-title mb-3">Top Errors</h4>
                    @forelse($errorBreakdown as $err)
                    <div class="d-flex justify-content-between align-items-start mb-2 gap-2">
                        <span class="fs-3 text-truncate text-danger">{{ $err->error_message }}</span>
                        <span class="badge bg-danger-subtle text-danger flex-shrink-0">{{ $err->cnt }}</span>
                    </div>
                    @empty
                    <p class="text-success text-center">No errors! 🎉</p>
                    @endforelse
                </div>
            </div>
        </div>
        <div class="col-lg-4">
            <div class="card">
                <div class="card-body">
                    <h4 class="card-title mb-3">HTTP Response Codes</h4>
                    @foreach($responseCodes as $code => $count)
                    <div class="d-flex justify-content-between mb-2">
                        <span class="badge bg-{{ $code >= 500 ? 'danger' : ($code >= 400 ? 'warning' : ($code >= 200 ? 'success' : 'info')) }}-subtle text-{{ $code >= 500 ? 'danger' : ($code >= 400 ? 'warning' : ($code >= 200 ? 'success' : 'info')) }}">HTTP {{ $code }}</span>
                        <span class="fw-semibold">{{ number_format($count) }}</span>
                    </div>
                    @endforeach
                    @if(empty($responseCodes))
                    <p class="text-center text-muted">No data</p>
                    @endif
                </div>
            </div>
        </div>
    </div>

    {{-- API Key Status Table --}}
    <div class="row g-4">
        <div class="col-12">
            <div class="card">
                <div class="card-body">
                    <h4 class="card-title mb-3">API Key Status & Usage</h4>
                    <div class="table-responsive">
                        <table class="table table-hover align-middle">
                            <thead class="table-light">
                                <tr><th>Key Name</th><th>Service</th><th>Provider</th><th>Usage Count</th><th>Environment</th><th>Last Used</th><th>Status</th></tr>
                            </thead>
                            <tbody>
                                @forelse($apiKeyUsage as $key)
                                <tr>
                                    <td class="fw-medium">{{ $key->name }}</td>
                                    <td class="text-capitalize">{{ $key->service }}</td>
                                    <td class="text-muted fs-3 text-capitalize">{{ $key->provider ?? '–' }}</td>
                                    <td><span class="badge bg-primary-subtle text-primary">{{ number_format($key->usage_count) }}</span></td>
                                    <td>
                                        <span class="badge bg-{{ $key->environment === 'production' ? 'danger' : ($key->environment === 'staging' ? 'warning' : 'info') }}-subtle text-{{ $key->environment === 'production' ? 'danger' : ($key->environment === 'staging' ? 'warning' : 'info') }}">
                                            {{ $key->environment ?? 'N/A' }}
                                        </span>
                                    </td>
                                    <td class="text-muted fs-3">{{ $key->last_used_at ? \Carbon\Carbon::parse($key->last_used_at)->diffForHumans() : 'Never' }}</td>
                                    <td>
                                        @if($key->expires_at && \Carbon\Carbon::parse($key->expires_at)->isPast())
                                            <span class="badge bg-danger-subtle text-danger">Expired</span>
                                        @else
                                            <span class="badge bg-success-subtle text-success">Active</span>
                                        @endif
                                    </td>
                                </tr>
                                @empty
                                <tr><td colspan="7" class="text-center text-muted">No API keys found</td></tr>
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

    new ApexCharts(document.querySelector('#chart-calls-trend'), {
        series: [{ name: 'API Calls', data: @json($callsValues) }],
        chart: { type: 'bar', height: 270, toolbar: { show: false }, fontFamily: 'inherit' },
        colors: ['#5d87ff'],
        xaxis: { categories: @json($callsDates), labels: { style: { colors: textColor } }, tickAmount: 7, axisBorder: { show: false } },
        yaxis: { labels: { style: { colors: textColor } } },
        grid: { borderColor: gridColor, strokeDashArray: 4 },
        dataLabels: { enabled: false },
        plotOptions: { bar: { borderRadius: 3, columnWidth: '70%' } },
        tooltip: { theme: isDark ? 'dark' : 'light' },
    }).render();

    var svcData = @json($byService);
    new ApexCharts(document.querySelector('#chart-by-service'), {
        series: svcData.map(function(s){ return s.count; }),
        labels: svcData.map(function(s){ return s.service || 'Unknown'; }),
        chart: { type: 'donut', height: 270, fontFamily: 'inherit' },
        colors: ['#5d87ff','#13deb9','#ffae1f','#fa896b','#49beff','#ff6692'],
        legend: { position: 'bottom', labels: { colors: textColor } },
        plotOptions: { pie: { donut: { size: '65%' } } },
        dataLabels: { enabled: false },
        tooltip: { theme: isDark ? 'dark' : 'light' },
    }).render();

});
</script>
@endpush
