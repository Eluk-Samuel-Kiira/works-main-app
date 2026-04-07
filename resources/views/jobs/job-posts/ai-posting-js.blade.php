<script>
// ============================================================
// CONFIG
// ============================================================
const API_BASE = '/api/v1/job-posts';
const AI_API_BASE = '/ai';  // Changed to web route
const CSRF_TOKEN = document.querySelector('meta[name="csrf-token"]')?.content ?? '';
let extractedData = null;
let imageBase64 = null;

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

function bsModal(id) {
    return bootstrap.Modal.getOrCreateInstance(document.getElementById(id));
}

function toast(msg, type = 'success') {
    if (typeof showToast === 'function') showToast(type, msg);
    else {
        const alert = `<div class="alert alert-${type} alert-dismissible fade show position-fixed top-0 end-0 m-3" style="z-index:9999" role="alert">${msg}<button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>`;
        document.body.insertAdjacentHTML('beforeend', alert);
        setTimeout(() => document.querySelector('.alert')?.remove(), 3000);
    }
}

function showBanner(text) {
    const b = document.getElementById('aiBanner');
    if (b) {
        document.getElementById('aiBannerText').textContent = text;
        b.classList.remove('d-none');
        b.classList.add('d-flex');
    }
}

function hideBanner() {
    const b = document.getElementById('aiBanner');
    if (b) {
        b.classList.add('d-none');
        b.classList.remove('d-flex');
    }
}

// ============================================================
// TYPABLE DROPDOWN (same as before)
// ============================================================
class TypableDropdown {
    constructor(config) {
        this.inputEl = document.getElementById(config.inputId);
        this.hiddenEl = document.getElementById(config.hiddenId);
        this.listEl = document.getElementById(config.listId);
        this.items = [];
        this.displayKey = config.displayKey || 'name';
        this.valueKey = config.valueKey || 'id';
        this.formatItem = config.formatItem || null;
        this.init();
    }
    init() {
        this.listEl.classList.add('show');
        this.listEl.style.display = 'none';
        this.inputEl.addEventListener('input', () => this.filter());
        this.inputEl.addEventListener('focus', () => this.show());
        this.inputEl.addEventListener('blur', () => setTimeout(() => this.hide(), 200));
        document.addEventListener('click', (e) => {
            if (!this.inputEl.contains(e.target) && !this.listEl.contains(e.target)) this.hide();
        });
    }
    setItems(items) { this.items = items; this.render(); }
    filter() {
        const term = this.inputEl.value.toLowerCase();
        const filtered = this.items.filter(i => this.getText(i).toLowerCase().includes(term));
        this.render(filtered);
        this.show();
    }
    getText(item) {
        if (this.formatItem) return this.formatItem(item);
        return item[this.displayKey] || '';
    }
    render(items = null) {
        const list = items || this.items;
        this.listEl.innerHTML = '';
        if (!list.length) {
            const li = document.createElement('li');
            li.className = 'dropdown-item text-muted';
            li.textContent = 'No results found';
            li.style.cursor = 'default';
            this.listEl.appendChild(li);
        } else {
            list.forEach(item => {
                const li = document.createElement('li');
                li.className = 'dropdown-item';
                li.textContent = this.getText(item);
                li.style.cursor = 'pointer';
                li.addEventListener('click', () => this.select(item));
                this.listEl.appendChild(li);
            });
        }
    }
    select(item) {
        this.inputEl.value = this.getText(item);
        this.hiddenEl.value = item[this.valueKey];
        this.hide();
        this.hiddenEl.dispatchEvent(new Event('change', { bubbles: true }));
    }
    show() { if (this.items.length > 0) { this.listEl.style.display = 'block'; this.listEl.classList.add('show'); } }
    hide() { this.listEl.style.display = 'none'; this.listEl.classList.remove('show'); }
    getValue() { return this.hiddenEl.value; }
    setValue(id, label = null) {
        const found = this.items.find(i => String(i[this.valueKey]) === String(id));
        if (found) this.select(found);
        else if (label) { this.inputEl.value = label; this.hiddenEl.value = id; }
    }
    setByName(name) {
        const found = this.items.find(i => this.getText(i).toLowerCase().includes(name.toLowerCase()));
        if (found) this.select(found);
    }
    reset() { this.inputEl.value = ''; this.hiddenEl.value = ''; this.render(); this.hide(); }
}

