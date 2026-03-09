{{-- resources/views/settings/artisan/index.blade.php --}}

@extends('layouts.app')
@section('title', __('Run Artisan Commands'))

@section('app-content')
<div class="body-wrapper">
    <div class="container-fluid">

        {{-- Page Header --}}
        <div class="row">
            <div class="col-12">
                <div class="page-title-box d-flex align-items-center justify-content-between py-3 mb-4">
                    <h4 class="mb-0 fw-bold">Artisan Command Runner</h4>
                    <div class="page-title-right">
                        <ol class="breadcrumb m-0">
                            <li class="breadcrumb-item"><a href="{{ url('/') }}">Home</a></li>
                            <li class="breadcrumb-item">Admin</li>
                            <li class="breadcrumb-item active">Artisan Runner</li>
                        </ol>
                    </div>
                </div>
            </div>
        </div>

        {{-- Warning Alert --}}
        <div class="row">
            <div class="col-12">
                <div class="alert alert-warning alert-dismissible border-0 d-flex align-items-center gap-3 mb-4" role="alert">
                    <i class="ti ti-alert-triangle fs-6"></i>
                    <div>
                        <strong>Super Admin Use Only.</strong>
                        Only whitelisted commands are available. Use carefully in production.
                    </div>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            </div>
        </div>

        {{-- Main Content Row --}}
        <div class="row">

            {{-- Command Form --}}
            <div class="col-xl-5 col-lg-5">
                <div class="card h-100">
                    <div class="card-header d-flex align-items-center">
                        <h5 class="card-title fw-semibold mb-0">
                            <i class="ti ti-terminal me-2 text-primary"></i>
                            Run Command
                        </h5>
                        <span class="badge bg-primary-subtle text-primary ms-auto px-3 py-2 rounded-pill fs-2">
                            Laravel CLI
                        </span>
                    </div>
                    <div class="card-body">

                        {{-- Command Select --}}
                        <div class="mb-4">
                            <label class="form-label fw-semibold" for="artisan_command">
                                Select Command <span class="text-danger">*</span>
                            </label>
                            <select id="artisan_command" class="form-select" onchange="artisanSelectChange(this.value)">
                                <option value="">-- Choose a command --</option>
                                @foreach($commands as $cmd => $description)
                                    <option value="{{ $cmd }}">php artisan {{ $cmd }}</option>
                                @endforeach
                            </select>
                        </div>

                        {{-- Description Box --}}
                        <div id="command_description" class="mb-4 d-none">
                            <div class="p-3 bg-light-subtle border border-info-subtle rounded d-flex align-items-start gap-3">
                                <i class="ti ti-info-circle text-info fs-6 mt-1"></i>
                                <div>
                                    <div class="text-muted fs-2 fw-semibold mb-1">Description</div>
                                    <div id="description_text" class="fw-semibold text-dark fs-3"></div>
                                </div>
                            </div>
                        </div>

                        {{-- Run Button — never disabled --}}
                        <button id="run_btn" class="btn btn-primary w-100 py-2" onclick="artisanRun()">
                            <span id="btn_label">
                                <i class="ti ti-rocket me-2"></i>Run Command
                            </span>
                            <span id="btn_loading" class="d-none">
                                <div class="spinner-border spinner-border-sm me-2" role="status">
                                    <span class="visually-hidden">Loading...</span>
                                </div>
                                Please wait...
                            </span>
                        </button>

                    </div>
                </div>
            </div>

            {{-- Terminal Output --}}
            <div class="col-xl-7 col-lg-7">
                <div class="card h-100">
                    <div class="card-header d-flex align-items-center">
                        <h5 class="card-title fw-semibold mb-0">
                            <i class="ti ti-device-desktop-code me-2 text-primary"></i>
                            Output
                        </h5>
                        <button id="clear_output" class="btn btn-sm btn-danger ms-auto" onclick="artisanClear()">
                            <i class="ti ti-trash me-1"></i> Clear
                        </button>
                    </div>
                    <div class="card-body p-0">

                        {{-- Terminal --}}
                        <div class="rounded-bottom overflow-hidden" style="min-height: 400px; background: #1e1e2e;">

                            {{-- Terminal Top Bar --}}
                            <div class="d-flex align-items-center px-4 py-2" style="background: #2a2a3e; border-bottom: 1px solid #313244;">
                                <div class="d-flex gap-2 me-3">
                                    <span class="rounded-circle" style="width:12px;height:12px;background:#ff5f57;display:inline-block;"></span>
                                    <span class="rounded-circle" style="width:12px;height:12px;background:#febc2e;display:inline-block;"></span>
                                    <span class="rounded-circle" style="width:12px;height:12px;background:#28c840;display:inline-block;"></span>
                                </div>
                                <span id="terminal_title" style="color:#6c7086; font-size:12px; font-family: monospace;">
                                    bash — artisan runner
                                </span>
                            </div>

                            {{-- Terminal Body --}}
                            <div id="terminal_output"
                                 class="p-4"
                                 style="font-family: 'Courier New', monospace; font-size: 13px; color: #cdd6f4; min-height: 350px; white-space: pre-wrap; word-break: break-word;">
                                <span style="color:#6c7086;">$ Waiting for command...</span>
                            </div>
                        </div>

                    </div>

                    {{-- Status Badge --}}
                    <div id="status_badge" class="card-footer bg-transparent border-top d-none">
                        <!-- dynamic -->
                    </div>

                </div>
            </div>

        </div>
    </div>
