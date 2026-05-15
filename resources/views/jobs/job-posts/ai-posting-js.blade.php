{{-- ============================================================
     AI JOB POSTING — JavaScript  (ai-posting-js.blade.php)
     ============================================================ --}}
<script>
// ============================================================
// CONFIG
// ============================================================
const API_BASE    = '/api/v1/job-posts';
const AI_API_BASE = '/ai';
const CSRF_TOKEN  = document.querySelector('meta[name="csrf-token"]')?.content ?? '';
let extractedData = null;

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
    if (typeof showToast === 'function') {
        showToast(type, msg);
    } else {
        const el = document.createElement('div');
        el.className = `alert alert-${type} alert-dismissible fade show position-fixed top-0 end-0 m-3`;
        el.style.zIndex = 9999;
        el.innerHTML = `${msg}<button type="button" class="btn-close" data-bs-dismiss="alert"></button>`;
        document.body.appendChild(el);
        setTimeout(() => el.remove(), 5000);
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
    if (b) { b.classList.add('d-none'); b.classList.remove('d-flex'); }
}

function escapeHtml(str) {
    if (!str) return '';
    return String(str)
        .replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;')
        .replace(/"/g, '&quot;').replace(/'/g, '&#39;').replace(/\n/g, ' ');
}

// ============================================================
// TYPABLE DROPDOWN
// ============================================================
class TypableDropdown {
    constructor(config) {
        this.inputEl    = document.getElementById(config.inputId);
        this.hiddenEl   = document.getElementById(config.hiddenId);
        this.listEl     = document.getElementById(config.listId);
        this.items      = [];
        this.displayKey = config.displayKey || 'name';
        this.valueKey   = config.valueKey   || 'id';
        this.formatItem = config.formatItem || null;
        this.init();
    }

    init() {
        this.listEl.style.display = 'none';
        this.inputEl.addEventListener('input', () => this.filter());
        this.inputEl.addEventListener('focus', () => this.show());
        this.inputEl.addEventListener('blur',  () => setTimeout(() => this.hide(), 200));
        document.addEventListener('click', (e) => {
            if (!this.inputEl.contains(e.target) && !this.listEl.contains(e.target)) this.hide();
        });
    }

    setItems(items) { this.items = items; this.render(); }

    filter() {
        const term     = this.inputEl.value.toLowerCase();
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
        this.inputEl.value  = this.getText(item);
        this.hiddenEl.value = item[this.valueKey];
        this.hide();
        this.hiddenEl.dispatchEvent(new Event('change', { bubbles: true }));
    }

    show() {
        if (this.items.length > 0) {
            this.listEl.style.display = 'block';
            this.listEl.classList.add('show');
        }
    }

    hide() {
        this.listEl.style.display = 'none';
        this.listEl.classList.remove('show');
    }

    getValue()  { return this.hiddenEl.value; }
    getLabel()  { return this.inputEl.value; }

    /**
     * Fuzzy match: exact → starts-with → contains → reverse-contains → first-word
     * fallbackToFirst: if nothing matches, select the first item in the list
     * Returns true if something was selected.
     */
    setByName(name, fallbackToFirst = false) {
        if (!name || !this.items.length) {
            if (fallbackToFirst && this.items[0]) { this.select(this.items[0]); return true; }
            return false;
        }

        const n = String(name).toLowerCase().trim();

        // 1. Exact
        let found = this.items.find(i => this.getText(i).toLowerCase() === n);
        // 2. Starts with
        if (!found) found = this.items.find(i => this.getText(i).toLowerCase().startsWith(n));
        // 3. Item text contains search
        if (!found) found = this.items.find(i => this.getText(i).toLowerCase().includes(n));
        // 4. Search contains item text  (e.g. "entry level position" → "Entry Level")
        if (!found) found = this.items.find(i => n.includes(this.getText(i).toLowerCase()));
        // 5. First word of search matches start of item
        if (!found) {
            const firstWord = n.split(/\s+/)[0];
            if (firstWord.length > 2) {
                found = this.items.find(i => this.getText(i).toLowerCase().startsWith(firstWord));
            }
        }

        if (found) { this.select(found); return true; }

        if (fallbackToFirst && this.items[0]) { this.select(this.items[0]); return true; }

        return false;
    }

    selectFirst() { if (this.items[0]) this.select(this.items[0]); }

    reset() {
        this.inputEl.value  = '';
        this.hiddenEl.value = '';
        this.render();
        this.hide();
    }
}

// ============================================================
// DROPDOWN INSTANCES
// ============================================================
const drops = {};

async function loadDropdowns() {
    const configs = {
        company:     { url: '/api/v1/companies?per_page=500',         inputId: 'f_company_input',     hiddenId: 'f_company_id',     listId: 'f_company_list',     displayKey: 'name' },
        category:    { url: '/api/v1/job-categories?per_page=200',    inputId: 'f_category_input',    hiddenId: 'f_category_id',    listId: 'f_category_list',    displayKey: 'name' },
        industry:    { url: '/api/v1/industries?per_page=200',        inputId: 'f_industry_input',    hiddenId: 'f_industry_id',    listId: 'f_industry_list',    displayKey: 'name' },
        jobtype:     { url: '/api/v1/job-types?per_page=200',         inputId: 'f_jobtype_input',     hiddenId: 'f_jobtype_id',     listId: 'f_jobtype_list',     displayKey: 'name' },
        salaryrange: { url: '/api/v1/salary-ranges?per_page=100',     inputId: 'f_salaryrange_input', hiddenId: 'f_salaryrange_id', listId: 'f_salaryrange_list', displayKey: 'name' },
        experience:  { url: '/api/v1/experience-levels?per_page=100', inputId: 'f_experience_input',  hiddenId: 'f_experience_id',  listId: 'f_experience_list',  displayKey: 'name' },
        education:   { url: '/api/v1/education-levels?per_page=100',  inputId: 'f_education_input',   hiddenId: 'f_education_id',   listId: 'f_education_list',   displayKey: 'name' },
        location: {
            url: '/api/v1/job-locations?per_page=200',
            inputId: 'f_location_input', hiddenId: 'f_location_id', listId: 'f_location_list',
            formatItem: i => [i.district, i.country].filter(Boolean).join(', '),
        },
    };

    for (const [key, cfg] of Object.entries(configs)) {
        try {
            const res  = await apiFetch(cfg.url);
            drops[key] = new TypableDropdown(cfg);
            drops[key].setItems(res.data ?? []);
        } catch (e) {
            console.error('Dropdown load failed:', key, e);
        }
    }
}

// ============================================================
// AUTO-SELECT DROPDOWNS  ← the key function
// Called after every extraction. Uses fuzzy matching with
// smart fallbacks so required IDs are always populated.
// ============================================================
function autoSelectDropdowns(d) {

    // JOB TYPE — match employment_type text, fallback = first item (e.g. Full Time)
    if (drops.jobtype) {
        const matched = d.employment_type
            ? drops.jobtype.setByName(d.employment_type, true)
            : false;
        if (!matched) drops.jobtype.selectFirst();
    }

    // EXPERIENCE LEVEL — fallback = first item (entry level)
    if (drops.experience) {
        const matched = d.experience_level_name
            ? drops.experience.setByName(d.experience_level_name, true)
            : false;
        if (!matched) drops.experience.selectFirst();
    }

    // EDUCATION LEVEL — fallback = first item (Certificate)
    if (drops.education) {
        const matched = d.education_level_name
            ? drops.education.setByName(d.education_level_name, true)
            : false;
        if (!matched) drops.education.selectFirst();
    }

    // COMPANY — no fallback, must be an actual match
    if (drops.company && d.company_name) {
        drops.company.setByName(d.company_name, false);
    }

    // CATEGORY — no fallback
    if (drops.category && d.category_name) {
        drops.category.setByName(d.category_name, false);
    }

    // INDUSTRY — no fallback
    if (drops.industry && d.industry_name) {
        drops.industry.setByName(d.industry_name, false);
    }

    // LOCATION — try duty_station text, no fallback
    if (drops.location && d.duty_station) {
        drops.location.setByName(d.duty_station, false);
    }

    // SALARY RANGE — optional
    if (drops.salaryrange && d.salary_range_name) {
        drops.salaryrange.setByName(d.salary_range_name, false);
    }
}

/**
 * After applying data, show which required dropdowns were
 * filled vs which still need manual attention.
 */
function showDropdownStatus() {
    const required = [
        { drop: 'company',    label: 'Company'          },
        { drop: 'category',   label: 'Category'         },
        { drop: 'industry',   label: 'Industry'         },
        { drop: 'location',   label: 'Location'         },
        { drop: 'jobtype',    label: 'Job Type'         },
        { drop: 'experience', label: 'Experience Level' },
        { drop: 'education',  label: 'Education Level'  },
    ];

    const missing = required.filter(r => !drops[r.drop]?.getValue());
    const filled  = required.length - missing.length;

    if (missing.length === 0) {
        toast(`✓ All ${filled} required fields filled — review and submit!`, 'success');
    } else {
        const names = missing.map(r => `<strong>${r.label}</strong>`).join(', ');
        toast(`AI filled ${filled}/${required.length} required fields. Please manually select: ${names}`, 'warning');
    }
}

// ============================================================
// RICH EDITOR HELPERS
// ============================================================
function richEditorSync(editorId) {
    const editor = document.getElementById(editorId);
    const hidden = document.getElementById(editorId + '_hidden');
    if (editor && hidden) {
        const content = isEditorEmpty(editor) ? '' : editor.innerHTML;
        hidden.value  = content;
        return content;
    }
    return '';
}

function richEditorGet(editorId) {
    const editor = document.getElementById(editorId);
    if (!editor || isEditorEmpty(editor)) return '';
    return editor.innerHTML;
}

function richEditorSet(editorId, html) {
    const editor = document.getElementById(editorId);
    const hidden = document.getElementById(editorId + '_hidden');
    if (editor) {
        editor.innerHTML = html || '';
        editor.dispatchEvent(new Event('input', { bubbles: true }));
        if (hidden) hidden.value = html || '';
    }
}

function isEditorEmpty(editor) {
    const c = editor.innerHTML;
    return !c || c === '<br>' || c === '<p><br></p>' || c === '<div><br></div>' || c.trim() === '';
}

function syncAllRichEditors() {
    ['f_job_description_editor','f_responsibilities_editor','f_qualifications_editor','f_skills_editor']
        .forEach(richEditorSync);
}

// ============================================================
// SEO TOGGLE
// ============================================================
function toggleSeo() {
    const body    = document.getElementById('seoBody');
    const chevron = document.getElementById('seoChevron');
    if (!body || !chevron) return;
    const visible = body.style.display !== 'none';
    body.style.display = visible ? 'none' : 'block';
    chevron.className  = visible ? 'ti ti-chevron-down' : 'ti ti-chevron-up';
}

// ============================================================
// CHAR COUNTERS
// ============================================================
function initCharCounters() {
    [['f_meta_title','metaTitleCount'],['f_meta_description','metaDescCount']].forEach(([fId, cId]) => {
        const field = document.getElementById(fId);
        const count = document.getElementById(cId);
        if (field && count) field.addEventListener('input', () => { count.textContent = `${field.value.length}/${field.maxLength}`; });
    });
}

// ============================================================
// SOURCE TYPE TOGGLE
// ============================================================
function initSourceTypeToggle() {
    function toggle() {
        const val       = document.querySelector('input[name="sourceType"]:checked')?.value;
        const textPanel = document.getElementById('textSourcePanel');
        const urlPanel  = document.getElementById('urlSourcePanel');
        if (!textPanel || !urlPanel) return;
        textPanel.style.display = val === 'url' ? 'none' : 'block';
        urlPanel.style.display  = val === 'url' ? 'block' : 'none';
    }
    document.querySelectorAll('input[name="sourceType"]').forEach(r => r.addEventListener('change', toggle));
    toggle();
}

// ============================================================
// MODEL SELECTION
// ============================================================
function selectModel(el, modelId) {
    document.querySelectorAll('.model-card').forEach(c => c.classList.remove('border-primary','bg-primary-subtle'));
    el.classList.add('border-primary','bg-primary-subtle');
    document.getElementById('selectedModel').value = modelId;
}

// ============================================================
// EXTRACT JOB DATA
// ============================================================
async function extractJobData() {
    const model      = document.getElementById('selectedModel').value;
    const sourceType = document.querySelector('input[name="sourceType"]:checked').value;
    let   content    = '';

    if (sourceType === 'text') {
        content = document.getElementById('aiSourceText')?.value?.trim();
        if (!content) { toast('Please paste some job content first.', 'warning'); return; }
    } else {
        const url = document.getElementById('aiSourceUrl')?.value?.trim();
        if (!url)  { toast('Please enter a job URL.', 'warning'); return; }
        content = url;
    }

    const btn     = document.getElementById('extractBtn');
    const spinner = document.getElementById('extractBtnSpinner');
    const preview = document.getElementById('aiPreviewPanel');

    btn.disabled = true;
    spinner.classList.remove('d-none');

    if (preview) {
        preview.innerHTML = `
            <div class="text-center py-5">
                <div class="spinner-border text-primary mb-3"></div>
                <p class="text-muted small">AI agent extracting job data…<br>Smart defaults applied for missing fields.</p>
            </div>`;
    }

    try {
        const result = await apiFetch(`${AI_API_BASE}/extract-job`, {
            method: 'POST',
            body: JSON.stringify({ model, content, source_type: sourceType }),
        });

        extractedData = result.data;
        renderExtractedPreview(result.data);

        const applyBtn  = document.getElementById('applyExtractedBtn');
        if (applyBtn) applyBtn.style.display = '';

        const tokenInfo = document.getElementById('aiTokenInfo');
        if (tokenInfo) tokenInfo.textContent = `${model.toUpperCase()} — extraction complete`;

        if (document.getElementById('autoApplyToggle')?.checked) {
            applyExtractedData();
            bsModal('aiExtractModal').hide();
        }

    } catch (e) {
        const msg = formatErrorMessage(e);
        if (preview) {
            preview.innerHTML = `<div class="alert alert-danger m-2">
                <i class="ti ti-alert-circle me-2"></i><strong>Extraction failed:</strong>
                <div class="mt-1 small">${escapeHtml(msg)}</div>
            </div>`;
        }
        toast(msg, 'danger');
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
        { key: 'job_title',             label: 'Job Title',        icon: 'ti-briefcase'        },
        { key: 'company_name',          label: 'Company',          icon: 'ti-building'         },
        { key: 'employment_type',       label: 'Employment Type',  icon: 'ti-clock'            },
        { key: 'location_type',         label: 'Location Type',    icon: 'ti-map-pin'          },
        { key: 'duty_station',          label: 'Duty Station',     icon: 'ti-map'              },
        { key: 'deadline',              label: 'Deadline',         icon: 'ti-calendar'         },
        { key: 'salary_amount',         label: 'Salary',           icon: 'ti-coin'             },
        { key: 'currency',              label: 'Currency',         icon: 'ti-currency-dollar'  },
        { key: 'payment_period',        label: 'Pay Period',       icon: 'ti-repeat'           },
        { key: 'email',                 label: 'Email',            icon: 'ti-mail'             },
        { key: 'telephone',             label: 'Phone',            icon: 'ti-phone'            },
        { key: 'application_procedure', label: 'How to Apply',     icon: 'ti-send'             },
        { key: 'experience_level_name', label: 'Experience Level', icon: 'ti-star'             },
        { key: 'education_level_name',  label: 'Education Level',  icon: 'ti-school'           },
        { key: 'industry_name',         label: 'Industry',         icon: 'ti-building-factory' },
        { key: 'category_name',         label: 'Category',         icon: 'ti-category'         },
        { key: 'skills',                label: 'Skills',           icon: 'ti-tools'            },
    ];

    const aiDefaulted = new Set(['employment_type','experience_level_name','education_level_name','deadline']);

    let html = `
        <div class="alert alert-success py-2 mb-2 d-flex align-items-center gap-2 small">
            <i class="ti ti-robot flex-shrink-0"></i>
            <div>AI applied smart defaults where fields were missing.
            <span class="badge bg-warning-subtle text-warning border">default</span> = auto-filled.</div>
        </div>
        <div class="d-flex flex-column gap-2">`;

    fields.forEach(f => {
        const val = data[f.key];
        if (val === null || val === undefined || val === '') return;
        const badge = aiDefaulted.has(f.key)
            ? `<span class="badge bg-warning-subtle text-warning ms-1" style="font-size:10px">default</span>`
            : '';
        html += `
            <div class="d-flex gap-2 p-2 bg-body rounded-2 align-items-start">
                <i class="ti ${f.icon} text-primary flex-shrink-0 mt-1" style="font-size:14px"></i>
                <div class="flex-grow-1 min-w-0">
                    <div class="text-muted small mb-1">${escapeHtml(f.label)}${badge}</div>
                    <div class="fw-semibold" style="font-size:13px;word-break:break-word;white-space:normal">
                        ${escapeHtml(String(val))}
                    </div>
                </div>
            </div>`;
    });

    if (data.job_description) {
        const text    = data.job_description.replace(/<[^>]*>/g, ' ').replace(/\s+/g, ' ').trim();
        const snippet = text.length > 250 ? text.substring(0, text.lastIndexOf(' ', 250)) + '…' : text;
        html += `
            <div class="d-flex gap-2 p-2 bg-body rounded-2 align-items-start">
                <i class="ti ti-file-description text-primary flex-shrink-0 mt-1" style="font-size:14px"></i>
                <div class="flex-grow-1 min-w-0">
                    <div class="text-muted small mb-1">Description (preview)</div>
                    <div style="font-size:13px;word-break:break-word;white-space:normal">${escapeHtml(snippet)}</div>
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
    if (!extractedData) {
        toast('No extracted data. Please extract first.', 'warning');
        return;
    }

    const d = extractedData;

    // Plain text fields
    const fieldMap = {
        job_title:             'f_job_title',
        duty_station:          'f_duty_station',
        application_procedure: 'f_application_procedure',
        email:                 'f_email',
        telephone:             'f_telephone',
        salary_amount:         'f_salary_amount',
        currency:              'f_currency',
        meta_description:      'f_meta_description',
        keywords:              'f_keywords',
        work_hours:            'f_work_hours',
    };
    Object.entries(fieldMap).forEach(([key, id]) => {
        if (d[key] !== undefined && d[key] !== null) {
            const el = document.getElementById(id);
            if (el) el.value = d[key];
        }
    });

    // Deadline: YYYY-MM-DD → MM/DD/YYYY
    if (d.deadline) {
        const el = document.getElementById('f_deadline');
        if (el) {
            const parts = d.deadline.split('-');
            if (parts.length === 3) el.value = `${parts[1]}/${parts[2]}/${parts[0]}`;
        }
    }

    // <select> elements
    if (d.employment_type) { const el = document.getElementById('f_employment_type'); if (el) el.value = d.employment_type; }
    if (d.location_type)   { const el = document.getElementById('f_location_type');   if (el) el.value = d.location_type;   }
    if (d.payment_period)  { const el = document.getElementById('f_payment_period');  if (el) el.value = d.payment_period;  }

    // Rich text editors
    const richMap = {
        job_description:  'f_job_description_editor',
        responsibilities: 'f_responsibilities_editor',
        qualifications:   'f_qualifications_editor',
        skills:           'f_skills_editor',
    };
    Object.entries(richMap).forEach(([key, editorId]) => {
        if (d[key]) richEditorSet(editorId, d[key]);
    });

    // Typable dropdowns — fuzzy match + smart fallbacks
    autoSelectDropdowns(d);

    // Checkboxes
    const checkMap = {
        is_urgent:                      'f_urgent',
        is_featured:                    'f_featured',
        is_verified:                    'f_verified',
        is_quick_gig:                   'f_quickgig',
        is_resume_required:             'f_resume',
        is_cover_letter_required:       'f_cover',
        is_academic_documents_required: 'f_academic',
        is_application_required:        'f_appletter',
        is_whatsapp_contact:            'f_whatsapp',
        is_telephone_call:              'f_telcall',
    };
    Object.entries(checkMap).forEach(([key, id]) => {
        if (d[key] !== undefined) {
            const el = document.getElementById(id);
            if (el) el.checked = !!d[key];
        }
    });

    syncAllRichEditors();

    // Close modals
    ['aiExtractModal','imageExtractModal'].forEach(id => {
        const modal = document.getElementById(id);
        if (modal?.classList.contains('show')) bsModal(id).hide();
    });

    // Report status
    showDropdownStatus();

    // Scroll to top of form
    const titleField = document.getElementById('f_job_title');
    if (titleField) {
        titleField.scrollIntoView({ behavior: 'smooth', block: 'center' });
        titleField.style.transition = 'background 0.5s';
        titleField.style.background = '#e8f5e9';
        setTimeout(() => { titleField.style.background = ''; }, 2000);
    }
}

// ============================================================
// AI ENHANCE / FORMAT / EXTRACT / REWRITE — FIELD BUTTONS
// ============================================================
async function aiEnhanceField(fieldName, instruction) {
    const editorMap = {
        job_description:      'f_job_description_editor',
        responsibilities:     'f_responsibilities_editor',
        qualifications:       'f_qualifications_editor',
        skills:               'f_skills_editor',
        application_procedure: null,
    };

    const editorId       = editorMap[fieldName];
    let   currentContent = '';

    if (editorId) {
        currentContent = richEditorGet(editorId);
    } else {
        const el = document.getElementById(`f_${fieldName}`);
        currentContent = el ? el.value : '';
    }

    // If blank, generate from job title
    const isBlank  = !currentContent || currentContent.replace(/<[^>]*>/g, '').trim() === '';
    const jobTitle = document.getElementById('f_job_title')?.value?.trim() || '';

    if (isBlank) {
        if (!jobTitle) {
            toast('Enter a job title first so AI knows what to generate.', 'warning');
            return;
        }
        instruction    = `Generate professional ${fieldName.replace(/_/g, ' ')} content for a "${jobTitle}" role in Uganda.`;
        currentContent = `Job Title: ${jobTitle}`;
    }

    const model = document.getElementById('selectedModel')?.value || 'claude';
    const btn   = document.getElementById(`btn-enhance-${fieldName}`);
    let origHtml = '';
    if (btn) {
        origHtml     = btn.innerHTML;
        btn.disabled = true;
        btn.innerHTML = `<span class="spinner-border spinner-border-sm me-1"></span>Working…`;
    }

    showBanner(`AI is enhancing ${fieldName.replace(/_/g, ' ')}…`);

    try {
        const stripped = currentContent.replace(/<[^>]*>/g, ' ').replace(/\s+/g, ' ').trim();

        const result = await apiFetch(`${AI_API_BASE}/enhance-field`, {
            method: 'POST',
            body: JSON.stringify({ model, field_name: fieldName, content: stripped, instruction }),
        });

        let enhanced = result.enhanced || '';
        enhanced = enhanced.replace(/```html\n?|```\n?/g, '').trim();
        if (!enhanced) throw new Error('AI returned empty content. Please try again.');

        if (editorId) {
            richEditorSet(editorId, enhanced);
        } else {
            const el = document.getElementById(`f_${fieldName}`);
            if (el) el.value = enhanced.replace(/<[^>]*>/g, '');
        }

        toast(`✓ ${fieldName.replace(/_/g, ' ')} improved by AI. Please review.`, 'success');

    } catch (e) {
        toast('Enhancement failed: ' + (e.message || 'Unknown error'), 'danger');
    } finally {
        hideBanner();
        if (btn) { btn.disabled = false; btn.innerHTML = origHtml; }
    }
}

// ============================================================
// GENERATE FULL POST FROM TITLE
// ============================================================
async function aiGenerateFullPost() {
    const title   = document.getElementById('f_job_title')?.value?.trim();
    const company = document.getElementById('f_company_input')?.value?.trim() || '';

    if (!title) {
        toast('Please enter a job title first.', 'warning');
        document.getElementById('f_job_title')?.focus();
        return;
    }

    const model = document.getElementById('selectedModel')?.value || 'claude';
    showBanner('AI agent generating complete job post…');

    try {
        const result = await apiFetch(`${AI_API_BASE}/generate-from-title`, {
            method: 'POST',
            body: JSON.stringify({ model, title, company }),
        });

        const d = result.data || {};

        if (d.job_description)  richEditorSet('f_job_description_editor',  d.job_description);
        if (d.responsibilities) richEditorSet('f_responsibilities_editor',  d.responsibilities);
        if (d.qualifications)   richEditorSet('f_qualifications_editor',    d.qualifications);
        if (d.skills)           richEditorSet('f_skills_editor',            d.skills);

        const setText = (id, val) => { if (val) { const el = document.getElementById(id); if (el) el.value = val; } };
        setText('f_meta_description', d.meta_description);
        setText('f_keywords', d.keywords);

        if (d.employment_type) { const el = document.getElementById('f_employment_type'); if (el) el.value = d.employment_type; }
        if (d.location_type)   { const el = document.getElementById('f_location_type');   if (el) el.value = d.location_type;   }

        autoSelectDropdowns(d);

        if (d.deadline) {
            const el = document.getElementById('f_deadline');
            if (el && !el.value) {
                const parts = d.deadline.split('-');
                if (parts.length === 3) el.value = `${parts[1]}/${parts[2]}/${parts[0]}`;
            }
        }

        showDropdownStatus();
        toast('✓ Full job post generated. Fill Company & Location, then submit.', 'success');

    } catch (e) {
        toast('Generation failed: ' + (e.message || 'Unknown error'), 'danger');
    } finally {
        hideBanner();
    }
}

// ============================================================
// FORM SUBMISSION
// ============================================================
async function submitJobPost(mode = 'live') {
    const isDraft    = mode === 'draft';
    const btn        = document.getElementById(isDraft ? 'submitDraftBtn' : 'submitJobBtn');
    if (!btn) return;

    const btnText    = document.getElementById(isDraft ? 'submitDraftBtnText'    : 'submitJobBtnText');
    const btnSpinner = document.getElementById(isDraft ? 'submitDraftBtnSpinner' : 'submitJobBtnSpinner');
    const origText   = btnText?.innerHTML || '';

    btn.disabled = true;
    if (btnSpinner) btnSpinner.classList.remove('d-none');
    if (btnText)    btnText.innerHTML = isDraft
        ? '<i class="ti ti-device-floppy me-2"></i>Saving…'
        : '<i class="ti ti-send me-2"></i>Posting…';

    syncAllRichEditors();

    const form = document.getElementById('aiJobForm');
    const data = {};
    new FormData(form).forEach((v, k) => data[k] = v);

    // Fallback: read rich editors directly
    const editorFields = {
        job_description:  'f_job_description_editor',
        responsibilities: 'f_responsibilities_editor',
        qualifications:   'f_qualifications_editor',
        skills:           'f_skills_editor',
    };
    Object.entries(editorFields).forEach(([field, editorId]) => {
        const content = richEditorGet(editorId);
        if (content) data[field] = content;
    });

    // Booleans
    ['is_resume_required','is_cover_letter_required','is_academic_documents_required',
     'is_application_required','is_whatsapp_contact','is_telephone_call',
     'is_featured','is_urgent','is_quick_gig','is_verified','is_simple_job',
    ].forEach(k => { data[k] = data[k] === 'on' || data[k] === true; });

    if (isDraft) data.is_active = false;

    // Deadline: MM/DD/YYYY → YYYY-MM-DD
    if (data.deadline) {
        const parts = data.deadline.split('/');
        if (parts.length === 3) data.deadline = `${parts[2]}-${parts[0].padStart(2,'0')}-${parts[1].padStart(2,'0')}`;
    }

    // Validation
    const errors = [];
    if (!data.job_title)           errors.push('Job title is required');
    if (!data.company_id)          errors.push('Company is required — type to search and click to select');
    if (!data.job_category_id)     errors.push('Category is required — type to search and click to select');
    if (!data.industry_id)         errors.push('Industry is required — type to search and click to select');
    if (!data.job_location_id)     errors.push('Location is required — type to search and click to select');
    if (!data.job_type_id)         errors.push('Job type is required — type to search and click to select');
    if (!data.experience_level_id) errors.push('Experience level is required — type to search and click to select');
    if (!data.education_level_id)  errors.push('Education level is required — type to search and click to select');
    if (!data.deadline)            errors.push('Application deadline is required');
    if (!richEditorGet('f_job_description_editor') && !data.job_description)
        errors.push('Job description is required');

    if (errors.length) {
        btn.disabled = false;
        if (btnSpinner) btnSpinner.classList.add('d-none');
        if (btnText)    btnText.innerHTML = origText;

        const errorDiv = document.getElementById('formErrors');
        if (errorDiv) {
            errorDiv.innerHTML = `<div class="alert alert-danger">
                <strong><i class="ti ti-alert-circle me-2"></i>Please complete the following:</strong>
                <ul class="mb-0 mt-2">${errors.map(e => `<li>${e}</li>`).join('')}</ul>
            </div>`;
            errorDiv.scrollIntoView({ behavior: 'smooth' });
        }
        toast(errors[0], 'danger');
        return;
    }

    showBanner(isDraft ? 'Saving draft…' : 'Submitting job post…');

    try {
        const res = await apiFetch(API_BASE, { method: 'POST', body: JSON.stringify(data) });
        hideBanner();
        btn.disabled = false;
        if (btnSpinner) btnSpinner.classList.add('d-none');
        if (btnText)    btnText.innerHTML = origText;

        toast(isDraft ? 'Draft saved!' : 'Job posted successfully!', 'success');

        const errorDiv = document.getElementById('formErrors');
        if (errorDiv) {
            errorDiv.innerHTML = `<div class="alert alert-success">
                <i class="ti ti-check me-2"></i><strong>${isDraft ? 'Draft saved!' : 'Job posted!'}</strong>
                <a href="/job-posts/${res.data?.slug || ''}" class="alert-link ms-2" target="_blank">
                    View job <i class="ti ti-external-link ms-1"></i>
                </a>
            </div>`;
        }

        setTimeout(() => {
            if (!isDraft) {
                if (confirm('Job posted! Post another?')) clearForm();
                else window.location.href = '/jobs';
            } else {
                if (!confirm('Draft saved. Continue editing?')) window.location.href = '/dashboard';
            }
        }, 600);

    } catch (err) {
        hideBanner();
        btn.disabled = false;
        if (btnSpinner) btnSpinner.classList.add('d-none');
        if (btnText)    btnText.innerHTML = origText;
        
        // SIMPLIFIED: Just get the message directly
        let errorMsg = err.message || (typeof err === 'string' ? err : 'Submission failed');
        
        // Show toast with the message
        toast(errorMsg, 'danger');
        
        // Display in formErrors div
        displayFormErrors(document.getElementById('formErrors'), err);
    }
}


// ============================================================
// SIMPLIFIED ERROR HELPERS
// ============================================================
function formatErrorMessage(err) {
    // If it's a string, return it
    if (typeof err === 'string') return err;
    
    // If there's a message property (like your API returns)
    if (err.message) return err.message;
    
    // If there's an error property
    if (err.error) return typeof err.error === 'string' ? err.error : JSON.stringify(err.error);
    
    // If there are validation errors
    if (err.errors) {
        // Check if errors is an object with specific fields
        if (typeof err.errors === 'object') {
            // Try to get the first error message
            for (let key in err.errors) {
                if (err.errors[key] && typeof err.errors[key] === 'string') {
                    return err.errors[key];
                }
                if (Array.isArray(err.errors[key]) && err.errors[key].length) {
                    return err.errors[key][0];
                }
            }
        }
        return String(err.errors);
    }
    
    // Default fallback
    return 'An unknown error occurred. Please try again.';
}

function displayFormErrors(errorDiv, err) {
    if (!errorDiv) return;
    
    // Get the error message
    let errorMessage = formatErrorMessage(err);
    
    // Check if we have duplicate job info
    let duplicateInfo = '';
    if (err.existing_job) {
        duplicateInfo = `
            <div class="mt-2 pt-2 border-top">
                <small class="text-muted">Existing job:</small>
                <div class="mt-1">
                    <strong>${escapeHtml(err.existing_job.title || 'N/A')}</strong>
                    ${err.similarity ? `<span class="badge bg-warning ms-2">${err.similarity}% similar</span>` : ''}
                    ${err.existing_job.slug ? `<a href="/job-posts/${err.existing_job.slug}" class="btn btn-sm btn-outline-secondary mt-1" target="_blank">View Existing Job</a>` : ''}
                </div>
            </div>
        `;
    }
    
    // Display the error
    errorDiv.innerHTML = `
        <div class="alert alert-danger">
            <div class="d-flex align-items-start gap-2">
                <i class="ti ti-alert-circle text-danger fs-5 mt-1 flex-shrink-0"></i>
                <div class="flex-grow-1">
                    <strong class="d-block mb-1">Error:</strong>
                    <div>${escapeHtml(errorMessage)}</div>
                    ${duplicateInfo}
                </div>
            </div>
        </div>
    `;
    
    errorDiv.scrollIntoView({ behavior: 'smooth', block: 'start' });
}



// ============================================================
// CLEAR FORM
// ============================================================
function clearForm() {
    document.getElementById('aiJobForm')?.reset();
    ['f_job_description_editor','f_responsibilities_editor','f_qualifications_editor','f_skills_editor']
        .forEach(id => richEditorSet(id, ''));
    Object.values(drops).forEach(d => d?.reset());
    const errorDiv = document.getElementById('formErrors');
    if (errorDiv) errorDiv.innerHTML = '';
    extractedData = null;
    hideBanner();
}

// ============================================================
// OPEN MODALS
// ============================================================
function openAiExtractModal()    { bsModal('aiExtractModal').show(); }
function openImageExtractModal() { bsModal('imageExtractModal')?.show(); }

// ============================================================
// INIT
// ============================================================
document.addEventListener('DOMContentLoaded', () => {
    loadDropdowns();
    initCharCounters();
    initSourceTypeToggle();

    ['f_job_description_editor','f_responsibilities_editor','f_qualifications_editor','f_skills_editor']
        .forEach(id => {
            const el = document.getElementById(id);
            if (el) {
                el.addEventListener('input', () => richEditorSync(id));
                el.addEventListener('blur',  () => richEditorSync(id));
            }
        });

    if (typeof $ !== 'undefined' && $.fn.datepicker) {
        $('.datepicker-autoclose').datepicker({ autoclose: true, todayHighlight: true, format: 'mm/dd/yyyy' });
    }
});
</script>