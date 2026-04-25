<script>
// ============================================================
// CONFIG & STATE
// ============================================================
const API_BASE = '/api/v1/blogs';
const CSRF_TOKEN = document.querySelector('meta[name="csrf-token"]')?.content ?? '';
let currentPage = 1;
let currentSlug = null;
let debounceTimer = null;

// ============================================================
// UTILS
// ============================================================
function esc(str) {
    if (str == null) return '';
    return String(str).replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;');
}

function formatDate(d) {
    if (!d) return '—';
    return new Date(d).toLocaleDateString('en-UG', { year:'numeric', month:'short', day:'numeric' });
}

function toast(msg, type = 'success') {
    if (typeof showToast === 'function') {
        showToast(type, msg);
    }
}

function bsModal(id) {
    const el = document.getElementById(id);
    if (!el._bsModal) {
        el._bsModal = new bootstrap.Modal(el, { backdrop: 'static', keyboard: false });
    }
    return el._bsModal;
}

function statusBadge(blog) {
    if (!blog.is_active) return '<span class="badge bg-secondary">Inactive</span>';
    
    // Check if it's a draft (not published)
    if (!blog.is_published) {
        return '<span class="badge bg-warning text-dark">Draft</span>';
    }
    
    // Check if published but future date
    if (blog.is_published && blog.published_at && new Date(blog.published_at) > new Date()) {
        return '<span class="badge bg-info">Scheduled</span>';
    }
    
    // Published and active
    if (blog.is_published && blog.published_at && new Date(blog.published_at) <= new Date()) {
        return '<span class="badge bg-success">Published</span>';
    }
    
    return '<span class="badge bg-secondary">Inactive</span>';
}

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

// ============================================================
// FILTERS
// ============================================================
function debounceLoad() {
    clearTimeout(debounceTimer);
    debounceTimer = setTimeout(() => loadBlogs(1), 400);
}

function resetFilters() {
    ['filterSearch', 'filterCategory', 'filterStatus', 'filterPublished', 'filterFeatured', 'filterAuthor']
        .forEach(id => {
            const el = document.getElementById(id);
            if (el) el.value = '';
        });
    loadBlogs(1);
}

function buildQueryString(page) {
    const params = new URLSearchParams({ page, per_page: 15 });
    const search = document.getElementById('filterSearch')?.value.trim();
    const category = document.getElementById('filterCategory')?.value;
    const status = document.getElementById('filterStatus')?.value;
    const published = document.getElementById('filterPublished')?.value;
    const featured = document.getElementById('filterFeatured')?.value;
    const authorId = document.getElementById('filterAuthor')?.value;
    
    if (search) params.set('search', search);
    if (category && category !== '') params.set('category', category);
    if (status !== '') params.set('is_active', status);
    
    // Handle published filter
    if (published && published !== '') {
        if (published === 'future') {
            params.set('is_published', '1');
            params.set('published_future', '1');
        } else {
            params.set('is_published', published);
        }
    }
    
    if (featured !== '') params.set('is_featured', featured);
    if (authorId && authorId !== '') params.set('author_id', authorId);
    
    return params.toString();
}

// ============================================================
// LOAD FILTERS
// ============================================================
async function loadFilters() {
    try {
        const res = await apiFetch(`${API_BASE}/categories`);
        const select = document.getElementById('filterCategory');
        if (select) {
            select.innerHTML = '<option value="">All Categories</option>';
            (res.data || []).forEach(cat => {
                const opt = document.createElement('option');
                opt.value = cat.category;
                opt.textContent = `${cat.category} (${cat.posts_count})`;
                select.appendChild(opt);
            });
        }

        const authorsRes = await apiFetch('/api/v1/users/list');
        const authorSelect = document.getElementById('filterAuthor');
        const formAuthorSelect = document.getElementById('formAuthorId');
        
        if (authorSelect) {
            authorSelect.innerHTML = '<option value="">All Authors</option>';
        }
        if (formAuthorSelect) {
            formAuthorSelect.innerHTML = '<option value="">-- Select Author --</option>';
        }
        
        (authorsRes.data || []).forEach(user => {
            if (authorSelect) {
                const opt = document.createElement('option');
                opt.value = user.id;
                opt.textContent = user.name;
                authorSelect.appendChild(opt);
            }
            if (formAuthorSelect) {
                const opt = document.createElement('option');
                opt.value = user.id;
                opt.textContent = user.name;
                formAuthorSelect.appendChild(opt);
            }
        });
    } catch (e) {
        console.error('Failed to load filters:', e);
    }
}

