@extends('layouts.app')
@section('title', 'Edit Blog Post - Stardena Works')

@section('app-content')
<div class="body-wrapper">
<div class="container-fluid">

{{-- Page Header --}}
<div class="mb-4">
    <div class="d-flex justify-content-between align-items-center">
        <div>
            <h4 class="fw-bold mb-0 d-flex align-items-center gap-2">
                <span class="badge bg-primary-subtle text-primary rounded-2 p-2">
                    <i class="ti ti-edit fs-5"></i>
                </span>
                Edit Blog Post
            </h4>
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb mb-0 small">
                    <li class="breadcrumb-item"><a class="text-muted text-decoration-none" href="{{ route('dashboard') }}">Home</a></li>
                    <li class="breadcrumb-item"><a class="text-muted text-decoration-none" href="{{ route('blogs.index') }}">Blog</a></li>
                    <li class="breadcrumb-item text-muted">Edit</li>
                </ol>
            </nav>
        </div>
        <div class="d-flex gap-2">
            <button class="btn btn-outline-secondary" onclick="window.location.href='{{ route('blogs.index') }}'">
                <i class="ti ti-arrow-left me-1"></i> Back
            </button>
            <!-- <button class="btn btn-outline-primary" id="submitDraftBtn" onclick="submitBlogPost('draft')">
                <span id="submitDraftBtnText"><i class="ti ti-device-floppy me-1"></i> Update Draft</span>
                <span id="submitDraftBtnSpinner" class="spinner-border spinner-border-sm ms-2 d-none"></span>
            </button> -->
            <button class="btn btn-primary" id="submitBlogBtn" onclick="submitBlogPost()">
                <span id="submitBlogBtnText"><i class="ti ti-send me-1"></i> Publish Blog</span>
                <span id="submitBlogBtnSpinner" class="spinner-border spinner-border-sm ms-2 d-none"></span>
            </button>
        </div>
    </div>
</div>

<div id="loadingSpinner" class="text-center py-5">
    <div class="spinner-border text-primary" role="status">
        <span class="visually-hidden">Loading...</span>
    </div>
</div>

<form id="blogForm" style="display:none">

<div class="row g-4">

