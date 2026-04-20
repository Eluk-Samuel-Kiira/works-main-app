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
                        <button class="btn btn-success btn-sm d-flex align-items-center gap-2" onclick="copyAllBatches()" id="copyAllBtn">
                            <i class="ti ti-copy fs-5"></i> Copy All Batches
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
                        <label class="form-label small fw-semibold mb-1">Category</label>
                        <select id="filterCategory" class="form-select form-select-sm" onchange="loadJobs()">
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
                        <label class="form-label small fw-semibold mb-1">Posted Date</label>
                        <select id="filterDateRange" class="form-select form-select-sm" onchange="loadJobs()">
                            <option value="">All Dates</option>
                            <option value="today">Today</option>
                            <option value="yesterday">Yesterday</option>
                            <option value="2days">2 Days Ago</option>
                            <option value="3days">3 Days Ago</option>
                            <option value="this_week">This Week</option>
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
                        Job Batches (10 jobs per batch)
                    </h6>
                    <div class="d-flex gap-1">
                        <button class="btn btn-sm btn-outline-success py-0 px-2" onclick="copyAllBatches()">
                            <i class="ti ti-copy me-1"></i>Copy All Batches
                        </button>
                        <button class="btn btn-sm btn-outline-secondary py-0 px-2" onclick="downloadTextFile()">
                            <i class="ti ti-download me-1"></i>.txt
                        </button>
                    </div>
                </div>
            </div>
            <div class="card-body p-3">
                <div id="loadingSpinner" class="text-center py-4 d-none">
                    <div class="spinner-border text-success spinner-border-sm"></div>
                    <p class="text-muted small mt-2">Loading jobs...</p>
                </div>
                <div id="generatedContent">
                    <div class="text-center text-muted py-4">
                        <i class="ti ti-brand-whatsapp fs-2 mb-2 d-block opacity-25"></i>
                        <p class="small mb-2">Select filters and generate messages</p>
                        <button class="btn btn-success btn-sm" onclick="loadJobs()">
                            <i class="ti ti-sparkles me-1"></i>Generate Batches
                        </button>
                    </div>
                </div>
            </div>
        </div>

    </div>
</div>

<style>
    .whatsapp-card {
        transition: transform 0.2s, box-shadow 0.2s;
        border: 1px solid #e5e7eb;
        border-radius: 16px;
        overflow: hidden;
        background: #fff;
    }

    .whatsapp-card:hover {
        transform: translateY(-3px);
        box-shadow: 0 15px 35px -10px rgba(0, 0, 0, 0.15);
    }

    .bg-gradient-success {
        background: linear-gradient(135deg, #f0fdf4 0%, #dcfce7 100%);
        border-bottom: 1px solid #bbf7d0;
    }

    .whatsapp-preview-wrapper {
        border: 1px solid #e5e7eb;
        border-radius: 12px;
        overflow: hidden;
        background: #fafafa;
    }

    .whatsapp-header {
        background: #075e54;
        color: white;
        padding: 10px 15px;
        font-size: 13px;
        font-weight: 600;
        display: flex;
        align-items: center;
        gap: 8px;
    }

    .whatsapp-header i {
        font-size: 18px;
    }

    .whatsapp-preview {
        margin: 0;
        padding: 16px;
        font-family: 'Segoe UI', -apple-system, BlinkMacSystemFont, 'Helvetica Neue', sans-serif;
        font-size: 13px;
        line-height: 1.6;
        background: #fafafa;
        color: #111b21;
        white-space: pre-wrap;
        word-wrap: break-word;
        word-break: break-word;
        max-height: 500px;
        overflow-y: auto;
    }

    /* Style for job titles */
    .whatsapp-preview {
        font-weight: normal;
    }

    /* Ensure URLs wrap properly */
    .whatsapp-preview {
        word-break: break-all;
    }

    .copy-batch-btn, .share-wa-btn {
        transition: all 0.2s;
        font-size: 12px;
        padding: 5px 12px;
    }

    .copy-batch-btn:hover, .share-wa-btn:hover {
        transform: translateY(-1px);
    }

    .card-header {
        padding: 12px 16px !important;
    }

    .card-body {
        padding: 16px !important;
    }

    .card-footer {
        padding: 10px 16px !important;
        background: #f9fafb;
    }

    .badge {
        font-size: 11px;
        padding: 4px 10px;
        border-radius: 20px;
    }

    .badge.bg-success {
        background: #25D366 !important;
    }

    .badge.bg-info {
        background: #128C7E !important;
    }

    /* Scrollbar styling */
    .whatsapp-preview::-webkit-scrollbar {
        width: 6px;
    }

    .whatsapp-preview::-webkit-scrollbar-track {
        background: #e5e7eb;
        border-radius: 3px;
    }

    .whatsapp-preview::-webkit-scrollbar-thumb {
        background: #075e54;
        border-radius: 3px;
    }

    /* Responsive */
    @media (max-width: 768px) {
        .whatsapp-preview {
            font-size: 11px;
            padding: 12px;
        }
        
        .card-header {
            flex-direction: column;
            gap: 10px;
        }
        
        .copy-batch-btn, .share-wa-btn {
            width: 100%;
            margin: 2px 0;
        }
    }
</style>

@include('home.whatsapp-docs.index-js')
@endsection