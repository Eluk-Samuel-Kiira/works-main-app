<script>
// ============================================================
// CONFIG & STATE
// ============================================================
const API_BASE      = '/api/v1/social-media';
const LOCATIONS_API = '/api/v1/job-locations';
const CSRF_TOKEN    = document.querySelector('meta[name="csrf-token"]')?.content ?? '';

let currentPage    = 1;
let currentId      = null;
let debounceTimer  = null;
let platformMeta   = {};   // keyed by platform value — holds label, color, icon
let locationItems  = [];   // flat list for typable dropdown

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
function formatNumber(n) {
    n = parseInt(n) || 0;
    if (n >= 1_000_000) return (n / 1_000_000).toFixed(1) + 'M';
    if (n >= 1_000)     return (n / 1_000).toFixed(1) + 'K';
    return String(n);
}
function toast(msg, type = 'success') { if (typeof showToast === 'function') showToast(type, msg); }
function bsModal(id) { return bootstrap.Modal.getOrCreateInstance(document.getElementById(id)); }

async function apiFetch(url, options = {}) {
    const res = await fetch(url, {
        ...options,
        headers: {
            'Content-Type': 'application/json',
            'Accept'       : 'application/json',
            'X-CSRF-TOKEN' : CSRF_TOKEN,
            ...(options.headers ?? {}),
        },
    });
    const data = await res.json();
    if (!res.ok) throw data;
    return data;
}

// ============================================================
// TYPABLE DROPDOWN HELPER
// ============================================================
function initTypableDropdown({ inputId, listId, hiddenId, getData, onSelect }) {
    const input  = document.getElementById(inputId);
    const list   = document.getElementById(listId);
    const hidden = document.getElementById(hiddenId);

    function showList(items) {
        list.innerHTML = '';
        if (!items.length) {
            list.innerHTML = '<li class="dropdown-item text-muted small py-2">No results found</li>';
        } else {
            items.forEach(item => {
                const li         = document.createElement('li');
                li.className     = 'dropdown-item cursor-pointer py-2';
                li.innerHTML     = item.html ?? esc(item.label);
                li.dataset.value = item.value;
                li.dataset.label = item.label;
                li.addEventListener('mousedown', e => {
                    e.preventDefault();
                    input.value  = item.label;
                    hidden.value = item.value;
                    list.classList.remove('show');
                    if (onSelect) onSelect(item);
                });
                list.appendChild(li);
            });
        }
        list.classList.add('show');
    }

    input.addEventListener('input', () => {
        const q     = input.value.toLowerCase().trim();
        const all   = getData();
        const match = q ? all.filter(i => i.label.toLowerCase().includes(q)) : all;
        showList(match);
        if (!q) hidden.value = '';
    });

    input.addEventListener('focus', () => {
        showList(getData());
    });

    input.addEventListener('blur', () => {
        setTimeout(() => list.classList.remove('show'), 150);
    });
}

// ============================================================
// TYPABLE DROPDOWN SET / CLEAR HELPERS
// ============================================================
function setTypable(inputId, hiddenId, value, label) {
    document.getElementById(inputId).value = label ?? '';
    document.getElementById(hiddenId).value = value ?? '';
}

function clearTypable(inputId, hiddenId) {
    document.getElementById(inputId).value = '';
    document.getElementById(hiddenId).value = '';
}

// ============================================================
// PLATFORM ICON BADGE (table)
// ============================================================
function platformBadge(item) {
    const color = item.platform_color ?? '#6c757d';
    const icon  = item.platform_icon  ?? 'bi bi-globe';
    const label = item.platform_label ?? item.platform ?? '—';
    return `
    <div class="d-flex align-items-center gap-2">
        <span style="width:30px;height:30px;background:${esc(color)};border-radius:50%;
                     display:inline-flex;align-items:center;justify-content:center;flex-shrink:0;">
            <i class="${esc(icon)}" style="color:#fff;font-size:14px;"></i>
        </span>
        <span class="fw-semibold">${esc(label)}</span>
    </div>`;
}