{{-- LEFT COLUMN — Main content --}}
<div class="col-12 col-xl-8">

    {{-- BASIC INFO --}}
    <div class="card border-0 shadow-sm mb-4">
        <div class="card-header bg-body border-bottom d-flex align-items-center gap-2 py-3">
            <span class="badge bg-primary rounded-circle p-1" style="width:28px;height:28px;display:flex;align-items:center;justify-content:center">
                <i class="ti ti-info-circle" style="font-size:14px"></i>
            </span>
            <h6 class="mb-0 fw-semibold">Basic Information</h6>
        </div>
        <div class="card-body">
            <div class="row g-3">
                <div class="col-12">
                    <label class="form-label fw-semibold">Blog Title <span class="text-danger">*</span></label>
                    <input type="text" name="title" id="f_title" class="form-control form-control-lg"
                           placeholder="e.g. How to Write a Winning CV">
                </div>

                <div class="col-md-6">
                    <label class="form-label fw-semibold">Category</label>
                    <div class="position-relative">
                        <input type="text" name="category" id="f_category" class="form-control" 
                            placeholder="Type to search or create new category..."
                            autocomplete="off">
                        <div id="categorySuggestions" class="dropdown-menu w-100" style="max-height: 250px; overflow-y: auto; display: none;"></div>
                        <small class="text-muted">Select existing category or type a new one (lowercase with hyphens)</small>
                    </div>
                </div>

                <div class="col-md-6">
                    <label class="form-label fw-semibold">Tags</label>
                    <div class="position-relative">
                        <input type="text" name="tags_input" id="f_tags_input" class="form-control" 
                            placeholder="Type to search or add tags..."
                            autocomplete="off">
                        <div id="tagsSuggestions" class="dropdown-menu w-100" style="max-height: 250px; overflow-y: auto; display: none;"></div>
                        <div id="selectedTags" class="d-flex flex-wrap gap-1 mt-2"></div>
                        <input type="hidden" name="tags" id="f_tags">
                        <small class="text-muted">Select existing tags or type new ones (comma separated)</small>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- EXCERPT --}}
    <div class="card border-0 shadow-sm mb-4">
        <div class="card-header bg-body border-bottom d-flex align-items-center gap-2 py-3">
            <span class="badge bg-info rounded-circle p-1" style="width:28px;height:28px;display:flex;align-items:center;justify-content:center">
                <i class="ti ti-file-text" style="font-size:14px"></i>
            </span>
            <h6 class="mb-0 fw-semibold">Excerpt</h6>
        </div>
        <div class="card-body">
            <textarea name="excerpt" id="f_excerpt" class="form-control" rows="3" 
                      placeholder="Short summary of the blog post (max 500 characters)"></textarea>
            <div class="d-flex justify-content-end mt-2">
                <small id="excerptCharCount" class="text-muted">0/500</small>
            </div>
        </div>
    </div>

    {{-- CONTENT — Rich Editor (Using x-rich-editor component) --}}
    <div class="card border-0 shadow-sm mb-4">
        <div class="card-header bg-body border-bottom d-flex align-items-center gap-2 py-3">
            <span class="badge bg-success rounded-circle p-1" style="width:28px;height:28px;display:flex;align-items:center;justify-content:center">
                <i class="ti ti-file-description" style="font-size:14px"></i>
            </span>
            <h6 class="mb-0 fw-semibold">Blog Content <span class="text-danger">*</span></h6>
        </div>
        <div class="card-body">
            <x-rich-editor id="f_content_editor" name="content" :height="400"/>
            <div class="d-flex justify-content-between mt-2">
                <small class="text-muted">Write your blog content here. Use the toolbar to format text, add images, create lists, and more.</small>
                <small id="contentCharCount" class="text-muted">0 chars</small>
            </div>
        </div>
    </div>

    {{-- COVER IMAGE --}}
    <div class="card border-0 shadow-sm mb-4">
        <div class="card-header bg-body border-bottom d-flex align-items-center gap-2 py-3">
            <span class="badge bg-warning rounded-circle p-1" style="width:28px;height:28px;display:flex;align-items:center;justify-content:center">
                <i class="ti ti-photo" style="font-size:14px"></i>
            </span>
            <h6 class="mb-0 fw-semibold">Cover Image</h6>
        </div>
        <div class="card-body">
            <div class="row g-3">
                <div class="col-12">
                    <label class="form-label fw-semibold small">Cover Image</label>
                    <div class="border rounded-3 p-3 text-center bg-light" style="border-style:dashed!important">
                        <input type="file" id="coverImageInput" accept="image/*" style="display:none" onchange="uploadCoverImage(this)">
                        <div id="coverImagePreview" style="display:none; margin-bottom:15px">
                            <img id="coverImagePreviewImg" src="" style="max-width:100%; max-height:200px; border-radius:8px; object-fit:cover;">
                        </div>
                        <div id="coverImagePlaceholder" style="display:block;">
                            <i class="ti ti-photo fs-1 text-muted mb-2 d-block"></i>
                            <p class="text-muted small mb-2">No image selected</p>
                        </div>
                        <div class="d-flex gap-2 justify-content-center">
                            <button type="button" class="btn btn-outline-primary" onclick="document.getElementById('coverImageInput').click()">
                                <i class="ti ti-upload me-1"></i> Select Cover Image
                            </button>
                            <button type="button" class="btn btn-outline-danger" onclick="clearCoverImage()" style="display:none" id="clearCoverBtn">
                                <i class="ti ti-trash me-1"></i> Remove
                            </button>
                        </div>
                        <small class="d-block mt-2 text-muted">JPG, PNG, GIF, WEBP (max 5MB)</small>
                    </div>
                    <input type="hidden" name="cover_image" id="f_cover_image">
                </div>
                <div class="col-12">
                    <label class="form-label fw-semibold small">Image Alt Text</label>
                    <input type="text" name="cover_image_alt" id="f_cover_image_alt" class="form-control" 
                        placeholder="Descriptive text for SEO">
                    <small class="text-muted">Important for SEO and accessibility</small>
                </div>
                <div class="col-12">
                    <label class="form-label fw-semibold small">Image Caption</label>
                    <input type="text" name="cover_image_caption" id="f_cover_image_caption" class="form-control" 
                        placeholder="Optional caption to display below the image">
                </div>
            </div>
        </div>
    </div>

    {{-- SEO METADATA --}}
    <div class="card border-0 shadow-sm mb-4">
        <div class="card-header bg-body border-bottom d-flex align-items-center justify-content-between py-3">
            <div class="d-flex align-items-center gap-2">
                <span class="badge bg-secondary rounded-circle p-1" style="width:28px;height:28px;display:flex;align-items:center;justify-content:center">
                    <i class="ti ti-seo" style="font-size:14px"></i>
                </span>
                <h6 class="mb-0 fw-semibold">SEO Metadata</h6>
            </div>
            <button type="button" class="btn btn-sm btn-outline-secondary" onclick="toggleSeo()">
                <i class="ti ti-chevron-down" id="seoChevron"></i>
            </button>
        </div>
        <div class="card-body" id="seoBody" style="display:none">
            <div class="row g-3">
                <div class="col-12">
                    <label class="form-label small fw-semibold">Meta Title <span class="text-muted">(50-60 chars)</span></label>
                    <input type="text" name="meta_title" id="f_meta_title" class="form-control" maxlength="60">
                    <div class="d-flex justify-content-end mt-1">
                        <small id="metaTitleCount" class="text-muted">0/60</small>
                    </div>
                </div>
                <div class="col-12">
                    <label class="form-label small fw-semibold">Meta Description <span class="text-muted">(150-160 chars)</span></label>
                    <textarea name="meta_description" id="f_meta_description" class="form-control" rows="2" maxlength="160"></textarea>
                    <div class="d-flex justify-content-end mt-1">
                        <small id="metaDescCount" class="text-muted">0/160</small>
                    </div>
                </div>
                <div class="col-12">
                    <label class="form-label small fw-semibold">Keywords</label>
                    <input type="text" name="keywords" id="f_keywords" class="form-control"
                           placeholder="SEO keywords, comma separated">
                </div>
                <div class="col-12">
                    <label class="form-label small fw-semibold">Canonical URL</label>
                    <input type="url" name="canonical_url" id="f_canonical_url" class="form-control">
                </div>
                <div class="col-12">
                    <label class="form-label small fw-semibold">Robots</label>
                    <select name="robots" id="f_robots" class="form-select">
                        <option value="index,follow">index, follow</option>
                        <option value="noindex,follow">noindex, follow</option>
                        <option value="noindex,nofollow">noindex, nofollow</option>
                    </select>
                </div>
            </div>
        </div>
    </div>

    {{-- OPEN GRAPH METADATA --}}
    <div class="card border-0 shadow-sm mb-4">
        <div class="card-header bg-body border-bottom d-flex align-items-center justify-content-between py-3">
            <div class="d-flex align-items-center gap-2">
                <span class="badge bg-purple rounded-circle p-1" style="width:28px;height:28px;background:#7c3aed!important;display:flex;align-items:center;justify-content:center">
                    <i class="ti ti-share" style="font-size:14px;color:white"></i>
                </span>
                <h6 class="mb-0 fw-semibold">Open Graph Metadata</h6>
            </div>
            <button type="button" class="btn btn-sm btn-outline-secondary" onclick="toggleOg()">
                <i class="ti ti-chevron-down" id="ogChevron"></i>
            </button>
        </div>
        <div class="card-body" id="ogBody" style="display:none">
            <div class="row g-3">
                <div class="col-12">
                    <label class="form-label small fw-semibold">OG Image URL</label>
                    <input type="url" name="og_image" id="f_og_image" class="form-control">
                </div>
                <div class="col-12">
                    <label class="form-label small fw-semibold">OG Title</label>
                    <input type="text" name="og_title" id="f_og_title" class="form-control">
                </div>
                <div class="col-12">
                    <label class="form-label small fw-semibold">OG Description</label>
                    <textarea name="og_description" id="f_og_description" class="form-control" rows="2"></textarea>
                </div>
            </div>
        </div>
    </div>