// ============================================================
// DROPDOWN INSTANCES
// ============================================================
const drops = {};

async function loadDropdowns() {
    const configs = {
        company: { url: '/api/v1/companies?per_page=500', inputId: 'f_company_input', hiddenId: 'f_company_id', listId: 'f_company_list', displayKey: 'name' },
        category: { url: '/api/v1/job-categories?per_page=200', inputId: 'f_category_input', hiddenId: 'f_category_id', listId: 'f_category_list', displayKey: 'name' },
        industry: { url: '/api/v1/industries?per_page=200', inputId: 'f_industry_input', hiddenId: 'f_industry_id', listId: 'f_industry_list', displayKey: 'name' },
        jobtype: { url: '/api/v1/job-types?per_page=200', inputId: 'f_jobtype_input', hiddenId: 'f_jobtype_id', listId: 'f_jobtype_list', displayKey: 'name' },
        salaryrange: { url: '/api/v1/salary-ranges?per_page=100', inputId: 'f_salaryrange_input', hiddenId: 'f_salaryrange_id', listId: 'f_salaryrange_list', displayKey: 'name' },
        experience: { url: '/api/v1/experience-levels?per_page=100', inputId: 'f_experience_input', hiddenId: 'f_experience_id', listId: 'f_experience_list', displayKey: 'name' },
        education: { url: '/api/v1/education-levels?per_page=100', inputId: 'f_education_input', hiddenId: 'f_education_id', listId: 'f_education_list', displayKey: 'name' },
        location: {
            url: '/api/v1/job-locations?per_page=200',
            inputId: 'f_location_input', hiddenId: 'f_location_id', listId: 'f_location_list',
            formatItem: i => [i.district, i.country].filter(Boolean).join(', ')
        },
    };

    for (const [key, cfg] of Object.entries(configs)) {
        try {
            const res = await apiFetch(cfg.url);
            drops[key] = new TypableDropdown(cfg);
            drops[key].setItems(res.data ?? []);
        } catch (e) {
            console.error('Dropdown load failed:', key, e);
        }
    }
}

// ============================================================
// SEO TOGGLE
// ============================================================
function toggleSeo() {
    const body = document.getElementById('seoBody');
    const chevron = document.getElementById('seoChevron');
    if (!body || !chevron) return;
    const visible = body.style.display !== 'none';
    body.style.display = visible ? 'none' : 'block';
    chevron.className = visible ? 'ti ti-chevron-down' : 'ti ti-chevron-up';
}

// ============================================================
// CHAR COUNTERS
// ============================================================
function initCharCounters() {
    const map = {
        'f_meta_title': 'metaTitleCount',
        'f_meta_description': 'metaDescCount',
    };
    Object.entries(map).forEach(([fieldId, countId]) => {
        const field = document.getElementById(fieldId);
        const count = document.getElementById(countId);
        if (field && count) {
            field.addEventListener('input', () => {
                count.textContent = `${field.value.length}/${field.maxLength}`;
            });
        }
    });
}

// ============================================================
// SOURCE TYPE TOGGLE (text/url)
// ============================================================
document.addEventListener('change', function(e) {
    if (e.target.name === 'sourceType') {
        const textPanel = document.getElementById('textSourcePanel');
        const urlPanel = document.getElementById('urlSourcePanel');
        if (textPanel) textPanel.style.display = e.target.value === 'text' ? '' : 'none';
        if (urlPanel) urlPanel.style.display = e.target.value === 'url' ? '' : 'none';
    }
});

