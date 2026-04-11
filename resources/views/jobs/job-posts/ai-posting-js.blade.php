<script>
// ============================================================
// CONFIG
// ============================================================
const API_BASE = '/api/v1/job-posts';
const AI_API_BASE = '/ai';
const CSRF_TOKEN = document.querySelector('meta[name="csrf-token"]')?.content ?? '';
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
// TYPABLE DROPDOWN
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
// RENDER EXTRACTED PREVIEW (Responsive with word wrap)
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
        
        // Convert value to string and handle long text
        let displayValue = String(val);
        
        html += `
            <div class="d-flex gap-2 p-2 bg-body rounded-2 align-items-start">
                <i class="ti ${f.icon} text-primary flex-shrink-0 mt-1" style="font-size:14px"></i>
                <div class="flex-grow-1 min-w-0">
                    <div class="text-muted small mb-1">${escapeHtml(f.label)}</div>
                    <div class="text-break word-wrap fw-semibold" style="font-size:13px; line-height:1.5; overflow-wrap: break-word; word-wrap: break-word; hyphens: auto; white-space: normal;">
                        ${escapeHtml(displayValue)}
                    </div>
                </div>
            </div>`;
    });

    if (data.job_description) {
        // Strip HTML tags and clean the description
        let descriptionText = data.job_description
            .replace(/<[^>]*>/g, ' ')  // Replace HTML tags with spaces
            .replace(/&nbsp;/g, ' ')    // Replace &nbsp; with spaces
            .replace(/\s+/g, ' ')       // Collapse multiple spaces
            .trim();
        
        // Limit length but preserve word boundaries
        let displayDesc = descriptionText;
        if (descriptionText.length > 300) {
            displayDesc = descriptionText.substring(0, 300);
            // Find last space to avoid cutting words
            const lastSpace = displayDesc.lastIndexOf(' ');
            if (lastSpace > 200) {
                displayDesc = displayDesc.substring(0, lastSpace);
            }
            displayDesc += '...';
        }
        
        html += `
            <div class="d-flex gap-2 p-2 bg-body rounded-2 align-items-start">
                <i class="ti ti-file-description text-primary flex-shrink-0 mt-1" style="font-size:14px"></i>
                <div class="flex-grow-1 min-w-0">
                    <div class="text-muted small mb-1">Description</div>
                    <div class="text-break word-wrap" style="font-size:13px; line-height:1.5; overflow-wrap: break-word; word-wrap: break-word; white-space: normal;">
                        ${escapeHtml(displayDesc)}
                    </div>
                </div>
            </div>`;
    }
    
    html += '</div>';
    panel.innerHTML = html;
}

// Helper function to escape HTML
function escapeHtml(str) {
    if (!str) return '';
    return String(str)
        .replace(/&/g, '&amp;')
        .replace(/</g, '&lt;')
        .replace(/>/g, '&gt;')
        .replace(/"/g, '&quot;')
        .replace(/'/g, '&#39;')
        .replace(/\n/g, ' ');
}

// ============================================================
// EXTRACT JOB DATA VIA WEB ROUTE (TEXT/URL)
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
            const errorMessage = formatErrorMessage(e);
            preview.innerHTML = `
                <div class="alert alert-danger">
                    <i class="ti ti-alert-circle me-2"></i>
                    <strong>Extraction failed:</strong>
                    <div class="mt-1">${escapeHtml(errorMessage)}</div>
                </div>`;
        }
        toast(formatErrorMessage(e).split('<br>')[0], 'error');
    } finally {
        btn.disabled = false;
        spinner.classList.add('d-none');
    }
}

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
// SOURCE TYPE TOGGLE (text/url)
// ============================================================
function initSourceTypeToggle() {
    const sourceTypeRadios = document.querySelectorAll('input[name="sourceType"]');
    const textPanel = document.getElementById('textSourcePanel');
    const urlPanel = document.getElementById('urlSourcePanel');
    
    function toggleSourceType() {
        const selectedValue = document.querySelector('input[name="sourceType"]:checked').value;
        if (selectedValue === 'text') {
            if (textPanel) textPanel.style.display = 'block';
            if (urlPanel) urlPanel.style.display = 'none';
        } else {
            if (textPanel) textPanel.style.display = 'none';
            if (urlPanel) urlPanel.style.display = 'block';
        }
    }
    
    // Add event listeners to all radio buttons
    sourceTypeRadios.forEach(radio => {
        radio.addEventListener('change', toggleSourceType);
    });
    
    // Set initial state
    toggleSourceType();
}

