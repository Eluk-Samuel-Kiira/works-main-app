@php
    $currentRange = request('range', '30d');
    $baseUrl      = url()->current();
@endphp
<div class="card mb-4 border-0 shadow-none" style="background: var(--bs-body-bg);">
    <div class="card-body py-2 px-0">
        <div class="d-flex align-items-center gap-3 flex-wrap">
            <span class="text-muted fw-semibold fs-3 d-flex align-items-center gap-1">
                <iconify-icon icon="solar:calendar-linear"></iconify-icon> Period:
            </span>
            <div class="btn-group btn-group-sm" role="group">
                <a href="{{ $baseUrl }}?range=24h"
                   class="btn {{ $currentRange === '24h' ? 'btn-primary' : 'btn-outline-primary' }}">24h</a>
                <a href="{{ $baseUrl }}?range=7d"
                   class="btn {{ $currentRange === '7d'  ? 'btn-primary' : 'btn-outline-primary' }}">7 Days</a>
                <a href="{{ $baseUrl }}?range=30d"
                   class="btn {{ $currentRange === '30d' ? 'btn-primary' : 'btn-outline-primary' }}">30 Days</a>
                <a href="{{ $baseUrl }}?range=all"
                   class="btn {{ $currentRange === 'all' ? 'btn-primary' : 'btn-outline-primary' }}">All Time</a>
            </div>
            <form method="GET" action="{{ $baseUrl }}" class="d-flex align-items-center gap-2">
                <input type="hidden" name="range" value="custom">
                <input type="date" name="from" class="form-control form-control-sm" style="width:140px;"
                       value="{{ request('from', '') }}">
                <span class="text-muted">–</span>
                <input type="date" name="to" class="form-control form-control-sm" style="width:140px;"
                       value="{{ request('to', '') }}">
                <button type="submit"
                        class="btn btn-sm {{ $currentRange === 'custom' ? 'btn-secondary' : 'btn-outline-secondary' }}">
                    Apply
                </button>
            </form>
            @if($currentRange !== '30d')
                <a href="{{ $baseUrl }}?range=30d" class="btn btn-sm btn-light text-muted d-flex align-items-center gap-1">
                    <iconify-icon icon="solar:restart-linear"></iconify-icon> Reset
                </a>
            @endif
        </div>
    </div>
</div>