// ============================================================
// LOAD & RENDER TABLE
// ============================================================
async function loadBlogs(page = 1) {
    currentPage = page;
    const tbody = document.getElementById('blogPostsBody');
    tbody.innerHTML = '<tr><td colspan="8" class="text-center py-4"><div class="spinner-border text-primary"></div></td></tr>';
    
    try {
        const res = await apiFetch(`${API_BASE}?${buildQueryString(page)}`);
        renderTable(res.data);
        renderPagination(res.meta ?? {});
    } catch (e) {
        tbody.innerHTML = '</tr><td colspan="8" class="text-center text-danger py-3">Failed to load blog posts.</td></tr>';
    }
}

function renderTable(blogs) {
    const tbody = document.getElementById('blogPostsBody');
    if (!blogs || blogs.length === 0) {
        tbody.innerHTML = '<tr><td colspan="8" class="text-center py-4 text-muted">No blog posts found.ERC</div> </td></tr>';
        return;
    }

    tbody.innerHTML = blogs.map((blog, i) => `
        <tr>
            <td class="text-muted">${((currentPage - 1) * 15) + i + 1}</td>
            <td>
                <div class="fw-semibold">${esc(blog.title)}</div>
                <div class="small text-muted">${esc(blog.slug)}</div>
            </td>
            <td><span class="badge bg-light text-dark">${esc(blog.category || 'general')}</span></td>
            <td>${esc(blog.author?.name || blog.author_name || '—')}</td>
            <td>${statusBadge(blog)}</td>
            <td>${blog.published_at ? formatDate(blog.published_at) : '—'}</td>
            <td>${(blog.view_count || 0).toLocaleString()}</td>
            <td>
                <div class="d-flex align-items-center gap-1">
                    <button class="btn btn-sm bg-primary-subtle text-primary p-1" title="View" onclick="openView('${blog.slug}')">
                        <i class="ti ti-eye" style="font-size:1.25rem;"></i>
                    </button>
                    <button class="btn btn-sm bg-success-subtle text-success p-1" title="Edit" onclick="window.location.href='/blog/edit/${blog.slug}'">
                        <i class="ti ti-pencil" style="font-size:1.25rem;"></i>
                    </button>
                    <div class="dropstart">
                        <button class="btn btn-sm btn-light dropdown-toggle" data-bs-toggle="dropdown">
                            <i class="ti ti-dots-vertical"></i>
                        </button>
                        <ul class="dropdown-menu">
                            <li><a class="dropdown-item" href="#" onclick="openStatus('${blog.slug}',event)">
                                <i class="ti ti-settings me-2"></i>Update Status</a></li>
                            <li><hr class="dropdown-divider"></li>
                            <li><a class="dropdown-item text-danger" href="#" onclick="openDelete('${blog.slug}','${esc(blog.title)}',event)">
                                <i class="ti ti-trash me-2"></i>Delete</a></li>
                        </ul>
                    </div>
                </div>
            </td>
        </tr>
    `).join('');
}

function renderPagination(meta) {
    const paginationInfo = document.getElementById('paginationInfo');
    const paginationLinks = document.getElementById('paginationLinks');
    
    if (paginationInfo) {
        paginationInfo.textContent = meta.total ? `Showing ${meta.from}–${meta.to} of ${meta.total}` : '';
    }
    
    if (!paginationLinks) return;
    
    const last = meta.last_page ?? 1;
    const cur = meta.current_page ?? 1;
    const pages = [];
    for (let p = Math.max(1, cur - 2); p <= Math.min(last, cur + 2); p++) pages.push(p);
    
    let html = '<ul class="pagination pagination-md mb-0">';
    html += `<li class="page-item ${cur > 1 ? '' : 'disabled'}"><a class="page-link" href="#" onclick="loadBlogs(1);return false;">«</a></li>`;
    html += `<li class="page-item ${cur > 1 ? '' : 'disabled'}"><a class="page-link" href="#" onclick="loadBlogs(${cur - 1});return false;">‹</a></li>`;
    
    if (pages[0] > 1) html += `<li class="page-item disabled"><span class="page-link">…</span></li>`;
    pages.forEach(p => {
        html += p === cur
            ? `<li class="page-item active"><span class="page-link">${p}</span></li>`
            : `<li class="page-item"><a class="page-link" href="#" onclick="loadBlogs(${p});return false;">${p}</a></li>`;
    });
    if (pages[pages.length - 1] < last) html += `<li class="page-item disabled"><span class="page-link">…</span></li>`;
    
    html += `<li class="page-item ${cur < last ? '' : 'disabled'}"><a class="page-link" href="#" onclick="loadBlogs(${cur + 1});return false;">›</a></li>`;
    html += `<li class="page-item ${cur < last ? '' : 'disabled'}"><a class="page-link" href="#" onclick="loadBlogs(${last});return false;">»</a></li>`;
    html += '</ul>';
    paginationLinks.innerHTML = html;
}

