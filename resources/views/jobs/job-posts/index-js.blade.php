<script>

    // ============================================================
    // CONFIG & STATE
    // ============================================================
    const API_BASE   = '/api/v1/job-posts';
    const CSRF_TOKEN = document.querySelector('meta[name="csrf-token"]')?.content ?? '';
    let currentPage   = 1;
    let currentSlug   = null;
    let debounceTimer = null;

    // ============================================================
    // UTILS
    // ============================================================
    function esc(str) {
        if (str == null) return '';
        return String(str)
            .replace(/&/g,'&amp;').replace(/</g,'&lt;')
            .replace(/>/g,'&gt;').replace(/"/g,'&quot;');
    }
    function formatDate(d) {
        if (!d) return '—';
        return new Date(d).toLocaleDateString('en-UG', { year:'numeric', month:'short', day:'numeric' });
    }
    function formatDateTime(d) {
        if (!d) return '—';
        return new Date(d).toLocaleString('en-UG', { year:'numeric', month:'short', day:'numeric', hour:'2-digit', minute:'2-digit' });
    }
    function capitalize(s) { return s ? s.charAt(0).toUpperCase() + s.slice(1) : s; }

    function locationLabel(loc, dutyStation) {
        if (!loc) return esc(dutyStation ?? '—');
        const parts = [loc.district, loc.country].filter(Boolean);
        return esc(parts.join(', ') || dutyStation || '—');
    }

    function posterLabel(p) {
        if (!p) return '—';
        // show() returns {name:...}, index() returns {first_name, last_name}
        const name = p.name
            ?? ((p.first_name ?? '') + ' ' + (p.last_name ?? '')).trim();
        return esc(name || p.email || '—');
    }

    // Reuse the existing showToast from app.blade.php layout
    // signature: showToast(type, message)  — type: success | error | warning | info
    function toast(msg, type = 'success') {
        if (typeof showToast === 'function') {
            showToast(type, msg);
        }
    }

    function bsModal(id) {
        return bootstrap.Modal.getOrCreateInstance(document.getElementById(id));
    }

    function statusBadge(job) {
        // console.log(job.is_active);
        if (!job.is_active)  return '<span class="badge bg-secondary">Inactive</span>';
        if (job.is_verified) return '<span class="badge bg-success">Verified</span>';
        return '<span class="badge bg-primary">Active</span>';
    }

    function boolBadge(val, trueLabel = 'Yes', falseLabel = 'No') {
        return val
            ? `<span class="badge bg-success-subtle text-success border border-success-subtle">${trueLabel}</span>`
            : `<span class="badge bg-secondary-subtle text-secondary border">${falseLabel}</span>`;
    }

    // ============================================================
    // API FETCH
    // ============================================================
    async function apiFetch(url, options = {}) {
        const res  = await fetch(url, {
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
    // FILTERS
    // ============================================================
    function debounceLoad() {
        clearTimeout(debounceTimer);
        debounceTimer = setTimeout(() => loadJobs(1), 400);
    }

    function resetFilters() {
        ['filterSearch','filterStatus','filterVerified','filterEmployment','filterPoster']
            .forEach(id => document.getElementById(id).value = '');
        loadJobs(1);
    }

    function buildQueryString(page) {
        const params     = new URLSearchParams({ page, per_page: 15 });
        const search     = document.getElementById('filterSearch').value.trim();
        const status     = document.getElementById('filterStatus').value;
        const verified   = document.getElementById('filterVerified').value;
        const employment = document.getElementById('filterEmployment').value;
        const posterId = document.getElementById('filterPoster').value;
        if (posterId) params.set('poster_id', posterId);
        if (search)      params.set('search', search);
        if (status !== '') params.set('is_active', status);
        if (verified !== '') params.set('is_verified', verified);
        if (employment)  params.set('employment_type', employment);
        return params.toString();
    }

    // ============================================================
    // LOAD & RENDER TABLE
    // ============================================================
    async function loadJobs(page = 1) {
        currentPage = page;
        const tbody = document.getElementById('jobPostsBody');
        tbody.innerHTML = `<tr><td colspan="9" class="text-center py-4">
            <div class="spinner-border text-primary"></div></td></tr>`;
        try {
            const res = await apiFetch(`${API_BASE}?${buildQueryString(page)}`);
            renderTable(res.data);
            renderPagination(res.meta ?? {});
        } catch (e) {
            tbody.innerHTML = `<tr><td colspan="9" class="text-center text-danger py-3">
                <i class="ti ti-alert-circle me-1"></i>Failed to load job posts.</td></tr>`;
        }
    }

    function renderTable(jobs) {
        const tbody = document.getElementById('jobPostsBody');
        if (!jobs || jobs.length === 0) {
            tbody.innerHTML = `<tr><td colspan="9" class="text-center py-4 text-muted">No job posts found.</td></tr>`;
            return;
        }
        tbody.innerHTML = jobs.map((job, i) => `
            <tr>
                <td class="text-muted">${((currentPage - 1) * 15) + i + 1}</td>
                <td>
                    <div class="fw-semibold">${esc(job.job_title)}</div>
                    <div class="mt-1">
                        ${job.is_urgent   ? '<span class="badge bg-danger me-1">Urgent</span>'              : ''}
                        ${job.is_featured ? '<span class="badge bg-warning me-1">Featured</span>' : ''}
                        ${job.is_simple_job ? '<span class="badge bg-info me-1">Simple</span>' : ''}
                        ${job.is_quick_gig ? '<span class="badge bg-warning text-dark me-1">Gig</span>' : ''}
                        ${job.is_pinged   ? '<span class="badge bg-info me-1">Pinged</span>'                : ''}
                        ${job.seo_score   ? `<span class="badge bg-light text-dark border">SEO ${job.seo_score}</span>` : ''}
                    </div>
                </td>
                <td>${esc(job.company?.name ?? '—')}</td>
                <td>${locationLabel(job.job_location, job.duty_station)}</td>
                <td><span class="badge bg-light text-dark border">${esc(job.employment_type ?? '—')}</span></td>
                <td>
                    ${statusBadge(job)}
                    ${job.is_indexed ? '<br><span class="badge bg-info-subtle text-info border border-info-subtle mt-1">Indexed</span>' : ''}
                </td>
                <td>
                    <small>${formatDate(job.deadline)}</small>
                    ${job.deadline && new Date(job.deadline) < new Date()
                        ? '<br><span class="badge bg-danger">Expired</span>' : ''}
                </td>
                <td><small>${posterLabel(job.poster)}</small></td>
                <td>
                    <div class="d-flex align-items-center gap-1">
                        <button class="btn btn-sm bg-primary-subtle text-primary p-1" title="View" onclick="openView('${job.slug}')">
                            <i class="ti ti-eye" style="width: 20px; height: 20px; font-size: 1.25rem;"></i>
                        </button>
                        <button class="btn btn-sm bg-success-subtle text-success p-1" title="Edit" onclick="openEdit('${job.slug}')">
                            <i class="ti ti-pencil" style="width: 20px; height: 20px; font-size: 1.25rem;"></i>
                        </button>
                        <div class="dropstart">
                            <button class="btn btn-sm btn-light dropdown-toggle" data-bs-toggle="dropdown">
                                <i class="ti ti-dots-vertical"></i>
                            </button>
                            <ul class="dropdown-menu">
                                <li><a class="dropdown-item" href="#" onclick="openStatus('${job.slug}',event)">
                                    <i class="ti ti-settings me-2"></i>Update Status</a></li>
                                <li><hr class="dropdown-divider"></li>
                                <li><a class="dropdown-item text-danger" href="#"
                                    onclick="openDelete('${job.slug}','${esc(job.job_title)}',event)">
                                    <i class="ti ti-trash me-2"></i>Delete</a></li>
                            </ul>
                        </div>
                    </div>
                </td>
            </tr>
        `).join('');
    }

    function renderPagination(meta) {
        document.getElementById('paginationInfo').textContent =
            meta.total ? `Showing ${meta.from}–${meta.to} of ${meta.total}` : '';

        const last = meta.last_page ?? 1;
        const cur  = meta.current_page ?? 1;
        const pages = [];
        for (let p = Math.max(1, cur - 2); p <= Math.min(last, cur + 2); p++) pages.push(p);

        const li = 'page-item', liA = 'page-item active', liD = 'page-item disabled', btn = 'page-link';
        let html = '<ul class="pagination pagination-md mb-0">';
        html += `<li class="${cur>1?li:liD}"><a class="${btn}" href="#" onclick="loadJobs(1);return false;">«</a></li>`;
        html += `<li class="${cur>1?li:liD}"><a class="${btn}" href="#" onclick="loadJobs(${cur-1});return false;">‹</a></li>`;
        if (pages[0] > 1) html += `<li class="${liD}"><span class="${btn}">…</span></li>`;
        pages.forEach(p => {
            html += p === cur
                ? `<li class="${liA}"><span class="${btn}">${p}</span></li>`
                : `<li class="${li}"><a class="${btn}" href="#" onclick="loadJobs(${p});return false;">${p}</a></li>`;
        });
        if (pages[pages.length-1] < last) html += `<li class="${liD}"><span class="${btn}">…</span></li>`;
        html += `<li class="${cur<last?li:liD}"><a class="${btn}" href="#" onclick="loadJobs(${cur+1});return false;">›</a></li>`;
        html += `<li class="${cur<last?li:liD}"><a class="${btn}" href="#" onclick="loadJobs(${last});return false;">»</a></li>`;
        html += '</ul>';
        document.getElementById('paginationLinks').innerHTML = html;
    }

    // ============================================================
    // VIEW MODAL
    // ============================================================
    async function openView(slug) {
        currentSlug = slug;
        document.getElementById('viewModalTitle').textContent = 'Loading…';
        document.getElementById('viewModalBody').innerHTML =
            '<div class="text-center py-5"><div class="spinner-border text-primary"></div></div>';
        bsModal('viewModal').show();
        try {
            const res = await apiFetch(`${API_BASE}/${slug}`);
            const job = res.data ?? res;
            document.getElementById('viewModalTitle').textContent = job.job_title;
            document.getElementById('viewModalBody').innerHTML = buildViewHtml(job);
        } catch (e) {
            document.getElementById('viewModalBody').innerHTML =
                '<div class="alert alert-danger">Failed to load job details.</div>';
        }
    }

    function buildViewHtml(job) {
        const loc     = job.job_location ?? {};
        const locStr  = [loc.district, loc.country].filter(Boolean).join(', ') || job.duty_station || '—';
        const company = job.company          ?? {};
        const exp     = job.experience_level ?? {};
        const edu     = job.education_level  ?? {};
        const cat     = job.job_category     ?? {};
        const ind     = job.industry         ?? {};
        const jtype   = job.job_type         ?? {};
        const sal     = job.salary_range     ?? {};
        const req     = {
            resume:   job.is_resume_required,
            cover:    job.is_cover_letter_required,
            academic: job.is_academic_documents_required,
        };

        return `
        <div class="row g-0">
            {{-- LEFT --}}
            <div class="col-md-8 pe-4 border-end">

                {{-- Header --}}
                <div class="d-flex align-items-start gap-3 mb-4">
                    ${company.logo
                        ? `<img src="${esc(company.logo)}" class="rounded" width="56" height="56" style="object-fit:contain;border:1px solid #eee">`
                        : `<div class="rounded bg-light d-flex align-items-center justify-content-center fw-bold text-secondary" style="width:56px;height:56px;font-size:20px">🏢</div>`}
                    <div>
                        <h5 class="mb-0 fw-bold">${esc(job.job_title)}</h5>
                        <div class="text-muted">${esc(company.name ?? '—')}</div>
                        <div class="mt-1 d-flex flex-wrap gap-1">
                            ${statusBadge(job)}
                            ${job.is_urgent   ? '<span class="badge bg-danger">Urgent</span>'             : ''}
                            ${job.is_featured ? '<span class="badge bg-warning text-dark">Featured</span>': ''}
                            ${job.is_simple_job ? '<span class="badge bg-info">Simple Job</span>' : ''}
                            ${job.is_quick_gig ? '<span class="badge bg-warning text-dark">Quick Gig</span>' : ''}
                            ${job.is_verified ? '<span class="badge bg-success">Verified</span>'          : ''}
                            ${job.is_pinged   ? '<span class="badge bg-info">Pinged</span>'               : ''}
                            ${job.is_indexed  ? '<span class="badge bg-primary">Indexed</span>'           : ''}
                        </div>
                    </div>
                </div>

                {{-- Quick stats --}}
                <div class="row g-2 mb-4">
                    <div class="col-6 col-md-3">
                        <div class="p-2 border rounded text-center">
                            <div class="text-muted small">Type</div>
                            <div class="fw-semibold">${esc(job.employment_type ?? '—')}</div>
                        </div>
                    </div>
                    <div class="col-6 col-md-3">
                        <div class="p-2 border rounded text-center">
                            <div class="text-muted small">Location</div>
                            <div class="fw-semibold">${esc(job.location_type ?? '—')}</div>
                        </div>
                    </div>
                    <div class="col-6 col-md-3">
                        <div class="p-2 border rounded text-center">
                            <div class="text-muted small">Salary</div>
                            <div class="fw-semibold text-success">${esc(job.formatted_salary ?? 'Negotiable')}</div>
                        </div>
                    </div>
                    <div class="col-6 col-md-3">
                        <div class="p-2 border rounded text-center">
                            <div class="text-muted small">Deadline</div>
                            <div class="fw-semibold ${job.deadline && new Date(job.deadline) < new Date() ? 'text-danger' : ''}">${formatDate(job.deadline)}</div>
                        </div>
                    </div>
                </div>

                ${job.job_description ? `
                <div class="mb-3">
                    <h6 class="fw-bold text-uppercase text-muted small mb-2">Job Description</h6>
                    <div class="border rounded p-3 bg-light">${job.job_description}</div>
                </div>` : ''}

                ${job.responsibilities ? `
                <div class="mb-3">
                    <h6 class="fw-bold text-uppercase text-muted small mb-2">Responsibilities</h6>
                    <div class="border rounded p-3 bg-light">${job.responsibilities}</div>
                </div>` : ''}

                ${job.skills ? `
                <div class="mb-3">
                    <h6 class="fw-bold text-uppercase text-muted small mb-2">Skills Required</h6>
                    <div class="border rounded p-3 bg-light">${job.skills}</div>
                </div>` : ''}

                ${job.qualifications ? `
                <div class="mb-3">
                    <h6 class="fw-bold text-uppercase text-muted small mb-2">Qualifications</h6>
                    <div class="border rounded p-3 bg-light">${job.qualifications}</div>
                </div>` : ''}

                ${job.application_procedure ? `
                <div class="mb-3">
                    <h6 class="fw-bold text-uppercase text-muted small mb-2">How to Apply</h6>
                    <div class="border rounded p-3 bg-light">${esc(job.application_procedure)}</div>
                </div>` : ''}
            </div>

            {{-- RIGHT SIDEBAR --}}
            <div class="col-md-4 ps-4">

                <h6 class="fw-bold text-uppercase text-muted small mb-3">Job Details</h6>
                <ul class="list-unstyled mb-4 small">
                    <li class="mb-2"><span class="text-muted">Location:</span> <strong>${esc(locStr)}</strong></li>
                    ${job.duty_station ? `<li class="mb-2"><span class="text-muted">Duty Station:</span> <strong>${esc(job.duty_station)}</strong></li>` : ''}
                    ${job.street_address ? `<li class="mb-2"><span class="text-muted">Address:</span> <strong>${esc(job.street_address)}</strong></li>` : ''}
                    <li class="mb-2"><span class="text-muted">Category:</span> <strong>${esc(cat.name ?? '—')}</strong></li>
                    <li class="mb-2"><span class="text-muted">Industry:</span> <strong>${esc(ind.name ?? '—')}</strong></li>
                    <li class="mb-2"><span class="text-muted">Job Type:</span> <strong>${esc(jtype.name ?? '—')}</strong></li>
                    <li class="mb-2"><span class="text-muted">Experience:</span> <strong>${esc(exp.name ?? '—')}${exp.min_years ? ` (${exp.min_years}+ yrs)` : ''}</strong></li>
                    <li class="mb-2"><span class="text-muted">Education:</span> <strong>${esc(edu.name ?? '—')}</strong></li>
                    ${sal.name ? `<li class="mb-2"><span class="text-muted">Salary Range:</span> <strong>${esc(sal.name)}</strong></li>` : ''}
                    <li class="mb-2"><span class="text-muted">Work Hours:</span> <strong>${esc(job.work_hours ?? '—')}</strong></li>
                    <li class="mb-2"><span class="text-muted">Currency:</span> <strong>${esc(job.currency ?? 'UGX')}</strong></li>
                    ${job.payment_period ? `<li class="mb-2"><span class="text-muted">Pay Period:</span> <strong>${esc(job.payment_period)}</strong></li>` : ''}
                </ul>

                <h6 class="fw-bold text-uppercase text-muted small mb-3">Status Flags</h6>
                <div class="row g-2 mb-4">
                    <div class="col-6">${boolBadge(job.is_active,    'Active',    'Inactive')}</div>
                    <div class="col-6">${boolBadge(job.is_verified,  'Verified',  'Unverified')}</div>
                    <div class="col-6">${boolBadge(job.is_featured,  'Featured',  'Not Featured')}</div>
                    <div class="col-6">${boolBadge(job.is_urgent,    'Urgent',    'Normal')}</div>
                    <div class="col-6">${boolBadge(job.is_simple_job, 'Simple Job', 'Regular Job')}</div>
                    <div class="col-6">${boolBadge(job.is_quick_gig,  'Quick Gig',  'Regular')}</div>
                    <div class="col-6">${boolBadge(job.is_pinged,    'Pinged',    'Not Pinged')}</div>
                    <div class="col-6">${boolBadge(job.is_indexed,   'Indexed',   'Not Indexed')}</div>
                    <div class="col-6">${boolBadge(job.is_whatsapp_contact, 'WhatsApp', 'No WhatsApp')}</div>
                    <div class="col-6">${boolBadge(job.is_telephone_call,   'Phone OK', 'No Phone')}</div>
                </div>

                ${job.last_pinged_at || job.last_indexed_at ? `
                <ul class="list-unstyled mb-4 small">
                    ${job.last_pinged_at  ? `<li class="mb-1"><span class="text-muted">Last Pinged:</span> ${formatDateTime(job.last_pinged_at)}</li>`  : ''}
                    ${job.last_indexed_at ? `<li class="mb-1"><span class="text-muted">Last Indexed:</span> ${formatDateTime(job.last_indexed_at)}</li>` : ''}
                    ${job.featured_until  ? `<li class="mb-1"><span class="text-muted">Featured Until:</span> ${formatDateTime(job.featured_until)}</li>` : ''}
                </ul>` : ''}

                <h6 class="fw-bold text-uppercase text-muted small mb-3">Application Requirements</h6>
                <ul class="list-unstyled mb-4 small">
                    <li class="mb-1">${req.resume   ? '✅' : '❌'} Resume / CV</li>
                    <li class="mb-1">${req.cover    ? '✅' : '❌'} Cover Letter</li>
                    <li class="mb-1">${req.academic ? '✅' : '❌'} Academic Documents</li>
                </ul>

                <h6 class="fw-bold text-uppercase text-muted small mb-3">Contact</h6>
                <ul class="list-unstyled mb-4 small">
                    ${job.email     ? `<li class="mb-1"><i class="ti ti-mail me-1 text-muted"></i>${esc(job.email)}</li>`     : ''}
                    ${job.telephone ? `<li class="mb-1"><i class="ti ti-phone me-1 text-muted"></i>${esc(job.telephone)}</li>` : ''}
                    ${!job.email && !job.telephone ? '<li class="text-muted">No contact info</li>' : ''}
                </ul>

                ${company.website || company.company_size ? `
                <h6 class="fw-bold text-uppercase text-muted small mb-3">Company</h6>
                <ul class="list-unstyled mb-4 small">
                    ${company.website      ? `<li class="mb-1"><a href="${esc(company.website)}" target="_blank" class="text-truncate d-block">${esc(company.website)}</a></li>` : ''}
                    ${company.company_size ? `<li class="mb-1"><span class="text-muted">Size:</span> ${esc(company.company_size)}</li>` : ''}
                    ${company.contact_email ? `<li class="mb-1"><span class="text-muted">Email:</span> ${esc(company.contact_email)}</li>` : ''}
                </ul>` : ''}

                <h6 class="fw-bold text-uppercase text-muted small mb-3">Performance</h6>
                <ul class="list-unstyled mb-4 small">
                    <li class="mb-1"><span class="text-muted">Views:</span> <strong>${(job.view_count ?? 0).toLocaleString()}</strong></li>
                    <li class="mb-1"><span class="text-muted">Applications:</span> <strong>${(job.application_count ?? 0).toLocaleString()}</strong></li>
                    <li class="mb-1"><span class="text-muted">Clicks:</span> <strong>${(job.click_count ?? 0).toLocaleString()}</strong></li>
                    <li class="mb-1"><span class="text-muted">SEO Score:</span> <strong>${job.seo_score ?? '—'}/100</strong></li>
                    ${job.content_quality_score ? `<li class="mb-1"><span class="text-muted">Content Score:</span> <strong>${job.content_quality_score}/100</strong></li>` : ''}
                    <li class="mb-1"><span class="text-muted">Posted:</span> ${formatDate(job.published_at)}</li>
                    ${job.poster ? `<li class="mb-1"><span class="text-muted">By:</span> ${esc(job.poster.name)}</li>` : ''}
                </ul>

                ${job.meta_title ? `
                <h6 class="fw-bold text-uppercase text-muted small mb-3">SEO</h6>
                <ul class="list-unstyled mb-4 small">
                    <li class="mb-1"><span class="text-muted">Meta Title:</span><br><span>${esc(job.meta_title)}</span></li>
                    ${job.meta_description ? `<li class="mb-1 mt-2"><span class="text-muted">Meta Desc:</span><br><span>${esc(job.meta_description)}</span></li>` : ''}
                    ${job.keywords ? `<li class="mb-1 mt-2"><span class="text-muted">Keywords:</span><br><span class="text-truncate d-block">${esc(job.keywords)}</span></li>` : ''}
                </ul>` : ''}
            </div>
        </div>`;
    }

    function switchToEdit() {
        bsModal('viewModal').hide();
        setTimeout(() => openEdit(currentSlug), 300);
    }

    // ============================================================
    // EDIT MODAL
    // ============================================================
    async function openEdit(slug) {
        currentSlug = slug;
        document.getElementById('editModalBody').innerHTML =
            '<div class="text-center py-5"><div class="spinner-border text-primary"></div></div>';
        bsModal('editModal').show();
        try {
            const res = await apiFetch(`${API_BASE}/${slug}`);
            const job = res.data ?? res;
            document.getElementById('editModalBody').innerHTML = buildEditForm(job);

            // Pre-fill rich editors AFTER DOM is ready
            richEditorSet('edit_job_description', job.job_description ?? '');
            richEditorSet('edit_responsibilities', job.responsibilities ?? '');
            richEditorSet('edit_qualifications', job.qualifications ?? '');
            richEditorSet('edit_skills', job.skills ?? '');

            // Initialize edit dropdowns
            initializeEditDropdowns(job);

            // Initialize datepicker
            if (typeof $ !== 'undefined' && $.fn.datepicker) {
                $('.datepicker-autoclose').datepicker({
                    autoclose: true,
                    todayHighlight: true,
                    format: 'mm/dd/yyyy'
                });
            }

        } catch (e) {
            document.getElementById('editModalBody').innerHTML =
                '<div class="alert alert-danger">Failed to load job for editing.</div>';
        }
    }

    function initializeEditDropdowns(job) {
        const editDropdownConfigs = {
            company: {
                url: '/api/v1/companies?per_page=500',
                inputId: 'e_company_input',
                hiddenId: 'e_company_id',
                listId: 'e_company_list',
                displayKey: 'name',
                valueKey: 'id'
            },
            job_category: {
                url: '/api/v1/job-categories?per_page=200',
                inputId: 'e_job_category_input',
                hiddenId: 'e_job_category_id',
                listId: 'e_job_category_list',
                displayKey: 'name',
                valueKey: 'id'
            },
            industry: {
                url: '/api/v1/industries?per_page=200',
                inputId: 'e_industry_input',
                hiddenId: 'e_industry_id',
                listId: 'e_industry_list',
                displayKey: 'name',
                valueKey: 'id'
            },
            job_location: {
                url: '/api/v1/job-locations?per_page=200',
                inputId: 'e_job_location_input',
                hiddenId: 'e_job_location_id',
                listId: 'e_job_location_list',
                formatItem: (item) => [item.district, item.country].filter(Boolean).join(', ')
            },
            job_type: {
                url: '/api/v1/job-types?per_page=200',
                inputId: 'e_job_type_input',
                hiddenId: 'e_job_type_id',
                listId: 'e_job_type_list',
                displayKey: 'name',
                valueKey: 'id'
            },
            experience_level: {
                url: '/api/v1/experience-levels?per_page=100',
                inputId: 'e_experience_level_input',
                hiddenId: 'e_experience_level_id',
                listId: 'e_experience_level_list',
                displayKey: 'name',
                valueKey: 'id'
            },
            education_level: {
                url: '/api/v1/education-levels?per_page=100',
                inputId: 'e_education_level_input',
                hiddenId: 'e_education_level_id',
                listId: 'e_education_level_list',
                displayKey: 'name',
                valueKey: 'id'
            },
            salary_range: {
                url: '/api/v1/salary-ranges?per_page=100',
                inputId: 'e_salary_range_input',
                hiddenId: 'e_salary_range_id',
                listId: 'e_salary_range_list',
                displayKey: 'name',
                valueKey: 'id'
            }
        };

        // Initialize edit dropdowns
        for (const [key, config] of Object.entries(editDropdownConfigs)) {
            loadDropdownItems(key, config, job);
        }
    }

    async function loadDropdownItems(key, config, job) {
        try {
            const res = await apiFetch(config.url);
            const items = res.data ?? [];
            
            if (!window.editDropdowns) window.editDropdowns = {};
            window.editDropdowns[key] = new TypableDropdown(config);
            window.editDropdowns[key].setItems(items);
            
            // Set current value if exists
            const currentId = job[config.valueKey];
            if (currentId) {
                // Find the item with matching id
                const selectedItem = items.find(item => item[config.valueKey] == currentId);
                if (selectedItem) {
                    window.editDropdowns[key].select(selectedItem);
                }
            }
        } catch (err) {
            console.error(`Failed to load ${key}:`, err);
        }
    }

    function buildEditForm(job) {
        const employmentTypes = ['full-time','part-time','contract','internship','volunteer','temporary'];
        const locationTypes   = ['on-site','remote','hybrid'];
        const paymentPeriods  = ['hourly','daily','weekly','monthly','yearly'];

        return `
        <form id="editForm">
            <div class="row g-3">

                <div class="col-12">
                    <label class="form-label fw-semibold">Job Title <span class="text-danger">*</span></label>
                    <input type="text" name="job_title" class="form-control" value="${esc(job.job_title)}" required>
                </div>

                <div class="col-md-6">
                    <label class="form-label fw-semibold">Employment Type</label>
                    <select name="employment_type" class="form-select">
                        ${employmentTypes.map(t => `<option value="${t}" ${job.employment_type===t?'selected':''}>${capitalize(t)}</option>`).join('')}
                    </select>
                </div>
                <div class="col-md-6">
                    <label class="form-label fw-semibold">Location Type</label>
                    <select name="location_type" class="form-select">
                        ${locationTypes.map(t => `<option value="${t}" ${job.location_type===t?'selected':''}>${capitalize(t)}</option>`).join('')}
                    </select>
                </div>

                {{-- Company Dropdown --}}
                <div class="col-md-4">
                    <label class="form-label fw-semibold">Company <span class="text-danger">*</span></label>
                    <div class="position-relative">
                        <input type="text" id="e_company_input" class="form-control" placeholder="Type to search company..." autocomplete="off" value="${esc(job.company?.name ?? '')}">
                        <input type="hidden" name="company_id" id="e_company_id" value="${job.company?.id ?? ''}">
                        <ul class="dropdown-menu w-100" id="e_company_list" style="max-height: 250px; overflow-y: auto;"></ul>
                    </div>
                </div>

                {{-- Job Category Dropdown --}}
                <div class="col-md-4">
                    <label class="form-label fw-semibold">Category <span class="text-danger">*</span></label>
                    <div class="position-relative">
                        <input type="text" id="e_job_category_input" class="form-control" placeholder="Type to search category..." autocomplete="off" value="${esc(job.job_category?.name ?? '')}">
                        <input type="hidden" name="job_category_id" id="e_job_category_id" value="${job.job_category?.id ?? ''}">
                        <ul class="dropdown-menu w-100" id="e_job_category_list" style="max-height: 250px; overflow-y: auto;"></ul>
                    </div>
                </div>

                {{-- Industry Dropdown --}}
                <div class="col-md-4">
                    <label class="form-label fw-semibold">Industry <span class="text-danger">*</span></label>
                    <div class="position-relative">
                        <input type="text" id="e_industry_input" class="form-control" placeholder="Type to search industry..." autocomplete="off" value="${esc(job.industry?.name ?? '')}">
                        <input type="hidden" name="industry_id" id="e_industry_id" value="${job.industry?.id ?? ''}">
                        <ul class="dropdown-menu w-100" id="e_industry_list" style="max-height: 250px; overflow-y: auto;"></ul>
                    </div>
                </div>

                {{-- Job Location Dropdown --}}
                <div class="col-md-4">
                    <label class="form-label fw-semibold">Location <span class="text-danger">*</span></label>
                    <div class="position-relative">
                        <input type="text" id="e_job_location_input" class="form-control" placeholder="Type to search location..." autocomplete="off" value="${job.job_location ? [job.job_location.district, job.job_location.country].filter(Boolean).join(', ') : ''}">
                        <input type="hidden" name="job_location_id" id="e_job_location_id" value="${job.job_location?.id ?? ''}">
                        <ul class="dropdown-menu w-100" id="e_job_location_list" style="max-height: 250px; overflow-y: auto;"></ul>
                    </div>
                </div>

                {{-- Job Type Dropdown --}}
                <div class="col-md-4">
                    <label class="form-label fw-semibold">Job Type <span class="text-danger">*</span></label>
                    <div class="position-relative">
                        <input type="text" id="e_job_type_input" class="form-control" placeholder="Type to search job type..." autocomplete="off" value="${esc(job.job_type?.name ?? '')}">
                        <input type="hidden" name="job_type_id" id="e_job_type_id" value="${job.job_type?.id ?? ''}">
                        <ul class="dropdown-menu w-100" id="e_job_type_list" style="max-height: 250px; overflow-y: auto;"></ul>
                    </div>
                </div>

                {{-- Experience Level Dropdown --}}
                <div class="col-md-4">
                    <label class="form-label fw-semibold">Experience Level <span class="text-danger">*</span></label>
                    <div class="position-relative">
                        <input type="text" id="e_experience_level_input" class="form-control" placeholder="Type to search experience..." autocomplete="off" value="${esc(job.experience_level?.name ?? '')}">
                        <input type="hidden" name="experience_level_id" id="e_experience_level_id" value="${job.experience_level?.id ?? ''}">
                        <ul class="dropdown-menu w-100" id="e_experience_level_list" style="max-height: 250px; overflow-y: auto;"></ul>
                    </div>
                </div>

                {{-- Education Level Dropdown --}}
                <div class="col-md-4">
                    <label class="form-label fw-semibold">Education Level <span class="text-danger">*</span></label>
                    <div class="position-relative">
                        <input type="text" id="e_education_level_input" class="form-control" placeholder="Type to search education..." autocomplete="off" value="${esc(job.education_level?.name ?? '')}">
                        <input type="hidden" name="education_level_id" id="e_education_level_id" value="${job.education_level?.id ?? ''}">
                        <ul class="dropdown-menu w-100" id="e_education_level_list" style="max-height: 250px; overflow-y: auto;"></ul>
                    </div>
                </div>

                {{-- Salary Range Dropdown --}}
                <div class="col-md-4">
                    <label class="form-label fw-semibold">Salary Range</label>
                    <div class="position-relative">
                        <input type="text" id="e_salary_range_input" class="form-control" placeholder="Type to search salary range..." autocomplete="off" value="${esc(job.salary_range?.name ?? '')}">
                        <input type="hidden" name="salary_range_id" id="e_salary_range_id" value="${job.salary_range?.id ?? ''}">
                        <ul class="dropdown-menu w-100" id="e_salary_range_list" style="max-height: 250px; overflow-y: auto;"></ul>
                    </div>
                </div>

                <div class="col-md-4">
                    <label class="form-label fw-semibold">Salary Amount</label>
                    <input type="number" name="salary_amount" class="form-control" value="${job.salary_amount ?? ''}">
                </div>
                <div class="col-md-4">
                    <label class="form-label fw-semibold">Currency</label>
                    <input type="text" name="currency" class="form-control" value="${esc(job.currency ?? 'UGX')}">
                </div>
                <div class="col-md-4">
                    <label class="form-label fw-semibold">Payment Period</label>
                    <select name="payment_period" class="form-select">
                        <option value="">— Select —</option>
                        ${paymentPeriods.map(p => `<option value="${p}" ${job.payment_period===p?'selected':''}>${capitalize(p)}</option>`).join('')}
                    </select>
                </div>

                <div class="col-md-6">
                    <label class="form-label fw-semibold">Application Deadline <span class="text-danger">*</span></label>
                    <div class="input-group">
                        <input type="text" name="deadline" class="form-control datepicker-autoclose" 
                            value="${job.deadline ? new Date(job.deadline).toLocaleDateString('en-US') : ''}" 
                            placeholder="mm/dd/yyyy" required/>
                        <span class="input-group-text">
                            <i class="ti ti-calendar fs-5"></i>
                        </span>
                    </div>
                </div>
                <div class="col-md-6">
                    <label class="form-label fw-semibold">Work Hours</label>
                    <input type="text" name="work_hours" class="form-control" value="${esc(job.work_hours ?? '')}">
                </div>

                <div class="col-md-6">
                    <label class="form-label fw-semibold">Duty Station</label>
                    <input type="text" name="duty_station" class="form-control" value="${esc(job.duty_station ?? '')}">
                </div>
                <div class="col-md-6">
                    <label class="form-label fw-semibold">Street Address</label>
                    <input type="text" name="street_address" class="form-control" value="${esc(job.street_address ?? '')}">
                </div>

                <div class="col-12">
                    <label class="form-label fw-semibold">Job Description <span class="text-danger">*</span></label>
                    ${buildRichEditor('edit_job_description', 'job_description', 'Describe the role…', 180)}
                    <input type="hidden" name="job_description" id="edit_job_description_hidden">
                </div>

                <div class="col-12">
                    <label class="form-label fw-semibold">Responsibilities</label>
                    ${buildRichEditor('edit_responsibilities', 'responsibilities', 'List key responsibilities…', 140)}
                    <input type="hidden" name="responsibilities" id="edit_responsibilities_hidden">
                </div>

                <div class="col-12">
                    <label class="form-label fw-semibold">Skills</label>
                    ${buildRichEditor('edit_skills', 'skills', 'List required skills…', 120)}
                    <input type="hidden" name="skills" id="edit_skills_hidden">
                </div>

                <div class="col-12">
                    <label class="form-label fw-semibold">Qualifications</label>
                    ${buildRichEditor('edit_qualifications', 'qualifications', 'List qualifications…', 120)}
                    <input type="hidden" name="qualifications" id="edit_qualifications_hidden">
                </div>

                <div class="col-12">
                    <label class="form-label fw-semibold">Application Procedure</label>
                    <input type="text" name="application_procedure" class="form-control"
                        value="${esc(job.application_procedure ?? '')}">
                </div>

                <div class="col-md-6">
                    <label class="form-label fw-semibold">Contact Email</label>
                    <input type="email" name="email" class="form-control" value="${esc(job.email ?? '')}">
                </div>
                <div class="col-md-6">
                    <label class="form-label fw-semibold">Telephone</label>
                    <input type="text" name="telephone" class="form-control" value="${esc(job.telephone ?? '')}">
                </div>

                <div class="col-12">
                    <label class="form-label fw-semibold mb-2">Job Type Flags</label>
                    <div class="d-flex flex-wrap gap-3 mb-3">
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" name="is_simple_job"
                                id="chkSimpleJob" value="1" ${job.is_simple_job ? 'checked' : ''}>
                            <label class="form-check-label" for="chkSimpleJob">
                                Simple Job <small class="text-muted">(Quick apply via link)</small>
                            </label>
                        </div>
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" name="is_quick_gig"
                                id="chkQuickGig" value="1" ${job.is_quick_gig ? 'checked' : ''}>
                            <label class="form-check-label" for="chkQuickGig">
                                Quick Gig <small class="text-muted">(Short-term / freelance)</small>
                            </label>
                        </div>
                    </div>
                </div>

                <div class="col-12">
                    <label class="form-label fw-semibold mb-2">Application Requirements</label>
                    <div class="d-flex flex-wrap gap-3">
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" name="is_resume_required"
                                id="chkResume" value="1" ${job.is_resume_required ? 'checked' : ''}>
                            <label class="form-check-label" for="chkResume">Resume / CV</label>
                        </div>
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" name="is_cover_letter_required"
                                id="chkCover" value="1" ${job.is_cover_letter_required ? 'checked' : ''}>
                            <label class="form-check-label" for="chkCover">Cover Letter</label>
                        </div>
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" name="is_academic_documents_required"
                                id="chkAcademic" value="1" ${job.is_academic_documents_required ? 'checked' : ''}>
                            <label class="form-check-label" for="chkAcademic">Academic Documents</label>
                        </div>
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" name="is_whatsapp_contact"
                                id="chkWA" value="1" ${job.is_whatsapp_contact ? 'checked' : ''}>
                            <label class="form-check-label" for="chkWA">WhatsApp Contact</label>
                        </div>
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" name="is_telephone_call"
                                id="chkTel" value="1" ${job.is_telephone_call ? 'checked' : ''}>
                            <label class="form-check-label" for="chkTel">Phone Call OK</label>
                        </div>
                    </div>
                </div>

                <div class="col-12">
                    <label class="form-label fw-semibold mb-2">SEO & Metadata</label>
                    <div class="row g-2">
                        <div class="col-md-6">
                            <label class="form-label small">Meta Title</label>
                            <input type="text" name="meta_title" class="form-control" value="${esc(job.meta_title ?? '')}">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small">Meta Description</label>
                            <textarea name="meta_description" class="form-control" rows="2">${esc(job.meta_description ?? '')}</textarea>
                        </div>
                        <div class="col-12">
                            <label class="form-label small">Keywords</label>
                            <input type="text" name="keywords" class="form-control" value="${esc(job.keywords ?? '')}">
                        </div>
                        <div class="col-12">
                            <label class="form-label small">Canonical URL</label>
                            <input type="url" name="canonical_url" class="form-control" value="${esc(job.canonical_url ?? '')}">
                        </div>
                    </div>
                </div>

                <div id="editFormErrors"></div>
            </div>
        </form>`;
    }

    async function submitEdit() {
        richEditorSync('edit_job_description');
        richEditorSync('edit_responsibilities');
        richEditorSync('edit_qualifications');
        richEditorSync('edit_skills');

        const form = document.getElementById('editForm');
        if (!form) return;
        const data = {};
        new FormData(form).forEach((v, k) => data[k] = v);
        
        // Convert deadline date from mm/dd/yyyy to YYYY-MM-DD
        if (data.deadline) {
            const dateParts = data.deadline.split('/');
            if (dateParts.length === 3) {
                // Assuming format is mm/dd/yyyy
                const month = dateParts[0];
                const day = dateParts[1];
                const year = dateParts[2];
                data.deadline = `${year}-${month.padStart(2, '0')}-${day.padStart(2, '0')}`;
            }
        }
        
        // Checkboxes — FormData omits unchecked ones
        ['is_resume_required','is_cover_letter_required',
        'is_academic_documents_required','is_whatsapp_contact','is_telephone_call',
        'is_simple_job','is_quick_gig']
            .forEach(k => { data[k] = data[k] === '1'; });

        document.getElementById('editBtnText').textContent = 'Saving…';
        document.getElementById('editBtnSpinner').classList.remove('d-none');
        document.getElementById('editSaveBtn').disabled = true;

        try {
            await apiFetch(`${API_BASE}/${currentSlug}`, { method: 'PATCH', body: JSON.stringify(data) });
            bsModal('editModal').hide();
            toast('Job post updated successfully!', 'success');
            loadJobs(currentPage);
        } catch (err) {
            const msgs = err.errors
                ? Object.values(err.errors).flat().map(m => `<li>${m}</li>`).join('')
                : (err.message ?? 'Update failed.');
            document.getElementById('editFormErrors').innerHTML =
                `<div class="alert alert-danger mt-2"><ul class="mb-0">${msgs}</ul></div>`;
        } finally {
            document.getElementById('editBtnText').textContent = 'Save Changes';
            document.getElementById('editBtnSpinner').classList.add('d-none');
            document.getElementById('editSaveBtn').disabled = false;
        }
    }

    // ============================================================
    // DELETE MODAL
    // ============================================================
    function openDelete(slug, title, e) {
        e?.preventDefault();
        currentSlug = slug;
        document.getElementById('deleteJobTitle').textContent = title;
        bsModal('deleteModal').show();
    }

    async function confirmDelete() {
        document.getElementById('deleteBtnText').textContent = 'Deleting…';
        document.getElementById('deleteBtnSpinner').classList.remove('d-none');
        document.getElementById('confirmDeleteBtn').disabled = true;
        try {
            await apiFetch(`${API_BASE}/${currentSlug}`, { method: 'DELETE' });
            bsModal('deleteModal').hide();
            toast('Job post deleted.', 'success');
            loadJobs(currentPage);
        } catch (e) {
            toast('Failed to delete job post.', 'error');
        } finally {
            document.getElementById('deleteBtnText').textContent = 'Delete';
            document.getElementById('deleteBtnSpinner').classList.add('d-none');
            document.getElementById('confirmDeleteBtn').disabled = false;
        }
    }

    // ============================================================
    // STATUS MODAL
    // ============================================================
    async function openStatus(slug, e) {
        e?.preventDefault();
        currentSlug = slug;
        document.getElementById('statusActionMsg').innerHTML = '';
        try {
            const res = await apiFetch(`${API_BASE}/${slug}`);
            const job = res.data ?? res;
            document.getElementById('statusJobTitle').textContent = job.job_title;
            
            // Active/Inactive badges
            if (job.is_active) {
                document.getElementById('badgeActive').textContent = 'Active';
                document.getElementById('badgeActive').className = 'badge bg-success';
                document.getElementById('badgeInactive').textContent = '';
                document.getElementById('badgeInactive').className = 'badge bg-secondary d-none';
            } else {
                document.getElementById('badgeActive').textContent = '';
                document.getElementById('badgeActive').className = 'badge bg-success d-none';
                document.getElementById('badgeInactive').textContent = 'Inactive';
                document.getElementById('badgeInactive').className = 'badge bg-danger';
            }
            
            // Verified badge
            if (job.is_verified) {
                document.getElementById('badgeVerified').textContent = 'Verified';
                document.getElementById('badgeVerified').className = 'badge bg-success';
            } else {
                document.getElementById('badgeVerified').textContent = 'Unverified';
                document.getElementById('badgeVerified').className = 'badge bg-secondary';
            }
            
            // Urgent badge
            if (job.is_urgent) {
                document.getElementById('badgeUrgent').textContent = 'Urgent';
                document.getElementById('badgeUrgent').className = 'badge bg-danger';
            } else {
                document.getElementById('badgeUrgent').textContent = 'Normal';
                document.getElementById('badgeUrgent').className = 'badge bg-secondary';
            }
            
        } catch (_) {}
        bsModal('statusModal').show();
    }

    async function doStatusAction(action) {
        const msgDiv = document.getElementById('statusActionMsg');
        msgDiv.innerHTML = `<div class="d-flex align-items-center gap-2 text-muted">
            <div class="spinner-border spinner-border-sm"></div> Updating…</div>`;
        try {
            const response = await apiFetch(`${API_BASE}/${currentSlug}/${action}`, { method: 'PATCH' });
            
            // Check if response has warning flag
            if (response.warning) {
                const warningMessage = response.message || `Job is already ${action}d!`;
                msgDiv.innerHTML = `
                    <div class="alert alert-warning py-2 mb-0">
                        <i class="ti ti-alert-triangle me-2"></i>⚠️ ${warningMessage}
                        ${response.warnings ? `<br><small class="text-muted">${JSON.stringify(response.warnings)}</small>` : ''}
                    </div>`;
                toast(warningMessage, 'warning');
                
                // Still reload jobs to reflect current state
                if (response.data) {
                    loadJobs(currentPage);
                }
                return;
            }
            
            // Success response
            const successMessage = response.message || `Job post ${action}d successfully!`;
            msgDiv.innerHTML = `<div class="alert alert-success py-2 mb-0">✓ ${successMessage}</div>`;
            toast(successMessage, 'success');

            // Close the modal
            const modal = bootstrap.Modal.getInstance(document.getElementById('statusModal'));
            if (modal) {
                modal.hide();
            }
            loadJobs(currentPage);
            
        } catch (e) {
            // Error response
            const errorMessage = e.message || `Failed to ${action} job post.`;
            msgDiv.innerHTML = `<div class="alert alert-danger py-2 mb-0">⚠️ ${errorMessage}</div>`;
            toast(errorMessage, 'error');
            
            // Display detailed errors if available
            if (e.errors) {
                const errorDetails = Object.values(e.errors).flat().join(', ');
                msgDiv.innerHTML += `<div class="alert alert-danger mt-2 small">Details: ${errorDetails}</div>`;
            }
        }
    }

    async function doFeature() {
        const days   = parseInt(document.getElementById('featureDays').value) || 7;
        const msgDiv = document.getElementById('statusActionMsg');
        msgDiv.innerHTML = `<div class="d-flex align-items-center gap-2 text-muted">
            <div class="spinner-border spinner-border-sm"></div> Featuring…</div>`;
        try {
            await apiFetch(`${API_BASE}/${currentSlug}/feature`, {
                method: 'PATCH', body: JSON.stringify({ days }),
            });
            msgDiv.innerHTML = `<div class="alert alert-success py-2 mb-0">Featured for ${days} days.</div>`;
            toast(`Featured for ${days} days!`, 'success');
            loadJobs(currentPage);
        } catch (e) {
            msgDiv.innerHTML = `<div class="alert alert-danger py-2 mb-0">Feature action failed.</div>`;
        }
    }

    function openCreateModal() {
        toast('Create modal coming soon!', 'info');
    }

    async function loadPosterFilter() {
        try {
            const res = await apiFetch('/api/v1/users/list');
            const select = document.getElementById('filterPoster');
            (res.data ?? []).forEach(u => {
                const opt = document.createElement('option');
                opt.value       = u.id;
                opt.textContent = u.name + (u.email ? ` (${u.email})` : '');
                // console.log(opt);
                select.appendChild(opt);
            });
        } catch (_) {}
    }

    // ============================================================
    // INIT
    // ============================================================
    document.addEventListener('DOMContentLoaded', () => {
        loadPosterFilter();
        loadJobs(1);
    });
</script>

<script>
    // Load indexing stats on page load
    async function loadIndexingStats() {
        try {
            const res = await apiFetch('/api/v1/job-posts/indexing-stats');
            const d   = res.data ?? res;
            
            // Map API keys to what the UI expects
            const pendingPing    = d.not_pinged ?? 0;        // jobs not yet pinged
            const pendingIndex   = d.not_indexed ?? 0;       // jobs not yet indexed
            const submitted      = d.submitted_to_indexing ?? 0;
            const indexed        = d.indexed ?? 0;
            
            // Update the badge on the button (show not_pinged count)
            const badge = document.getElementById('pendingIndexBadge');
            if (badge) {
                badge.textContent = pendingPing;
                // Change color based on count
                badge.className = (pendingPing > 0) 
                    ? 'badge bg-warning text-dark ms-1' 
                    : 'badge bg-success ms-1';
            }
            
            // Optional: update other stats if you add elements with these IDs later
            // document.getElementById('statPending')?.textContent = pendingPing;
            // document.getElementById('statSubmitted')?.textContent = submitted;
            // document.getElementById('statIndexed')?.textContent = indexed;
            
        } catch (e) {
            console.error('Stats load failed:', e);
            // Fallback: hide badge on error
            const badge = document.getElementById('pendingIndexBadge');
            if (badge) {
                badge.textContent = '–';
                badge.className = 'badge bg-secondary ms-1';
            }
        }
    }

    function openIndexingModal() {
        loadIndexingStats();
        bsModal('indexingModal').show();
    }

    async function runManualIndexing(mode = 'new') {
        const resultDiv = document.getElementById('indexingResult');
        resultDiv.innerHTML = `<div class="d-flex align-items-center gap-2 text-muted">
            <div class="spinner-border spinner-border-sm"></div>
            <span>Submitting to Google & Bing Indexing APIs...</span>
        </div>`;

        try {
            const res = await apiFetch('/api/v1/job-posts/manual-index', {
                method: 'POST',
                body: JSON.stringify({ mode })
            });

            const submitted = res.submitted ?? 0;
            const results   = res.results   ?? [];
            const okCount   = results.filter(r => r.success).length;
            const failCount = submitted - okCount;

            let html = `<div class="alert ${failCount === 0 ? 'alert-success' : 'alert-warning'}">
                <strong>${submitted} jobs processed</strong> — ${okCount} submitted successfully, ${failCount} failed.
            </div>`;

            if (results.length > 0) {
                html += '<table class="table table-sm"><thead><tr><th>Job</th><th>Google</th><th>Bing</th></tr></thead><tbody>';
                results.forEach(r => {
                    const g = r.google?.success ? '✅' : (r.google ? '❌' : '⏭');
                    const b = r.bing?.success   ? '✅' : '❌';
                    html += `<tr><td>${r.title}</td><td>${g}</td><td>${b}</td></tr>`;
                });
                html += '</tbody></table>';
            }

            resultDiv.innerHTML = html;
            loadIndexingStats();
            toast(`${okCount} jobs submitted to indexing!`, 'success');
        } catch (e) {
            resultDiv.innerHTML = `<div class="alert alert-danger">${e.message || 'Indexing failed'}</div>`;
            toast('Indexing failed: ' + (e.message || 'Unknown error'), 'error');
        }
    }

    // Load stats on init
    document.addEventListener('DOMContentLoaded', () => {
        loadPosterFilter();
        loadJobs(1);
        loadIndexingStats(); // ← add this
    });
</script>