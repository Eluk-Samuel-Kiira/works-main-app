@extends('layouts.app')
@section('title', 'Social Media Platforms - Stardena Works')

@section('app-content')
<div class="body-wrapper">
    <div class="container-fluid">

        {{-- Page Header --}}
        <div class="font-weight-medium shadow-none position-relative overflow-hidden mb-7">
            <div class="card-body px-0">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h4 class="font-weight-medium mb-0">Social Media Platforms</h4>
                        <nav aria-label="breadcrumb">
                            <ol class="breadcrumb">
                                <li class="breadcrumb-item">
                                    <a class="text-muted text-decoration-none" href="{{ route('dashboard') }}">Home</a>
                                </li>
                                <li class="breadcrumb-item text-muted" aria-current="page">Social Media</li>
                            </ol>
                        </nav>
                    </div>
                    <div>
                        <button class="btn btn-primary d-flex align-items-center gap-2" onclick="openCreateModal()">
                            <i class="ti ti-plus fs-4"></i> Add Platform
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
                            placeholder="Search by name or handle…" oninput="debounceLoad()">
                    </div>
                    <div class="col-md-2">
                        <select id="filterLocation" class="form-select form-select-sm" onchange="loadItems(1)">
                            <option value="">All Locations</option>
                        </select>
                    </div>
                    <div class="col-md-2">
                        <select id="filterPlatform" class="form-select form-select-sm" onchange="loadItems(1)">
                            <option value="">All Platforms</option>
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
                        <select id="filterFeatured" class="form-select form-select-sm" onchange="loadItems(1)">
                            <option value="">All</option>
                            <option value="1">Featured</option>
                            <option value="0">Not Featured</option>
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
                    <h4 class="card-title mb-0">All Platforms</h4>
                    <small class="text-muted" id="paginationInfo"></small>
                </div>
                <div class="table-responsive">
                    <table class="table table-striped table-bordered display text-nowrap align-middle">
                        <thead class="table-light">
                            <tr>
                                <th width="40">#</th>
                                <th>Platform</th>
                                <th>Handle / Name</th>
                                <th>Location</th>
                                <th>Followers</th>
                                <th>Status</th>
                                <th>Featured</th>
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

{{-- ============================================================ VIEW MODAL ============================================================ --}}
<div class="modal fade" id="viewModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><i class="ti ti-share me-2"></i><span id="viewModalTitle">Platform Details</span></h5>
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

{{-- ============================================================ CREATE / EDIT MODAL ============================================================ --}}
<div class="modal fade" id="formModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-xl modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="formModalTitle"><i class="ti ti-plus me-2"></i>Add Platform</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <form id="itemForm" onsubmit="return false;">
                    <input type="hidden" id="formId">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Platform <span class="text-danger">*</span></label>
                            <div class="d-flex align-items-center gap-2">
                                <div class="position-relative flex-grow-1">
                                    <input type="text" id="formPlatformInput" class="form-control"
                                        placeholder="Type to search platform..." autocomplete="off">
                                    <input type="hidden" id="formPlatform">
                                    <ul class="dropdown-menu w-100" id="formPlatformList"
                                        style="max-height:250px;overflow-y:auto;"></ul>
                                </div>
                                <span class="input-group-text p-1" id="platformIconPreview"
                                    style="min-width:46px;justify-content:center;border-radius:6px;">
                                    <i class="bi bi-globe text-muted" style="font-size:20px"></i>
                                </span>
                            </div>
                        </div>

                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Location (Country) <span class="text-danger">*</span>
                                <small class="text-muted fw-normal">— one per platform per country</small>
                            </label>
                            <div class="position-relative">
                                <input type="text" id="formLocationInput" class="form-control"
                                    placeholder="Type to search location..." autocomplete="off">
                                <input type="hidden" id="formLocationId">
                                <ul class="dropdown-menu w-100" id="formLocationList"
                                    style="max-height:250px;overflow-y:auto;"></ul>
                            </div>
                        </div>

                        <div class="col-md-8">
                            <label class="form-label fw-semibold">Display Name <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" id="formName"
                                placeholder="e.g. Stardena Jobs Uganda Facebook" required>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label fw-semibold">Handle / Username</label>
                            <div class="input-group">
                                <span class="input-group-text">@</span>
                                <input type="text" class="form-control" id="formHandle" placeholder="stardenaworks">
                            </div>
                        </div>

                        <div class="col-12">
                            <label class="form-label fw-semibold">URL / Link <span class="text-danger">*</span></label>
                            <input type="url" class="form-control" id="formUrl"
                                placeholder="https://facebook.com/groups/..." required>
                            <small class="form-text text-muted">Full URL including https://</small>
                        </div>

                        <div class="col-12">
                            <label class="form-label fw-semibold">Description</label>
                            <textarea class="form-control" id="formDescription" rows="2"
                                placeholder="Brief description of what this page/group is about…"></textarea>
                        </div>

                        <div class="col-md-4">
                            <label class="form-label fw-semibold">Followers / Members</label>
                            <input type="number" class="form-control" id="formFollowersCount"
                                placeholder="0" min="0">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label fw-semibold">Sort Order</label>
                            <input type="number" class="form-control" id="formSortOrder"
                                placeholder="0" min="0">
                            <small class="form-text text-muted">Lower = shown first</small>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label fw-semibold">Custom Icon Class</label>
                            <input type="text" class="form-control" id="formIcon"
                                placeholder="bi bi-facebook (optional override)">
                            <small class="form-text text-muted">Leave blank to use default</small>
                        </div>

                        <div class="col-12"><hr class="my-1"></div>

                        <div class="col-md-4">
                            <div class="form-check form-switch">
                                <input class="form-check-input" type="checkbox" id="formIsActive" checked>
                                <label class="form-check-label" for="formIsActive">Active</label>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="form-check form-switch">
                                <input class="form-check-input" type="checkbox" id="formIsVerified">
                                <label class="form-check-label" for="formIsVerified">Officially Verified</label>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="form-check form-switch">
                                <input class="form-check-input" type="checkbox" id="formIsFeatured">
                                <label class="form-check-label" for="formIsFeatured">Featured (shown prominently)</label>
                            </div>
                        </div>

                        <div class="col-12"><hr class="my-1"></div>

                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Meta Title</label>
                            <input type="text" class="form-control" id="formMetaTitle" maxlength="70"
                                placeholder="Auto-generated if blank">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Meta Description</label>
                            <input type="text" class="form-control" id="formMetaDescription" maxlength="170">
                        </div>

                        <div id="formErrors"></div>
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

{{-- ============================================================ DELETE MODAL ============================================================ --}}
<div class="modal fade" id="deleteModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header bg-danger text-white">
                <h5 class="modal-title"><i class="ti ti-trash me-2"></i>Delete Platform</h5>
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

@include('home.social-media.index-js')
@endsection