// ============================================================
// MODEL SELECTION
// ============================================================
function selectModel(el, modelId) {
    document.querySelectorAll('.model-card').forEach(c => {
        c.classList.remove('border-primary', 'bg-primary-subtle');
    });
    el.classList.add('border-primary', 'bg-primary-subtle');
    document.getElementById('selectedModel').value = modelId;
}

// ============================================================
// EXTRACT JOB DATA VIA WEB ROUTE
// ============================================================
async function extractJobData() {
    const model = document.getElementById('selectedModel').value;
    const sourceType = document.querySelector('input[name="sourceType"]:checked').value;
    let content = '';

    if (sourceType === 'text') {
        content = document.getElementById('aiSourceText').value.trim();
        if (!content) { toast('Please paste some job content first.', 'error'); return; }
    } else {
        const url = document.getElementById('aiSourceUrl').value.trim();
        if (!url) { toast('Please enter a job URL.', 'error'); return; }
        content = url;
    }

    const btn = document.getElementById('extractBtn');
    const spinner = document.getElementById('extractBtnSpinner');
    const preview = document.getElementById('aiPreviewPanel');

    btn.disabled = true;
    spinner.classList.remove('d-none');
    if (preview) {
        preview.innerHTML = `
            <div class="text-center py-5">
                <div class="spinner-border text-primary mb-3"></div>
                <p class="text-muted">AI is extracting job data...</p>
            </div>`;
    }

    try {
        const result = await apiFetch(`${AI_API_BASE}/extract-job`, {
            method: 'POST',
            body: JSON.stringify({ model, content, source_type: sourceType })
        });
        
        extractedData = result.data;
        renderExtractedPreview(result.data);
        const applyBtn = document.getElementById('applyExtractedBtn');
        if (applyBtn) applyBtn.style.display = '';
        const tokenInfo = document.getElementById('aiTokenInfo');
        if (tokenInfo) tokenInfo.textContent = `${model.toUpperCase()} — extraction complete`;

        if (document.getElementById('autoApplyToggle')?.checked) {
            applyExtractedData();
            bsModal('aiExtractModal').hide();
            toast('Job data extracted and applied to form!', 'success');
        }
    } catch (e) {
        if (preview) {
            preview.innerHTML = `
                <div class="alert alert-danger">
                    <i class="ti ti-alert-circle me-2"></i>
                    <strong>Extraction failed:</strong> ${e.message || 'Unknown error'}
                </div>`;
        }
        toast('AI extraction failed: ' + (e.message || 'Unknown error'), 'error');
    } finally {
        btn.disabled = false;
        spinner.classList.add('d-none');
    }
}

