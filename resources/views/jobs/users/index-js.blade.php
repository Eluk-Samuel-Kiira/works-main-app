<script>

    const API_BASE   = '/api/v1/users';
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
    function formatDateTime(d) {
        if (!d) return '—';
        return new Date(d).toLocaleString('en-UG', { year:'numeric', month:'short', day:'numeric', hour:'2-digit', minute:'2-digit' });
    }
    function userName(item) {
        return [item.first_name, item.last_name].filter(Boolean).join(' ') || item.email || '—';
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
        tbody.innerHTML = `<tr><td colspan="7" class="text-center py-4"><div class="spinner-border text-primary"></div></td></tr>`;
        try {
            const res = await apiFetch(`${API_BASE}?${buildQueryString(page)}`);
            renderTable(res.data);
            renderPagination(res.meta ?? {});
        } catch (e) {
            tbody.innerHTML = `<tr><td colspan="7" class="text-center text-danger py-3"><i class="ti ti-alert-circle me-1"></i>Failed to load users.</td></tr>`;
        }
    }

    function renderTable(items) {
        const tbody = document.getElementById('tableBody');
        if (!items || items.length === 0) {
            tbody.innerHTML = `<tr><td colspan="7" class="text-center py-4 text-muted">No users found.</td></tr>`;
            return;
        }
        tbody.innerHTML = items.map((item, i) => `
            <tr>
                <td class="text-muted">${((currentPage - 1) * 15) + i + 1}</td>
                <td><span class="fw-semibold">${esc(userName(item))}</span></td>
                <td><small>${esc(item.email)}</small></td>
                <td>${esc(item.phone) || '—'}</td>
                <td>${item.is_active ? '<span class="badge bg-success">Active</span>' : '<span class="badge bg-secondary">Inactive</span>'}</td>
                <td><small>${formatDateTime(item.last_login_at)}</small></td>
                <td>
                    <div class="d-flex align-items-center gap-1">
                        <button class="btn btn-sm bg-primary-subtle text-primary p-1" title="View" onclick="openViewModal(${item.id})"><i class="ti ti-eye" style="font-size:1.25rem;"></i></button>
                        <button class="btn btn-sm bg-success-subtle text-success p-1" title="Edit" onclick="openEditModal(${item.id})"><i class="ti ti-pencil" style="font-size:1.25rem;"></i></button>
                        <button class="btn btn-sm bg-danger-subtle text-danger p-1" title="Delete" onclick="openDeleteModal(${item.id}, '${esc(userName(item))}')"><i class="ti ti-trash" style="font-size:1.25rem;"></i></button>
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
            document.getElementById('viewModalTitle').textContent = userName(item);
            document.getElementById('viewModalBody').innerHTML = `
            <table class="table table-bordered mb-0">
                <tr><th width="180">Name</th><td>${esc(userName(item))}</td></tr>
                <tr><th>Email</th><td>${esc(item.email)}</td></tr>
                <tr><th>Phone</th><td>${esc(item.phone) || '—'}</td></tr>
                <tr><th>Country Code</th><td>${esc(item.country_code) || '—'}</td></tr>
                <tr><th>UUID</th><td><code>${esc(item.uuid) || '—'}</code></td></tr>
                <tr><th>Status</th><td>${item.is_active ? '<span class="badge bg-success">Active</span>' : '<span class="badge bg-secondary">Inactive</span>'}</td></tr>
                <tr><th>Email Verified</th><td>${item.email_verified_at ? `<span class="badge bg-success">Verified</span> <small class="text-muted">${formatDateTime(item.email_verified_at)}</small>` : '<span class="badge bg-warning text-dark">Unverified</span>'}</td></tr>
                <tr><th>Last Login</th><td>${formatDateTime(item.last_login_at)}</td></tr>
                <tr><th>Created</th><td>${formatDate(item.created_at)}</td></tr>
                <tr><th>Updated</th><td>${formatDate(item.updated_at)}</td></tr>
            </table>`;
        } catch (e) {
            document.getElementById('viewModalBody').innerHTML = '<div class="alert alert-danger">Failed to load user details.</div>';
        }
    }

    function switchToEdit() { bsModal('viewModal').hide(); openEditModal(currentId); }

    function openCreateModal() {
        currentId = null;
        document.getElementById('formModalTitle').innerHTML = '<i class="ti ti-plus me-2"></i>Add User';
        document.getElementById('formId').value = '';
        document.getElementById('formFirstName').value = '';
        document.getElementById('formLastName').value = '';
        document.getElementById('formEmail').value = '';
        document.getElementById('formPhone').value = '';
        document.getElementById('formCountryCode').value = '';
        document.getElementById('formIsActive').checked = true;
        document.getElementById('formBtnText').textContent = 'Save';
        bsModal('formModal').show();
    }

    async function openEditModal(id) {
        currentId = id;
        document.getElementById('formModalTitle').innerHTML = '<i class="ti ti-pencil me-2"></i>Edit User';
        document.getElementById('formBtnText').textContent = 'Update';
        bsModal('formModal').show();
        try {
            const res = await apiFetch(`${API_BASE}/${id}`);
            const item = res.data ?? res;
            document.getElementById('formId').value = item.id;
            document.getElementById('formFirstName').value = item.first_name ?? '';
            document.getElementById('formLastName').value = item.last_name ?? '';
            document.getElementById('formEmail').value = item.email ?? '';
            document.getElementById('formPhone').value = item.phone ?? '';
            document.getElementById('formCountryCode').value = item.country_code ?? '';
            document.getElementById('formIsActive').checked = !!item.is_active;
        } catch (e) { toast('Failed to load user data.', 'error'); bsModal('formModal').hide(); }
    }

    async function submitSave() {
        const btn = document.getElementById('formSaveBtn'), spinner = document.getElementById('formBtnSpinner');
        btn.disabled = true; spinner.classList.remove('d-none');
        const payload = {
            first_name:   document.getElementById('formFirstName').value.trim(),
            last_name:    document.getElementById('formLastName').value.trim(),
            email:        document.getElementById('formEmail').value.trim(),
            phone:        document.getElementById('formPhone').value.trim() || null,
            country_code: document.getElementById('formCountryCode').value.trim() || null,
            is_active:    document.getElementById('formIsActive').checked,
        };
        try {
            if (currentId) {
                await apiFetch(`${API_BASE}/${currentId}`, { method: 'PATCH', body: JSON.stringify(payload) });
                toast('User updated successfully.');
            } else {
                await apiFetch(API_BASE, { method: 'POST', body: JSON.stringify(payload) });
                toast('User created successfully.');
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
            toast('User deleted successfully.');
            bsModal('deleteModal').hide();
            loadItems(currentPage);
        } catch (e) { toast(e.message ?? 'Failed to delete.', 'error'); }
        finally { btn.disabled = false; spinner.classList.add('d-none'); }
    }

    document.addEventListener('DOMContentLoaded', () => loadItems(1));

</script>