function updateStats(editorId) {
    const el = document.getElementById(editorId);
    if (!el) return;
    const text = el.innerText || '';
    const words = text.trim() ? text.trim().split(/\s+/).length : 0;
    const chars = text.length;
    const wEl = document.getElementById(editorId + '-words');
    const cEl = document.getElementById(editorId + '-chars');
    if (wEl) wEl.textContent = words + (words === 1 ? ' word' : ' words');
    if (cEl) cEl.textContent = chars + (chars === 1 ? ' char' : ' chars');
}



// ============================================================
// VIEW MODAL
// ============================================================
async function openView(slug) {
    currentSlug = slug;
    document.getElementById('viewModalTitle').textContent = 'Loading…';
    document.getElementById('viewModalBody').innerHTML = '<div class="text-center py-5"><div class="spinner-border text-primary"></div></div>';
    bsModal('viewModal').show();

    try {
        const res = await apiFetch(`${API_BASE}/${slug}`);
        const blog = res.data ?? res;
        document.getElementById('viewModalTitle').textContent = blog.title;
        document.getElementById('viewModalBody').innerHTML = buildViewHtml(blog);
    } catch (e) {
        document.getElementById('viewModalBody').innerHTML = '<div class="alert alert-danger">Failed to load blog post.</div>';
    }
}

function buildViewHtml(blog) {
    return `
    <div class="row">
        <div class="col-md-8">
            <h4 class="mb-3">${esc(blog.title)}</h4>
            ${blog.cover_image ? `<img src="${esc(blog.cover_image)}" class="img-fluid rounded mb-3" alt="${esc(blog.cover_image_alt || blog.title)}">` : ''}
            <div class="mb-3">
                <span class="badge bg-light text-dark">${esc(blog.category)}</span>
                ${(blog.tags || []).map(t => `<span class="badge bg-secondary ms-1">${esc(t)}</span>`).join('')}
            </div>
            <div class="mb-3"><strong>Excerpt:</strong> ${esc(blog.excerpt)}</div>
            <div class="mb-3"><strong>Content:</strong> <div class="border rounded p-3 bg-light">${blog.content || '—'}</div></div>
        </div>
        <div class="col-md-4">
            <div class="card mb-3">
                <div class="card-header">Metadata</div>
                <div class="card-body">
                    <div><strong>Author:</strong> ${esc(blog.author?.name || blog.author_name || '—')}</div>
                    <div><strong>Reading Time:</strong> ${blog.reading_time || '—'}</div>
                    <div><strong>Views:</strong> ${(blog.view_count || 0).toLocaleString()}</div>
                    <div><strong>Shares:</strong> ${(blog.share_count || 0).toLocaleString()}</div>
                    <div><strong>Published:</strong> ${formatDate(blog.published_at)}</div>
                </div>
            </div>
            <div class="card">
                <div class="card-header">SEO</div>
                <div class="card-body">
                    <div><strong>SEO Score:</strong> ${blog.seo_score || '—'}/100</div>
                    <div><strong>Meta Title:</strong> ${esc(blog.meta_title)}</div>
                    <div><strong>Meta Description:</strong> ${esc(blog.meta_description)}</div>
                    <div><strong>Keywords:</strong> ${esc(blog.keywords)}</div>
                    <div><strong>Pinged:</strong> ${blog.is_pinged ? '✅ Yes' : '❌ No'}</div>
                    <div><strong>Indexed:</strong> ${blog.is_indexed ? '✅ Yes' : '❌ No'}</div>
                </div>
            </div>
        </div>
    </div>`;
}