// ============================================================
// PLATFORM ICON PREVIEW (form)
// ============================================================
function updatePlatformPreview() {
    const val     = document.getElementById('formPlatform').value;
    const meta    = platformMeta[val];
    const preview = document.getElementById('platformIconPreview');
    if (!preview) return;
    if (meta) {
        preview.innerHTML = `
        <span style="width:34px;height:34px;background:${esc(meta.color)};border-radius:50%;
                     display:inline-flex;align-items:center;justify-content:center;">
            <i class="${esc(meta.icon)}" style="color:#fff;font-size:16px;"></i>
        </span>`;
    } else {
        preview.innerHTML = '<i class="bi bi-globe text-muted" style="font-size:20px"></i>';
    }
}

// ============================================================
// LOAD DROPDOWNS
// ============================================================
async function loadLocations() {
    try {
        const res   = await apiFetch(`${LOCATIONS_API}?per_page=200&is_active=1`);
        const items = res.data ?? [];

        // Store for typable dropdown
        locationItems = items.map(i => ({
            value : String(i.id),
            label : [i.district, i.country].filter(Boolean).join(', '),
        }));

        // Filter bar select
        const opts = items.map(i => {
            const label = [i.district, i.country].filter(Boolean).join(', ');
            return `<option value="${i.id}">${esc(label)}</option>`;
        }).join('');
        document.getElementById('filterLocation').innerHTML =
            '<option value="">All Locations</option>' + opts;

    } catch (_) {}
}

async function loadPlatformOptions() {
    try {
        const res   = await apiFetch(`${API_BASE}/platforms`);
        const items = res.data ?? [];
        platformMeta = {};
        items.forEach(p => { platformMeta[p.value] = p; });

        // Filter bar select only — form uses typable dropdown
        const opts = items.map(p =>
            `<option value="${p.value}">${esc(p.label)}</option>`
        ).join('');
        document.getElementById('filterPlatform').innerHTML =
            '<option value="">All Platforms</option>' + opts;

    } catch (_) {}
}

function getPlatformItems() {
    return Object.entries(platformMeta).map(([value, p]) => ({
        value,
        label : p.label,
        html  : `
            <div class="d-flex align-items-center gap-2">
                <span style="width:24px;height:24px;background:${esc(p.color)};border-radius:50%;
                             display:inline-flex;align-items:center;justify-content:center;flex-shrink:0;">
                    <i class="${esc(p.icon)}" style="color:#fff;font-size:11px;"></i>
                </span>
                <span>${esc(p.label)}</span>
            </div>`,
        color : p.color,
        icon  : p.icon,
    }));
}

// ============================================================
// FILTERS
// ============================================================
function debounceLoad() {
    clearTimeout(debounceTimer);
    debounceTimer = setTimeout(() => loadItems(1), 400);
}

function resetFilters() {
    ['filterSearch','filterLocation','filterPlatform','filterStatus','filterFeatured']
        .forEach(id => document.getElementById(id).value = '');
    loadItems(1);
}

function buildQueryString(page) {
    const params   = new URLSearchParams({ page, per_page: 15 });
    const search   = document.getElementById('filterSearch').value.trim();
    const location = document.getElementById('filterLocation').value;
    const platform = document.getElementById('filterPlatform').value;
    const status   = document.getElementById('filterStatus').value;
    const featured = document.getElementById('filterFeatured').value;
    if (search)         params.set('search', search);
    if (location)       params.set('location_id', location);
    if (platform)       params.set('platform', platform);
    if (status !== '')  params.set('is_active', status);
    if (featured !== '') params.set('is_featured', featured);
    return params.toString();
}

