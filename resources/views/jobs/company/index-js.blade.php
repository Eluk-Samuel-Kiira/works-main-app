<script>

    const API_BASE      = '/api/v1/companies';
    const INDUSTRY_API  = '/api/v1/industries';
    const CSRF_TOKEN    = document.querySelector('meta[name="csrf-token"]')?.content ?? '';
    let currentPage      = 1;
    let currentId        = null;
    let debounceTimer    = null;

    function esc(str) {
        if (str == null) return '';
        return String(str).replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;');
    }
    function formatDate(d) {
        if (!d) return '—';
        return new Date(d).toLocaleDateString('en-UG', { year:'numeric', month:'short', day:'numeric' });
    }
    function toast(msg, type = 'success') { if (typeof showToast === 'function') showToast(type, msg); }
    function bsModal(id) { return bootstrap.Modal.getOrCreateInstance(document.getElementById(id)); }

    async function apiFetch(url, options = {}) {
        const res = await fetch(url, {
            ...options,
            headers: { 'Content-Type':'application/json', 'Accept':'application/json', 'X-CSRF-TOKEN':CSRF_TOKEN, ...(options.headers ?? {}) },
        });
        const data = await res.json();
        if (!res.ok) throw data;
        return data;
    }

    // ============================================================
    // LOAD INDUSTRIES FOR DROPDOWNS
    // ============================================================
    async function loadIndustries() {
        try {
            const res = await apiFetch(`${INDUSTRY_API}?per_page=100&is_active=1`);
            const items = res.data ?? [];
            const options = items.map(i => `<option value="${i.id}">${esc(i.name)}</option>`).join('');
            document.getElementById('filterIndustry').innerHTML = '<option value="">All Industries</option>' + options;
            document.getElementById('formIndustryId').innerHTML = '<option value="">— Select Industry —</option>' + options;
        } catch (e) { /* silent */ }
    }

    // ============================================================
    // FILTERS
    // ============================================================
    function debounceLoad() { clearTimeout(debounceTimer); debounceTimer = setTimeout(() => loadItems(1), 400); }
    function resetFilters() {
        document.getElementById('filterSearch').value = '';
        document.getElementById('filterIndustry').value = '';
        document.getElementById('filterStatus').value = '';
        document.getElementById('filterVerified').value = '';
        loadItems(1);
    }

    function buildQueryString(page) {
        const params   = new URLSearchParams({ page, per_page: 15 });
        const search   = document.getElementById('filterSearch').value.trim();
        const industry = document.getElementById('filterIndustry').value;
        const status   = document.getElementById('filterStatus').value;
        const verified = document.getElementById('filterVerified').value;
        if (search)        params.set('search', search);
        if (industry)      params.set('industry_id', industry);
        if (status !== '')  params.set('is_active', status);
        if (verified !== '') params.set('is_verified', verified);
        return params.toString();
    }

    // ============================================================
    // LOAD & RENDER TABLE
    // ============================================================
    async function loadItems(page = 1) {
        currentPage = page;
        const tbody = document.getElementById('tableBody');
        tbody.innerHTML = `<tr><td colspan="8" class="text-center py-4"><div class="spinner-border text-primary"></div></td></tr>`;
        try {
            const res = await apiFetch(`${API_BASE}?${buildQueryString(page)}`);
            renderTable(res.data);
            renderPagination(res.meta ?? {});
        } catch (e) {
            tbody.innerHTML = `<tr><td colspan="8" class="text-center text-danger py-3"><i class="ti ti-alert-circle me-1"></i>Failed to load companies.</td></tr>`;
        }
    }

    function renderTable(items) {
        const tbody = document.getElementById('tableBody');
        if (!items || items.length === 0) {
            tbody.innerHTML = `<tr><td colspan="8" class="text-center py-4 text-muted">No companies found.</td></tr>`;
            return;
        }
        tbody.innerHTML = items.map((item, i) => `
            <tr>
                <td class="text-muted">${((currentPage - 1) * 15) + i + 1}</td>
                <td>
                    <div class="d-flex align-items-center gap-2">
                        ${item.logo_url
                            ? `<img src="${esc(item.logo_url)}" class="rounded" width="32" height="32" style="object-fit:contain;border:1px solid #eee">`
                            : `<div class="rounded bg-light d-flex align-items-center justify-content-center" style="width:32px;height:32px"><i class="ti ti-building text-muted"></i></div>`}
                        <span class="fw-semibold">${esc(item.name)}</span>
                    </div>
                </td>
                <td>${esc(item.industry?.name ?? '—')}</td>
                <td><small>${esc(item.contact_name ?? '—')}</small></td>
                <td>${item.website ? `<a href="${esc(item.website)}" target="_blank" class="text-truncate d-inline-block" style="max-width:150px">${esc(item.website)}</a>` : '—'}</td>
                <td>${item.is_active ? '<span class="badge bg-success">Active</span>' : '<span class="badge bg-secondary">Inactive</span>'}</td>
                <td>${item.is_verified ? '<span class="badge bg-info">Verified</span>' : '<span class="badge bg-warning text-dark">Unverified</span>'}</td>
                <td>
                    <div class="d-flex align-items-center gap-1">
                        <button class="btn btn-sm bg-primary-subtle text-primary p-1" title="View" onclick="openViewModal('${esc(item.slug)}')"><i class="ti ti-eye" style="font-size:1.25rem;"></i></button>
                        <button class="btn btn-sm bg-success-subtle text-success p-1" title="Edit" onclick="openEditModal('${esc(item.slug)}')"><i class="ti ti-pencil" style="font-size:1.25rem;"></i></button>
                        <button class="btn btn-sm bg-danger-subtle text-danger p-1" title="Delete" onclick="openDeleteModal('${esc(item.slug)}', '${esc(item.name)}')"><i class="ti ti-trash" style="font-size:1.25rem;"></i></button>
                    </div>
                </td>
            </tr>
        `).join('');
    }

    function renderPagination(meta) {
        document.getElementById('paginationInfo').textContent = meta.total ? `Showing ${meta.from}–${meta.to} of ${meta.total}` : '';
        const last = meta.last_page ?? 1, cur = meta.current_page ?? 1, pages = [];
        for (let p = Math.max(1, cur - 2); p <= Math.min(last, cur + 2); p++) pages.push(p);
        const li = 'page-item', liA = 'page-item active', liD = 'page-item disabled', btn = 'page-link';
        let html = '<ul class="pagination pagination-md mb-0">';
        html += `<li class="${cur>1?li:liD}"><a class="${btn}" href="#" onclick="loadItems(1);return false;">«</a></li>`;
        html += `<li class="${cur>1?li:liD}"><a class="${btn}" href="#" onclick="loadItems(${cur-1});return false;">‹</a></li>`;
        if (pages[0] > 1) html += `<li class="${liD}"><span class="${btn}">…</span></li>`;
        pages.forEach(p => { html += p === cur ? `<li class="${liA}"><span class="${btn}">${p}</span></li>` : `<li class="${li}"><a class="${btn}" href="#" onclick="loadItems(${p});return false;">${p}</a></li>`; });
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
        document.getElementById('viewModalBody').innerHTML = '<div class="text-center py-5"><div class="spinner-border text-primary"></div></div>';
        bsModal('viewModal').show();
        try {
            const res = await apiFetch(`${API_BASE}/${slug}`);
            const item = res.data ?? res;
            document.getElementById('viewModalTitle').textContent = item.name;
            document.getElementById('viewModalBody').innerHTML = `
            <div class="row">
                <div class="col-md-8">
                    <table class="table table-bordered mb-0">
                        <tr><th width="160">Name</th><td>${esc(item.name)}</td></tr>
                        <tr><th>Slug</th><td><code>${esc(item.slug)}</code></td></tr>
                        <tr><th>Industry</th><td>${esc(item.industry?.name ?? '—')}</td></tr>
                        <tr><th>Description</th><td>${esc(item.description) || '—'}</td></tr>
                        <tr><th>Website</th><td>${item.website ? `<a href="${esc(item.website)}" target="_blank">${esc(item.website)}</a>` : '—'}</td></tr>
                        <tr><th>Address</th><td>${esc(item.address1) || '—'}</td></tr>
                        <tr><th>Company Size</th><td>${esc(item.company_size) || '—'}</td></tr>
                        <tr><th>Contact Name</th><td>${esc(item.contact_name) || '—'}</td></tr>
                        <tr><th>Contact Email</th><td>${esc(item.contact_email) || '—'}</td></tr>
                        <tr><th>Contact Phone</th><td>${esc(item.contact_phone) || '—'}</td></tr>
                        <tr><th>Status</th><td>${item.is_active ? '<span class="badge bg-success">Active</span>' : '<span class="badge bg-secondary">Inactive</span>'}</td></tr>
                        <tr><th>Verified</th><td>${item.is_verified ? '<span class="badge bg-info">Verified</span>' : '<span class="badge bg-warning text-dark">Unverified</span>'}</td></tr>
                        <tr><th>Created</th><td>${formatDate(item.created_at)}</td></tr>
                        <tr><th>Updated</th><td>${formatDate(item.updated_at)}</td></tr>
                    </table>
                </div>
                <div class="col-md-4 text-center">
                    ${item.logo_url
                        ? `<img src="${esc(item.logo_url)}" class="img-fluid rounded border" style="max-height:200px" alt="Logo">`
                        : '<div class="rounded bg-light d-flex align-items-center justify-content-center mx-auto" style="width:150px;height:150px"><i class="ti ti-building fs-1 text-muted"></i></div>'}
                </div>
            </div>`;
        } catch (e) {
            document.getElementById('viewModalBody').innerHTML = '<div class="alert alert-danger">Failed to load company details.</div>';
        }
    }

    function switchToEdit() { bsModal('viewModal').hide(); openEditModal(currentId); }

    // ============================================================
    // CREATE / EDIT MODAL
    // ============================================================
    function openCreateModal() {
        currentId = null;
        document.getElementById('formModalTitle').innerHTML = '<i class="ti ti-plus me-2"></i>Add Company';
        document.getElementById('formId').value = '';
        document.getElementById('formName').value = '';
        document.getElementById('formIndustryId').value = '';
        document.getElementById('formDescription').value = '';
        document.getElementById('formWebsite').value = '';
        document.getElementById('formLogo').value = '';
        document.getElementById('formContactName').value = '';
        document.getElementById('formContactEmail').value = '';
        document.getElementById('formContactPhone').value = '';
        document.getElementById('formAddress1').value = '';
        document.getElementById('formCompanySize').value = '';
        document.getElementById('formIsActive').checked = true;
        document.getElementById('formIsVerified').checked = false;
        document.getElementById('formBtnText').textContent = 'Save';
        bsModal('formModal').show();
    }

    async function openEditModal(slug) {
        currentId = slug;
        document.getElementById('formModalTitle').innerHTML = '<i class="ti ti-pencil me-2"></i>Edit Company';
        document.getElementById('formBtnText').textContent = 'Update';
        bsModal('formModal').show();
        try {
            const res = await apiFetch(`${API_BASE}/${slug}`);
            const item = res.data ?? res;
            document.getElementById('formId').value = item.id;
            document.getElementById('formName').value = item.name ?? '';
            document.getElementById('formIndustryId').value = item.industry_id ?? '';
            document.getElementById('formDescription').value = item.description ?? '';
            document.getElementById('formWebsite').value = item.website ?? '';
            document.getElementById('formLogo').value = item.logo ?? '';
            document.getElementById('formContactName').value = item.contact_name ?? '';
            document.getElementById('formContactEmail').value = item.contact_email ?? '';
            document.getElementById('formContactPhone').value = item.contact_phone ?? '';
            document.getElementById('formAddress1').value = item.address1 ?? '';
            document.getElementById('formCompanySize').value = item.company_size ?? '';
            document.getElementById('formIsActive').checked = !!item.is_active;
            document.getElementById('formIsVerified').checked = !!item.is_verified;
        } catch (e) { toast('Failed to load company data.', 'error'); bsModal('formModal').hide(); }
    }

    async function submitSave() {
        const btn = document.getElementById('formSaveBtn'), spinner = document.getElementById('formBtnSpinner');
        btn.disabled = true; spinner.classList.remove('d-none');
        const industryId = document.getElementById('formIndustryId').value;
        const payload = {
            name:          document.getElementById('formName').value.trim(),
            industry_id:   industryId ? parseInt(industryId) : null,
            description:   document.getElementById('formDescription').value.trim() || null,
            website:       document.getElementById('formWebsite').value.trim() || null,
            logo:          document.getElementById('formLogo').value.trim() || null,
            contact_name:  document.getElementById('formContactName').value.trim() || null,
            contact_email: document.getElementById('formContactEmail').value.trim() || null,
            contact_phone: document.getElementById('formContactPhone').value.trim() || null,
            address1:      document.getElementById('formAddress1').value.trim() || null,
            company_size:  document.getElementById('formCompanySize').value.trim() || null,
            is_active:     document.getElementById('formIsActive').checked,
            is_verified:   document.getElementById('formIsVerified').checked,
        };
        try {
            if (currentId) {
                await apiFetch(`${API_BASE}/${currentId}`, { method: 'PATCH', body: JSON.stringify(payload) });
                toast('Company updated successfully.');
            } else {
                await apiFetch(API_BASE, { method: 'POST', body: JSON.stringify(payload) });
                toast('Company created successfully.');
            }
            bsModal('formModal').hide();
            loadItems(currentPage);
        } catch (e) {
            const msg = e.message ?? 'Validation failed.';
            toast(e.errors ? Object.values(e.errors).flat().join('<br>') : msg, 'error');
        } finally { btn.disabled = false; spinner.classList.add('d-none'); }
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
        const btn = document.getElementById('confirmDeleteBtn'), spinner = document.getElementById('deleteBtnSpinner');
        btn.disabled = true; spinner.classList.remove('d-none');
        try {
            await apiFetch(`${API_BASE}/${currentId}`, { method: 'DELETE' });
            toast('Company deleted successfully.');
            bsModal('deleteModal').hide();
            loadItems(currentPage);
        } catch (e) { toast(e.message ?? 'Failed to delete.', 'error'); }
        finally { btn.disabled = false; spinner.classList.add('d-none'); }
    }

    // ============================================================
    // INIT
    // ============================================================
    document.addEventListener('DOMContentLoaded', () => {
        loadIndustries();
        loadItems(1);
    });

</script>
