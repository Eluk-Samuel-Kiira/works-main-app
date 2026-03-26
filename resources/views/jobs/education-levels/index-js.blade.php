<script>

    const API_BASE   = '/api/v1/education-levels';
    const CSRF_TOKEN = document.querySelector('meta[name="csrf-token"]')?.content ?? '';
    let currentPage   = 1;
    let currentId     = null;
    let debounceTimer = null;

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

    function debounceLoad() { clearTimeout(debounceTimer); debounceTimer = setTimeout(() => loadItems(1), 400); }
    function resetFilters() {
        document.getElementById('filterSearch').value = '';
        document.getElementById('filterStatus').value = '';
        loadItems(1);
    }

    function buildQueryString(page) {
        const params = new URLSearchParams({ page, per_page: 15 });
        const search = document.getElementById('filterSearch').value.trim();
        const status = document.getElementById('filterStatus').value;
        if (search)       params.set('search', search);
        if (status !== '') params.set('is_active', status);
        return params.toString();
    }

    async function loadItems(page = 1) {
        currentPage = page;
        const tbody = document.getElementById('tableBody');
        tbody.innerHTML = `<tr><td colspan="5" class="text-center py-4"><div class="spinner-border text-primary"></div></td></tr>`;
        try {
            const res = await apiFetch(`${API_BASE}?${buildQueryString(page)}`);
            renderTable(res.data);
            renderPagination(res.meta ?? {});
        } catch (e) {
            tbody.innerHTML = `<tr><td colspan="5" class="text-center text-danger py-3"><i class="ti ti-alert-circle me-1"></i>Failed to load education levels.</td></tr>`;
        }
    }

    function renderTable(items) {
        const tbody = document.getElementById('tableBody');
        if (!items || items.length === 0) {
            tbody.innerHTML = `<tr><td colspan="5" class="text-center py-4 text-muted">No education levels found.</td></tr>`;
            return;
        }
        tbody.innerHTML = items.map((item, i) => `
            <tr>
                <td class="text-muted">${((currentPage - 1) * 15) + i + 1}</td>
                <td><span class="fw-semibold">${esc(item.name)}</span></td>
                <td>${item.is_active ? '<span class="badge bg-success">Active</span>' : '<span class="badge bg-secondary">Inactive</span>'}</td>
                <td>${item.sort_order ?? 0}</td>
                <td>
                    <div class="d-flex align-items-center gap-1">
                        <button class="btn btn-sm bg-primary-subtle text-primary p-1" title="View" onclick="openViewModal(${item.id})"><i class="ti ti-eye" style="font-size:1.25rem;"></i></button>
                        <button class="btn btn-sm bg-success-subtle text-success p-1" title="Edit" onclick="openEditModal(${item.id})"><i class="ti ti-pencil" style="font-size:1.25rem;"></i></button>
                        <button class="btn btn-sm bg-danger-subtle text-danger p-1" title="Delete" onclick="openDeleteModal(${item.id}, '${esc(item.name)}')"><i class="ti ti-trash" style="font-size:1.25rem;"></i></button>
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

    async function openViewModal(id) {
        currentId = id;
        document.getElementById('viewModalTitle').textContent = 'Loading…';
        document.getElementById('viewModalBody').innerHTML = '<div class="text-center py-5"><div class="spinner-border text-primary"></div></div>';
        bsModal('viewModal').show();
        try {
            const res = await apiFetch(`${API_BASE}/${id}`);
            const item = res.data ?? res;
            document.getElementById('viewModalTitle').textContent = item.name;
            document.getElementById('viewModalBody').innerHTML = `
            <table class="table table-bordered mb-0">
                <tr><th width="180">Name</th><td>${esc(item.name)}</td></tr>
                <tr><th>Slug</th><td><code>${esc(item.slug)}</code></td></tr>
                <tr><th>Description</th><td>${esc(item.description) || '—'}</td></tr>
                <tr><th>Meta Title</th><td>${esc(item.meta_title) || '—'}</td></tr>
                <tr><th>Meta Description</th><td>${esc(item.meta_description) || '—'}</td></tr>
                <tr><th>Status</th><td>${item.is_active ? '<span class="badge bg-success">Active</span>' : '<span class="badge bg-secondary">Inactive</span>'}</td></tr>
                <tr><th>Sort Order</th><td>${item.sort_order ?? 0}</td></tr>
                <tr><th>Created</th><td>${formatDate(item.created_at)}</td></tr>
                <tr><th>Updated</th><td>${formatDate(item.updated_at)}</td></tr>
            </table>`;
        } catch (e) {
            document.getElementById('viewModalBody').innerHTML = '<div class="alert alert-danger">Failed to load details.</div>';
        }
    }

    function switchToEdit() { bsModal('viewModal').hide(); openEditModal(currentId); }

    function openCreateModal() {
        currentId = null;
        document.getElementById('formModalTitle').innerHTML = '<i class="ti ti-plus me-2"></i>Add Education Level';
        document.getElementById('formId').value = '';
        document.getElementById('formName').value = '';
        document.getElementById('formDescription').value = '';
        document.getElementById('formMetaTitle').value = '';
        document.getElementById('formMetaDescription').value = '';
        document.getElementById('formSortOrder').value = '0';
        document.getElementById('formIsActive').checked = true;
        document.getElementById('formBtnText').textContent = 'Save';
        bsModal('formModal').show();
    }

    async function openEditModal(id) {
        currentId = id;
        document.getElementById('formModalTitle').innerHTML = '<i class="ti ti-pencil me-2"></i>Edit Education Level';
        document.getElementById('formBtnText').textContent = 'Update';
        bsModal('formModal').show();
        try {
            const res = await apiFetch(`${API_BASE}/${id}`);
            const item = res.data ?? res;
            document.getElementById('formId').value = item.id;
            document.getElementById('formName').value = item.name ?? '';
            document.getElementById('formDescription').value = item.description ?? '';
            document.getElementById('formMetaTitle').value = item.meta_title ?? '';
            document.getElementById('formMetaDescription').value = item.meta_description ?? '';
            document.getElementById('formSortOrder').value = item.sort_order ?? 0;
            document.getElementById('formIsActive').checked = !!item.is_active;
        } catch (e) { toast('Failed to load data.', 'error'); bsModal('formModal').hide(); }
    }

    async function submitSave() {
        const btn = document.getElementById('formSaveBtn'), spinner = document.getElementById('formBtnSpinner');
        btn.disabled = true; spinner.classList.remove('d-none');
        const payload = {
            name: document.getElementById('formName').value.trim(),
            description: document.getElementById('formDescription').value.trim() || null,
            meta_title: document.getElementById('formMetaTitle').value.trim() || null,
            meta_description: document.getElementById('formMetaDescription').value.trim() || null,
            sort_order: parseInt(document.getElementById('formSortOrder').value) || 0,
            is_active: document.getElementById('formIsActive').checked,
        };
        try {
            if (currentId) {
                await apiFetch(`${API_BASE}/${currentId}`, { method: 'PATCH', body: JSON.stringify(payload) });
                toast('Education level updated successfully.');
            } else {
                await apiFetch(API_BASE, { method: 'POST', body: JSON.stringify(payload) });
                toast('Education level created successfully.');
            }
            bsModal('formModal').hide();
            loadItems(currentPage);
        } catch (e) {
            const msg = e.message ?? 'Validation failed.';
            toast(e.errors ? Object.values(e.errors).flat().join('<br>') : msg, 'error');
        } finally { btn.disabled = false; spinner.classList.add('d-none'); }
    }

    function openDeleteModal(id, name) {
        currentId = id;
        document.getElementById('deleteItemName').textContent = name;
        bsModal('deleteModal').show();
    }

    async function confirmDelete() {
        const btn = document.getElementById('confirmDeleteBtn'), spinner = document.getElementById('deleteBtnSpinner');
        btn.disabled = true; spinner.classList.remove('d-none');
        try {
            await apiFetch(`${API_BASE}/${currentId}`, { method: 'DELETE' });
            toast('Education level deleted successfully.');
            bsModal('deleteModal').hide();
            loadItems(currentPage);
        } catch (e) { toast(e.message ?? 'Failed to delete.', 'error'); }
        finally { btn.disabled = false; spinner.classList.add('d-none'); }
    }

    document.addEventListener('DOMContentLoaded', () => loadItems(1));

</script>