</div>

<script>
    /* Global functions called via inline onclick — no DOMContentLoaded needed */
    const ARTISAN_COMMANDS = @json($commands);
    const ARTISAN_RUN_URL  = "{{ route('artisan.run') }}";
    const ARTISAN_CSRF     = "{{ csrf_token() }}";

    function artisanEscape(str) {
        return String(str ?? '').replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;');
    }
    function artisanWrite(html) {
        document.getElementById('terminal_output').innerHTML = html;
    }
    function artisanStatus(success, message) {
        const badge = document.getElementById('status_badge');
        badge.classList.remove('d-none');
        badge.innerHTML = success
            ? `<span class="badge bg-success-subtle text-success px-3 py-2 fs-2"><i class="ti ti-circle-check me-1"></i> ${artisanEscape(message)}</span>`
            : `<span class="badge bg-danger-subtle text-danger px-3 py-2 fs-2"><i class="ti ti-circle-x me-1"></i> ${artisanEscape(message)}</span>`;
    }
    function artisanSelectChange(val) {
        const descBox  = document.getElementById('command_description');
        const descText = document.getElementById('description_text');
        if (val) { descText.textContent = ARTISAN_COMMANDS[val] ?? ''; descBox.classList.remove('d-none'); }
        else { descBox.classList.add('d-none'); }
    }
    function artisanRun() {
        const command = document.getElementById('artisan_command').value;
        if (!command) { artisanWrite('<span style="color:#f38ba8;">⚠ Please select a command first.</span>'); return; }
        document.getElementById('btn_label').classList.add('d-none');
        document.getElementById('btn_loading').classList.remove('d-none');
        document.getElementById('terminal_title').textContent = 'running: php artisan ' + command;
        document.getElementById('status_badge').classList.add('d-none');
        artisanWrite(`<span style="color:#89b4fa;">$ php artisan ${artisanEscape(command)}</span>\n<span style="color:#6c7086;">Running…</span>`);
        fetch(ARTISAN_RUN_URL, {
            method: 'POST',
            headers: { 'Content-Type':'application/json','Accept':'application/json','X-CSRF-TOKEN':ARTISAN_CSRF,'X-Requested-With':'XMLHttpRequest' },
            body: JSON.stringify({ command })
        })
        .then(async res => {
            const raw = await res.text();
            let data;
            try { data = JSON.parse(raw); } catch(_) { throw new Error(`HTTP ${res.status} — not JSON:\n${raw.substring(0,300)}`); }
            if (!res.ok) throw new Error(data.message ?? `HTTP ${res.status}`);
            return data;
        })
        .then(data => {
            artisanWrite(`<span style="color:#89b4fa;">$ php artisan ${artisanEscape(command)}</span>\n\n<span style="color:${data.success?'#a6e3a1':'#f38ba8'};">${artisanEscape(data.output??'')}</span>`);
            document.getElementById('terminal_title').textContent = `${data.success?'✅':'❌'} bash — php artisan ${command}`;
            artisanStatus(data.success, data.success ? 'Command completed successfully' : 'Command failed');
            if (typeof window.showToast === 'function') window.showToast(data.success?'success':'error', data.success?'Command completed':'Command failed', (data.output??'').substring(0,120));
        })
        .catch(err => {
            artisanWrite(`<span style="color:#89b4fa;">$ php artisan ${artisanEscape(command)}</span>\n\n<span style="color:#f38ba8;">❌ ${artisanEscape(err.message)}</span>`);
            document.getElementById('terminal_title').textContent = `❌ bash — php artisan ${command}`;
            artisanStatus(false, 'Request failed — see terminal');
            if (typeof window.showToast === 'function') window.showToast('error', 'Request failed', err.message);
        })
        .finally(() => {
            document.getElementById('btn_label').classList.remove('d-none');
            document.getElementById('btn_loading').classList.add('d-none');
        });
    }
    function artisanClear() {
        artisanWrite('<span style="color:#6c7086;">$ Waiting for command...</span>');
        document.getElementById('terminal_title').textContent = 'bash — artisan runner';
        document.getElementById('status_badge').classList.add('d-none');
    }
</script>
@endsection