// ============================================================
// DELETE MODAL
// ============================================================
function openDelete(slug, title, e) {
    e?.preventDefault();
    currentSlug = slug;
    document.getElementById('deleteBlogTitle').textContent = title;
    bsModal('deleteModal').show();
}

async function confirmDelete() {
    const btn = document.getElementById('confirmDeleteBtn');
    const spinner = document.getElementById('deleteBtnSpinner');
    btn.disabled = true;
    spinner.classList.remove('d-none');

    try {
        await apiFetch(`${API_BASE}/${currentSlug}`, { method: 'DELETE' });
        bsModal('deleteModal').hide();
        toast('Blog post deleted.', 'success');
        loadBlogs(currentPage);
    } catch (e) {
        toast('Failed to delete blog post.', 'error');
    } finally {
        btn.disabled = false;
        spinner.classList.add('d-none');
    }
}



async function doStatusAction(action) {
    const msgDiv = document.getElementById('statusActionMsg');
    msgDiv.innerHTML = `<div class="d-flex align-items-center gap-2 text-muted"><div class="spinner-border spinner-border-sm"></div> Updating...</div>`;

    try {
        await apiFetch(`${API_BASE}/${currentSlug}/${action}`, { method: 'PATCH' });
        
        msgDiv.innerHTML = `<div class="alert alert-success py-2 mb-0">✓ Blog ${action}d successfully!</div>`;
        toast(`Blog ${action}d successfully!`, 'success');
        loadBlogs(currentPage);
        
        setTimeout(() => {
            const modal = bootstrap.Modal.getInstance(document.getElementById('statusModal'));
            if (modal) modal.hide();
        }, 1500);
        
    } catch (e) {
        msgDiv.innerHTML = `<div class="alert alert-danger py-2 mb-0">❌ Failed to ${action} blog post.</div>`;
        toast(`Failed to ${action} blog post`, 'error');
    }
}

// ============================================================
// SEO FUNCTIONS FOR INDIVIDUAL BLOG - ONE BY ONE
// ============================================================

async function pingThisBlog() {
    if (!currentSlug) return;
    
    const pingBtn = document.getElementById('pingBtn');
    const msgDiv = document.getElementById('statusActionMsg');
    
    if (!pingBtn) return;
    
    // Change button to loading state
    const originalText = pingBtn.innerHTML;
    pingBtn.disabled = true;
    pingBtn.innerHTML = '<div class="spinner-border spinner-border-sm me-1"></div>Pinging...';
    
    msgDiv.innerHTML = '<div class="text-muted small">Submitting to IndexNow...</div>';

    try {
        const response = await fetch(`/api/v1/blog-seo/ping-blog/${currentSlug}`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json',
                'X-CSRF-TOKEN': CSRF_TOKEN
            }
        });
        
        const result = await response.json();
        
        if (result.success) {
            msgDiv.innerHTML = `
                <div class="alert alert-success py-2 mb-0">
                    <i class="ti ti-check me-1"></i> 
                    ${result.message || 'Blog pinged successfully! Notified Bing, Yandex & other search engines.'}
                </div>`;
            toast('Blog pinged successfully!', 'success');
            
            // Update the ping badge in modal
            const statusPingBadge = document.getElementById('statusPingBadge');
            if (statusPingBadge) {
                statusPingBadge.innerHTML = '<span class="badge bg-success">✅ Pinged</span>';
            }
            
            // Reload the table to update the ping status
            setTimeout(() => {
                loadBlogs(currentPage);
            }, 1000);
        } else {
            throw new Error(result.message || 'Ping failed');
        }
        
    } catch (error) {
        console.error('Ping error:', error);
        msgDiv.innerHTML = `
            <div class="alert alert-danger py-2 mb-0">
                <i class="ti ti-alert-circle me-1"></i> 
                ${error.message || 'Failed to ping blog. Please try again.'}
            </div>`;
        toast('Ping failed: ' + (error.message || 'Unknown error'), 'error');
    } finally {
        // Reset button
        pingBtn.disabled = false;
        pingBtn.innerHTML = originalText;
    }
}

