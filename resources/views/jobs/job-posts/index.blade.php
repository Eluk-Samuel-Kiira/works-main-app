@extends('layouts.app')
@section('title', 'Job Posts - Stardena Works')

@section('app-content')

<div class="body-wrapper">
    <div class="container-fluid">

        {{-- Page Header --}}
        <div class="font-weight-medium shadow-none position-relative overflow-hidden mb-7">
            <div class="card-body px-0">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h4 class="font-weight-medium mb-0">Job Posts</h4>
                        <nav aria-label="breadcrumb">
                            <ol class="breadcrumb">
                                <li class="breadcrumb-item">
                                    <a class="text-muted text-decoration-none" href="{{ route('dashboard') }}">Home</a>
                                </li>
                                <li class="breadcrumb-item text-muted" aria-current="page">Job Posts</li>
                            </ol>
                        </nav>
                    </div>
                    <div>
                        <button class="btn btn-outline-success d-flex align-items-center gap-2 me-2"
                                onclick="openBulkSeoModal()">
                            <i class="ti ti-search-check fs-4"></i>
                            Ping
                            <span class="badge bg-warning text-dark ms-1" id="pendingIndexBadge">–</span>
                        </button>
                    </div>
                    <div>
                        <button class="btn btn-primary d-flex align-items-center gap-2" onclick="openCreateModal()">
                            <i class="ti ti-plus fs-4"></i> Add Job Post
                        </button>
                    </div>
                </div>
            </div>
        </div>

        {{-- Filters --}}
        <div class="card mb-4">
            <div class="card-body py-3">
                <div class="row g-2 align-items-end">
                    <div class="col-md-3">
                        <input type="text" id="filterSearch" class="form-control form-control-sm"
                            placeholder="Search job title…" oninput="debounceLoad()">
                    </div>
                    <div class="col-md-1">
                        <select id="filterStatus" class="form-select form-select-sm" onchange="loadJobs(1)">
                            <option value="">All Statuses</option>
                            <option value="1">Active</option>
                            <option value="0">Inactive</option>
                        </select>
                    </div>
                    <div class="col-md-2">
                        <select id="filterVerified" class="form-select form-select-sm" onchange="loadJobs(1)">
                            <option value="">All</option>
                            <option value="1">Verified</option>
                            <option value="0">Unverified</option>
                        </select>
                    </div>
                    <div class="col-md-2">
                        <select id="filterEmployment" class="form-select form-select-sm" onchange="loadJobs(1)">
                            <option value="">All Types</option>
                            <option value="full-time">Full-time</option>
                            <option value="part-time">Part-time</option>
                            <option value="contract">Contract</option>
                            <option value="internship">Internship</option>
                            <option value="temporary">Temporary</option>
                            <option value="volunteer">Volunteer</option>
                        </select>
                    </div>
                    <div class="col-md-2">
                        <select id="filterPoster" class="form-select form-select-sm" onchange="loadJobs(1)">
                            <option value="">All Posters</option>
                        </select>
                    </div>
                    <div class="col-md-2">
                        <button class="btn btn-sm btn-outline-secondary w-100" onclick="resetFilters()">
                            <i class="ti ti-refresh me-1"></i>Reset
                        </button>
                    </div>
                </div>
            </div>
        </div>

        {{-- Table Card --}}
        <div class="card">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <h4 class="card-title mb-0">All Job Posts</h4>
                    <small class="text-muted" id="paginationInfo"></small>
                </div>
                <div class="table-responsive">
                    <table class="table table-striped table-bordered display text-nowrap align-middle">
                        <thead>
                            <tr>
                                <th width="40">#</th>
                                <th>Job Title</th>
                                <th>Company</th>
                                <th>Location</th>
                                <th>Type</th>
                                <th>Status</th>
                                <th>Deadline</th>
                                <th>Poster</th>
                                <th width="130">Actions</th>
                            </tr>
                        </thead>
                        <tbody id="jobPostsBody">
                            <tr><td colspan="9" class="text-center py-4">
                                <div class="spinner-border text-primary"></div>
                            </td></tr>
                        </tbody>
                    </table>
                    <div class="d-flex justify-content-end mt-3" id="paginationLinks"></div>
                </div>
            </div>
        </div>

    </div>
</div>