</div>

{{-- RIGHT SIDEBAR --}}
<div class="col-12 col-xl-4">

    {{-- AUTHOR INFO --}}
    <div class="card border-0 shadow-sm mb-4">
        <div class="card-header bg-body border-bottom py-3">
            <h6 class="mb-0 fw-semibold d-flex align-items-center gap-2">
                <i class="ti ti-user text-primary"></i> Author Information
            </h6>
        </div>
        <div class="card-body">
            <div class="row g-3">
                <div class="col-12">
                    <label class="form-label small fw-semibold">Select Author</label>
                    <select name="author_id" id="f_author_id" class="form-select">
                        <option value="">-- Select Author --</option>
                    </select>
                    <small class="text-muted">Leave empty to use logged-in user</small>
                </div>
                <div class="col-12">
                    <label class="form-label small fw-semibold">Author Name</label>
                    <input type="text" name="author_name" id="f_author_name" class="form-control">
                </div>
                <div class="col-12">
                    <label class="form-label small fw-semibold">Author Title</label>
                    <input type="text" name="author_title" id="f_author_title" class="form-control"
                           placeholder="e.g. Career Expert, HR Professional">
                </div>
                <div class="col-12">
                    <label class="form-label small fw-semibold">Author Avatar URL</label>
                    <input type="url" name="author_avatar" id="f_author_avatar" class="form-control"
                           placeholder="https://example.com/avatar.jpg">
                </div>
            </div>
        </div>
    </div>

    {{-- STATUS & DATES --}}
    <div class="card border-0 shadow-sm mb-4">
        <div class="card-header bg-body border-bottom py-3">
            <h6 class="mb-0 fw-semibold d-flex align-items-center gap-2">
                <i class="ti ti-calendar text-info"></i> Status & Dates
            </h6>
        </div>
        <div class="card-body">
            <div class="row g-3">
                <div class="col-12">
                    <div class="form-check form-switch">
                        <input class="form-check-input" type="checkbox" name="is_active" id="f_is_active" checked>
                        <label class="form-check-label" for="f_is_active">Active (visible to public)</label>
                    </div>
                </div>
                <div class="col-12">
                    <div class="form-check form-switch">
                        <input class="form-check-input" type="checkbox" name="is_featured" id="f_is_featured">
                        <label class="form-check-label" for="f_is_featured">Featured Post</label>
                    </div>
                </div>
                <div class="col-12">
                    <div class="form-check form-switch">
                        <input class="form-check-input" type="checkbox" name="is_published" id="f_is_published">
                        <label class="form-check-label" for="f_is_published">Published</label>
                    </div>
                </div>
                <div class="col-12">
                    <label class="form-label small fw-semibold">Publish Date</label>
                    <div class="input-group">
                        <input type="text" name="published_at" id="f_published_at" class="form-control datepicker-autoclose" placeholder="mm/dd/yyyy">
                        <span class="input-group-text"><i class="ti ti-calendar fs-5"></i></span>
                    </div>
                </div>
                <div class="col-12">
                    <label class="form-label small fw-semibold">Sort Order</label>
                    <input type="number" name="sort_order" id="f_sort_order" class="form-control" value="0" min="0">
                </div>
            </div>
        </div>
    </div>

    {{-- POSTING TIPS --}}
    <div class="card border-0 bg-body-secondary mb-4">
        <div class="card-body p-3">
            <h6 class="fw-semibold small mb-2 d-flex align-items-center gap-2">
                <i class="ti ti-bulb text-warning"></i> Blog Writing Tips
            </h6>
            <ul class="list-unstyled mb-0 small text-muted">
                <li class="mb-1"><i class="ti ti-check text-success me-1"></i>Use clear, descriptive titles for better SEO</li>
                <li class="mb-1"><i class="ti ti-check text-success me-1"></i>Add a compelling excerpt to capture attention</li>
                <li class="mb-1"><i class="ti ti-check text-success me-1"></i>Use headings (H2, H3) to structure content</li>
                <li class="mb-1"><i class="ti ti-check text-success me-1"></i>Include relevant tags for better discoverability</li>
                <li class="mb-1"><i class="ti ti-check text-success me-1"></i>Add a cover image with descriptive alt text</li>
                <li class="mb-1"><i class="ti ti-check text-success me-1"></i>Meta description should be 150-160 characters</li>
                <li><i class="ti ti-check text-success me-1"></i>Use OG tags for better social media sharing</li>
            </ul>
        </div>
    </div>

    <div id="formErrors" class="mt-3"></div>

