{{-- Quick Location Creation Modal --}}
<div class="modal fade" id="quickLocationModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header bg-primary text-white">
                <h5 class="modal-title"><i class="ti ti-map-pin me-2"></i>Add New Location</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <form id="quickLocationForm" onsubmit="return false;">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label">Country <span class="text-danger">*</span></label>
                            <select class="form-select" id="quickLocationCountry" required>
                                <option value="">Select Country</option>
                                <option value="UG">Uganda (UG)</option>
                                <option value="KE">Kenya (KE)</option>
                                <option value="TZ">Tanzania (TZ)</option>
                                <option value="NG">Nigeria (NG)</option>
                                <option value="ZA">South Africa (ZA)</option>
                                <option value="GH">Ghana (GH)</option>
                                <option value="RW">Rwanda (RW)</option>
                                <option value="SS">South Sudan (SS)</option>
                                <option value="CD">DR Congo (CD)</option>
                                <option value="ET">Ethiopia (ET)</option>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">District <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" id="quickLocationDistrict" required placeholder="e.g. Kampala">
                        </div>
                        <div class="col-12">
                            <label class="form-label">Description</label>
                            <textarea class="form-control" id="quickLocationDescription" rows="2"></textarea>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Meta Title</label>
                            <input type="text" class="form-control" id="quickLocationMetaTitle" placeholder="SEO Title">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Meta Description</label>
                            <input type="text" class="form-control" id="quickLocationMetaDescription" placeholder="SEO Description">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Sort Order</label>
                            <input type="number" class="form-control" id="quickLocationSortOrder" value="0" min="0">
                        </div>
                        <div class="col-md-6">
                            <div class="form-check form-switch mt-4">
                                <input class="form-check-input" type="checkbox" id="quickLocationIsActive" checked>
                                <label class="form-check-label" for="quickLocationIsActive">Active</label>
                            </div>
                        </div>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button class="btn btn-primary" id="quickLocationSaveBtn" onclick="submitQuickLocation()">
                    <span id="quickLocationBtnText"><i class="ti ti-map-pin me-1"></i>Create Location</span>
                    <span id="quickLocationBtnSpinner" class="spinner-border spinner-border-sm ms-2 d-none"></span>
                </button>
            </div>
        </div>
    </div>
</div>


