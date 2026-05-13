@extends('layouts.app')
@section('title', 'Notification Analytics – Stardena Works')

@section('app-content')
<div class="body-wrapper">
<div class="container-fluid">

    <div class="font-weight-medium shadow-none position-relative overflow-hidden mb-4">
        <div class="card-body px-0">
            <div>
                <h4 class="font-weight-medium mb-0">Notifications & Audit Analytics</h4>
                <nav aria-label="breadcrumb"><ol class="breadcrumb">
                    <li class="breadcrumb-item"><a class="text-muted text-decoration-none" href="{{ route('dashboard') }}">Home</a></li>
                    <li class="breadcrumb-item"><a class="text-muted text-decoration-none" href="{{ route('analytics.dashboard') }}">Analytics</a></li>
                    <li class="breadcrumb-item text-muted" aria-current="page">Notifications</li>
                </ol></nav>
            </div>
        </div>
    </div>

    @include('analytics.partials.date-range-filter')

    <div class="row g-3 mb-4">
        @foreach([
            ['label'=>'Total',             'value'=>number_format($kpis['total']),              'icon'=>'solar:bell-linear',           'color'=>'primary'],
            ['label'=>'Unread',            'value'=>number_format($kpis['unread']),             'icon'=>'solar:bell-bing-linear',      'color'=>'warning'],
            ['label'=>'Resolved',          'value'=>number_format($kpis['resolved']),           'icon'=>'solar:check-circle-linear',   'color'=>'success'],
            ['label'=>'Urgent Unresolved', 'value'=>number_format($kpis['unresolved_urgent']), 'icon'=>'solar:fire-linear',           'color'=>'danger'],
            ['label'=>'High Unresolved',   'value'=>number_format($kpis['unresolved_high']),   'icon'=>'solar:danger-triangle-linear','color'=>'warning'],
            ['label'=>'In Period',         'value'=>number_format($kpis['in_period']),          'icon'=>'solar:calendar-linear',      'color'=>'info'],
            ['label'=>'Avg Resolution',    'value'=>$kpis['avg_resolution_hours'].'h',          'icon'=>'solar:stopwatch-linear',     'color'=>'secondary'],
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
                    <h4 class="card-title mb-0">Notification Volume Over Time</h4>
                    <div id="chart-notif-trend" class="mt-3"></div>
                </div>
            </div>
        </div>
        <div class="col-lg-4">
            <div class="card h-100">
                <div class="card-body">
                    <h4 class="card-title mb-0">Status Breakdown</h4>
                    <div id="chart-notif-status" class="mt-3"></div>
                </div>
            </div>
        </div>
    </div>

    <div class="row g-4 mb-4">
        <div class="col-lg-4">
            <div class="card h-100">
                <div class="card-body">
                    <h4 class="card-title mb-0">Priority Breakdown</h4>
                    <div id="chart-notif-priority" class="mt-3"></div>
                </div>
            </div>
        </div>
        <div class="col-lg-4">
            <div class="card h-100">
                <div class="card-body">
                    <h4 class="card-title mb-0">Resolution Trend</h4>
                    <p class="card-subtitle text-muted">Notifications resolved per day</p>
                    <div id="chart-resolved-trend" class="mt-3"></div>
                </div>
            </div>
        </div>
        <div class="col-lg-4">
            <div class="card h-100">
                <div class="card-body">
                    <h4 class="card-title mb-0">Notification Types</h4>
                    <div class="mt-3">
                        @foreach($typeBreakdown as $type => $count)
                        <div class="d-flex justify-content-between mb-2">
                            <span class="fs-3 text-truncate" style="max-width:200px;">{{ $type ?: 'Unknown' }}</span>
                            <span class="badge bg-primary-subtle text-primary">{{ $count }}</span>
                        </div>
                        @endforeach
                        @if(empty($typeBreakdown))
                        <p class="text-center text-muted">No data</p>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- Unresolved Urgent --}}
    <div class="row g-4 mb-4">
        <div class="col-12">
            <div class="card">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <h4 class="card-title mb-0">Unresolved High-Priority Notifications</h4>
                        <span class="badge bg-danger-subtle text-danger">{{ $kpis['unresolved_urgent'] + $kpis['unresolved_high'] }} unresolved</span>
                    </div>
                    <div class="table-responsive">
                        <table class="table table-hover align-middle">
                            <thead class="table-light"><tr><th>Type</th><th>Title</th><th>Priority</th><th>Status</th><th>Created</th></tr></thead>
                            <tbody>
                                @forelse($unresolvedUrgent as $notif)
                                <tr>
                                    <td class="text-muted fs-3">{{ $notif->type ?? '–' }}</td>
                                    <td class="fw-medium">{{ Str::limit($notif->title ?? $notif->message ?? 'N/A', 60) }}</td>
                                    <td>
                                        <span class="badge bg-{{ $notif->priority === 'urgent' ? 'danger' : 'warning' }}-subtle text-{{ $notif->priority === 'urgent' ? 'danger' : 'warning' }}">
                                            {{ ucfirst($notif->priority) }}
                                        </span>
                                    </td>
                                    <td><span class="badge bg-warning-subtle text-warning">{{ ucfirst($notif->status) }}</span></td>
                                    <td class="text-muted fs-3">{{ \Carbon\Carbon::parse($notif->created_at)->diffForHumans() }}</td>
                                </tr>
                                @empty
                                <tr><td colspan="5" class="text-center text-success">No urgent unresolved notifications! ✓</td></tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- Audit Log Section --}}
    <div class="row g-3 mb-4">
        <div class="col-12"><h5 class="text-muted fw-semibold">Audit Log Analytics</h5></div>
        <div class="col-lg-3 col-md-6">
            <div class="card">
                <div class="card-body text-center">
                    <h3 class="mb-0 fw-bold text-primary">{{ number_format($auditKpis['total']) }}</h3>
                    <p class="text-muted mb-0 fs-3">Total Audit Entries</p>
                </div>
            </div>
        </div>
        <div class="col-lg-3 col-md-6">
            <div class="card">
                <div class="card-body text-center">
                    <h3 class="mb-0 fw-bold text-info">{{ number_format($auditKpis['in_period']) }}</h3>
                    <p class="text-muted mb-0 fs-3">In Selected Period</p>
                </div>
            </div>
        </div>
    </div>

    <div class="row g-4 mb-4">
        <div class="col-lg-4">
            <div class="card h-100">
                <div class="card-body">
                    <h4 class="card-title mb-3">Audit Actions</h4>
                    <div id="chart-audit-actions" class="mt-2"></div>
                </div>
            </div>
        </div>
        <div class="col-lg-4">
            <div class="card h-100">
                <div class="card-body">
                    <h4 class="card-title mb-3">Audit by Source</h4>
                    <div id="chart-audit-source" class="mt-2"></div>
                </div>
            </div>
        </div>
        <div class="col-lg-4">
            <div class="card h-100">
                <div class="card-body">
                    <h4 class="card-title mb-3">Most Active Users (Audit)</h4>
                    <div class="table-responsive">
                        <table class="table table-sm">
                            <thead><tr><th>User</th><th>Actions</th></tr></thead>
                            <tbody>
                                @forelse($auditTopUsers as $u)
                                <tr>
                                    <td>
                                        <p class="mb-0 fw-medium fs-3">{{ $u->first_name }} {{ $u->last_name }}</p>
                                        <small class="text-muted">{{ $u->email }}</small>
                                    </td>
                                    <td><span class="badge bg-primary-subtle text-primary">{{ $u->cnt }}</span></td>
                                </tr>
                                @empty
                                <tr><td colspan="2" class="text-center text-muted">No data</td></tr>
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

    new ApexCharts(document.querySelector('#chart-notif-trend'), {
        series: [
            { name: 'Created',  data: @json($notifValues) },
            { name: 'Resolved', data: @json($resolvedValues) },
        ],
        chart: { type: 'area', height: 270, toolbar: { show: false }, fontFamily: 'inherit' },
        colors: ['#5d87ff','#13deb9'],
        fill: { type: 'gradient', gradient: { opacityFrom: 0.3, opacityTo: 0.05 } },
        stroke: { curve: 'smooth', width: 2 },
        xaxis: { categories: @json($notifDates), labels: { style: { colors: textColor } }, tickAmount: 7, axisBorder: { show: false } },
        yaxis: { labels: { style: { colors: textColor } } },
        grid: { borderColor: gridColor, strokeDashArray: 4 },
        dataLabels: { enabled: false },
        legend: { labels: { colors: textColor } },
        tooltip: { theme: isDark ? 'dark' : 'light' },
    }).render();

    var statusData = @json($statusBreakdown);
    new ApexCharts(document.querySelector('#chart-notif-status'), {
        series: Object.values(statusData),
        labels: Object.keys(statusData).map(function(s){ return s.charAt(0).toUpperCase()+s.slice(1); }),
        chart: { type: 'donut', height: 250, fontFamily: 'inherit' },
        colors: ['#fa896b','#49beff','#13deb9','#adb5bd'],
        legend: { position: 'bottom', labels: { colors: textColor } },
        plotOptions: { pie: { donut: { size: '65%' } } },
        dataLabels: { enabled: false },
        tooltip: { theme: isDark ? 'dark' : 'light' },
    }).render();

    var priorityData = @json($priorityBreakdown);
    new ApexCharts(document.querySelector('#chart-notif-priority'), {
        series: Object.values(priorityData),
        labels: Object.keys(priorityData).map(function(s){ return s.charAt(0).toUpperCase()+s.slice(1); }),
        chart: { type: 'donut', height: 250, fontFamily: 'inherit' },
        colors: ['#adb5bd','#ffae1f','#fa896b','#ff3366'],
        legend: { position: 'bottom', labels: { colors: textColor } },
        plotOptions: { pie: { donut: { size: '65%' } } },
        dataLabels: { enabled: false },
        tooltip: { theme: isDark ? 'dark' : 'light' },
    }).render();

    new ApexCharts(document.querySelector('#chart-resolved-trend'), {
        series: [{ name: 'Resolved', data: @json($resolvedValues) }],
        chart: { type: 'line', height: 220, toolbar: { show: false }, fontFamily: 'inherit' },
        colors: ['#13deb9'],
        stroke: { curve: 'smooth', width: 2 },
        xaxis: { categories: @json($resolvedDates), labels: { style: { colors: textColor } }, tickAmount: 7, axisBorder: { show: false } },
        yaxis: { labels: { style: { colors: textColor } } },
        grid: { borderColor: gridColor, strokeDashArray: 4 },
        dataLabels: { enabled: false },
        markers: { size: 3 },
        tooltip: { theme: isDark ? 'dark' : 'light' },
    }).render();

    var actionsData = @json($auditByAction);
    new ApexCharts(document.querySelector('#chart-audit-actions'), {
        series: [{ name: 'Actions', data: Object.values(actionsData) }],
        chart: { type: 'bar', height: 250, toolbar: { show: false }, fontFamily: 'inherit' },
        colors: ['#49beff'],
        plotOptions: { bar: { horizontal: true, borderRadius: 4 } },
        xaxis: { categories: Object.keys(actionsData).map(function(a){ return a.charAt(0).toUpperCase()+a.slice(1); }), labels: { style: { colors: textColor } } },
        yaxis: { labels: { style: { colors: textColor } } },
        grid: { borderColor: gridColor, strokeDashArray: 4 },
        dataLabels: { enabled: false },
        tooltip: { theme: isDark ? 'dark' : 'light' },
    }).render();

    var sourceData = @json($auditBySource);
    new ApexCharts(document.querySelector('#chart-audit-source'), {
        series: Object.values(sourceData),
        labels: Object.keys(sourceData).map(function(s){ return s.charAt(0).toUpperCase()+s.slice(1); }),
        chart: { type: 'pie', height: 250, fontFamily: 'inherit' },
        colors: ['#5d87ff','#13deb9','#ffae1f','#fa896b'],
        legend: { position: 'bottom', labels: { colors: textColor } },
        dataLabels: { enabled: true, style: { colors: ['#fff'] } },
        tooltip: { theme: isDark ? 'dark' : 'light' },
    }).render();

});
</script>
@endpush
