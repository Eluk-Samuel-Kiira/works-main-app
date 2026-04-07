{{-- Quick Company Creation Modal --}}
<div class="modal fade" id="quickCompanyModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header bg-primary text-white">
                <h5 class="modal-title"><i class="ti ti-building me-2"></i>Add New Company</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <form id="quickCompanyForm" onsubmit="return false;">
                    <div class="row g-3">
                        <div class="col-12">
                            <label class="form-label">Company Name <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" id="quickCompanyName" required>
                        </div>
                        
                        {{-- Typable Industry Dropdown --}}
                        <div class="col-md-6">
                            <label class="form-label">Industry</label>
                            <div class="position-relative">
                                <input type="text" id="quickCompanyIndustryInput" class="form-control" placeholder="Type to search industry..." autocomplete="off">
                                <input type="hidden" id="quickCompanyIndustryId">
                                <ul class="dropdown-menu w-100" id="quickCompanyIndustryList" style="max-height: 200px; overflow-y: auto;"></ul>
                            </div>
                        </div>
                        
                        {{-- Typable Location Dropdown --}}
                        <div class="col-md-6">
                            <label class="form-label">Location</label>
                            <div class="position-relative">
                                <input type="text" id="quickCompanyLocationInput" class="form-control" placeholder="Type to search location..." autocomplete="off">
                                <input type="hidden" id="quickCompanyLocationId">
                                <ul class="dropdown-menu w-100" id="quickCompanyLocationList" style="max-height: 200px; overflow-y: auto;"></ul>
                            </div>
                        </div>
                        
                        <div class="col-12">
                            <label class="form-label">Description</label>
                            <textarea class="form-control" id="quickCompanyDescription" rows="2"></textarea>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Website</label>
                            <input type="url" class="form-control" id="quickCompanyWebsite" placeholder="https://example.com">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Company Logo <span class="text-danger">*</span></label>
                            <div class="input-group">
                                <input type="file" id="quickCompanyLogo" class="form-control" accept="image/jpeg,image/png,image/gif,image/webp" onchange="previewQuickLogo()">
                                <button class="btn btn-outline-secondary" type="button" onclick="clearQuickLogo()">Clear</button>
                            </div>
                            <small class="form-text text-muted">Max 2MB • Formats: JPG, PNG, GIF, WebP</small>
                            <div id="quickLogoPreview" class="mt-2"></div>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Contact Name</label>
                            <input type="text" class="form-control" id="quickCompanyContactName">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Contact Email</label>
                            <input type="email" class="form-control" id="quickCompanyContactEmail">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Contact Phone</label>
                            <input type="text" class="form-control" id="quickCompanyContactPhone">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Address</label>
                            <input type="text" class="form-control" id="quickCompanyAddress">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Company Size</label>
                            <input type="text" class="form-control" id="quickCompanySize" placeholder="e.g. 50-200">
                        </div>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button class="btn btn-primary" id="quickCompanySaveBtn" onclick="submitQuickCompany()">
                    <span id="quickCompanyBtnText"><i class="ti ti-building me-1"></i>Create Company</span>
                    <span id="quickCompanyBtnSpinner" class="spinner-border spinner-border-sm ms-2 d-none"></span>
                </button>
            </div>
        </div>
    </div>
</div>

<style>
    .input-group .btn-outline-primary {
        border-top-left-radius: 0;
        border-bottom-left-radius: 0;
        border-left: none;
    }

    .input-group .form-control:focus + .btn-outline-primary {
        border-color: #86b7fe;
    }
    
    .btn:disabled {
        opacity: 0.7;
        cursor: not-allowed;
    }
    
    .btn .spinner-border {
        vertical-align: middle;
    }
    
    .dropdown-menu {
        z-index: 1060;
    }
</style>