// ============================================================
// LOAD & RENDER TABLE
// ============================================================
async function loadItems(page = 1) {
    currentPage = page;
    const tbody = document.getElementById('tableBody');
    tbody.innerHTML = `<tr><td colspan="8" class="text-center py-4">
        <div class="spinner-border text-primary"></div></td></tr>`;
    try {
        const res = await apiFetch(`${API_BASE}?${buildQueryString(page)}`);
        renderTable(res.data);
        renderPagination(res.meta ?? {});
    } catch (e) {
        tbody.innerHTML = `<tr><td colspan="8" class="text-center text-danger py-3">
            <i class="ti ti-alert-circle me-1"></i>Failed to load platforms.</td></tr>`;
    }
}

function renderTable(items) {
    const tbody = document.getElementById('tableBody');
    if (!items || items.length === 0) {
        tbody.innerHTML = `<tr><td colspan="8" class="text-center py-4 text-muted">No platforms found.</td></tr>`;
        return;
    }
    tbody.innerHTML = items.map((item, i) => `
        <tr>
            <td class="text-muted">${((currentPage - 1) * 15) + i + 1}</td>
            <td>${platformBadge(item)}</td>
            <td>
                <div class="fw-semibold">${esc(item.name)}</div>
                ${item.handle ? `<small class="text-muted">@${esc(item.handle)}</small>` : ''}
            </td>
            <td>
                <small>${esc(item.location?.district ?? item.location?.country ?? '—')}</small>
                ${item.location?.country ? `<br><small class="text-muted">${esc(item.location.country)}</small>` : ''}
            </td>
            <td>
                <span class="fw-semibold">${esc(item.followers_formatted ?? '0')}</span>
                ${item.is_verified ? ' <i class="ti ti-shield-check text-info" title="Verified"></i>' : ''}
            </td>
            <td>${item.is_active
                ? '<span class="badge bg-success">Active</span>'
                : '<span class="badge bg-secondary">Inactive</span>'}</td>
            <td>${item.is_featured
                ? '<span class="badge bg-warning text-dark"><i class="ti ti-star me-1"></i>Featured</span>'
                : '<span class="badge bg-light text-muted border">No</span>'}</td>
            <td>
                <div class="d-flex align-items-center gap-1">
                    <button class="btn btn-sm bg-primary-subtle text-primary p-1" title="View"
                        onclick="openViewModal('${esc(item.slug)}')">
                        <i class="ti ti-eye" style="font-size:1.25rem;"></i>
                    </button>
                    <button class="btn btn-sm bg-success-subtle text-success p-1" title="Edit"
                        onclick="openEditModal('${esc(item.slug)}')">
                        <i class="ti ti-pencil" style="font-size:1.25rem;"></i>
                    </button>
                    <a href="${esc(item.url)}" target="_blank" rel="noopener"
                        class="btn btn-sm bg-info-subtle text-info p-1" title="Open Link">
                        <i class="ti ti-external-link" style="font-size:1.25rem;"></i>
                    </a>
                    <button class="btn btn-sm bg-danger-subtle text-danger p-1" title="Delete"
                        onclick="openDeleteModal('${esc(item.slug)}', '${esc(item.name)}')">
                        <i class="ti ti-trash" style="font-size:1.25rem;"></i>
                    </button>
                </div>
            </td>
        </tr>
    `).join('');
}