{{-- ============================================================ VIEW MODAL ============================================================ --}}
<div class="modal fade" id="viewModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-xl modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">
                    <i class="ti ti-briefcase me-2"></i>
                    <span id="viewModalTitle">Job Post Details</span>
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body" id="viewModalBody">
                <div class="text-center py-5"><div class="spinner-border text-primary"></div></div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                <button type="button" class="btn btn-primary" id="runIndexBtn" onclick="runManualIndexing('new')">
                    <i class="ti ti-sparkles me-1"></i>Run Indexing
                </button>
            </div>
        </div>
    </div>
</div>

{{-- ============================================================ EDIT MODAL ============================================================ --}}
<div class="modal fade" id="editModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-xl modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><i class="ti ti-pencil me-2"></i>Edit Job Post</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body" id="editModalBody">
                <div class="text-center py-5"><div class="spinner-border text-primary"></div></div>
            </div>
            <div class="modal-footer">
                <button class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button class="btn btn-primary" id="editSaveBtn" onclick="submitEdit()">
                    <span id="editBtnText">Save Changes</span>
                    <span id="editBtnSpinner" class="spinner-border spinner-border-sm ms-1 d-none"></span>
                </button>
            </div>
        </div>
    </div>
</div>

{{-- ============================================================ DELETE MODAL ============================================================ --}}
<div class="modal fade" id="deleteModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header bg-danger text-white">
                <h5 class="modal-title"><i class="ti ti-trash me-2"></i>Delete Job Post</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <p class="mb-0">Are you sure you want to delete <strong id="deleteJobTitle"></strong>?<br>
                <small class="text-muted">This action soft-deletes the record.</small></p>
            </div>
            <div class="modal-footer">
                <button class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button class="btn btn-danger" id="confirmDeleteBtn" onclick="confirmDelete()">
                    <span id="deleteBtnText">Delete</span>
                    <span id="deleteBtnSpinner" class="spinner-border spinner-border-sm ms-1 d-none"></span>
                </button>
            </div>
        </div>
    </div>
</div>