async function indexThisBlog() {
    if (!currentSlug) return;
    
    const indexBtn = document.getElementById('indexBtn');
    const msgDiv = document.getElementById('statusActionMsg');
    
    if (!indexBtn) return;
    
    // Change button to loading state
    const originalText = indexBtn.innerHTML;
    indexBtn.disabled = true;
    indexBtn.innerHTML = '<div class="spinner-border spinner-border-sm me-1"></div>Submitting...';
    
    msgDiv.innerHTML = '<div class="text-muted small">Submitting to Google Indexing API...</div>';

    try {
        const response = await fetch(`/api/v1/blog-seo/index-blog/${currentSlug}`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json',
                'X-CSRF-TOKEN': CSRF_TOKEN
            }
        });
        
        const result = await response.json();
        
        if (result.success) {
            msgDiv.innerHTML = `
                <div class="alert alert-success py-2 mb-0">
                    <i class="ti ti-check me-1"></i> 
                    ${result.message || 'URL submitted to Google Indexing API. Expect indexing within minutes to hours.'}
                </div>`;
            toast('Submitted to Google!', 'success');
            
            // Update quota display
            if (result.quota_left !== undefined) {
                const quotaSpan = document.getElementById('googleQuotaLeft');
                if (quotaSpan) quotaSpan.textContent = result.quota_left;
            }
            
            // Update the index badge in modal
            const statusIndexBadge = document.getElementById('statusIndexBadge');
            if (statusIndexBadge) {
                statusIndexBadge.innerHTML = '<span class="badge bg-info">⏳ Submitted</span>';
            }
            
            // Reload the table to update the index status
            setTimeout(() => {
                loadBlogs(currentPage);
            }, 1000);
        } else {
            throw new Error(result.message || 'Submission failed');
        }
        
    } catch (error) {
        console.error('Index error:', error);
        msgDiv.innerHTML = `
            <div class="alert alert-danger py-2 mb-0">
                <i class="ti ti-alert-circle me-1"></i> 
                ${error.message || 'Failed to submit to Google. Please check API configuration.'}
            </div>`;
        toast('Google submission failed: ' + (error.message || 'Unknown error'), 'error');
    } finally {
        // Reset button
        indexBtn.disabled = false;
        indexBtn.innerHTML = originalText;
    }
}

function updatePingBadge(success) {
    const el = document.getElementById('statusPingBadge');
    if (el) {
        el.innerHTML = success
            ? '<span class="badge bg-success">✅ Pinged</span>'
            : '<span class="badge bg-danger">❌ Failed</span>';
    }
}

function updateIndexBadge(success) {
    const el = document.getElementById('statusIndexBadge');
    if (el) {
        el.innerHTML = success
            ? '<span class="badge bg-success">✅ Submitted</span>'
            : '<span class="badge bg-warning text-dark">⏳ Not submitted</span>';
    }
}

async function loadBlogSeoStatus(slug) {
    try {
        const res = await apiFetch(`${API_BASE}/${slug}`);
        const blog = res.data ?? res;
        
        const pingEl = document.getElementById('statusPingBadge');
        const indexEl = document.getElementById('statusIndexBadge');
        
        if (pingEl) {
            if (blog.is_pinged) {
                pingEl.innerHTML = '<span class="badge bg-success">✅ Pinged</span>';
            } else if (blog.last_pinged_at) {
                pingEl.innerHTML = '<span class="badge bg-danger">❌ Failed</span>';
            } else {
                pingEl.innerHTML = '<span class="badge bg-secondary">Not pinged</span>';
            }
        }
        
        if (indexEl) {
            if (blog.is_indexed) {
                indexEl.innerHTML = '<span class="badge bg-success">✅ Indexed</span>';
            } else if (blog.submitted_to_indexing) {
                indexEl.innerHTML = '<span class="badge bg-info">⏳ Submitted</span>';
            } else {
                indexEl.innerHTML = '<span class="badge bg-secondary">Not submitted</span>';
            }
        }
        
        // Update quota display if available
        try {
            const quotaRes = await apiFetch('/api/v1/blog-seo/indexing-stats');
            const quotaLeft = quotaRes.data?.quota_remaining ?? '?';
            const quotaSpan = document.getElementById('googleQuotaLeft');
            if (quotaSpan) quotaSpan.textContent = quotaLeft;
        } catch(e) {
            // Ignore quota fetch errors
        }
        
    } catch(e) {
        console.error('Failed to load blog SEO status:', e);
    }
}