function renderPagination(meta) {
    document.getElementById('paginationInfo').textContent =
        meta.total ? `Showing ${meta.from}–${meta.to} of ${meta.total}` : '';
    const last = meta.last_page ?? 1, cur = meta.current_page ?? 1, pages = [];
    for (let p = Math.max(1, cur - 2); p <= Math.min(last, cur + 2); p++) pages.push(p);
    const li = 'page-item', liA = 'page-item active', liD = 'page-item disabled', btn = 'page-link';
    let html = '<ul class="pagination pagination-md mb-0">';
    html += `<li class="${cur>1?li:liD}"><a class="${btn}" href="#" onclick="loadItems(1);return false;">«</a></li>`;
    html += `<li class="${cur>1?li:liD}"><a class="${btn}" href="#" onclick="loadItems(${cur-1});return false;">‹</a></li>`;
    if (pages[0] > 1) html += `<li class="${liD}"><span class="${btn}">…</span></li>`;
    pages.forEach(p => {
        html += p === cur
            ? `<li class="${liA}"><span class="${btn}">${p}</span></li>`
            : `<li class="${li}"><a class="${btn}" href="#" onclick="loadItems(${p});return false;">${p}</a></li>`;
    });
    if (pages[pages.length-1] < last) html += `<li class="${liD}"><span class="${btn}">…</span></li>`;
    html += `<li class="${cur<last?li:liD}"><a class="${btn}" href="#" onclick="loadItems(${cur+1});return false;">›</a></li>`;
    html += `<li class="${cur<last?li:liD}"><a class="${btn}" href="#" onclick="loadItems(${last});return false;">»</a></li>`;
    html += '</ul>';
    document.getElementById('paginationLinks').innerHTML = html;
}

// ============================================================
// VIEW MODAL
// ============================================================
async function openViewModal(slug) {
    currentId = slug;
    document.getElementById('viewModalTitle').textContent = 'Loading…';
    document.getElementById('viewModalBody').innerHTML =
        '<div class="text-center py-5"><div class="spinner-border text-primary"></div></div>';
    bsModal('viewModal').show();

    try {
        const res  = await apiFetch(`${API_BASE}/${slug}`);
        const item = res.data ?? res;
        document.getElementById('viewModalTitle').textContent = item.name;
        const color = item.platform_color ?? '#6c757d';
        const icon  = item.platform_icon  ?? 'bi bi-globe';

        document.getElementById('viewModalBody').innerHTML = `
        <div class="row g-3">
            <div class="col-md-3 text-center">
                <div style="width:80px;height:80px;background:${esc(color)};border-radius:50%;
                            display:flex;align-items:center;justify-content:center;margin:0 auto 12px;">
                    <i class="${esc(icon)}" style="color:#fff;font-size:36px;"></i>
                </div>
                <div class="fw-bold fs-5">${esc(item.platform_label ?? item.platform)}</div>
                <div class="text-muted small">${item.handle ? '@' + esc(item.handle) : ''}</div>
                <div class="mt-2">
                    <a href="${esc(item.url)}" target="_blank" rel="noopener"
                       class="btn btn-sm btn-outline-primary">
                        <i class="ti ti-external-link me-1"></i>Open Link
                    </a>
                </div>
            </div>
            <div class="col-md-9">
                <table class="table table-bordered mb-0">
                    <tr><th width="160">Name</th><td>${esc(item.name)}</td></tr>
                    <tr><th>Slug</th><td><code>${esc(item.slug)}</code></td></tr>
                    <tr><th>Platform</th><td>${esc(item.platform_label ?? item.platform)}</td></tr>
                    <tr><th>URL</th><td><a href="${esc(item.url)}" target="_blank" rel="noopener" class="text-truncate d-inline-block" style="max-width:300px">${esc(item.url)}</a></td></tr>
                    <tr><th>Location</th><td>${esc([item.location?.district, item.location?.country].filter(Boolean).join(', ') || '—')}</td></tr>
                    <tr><th>Followers</th><td>${esc(item.followers_formatted ?? '0')} <span class="text-muted small">(${(item.followers_count ?? 0).toLocaleString()} total)</span></td></tr>
                    <tr><th>Description</th><td>${esc(item.description) || '—'}</td></tr>
                    <tr><th>Active</th><td>${item.is_active ? '<span class="badge bg-success">Yes</span>' : '<span class="badge bg-secondary">No</span>'}</td></tr>
                    <tr><th>Verified</th><td>${item.is_verified ? '<span class="badge bg-info">Verified</span>' : '<span class="badge bg-warning text-dark">No</span>'}</td></tr>
                    <tr><th>Featured</th><td>${item.is_featured ? '<span class="badge bg-warning text-dark"><i class="ti ti-star me-1"></i>Yes</span>' : 'No'}</td></tr>
                    <tr><th>Sort Order</th><td>${item.sort_order ?? 0}</td></tr>
                    <tr><th>Added by</th><td>${esc(item.creator?.name ?? '—')}</td></tr>
                    <tr><th>Created</th><td>${formatDate(item.created_at)}</td></tr>
                    <tr><th>Updated</th><td>${formatDate(item.updated_at)}</td></tr>
                </table>
                ${item.meta_title ? `
                <div class="mt-3 p-3 bg-light rounded">
                    <div class="small text-muted mb-1">SEO Meta Title</div>
                    <div class="fw-semibold">${esc(item.meta_title)}</div>
                    ${item.meta_description ? `<div class="small text-muted mt-1">${esc(item.meta_description)}</div>` : ''}
                </div>` : ''}
            </div>
        </div>`;
    } catch (e) {
        document.getElementById('viewModalBody').innerHTML =
            '<div class="alert alert-danger">Failed to load platform details.</div>';
    }
}