{{--
    Replace the existing statusModal div in index.blade.php with this.
    The Ping and Index sections are added at the bottom of the modal body.
--}}
<div class="modal fade" id="statusModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><i class="ti ti-settings me-2"></i>Update Status</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <p class="text-muted mb-3 fw-semibold" id="statusJobTitle"></p>
 
                {{-- ── Existing status actions ── --}}
                <div class="d-grid gap-2 mb-4">
                    <button class="btn btn-outline-success d-flex justify-content-between align-items-center"
                        onclick="doStatusAction('activate')">
                        <span><i class="ti ti-player-play me-2"></i>Activate</span>
                        <span class="badge bg-success" id="badgeActive"></span>
                    </button>
                    <button class="btn btn-outline-secondary d-flex justify-content-between align-items-center"
                        onclick="doStatusAction('deactivate')">
                        <span><i class="ti ti-player-stop me-2"></i>Deactivate</span>
                        <span class="badge bg-success" id="badgeInactive"></span>
                    </button>
                    <button class="btn btn-outline-info d-flex justify-content-between align-items-center"
                        onclick="doStatusAction('verify')">
                        <span><i class="ti ti-shield-check me-2"></i>Mark as Verified</span>
                        <span class="badge bg-info" id="badgeVerified"></span>
                    </button>
                    <button class="btn btn-outline-warning d-flex justify-content-between align-items-center"
                        onclick="doStatusAction('urgent')">
                        <span><i class="ti ti-alert-triangle me-2"></i>Mark as Urgent</span>
                        <span class="badge bg-warning text-dark" id="badgeUrgent"></span>
                    </button>
                    <div class="input-group">
                        <button class="btn btn-outline-primary" onclick="doFeature()">
                            <i class="ti ti-star me-1"></i>Feature for
                        </button>
                        <input type="number" class="form-control" id="featureDays" value="7" min="1" max="90">
                        <span class="input-group-text">days</span>
                    </div>
                </div>
 
                {{-- ── SEO ACTIONS SECTION ── --}}
                <div class="border-top pt-3 mb-3">
                    <p class="text-muted small fw-semibold text-uppercase mb-3" style="letter-spacing:.06em">
                        <i class="ti ti-search me-1"></i>SEO Actions
                    </p>
 
                    {{-- Current SEO status for this job --}}
                    <div class="row g-2 mb-3" id="jobSeoStatus">
                        <div class="col-6">
                            <div class="p-2 border rounded-2 text-center">
                                <div class="small text-muted">IndexNow Ping</div>
                                <div id="statusPingBadge" class="mt-1">—</div>
                            </div>
                        </div>
                        <div class="col-6">
                            <div class="p-2 border rounded-2 text-center">
                                <div class="small text-muted">Google Index</div>
                                <div id="statusIndexBadge" class="mt-1">—</div>
                            </div>
                        </div>
                    </div>
 
                    {{-- PING button --}}
                    <div class="card border-0 bg-body-secondary mb-2">
                        <div class="card-body p-3">
                            <div class="d-flex align-items-start gap-3">
                                <div class="rounded-2 bg-primary bg-opacity-10 p-2 flex-shrink-0">
                                    <i class="ti ti-bell-ringing text-primary fs-5"></i>
                                </div>
                                <div class="flex-grow-1">
                                    <div class="fw-semibold small mb-1">IndexNow Ping</div>
                                    <div class="text-muted" style="font-size:12px">
                                        Notifies Bing, Yandex & other search engines via IndexNow protocol.
                                        Always safe to run — no quota limits.
                                    </div>
                                </div>
                                <button class="btn btn-primary btn-sm fw-semibold flex-shrink-0"
                                        onclick="pingThisJob()" id="pingBtn">
                                    <i class="ti ti-bell me-1"></i>Ping Now
                                </button>
                            </div>
                        </div>
                    </div>
 
                    {{-- GOOGLE INDEX button --}}
                    <div class="card border-0 bg-body-secondary mb-2">
                        <div class="card-body p-3">
                            <div class="d-flex align-items-start gap-3">
                                <div class="rounded-2 bg-danger bg-opacity-10 p-2 flex-shrink-0">
                                    <img src="https://www.google.com/favicon.ico" width="20" alt="Google">
                                </div>
                                <div class="flex-grow-1">
                                    <div class="fw-semibold small mb-1">
                                        Google Indexing API
                                        <span class="badge bg-warning text-dark ms-1" style="font-size:10px">
                                            <span id="googleQuotaLeft">...</span> left today
                                        </span>
                                    </div>
                                    <div class="text-muted" style="font-size:12px">
                                        Submits directly to Google's index queue. Limited to
                                        <strong>200 URLs/day</strong> — use for priority jobs only.
                                    </div>
                                </div>
                                <button class="btn btn-danger btn-sm fw-semibold flex-shrink-0"
                                        onclick="indexThisJob()" id="indexBtn">
                                    <i class="ti ti-brand-google me-1"></i>Index Now
                                </button>
                            </div>
                        </div>
                    </div>
 
                </div>
 
                <div id="statusActionMsg" class="mt-3"></div>
            </div>
            <div class="modal-footer">
                <button class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>

