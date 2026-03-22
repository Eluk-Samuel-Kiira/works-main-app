<script>
    // ============================================================
// DESCRIPTION EDITOR — lightweight formatter
// ============================================================
function descFmt(cmd, val = null) {
    document.getElementById('descEditor').focus();
    document.execCommand(cmd, false, val);
}

// Placeholder behaviour
document.addEventListener('DOMContentLoaded', () => {
    const editor = document.getElementById('descEditor');
    if (!editor) return;
    editor.addEventListener('focus', () => {
        if (editor.innerText.trim() === '') editor.innerHTML = '';
    });
    editor.addEventListener('blur', () => {
        if (editor.innerText.trim() === '')
            editor.innerHTML = '';
    });
});

// ============================================================
// LOAD DROPDOWNS FOR CREATE MODAL
// ============================================================
const createDropdowns = {
    '#c_company_id'        : '/api/v1/companies?per_page=500',
    '#c_job_category_id'   : '/api/v1/job-categories?per_page=200',
    '#c_industry_id'       : '/api/v1/industries?per_page=200',
    '#c_job_location_id'   : '/api/v1/job-locations?per_page=200',
    '#c_job_type_id'       : '/api/v1/job-types?per_page=200',
    '#c_experience_level_id': '/api/v1/experience-levels?per_page=100',
    '#c_education_level_id' : '/api/v1/education-levels?per_page=100',
};

async function loadCreateDropdowns() {
    for (const [selector, url] of Object.entries(createDropdowns)) {
        try {
            const res  = await apiFetch(url);
            const items = res.data ?? [];
            const sel  = document.querySelector(selector);
            if (!sel) continue;
            items.forEach(item => {
                const opt = document.createElement('option');
                opt.value = item.id;
                // JobLocation: use district + country
                if (selector === '#c_job_location_id') {
                    opt.textContent = [item.district, item.country].filter(Boolean).join(', ');
                } else {
                    opt.textContent = item.name ?? item.title ?? item.id;
                }
                sel.appendChild(opt);
            });
        } catch (_) {}
    }
}

// ============================================================
// OPEN CREATE MODAL
// ============================================================
function openCreateModal() {
    document.getElementById('createForm')?.reset();
    document.getElementById('descEditor').innerHTML = '';
    document.getElementById('createFormErrors').innerHTML = '';
    // Load dropdowns once
    if (document.querySelector('#c_company_id option:nth-child(2)') === null) {
        loadCreateDropdowns();
    }
    bsModal('createModal').show();
}

// ============================================================
// SUBMIT CREATE
// ============================================================
async function submitCreate() {
    // Sync editor HTML → hidden input
    const editor = document.getElementById('descEditor');
    document.getElementById('c_job_description').value = editor.innerHTML.trim();

    const form = document.getElementById('createForm');
    if (!form.checkValidity()) {
        form.reportValidity();
        return;
    }

    const data = {};
    new FormData(form).forEach((v, k) => data[k] = v);

    // Booleans — unchecked checkboxes are omitted by FormData
    ['is_resume_required','is_cover_letter_required','is_academic_documents_required',
     'is_application_required','is_whatsapp_contact','is_telephone_call','is_featured']
        .forEach(k => { data[k] = data[k] === 'on'; });

    document.getElementById('createBtnText').textContent = 'Posting…';
    document.getElementById('createBtnSpinner').classList.remove('d-none');
    document.getElementById('createSaveBtn').disabled = true;

    try {
        await apiFetch(API_BASE, { method: 'POST', body: JSON.stringify(data) });
        bsModal('createModal').hide();
        toast('Job post created successfully!', 'success');
        loadJobs(1);
    } catch (err) {
        const msgs = err.errors
            ? Object.values(err.errors).flat().map(m => `<li>${m}</li>`).join('')
            : (err.message ?? 'Failed to create job post.');
        document.getElementById('createFormErrors').innerHTML =
            `<div class="alert alert-danger mt-2"><ul class="mb-0">${msgs}</ul></div>`;
    } finally {
        document.getElementById('createBtnText').textContent = 'Post Job';
        document.getElementById('createBtnSpinner').classList.add('d-none');
        document.getElementById('createSaveBtn').disabled = false;
    }
}
</script>