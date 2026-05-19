<script>
    const API_BASE   = '/api/v1/users';
    const API_ROLES  = '/api/v1/roles';
    const CSRF_TOKEN = document.querySelector('meta[name="csrf-token"]')?.content ?? '';
    let currentPage   = 1;
    let currentId     = null;
    let debounceTimer = null;
    let availableRoles = [];
    let roleStats = {};

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
    
    function getRoleBadge(roleName) {
        if (!roleName || roleName === 'no_role') {
            return '<span class="badge bg-secondary">No Role</span>';
        }
        
        const colorMap = {
            'admin': 'primary',
            'job_seeker': 'info',
            'employer': 'success',
            'super_admin': 'danger'
        };
        const color = colorMap[roleName] || 'secondary';
        const displayName = roleName.replace(/_/g, ' ').replace(/\b\w/g, l => l.toUpperCase());
        return `<span class="badge bg-${color}">${displayName}</span>`;
    }
    
    function toast(msg, type = 'success') { 
        if (typeof showToast === 'function') {
            showToast(type, msg);
        } else {
            alert(msg);
        }
    }
    
    function bsModal(id) { 
        return bootstrap.Modal.getOrCreateInstance(document.getElementById(id)); 
    }

    async function apiFetch(url, options = {}) {
        const res = await fetch(url, {
            ...options,
            headers: { 
                'Content-Type':'application/json', 
                'Accept':'application/json', 
                'X-CSRF-TOKEN':CSRF_TOKEN, 
                ...(options.headers ?? {}) 
            },
        });
        const data = await res.json();
        if (!res.ok) throw data;
        return data;
    }

    // Update role dropdown with counts
    function updateRoleDropdownWithCounts() {
        const filterRoleSelect = document.getElementById('filterRole');
        if (!filterRoleSelect) {
            console.error('filterRole element not found');
            return;
        }
        
        // Keep the "All Roles" option
        filterRoleSelect.innerHTML = '<option value="">All Roles</option>';
        
        // Add each role with its count
        if (availableRoles && availableRoles.length > 0) {
            availableRoles.forEach(role => {
                const count = roleStats[role.name] || 0;
                const option = document.createElement('option');
                option.value = role.name;
                const displayName = role.name.replace(/_/g, ' ').replace(/\b\w/g, l => l.toUpperCase());
                option.textContent = `${displayName} (${count})`;
                filterRoleSelect.appendChild(option);
            });
        } else {
            // Fallback: Add default roles if no roles from API
            const defaultRoles = [
                { name: 'admin', display: 'Admin' },
                { name: 'job_seeker', display: 'Job Seeker' },
                { name: 'employer', display: 'Employer' }
            ];
            
            defaultRoles.forEach(role => {
                const count = roleStats[role.name] || 0;
                const option = document.createElement('option');
                option.value = role.name;
                option.textContent = `${role.display} (${count})`;
                filterRoleSelect.appendChild(option);
            });
        }
        
        // Add "No Role" option (disabled)
        const noRoleCount = roleStats['no_role'] || 0;
        const noRoleOption = document.createElement('option');
        noRoleOption.value = '';
        noRoleOption.textContent = `── No Role Assigned (${noRoleCount}) ──`;
        noRoleOption.disabled = true;
        noRoleOption.style.backgroundColor = '#f0f0f0';
        filterRoleSelect.appendChild(noRoleOption);
        
        // console.log('Role dropdown updated with', filterRoleSelect.options.length, 'options');
    }

    async function loadAvailableRoles() {
        // console.log('Fetching roles from:', API_ROLES);
        
        try {
            const response = await fetch(API_ROLES, {
                headers: {
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': CSRF_TOKEN
                }
            });
            
            // console.log('Roles API response status:', response.status);
            
            if (!response.ok) {
                throw new Error(`HTTP ${response.status}: ${response.statusText}`);
            }
            
            const result = await response.json();
            // console.log('Roles API response data:', result);
            
            if (result && result.data && Array.isArray(result.data)) {
                availableRoles = result.data;
                // console.log('Available roles loaded:', availableRoles.length, availableRoles);
            } else {
                console.warn('No roles data received, using empty array');
                availableRoles = [];
            }
            
            // Populate form role dropdown
            const formRoleSelect = document.getElementById('formRole');
            if (formRoleSelect) {
                formRoleSelect.innerHTML = '<option value="">Select Role</option>';
                
                if (availableRoles.length > 0) {
                    availableRoles.forEach(role => {
                        const option = document.createElement('option');
                        option.value = role.name;
                        const displayName = role.name.replace(/_/g, ' ').replace(/\b\w/g, l => l.toUpperCase());
                        option.textContent = displayName;
                        formRoleSelect.appendChild(option);
                    });
                } else {
                    // Fallback default roles
                    const defaultRoles = [
                        { name: 'admin', display: 'Admin' },
                        { name: 'job_seeker', display: 'Job Seeker' },
                        { name: 'employer', display: 'Employer' }
                    ];
                    defaultRoles.forEach(role => {
                        const option = document.createElement('option');
                        option.value = role.name;
                        option.textContent = role.display;
                        formRoleSelect.appendChild(option);
                    });
                }
                // console.log('Form role dropdown populated');
            }
            
        } catch (e) {
            console.error('Failed to load roles:', e);
            console.error('Error details:', e.message);
            
            // Fallback: Use default roles
            // console.log('Using default roles as fallback');
            availableRoles = [
                { name: 'admin', display: 'Admin' },
                { name: 'job_seeker', display: 'Job Seeker' },
                { name: 'employer', display: 'Employer' }
            ];
            
            // Still populate the form dropdown with defaults
            const formRoleSelect = document.getElementById('formRole');
            if (formRoleSelect) {
                formRoleSelect.innerHTML = '<option value="">Select Role</option>';
                availableRoles.forEach(role => {
                    const option = document.createElement('option');
                    option.value = role.name;
                    option.textContent = role.display;
                    formRoleSelect.appendChild(option);
                });
            }
        }
    }

    function debounceLoad() { 
        clearTimeout(debounceTimer); 
        debounceTimer = setTimeout(() => loadItems(1), 400); 
    }
    
    function resetFilters() {
        document.getElementById('filterSearch').value = '';
        document.getElementById('filterStatus').value = '';
        const filterRole = document.getElementById('filterRole');
        if (filterRole) filterRole.value = '';
        loadItems(1);
    }

    function buildQueryString(page) {
        const params = new URLSearchParams({ page, per_page: 15 });
        const search = document.getElementById('filterSearch')?.value.trim();
        const status = document.getElementById('filterStatus')?.value;
        const role = document.getElementById('filterRole')?.value;
        if (search) params.set('search', search);
        if (status !== '') params.set('is_active', status);
        if (role && role !== '') params.set('role', role);
        return params.toString();
    }

    async function loadItems(page = 1) {
        currentPage = page;
        const tbody = document.getElementById('tableBody');
        if (tbody) {
            tbody.innerHTML = `<tr><td colspan="8" class="text-center py-4"><div class="spinner-border text-primary"></div></td></tr>`;
        }
        
        try {
            const res = await apiFetch(`${API_BASE}?${buildQueryString(page)}`);
            
            // Store role stats
            if (res.meta && res.meta.role_stats) {
                roleStats = res.meta.role_stats;
                // console.log('Role stats received:', roleStats);
                // Update dropdown with counts
                updateRoleDropdownWithCounts();
            } else {
                console.warn('No role stats in response');
                // Still try to update dropdown with whatever stats we have
                updateRoleDropdownWithCounts();
            }
            
            renderTable(res.data);
            renderPagination(res.meta ?? {});
        } catch (e) {
            console.error('Error loading users:', e);
            if (tbody) {
                tbody.innerHTML = `<tr><td colspan="8" class="text-center text-danger py-3">
                    <i class="ti ti-alert-circle me-1"></i>Failed to load users: ${e.message || 'Unknown error'}
                <\/td></tr>`;
            }
            // Still try to update dropdown
            updateRoleDropdownWithCounts();
        }
    }

    function renderTable(items) {
        const tbody = document.getElementById('tableBody');
        if (!tbody) return;
        
        if (!items || items.length === 0) {
            tbody.innerHTML = `<tr><td colspan="8" class="text-center py-4 text-muted">No users found.<\/td></tr>`;
            return;
        }
        
        tbody.innerHTML = items.map((item, i) => {
            const roleName = item.primary_role || (item.role_names && item.role_names[0]) || null;
            return `
            <tr>
                <td class="text-muted">${((currentPage - 1) * 15) + i + 1}<\/td>
                <td><span class="fw-semibold">${esc(userName(item))}</span><\/td>
                <td><small>${esc(item.email)}</small><\/td>
                <td>${esc(item.phone) || '—'}<\/td>
                <td>${getRoleBadge(roleName)}<\/td>
                <td>${item.is_active ? '<span class="badge bg-success">Active</span>' : '<span class="badge bg-secondary">Inactive</span>'}<\/td>
                <td><small>${formatDateTime(item.last_login_at)}</small><\/td>
                <td>
                    <div class="d-flex align-items-center gap-1">
                        <button class="btn btn-sm bg-primary-subtle text-primary p-1" title="View" onclick="openViewModal(${item.id})"><i class="ti ti-eye" style="font-size:1.25rem;"><\/i><\/button>
                        <button class="btn btn-sm bg-success-subtle text-success p-1" title="Edit" onclick="openEditModal(${item.id})"><i class="ti ti-pencil" style="font-size:1.25rem;"><\/i><\/button>
                        <button class="btn btn-sm bg-danger-subtle text-danger p-1" title="Delete" onclick="openDeleteModal(${item.id}, '${esc(userName(item))}')"><i class="ti ti-trash" style="font-size:1.25rem;"><\/i><\/button>
                    </div>
                <\/td>
            </tr>
        `}).join('');
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
        html += `<li class="${cur > 1 ? 'page-item' : 'page-item disabled'}"><a class="page-link" href="#" onclick="loadItems(1);return false;">«<\/a><\/li>`;
        html += `<li class="${cur > 1 ? 'page-item' : 'page-item disabled'}"><a class="page-link" href="#" onclick="loadItems(${cur-1});return false;">‹<\/a><\/li>`;
        
        if (pages[0] > 1) html += `<li class="page-item disabled"><span class="page-link">…<\/span><\/li>`;
        
        pages.forEach(p => { 
            html += p === cur 
                ? `<li class="page-item active"><span class="page-link">${p}<\/span><\/li>` 
                : `<li class="page-item"><a class="page-link" href="#" onclick="loadItems(${p});return false;">${p}<\/a><\/li>`; 
        });
        
        if (pages[pages.length-1] < last) html += `<li class="page-item disabled"><span class="page-link">…<\/span><\/li>`;
        
        html += `<li class="${cur < last ? 'page-item' : 'page-item disabled'}"><a class="page-link" href="#" onclick="loadItems(${cur+1});return false;">›<\/a><\/li>`;
        html += `<li class="${cur < last ? 'page-item' : 'page-item disabled'}"><a class="page-link" href="#" onclick="loadItems(${last});return false;">»<\/a><\/li>`;
        html += '<\/ul>';
        
        paginationLinks.innerHTML = html;
    }

    // Keep all your modal functions (openViewModal, openEditModal, submitSave, etc.) 
    // exactly as they were in the previous version
    
    async function openViewModal(id) {
        currentId = id;
        const modalTitle = document.getElementById('viewModalTitle');
        const modalBody = document.getElementById('viewModalBody');
        
        if (modalTitle) modalTitle.textContent = 'Loading…';
        if (modalBody) modalBody.innerHTML = '<div class="text-center py-5"><div class="spinner-border text-primary"></div><\/div>';
        
        bsModal('viewModal').show();
        
        try {
            const res = await apiFetch(`${API_BASE}/${id}`);
            const item = res.data ?? res;
            const roleName = item.primary_role || (item.role_names && item.role_names[0]) || 'No Role';
            
            if (modalTitle) modalTitle.textContent = userName(item);
            if (modalBody) {
                modalBody.innerHTML = `
                <table class="table table-bordered mb-0">
                    <tr><th width="180">Name</th><td>${esc(userName(item))}<\/td><\/tr>
                    <tr><th>Email</th><td>${esc(item.email)}<\/td><\/tr>
                    <tr><th>Phone</th><td>${esc(item.phone) || '—'}<\/td><\/tr>
                    <tr><th>Country Code</th><td>${esc(item.country_code) || '—'}<\/td><\/tr>
                    <tr><th>UUID</th><td><code>${esc(item.uuid) || '—'}<\/code><\/td><\/tr>
                    <tr><th>Role</th><td>${getRoleBadge(roleName)}<\/td><\/tr>
                    <tr><th>Status</th><td>${item.is_active ? '<span class="badge bg-success">Active</span>' : '<span class="badge bg-secondary">Inactive</span>'}<\/td><\/tr>
                    <tr><th>Email Verified</th><td>${item.email_verified_at ? `<span class="badge bg-success">Verified</span> <small class="text-muted">${formatDateTime(item.email_verified_at)}</small>` : '<span class="badge bg-warning text-dark">Unverified</span>'}<\/td><\/tr>
                    <tr><th>Last Login</th><td>${formatDateTime(item.last_login_at)}<\/td><\/tr>
                    <tr><th>Created</th><td>${formatDate(item.created_at)}<\/td><\/tr>
                    <tr><th>Updated</th><td>${formatDate(item.updated_at)}<\/td><\/tr>
                <\/table>`;
            }
        } catch (e) {
            if (modalBody) {
                modalBody.innerHTML = '<div class="alert alert-danger">Failed to load user details.<\/div>';
            }
        }
    }

    function switchToEdit() { 
        bsModal('viewModal').hide(); 
        openEditModal(currentId); 
    }

    function openCreateModal() {
        currentId = null;
        document.getElementById('formModalTitle').innerHTML = '<i class="ti ti-plus me-2"></i>Add User';
        document.getElementById('formId').value = '';
        document.getElementById('formFirstName').value = '';
        document.getElementById('formLastName').value = '';
        document.getElementById('formEmail').value = '';
        document.getElementById('formPhone').value = '';
        document.getElementById('formCountryCode').value = '';
        if (document.getElementById('formRole')) document.getElementById('formRole').value = '';
        if (document.getElementById('formIsActive')) document.getElementById('formIsActive').checked = true;
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
            if (document.getElementById('formRole')) {
                document.getElementById('formRole').value = item.primary_role || (item.role_names && item.role_names[0]) || '';
            }
            if (document.getElementById('formIsActive')) {
                document.getElementById('formIsActive').checked = !!item.is_active;
            }
        } catch (e) { 
            toast('Failed to load user data.', 'error'); 
            bsModal('formModal').hide(); 
        }
    }

    async function submitSave() {
        const btn = document.getElementById('formSaveBtn');
        const spinner = document.getElementById('formBtnSpinner');
        
        if (btn) btn.disabled = true;
        if (spinner) spinner.classList.remove('d-none');
        
        const payload = {
            first_name:   document.getElementById('formFirstName')?.value.trim() || '',
            last_name:    document.getElementById('formLastName')?.value.trim() || '',
            email:        document.getElementById('formEmail')?.value.trim() || '',
            phone:        document.getElementById('formPhone')?.value.trim() || null,
            country_code: document.getElementById('formCountryCode')?.value.trim() || null,
            is_active:    document.getElementById('formIsActive')?.checked || false,
            role:         document.getElementById('formRole')?.value || null,
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
        } finally { 
            if (btn) btn.disabled = false;
            if (spinner) spinner.classList.add('d-none');
        }
    }

    function openDeleteModal(id, name) {
        currentId = id;
        const deleteItemName = document.getElementById('deleteItemName');
        if (deleteItemName) deleteItemName.textContent = name;
        bsModal('deleteModal').show();
    }

    async function confirmDelete() {
        const btn = document.getElementById('confirmDeleteBtn');
        const spinner = document.getElementById('deleteBtnSpinner');
        
        if (btn) btn.disabled = true;
        if (spinner) spinner.classList.remove('d-none');
        
        try {
            await apiFetch(`${API_BASE}/${currentId}`, { method: 'DELETE' });
            toast('User deleted successfully.');
            bsModal('deleteModal').hide();
            loadItems(currentPage);
        } catch (e) { 
            toast(e.message ?? 'Failed to delete.', 'error'); 
        } finally { 
            if (btn) btn.disabled = false;
            if (spinner) spinner.classList.add('d-none');
        }
    }

    // Initialize
    document.addEventListener('DOMContentLoaded', async () => {
        // console.log('DOM loaded, initializing...');
        await loadAvailableRoles();
        await loadItems(1);
    });
</script>