</div>
</div>
</form>

</div>
</div>

<style>
.btn:disabled {
    opacity: 0.7;
    cursor: not-allowed;
}
.btn .spinner-border {
    vertical-align: middle;
}
.bg-purple {
    background-color: #7c3aed !important;
}
</style>

<script>
// ============================================================
// CONFIG
// ============================================================
const API_BASE = '/api/v1/blogs';
const IMAGE_API_BASE = '/api/v1/blog-images';
const CSRF_TOKEN = document.querySelector('meta[name="csrf-token"]')?.content ?? '';
let currentSlug = null;

// Get blog ID from URL
const blogId = window.location.pathname.split('/').pop();

// ============================================================
// HELPERS
// ============================================================
async function apiFetch(url, options = {}) {
    const res = await fetch(url, {
        ...options,
        headers: {
            'Content-Type': 'application/json',
            'Accept': 'application/json',
            'X-CSRF-TOKEN': CSRF_TOKEN,
            ...(options.headers ?? {}),
        },
    });
    const data = await res.json();
    if (!res.ok) throw data;
    return data;
}

function toast(msg, type = 'success') {
    if (typeof showToast === 'function') showToast(type, msg);
    else {
        const alert = `<div class="alert alert-${type} alert-dismissible fade show position-fixed top-0 end-0 m-3" style="z-index:9999" role="alert">${msg}<button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>`;
        document.body.insertAdjacentHTML('beforeend', alert);
        setTimeout(() => document.querySelector('.alert')?.remove(), 3000);
    }
}

// Rich editor sync functions for x-rich-editor component
function richEditorSync(editorId) {
    const editor = document.getElementById(editorId);
    const hidden = document.getElementById(editorId + '_hidden');
    if (editor && hidden) {
        let content = editor.innerHTML;
        if (!content || content === '<br>' || content === '<p><br></p>' || content === '<div><br></div>') {
            content = '';
        }
        hidden.value = content;
        return content;
    }
    return '';
}

function syncAllEditors() {
    richEditorSync('f_content_editor');
}

function toggleSeo() {
    const body = document.getElementById('seoBody');
    const chevron = document.getElementById('seoChevron');
    if (!body || !chevron) return;
    const visible = body.style.display !== 'none';
    body.style.display = visible ? 'none' : 'block';
    chevron.className = visible ? 'ti ti-chevron-down' : 'ti ti-chevron-up';
}

function toggleOg() {
    const body = document.getElementById('ogBody');
    const chevron = document.getElementById('ogChevron');
    if (!body || !chevron) return;
    const visible = body.style.display !== 'none';
    body.style.display = visible ? 'none' : 'block';
    chevron.className = visible ? 'ti ti-chevron-down' : 'ti ti-chevron-up';
}