// ============================================================
// RENDER EXTRACTED PREVIEW
// ============================================================
function renderExtractedPreview(data) {
    const panel = document.getElementById('aiPreviewPanel');
    if (!panel) return;
    
    const fields = [
        { key: 'job_title', label: 'Job Title', icon: 'ti-briefcase' },
        { key: 'company_name', label: 'Company', icon: 'ti-building' },
        { key: 'employment_type', label: 'Employment Type', icon: 'ti-clock' },
        { key: 'location_type', label: 'Location Type', icon: 'ti-map-pin' },
        { key: 'duty_station', label: 'Duty Station', icon: 'ti-map' },
        { key: 'deadline', label: 'Deadline', icon: 'ti-calendar' },
        { key: 'salary_amount', label: 'Salary', icon: 'ti-coin' },
        { key: 'currency', label: 'Currency', icon: 'ti-currency-dollar' },
        { key: 'payment_period', label: 'Pay Period', icon: 'ti-repeat' },
        { key: 'email', label: 'Email', icon: 'ti-mail' },
        { key: 'telephone', label: 'Phone', icon: 'ti-phone' },
        { key: 'application_procedure', label: 'How to Apply', icon: 'ti-send' },
        { key: 'experience_level_name', label: 'Experience Level', icon: 'ti-star' },
        { key: 'education_level_name', label: 'Education Level', icon: 'ti-school' },
        { key: 'industry_name', label: 'Industry', icon: 'ti-building-factory' },
        { key: 'category_name', label: 'Category', icon: 'ti-category' },
        { key: 'skills', label: 'Skills', icon: 'ti-tools' },
    ];

    let html = '<div class="d-flex flex-column gap-2">';
    fields.forEach(f => {
        const val = data[f.key];
        if (!val) return;
        html += `
            <div class="d-flex gap-2 p-2 bg-body rounded-2">
                <i class="ti ${f.icon} text-primary flex-shrink-0 mt-1" style="font-size:14px"></i>
                <div class="min-w-0">
                    <div class="text-muted" style="font-size:11px">${f.label}</div>
                    <div class="fw-semibold text-truncate" style="font-size:13px">${String(val).substring(0, 120)}${String(val).length > 120 ? '...' : ''}</div>
                </div>
            </div>`;
    });

    if (data.job_description) {
        const stripped = data.job_description.replace(/<[^>]*>/g, '').substring(0, 200);
        html += `
            <div class="d-flex gap-2 p-2 bg-body rounded-2">
                <i class="ti ti-file-description text-primary flex-shrink-0 mt-1" style="font-size:14px"></i>
                <div>
                    <div class="text-muted" style="font-size:11px">Description</div>
                    <div style="font-size:13px">${stripped}...</div>
                </div>
            </div>`;
    }
    html += '</div>';
    panel.innerHTML = html;
}

// ============================================================
// APPLY EXTRACTED DATA TO FORM
// ============================================================
function applyExtractedData() {
    if (!extractedData) return;
    const d = extractedData;

    const fieldMap = {
        'job_title': 'f_job_title',
        'duty_station': 'f_duty_station',
        'application_procedure': 'f_application_procedure',
        'email': 'f_email',
        'telephone': 'f_telephone',
        'salary_amount': 'f_salary_amount',
        'currency': 'f_currency',
        'meta_description': 'f_meta_description',
        'keywords': 'f_keywords',
        'work_hours': 'f_work_hours',
    };

    Object.entries(fieldMap).forEach(([dataKey, fieldId]) => {
        if (d[dataKey]) {
            const el = document.getElementById(fieldId);
            if (el) el.value = d[dataKey];
        }
    });

    if (d.deadline) {
        const el = document.getElementById('f_deadline');
        if (el) {
            const parts = d.deadline.split('-');
            if (parts.length === 3) el.value = `${parts[1]}/${parts[2]}/${parts[0]}`;
        }
    }

    if (d.employment_type) {
        const el = document.getElementById('f_employment_type');
        if (el) el.value = d.employment_type;
    }
    if (d.location_type) {
        const el = document.getElementById('f_location_type');
        if (el) el.value = d.location_type;
    }

    const richMap = {
        'job_description': 'f_job_description_editor',
        'responsibilities': 'f_responsibilities_editor',
        'qualifications': 'f_qualifications_editor',
        'skills': 'f_skills_editor',
    };
    Object.entries(richMap).forEach(([dataKey, editorId]) => {
        if (d[dataKey]) richEditorSet(editorId, d[dataKey]);
    });

    if (d.company_name && drops.company) drops.company.setByName(d.company_name);
    if (d.category_name && drops.category) drops.category.setByName(d.category_name);
    if (d.industry_name && drops.industry) drops.industry.setByName(d.industry_name);
    if (d.experience_level_name && drops.experience) drops.experience.setByName(d.experience_level_name);
    if (d.education_level_name && drops.education) drops.education.setByName(d.education_level_name);
    if (d.is_urgent) document.getElementById('f_urgent').checked = true;

    toast('Data applied to form. Please review and confirm dropdown selections.', 'success');
    bsModal('aiExtractModal').hide();
    document.getElementById('f_job_title')?.scrollIntoView({ behavior: 'smooth', block: 'center' });
    document.getElementById('f_job_title')?.focus();
}