function switchToEdit() {
    bsModal('viewModal').hide();
    setTimeout(() => openEditModal(currentId), 300);
}

// ============================================================
// CREATE MODAL
// ============================================================
function openCreateModal() {
    currentId = null;
    document.getElementById('formModalTitle').innerHTML = '<i class="ti ti-plus me-2"></i>Add Platform';
    document.getElementById('formBtnText').textContent  = 'Save';
    document.getElementById('formId').value             = '';
    document.getElementById('formName').value           = '';
    document.getElementById('formHandle').value         = '';
    document.getElementById('formUrl').value            = '';
    document.getElementById('formDescription').value    = '';
    document.getElementById('formFollowersCount').value = '';
    document.getElementById('formSortOrder').value      = '0';
    document.getElementById('formIcon').value           = '';
    document.getElementById('formIsActive').checked     = true;
    document.getElementById('formIsVerified').checked   = false;
    document.getElementById('formIsFeatured').checked   = false;
    document.getElementById('formMetaTitle').value      = '';
    document.getElementById('formMetaDescription').value= '';
    document.getElementById('formErrors').innerHTML     = '';
    clearTypable('formPlatformInput', 'formPlatform');
    clearTypable('formLocationInput', 'formLocationId');
    updatePlatformPreview();
    bsModal('formModal').show();
}

// ============================================================
// EDIT MODAL
// ============================================================
async function openEditModal(slug) {
    currentId = slug;
    document.getElementById('formModalTitle').innerHTML = '<i class="ti ti-pencil me-2"></i>Edit Platform';
    document.getElementById('formBtnText').textContent  = 'Update';
    document.getElementById('formErrors').innerHTML     = '';
    bsModal('formModal').show();
    try {
        const res  = await apiFetch(`${API_BASE}/${slug}`);
        const item = res.data ?? res;
        document.getElementById('formId').value              = item.id;
        document.getElementById('formName').value            = item.name ?? '';
        document.getElementById('formHandle').value          = item.handle ?? '';
        document.getElementById('formUrl').value             = item.url ?? '';
        document.getElementById('formDescription').value     = item.description ?? '';
        document.getElementById('formFollowersCount').value  = item.followers_count ?? '';
        document.getElementById('formSortOrder').value       = item.sort_order ?? 0;
        document.getElementById('formIcon').value            = item.icon ?? '';
        document.getElementById('formIsActive').checked      = !!item.is_active;
        document.getElementById('formIsVerified').checked    = !!item.is_verified;
        document.getElementById('formIsFeatured').checked    = !!item.is_featured;
        document.getElementById('formMetaTitle').value       = item.meta_title ?? '';
        document.getElementById('formMetaDescription').value = item.meta_description ?? '';

        // Typable dropdown setters
        const platLabel = platformMeta[item.platform]?.label ?? item.platform ?? '';
        const locLabel  = [item.location?.district, item.location?.country].filter(Boolean).join(', ');
        setTypable('formPlatformInput', 'formPlatform',   item.platform ?? '',        platLabel);
        setTypable('formLocationInput', 'formLocationId', item.location?.id ?? '', locLabel);
        updatePlatformPreview();

    } catch (e) {
        toast('Failed to load platform data.', 'error');
        bsModal('formModal').hide();
    }
}