// ============================================================
// COVER IMAGE UPLOAD WITH PREVIEW
// ============================================================
async function uploadCoverImage(input) {
    const file = input.files[0];
    if (!file) return;
    
    const allowedTypes = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
    if (!allowedTypes.includes(file.type)) {
        toast('Please select a valid image (JPG, PNG, GIF, WEBP)', 'error');
        return;
    }
    
    if (file.size > 5 * 1024 * 1024) {
        toast('Image must be less than 5MB', 'error');
        return;
    }
    
    const formData = new FormData();
    formData.append('cover_image', file);
    
    // Show preview immediately
    const previewImg = document.getElementById('coverImagePreviewImg');
    const previewDiv = document.getElementById('coverImagePreview');
    const placeholderDiv = document.getElementById('coverImagePlaceholder');
    const clearBtn = document.getElementById('clearCoverBtn');
    const reader = new FileReader();
    
    reader.onload = function(e) {
        previewImg.src = e.target.result;
        previewDiv.style.display = 'block';
        placeholderDiv.style.display = 'none';
        clearBtn.style.display = 'inline-block';
    };
    reader.readAsDataURL(file);
    
    try {
        const res = await fetch(`${IMAGE_API_BASE}/cover`, {
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': CSRF_TOKEN
            },
            body: formData
        });
        
        const data = await res.json();
        if (data.success) {
            document.getElementById('f_cover_image').value = data.url;
            // Update preview with actual URL
            previewImg.src = data.url;
            toast('Cover image uploaded successfully!', 'success');
        } else {
            throw new Error(data.message || 'Upload failed');
        }
    } catch (error) {
        console.error('Upload error:', error);
        toast('Failed to upload cover image: ' + error.message, 'error');
        // Revert preview
        previewDiv.style.display = 'none';
        placeholderDiv.style.display = 'block';
        clearBtn.style.display = 'none';
        document.getElementById('coverImageInput').value = '';
    }
}

function clearCoverImage() {
    document.getElementById('f_cover_image').value = '';
    document.getElementById('coverImagePreview').style.display = 'none';
    document.getElementById('coverImagePlaceholder').style.display = 'block';
    document.getElementById('clearCoverBtn').style.display = 'none';
    document.getElementById('coverImageInput').value = '';
    toast('Cover image cleared', 'info');
}


function loadCoverImagePreview(imageUrl) {
    if (imageUrl && imageUrl !== '') {
        const previewImg = document.getElementById('coverImagePreviewImg');
        const previewDiv = document.getElementById('coverImagePreview');
        const placeholderDiv = document.getElementById('coverImagePlaceholder');
        const clearBtn = document.getElementById('clearCoverBtn');
        
        if (previewImg && previewDiv && placeholderDiv && clearBtn) {
            previewImg.src = imageUrl;
            previewDiv.style.display = 'block';
            placeholderDiv.style.display = 'none';
            clearBtn.style.display = 'inline-block';
            document.getElementById('f_cover_image').value = imageUrl;
        }
    }
}

// ============================================================
// LOAD BLOG DATA
// ============================================================
async function loadBlogData() {
    try {
        const res = await apiFetch(`${API_BASE}/${blogId}?with_content=true`);
        const blog = res.data ?? res;
        
        currentSlug = blog.slug;
        
        // Populate form fields
        document.getElementById('f_title').value = blog.title || '';
        document.getElementById('f_excerpt').value = blog.excerpt || '';
        document.getElementById('f_cover_image_alt').value = blog.cover_image_alt || '';
        document.getElementById('f_cover_image_caption').value = blog.cover_image_caption || '';
        document.getElementById('f_author_id').value = blog.author?.id || blog.author_id || '';
        document.getElementById('f_author_name').value = blog.author?.name || blog.author_name || '';
        document.getElementById('f_author_title').value = blog.author?.title || blog.author_title || '';
        document.getElementById('f_author_avatar').value = blog.author?.avatar || blog.author_avatar || '';
        document.getElementById('f_is_active').checked = !!blog.is_active;
        document.getElementById('f_is_featured').checked = !!blog.is_featured;
        document.getElementById('f_is_published').checked = !!blog.is_published;
        document.getElementById('f_published_at').value = blog.published_at ? new Date(blog.published_at).toLocaleDateString('en-US') : '';
        document.getElementById('f_meta_title').value = blog.meta_title || '';
        document.getElementById('f_meta_description').value = blog.meta_description || '';
        document.getElementById('f_keywords').value = blog.keywords || '';
        document.getElementById('f_canonical_url').value = blog.canonical_url || '';
        document.getElementById('f_robots').value = blog.robots || 'index,follow';
        document.getElementById('f_og_image').value = blog.og_image || '';
        document.getElementById('f_og_title').value = blog.og_title || '';
        document.getElementById('f_og_description').value = blog.og_description || '';
        document.getElementById('f_sort_order').value = blog.sort_order || 0;
        
        // Set category
        if (blog.category) {
            document.getElementById('f_category').value = blog.category;
        }
        
        // Set tags
        if (blog.tags) {
            // Clear any existing tags first
            selectedTags = [];
            if (Array.isArray(blog.tags)) {
                blog.tags.forEach(tag => addTag(tag));
            } else if (typeof blog.tags === 'string') {
                const tagArray = blog.tags.split(',').map(t => t.trim()).filter(t => t);
                tagArray.forEach(tag => addTag(tag));
            }
        }
        
        // Set editor content using the x-rich-editor component's hidden field
        const contentHidden = document.getElementById('f_content_editor_hidden');
        if (contentHidden) {
            contentHidden.value = blog.content || '';
            // Also set the visual editor if it exists
            const editor = document.getElementById('f_content_editor');
            if (editor) {
                editor.innerHTML = blog.content || '';
            }
        }
        
        // Load cover image preview
        if (blog.cover_image) {
            loadCoverImagePreview(blog.cover_image);
        }
        
        // Hide spinner, show form
        document.getElementById('loadingSpinner').style.display = 'none';
        document.getElementById('blogForm').style.display = 'block';
        
        // Update char counters
        updateCharCounters();
        
    } catch (e) {
        console.error('Failed to load blog:', e);
        toast('Failed to load blog post', 'error');
        document.getElementById('loadingSpinner').innerHTML = '<div class="alert alert-danger">Failed to load blog post. <a href="{{ route("blogs.index") }}">Go back</a></div>';
    }
}

