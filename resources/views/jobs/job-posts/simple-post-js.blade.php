<script>

    // ============================================================
    // TYPABLE DROPDOWN MANAGER
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
                if (!this.inputEl.contains(e.target) && !this.listEl.contains(e.target)) {
                    this.hide();
                }
            });
        }
        
        setItems(items) {
            this.items = items;
            this.render();
        }
        
        filter() {
            const searchTerm = this.inputEl.value.toLowerCase();
            const filtered = this.items.filter(item => {
                const displayText = this.getDisplayText(item).toLowerCase();
                return displayText.includes(searchTerm);
            });
            this.render(filtered);
            this.show();
        }
        
        getDisplayText(item) {
            if (this.formatItem) {
                return this.formatItem(item);
            }
            return item[this.displayKey] || '';
        }
        
        render(itemsToShow = null) {
            const items = itemsToShow || this.items;
            this.listEl.innerHTML = '';
            
            if (items.length === 0) {
                const li = document.createElement('li');
                li.className = 'dropdown-item text-muted';
                li.textContent = 'No results found';
                li.style.cursor = 'default';
                this.listEl.appendChild(li);
            } else {
                items.forEach(item => {
                    const li = document.createElement('li');
                    li.className = 'dropdown-item';
                    li.textContent = this.getDisplayText(item);
                    li.style.cursor = 'pointer';
                    li.addEventListener('click', () => this.select(item));
                    this.listEl.appendChild(li);
                });
            }
        }
        
        select(item) {
            this.inputEl.value = this.getDisplayText(item);
            this.hiddenEl.value = item[this.valueKey];
            this.hide();
            
            const event = new Event('change', { bubbles: true });
            this.hiddenEl.dispatchEvent(event);
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
        
        getValue() {
            return this.hiddenEl.value;
        }
        
        setValue(value) {
            const item = this.items.find(i => i[this.valueKey] == value);
            if (item) {
                this.select(item);
            }
        }
        
        reset() {
            this.inputEl.value = '';
            this.hiddenEl.value = '';
            this.render();
            this.hide();
        }
    }

    // Store dropdown instances
    const dropdowns = {};

    // ============================================================
    // LOAD DROPDOWNS FOR CREATE MODAL
    // ============================================================
    const createDropdownConfigs = {
        company: {
            url: '/api/v1/companies?per_page=500',
            inputId: 'c_company_input',
            hiddenId: 'c_company_id',
            listId: 'c_company_list',
            displayKey: 'name',
            valueKey: 'id'
        },
        job_category: {
            url: '/api/v1/job-categories?per_page=200',
            inputId: 'c_job_category_input',
            hiddenId: 'c_job_category_id',
            listId: 'c_job_category_list',
            displayKey: 'name',
            valueKey: 'id'
        },
        industry: {
            url: '/api/v1/industries?per_page=200',
            inputId: 'c_industry_input',
            hiddenId: 'c_industry_id',
            listId: 'c_industry_list',
            displayKey: 'name',
            valueKey: 'id'
        },
        job_location: {
            url: '/api/v1/job-locations?per_page=200',
            inputId: 'c_job_location_input',
            hiddenId: 'c_job_location_id',
            listId: 'c_job_location_list',
            formatItem: (item) => [item.district, item.country].filter(Boolean).join(', ')
        },
        job_type: {
            url: '/api/v1/job-types?per_page=200',
            inputId: 'c_job_type_input',
            hiddenId: 'c_job_type_id',
            listId: 'c_job_type_list',
            displayKey: 'name',
            valueKey: 'id'
        },
        experience_level: {
            url: '/api/v1/experience-levels?per_page=100',
            inputId: 'c_experience_level_input',
            hiddenId: 'c_experience_level_id',
            listId: 'c_experience_level_list',
            displayKey: 'name',
            valueKey: 'id'
        },
        education_level: {
            url: '/api/v1/education-levels?per_page=100',
            inputId: 'c_education_level_input',
            hiddenId: 'c_education_level_id',
            listId: 'c_education_level_list',
            displayKey: 'name',
            valueKey: 'id'
        }
    };

    async function loadCreateDropdowns() {
        for (const [key, config] of Object.entries(createDropdownConfigs)) {
            try {
                const res = await apiFetch(config.url);
                const items = res.data ?? [];
                
                if (!dropdowns[key]) {
                    dropdowns[key] = new TypableDropdown(config);
                }
                
                dropdowns[key].setItems(items);
            } catch (err) {
                console.error(`Failed to load ${key}:`, err);
            }
        }
    }

    // ============================================================
    // DISPLAY ERROR MESSAGES
    // ============================================================
    function displayErrorMessages(err) {
        const errorContainer = document.getElementById('createFormErrors');
        let errorHtml = '<div class="alert alert-danger mt-2">';
        
        // Check if it's a duplicate job error
        if (err.is_duplicate || (err.errors && err.errors.is_duplicate)) {
            const duplicateData = err.errors || err;
            errorHtml += `
                <div class="d-flex align-items-start mb-3">
                    <i class="ti ti-alert-circle fs-4 me-2"></i>
                    <div class="flex-grow-1">
                        <strong class="d-block mb-2">⚠️ Duplicate Job Detected!</strong>
                        <p class="mb-2">${err.message || 'A similar job already exists.'}</p>
                        <div class="bg-white bg-opacity-10 p-3 rounded">
                            <p class="mb-1"><strong>Existing Job:</strong> ${duplicateData.existing_job?.title || 'Unknown'}</p>
                            <p class="mb-1"><strong>Similarity:</strong> ${duplicateData.similarity || duplicateData.existing_job?.similarity || 0}% match</p>
                            <p class="mb-1"><strong>Posted:</strong> ${duplicateData.existing_job?.posted_at || 'Recently'}</p>
                            <p class="mb-0"><strong>Slug:</strong> ${duplicateData.existing_job?.slug || 'N/A'}</p>
                        </div>
                        <div class="mt-3">
                            <button type="button" class="btn btn-sm btn-outline-light me-2" onclick="document.getElementById('createModal').querySelector('.btn-close').click();">
                                <i class="ti ti-x me-1"></i>Cancel
                            </button>
                        </div>
                    </div>
                </div>
            `;
        }
        // Check for validation errors (Laravel format)
        else if (err.errors && typeof err.errors === 'object') {
            errorHtml += '<strong><i class="ti ti-alert-circle me-2"></i>Validation Errors:</strong><ul class="mb-0 mt-2">';
            for (const [field, messages] of Object.entries(err.errors)) {
                const fieldName = field.replace(/_/g, ' ').replace(/\b\w/g, l => l.toUpperCase());
                messages.forEach(message => {
                    errorHtml += `<li><strong>${fieldName}:</strong> ${message}</li>`;
                });
            }
            errorHtml += '</ul>';
        }
        // Check for string error message
        else if (err.message) {
            errorHtml += `
                <div class="d-flex align-items-center">
                    <i class="ti ti-alert-circle fs-4 me-2"></i>
                    <span>${err.message}</span>
                </div>
            `;
        }
        // Fallback for unknown error format
        else {
            errorHtml += `
                <div class="d-flex align-items-center">
                    <i class="ti ti-alert-circle fs-4 me-2"></i>
                    <span>${JSON.stringify(err) || 'An unknown error occurred.'}</span>
                </div>
            `;
        }
        
        errorHtml += '</div>';
        errorContainer.innerHTML = errorHtml;
        
        // Scroll to errors
        errorContainer.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
    }


    // ============================================================
    // OPEN CREATE MODAL
    // ============================================================
    function openCreateModal() {
        const form = document.getElementById('createForm');
        if (form) form.reset();
        
        const editor = document.getElementById('descEditor');
        if (editor) editor.innerHTML = '';
        
        Object.values(dropdowns).forEach(dropdown => dropdown.reset());
        
        document.getElementById('createFormErrors').innerHTML = '';
        forceSubmit = false;
        
        if (Object.keys(dropdowns).length === 0) {
            loadCreateDropdowns();
        }
        
        bsModal('createModal').show();
    }

    // ============================================================
    // CHECK FOR DUPLICATE JOB
    // ============================================================
    async function checkDuplicateJob(jobTitle, companyId) {
        try {
            const response = await apiFetch(`${API_BASE}/check-duplicate`, {
                method: 'POST',
                body: JSON.stringify({
                    job_title: jobTitle,
                    company_id: companyId
                })
            });
            return response;
        } catch (err) {
            // If duplicate check fails, still allow submission
            console.error('Duplicate check failed:', err);
            return null;
        }
    }

    // ============================================================
    // SUBMIT CREATE
    // ============================================================
    async function submitCreate(bypassDuplicate = false) {
        // Sync editor HTML → hidden input
        const editor = document.getElementById('descEditor');
        document.getElementById('c_job_description').value = editor.innerHTML.trim();

        const form = document.getElementById('createForm');
        
        // Clear previous errors and remove is-invalid classes
        document.getElementById('createFormErrors').innerHTML = '';
        document.querySelectorAll('.is-invalid').forEach(el => el.classList.remove('is-invalid'));

        // Get all form data
        const data = {};
        new FormData(form).forEach((v, k) => data[k] = v);

        // Booleans — unchecked checkboxes are omitted by FormData
        ['is_resume_required','is_cover_letter_required','is_academic_documents_required',
        'is_application_required','is_whatsapp_contact','is_telephone_call','is_featured']
            .forEach(k => { data[k] = data[k] === 'on'; });

        // Set button to loading state
        document.getElementById('createBtnText').textContent = 'Posting...';
        document.getElementById('createBtnSpinner').classList.remove('d-none');
        document.getElementById('createSaveBtn').disabled = true;

        try {
            // Check for duplicate if not bypassing
            if (!bypassDuplicate && !forceSubmit) {
                document.getElementById('createBtnText').textContent = 'Checking for duplicates...';
                const duplicateCheck = await checkDuplicateJob(data.job_title, data.company_id);
                
                if (duplicateCheck && duplicateCheck.is_duplicate) {
                    displayErrorMessages({
                        message: duplicateCheck.message || 'A similar job already exists.',
                        is_duplicate: true,
                        similarity: duplicateCheck.similarity,
                        existing_job: duplicateCheck.existing_job
                    });
                    return;
                }
            }
            
            // Proceed with creation - let backend handle all validation
            document.getElementById('createBtnText').textContent = 'Posting...';
            
            const response = await apiFetch(API_BASE, { 
                method: 'POST', 
                body: JSON.stringify(data) 
            });
            
            bsModal('createModal').hide();
            toast('Job post created successfully!', 'success');
            if (typeof loadJobs === 'function') loadJobs(1);
            
        } catch (err) {
            // Display backend validation errors
            displayErrorMessages(err);
            
            // Highlight fields with errors
            if (err.errors) {
                for (const [field, messages] of Object.entries(err.errors)) {
                    const input = document.querySelector(`[name="${field}"]`);
                    if (input) {
                        input.classList.add('is-invalid');
                        
                        // Add error message below field if needed
                        const feedbackDiv = document.createElement('div');
                        feedbackDiv.className = 'invalid-feedback';
                        feedbackDiv.innerHTML = messages.join(', ');
                        
                        const parent = input.parentElement;
                        if (parent && !parent.querySelector('.invalid-feedback')) {
                            parent.appendChild(feedbackDiv);
                        }
                    }
                }
            }
            
            toast(err.message || 'Failed to create job post.', 'error');
        } finally {
            document.getElementById('createBtnText').textContent = 'Post Job';
            document.getElementById('createBtnSpinner').classList.add('d-none');
            document.getElementById('createSaveBtn').disabled = false;
        }
    }

    // ============================================================
    // DISPLAY ERROR MESSAGES
    // ============================================================
    function displayErrorMessages(err) {
        const errorContainer = document.getElementById('createFormErrors');
        let errorHtml = '<div class="alert alert-danger mt-2">';
        
        // Check if it's a duplicate job error
        if (err.is_duplicate || (err.errors && err.errors.is_duplicate)) {
            const duplicateData = err.errors || err;
            errorHtml += `
                <div class="d-flex align-items-start mb-3">
                    <i class="ti ti-alert-circle fs-4 me-2"></i>
                    <div class="flex-grow-1">
                        <strong class="d-block mb-2">⚠️ Duplicate Job Detected!</strong>
                        <p class="mb-2">${err.message || 'A similar job already exists.'}</p>
                        <div class="bg-light p-3 rounded">
                            <p class="mb-1"><strong>Existing Job:</strong> ${duplicateData.existing_job?.title || 'Unknown'}</p>
                            <p class="mb-1"><strong>Similarity:</strong> ${duplicateData.similarity || duplicateData.existing_job?.similarity || 0}% match</p>
                            <p class="mb-1"><strong>Posted:</strong> ${duplicateData.existing_job?.posted_at || 'Recently'}</p>
                            <p class="mb-0"><strong>Slug:</strong> ${duplicateData.existing_job?.slug || 'N/A'}</p>
                        </div>
                        <div class="mt-3">
                            <button type="button" class="btn btn-sm btn-outline-danger me-2" onclick="document.getElementById('createModal').querySelector('.btn-close').click();">
                                <i class="ti ti-x me-1"></i>Cancel
                            </button>
                        </div>
                    </div>
                </div>
            `;
        }
        // Check for validation errors (Laravel format)
        else if (err.errors && typeof err.errors === 'object') {
            errorHtml += '<strong><i class="ti ti-alert-circle me-2"></i>Please fix the following errors:</strong><ul class="mb-0 mt-2">';
            
            for (const [field, messages] of Object.entries(err.errors)) {
                const fieldName = field.replace(/_/g, ' ').replace(/\b\w/g, l => l.toUpperCase());
                messages.forEach(message => {
                    errorHtml += `<li><strong>${fieldName}:</strong> ${message}</li>`;
                });
            }
            errorHtml += '</ul>';
        }
        // Check for string error message
        else if (err.message) {
            errorHtml += `
                <div class="d-flex align-items-center">
                    <i class="ti ti-alert-circle fs-4 me-2"></i>
                    <span>${err.message}</span>
                </div>
            `;
        }
        // Fallback for unknown error format
        else {
            errorHtml += `
                <div class="d-flex align-items-center">
                    <i class="ti ti-alert-circle fs-4 me-2"></i>
                    <span>An unknown error occurred. Please check your input and try again.</span>
                </div>
            `;
        }
        
        errorHtml += '</div>';
        errorContainer.innerHTML = errorHtml;
        errorContainer.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
    }

</script>