// ============================================================
// IMAGE EXTRACTION VIA WEB ROUTE
// ============================================================
function openImageExtractModal() { bsModal('imageExtractModal').show(); }

function handleImgDrop(e) {
    e.preventDefault();
    const file = e.dataTransfer.files[0];
    if (file) processImageFile(file);
}

function handleImgSelect(e) {
    const file = e.target.files[0];
    if (file) processImageFile(file);
}

function processImageFile(file) {
    if (file.size > 5 * 1024 * 1024) { toast('Image too large. Max 5MB.', 'error'); return; }
    const reader = new FileReader();
    reader.onload = (e) => {
        const dataUrl = e.target.result;
        imageBase64 = dataUrl.split(',')[1];
        const preview = document.getElementById('imgPreview');
        if (preview) preview.src = dataUrl;
        const dropZone = document.getElementById('imgDropZone');
        const previewWrap = document.getElementById('imgPreviewWrap');
        if (dropZone) dropZone.style.display = 'none';
        if (previewWrap) previewWrap.style.display = '';
    };
    reader.readAsDataURL(file);
}

function clearImage() {
    imageBase64 = null;
    document.getElementById('imgFileInput').value = '';
    const dropZone = document.getElementById('imgDropZone');
    const previewWrap = document.getElementById('imgPreviewWrap');
    if (dropZone) dropZone.style.display = '';
    if (previewWrap) previewWrap.style.display = 'none';
}

async function extractFromImage() {
    if (!imageBase64) { toast('Please upload an image first.', 'error'); return; }
    const model = document.getElementById('imgModel').value;

    const btn = document.getElementById('imgExtractBtn');
    const spinner = document.getElementById('imgExtractBtnSpinner');
    const preview = document.getElementById('imgPreviewPanel');

    btn.disabled = true;
    spinner.classList.remove('d-none');
    if (preview) {
        preview.innerHTML = `<div class="text-center py-5"><div class="spinner-border text-primary mb-3"></div><p class="text-muted">Analyzing image...</p></div>`;
    }

    try {
        const result = await apiFetch(`${AI_API_BASE}/extract-image`, {
            method: 'POST',
            body: JSON.stringify({ model, image_base64: imageBase64 })
        });
        
        extractedData = result.data;
        renderImagePreview(result.data);
        const applyBtn = document.getElementById('applyImgBtn');
        if (applyBtn) applyBtn.style.display = '';
        toast('Image analyzed successfully!', 'success');
    } catch (e) {
        if (preview) {
            preview.innerHTML = `<div class="alert alert-danger"><i class="ti ti-alert-circle me-2"></i>${e.message || 'Extraction failed'}</div>`;
        }
        toast('Image extraction failed: ' + (e.message || 'Unknown error'), 'error');
    } finally {
        btn.disabled = false;
        spinner.classList.add('d-none');
    }
}

function renderImagePreview(data) {
    renderExtractedPreview(data);
    const sourcePanel = document.getElementById('aiPreviewPanel');
    const targetPanel = document.getElementById('imgPreviewPanel');
    if (sourcePanel && targetPanel) {
        targetPanel.innerHTML = sourcePanel.innerHTML;
    }
}

function applyImageData() {
    applyExtractedData();
    bsModal('imageExtractModal').hide();
}