function updateCharCounters() {
    const excerpt = document.getElementById('f_excerpt');
    const excerptCount = document.getElementById('excerptCharCount');
    if (excerpt && excerptCount) {
        excerptCount.textContent = `${excerpt.value.length}/500`;
    }
    const metaTitle = document.getElementById('f_meta_title');
    const metaTitleCount = document.getElementById('metaTitleCount');
    if (metaTitle && metaTitleCount) {
        metaTitleCount.textContent = `${metaTitle.value.length}/60`;
    }
    const metaDesc = document.getElementById('f_meta_description');
    const metaDescCount = document.getElementById('metaDescCount');
    if (metaDesc && metaDescCount) {
        metaDescCount.textContent = `${metaDesc.value.length}/160`;
    }
}

// ============================================================
// FORM SUBMISSION
// ============================================================
async function submitBlogPost(mode = 'live') {
    const isDraft = mode === 'draft';
    
    // Sync rich editor content
    syncAllEditors();
    
    const submitBtn = document.getElementById('submitBlogBtn');
    const draftBtn = document.getElementById('submitDraftBtn');
    const btnText = document.getElementById(isDraft ? 'submitDraftBtnText' : 'submitBlogBtnText');
    const btnSpinner = document.getElementById(isDraft ? 'submitDraftBtnSpinner' : 'submitBlogBtnSpinner');
    
    const currentBtn = isDraft ? draftBtn : submitBtn;
    
    if (!currentBtn) return;
    
    currentBtn.disabled = true;
    if (btnSpinner) btnSpinner.classList.remove('d-none');
    
    let originalText = '';
    if (btnText) {
        originalText = btnText.innerHTML;
        btnText.innerHTML = isDraft ? '<i class="ti ti-device-floppy me-2"></i>Saving...' : '<i class="ti ti-send me-2"></i>Publishing...';
    }
    
    const content = document.getElementById('f_content_editor_hidden')?.value || '';
    
    const form = document.getElementById('blogForm');
    const data = {};
    new FormData(form).forEach((v, k) => data[k] = v);
    
    data.content = content;
    
    // Handle tags
    if (data.tags && typeof data.tags === 'string') {
        data.tags = data.tags.split(',').map(t => t.trim()).filter(t => t);
    }
    
    // Handle booleans
    data.is_active = data.is_active === 'on' || data.is_active === true;
    data.is_featured = data.is_featured === 'on' || data.is_featured === true;
    data.is_published = data.is_published === 'on' || data.is_published === true;
    
    if (isDraft) {
        data.is_published = false;
        data.is_active = true;
    }
    
    // Convert date
    if (data.published_at) {
        const parts = data.published_at.split('/');
        if (parts.length === 3) {
            data.published_at = `${parts[2]}-${parts[0].padStart(2, '0')}-${parts[1].padStart(2, '0')}`;
        }
    }
    
    // Validation
    const errors = [];
    if (!data.title) errors.push('Blog title is required');
    if (!isDraft && !data.content) errors.push('Blog content is required');
    
    if (errors.length) {
        currentBtn.disabled = false;
        if (btnSpinner) btnSpinner.classList.add('d-none');
        if (btnText) btnText.innerHTML = originalText;
        
        const errorDiv = document.getElementById('formErrors');
        if (errorDiv) {
            errorDiv.innerHTML = `
                <div class="alert alert-danger">
                    <strong><i class="ti ti-alert-circle me-2"></i>Please fix:</strong>
                    <ul class="mb-0 mt-2">${errors.map(e => `<li>${escapeHtml(e)}</li>`).join('')}</ul>
                </div>`;
            errorDiv.scrollIntoView({ behavior: 'smooth' });
        }
        toast(errors[0], 'error');
        return;
    }
    
    try {
        const res = await apiFetch(`${API_BASE}/${currentSlug}`, { method: 'PATCH', body: JSON.stringify(data) });
        
        currentBtn.disabled = false;
        if (btnSpinner) btnSpinner.classList.add('d-none');
        if (btnText) btnText.innerHTML = originalText;
        
        toast(isDraft ? 'Blog draft updated successfully!' : 'Blog post published successfully!', 'success');
        
        setTimeout(() => {
            window.location.href = '/blogs';
        }, 1500);
        
    } catch (err) {
        currentBtn.disabled = false;
        if (btnSpinner) btnSpinner.classList.add('d-none');
        if (btnText) btnText.innerHTML = originalText;
        
        let errorMessage = '';
        if (typeof err === 'string') errorMessage = err;
        else if (err.message) errorMessage = err.message;
        else if (err.errors) {
            const errorList = [];
            Object.values(err.errors).forEach(e => {
                if (Array.isArray(e)) errorList.push(...e);
                else errorList.push(e);
            });
            errorMessage = errorList.join(', ');
        } else {
            errorMessage = 'Failed to update blog. Please try again.';
        }
        
        toast(errorMessage, 'error');
        
        const errorDiv = document.getElementById('formErrors');
        if (errorDiv) {
            errorDiv.innerHTML = `
                <div class="alert alert-danger">
                    <strong><i class="ti ti-alert-circle me-2"></i>Error:</strong>
                    <div class="mt-1">${escapeHtml(errorMessage)}</div>
                </div>`;
        }
    }
}

