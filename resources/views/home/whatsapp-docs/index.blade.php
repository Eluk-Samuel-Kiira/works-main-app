@extends('layouts.app')
@section('title', 'WhatsApp Job Links - Stardena Works')

@section('app-content')
<div class="body-wrapper">
    <div class="container-fluid">

        {{-- Page Header --}}
        <div class="font-weight-medium shadow-none position-relative overflow-hidden mb-4">
            <div class="card-body px-0 py-2">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h4 class="font-weight-medium mb-0 d-flex align-items-center gap-2">
                            <i class="ti ti-brand-whatsapp fs-4 text-success"></i>
                            WhatsApp Job Links
                        </h4>
                        <nav aria-label="breadcrumb">
                            <ol class="breadcrumb">
                                <li class="breadcrumb-item">
                                    <a class="text-muted text-decoration-none" href="{{ route('dashboard') }}">Home</a>
                                </li>
                                <li class="breadcrumb-item text-muted" aria-current="page">WhatsApp Docs</li>
                            </ol>
                        </nav>
                    </div>
                    <div>
                        <button class="btn btn-success btn-sm d-flex align-items-center gap-2" onclick="copyAllLinks()" id="copyAllBtn">
                            <i class="ti ti-copy fs-5"></i> Copy All
                        </button>
                    </div>
                </div>
            </div>
        </div>

        {{-- Filters --}}
        <div class="card mb-3">
            <div class="card-body py-2">
                <div class="row g-2 align-items-end">
                    <div class="col-md-3">
                        <label class="form-label small fw-semibold mb-1">Category <span class="text-muted fw-normal" id="selectedCategoryCount"></span></label>
                        <select id="filterCategory" class="form-select form-select-sm" onchange="loadJobs(); updateSelectedCount()">
                            <option value="">All Categories</option>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label small fw-semibold mb-1">Country</label>
                        <select id="filterCountry" class="form-select form-select-sm" onchange="loadJobs()">
                            <option value="">All Countries</option>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label small fw-semibold mb-1">Employment Type</label>
                        <select id="filterEmploymentType" class="form-select form-select-sm" onchange="loadJobs()">
                            <option value="">All Types</option>
                            <option value="full-time">Full Time</option>
                            <option value="part-time">Part Time</option>
                            <option value="contract">Contract</option>
                            <option value="internship">Internship</option>
                        </select>
                    </div>
                    <div class="col-md-2">
                        <label class="form-label small fw-semibold mb-1">Limit</label>
                        <select id="filterLimit" class="form-select form-select-sm" onchange="loadJobs()">
                            <option value="10">10 jobs</option>
                            <option value="25" selected>25 jobs</option>
                            <option value="50">50 jobs</option>
                            <option value="100">100 jobs</option>
                        </select>
                    </div>
                    <div class="col-md-1">
                        <button class="btn btn-sm btn-outline-secondary w-100" onclick="resetFilters()">
                            <i class="ti ti-refresh me-1"></i>Reset
                        </button>
                    </div>
                </div>
            </div>
        </div>

        {{-- Stats Row --}}
        <div class="row g-2 mb-3">
            <div class="col-md-3 col-6">
                <div class="card border-0 shadow-sm">
                    <div class="card-body py-2 px-3">
                        <div class="d-flex align-items-center gap-2">
                            <div class="rounded-circle bg-success bg-opacity-10 p-1">
                                <i class="ti ti-briefcase text-success fs-6"></i>
                            </div>
                            <div>
                                <div class="text-muted small">Total Jobs</div>
                                <div class="fw-bold fs-5" id="totalJobs">0</div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-3 col-6">
                <div class="card border-0 shadow-sm">
                    <div class="card-body py-2 px-3">
                        <div class="d-flex align-items-center gap-2">
                            <div class="rounded-circle bg-info bg-opacity-10 p-1">
                                <i class="ti ti-building text-info fs-6"></i>
                            </div>
                            <div>
                                <div class="text-muted small">Companies</div>
                                <div class="fw-bold fs-5" id="totalCompanies">0</div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-3 col-6">
                <div class="card border-0 shadow-sm">
                    <div class="card-body py-2 px-3">
                        <div class="d-flex align-items-center gap-2">
                            <div class="rounded-circle bg-warning bg-opacity-10 p-1">
                                <i class="ti ti-map-pin text-warning fs-6"></i>
                            </div>
                            <div>
                                <div class="text-muted small">Locations</div>
                                <div class="fw-bold fs-5" id="totalLocations">0</div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-3 col-6">
                <div class="card border-0 shadow-sm">
                    <div class="card-body py-2 px-3">
                        <div class="d-flex align-items-center gap-2">
                            <div class="rounded-circle bg-danger bg-opacity-10 p-1">
                                <i class="ti ti-calendar text-danger fs-6"></i>
                            </div>
                            <div>
                                <div class="text-muted small">Expiring Soon</div>
                                <div class="fw-bold fs-5" id="expiringSoon">0</div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        {{-- Generated Links Card --}}
        <div class="card border-0 shadow-sm">
            <div class="card-header bg-body border-bottom py-2">
                <div class="d-flex justify-content-between align-items-center">
                    <h6 class="mb-0 fw-semibold">
                        <i class="ti ti-brand-whatsapp text-success me-1"></i>
                        Generated Messages
                    </h6>
                    <div class="d-flex gap-1">
                        <button class="btn btn-sm btn-outline-success py-0 px-2" onclick="copyAllLinks()">
                            <i class="ti ti-copy me-1"></i>Copy All
                        </button>
                        <button class="btn btn-sm btn-outline-secondary py-0 px-2" onclick="downloadTextFile()">
                            <i class="ti ti-download me-1"></i>.txt
                        </button>
                    </div>
                </div>
            </div>
            <div class="card-body p-2">
                <div id="loadingSpinner" class="text-center py-4 d-none">
                    <div class="spinner-border text-success spinner-border-sm"></div>
                    <p class="text-muted small mt-2">Loading jobs...</p>
                </div>
                <div id="generatedContent" style="max-height: 500px; overflow-y: auto;">
                    <div class="text-center text-muted py-4">
                        <i class="ti ti-brand-whatsapp fs-2 mb-2 d-block opacity-25"></i>
                        <p class="small mb-2">Select filters and generate messages</p>
                        <button class="btn btn-success btn-sm" onclick="loadJobs()">
                            <i class="ti ti-sparkles me-1"></i>Generate
                        </button>
                    </div>
                </div>
            </div>
        </div>

    </div>
</div>

<style>
    .whatsapp-message {
        background: #f0fdf4;
        border-left: 3px solid #25D366;
        padding: 8px 12px;
        margin-bottom: 6px;
        border-radius: 6px;
        font-size: 12px;
        transition: all 0.2s;
    }
    .whatsapp-message:hover {
        background: #dcfce7;
    }
    .whatsapp-message pre {
        margin: 0;
        white-space: pre-wrap;
        font-family: inherit;
        font-size: 12px;
        line-height: 1.5;
    }
    .copy-job-btn {
        opacity: 0;
        transition: opacity 0.2s;
        padding: 2px 6px;
        font-size: 11px;
    }
    .whatsapp-message:hover .copy-job-btn {
        opacity: 1;
    }
    .job-header {
        margin-bottom: 6px;
    }
</style>

@include('home.whatsapp-docs.index-js')
@endsection