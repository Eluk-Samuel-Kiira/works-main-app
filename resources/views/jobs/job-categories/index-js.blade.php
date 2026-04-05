<script>

    const API_BASE   = '/api/v1/job-categories';
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
        tbody.innerHTML = `<tr><td colspan="6" class="text-center py-4"><div class="spinner-border text-primary"></div></td></tr>`;
        try {
            const res = await apiFetch(`${API_BASE}?${buildQueryString(page)}`);
            renderTable(res.data);
            renderPagination(res.meta ?? {});
        } catch (e) {
            tbody.innerHTML = `<tr><td colspan="6" class="text-center text-danger py-3"><i class="ti ti-alert-circle me-1"></i>Failed to load job categories.</td></tr>`;
        }
    }

    function renderTable(items) {
        const tbody = document.getElementById('tableBody');
        if (!items || items.length === 0) {
            tbody.innerHTML = `<tr><td colspan="6" class="text-center py-4 text-muted">No job categories found.</td></tr>`;
            return;
        }
        tbody.innerHTML = items.map((item, i) => `
            <tr>
                <td class="text-muted">${((currentPage - 1) * 15) + i + 1}</td>
                <td><span class="fw-semibold">${esc(item.name)}</span></td>
                <td class="text-center">
                    ${item.icon
                        ? `<i class="${esc(item.icon)} fs-5 text-primary"></i>`
                        : '<span class="text-muted">—</span>'}
                </td>
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
                <tr><th>Icon</th><td>${item.icon ? `<i class="${esc(item.icon)} fs-5 me-2"></i>${esc(item.icon)}` : '—'}</td></tr>
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
        document.getElementById('formModalTitle').innerHTML = '<i class="ti ti-plus me-2"></i>Add Category';
        document.getElementById('formId').value = '';
        document.getElementById('formName').value = '';
        document.getElementById('formIcon').value = '';
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
        document.getElementById('formModalTitle').innerHTML = '<i class="ti ti-pencil me-2"></i>Edit Category';
        document.getElementById('formBtnText').textContent = 'Update';
        bsModal('formModal').show();
        try {
            const res = await apiFetch(`${API_BASE}/${id}`);
            const item = res.data ?? res;
            document.getElementById('formId').value = item.id;
            document.getElementById('formName').value = item.name ?? '';
            setIconPickerValue(item.icon || 'bi bi-folder2');
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
            icon: document.getElementById('formIcon').value.trim() || null,
            description: document.getElementById('formDescription').value.trim() || null,
            meta_title: document.getElementById('formMetaTitle').value.trim() || null,
            meta_description: document.getElementById('formMetaDescription').value.trim() || null,
            sort_order: parseInt(document.getElementById('formSortOrder').value) || 0,
            is_active: document.getElementById('formIsActive').checked,
        };
        try {
            if (currentId) {
                await apiFetch(`${API_BASE}/${currentId}`, { method: 'PATCH', body: JSON.stringify(payload) });
                toast('Category updated successfully.');
            } else {
                await apiFetch(API_BASE, { method: 'POST', body: JSON.stringify(payload) });
                toast('Category created successfully.');
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
            toast('Category deleted successfully.');
            bsModal('deleteModal').hide();
            loadItems(currentPage);
        } catch (e) { toast(e.message ?? 'Failed to delete.', 'error'); }
        finally { btn.disabled = false; spinner.classList.add('d-none'); }
    }

    document.addEventListener('DOMContentLoaded', () => loadItems(1));


    // ── Bootstrap Icons list (job-relevant subset + common) ──────────────────
    const BI_ICONS = [
        'bi-briefcase','bi-briefcase-fill','bi-building','bi-building-fill',
        'bi-person','bi-person-fill','bi-people','bi-people-fill',
        'bi-laptop','bi-laptop-fill','bi-code-slash','bi-terminal',
        'bi-heart-pulse','bi-hospital','bi-capsule','bi-activity',
        'bi-book','bi-book-fill','bi-mortarboard','bi-mortarboard-fill',
        'bi-cash-coin','bi-currency-dollar','bi-bank','bi-wallet2',
        'bi-truck','bi-car-front','bi-bus-front','bi-bicycle',
        'bi-hammer','bi-tools','bi-wrench','bi-gear','bi-gear-fill',
        'bi-camera','bi-camera-fill','bi-palette','bi-brush',
        'bi-shop','bi-shop-window','bi-cart','bi-bag',
        'bi-telephone','bi-telephone-fill','bi-headset','bi-chat-dots',
        'bi-tree','bi-flower1','bi-sun','bi-droplet',
        'bi-shield','bi-shield-fill','bi-lock','bi-key',
        'bi-graph-up','bi-bar-chart','bi-pie-chart','bi-calculator',
        'bi-house','bi-house-fill','bi-buildings','bi-door-open',
        'bi-airplane','bi-globe','bi-map','bi-geo-alt',
        'bi-cpu','bi-server','bi-hdd','bi-phone',
        'bi-newspaper','bi-megaphone','bi-broadcast','bi-tv',
        'bi-scissors','bi-needle','bi-basket','bi-box',
        'bi-music-note','bi-film','bi-controller','bi-joystick',
        'bi-lightning','bi-plugin','bi-battery','bi-wifi',
        'bi-star','bi-award','bi-trophy','bi-medal',
        'bi-envelope','bi-chat','bi-bell','bi-flag',
        'bi-clipboard','bi-file-text','bi-journal','bi-list-check',
        'bi-person-workspace','bi-person-badge','bi-person-gear','bi-person-check',
        'bi-robot','bi-cpu-fill','bi-diagram-3','bi-share',
        'bi-search','bi-eye','bi-binoculars','bi-zoom-in',
        'bi-folder2','bi-folder2-open','bi-archive','bi-inbox',
        'bi-plus-circle','bi-dash-circle','bi-check-circle','bi-x-circle',
    ];

    let iconPickerOpen = false;

    function renderIconGrid(filter = '') {
        const grid = document.getElementById('iconGrid');
        const filtered = filter
            ? BI_ICONS.filter(i => i.includes(filter.toLowerCase().replace(/^bi-?/,'')))
            : BI_ICONS;

        if (filtered.length === 0) {
            grid.innerHTML = '<p class="text-muted small p-2">No icons found.</p>';
            return;
        }

        grid.innerHTML = filtered.map(icon => `
            <button type="button"
                    class="btn btn-sm btn-outline-secondary p-2 icon-option"
                    style="width:44px;height:44px;border-radius:8px"
                    title="bi ${icon}"
                    onclick="selectIcon('bi ${icon}')">
                <i class="bi ${icon} fs-5"></i>
            </button>
        `).join('');

        // Highlight currently selected
        const current = document.getElementById('formIcon').value;
        grid.querySelectorAll('.icon-option').forEach(btn => {
            if (btn.title === current) {
                btn.classList.replace('btn-outline-secondary', 'btn-primary');
            }
        });
    }

    function filterIcons(val) {
        renderIconGrid(val);
    }

    function toggleIconPicker() {
        const panel = document.getElementById('iconPickerPanel');
        iconPickerOpen = !iconPickerOpen;
        panel.style.display = iconPickerOpen ? 'block' : 'none';
        if (iconPickerOpen) {
            document.getElementById('iconSearch').value = '';
            renderIconGrid();
            document.getElementById('iconSearch').focus();
        }
    }

    function selectIcon(fullClass) {
        // fullClass = "bi bi-briefcase"
        document.getElementById('formIcon').value = fullClass;
        document.getElementById('iconPreviewEl').className = fullClass + ' fs-5';
        document.getElementById('iconPreviewText').textContent = fullClass;
        document.getElementById('iconPickerPanel').style.display = 'none';
        iconPickerOpen = false;
    }

    function setIconPickerValue(val) {
        const icon = val || 'bi bi-folder2';
        document.getElementById('formIcon').value = icon;
        document.getElementById('iconPreviewEl').className = icon + ' fs-5';
        document.getElementById('iconPreviewText').textContent = icon;
    }

    // Close picker when clicking outside
    document.addEventListener('click', function(e) {
        if (iconPickerOpen && !document.getElementById('iconDropdownWrapper').contains(e.target)) {
            document.getElementById('iconPickerPanel').style.display = 'none';
            iconPickerOpen = false;
        }
    });

</script>