function escapeHtml(str) {
    if (!str) return '';
    return String(str).replace(/[&<>]/g, function(m) {
        if (m === '&') return '&amp;';
        if (m === '<') return '&lt;';
        if (m === '>') return '&gt;';
        return m;
    });
}

// ============================================================
// CHAR COUNTERS
// ============================================================
function initCharCounters() {
    const excerptField = document.getElementById('f_excerpt');
    const excerptCount = document.getElementById('excerptCharCount');
    if (excerptField && excerptCount) {
        excerptField.addEventListener('input', () => {
            const len = excerptField.value.length;
            excerptCount.textContent = `${len}/500`;
            if (len > 500) {
                excerptCount.classList.add('text-danger');
                excerptField.value = excerptField.value.substring(0, 500);
                excerptCount.textContent = '500/500';
            } else {
                excerptCount.classList.remove('text-danger');
            }
        });
    }
    
    const metaTitle = document.getElementById('f_meta_title');
    const metaTitleCount = document.getElementById('metaTitleCount');
    if (metaTitle && metaTitleCount) {
        metaTitle.addEventListener('input', () => {
            metaTitleCount.textContent = `${metaTitle.value.length}/60`;
        });
    }
    
    const metaDesc = document.getElementById('f_meta_description');
    const metaDescCount = document.getElementById('metaDescCount');
    if (metaDesc && metaDescCount) {
        metaDesc.addEventListener('input', () => {
            const len = metaDesc.value.length;
            metaDescCount.textContent = `${len}/160`;
            if (len > 160) {
                metaDescCount.classList.add('text-danger');
            } else {
                metaDescCount.classList.remove('text-danger');
            }
        });
    }
}

// ============================================================
// LOAD AUTHORS
// ============================================================
async function loadAuthors() {
    try {
        const res = await apiFetch('/api/v1/users/list');
        const select = document.getElementById('f_author_id');
        if (select) {
            select.innerHTML = '<option value="">-- Select Author --</option>';
            (res.data || []).forEach(user => {
                const opt = document.createElement('option');
                opt.value = user.id;
                opt.textContent = user.name;
                select.appendChild(opt);
            });
        }
    } catch (e) {
        console.error('Failed to load authors:', e);
    }
}

// ============================================================
// INITIALIZE
// ============================================================
document.addEventListener('DOMContentLoaded', () => {
    loadAuthors();
    initCharCounters();    
    loadCategoriesList();
    loadTagsList();
    loadBlogData();
    
    if (typeof $ !== 'undefined' && $.fn.datepicker) {
        $('.datepicker-autoclose').datepicker({ 
            autoclose: true, 
            todayHighlight: true, 
            format: 'mm/dd/yyyy' 
        });
    }
});
</script>
<script>
    // ============================================================
// CATEGORY AUTOCOMPLETE
// ============================================================
let categoriesList = [];
let tagsList = [];
let selectedTags = [];

async function loadCategoriesList() {
    try {
        const res = await apiFetch('/api/v1/blogs/categories/list');
        categoriesList = res.data || [];
        
        // Initialize category input with datalist-like behavior
        const categoryInput = document.getElementById('f_category');
        const suggestionsDiv = document.getElementById('categorySuggestions');
        
        if (categoryInput) {
            categoryInput.addEventListener('input', function() {
                const value = this.value.toLowerCase().trim();
                if (!value) {
                    suggestionsDiv.style.display = 'none';
                    return;
                }
                
                const matches = categoriesList.filter(cat => 
                    cat.toLowerCase().includes(value)
                ).slice(0, 10);
                
                if (matches.length > 0) {
                    suggestionsDiv.innerHTML = matches.map(match => 
                        `<div class="dropdown-item" style="cursor:pointer" onclick="selectCategory('${match.replace(/'/g, "\\'")}')">${escapeHtml(match)}</div>`
                    ).join('');
                    suggestionsDiv.style.display = 'block';
                } else {
                    // Show "Create new" option
                    suggestionsDiv.innerHTML = `<div class="dropdown-item text-primary" style="cursor:pointer" onclick="selectCategory('${escapeHtml(value)}')">
                        <i class="ti ti-plus me-1"></i> Create "${escapeHtml(value)}"
                    </div>`;
                    suggestionsDiv.style.display = 'block';
                }
            });
            
            categoryInput.addEventListener('blur', function() {
                setTimeout(() => {
                    suggestionsDiv.style.display = 'none';
                }, 200);
            });
        }
    } catch (e) {
        console.error('Failed to load categories:', e);
    }
}