{{-- ============================================================
     BULK PING & INDEX MODAL
     Add this as a NEW modal — opened from the page header button
============================================================ --}}
<div class="modal fade" id="bulkSeoModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header" style="background:linear-gradient(135deg,#4f46e5,#7c3aed)">
                <h5 class="modal-title text-white d-flex align-items-center gap-2">
                    <i class="ti ti-search-check fs-5"></i> Bulk Ping & Index
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
 
                {{-- Stats --}}
                <div class="row g-3 mb-4" id="bulkSeoStats">
                    <div class="col-3">
                        <div class="p-3 border rounded-2 text-center">
                            <div class="fw-bold fs-5 text-body" id="bsStat1">...</div>
                            <div class="text-muted" style="font-size:11px">Active Jobs</div>
                        </div>
                    </div>
                    <div class="col-3">
                        <div class="p-3 border rounded-2 text-center">
                            <div class="fw-bold fs-5 text-primary" id="bsStat2">...</div>
                            <div class="text-muted" style="font-size:11px">Pinged</div>
                        </div>
                    </div>
                    <div class="col-3">
                        <div class="p-3 border rounded-2 text-center">
                            <div class="fw-bold fs-5 text-danger" id="bsStat3">...</div>
                            <div class="text-muted" style="font-size:11px">Ping Failed</div>
                        </div>
                    </div>
                    <div class="col-3">
                        <div class="p-3 border rounded-2 text-center">
                            <div class="fw-bold fs-5 text-warning" id="bsStat4">...</div>
                            <div class="text-muted" style="font-size:11px">Not Pinged</div>
                        </div>
                    </div>
                </div>
 
                <hr class="my-3">
 
                {{-- PING SECTION --}}
                <div class="mb-4">
                    <h6 class="fw-semibold d-flex align-items-center gap-2 mb-3">
                        <span class="badge bg-primary rounded-2 p-1"><i class="ti ti-bell fs-6"></i></span>
                        IndexNow Ping
                        <small class="text-muted fw-normal">— Bing, Yandex & others — no quota limits</small>
                    </h6>
                    <div class="d-flex gap-2">
                        <button class="btn btn-primary" onclick="bulkPing('failed')" id="pingFailedBtn">
                            <i class="ti ti-bell-ringing me-1"></i>
                            Ping Failed Jobs
                            <span class="badge bg-white text-primary ms-1" id="failedPingCount">...</span>
                        </button>
                        <button class="btn btn-outline-primary" onclick="bulkPing('all')">
                            <i class="ti ti-bell me-1"></i>Ping All Unpigged
                        </button>
                    </div>
                </div>
 
                <hr class="my-3">
 
                {{-- GOOGLE INDEX SECTION --}}
                <div class="mb-3">
                    <h6 class="fw-semibold d-flex align-items-center gap-2 mb-2">
                        <span class="badge bg-danger rounded-2 p-1">
                            <img src="https://www.google.com/favicon.ico" width="14" alt="G">
                        </span>
                        Google Indexing API
                        <small class="text-muted fw-normal">— Direct submission — max 200/day</small>
                    </h6>
 
                    {{-- Quota bar --}}
                    <div class="bg-body-secondary rounded-2 p-3 mb-3">
                        <div class="d-flex justify-content-between align-items-center mb-2">
                            <span class="small fw-semibold">Daily Quota</span>
                            <span class="small text-muted">
                                <span id="quotaUsed">...</span> / 200 used
                                (<span id="quotaLeft">...</span> remaining)
                            </span>
                        </div>
                        <div class="progress" style="height:8px">
                            <div class="progress-bar bg-primary" id="quotaBar" style="width:0%"></div>
                        </div>
                        <div class="d-flex justify-content-between mt-1">
                            <small class="text-muted">Resets at midnight UTC</small>
                            <small id="quotaApiStatus" class="text-muted">Checking...</small>
                        </div>
                    </div>
 
                    {{-- Google indexing stats --}}
                    <div class="row g-2 mb-3">
                        <div class="col-4 text-center">
                            <div class="p-2 border rounded-2">
                                <div class="fw-bold text-warning" id="gsNotSubmitted">...</div>
                                <small class="text-muted">Not submitted</small>
                            </div>
                        </div>
                        <div class="col-4 text-center">
                            <div class="p-2 border rounded-2">
                                <div class="fw-bold text-success" id="gsSubmitted">...</div>
                                <small class="text-muted">Submitted</small>
                            </div>
                        </div>
                        <div class="col-4 text-center">
                            <div class="p-2 border rounded-2">
                                <div class="fw-bold text-primary" id="gsIndexed">...</div>
                                <small class="text-muted">Confirmed indexed</small>
                            </div>
                        </div>
                    </div>
 
                    <div class="d-flex gap-2 flex-wrap">
                        <button class="btn btn-danger" onclick="bulkIndex('new')" id="indexNewBtn">
                            <i class="ti ti-brand-google me-1"></i>
                            Submit New Jobs
                            <span class="badge bg-white text-danger ms-1" id="newJobsCount">...</span>
                        </button>
                        <button class="btn btn-outline-danger" onclick="bulkIndex('priority')">
                            <i class="ti ti-star me-1"></i>Submit Featured Jobs
                        </button>
                    </div>
                    <small class="text-muted d-block mt-2">
                        <i class="ti ti-info-circle me-1"></i>
                        Only submit jobs you want Google to crawl urgently.
                        Google finds all jobs automatically via sitemap within days.
                    </small>
                </div>
 
                {{-- Result output --}}
                <div id="bulkSeoResult" class="mt-3"></div>
 
            </div>
            <div class="modal-footer">
                <button class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>



@include('jobs.job-posts.index-js')
@include('jobs.job-posts.simple-post')
@endsection