// Simple stats loader for the badge
async function loadSeoStats() {
    try {
        const res = await apiFetch('/api/v1/blog-seo/ping-stats');
        const unpingedCount = res.data?.not_pinged ?? 0;
        const badge = document.getElementById('pendingIndexBadge');
        if (badge) {
            if (unpingedCount > 0) {
                badge.textContent = unpingedCount;
                badge.className = 'badge bg-warning text-dark ms-1';
            } else {
                badge.textContent = '✓';
                badge.className = 'badge bg-success ms-1';
            }
        }
    } catch(e) { 
        console.error('Failed to load SEO stats:', e);
    }
}

function updatePingBadge(success) {
    const el = document.getElementById('statusPingBadge');
    if (el) el.innerHTML = success
        ? '<span class="badge bg-success">✅ Pinged</span>'
        : '<span class="badge bg-danger">❌ Failed</span>';
}

function updateIndexBadge(success) {
    const el = document.getElementById('statusIndexBadge');
    if (el) el.innerHTML = success
        ? '<span class="badge bg-success">✅ Submitted</span>'
        : '<span class="badge bg-warning text-dark">⏳ Not submitted</span>';
}

async function loadSeoStats() {
    try {
        const res = await apiFetch('/api/v1/blog-seo/ping-stats');
        const unpingedCount = res.data?.not_pinged ?? 0;
        const badge = document.getElementById('pendingIndexBadge');
        if (badge) {
            if (unpingedCount > 0) {
                badge.textContent = unpingedCount;
                badge.className = 'badge bg-warning text-dark ms-1';
            } else {
                badge.textContent = '✓';
                badge.className = 'badge bg-success ms-1';
            }
        }
    } catch(e) { console.error(e); }
}

async function loadBlogSeoStatus(slug) {
    try {
        const res = await apiFetch(`${API_BASE}/${slug}`);
        const blog = res.data ?? res;
        const pingEl = document.getElementById('statusPingBadge');
        const indexEl = document.getElementById('statusIndexBadge');
        
        if (pingEl) pingEl.innerHTML = blog.is_pinged 
            ? '<span class="badge bg-success">✅ Pinged</span>' 
            : '<span class="badge bg-secondary">Not pinged</span>';
        if (indexEl) indexEl.innerHTML = blog.is_indexed 
            ? '<span class="badge bg-success">✅ Indexed</span>' 
            : '<span class="badge bg-secondary">Not indexed</span>';
    } catch(e) {}
}

// Update openStatus to load SEO status
const originalOpenStatus = openStatus;

// ============================================================
// STATUS MODAL - FIXED (No recursion)
// ============================================================
async function openStatus(slug, e) {
    e?.preventDefault();
    currentSlug = slug;
    document.getElementById('statusActionMsg').innerHTML = '';

    try {
        const res = await apiFetch(`${API_BASE}/${slug}?with_content=true`);
        const blog = res.data ?? res;
        document.getElementById('statusBlogTitle').textContent = blog.title;
        
        // Update status badges
        const badgePublished = document.getElementById('badgePublished');
        const badgeUnpublished = document.getElementById('badgeUnpublished');
        const badgeFeatured = document.getElementById('badgeFeatured');
        
        if (badgePublished) {
            badgePublished.textContent = blog.is_published ? 'Published' : 'Not Published';
            badgePublished.className = blog.is_published ? 'badge bg-success' : 'badge bg-secondary';
        }
        
        if (badgeUnpublished) {
            badgeUnpublished.textContent = !blog.is_published ? 'Unpublished' : 'Published';
            badgeUnpublished.className = !blog.is_published ? 'badge bg-warning' : 'badge bg-secondary';
        }
        
        if (badgeFeatured) {
            badgeFeatured.textContent = blog.is_featured ? 'Featured' : 'Not Featured';
            badgeFeatured.className = blog.is_featured ? 'badge bg-warning' : 'badge bg-secondary';
        }
        
        // Load SEO status
        await loadBlogSeoStatus(slug);
        
    } catch(e) {
        console.error('Failed to load blog status:', e);
    }

    bsModal('statusModal').show();
}

// ============================================================
// INITIALIZE
// ============================================================
document.addEventListener('DOMContentLoaded', () => {
    loadFilters();
    loadBlogs(1);

    if (typeof $ !== 'undefined' && $.fn.datepicker) {
        $('.datepicker-autoclose').datepicker({ 
            autoclose: true, 
            todayHighlight: true, 
            format: 'mm/dd/yyyy' 
        });
    }
});
</script>