<script>
    // ============================================================
    // QUICK LOCATION MODAL
    // ============================================================
    let pendingLocationInputId = null;
    let pendingLocationHiddenId = null;

    function openQuickLocationModal(inputId, hiddenId) {
        pendingLocationInputId = inputId;
        pendingLocationHiddenId = hiddenId;
        
        // Reset form
        const form = document.getElementById('quickLocationForm');
        if (form) form.reset();
        
        // Reset to default values
        document.getElementById('quickLocationSortOrder').value = '0';
        document.getElementById('quickLocationIsActive').checked = true;
        
        // Pre-fill from input if exists
        const locationInput = document.getElementById(inputId);
        if (locationInput && locationInput.value) {
            // Try to parse district and country from input value
            const value = locationInput.value;
            const parts = value.split(',');
            if (parts.length === 2) {
                document.getElementById('quickLocationDistrict').value = parts[0].trim();
                // Try to find country code
                const countryCode = parts[1].trim().slice(0, 2).toUpperCase();
                if (countryCode) {
                    const countrySelect = document.getElementById('quickLocationCountry');
                    for (let i = 0; i < countrySelect.options.length; i++) {
                        if (countrySelect.options[i].value === countryCode) {
                            countrySelect.value = countryCode;
                            break;
                        }
                    }
                }
            } else {
                document.getElementById('quickLocationDistrict').value = value;
            }
        }
        
        // Reset button state
        const btn = document.getElementById('quickLocationSaveBtn');
        const spinner = document.getElementById('quickLocationBtnSpinner');
        const btnText = document.getElementById('quickLocationBtnText');
        if (btn) btn.disabled = false;
        if (spinner) spinner.classList.add('d-none');
        if (btnText) btnText.innerHTML = '<i class="ti ti-map-pin me-1"></i>Create Location';
        
        const modal = document.getElementById('quickLocationModal');
        if (modal) {
            const bsModalInstance = bootstrap.Modal.getOrCreateInstance(modal);
            bsModalInstance.show();
        }
    }

    async function submitQuickLocation() {
        const btn = document.getElementById('quickLocationSaveBtn');
        const spinner = document.getElementById('quickLocationBtnSpinner');
        const btnText = document.getElementById('quickLocationBtnText');
        
        // Store original text
        const originalText = btnText.innerHTML;
        
        // Disable button and show spinner
        btn.disabled = true;
        spinner.classList.remove('d-none');
        btnText.innerHTML = '<i class="ti ti-loader me-1"></i>Creating...';
        
        try {
            const country = document.getElementById('quickLocationCountry').value;
            const district = document.getElementById('quickLocationDistrict').value.trim();
            
            if (!country) {
                toast('Please select a country', 'error');
                btn.disabled = false;
                spinner.classList.add('d-none');
                btnText.innerHTML = originalText;
                return;
            }
            
            if (!district) {
                toast('District is required', 'error');
                btn.disabled = false;
                spinner.classList.add('d-none');
                btnText.innerHTML = originalText;
                return;
            }
            
            const payload = {
                country: country,
                district: district,
                description: document.getElementById('quickLocationDescription').value.trim() || null,
                meta_title: document.getElementById('quickLocationMetaTitle').value.trim() || null,
                meta_description: document.getElementById('quickLocationMetaDescription').value.trim() || null,
                sort_order: parseInt(document.getElementById('quickLocationSortOrder').value) || 0,
                is_active: document.getElementById('quickLocationIsActive').checked,
            };
            
            const response = await fetch('/api/v1/job-locations', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': CSRF_TOKEN
                },
                body: JSON.stringify(payload)
            });
            
            const result = await response.json();
            
            if (!response.ok) {
                throw result;
            }
            
            const newLocation = result.data;
            
            // Add the new location to the main dropdown items
            if (typeof dropdowns !== 'undefined' && dropdowns.job_location && newLocation) {
                const newItem = {
                    id: newLocation.id,
                    district: newLocation.district,
                    country: newLocation.country
                };
                const displayText = `${newLocation.district}, ${newLocation.country}`;
                newItem.displayText = displayText;
                
                dropdowns.job_location.items.unshift(newItem);
                dropdowns.job_location.render();
            }
            
            // Also update the quick modal dropdown if exists
            if (typeof quickLocationDropdown !== 'undefined' && quickLocationDropdown) {
                const newItem = {
                    id: newLocation.id,
                    district: newLocation.district,
                    country: newLocation.country,
                    displayText: `${newLocation.district}, ${newLocation.country}`
                };
                quickLocationDropdown.items.unshift(newItem);
                quickLocationDropdown.render();
            }
            
            // Set the value in the original location input
            const locationInput = document.getElementById(pendingLocationInputId);
            const locationHidden = document.getElementById(pendingLocationHiddenId);
            
            const displayValue = `${newLocation.district}, ${newLocation.country}`;
            
            if (locationInput) locationInput.value = displayValue;
            if (locationHidden) locationHidden.value = newLocation.id;
            
            // Trigger change event
            if (locationHidden) {
                locationHidden.dispatchEvent(new Event('change', { bubbles: true }));
            }
            
            toast('Location created successfully!', 'success');
            
            // Close modal
            const modal = document.getElementById('quickLocationModal');
            if (modal) {
                const bsModalInstance = bootstrap.Modal.getInstance(modal);
                if (bsModalInstance) bsModalInstance.hide();
            }
            
        } catch (e) {
            let errorMsg = e.message || 'Failed to create location';
            
            // Handle duplicate location error
            if (errorMsg.includes('Duplicate entry') || errorMsg.includes('already exists')) {
                errorMsg = `Location "${document.getElementById('quickLocationDistrict').value}" already exists in the selected country.`;
            }
            
            if (e.errors) {
                const errors = Object.values(e.errors).flat();
                errorMsg = errors.join('<br>');
            }
            
            toast(errorMsg, 'error');
        } finally {
            btn.disabled = false;
            spinner.classList.add('d-none');
            btnText.innerHTML = originalText;
        }
    }
</script>