<script>
    // ============================================================
    // QUICK COMPANY MODAL - TYPABLE DROPDOWNS
    // ============================================================
    let quickCompanyIndustryItems = [];
    let quickCompanyLocationItems = [];
    let quickIndustryDropdown = null;
    let quickLocationDropdown = null;
    let pendingCompanyInputId = null;
    let pendingCompanyHiddenId = null;

    // TypableDropdown class for quick modal
    class QuickTypableDropdown {
        constructor(config) {
            this.inputEl = document.getElementById(config.inputId);
            this.hiddenEl = document.getElementById(config.hiddenId);
            this.listEl = document.getElementById(config.listId);
            this.items = [];
            this.displayKey = config.displayKey || 'name';
            this.valueKey = config.valueKey || 'id';
            this.formatItem = config.formatItem || null;
            this.onSelect = config.onSelect || null;
            this.init();
        }
        
        init() {
            if (!this.listEl) return;
            this.listEl.classList.add('show');
            this.listEl.style.display = 'none';
            this.inputEl.addEventListener('input', () => this.filter());
            this.inputEl.addEventListener('focus', () => this.show());
            this.inputEl.addEventListener('blur', () => setTimeout(() => this.hide(), 200));
            document.addEventListener('click', (e) => {
                if (!this.inputEl.contains(e.target) && !this.listEl.contains(e.target)) this.hide();
            });
        }
        
        setItems(items) { 
            this.items = items; 
            this.render();
        }
        
        filter() {
            const term = this.inputEl.value.toLowerCase();
            const filtered = this.items.filter(i => this.getText(i).toLowerCase().includes(term));
            this.render(filtered);
            this.show();
        }
        
        getText(item) {
            if (this.formatItem) return this.formatItem(item);
            if (typeof this.displayKey === 'function') return this.displayKey(item);
            return item[this.displayKey] || '';
        }
        
        render(items = null) {
            if (!this.listEl) return;
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
            if (this.onSelect) this.onSelect(item);
            this.hiddenEl.dispatchEvent(new Event('change', { bubbles: true }));
        }
        
        show() { 
            if (this.listEl && this.items.length > 0) { 
                this.listEl.style.display = 'block'; 
                this.listEl.classList.add('show'); 
            } 
        }
        
        hide() { 
            if (this.listEl) {
                this.listEl.style.display = 'none'; 
                this.listEl.classList.remove('show'); 
            }
        }
        
        reset() {
            this.inputEl.value = '';
            this.hiddenEl.value = '';
            this.render();
            this.hide();
        }
        
        setValue(id, label = null) {
            const found = this.items.find(i => String(i[this.valueKey]) === String(id));
            if (found) this.select(found);
            else if (label) { this.inputEl.value = label; this.hiddenEl.value = id; }
        }
    }

    async function loadQuickCompanyDropdowns() {
        try {
            // Load industries
            const industriesRes = await fetch('/api/v1/industries?per_page=200&is_active=1', {
                headers: { 'Accept': 'application/json', 'X-CSRF-TOKEN': CSRF_TOKEN }
            });
            const industries = await industriesRes.json();
            quickCompanyIndustryItems = industries.data || [];
            
            // Load locations
            const locationsRes = await fetch('/api/v1/job-locations?per_page=200&is_active=1', {
                headers: { 'Accept': 'application/json', 'X-CSRF-TOKEN': CSRF_TOKEN }
            });
            const locations = await locationsRes.json();
            quickCompanyLocationItems = locations.data || [];
            
            // Initialize typable dropdowns
            quickIndustryDropdown = new QuickTypableDropdown({
                inputId: 'quickCompanyIndustryInput',
                hiddenId: 'quickCompanyIndustryId',
                listId: 'quickCompanyIndustryList',
                displayKey: 'name',
                valueKey: 'id'
            });
            quickIndustryDropdown.setItems(quickCompanyIndustryItems);
            
            quickLocationDropdown = new QuickTypableDropdown({
                inputId: 'quickCompanyLocationInput',
                hiddenId: 'quickCompanyLocationId',
                listId: 'quickCompanyLocationList',
                displayKey: (item) => `${item.country} - ${item.district}`,
                valueKey: 'id'
            });
            quickLocationDropdown.setItems(quickCompanyLocationItems);
            
        } catch (e) {
            console.error('Failed to load dropdowns:', e);
        }
    }

    // Logo preview for quick modal
    function previewQuickLogo() {
        const file = document.getElementById('quickCompanyLogo').files[0];
        const preview = document.getElementById('quickLogoPreview');
        if (!file) { 
            preview.innerHTML = ''; 
            return; 
        }
        
        // Validate file size (2MB)
        if (file.size > 2 * 1024 * 1024) {
            if (typeof toast === 'function') {
                toast('Logo file must be less than 2MB', 'error');
            } else {
                alert('Logo file must be less than 2MB');
            }
            document.getElementById('quickCompanyLogo').value = '';
            preview.innerHTML = '';
            return;
        }
        
        // Validate file type
        const allowedTypes = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
        if (!allowedTypes.includes(file.type)) {
            if (typeof toast === 'function') {
                toast('Logo must be JPG, PNG, GIF, or WebP format', 'error');
            } else {
                alert('Logo must be JPG, PNG, GIF, or WebP format');
            }
            document.getElementById('quickCompanyLogo').value = '';
            preview.innerHTML = '';
            return;
        }
        
        const reader = new FileReader();
        reader.onload = (e) => {
            preview.innerHTML = `<img src="${e.target.result}" class="img-thumbnail" style="max-height:100px" alt="Logo Preview">`;
        };
        reader.readAsDataURL(file);
    }

    function clearQuickLogo() {
        document.getElementById('quickCompanyLogo').value = '';
        document.getElementById('quickLogoPreview').innerHTML = '';
    }

    function openQuickCompanyModal(inputId, hiddenId) {
        pendingCompanyInputId = inputId;
        pendingCompanyHiddenId = hiddenId;
        
        // Reset form
        const form = document.getElementById('quickCompanyForm');
        if (form) form.reset();
        
        if (quickIndustryDropdown) quickIndustryDropdown.reset();
        if (quickLocationDropdown) quickLocationDropdown.reset();
        clearQuickLogo();
        
        // Pre-fill company name from input if exists
        const companyInput = document.getElementById(inputId);
        if (companyInput && companyInput.value) {
            document.getElementById('quickCompanyName').value = companyInput.value;
        }
        
        // Reset button state
        const btn = document.getElementById('quickCompanySaveBtn');
        const spinner = document.getElementById('quickCompanyBtnSpinner');
        const btnText = document.getElementById('quickCompanyBtnText');
        if (btn) btn.disabled = false;
        if (spinner) spinner.classList.add('d-none');
        if (btnText) btnText.innerHTML = '<i class="ti ti-building me-1"></i>Create Company';
        
        const modal = document.getElementById('quickCompanyModal');
        if (modal) {
            const bsModalInstance = bootstrap.Modal.getOrCreateInstance(modal);
            bsModalInstance.show();
        }
    }

    async function submitQuickCompany() {
        const btn = document.getElementById('quickCompanySaveBtn');
        const spinner = document.getElementById('quickCompanyBtnSpinner');
        const btnText = document.getElementById('quickCompanyBtnText');
        
        // Store original text
        const originalText = btnText.innerHTML;
        
        // Disable button and show spinner
        btn.disabled = true;
        spinner.classList.remove('d-none');
        btnText.innerHTML = '<i class="ti ti-loader me-1"></i>Creating...';
        
        try {
            const formData = new FormData();
            
            const name = document.getElementById('quickCompanyName').value.trim();
            if (!name) {
                if (typeof toast === 'function') {
                    toast('Company name is required', 'error');
                } else {
                    alert('Company name is required');
                }
                btn.disabled = false;
                spinner.classList.add('d-none');
                btnText.innerHTML = originalText;
                return;
            }
            formData.append('name', name);
            
            const industryId = document.getElementById('quickCompanyIndustryId').value;
            if (industryId) formData.append('industry_id', industryId);
            
            const locationId = document.getElementById('quickCompanyLocationId').value;
            if (locationId) formData.append('location_id', locationId);
            
            const description = document.getElementById('quickCompanyDescription').value.trim();
            if (description) formData.append('description', description);
            
            const website = document.getElementById('quickCompanyWebsite').value.trim();
            if (website) formData.append('website', website);
            
            const contactName = document.getElementById('quickCompanyContactName').value.trim();
            if (contactName) formData.append('contact_name', contactName);
            
            const contactEmail = document.getElementById('quickCompanyContactEmail').value.trim();
            if (contactEmail) formData.append('contact_email', contactEmail);
            
            const contactPhone = document.getElementById('quickCompanyContactPhone').value.trim();
            if (contactPhone) formData.append('contact_phone', contactPhone);
            
            const address = document.getElementById('quickCompanyAddress').value.trim();
            if (address) formData.append('address1', address);
            
            const companySize = document.getElementById('quickCompanySize').value.trim();
            if (companySize) formData.append('company_size', companySize);
            
            const logo = document.getElementById('quickCompanyLogo').files[0];
            if (logo) {
                formData.append('logo', logo);
            } else {
                if (typeof toast === 'function') {
                    toast('Company logo is required', 'error');
                } else {
                    alert('Company logo is required');
                }
                btn.disabled = false;
                spinner.classList.add('d-none');
                btnText.innerHTML = originalText;
                return;
            }
            
            formData.append('is_active', '1');
            formData.append('is_verified', '0');
            
            const response = await fetch('/api/v1/companies', {
                method: 'POST',
                body: formData,
                headers: { 'Accept': 'application/json', 'X-CSRF-TOKEN': CSRF_TOKEN }
            });
            
            const result = await response.json();
            
            if (!response.ok) {
                throw result;
            }
            
            const newCompany = result.data;
            
            // Add the new company to the main dropdown items
            if (typeof drops !== 'undefined' && drops.company && newCompany) {
                const newItem = {
                    id: newCompany.id,
                    name: newCompany.name
                };
                drops.company.items.unshift(newItem);
                drops.company.render();
            }
            
            // Also update simple posting dropdown if it exists
            if (typeof simpleDrops !== 'undefined' && simpleDrops.company && newCompany) {
                simpleDrops.company.items.unshift(newItem);
                simpleDrops.company.render();
            }
            
            // Also refresh the quick modal dropdowns
            await loadQuickCompanyDropdowns();
            
            // Set the value in the original company input
            const companyInput = document.getElementById(pendingCompanyInputId);
            const companyHidden = document.getElementById(pendingCompanyHiddenId);
            
            if (companyInput) companyInput.value = newCompany.name;
            if (companyHidden) companyHidden.value = newCompany.id;
            
            // Trigger change event
            if (companyHidden) {
                companyHidden.dispatchEvent(new Event('change', { bubbles: true }));
            }
            
            if (typeof toast === 'function') {
                toast('Company created successfully!', 'success');
            } else {
                alert('Company created successfully!');
            }
            
            // Close modal
            const modal = document.getElementById('quickCompanyModal');
            if (modal) {
                const bsModalInstance = bootstrap.Modal.getInstance(modal);
                if (bsModalInstance) bsModalInstance.hide();
            }
            
        } catch (e) {
            const msg = e.errors ? Object.values(e.errors).flat().join('<br>') : (e.message || 'Failed to create company');
            if (typeof toast === 'function') {
                toast(msg, 'error');
            } else {
                alert(msg);
            }
        } finally {
            btn.disabled = false;
            spinner.classList.add('d-none');
            btnText.innerHTML = '<i class="ti ti-building me-1"></i>Create Company';
        }
    }

    // Initialize quick modal dropdowns when DOM is ready
    document.addEventListener('DOMContentLoaded', () => {
        if (document.getElementById('quickCompanyModal')) {
            loadQuickCompanyDropdowns();
            
            // Reset form when modal is hidden
            const modal = document.getElementById('quickCompanyModal');
            if (modal) {
                modal.addEventListener('hidden.bs.modal', () => {
                    const form = document.getElementById('quickCompanyForm');
                    if (form) form.reset();
                    clearQuickLogo();
                    if (quickIndustryDropdown) quickIndustryDropdown.reset();
                    if (quickLocationDropdown) quickLocationDropdown.reset();
                    
                    // Reset button state
                    const btn = document.getElementById('quickCompanySaveBtn');
                    const spinner = document.getElementById('quickCompanyBtnSpinner');
                    const btnText = document.getElementById('quickCompanyBtnText');
                    if (btn) btn.disabled = false;
                    if (spinner) spinner.classList.add('d-none');
                    if (btnText) btnText.innerHTML = '<i class="ti ti-building me-1"></i>Create Company';
                });
            }
        }
    });
</script>