// ============================================================
// AI ENHANCE FIELD VIA WEB ROUTE
// ============================================================
async function aiEnhanceField(fieldName, instruction) {
    const editorMap = {
        'job_description': 'f_job_description_editor',
        'responsibilities': 'f_responsibilities_editor',
        'qualifications': 'f_qualifications_editor',
        'skills': 'f_skills_editor',
        'application_procedure': null,
    };

    let currentContent = '';
    if (editorMap[fieldName]) {
        const editor = document.getElementById(editorMap[fieldName]);
        currentContent = editor ? editor.innerHTML : '';
    } else {
        const el = document.getElementById(`f_${fieldName}`);
        currentContent = el ? el.value : '';
    }

    if (!currentContent.trim() || currentContent === '<br>') {
        toast('Please add some content first before enhancing.', 'warning');
        return;
    }

    const model = document.getElementById('selectedModel')?.value || 'claude';
    showBanner(`AI is enhancing ${fieldName.replace(/_/g, ' ')}...`);

    try {
        const stripped = currentContent.replace(/<[^>]*>/g, '');
        const result = await apiFetch(`${AI_API_BASE}/enhance-field`, {
            method: 'POST',
            body: JSON.stringify({ model, field_name: fieldName, content: stripped, instruction })
        });
        
        let enhanced = result.enhanced;
        enhanced = enhanced.replace(/```html\n?/g, '').replace(/```\n?/g, '').trim();

        if (editorMap[fieldName]) {
            richEditorSet(editorMap[fieldName], enhanced);
        } else {
            const el = document.getElementById(`f_${fieldName}`);
            if (el) el.value = enhanced.replace(/<[^>]*>/g, '');
        }
        toast(`${fieldName.replace(/_/g, ' ')} enhanced successfully!`, 'success');
    } catch (e) {
        toast('Enhancement failed: ' + (e.message || 'Unknown error'), 'error');
    } finally {
        hideBanner();
    }
}

// ============================================================
// GENERATE FULL POST FROM TITLE VIA WEB ROUTE
// ============================================================
async function aiGenerateFullPost() {
    const title = document.getElementById('f_job_title').value.trim();
    const company = document.getElementById('f_company_input')?.value?.trim() || '';

    if (!title) {
        toast('Please enter a job title first.', 'warning');
        document.getElementById('f_job_title').focus();
        return;
    }

    const model = document.getElementById('selectedModel')?.value || 'claude';
    showBanner('AI is generating full job post...');

    try {
        const result = await apiFetch(`${AI_API_BASE}/generate-from-title`, {
            method: 'POST',
            body: JSON.stringify({ model, title, company })
        });
        
        const data = result.data;
        if (data.job_description) richEditorSet('f_job_description_editor', data.job_description);
        if (data.responsibilities) richEditorSet('f_responsibilities_editor', data.responsibilities);
        if (data.qualifications) richEditorSet('f_qualifications_editor', data.qualifications);
        if (data.skills) richEditorSet('f_skills_editor', data.skills);
        if (data.meta_description) document.getElementById('f_meta_description').value = data.meta_description;
        if (data.keywords) document.getElementById('f_keywords').value = data.keywords;

        toast('Job post generated! Review and adjust as needed.', 'success');
    } catch (e) {
        toast('Generation failed: ' + (e.message || 'Unknown error'), 'error');
    } finally {
        hideBanner();
    }
}

// ============================================================
// RICH EDITOR HELPERS
// ============================================================
function richEditorSet(editorId, html) {
    const el = document.getElementById(editorId);
    if (el) el.innerHTML = html;
}

function richEditorGet(editorId) {
    const el = document.getElementById(editorId);
    return el ? el.innerHTML : '';
}

function richEditorSync() {
    const syncMap = {
        'f_job_description_editor': 'f_job_description',
        'f_responsibilities_editor': 'f_responsibilities',
        'f_qualifications_editor': 'f_qualifications',
        'f_skills_editor': 'f_skills',
    };
    Object.entries(syncMap).forEach(([editorId, hiddenId]) => {
        const hidden = document.getElementById(hiddenId);
        if (hidden) hidden.value = richEditorGet(editorId);
    });
}

