@extends('layouts.app')
@section('title', 'Revenue Analytics – Stardena Works')

@section('app-content')
<div class="body-wrapper">
<div class="container-fluid">

    <div class="font-weight-medium shadow-none position-relative overflow-hidden mb-4">
        <div class="card-body px-0">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <h4 class="font-weight-medium mb-0">Revenue Analytics</h4>
                    <nav aria-label="breadcrumb"><ol class="breadcrumb">
                        <li class="breadcrumb-item"><a class="text-muted text-decoration-none" href="{{ route('dashboard') }}">Home</a></li>
                        <li class="breadcrumb-item"><a class="text-muted text-decoration-none" href="{{ route('analytics.dashboard') }}">Analytics</a></li>
                        <li class="breadcrumb-item text-muted" aria-current="page">Revenue</li>
                    </ol></nav>
                </div>
                <a href="{{ route('analytics.export.revenue', request()->query()) }}" class="btn btn-sm btn-outline-secondary d-flex align-items-center gap-1">
                    <iconify-icon icon="solar:download-linear"></iconify-icon> Export CSV
                </a>
            </div>
        </div>
    </div>

    @include('analytics.partials.date-range-filter')

    {{-- Revenue KPI Cards --}}
    <div class="row g-3 mb-4">
        <div class="col-lg-3 col-md-6">
            <div class="card bg-primary text-white">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-start">
                        <div>
                            <p class="mb-1 opacity-75 fs-3">Total Revenue</p>
                            <h3 class="mb-0 fw-bold">UGX {{ number_format($kpis['total_revenue']) }}</h3>
                            <small class="opacity-75">All time · net of fees</small>
                        </div>
                        <iconify-icon icon="solar:wallet-money-linear" class="fs-6 opacity-75"></iconify-icon>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-lg-3 col-md-6">
            <div class="card bg-success text-white">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-start">
                        <div>
                            <p class="mb-1 opacity-75 fs-3">This Month</p>
                            <h3 class="mb-0 fw-bold">UGX {{ number_format($kpis['month_revenue']) }}</h3>
                            <small class="opacity-75">Period revenue: UGX {{ number_format($kpis['period_revenue']) }}</small>
                        </div>
                        <iconify-icon icon="solar:chart-2-linear" class="fs-6 opacity-75"></iconify-icon>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-lg-3 col-md-6">
            <div class="card">
                <div class="card-body">
                    <p class="mb-1 text-muted fs-3">Transaction Success Rate</p>
                    <h3 class="mb-0 fw-bold text-success">{{ $kpis['success_rate'] }}%</h3>
                    <small class="text-muted">{{ number_format($kpis['successful']) }} / {{ number_format($kpis['total_count']) }} transactions</small>
                </div>
            </div>
        </div>
        <div class="col-lg-3 col-md-6">
            <div class="card">
                <div class="card-body">
                    <p class="mb-1 text-muted fs-3">Avg Transaction Value</p>
                    <h3 class="mb-0 fw-bold">UGX {{ number_format($kpis['avg_transaction']) }}</h3>
                    <small class="text-muted">Total fees: UGX {{ number_format($kpis['total_fees']) }}</small>
                </div>
            </div>
        </div>
    </div>

    {{-- Secondary KPIs --}}
    <div class="row g-3 mb-4">
        @foreach([
            ['label'=>'Successful','value'=>$kpis['successful'],'color'=>'success'],
            ['label'=>'Pending',   'value'=>$kpis['pending'],   'color'=>'warning'],
            ['label'=>'Failed',    'value'=>$kpis['failed'],    'color'=>'danger'],
            ['label'=>'Refunded',  'value'=>$kpis['refunded'],  'color'=>'info'],
            ['label'=>'Disputed',  'value'=>$kpis['disputed'],  'color'=>'dark'],
            ['label'=>'Cancelled', 'value'=>$kpis['cancelled'], 'color'=>'secondary'],
            ['label'=>'Flagged',   'value'=>$kpis['flagged'],   'color'=>'danger'],
        ] as $s)
        <div class="col">
            <div class="card text-center">
                <div class="card-body py-3">
                    <h5 class="mb-0 fw-bold text-{{ $s['color'] }}">{{ number_format($s['value']) }}</h5>
                    <p class="mb-0 text-muted fs-2">{{ $s['label'] }}</p>
                </div>
            </div>
        </div>
        @endforeach
    </div>

    {{-- Revenue Trend + Transaction Volume --}}
    <div class="row g-4 mb-4">
        <div class="col-lg-8">
            <div class="card h-100">
                <div class="card-body">
                    <h4 class="card-title mb-0">Revenue Over Time</h4>
                    <p class="card-subtitle text-muted">Net revenue from successful transactions</p>
                    <div id="chart-revenue-trend" class="mt-3"></div>
                </div>
            </div>
        </div>
        <div class="col-lg-4">
            <div class="card h-100">
                <div class="card-body">
                    <h4 class="card-title mb-0">Transaction Status</h4>
                    <p class="card-subtitle text-muted">All-time breakdown</p>
                    <div id="chart-tx-status" class="mt-2"></div>
                </div>
            </div>
        </div>
    </div>

    {{-- Monthly Comparison + Revenue by Gateway --}}
    <div class="row g-4 mb-4">
        <div class="col-lg-6">
            <div class="card h-100">
                <div class="card-body">
                    <h4 class="card-title mb-0">Monthly Revenue (Last 6 Months)</h4>
                    <div id="chart-monthly-revenue" class="mt-3"></div>
                </div>
            </div>
        </div>
        <div class="col-lg-6">
            <div class="card h-100">
                <div class="card-body">
                    <h4 class="card-title mb-0">Revenue by Payment Gateway</h4>
                    <div id="chart-by-gateway" class="mt-3"></div>
                    <div class="mt-3">
                        @foreach($revenueByGateway as $gw)
                        <div class="d-flex justify-content-between mb-1">
                            <span class="fs-3 text-capitalize">{{ $gw['gateway'] }}</span>
                            <div class="text-end">
                                <span class="fw-semibold fs-3">UGX {{ number_format($gw['total']) }}</span>
                                <small class="text-muted ms-2">{{ $gw['count'] }} txns</small>
                            </div>
                        </div>
                        @endforeach
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- Plan Type + Method --}}
    <div class="row g-4 mb-4">
        <div class="col-lg-6">
            <div class="card h-100">
                <div class="card-body">
                    <h4 class="card-title mb-0">Revenue by Plan Type</h4>
                    <div id="chart-by-plan-type" class="mt-3"></div>
                </div>
            </div>
        </div>
        <div class="col-lg-6">
            <div class="card h-100">
                <div class="card-body">
                    <h4 class="card-title mb-0">Revenue by Payment Method</h4>
                    <div id="chart-by-method" class="mt-3"></div>
                </div>
            </div>
        </div>
    </div>

    {{-- Top Plans Table --}}
    <div class="row g-4 mb-4">
        <div class="col-12">
            <div class="card">
                <div class="card-body">
                    <h4 class="card-title mb-3">Top Payment Plans by Revenue</h4>
                    <div class="table-responsive">
                        <table class="table table-hover align-middle">
                            <thead class="table-light">
                                <tr><th>Plan</th><th>Type</th><th>Price</th><th>Transactions</th><th>Total Revenue</th><th>Duration</th></tr>
                            </thead>
                            <tbody>
                                @forelse($topPlans as $plan)
                                <tr>
                                    <td class="fw-medium">{{ $plan->name }}</td>
                                    <td><span class="badge bg-primary-subtle text-primary">{{ $plan->type }}</span></td>
                                    <td>{{ $plan->currency }} {{ number_format($plan->amount) }}</td>
                                    <td>{{ number_format($plan->txn_count) }}</td>
                                    <td class="fw-semibold text-success">UGX {{ number_format($plan->revenue ?? 0) }}</td>
                                    <td class="text-muted fs-3">{{ $plan->duration_days }} days</td>
                                </tr>
                                @empty
                                <tr><td colspan="6" class="text-center text-muted">No payment plans found</td></tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- Failure Reasons + Flagged Transactions --}}
    <div class="row g-4">
        <div class="col-lg-5">
            <div class="card">
                <div class="card-body">
                    <h4 class="card-title mb-3">Top Failure Reasons</h4>
                    @forelse($failureReasons as $reason => $count)
                    <div class="d-flex justify-content-between align-items-center mb-2">
                        <span class="fs-3 text-truncate" style="max-width:280px;">{{ $reason }}</span>
                        <span class="badge bg-danger-subtle text-danger">{{ $count }}</span>
                    </div>
                    @empty
                    <p class="text-muted text-center">No failure data</p>
                    @endforelse
                </div>
            </div>
        </div>
        <div class="col-lg-7">
            <div class="card">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <h4 class="card-title mb-0">Flagged Transactions</h4>
                        <span class="badge bg-danger-subtle text-danger">{{ $kpis['flagged'] }} flagged</span>
                    </div>
                    <div class="table-responsive">
                        <table class="table table-sm table-hover">
                            <thead><tr><th>Reference</th><th>Customer</th><th>Amount</th><th>Status</th><th>Reason</th><th>Date</th></tr></thead>
                            <tbody>
                                @forelse($flaggedTransactions as $tx)
                                <tr>
                                    <td class="fs-3 text-muted">{{ $tx->reference }}</td>
                                    <td class="fs-3">{{ $tx->customer_name ?? '–' }}</td>
                                    <td>{{ $tx->currency }} {{ number_format($tx->amount) }}</td>
                                    <td><span class="badge bg-warning-subtle text-warning">{{ $tx->status }}</span></td>
                                    <td class="fs-3 text-truncate" style="max-width:120px;">{{ $tx->flag_reason ?? '–' }}</td>
                                    <td class="fs-3 text-muted">{{ \Carbon\Carbon::parse($tx->created_at)->format('M d') }}</td>
                                </tr>
                                @empty
                                <tr><td colspan="6" class="text-center text-muted">No flagged transactions</td></tr>
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

    var revDates  = @json($revTrendDates);
    var revValues = @json($revTrendValues);

    new ApexCharts(document.querySelector('#chart-revenue-trend'), {
        series: [{ name: 'Revenue (UGX)', data: revValues }],
        chart: { type: 'area', height: 280, toolbar: { show: false }, fontFamily: 'inherit' },
        colors: ['#13deb9'],
        fill: { type: 'gradient', gradient: { opacityFrom: 0.4, opacityTo: 0.05 } },
        stroke: { curve: 'smooth', width: 2 },
        xaxis: { categories: revDates, labels: { style: { colors: textColor } }, tickAmount: 7, axisBorder: { show: false } },
        yaxis: { labels: { style: { colors: textColor }, formatter: function(v){ return 'UGX '+v.toLocaleString(); } } },
        grid: { borderColor: gridColor, strokeDashArray: 4 },
        dataLabels: { enabled: false },
        tooltip: { theme: isDark ? 'dark' : 'light', y: { formatter: function(v){ return 'UGX '+v.toLocaleString(); } } },
    }).render();

    var statusData = @json($statusBreakdown);
    new ApexCharts(document.querySelector('#chart-tx-status'), {
        series: Object.values(statusData),
        labels: Object.keys(statusData).map(function(s){ return s.charAt(0).toUpperCase()+s.slice(1); }),
        chart: { type: 'donut', height: 280, fontFamily: 'inherit' },
        colors: ['#13deb9','#ffae1f','#fa896b','#49beff','#adb5bd','#5d87ff','#ff6692'],
        legend: { position: 'bottom', labels: { colors: textColor } },
        plotOptions: { pie: { donut: { size: '65%' } } },
        dataLabels: { enabled: false },
        tooltip: { theme: isDark ? 'dark' : 'light' },
    }).render();

    var monthLabels = @json($monthlyLabels);
    var monthValues = @json($monthlyValues);

    new ApexCharts(document.querySelector('#chart-monthly-revenue'), {
        series: [{ name: 'Revenue', data: monthValues }],
        chart: { type: 'bar', height: 280, toolbar: { show: false }, fontFamily: 'inherit' },
        colors: ['#5d87ff'],
        xaxis: { categories: monthLabels, labels: { style: { colors: textColor } } },
        yaxis: { labels: { style: { colors: textColor }, formatter: function(v){ return 'UGX '+Number(v).toLocaleString(); } } },
        grid: { borderColor: gridColor, strokeDashArray: 4 },
        dataLabels: { enabled: false },
        plotOptions: { bar: { borderRadius: 4 } },
        tooltip: { theme: isDark ? 'dark' : 'light', y: { formatter: function(v){ return 'UGX '+Number(v).toLocaleString(); } } },
    }).render();

    var gwData = @json($revenueByGateway);
    new ApexCharts(document.querySelector('#chart-by-gateway'), {
        series: gwData.map(function(g){ return parseFloat(g.total); }),
        labels: gwData.map(function(g){ return g.gateway ? g.gateway.charAt(0).toUpperCase()+g.gateway.slice(1) : 'Unknown'; }),
        chart: { type: 'pie', height: 200, fontFamily: 'inherit' },
        colors: ['#5d87ff','#13deb9','#ffae1f','#fa896b'],
        legend: { position: 'bottom', labels: { colors: textColor } },
        dataLabels: { enabled: true, style: { colors: ['#fff'] } },
        tooltip: { theme: isDark ? 'dark' : 'light', y: { formatter: function(v){ return 'UGX '+Number(v).toLocaleString(); } } },
    }).render();

    var ptData = @json($revenueByPlanType);
    new ApexCharts(document.querySelector('#chart-by-plan-type'), {
        series: ptData.map(function(p){ return parseFloat(p.total); }),
        labels: ptData.map(function(p){ return (p.type||'').replace(/_/g,' ').replace(/\b\w/g,function(c){ return c.toUpperCase(); }); }),
        chart: { type: 'donut', height: 280, fontFamily: 'inherit' },
        colors: ['#5d87ff','#13deb9','#ffae1f','#fa896b'],
        legend: { position: 'bottom', labels: { colors: textColor } },
        plotOptions: { pie: { donut: { size: '65%' } } },
        dataLabels: { enabled: false },
        tooltip: { theme: isDark ? 'dark' : 'light', y: { formatter: function(v){ return 'UGX '+Number(v).toLocaleString(); } } },
    }).render();

    var methodData = @json($revenueByMethod);
    new ApexCharts(document.querySelector('#chart-by-method'), {
        series: [{ name: 'Revenue', data: methodData.map(function(m){ return parseFloat(m.total); }) }],
        chart: { type: 'bar', height: 280, toolbar: { show: false }, fontFamily: 'inherit' },
        colors: ['#49beff'],
        plotOptions: { bar: { horizontal: true, borderRadius: 4 } },
        xaxis: { categories: methodData.map(function(m){ return (m.method||'Unknown').replace(/_/g,' ').replace(/\b\w/g,function(c){ return c.toUpperCase(); }); }), labels: { style: { colors: textColor } } },
        yaxis: { labels: { style: { colors: textColor } } },
        grid: { borderColor: gridColor, strokeDashArray: 4 },
        dataLabels: { enabled: false },
        tooltip: { theme: isDark ? 'dark' : 'light', y: { formatter: function(v){ return 'UGX '+Number(v).toLocaleString(); } } },
    }).render();

});
</script>
@endpush
