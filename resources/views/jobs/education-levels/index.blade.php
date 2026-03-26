@extends('layouts.app')
@section('title', 'Education Levels - Stardena Works')

@section('app-content')
<div class="body-wrapper">
    <div class="container-fluid">

        {{-- Page Header --}}
        <div class="font-weight-medium shadow-none position-relative overflow-hidden mb-7">
            <div class="card-body px-0">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h4 class="font-weight-medium mb-0">Education Levels</h4>
                        <nav aria-label="breadcrumb">
                            <ol class="breadcrumb">
                                <li class="breadcrumb-item">
                                    <a class="text-muted text-decoration-none" href="{{ route('dashboard') }}">Home</a>
                                </li>
                                <li class="breadcrumb-item text-muted" aria-current="page">Education Levels</li>
                            </ol>
                        </nav>
                    </div>
                    <div>
                        <button class="btn btn-primary d-flex align-items-center gap-2" onclick="openCreateModal()">
                            <i class="ti ti-plus fs-4"></i> Add Education Level
                        </button>
                    </div>
                </div>
            </div>
        </div>

        {{-- Filters --}}
        <div class="card mb-4">
            <div class="card-body py-3">
                <div class="row g-2 align-items-end">
                    <div class="col-md-4">
                        <input type="text" id="filterSearch" class="form-control form-control-sm"
                            placeholder="Search by name…" oninput="debounceLoad()">
                    </div>
                    <div class="col-md-2">
                        <select id="filterStatus" class="form-select form-select-sm" onchange="loadItems(1)">
                            <option value="">All Statuses</option>
                            <option value="1">Active</option>
                            <option value="0">Inactive</option>
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
                    <h4 class="card-title mb-0">All Education Levels</h4>
                    <small class="text-muted" id="paginationInfo"></small>
                </div>
                <div class="table-responsive">
                    <table class="table table-striped table-bordered display text-nowrap align-middle">
                        <thead>
                            <tr>
                                <th width="40">#</th>
                                <th>Name</th>
                                <th>Status</th>
                                <th>Sort Order</th>
                                <th width="130">Actions</th>
                            </tr>
                        </thead>
                        <tbody id="tableBody">
                            <tr><td colspan="5" class="text-center py-4">
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
    <div class="modal-dialog modal-lg modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><i class="ti ti-school me-2"></i><span id="viewModalTitle">Education Level Details</span></h5>
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
    <div class="modal-dialog modal-lg modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="formModalTitle"><i class="ti ti-plus me-2"></i>Add Education Level</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <form id="itemForm" onsubmit="return false;">
                    <input type="hidden" id="formId">
                    <div class="row g-3">
                        <div class="col-md-12">
                            <label class="form-label">Name <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" id="formName" required>
                        </div>
                        <div class="col-12">
                            <label class="form-label">Description</label>
                            <textarea class="form-control" id="formDescription" rows="3"></textarea>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Meta Title</label>
                            <input type="text" class="form-control" id="formMetaTitle">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Meta Description</label>
                            <input type="text" class="form-control" id="formMetaDescription">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Sort Order</label>
                            <input type="number" class="form-control" id="formSortOrder" value="0" min="0">
                        </div>
                        <div class="col-md-6">
                            <div class="form-check form-switch mt-4">
                                <input class="form-check-input" type="checkbox" id="formIsActive" checked>
                                <label class="form-check-label" for="formIsActive">Active</label>
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
                <h5 class="modal-title"><i class="ti ti-trash me-2"></i>Delete Education Level</h5>
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

@include('jobs.education-levels.index-js')
@endsection