// ============================================================
// FORM SUBMISSION
// ============================================================
async function submitJobPost(mode = 'live') {
    richEditorSync();

    const form = document.getElementById('aiJobForm');
    const data = {};
    new FormData(form).forEach((v, k) => data[k] = v);

    const bools = [
        'is_resume_required', 'is_cover_letter_required', 'is_academic_documents_required',
        'is_application_required', 'is_whatsapp_contact', 'is_telephone_call',
        'is_featured', 'is_urgent', 'is_quick_gig', 'is_verified', 'is_simple_job',
    ];
    bools.forEach(k => { data[k] = data[k] === 'on'; });

    if (mode === 'draft') data.is_active = false;

    if (data.deadline) {
        const parts = data.deadline.split('/');
        if (parts.length === 3) data.deadline = `${parts[2]}-${parts[0].padStart(2, '0')}-${parts[1].padStart(2, '0')}`;
    }

    const errors = [];
    if (!data.job_title) errors.push('Job title is required');
    if (!data.company_id) errors.push('Company is required');
    if (!data.job_category_id) errors.push('Category is required');
    if (!data.industry_id) errors.push('Industry is required');
    if (!data.job_location_id) errors.push('Location is required');
    if (!data.job_type_id) errors.push('Job type is required');
    if (!data.experience_level_id) errors.push('Experience level is required');
    if (!data.education_level_id) errors.push('Education level is required');
    if (!data.deadline) errors.push('Application deadline is required');
    if (!data.job_description) errors.push('Job description is required');

    if (errors.length) {
        const errorDiv = document.getElementById('formErrors');
        if (errorDiv) {
            errorDiv.innerHTML = `
                <div class="alert alert-danger">
                    <strong><i class="ti ti-alert-circle me-2"></i>Please fix:</strong>
                    <ul class="mb-0 mt-2">${errors.map(e => `<li>${e}</li>`).join('')}</ul>
                </div>`;
            errorDiv.scrollIntoView({ behavior: 'smooth' });
        }
        return;
    }

    showBanner('Submitting job post...');

    try {
        const res = await apiFetch(API_BASE, { method: 'POST', body: JSON.stringify(data) });
        hideBanner();
        toast('Job post created successfully!', 'success');
        const errorDiv = document.getElementById('formErrors');
        if (errorDiv) {
            errorDiv.innerHTML = `
                <div class="alert alert-success">
                    <i class="ti ti-check me-2"></i>
                    <strong>Job posted!</strong>
                    <a href="/job-posts/${res.data?.slug || ''}" class="alert-link ms-2" target="_blank">
                        View job <i class="ti ti-external-link ms-1"></i>
                    </a>
                </div>`;
        }
        clearForm();
    } catch (err) {
        hideBanner();
        const msgs = err.errors ? Object.values(err.errors).flat().map(m => `<li>${m}</li>`).join('') : (err.message || 'Submission failed');
        const errorDiv = document.getElementById('formErrors');
        if (errorDiv) {
            errorDiv.innerHTML = `
                <div class="alert alert-danger">
                    <strong><i class="ti ti-alert-circle me-2"></i>Error:</strong>
                    <ul class="mb-0 mt-2">${msgs}</ul>
                </div>`;
            errorDiv.scrollIntoView({ behavior: 'smooth' });
        }
        toast(err.message || 'Failed to post job.', 'error');
    }
}

// ============================================================
// CLEAR FORM
// ============================================================
function clearForm() {
    document.getElementById('aiJobForm')?.reset();
    const editors = ['f_job_description_editor', 'f_responsibilities_editor', 'f_qualifications_editor', 'f_skills_editor'];
    editors.forEach(id => { const el = document.getElementById(id); if (el) el.innerHTML = ''; });
    Object.values(drops).forEach(d => d?.reset());
    const errorDiv = document.getElementById('formErrors');
    if (errorDiv) errorDiv.innerHTML = '';
    extractedData = null;
    hideBanner();
}

// ============================================================
// OPEN MODALS
// ============================================================
function openAiExtractModal() { bsModal('aiExtractModal').show(); }

// ============================================================
// INIT
// ============================================================
document.addEventListener('DOMContentLoaded', () => {
    loadDropdowns();
    initCharCounters();

    if (typeof $ !== 'undefined' && $.fn.datepicker) {
        $('.datepicker-autoclose').datepicker({ autoclose: true, todayHighlight: true, format: 'mm/dd/yyyy' });
    }
});
</script>