// ============================================================
// SUBMIT SAVE
// ============================================================
async function submitSave() {
    const btn     = document.getElementById('formSaveBtn');
    const spinner = document.getElementById('formBtnSpinner');
    const errDiv  = document.getElementById('formErrors');
    btn.disabled  = true;
    spinner.classList.remove('d-none');
    errDiv.innerHTML = '';

    try {
        const data = {
            platform        : document.getElementById('formPlatform').value,
            location_id     : document.getElementById('formLocationId').value || null,
            name            : document.getElementById('formName').value.trim(),
            handle          : document.getElementById('formHandle').value.trim() || null,
            url             : document.getElementById('formUrl').value.trim(),
            description     : document.getElementById('formDescription').value.trim() || null,
            followers_count : parseInt(document.getElementById('formFollowersCount').value) || 0,
            sort_order      : parseInt(document.getElementById('formSortOrder').value) || 0,
            icon            : document.getElementById('formIcon').value.trim() || null,
            is_active       : document.getElementById('formIsActive').checked,
            is_verified     : document.getElementById('formIsVerified').checked,
            is_featured     : document.getElementById('formIsFeatured').checked,
            meta_title      : document.getElementById('formMetaTitle').value.trim() || null,
            meta_description: document.getElementById('formMetaDescription').value.trim() || null,
        };

        const url    = currentId ? `${API_BASE}/${currentId}` : API_BASE;
        const method = currentId ? 'PATCH' : 'POST';

        await apiFetch(url, { method, body: JSON.stringify(data) });

        bsModal('formModal').hide();
        toast(currentId ? 'Platform updated successfully.' : 'Platform created successfully.', 'success');
        loadItems(currentPage);

    } catch (e) {
        if (e.errors) {
            const msgs = Object.values(e.errors).flat().map(m => `<li>${m}</li>`).join('');
            errDiv.innerHTML = `<div class="alert alert-danger mt-2"><ul class="mb-0">${msgs}</ul></div>`;
        } else {
            toast(e.message ?? 'Failed to save platform.', 'error');
        }
    } finally {
        btn.disabled = false;
        spinner.classList.add('d-none');
    }
}

// ============================================================
// DELETE MODAL
// ============================================================
function openDeleteModal(slug, name) {
    currentId = slug;
    document.getElementById('deleteItemName').textContent = name;
    bsModal('deleteModal').show();
}

async function confirmDelete() {
    const btn     = document.getElementById('confirmDeleteBtn');
    const spinner = document.getElementById('deleteBtnSpinner');
    btn.disabled  = true;
    spinner.classList.remove('d-none');
    try {
        await apiFetch(`${API_BASE}/${currentId}`, { method: 'DELETE' });
        toast('Platform deleted successfully.', 'success');
        bsModal('deleteModal').hide();
        loadItems(currentPage);
    } catch (e) {
        toast(e.message ?? 'Failed to delete.', 'error');
    } finally {
        btn.disabled = false;
        spinner.classList.add('d-none');
    }
}

// ============================================================
// INIT
// ============================================================
document.addEventListener('DOMContentLoaded', async () => {
    await Promise.all([loadPlatformOptions(), loadLocations()]);

    initTypableDropdown({
        inputId  : 'formPlatformInput',
        listId   : 'formPlatformList',
        hiddenId : 'formPlatform',
        getData  : getPlatformItems,
        onSelect : () => updatePlatformPreview(),
    });

    initTypableDropdown({
        inputId  : 'formLocationInput',
        listId   : 'formLocationList',
        hiddenId : 'formLocationId',
        getData  : () => locationItems,
    });

    loadItems(1);
});
</script>