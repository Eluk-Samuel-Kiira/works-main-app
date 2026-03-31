@extends('layouts.app')
@section('title', 'Companies - Stardena Works')

@section('app-content')
<div class="body-wrapper">
    <div class="container-fluid">

        {{-- Page Header --}}
        <div class="font-weight-medium shadow-none position-relative overflow-hidden mb-7">
            <div class="card-body px-0">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h4 class="font-weight-medium mb-0">Companies</h4>
                        <nav aria-label="breadcrumb">
                            <ol class="breadcrumb">
                                <li class="breadcrumb-item">
                                    <a class="text-muted text-decoration-none" href="{{ route('dashboard') }}">Home</a>
                                </li>
                                <li class="breadcrumb-item text-muted" aria-current="page">Companies</li>
                            </ol>
                        </nav>
                    </div>
                    <div>
                        <button class="btn btn-primary d-flex align-items-center gap-2" onclick="openCreateModal()">
                            <i class="ti ti-plus fs-4"></i> Add Company
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
                            placeholder="Search by name…" oninput="debounceLoad()">
                    </div>
                    <div class="col-md-2">
                        <select id="filterIndustry" class="form-select form-select-sm" onchange="loadItems(1)">
                            <option value="">All Industries</option>
                        </select>
                    </div>
                    <div class="col-md-2">
                        <select id="filterStatus" class="form-select form-select-sm" onchange="loadItems(1)">
                            <option value="">All Statuses</option>
                            <option value="1">Active</option>
                            <option value="0">Inactive</option>
                        </select>
                    </div>
                    <div class="col-md-2">
                        <select id="filterVerified" class="form-select form-select-sm" onchange="loadItems(1)">
                            <option value="">All</option>
                            <option value="1">Verified</option>
                            <option value="0">Unverified</option>
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

        {{-- Table Card --}}
        <div class="card">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <h4 class="card-title mb-0">All Companies</h4>
                    <small class="text-muted" id="paginationInfo"></small>
                </div>
                <div class="table-responsive">
                    <table class="table table-striped table-bordered display text-nowrap align-middle">
                        <thead>
                            <tr>
                                <th width="40">#</th>
                                <th>Company</th>
                                <th>Industry</th>
                                <th>Contact</th>
                                <th>Website</th>
                                <th>Status</th>
                                <th>Verified</th>
                                <th width="130">Actions</th>
                            </tr>
                        </thead>
                        <tbody id="tableBody">
                            <tr><td colspan="8" class="text-center py-4">
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

{{-- VIEW MODAL --}}
<div class="modal fade" id="viewModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-xl modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><i class="ti ti-building me-2"></i><span id="viewModalTitle">Company Details</span></h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body" id="viewModalBody">
                <div class="text-center py-5"><div class="spinner-border text-primary"></div></div>
            </div>
            <div class="modal-footer">
                <button class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                <button class="btn btn-primary" onclick="switchToEdit()"><i class="ti ti-pencil me-1"></i>Edit</button>
            </div>
        </div>
    </div>
</div>

{{-- CREATE/EDIT MODAL --}}
<div class="modal fade" id="formModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-xl modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="formModalTitle"><i class="ti ti-plus me-2"></i>Add Company</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <form id="itemForm" onsubmit="return false;">
                    <input type="hidden" id="formId">
                    <div class="row g-3">
                        <div class="col-md-8">
                            <label class="form-label">Company Name <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" id="formName" required>
                        </div>
                        <div class="col-md-2">
                            <label class="form-label">Industry</label>
                            <select class="form-select" id="formIndustryId">
                                <option value="">— Select Industry —</option>
                            </select>
                        </div>
                        <div class="col-md-2">
                            <label class="form-label">Location</label>
                            <select class="form-select" id="formLocationId">
                                <option value="">— Select Location —</option>
                            </select>
                        </div>
                        <div class="col-12">
                            <label class="form-label">Description</label>
                            <textarea class="form-control" id="formDescription" rows="3"></textarea>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Website</label>
                            <input type="url" class="form-control" id="formWebsite" placeholder="https://example.com">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Company Logo</label>
                            <div class="input-group">
                                <input type="file" id="formLogoFile" class="form-control" accept="image/jpeg,image/png,image/gif,image/webp" onchange="previewLogo()">
                                <button class="btn btn-outline-secondary" type="button" onclick="clearLogo()">Clear</button>
                            </div>
                            <small class="form-text text-muted">Max 2MB • Formats: JPG, PNG, GIF, WebP</small>
                            <div id="logoPreview" class="mt-2"></div>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Contact Name</label>
                            <input type="text" class="form-control" id="formContactName">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Contact Email</label>
                            <input type="email" class="form-control" id="formContactEmail">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Contact Phone</label>
                            <input type="text" class="form-control" id="formContactPhone">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Address</label>
                            <input type="text" class="form-control" id="formAddress1">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Company Size</label>
                            <input type="text" class="form-control" id="formCompanySize" placeholder="e.g. 50-200">
                        </div>
                        <div class="col-md-6">
                            <div class="form-check form-switch">
                                <input class="form-check-input" type="checkbox" id="formIsActive" checked>
                                <label class="form-check-label" for="formIsActive">Active</label>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-check form-switch">
                                <input class="form-check-input" type="checkbox" id="formIsVerified">
                                <label class="form-check-label" for="formIsVerified">Verified</label>
                            </div>
                        </div>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button class="btn btn-primary" id="formSaveBtn" onclick="submitSave()">
                    <span id="formBtnText">Save</span>
                    <span id="formBtnSpinner" class="spinner-border spinner-border-sm ms-1 d-none"></span>
                </button>
            </div>
        </div>
    </div>
</div>

{{-- DELETE MODAL --}}
<div class="modal fade" id="deleteModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header bg-danger text-white">
                <h5 class="modal-title"><i class="ti ti-trash me-2"></i>Delete Company</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <p class="mb-0">Are you sure you want to delete <strong id="deleteItemName"></strong>?<br>
                <small class="text-muted">This action cannot be undone.</small></p>
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

@include('jobs.company.index-js')
@endsection