function selectCategory(category) {
    const categoryInput = document.getElementById('f_category');
    categoryInput.value = category.toLowerCase().replace(/\s+/g, '-');
    document.getElementById('categorySuggestions').style.display = 'none';
}

// ============================================================
// TAGS AUTOCOMPLETE WITH MULTI-SELECT
// ============================================================
async function loadTagsList() {
    try {
        const res = await apiFetch('/api/v1/blogs/tags/list');
        tagsList = res.data || [];
        
        const tagsInput = document.getElementById('f_tags_input');
        const suggestionsDiv = document.getElementById('tagsSuggestions');
        
        if (tagsInput) {
            tagsInput.addEventListener('input', function() {
                const value = this.value.toLowerCase().trim();
                if (!value) {
                    suggestionsDiv.style.display = 'none';
                    return;
                }
                
                const matches = tagsList.filter(tag => 
                    tag.toLowerCase().includes(value) && !selectedTags.includes(tag)
                ).slice(0, 10);
                
                if (matches.length > 0) {
                    suggestionsDiv.innerHTML = matches.map(match => 
                        `<div class="dropdown-item" style="cursor:pointer" onclick="addTag('${match.replace(/'/g, "\\'")}')">${escapeHtml(match)}</div>`
                    ).join('');
                    suggestionsDiv.style.display = 'block';
                } else {
                    // Show "Add new tag" option
                    suggestionsDiv.innerHTML = `<div class="dropdown-item text-primary" style="cursor:pointer" onclick="addTag('${escapeHtml(value)}')">
                        <i class="ti ti-plus me-1"></i> Add "${escapeHtml(value)}"
                    </div>`;
                    suggestionsDiv.style.display = 'block';
                }
            });
            
            tagsInput.addEventListener('keydown', function(e) {
                if (e.key === 'Enter') {
                    e.preventDefault();
                    const value = this.value.trim();
                    if (value) {
                        addTag(value);
                    }
                }
            });
            
            tagsInput.addEventListener('blur', function() {
                setTimeout(() => {
                    suggestionsDiv.style.display = 'none';
                }, 200);
            });
        }
        
        // Load existing tags if editing
        const existingTags = document.getElementById('f_tags').value;
        if (existingTags) {
            try {
                const tags = JSON.parse(existingTags);
                if (Array.isArray(tags)) {
                    tags.forEach(tag => addTag(tag));
                }
            } catch(e) {
                // Handle comma-separated string
                const tags = existingTags.split(',').map(t => t.trim()).filter(t => t);
                tags.forEach(tag => addTag(tag));
            }
        }
        
    } catch (e) {
        console.error('Failed to load tags:', e);
    }
}

function addTag(tag) {
    tag = tag.toLowerCase().trim().replace(/\s+/g, '-');
    if (!tag || selectedTags.includes(tag)) return;
    
    selectedTags.push(tag);
    if (!tagsList.includes(tag)) {
        tagsList.push(tag);
    }
    
    renderSelectedTags();
    
    // Clear input
    const tagsInput = document.getElementById('f_tags_input');
    tagsInput.value = '';
    document.getElementById('tagsSuggestions').style.display = 'none';
}

function removeTag(tag) {
    selectedTags = selectedTags.filter(t => t !== tag);
    renderSelectedTags();
}

function renderSelectedTags() {
    const container = document.getElementById('selectedTags');
    const hiddenInput = document.getElementById('f_tags');
    
    container.innerHTML = selectedTags.map(tag => `
        <span class="badge bg-primary bg-opacity-10 text-primary px-3 py-2 rounded-pill" style="font-size:12px">
            ${escapeHtml(tag)}
            <i class="ti ti-x ms-1" style="cursor:pointer; font-size:10px" onclick="removeTag('${tag.replace(/'/g, "\\'")}')"></i>
        </span>
    `).join('');
    
    hiddenInput.value = JSON.stringify(selectedTags);
}

// ============================================================
// LOAD EXISTING CATEGORY VALUE
// ============================================================
function loadExistingCategory(category) {
    if (category) {
        document.getElementById('f_category').value = category;
    }
}

function loadExistingTags(tags) {
    if (tags) {
        if (Array.isArray(tags)) {
            tags.forEach(tag => addTag(tag));
        } else if (typeof tags === 'string') {
            const tagArray = tags.split(',').map(t => t.trim()).filter(t => t);
            tagArray.forEach(tag => addTag(tag));
        }
    }
}
</script>

@endsection