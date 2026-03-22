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
                <button class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                <button class="btn btn-primary" onclick="switchToEdit()">
                    <i class="ti ti-pencil me-1"></i>Edit
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

{{-- ============================================================ STATUS MODAL ============================================================ --}}
<div class="modal fade" id="statusModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><i class="ti ti-settings me-2"></i>Update Status</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <p class="text-muted mb-3 fw-semibold" id="statusJobTitle"></p>
                <div class="d-grid gap-2">
                    <button class="btn btn-outline-success d-flex justify-content-between align-items-center"
                        onclick="doStatusAction('activate')">
                        <span><i class="ti ti-player-play me-2"></i>Activate</span>
                        <span class="badge bg-success" id="badgeActive"></span>
                    </button>
                    <button class="btn btn-outline-secondary d-flex justify-content-between align-items-center"
                        onclick="doStatusAction('deactivate')">
                        <span><i class="ti ti-player-stop me-2"></i>Deactivate</span>
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
                <div id="statusActionMsg" class="mt-3"></div>
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