@extends('layouts.app')
@section('title', 'Blog Posts - Stardena Works')

@section('app-content')

<div class="body-wrapper">
    <div class="container-fluid">

        {{-- Page Header --}}
        <div class="font-weight-medium shadow-none position-relative overflow-hidden mb-7">
            <div class="card-body px-0">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h4 class="font-weight-medium mb-0">Blog Posts</h4>
                        <nav aria-label="breadcrumb">
                            <ol class="breadcrumb">
                                <li class="breadcrumb-item">
                                    <a class="text-muted text-decoration-none" href="{{ route('dashboard') }}">Home</a>
                                </li>
                                <li class="breadcrumb-item text-muted" aria-current="page">Blog Posts</li>
                            </ol>
                        </nav>
                    </div>
                    <div>
                        <button class="btn btn-primary d-flex align-items-center gap-2" onclick="window.location.href='{{ route('blogs.create') }}'">
                            <i class="ti ti-plus fs-4"></i> Add Blog Post
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
                            placeholder="Search title or content…" oninput="debounceLoad()">
                    </div>
                    <div class="col-md-2">
                        <select id="filterCategory" class="form-select form-select-sm" onchange="loadBlogs(1)">
                            <option value="">All Categories</option>
                        </select>
                    </div>
                    <div class="col-md-1">
                        <select id="filterStatus" class="form-select form-select-sm" onchange="loadBlogs(1)">
                            <option value="">Status</option>
                            <option value="1">Active</option>
                            <option value="0">Inactive</option>
                        </select>
                    </div>
                    <div class="col-md-1">
                        <select id="filterPublished" class="form-select form-select-sm" onchange="loadBlogs(1)">
                            <option value="">All</option>
                            <option value="1">Published</option>
                            <option value="0">Draft</option>
                            <option value="future">Scheduled</option>
                        </select>
                    </div>
                    <div class="col-md-1">
                        <select id="filterFeatured" class="form-select form-select-sm" onchange="loadBlogs(1)">
                            <option value="">Featured</option>
                            <option value="1">Yes</option>
                            <option value="0">No</option>
                        </select>
                    </div>
                    <div class="col-md-2">
                        <select id="filterAuthor" class="form-select form-select-sm" onchange="loadBlogs(1)">
                            <option value="">All Authors</option>
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
                    <h4 class="card-title mb-0">All Blog Posts</h4>
                    <small class="text-muted" id="paginationInfo"></small>
                </div>
                <div class="table-responsive">
                    <table class="table table-striped table-bordered display text-nowrap align-middle">
                        <thead>
                            <tr>
                                <th width="40">#</th>
                                <th>Title</th>
                                <th>Category</th>
                                <th>Author</th>
                                <th>Status</th>
                                <th>Published</th>
                                <th>Views</th>
                                <th width="130">Actions</th>
                            </tr>
                        </thead>
                        <tbody id="blogPostsBody">
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
    <div class="modal-dialog modal-xl modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><i class="ti ti-file-text me-2"></i><span id="viewModalTitle">Blog Post Details</span></h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body" id="viewModalBody">
                <div class="text-center py-5"><div class="spinner-border text-primary"></div></div>
            </div>
            <div class="modal-footer">
                <button class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>

{{-- ============================================================ DELETE MODAL ============================================================ --}}
<div class="modal fade" id="deleteModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header bg-danger text-white">
                <h5 class="modal-title"><i class="ti ti-trash me-2"></i>Delete Blog Post</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <p class="mb-0">Are you sure you want to delete <strong id="deleteBlogTitle"></strong>?<br>
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
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><i class="ti ti-settings me-2"></i>Update Blog Status</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <p class="text-muted mb-3 fw-semibold" id="statusBlogTitle"></p>

                <div class="d-grid gap-2 mb-4">
                    <button class="btn btn-outline-success d-flex justify-content-between align-items-center"
                        onclick="doStatusAction('publish')">
                        <span><i class="ti ti-player-play me-2"></i>Publish</span>
                        <span class="badge bg-light text-dark" id="badgePublished"></span>
                    </button>
                    <button class="btn btn-outline-secondary d-flex justify-content-between align-items-center"
                        onclick="doStatusAction('unpublish')">
                        <span><i class="ti ti-player-stop me-2"></i>Unpublish</span>
                        <span class="badge bg-light text-dark" id="badgeUnpublished"></span>
                    </button>
                    <button class="btn btn-outline-warning d-flex justify-content-between align-items-center"
                        onclick="doStatusAction('feature')">
                        <span><i class="ti ti-star me-2"></i>Feature</span>
                        <span class="badge bg-light text-dark" id="badgeFeatured"></span>
                    </button>
                </div>

                {{-- SEO ACTIONS SECTION --}}
                <div class="border-top pt-3 mb-3">
                    <p class="text-muted small fw-semibold text-uppercase mb-3" style="letter-spacing:.06em">
                        <i class="ti ti-search me-1"></i>SEO Actions
                    </p>

                    <div class="row g-2 mb-3" id="blogSeoStatus">
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
                                        onclick="pingThisBlog()" id="pingBtn">
                                    <i class="ti ti-bell me-1"></i>Ping Now
                                </button>
                            </div>
                        </div>
                    </div>

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
                                        <strong>200 URLs/day</strong> — use for priority blogs only.
                                    </div>
                                </div>
                                <button class="btn btn-danger btn-sm fw-semibold flex-shrink-0"
                                        onclick="indexThisBlog()" id="indexBtn">
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

@include('blog.index-js')
@endsection