// ============================================================
// SOURCE TYPE TOGGLE (Alternative using event delegation)
// ============================================================
document.addEventListener('change', function(e) {
    if (e.target && e.target.name === 'sourceType') {
        const textPanel = document.getElementById('textSourcePanel');
        const urlPanel = document.getElementById('urlSourcePanel');
        
        if (textPanel && urlPanel) {
            if (e.target.value === 'text') {
                textPanel.style.display = 'block';
                urlPanel.style.display = 'none';
            } else if (e.target.value === 'url') {
                textPanel.style.display = 'none';
                urlPanel.style.display = 'block';
            }
        }
    }
});



// ============================================================
// APPLY EXTRACTED DATA TO FORM
// ============================================================
function applyExtractedData() {
    if (!extractedData) {
        toast('No extracted data to apply. Please extract data first.', 'warning');
        return;
    }
    
    const d = extractedData;
    
    // console.log('Applying extracted data:', d);

    // Simple text fields
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
            if (el) {
                el.value = d[dataKey];
                // console.log(`Set ${fieldId} to:`, d[dataKey]);
            }
        }
    });

    // Handle deadline (convert YYYY-MM-DD to MM/DD/YYYY)
    if (d.deadline) {
        const el = document.getElementById('f_deadline');
        if (el) {
            const parts = d.deadline.split('-');
            if (parts.length === 3) {
                el.value = `${parts[1]}/${parts[2]}/${parts[0]}`;
                // console.log(`Set deadline to: ${el.value}`);
            }
        }
    }

    // Handle dropdown selects
    if (d.employment_type) {
        const el = document.getElementById('f_employment_type');
        if (el) {
            el.value = d.employment_type;
            // console.log(`Set employment_type to: ${d.employment_type}`);
        }
    }
    
    if (d.location_type) {
        const el = document.getElementById('f_location_type');
        if (el) {
            el.value = d.location_type;
            // console.log(`Set location_type to: ${d.location_type}`);
        }
    }

    // Handle rich text editors
    const richMap = {
        'job_description': 'f_job_description_editor',
        'responsibilities': 'f_responsibilities_editor',
        'qualifications': 'f_qualifications_editor',
        'skills': 'f_skills_editor',
    };
    
    Object.entries(richMap).forEach(([dataKey, editorId]) => {
        if (d[dataKey]) {
            const editor = document.getElementById(editorId);
            if (editor) {
                editor.innerHTML = d[dataKey];
                // console.log(`Set ${editorId} HTML content (${d[dataKey].length} chars)`);
                
                // Sync to hidden input
                const hiddenInput = document.getElementById(editorId + '_hidden');
                if (hiddenInput) {
                    hiddenInput.value = d[dataKey];
                    // console.log(`Set hidden input ${editorId}_hidden`);
                }
                
                // Trigger input event
                editor.dispatchEvent(new Event('input', { bubbles: true }));
            } else {
                console.warn(`Editor not found: ${editorId}`);
            }
        }
    });

    // Handle typable dropdowns (match by name)
    if (d.company_name && drops.company) {
        drops.company.setByName(d.company_name);
        // console.log(`Set company to: ${d.company_name}`);
    }
    if (d.category_name && drops.category) {
        drops.category.setByName(d.category_name);
        // console.log(`Set category to: ${d.category_name}`);
    }
    if (d.industry_name && drops.industry) {
        drops.industry.setByName(d.industry_name);
        // console.log(`Set industry to: ${d.industry_name}`);
    }
    if (d.experience_level_name && drops.experience) {
        drops.experience.setByName(d.experience_level_name);
        // console.log(`Set experience level to: ${d.experience_level_name}`);
    }
    if (d.education_level_name && drops.education) {
        drops.education.setByName(d.education_level_name);
        // console.log(`Set education level to: ${d.education_level_name}`);
    }
    
    // Handle checkboxes
    if (d.is_urgent) {
        const el = document.getElementById('f_urgent');
        if (el) el.checked = true;
    }
    if (d.is_featured) {
        const el = document.getElementById('f_featured');
        if (el) el.checked = true;
    }
    if (d.is_verified) {
        const el = document.getElementById('f_verified');
        if (el) el.checked = true;
    }
    if (d.is_quick_gig) {
        const el = document.getElementById('f_quickgig');
        if (el) el.checked = true;
    }

    // Handle application requirements
    if (d.is_resume_required !== undefined) {
        const el = document.getElementById('f_resume');
        if (el) el.checked = d.is_resume_required;
    }
    if (d.is_cover_letter_required !== undefined) {
        const el = document.getElementById('f_cover');
        if (el) el.checked = d.is_cover_letter_required;
    }
    if (d.is_academic_documents_required !== undefined) {
        const el = document.getElementById('f_academic');
        if (el) el.checked = d.is_academic_documents_required;
    }
    if (d.is_application_required !== undefined) {
        const el = document.getElementById('f_appletter');
        if (el) el.checked = d.is_application_required;
    }
    if (d.is_whatsapp_contact !== undefined) {
        const el = document.getElementById('f_whatsapp');
        if (el) el.checked = d.is_whatsapp_contact;
    }
    if (d.is_telephone_call !== undefined) {
        const el = document.getElementById('f_telcall');
        if (el) el.checked = d.is_telephone_call;
    }

    // Final sync for rich editors
    if (typeof syncAllRichEditors === 'function') {
        syncAllRichEditors();
    } else {
        // Manual sync
        const editors = ['f_job_description_editor', 'f_responsibilities_editor', 'f_qualifications_editor', 'f_skills_editor'];
        editors.forEach(editorId => {
            const editor = document.getElementById(editorId);
            const hidden = document.getElementById(editorId + '_hidden');
            if (editor && hidden) {
                hidden.value = editor.innerHTML;
            }
        });
    }

    toast('Data applied to form. Please review and confirm dropdown selections.', 'success');
    
    // Close both modals if they're open
    const aiModal = document.getElementById('aiExtractModal');
    const imageModal = document.getElementById('imageExtractModal');
    
    if (aiModal && aiModal.classList.contains('show')) {
        bsModal('aiExtractModal').hide();
    }
    if (imageModal && imageModal.classList.contains('show')) {
        bsModal('imageExtractModal').hide();
    }
    
    // Scroll to and highlight the job title field
    const titleField = document.getElementById('f_job_title');
    if (titleField) {
        titleField.scrollIntoView({ behavior: 'smooth', block: 'center' });
        titleField.focus();
        
        // Temporarily highlight the field
        titleField.style.transition = 'background 0.5s';
        titleField.style.background = '#e8f0fe';
        setTimeout(() => {
            titleField.style.background = '';
        }, 1500);
    }
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

    if (!currentContent.trim() || currentContent === '<br>' || currentContent === '<p><br></p>' || currentContent === '<div><br></div>') {
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
        
        console.log('AI Enhance response:', result);
        
        // Check if result exists and has enhanced property
        let enhanced = null;
        
        if (result && result.enhanced) {
            enhanced = result.enhanced;
        } else if (result && result.data && result.data.enhanced) {
            enhanced = result.data.enhanced;
        } else if (result && typeof result === 'string') {
            enhanced = result;
        } else {
            // Try to extract from response
            enhanced = result?.message || result?.error || 'No enhanced content returned';
        }
        
        if (!enhanced || enhanced === 'null' || enhanced === 'undefined') {
            throw new Error('AI returned empty response. Please try again.');
        }
        
        // Clean the enhanced content
        if (typeof enhanced === 'string') {
            enhanced = enhanced.replace(/```html\n?/g, '').replace(/```\n?/g, '').trim();
        } else {
            enhanced = String(enhanced);
        }

        if (editorMap[fieldName]) {
            const editor = document.getElementById(editorMap[fieldName]);
            if (editor) {
                editor.innerHTML = enhanced;
                // Also update hidden input
                const hiddenInput = document.getElementById(editorMap[fieldName] + '_hidden');
                if (hiddenInput) hiddenInput.value = enhanced;
            }
        } else {
            const el = document.getElementById(`f_${fieldName}`);
            if (el) el.value = enhanced.replace(/<[^>]*>/g, '');
        }
        
        toast(`${fieldName.replace(/_/g, ' ')} enhanced successfully!`, 'success');
        
    } catch (e) {
        console.error('AI Enhance error:', e);
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
function richEditorSync(editorId) {
    const editor = document.getElementById(editorId);
    const hidden = document.getElementById(editorId + '_hidden');
    
    if (editor && hidden) {
        let content = editor.innerHTML;
        if (!content || content === '<br>' || content === '<p><br></p>' || 
            content === '<div><br></div>' || content.trim() === '') {
            content = '';
        }
        hidden.value = content;
        return content;
    }
    return '';
}

function richEditorGet(editorId) {
    const editor = document.getElementById(editorId);
    if (!editor) return '';
    let content = editor.innerHTML;
    if (!content || content === '<br>' || content === '<p><br></p>' || content === '<div><br></div>') {
        return '';
    }
    return content;
}

function richEditorSet(editorId, html) {
    const editor = document.getElementById(editorId);
    const hidden = document.getElementById(editorId + '_hidden');
    if (editor) {
        editor.innerHTML = html || '';
        if (hidden) hidden.value = html || '';
    }
}

function richEditorClear(editorId) {
    richEditorSet(editorId, '');
}

function syncAllRichEditors() {
    const editors = ['f_job_description_editor', 'f_responsibilities_editor', 'f_qualifications_editor', 'f_skills_editor'];
    editors.forEach(editorId => richEditorSync(editorId));
}

// ============================================================
// FORM SUBMISSION WITH LOADING STATES
// ============================================================
async function submitJobPost(mode = 'live') {
    const isDraft = mode === 'draft';
    const submitBtn = document.getElementById(isDraft ? 'submitDraftBtn' : 'submitJobBtn');
    const btnText = document.getElementById(isDraft ? 'submitDraftBtnText' : 'submitJobBtnText');
    const btnSpinner = document.getElementById(isDraft ? 'submitDraftBtnSpinner' : 'submitJobBtnSpinner');
    
    submitBtn.disabled = true;
    btnSpinner.classList.remove('d-none');
    const originalText = btnText.innerHTML;
    btnText.innerHTML = isDraft ? '<i class="ti ti-device-floppy me-2"></i>Saving...' : '<i class="ti ti-send me-2"></i>Posting...';
    
    syncAllRichEditors();
    
    const form = document.getElementById('aiJobForm');
    const data = {};
    new FormData(form).forEach((v, k) => data[k] = v);
    
    // Manually get rich editor content as backup
    const descriptionEditor = document.getElementById('f_job_description_editor');
    if (descriptionEditor) {
        let content = descriptionEditor.innerHTML;
        if (content && content !== '<br>' && content !== '<p><br></p>' && content !== '<div><br></div>') {
            data.job_description = content;
        }
    }
    
    const responsibilitiesEditor = document.getElementById('f_responsibilities_editor');
    if (responsibilitiesEditor) {
        let content = responsibilitiesEditor.innerHTML;
        if (content && content !== '<br>' && content !== '<p><br></p>' && content !== '<div><br></div>') {
            data.responsibilities = content;
        }
    }
    
    const qualificationsEditor = document.getElementById('f_qualifications_editor');
    if (qualificationsEditor) {
        let content = qualificationsEditor.innerHTML;
        if (content && content !== '<br>' && content !== '<p><br></p>' && content !== '<div><br></div>') {
            data.qualifications = content;
        }
    }
    
    const skillsEditor = document.getElementById('f_skills_editor');
    if (skillsEditor) {
        let content = skillsEditor.innerHTML;
        if (content && content !== '<br>' && content !== '<p><br></p>' && content !== '<div><br></div>') {
            data.skills = content;
        }
    }
    
    // Booleans
    const bools = [
        'is_resume_required', 'is_cover_letter_required', 'is_academic_documents_required',
        'is_application_required', 'is_whatsapp_contact', 'is_telephone_call',
        'is_featured', 'is_urgent', 'is_quick_gig', 'is_verified', 'is_simple_job',
    ];
    bools.forEach(k => { data[k] = data[k] === 'on' || data[k] === true; });
    
    if (isDraft) data.is_active = false;
    
    if (data.deadline) {
        const parts = data.deadline.split('/');
        if (parts.length === 3) data.deadline = `${parts[2]}-${parts[0].padStart(2, '0')}-${parts[1].padStart(2, '0')}`;
    }
    
    // Validation
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
    
    const hasDescription = data.job_description && 
                          data.job_description.trim() !== '' && 
                          data.job_description !== '<br>' &&
                          data.job_description !== '<p><br></p>' &&
                          data.job_description !== '<div><br></div>';
    
    if (!hasDescription) errors.push('Job description is required');
    
    if (errors.length) {
        submitBtn.disabled = false;
        btnSpinner.classList.add('d-none');
        btnText.innerHTML = originalText;
        
        const errorDiv = document.getElementById('formErrors');
        if (errorDiv) {
            errorDiv.innerHTML = `
                <div class="alert alert-danger">
                    <strong><i class="ti ti-alert-circle me-2"></i>Please fix:</strong>
                    <ul class="mb-0 mt-2">${errors.map(e => `<li>${e}</li>`).join('')}</ul>
                </div>`;
            errorDiv.scrollIntoView({ behavior: 'smooth' });
        }
        toast(errors[0], 'error');
        return;
    }
    
    showBanner(isDraft ? 'Saving draft...' : 'Submitting job post...');
    
    try {
        const res = await apiFetch(API_BASE, { method: 'POST', body: JSON.stringify(data) });
        hideBanner();
        
        submitBtn.disabled = false;
        btnSpinner.classList.add('d-none');
        btnText.innerHTML = originalText;
        
        toast(isDraft ? 'Job draft saved successfully!' : 'Job post created successfully!', 'success');
        
        const errorDiv = document.getElementById('formErrors');
        if (errorDiv) {
            errorDiv.innerHTML = `
                <div class="alert alert-success">
                    <i class="ti ti-check me-2"></i>
                    <strong>${isDraft ? 'Draft saved!' : 'Job posted!'}</strong>
                    <a href="/job-posts/${res.data?.slug || ''}" class="alert-link ms-2" target="_blank">
                        View job <i class="ti ti-external-link ms-1"></i>
                    </a>
                </div>`;
        }
        
        if (!isDraft) {
            setTimeout(() => {
                clearForm();
            }, 2000);
        } else {
            clearForm();
        }
            
    } catch (err) {
        hideBanner();
        
        // Reset button state
        submitBtn.disabled = false;
        btnSpinner.classList.add('d-none');
        btnText.innerHTML = originalText;
        
        // console.log('Full error:', err);
        
        // Extract the actual error message
        let errorMessage = '';
        
        if (typeof err === 'string') {
            errorMessage = err;
        } 
        else if (err.message) {
            errorMessage = err.message;
        }
        else if (err.error) {
            errorMessage = typeof err.error === 'string' ? err.error : JSON.stringify(err.error);
        }
        else if (err.errors) {
            // Handle validation errors
            const errorList = [];
            Object.values(err.errors).forEach(e => {
                if (Array.isArray(e)) errorList.push(...e);
                else errorList.push(e);
            });
            errorMessage = errorList.join(', ');
        }
        else {
            errorMessage = 'Failed to post job. Please try again.';
        }
        
        // Show toast with the actual error message
        toast(errorMessage, 'error');
        
        // Display detailed errors in the form
        const errorDiv = document.getElementById('formErrors');
        if (errorDiv) {
            displayFormErrors(errorDiv, err);
        }
    }
}

// ============================================================
// IMPROVED ERROR HANDLING - Display errors systematically
// ============================================================

function formatErrorMessage(err) {
    // If it's a string, return it directly
    if (typeof err === 'string') {
        return err;
    }
    
    // If it's an array, join with line breaks
    if (Array.isArray(err)) {
        return err.join('<br>');
    }
    
    // If it's an object with errors (Laravel validation)
    if (err.errors && typeof err.errors === 'object') {
        const errorList = [];
        Object.values(err.errors).forEach(error => {
            if (Array.isArray(error)) {
                errorList.push(...error);
            } else if (typeof error === 'string') {
                errorList.push(error);
            } else {
                errorList.push(String(error));
            }
        });
        return errorList.join('<br>');
    }
    
    // If it has a message property
    if (err.message) {
        return err.message;
    }
    
    // If it has an error property
    if (err.error) {
        return typeof err.error === 'string' ? err.error : JSON.stringify(err.error);
    }
    
    // Try to stringify the object
    try {
        return JSON.stringify(err, null, 2);
    } catch (e) {
        return 'An unknown error occurred';
    }
}

function displayFormErrors(errorDiv, errors) {
    if (!errorDiv) return;
    
    // console.log('Raw error received:', errors);
    
    let errorMessage = '';
    
    // Handle different error formats
    if (typeof errors === 'string') {
        errorMessage = errors;
    } 
    else if (errors && typeof errors === 'object') {
        // PRIORITY 1: Check for message property (most important)
        if (errors.message && typeof errors.message === 'string') {
            errorMessage = errors.message;
        }
        // PRIORITY 2: Check for error property
        else if (errors.error && typeof errors.error === 'string') {
            errorMessage = errors.error;
        }
        // PRIORITY 3: Check for validation errors
        else if (errors.errors && typeof errors.errors === 'object') {
            const errorList = [];
            Object.values(errors.errors).forEach(err => {
                if (Array.isArray(err)) {
                    errorList.push(...err);
                } else if (typeof err === 'string') {
                    errorList.push(err);
                } else {
                    errorList.push(String(err));
                }
            });
            errorMessage = errorList.join('<br>');
        }
        // PRIORITY 4: If no message found, try to get a useful string
        else {
            // Check if there's a success flag with false (API error pattern)
            if (errors.success === false && errors.message) {
                errorMessage = errors.message;
            }
            // Otherwise, convert the object to a readable string
            else {
                try {
                    const jsonStr = JSON.stringify(errors);
                    if (jsonStr !== '{}' && jsonStr !== 'null') {
                        // Try to parse and extract meaningful info
                        if (errors.data && errors.data.message) {
                            errorMessage = errors.data.message;
                        } else {
                            errorMessage = errors.message || 'An error occurred';
                        }
                    } else {
                        errorMessage = 'An unknown error occurred';
                    }
                } catch (e) {
                    errorMessage = 'An unknown error occurred';
                }
            }
        }
    }
    else {
        errorMessage = String(errors);
    }
    
    // Clean up the message - remove any HTML tags that might be in it
    errorMessage = errorMessage.replace(/<[^>]*>/g, '');
    
    // Display the error
    errorDiv.innerHTML = `
        <div class="alert alert-danger">
            <strong><i class="ti ti-alert-circle me-2"></i>Error:</strong>
            <div class="mt-1">${escapeHtml(errorMessage)}</div>
        </div>`;
    errorDiv.scrollIntoView({ behavior: 'smooth', block: 'start' });
}

// Helper function to escape HTML
function escapeHtml(str) {
    if (!str) return '';
    return String(str)
        .replace(/&/g, '&amp;')
        .replace(/</g, '&lt;')
        .replace(/>/g, '&gt;')
        .replace(/"/g, '&quot;')
        .replace(/'/g, '&#39;');
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
function openAiExtractModal() { 
    bsModal('aiExtractModal').show(); 
}

// ============================================================
// INIT
// ============================================================
document.addEventListener('DOMContentLoaded', () => {
    loadDropdowns();
    initCharCounters();
    
    // Initialize source type toggle
    initSourceTypeToggle();
    
    // Alternative: Also add the event listener directly
    const sourceTypeRadios = document.querySelectorAll('input[name="sourceType"]');
    sourceTypeRadios.forEach(radio => {
        radio.addEventListener('change', function(e) {
            const textPanel = document.getElementById('textSourcePanel');
            const urlPanel = document.getElementById('urlSourcePanel');
            if (this.value === 'text') {
                if (textPanel) textPanel.style.display = 'block';
                if (urlPanel) urlPanel.style.display = 'none';
            } else {
                if (textPanel) textPanel.style.display = 'none';
                if (urlPanel) urlPanel.style.display = 'block';
            }
        });
    });
    
    // Set initial state
    const textPanel = document.getElementById('textSourcePanel');
    const urlPanel = document.getElementById('urlSourcePanel');
    if (textPanel && urlPanel) {
        const isTextSelected = document.querySelector('input[name="sourceType"]:checked')?.value === 'text';
        textPanel.style.display = isTextSelected ? 'block' : 'none';
        urlPanel.style.display = isTextSelected ? 'none' : 'block';
    }
    
    const editorIds = ['f_job_description_editor', 'f_responsibilities_editor', 'f_qualifications_editor', 'f_skills_editor'];
    editorIds.forEach(editorId => {
        const editor = document.getElementById(editorId);
        if (editor) {
            editor.addEventListener('input', () => richEditorSync(editorId));
            editor.addEventListener('blur', () => richEditorSync(editorId));
        }
    });
    
    if (typeof $ !== 'undefined' && $.fn.datepicker) {
        $('.datepicker-autoclose').datepicker({ autoclose: true, todayHighlight: true, format: 'mm/dd/yyyy' });
    }
});
</script>

<style>
    .btn:disabled {
        opacity: 0.7;
        cursor: not-allowed;
    }
    .btn .spinner-border {
        vertical-align: middle;
    }
</style>
