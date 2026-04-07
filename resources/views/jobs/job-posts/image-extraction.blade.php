{{-- ═══════════════════════════════════════════════════════════════════
     IMAGE EXTRACT MODAL (MULTIPLE IMAGES SUPPORT ONLY)
═══════════════════════════════════════════════════════════════════ --}}
<div class="modal fade" id="imageExtractModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header bg-dark text-white">
                <h5 class="modal-title d-flex align-items-center gap-2">
                    <i class="ti ti-photo"></i> Image Job Extractor
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="row g-4">
                    <div class="col-md-5">
                        {{-- Image model selector --}}
                        <div class="mb-3">
                            <label class="form-label fw-semibold">AI Model for Image</label>
                            <select id="imgModel" class="form-select">
                                <option value="claude">Claude (Anthropic)</option>
                                <option value="openai">GPT-4 Vision (OpenAI)</option>
                                <option value="gemini">Gemini Vision (Google)</option>
                            </select>
                        </div>

                        {{-- Multiple image upload --}}
                        <div class="mb-3">
                            <label class="form-label fw-semibold">Upload Images (max 5)</label>
                            <div id="multiImgDropZone"
                                 class="border-2 border-dashed rounded-3 p-4 text-center bg-body-secondary"
                                 style="border-style:dashed!important;cursor:pointer;min-height:150px"
                                 onclick="document.getElementById('multiImgFileInput').click()"
                                 ondrop="handleMultiImgDrop(event)" ondragover="event.preventDefault()">
                                <i class="ti ti-cloud-upload fs-1 text-muted mb-2"></i>
                                <p class="mb-1 fw-semibold">Drop images here or click to browse</p>
                                <p class="text-muted small mb-0">JPG, PNG, WEBP — max 5MB each • Up to 5 images</p>
                            </div>
                            <input type="file" id="multiImgFileInput" accept="image/*" multiple style="display:none"
                                   onchange="handleMultiImgSelect(event)">
                        </div>

                        {{-- Image gallery --}}
                        <div id="imageGallery" class="mt-3">
                            <label class="form-label fw-semibold small">Image Gallery</label>
                            <div id="galleryContainer" class="d-flex flex-wrap gap-2" style="max-height: 300px; overflow-y: auto;"></div>
                        </div>

                        {{-- Image preview --}}
                        <div id="imgPreviewWrap" style="display:none" class="mt-3">
                            <label class="form-label fw-semibold small">Preview</label>
                            <img id="imgPreview" class="img-fluid rounded-2 border" alt="Preview">
                        </div>

                        {{-- Extract button --}}
                        <button class="btn btn-primary w-100 mt-3" onclick="extractFromMultipleImages()" id="multiImgExtractBtn">
                            <span id="multiImgExtractBtnText"><i class="ti ti-photo me-1"></i>Extract from All Images</span>
                            <span id="multiImgExtractBtnSpinner" class="spinner-border spinner-border-sm ms-1 d-none"></span>
                        </button>
                    </div>

                    <div class="col-md-7">
                        <label class="form-label fw-semibold">Extracted Data</label>
                        <div id="imgPreviewPanel"
                             class="border rounded-2 p-3 bg-body-secondary"
                             style="min-height:360px;max-height:480px;overflow-y:auto">
                            <div class="text-center text-muted py-5">
                                <i class="ti ti-photo-scan d-block fs-1 mb-3 opacity-25"></i>
                                <p>Upload multiple images of job postings and AI will extract all fields.</p>
                            </div>
                        </div>
                        <div class="mt-2 d-flex justify-content-between align-items-center">
                            <small class="text-muted" id="imgTokenInfo"></small>
                            <div class="form-check form-switch">
                                <input class="form-check-input" type="checkbox" id="imgAutoApplyToggle" checked>
                                <label class="form-check-label small" for="imgAutoApplyToggle">Auto-apply to form</label>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button class="btn btn-primary d-none" id="applyImgBtn" onclick="applyImageData()">
                    <i class="ti ti-check me-1"></i>Apply to Form
                </button>
            </div>
        </div>
    </div>
</div>

<style>
    .gallery-image-item {
        position: relative;
        width: 80px;
        height: 80px;
        cursor: pointer;
        border-radius: 8px;
        overflow: hidden;
        border: 2px solid transparent;
        transition: all 0.2s;
    }
    .gallery-image-item.selected {
        border-color: #0d6efd;
        box-shadow: 0 0 0 2px rgba(13,110,253,0.25);
    }
    .gallery-image-item img {
        width: 100%;
        height: 100%;
        object-fit: cover;
    }
    .gallery-image-item .remove-btn {
        position: absolute;
        top: 2px;
        right: 2px;
        background: rgba(0,0,0,0.6);
        color: white;
        border-radius: 50%;
        width: 20px;
        height: 20px;
        display: flex;
        align-items: center;
        justify-content: center;
        cursor: pointer;
        font-size: 12px;
        opacity: 0;
        transition: opacity 0.2s;
    }
    .gallery-image-item:hover .remove-btn {
        opacity: 1;
    }
</style>

<script>
    // ============================================================
    // MULTIPLE IMAGE EXTRACTION (ONLY)
    // ============================================================
    let uploadedImages = [];
    let selectedImageIndex = null;

    // Handle multiple image drop
    function handleMultiImgDrop(e) {
        e.preventDefault();
        const files = Array.from(e.dataTransfer.files);
        processMultipleImages(files);
    }

    function handleMultiImgSelect(e) {
        const files = Array.from(e.target.files);
        processMultipleImages(files);
    }

    function processMultipleImages(files) {
        const remainingSlots = 5 - uploadedImages.length;
        const validFiles = files.slice(0, remainingSlots).filter(file => {
            const isValidType = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'].includes(file.type);
            const isValidSize = file.size <= 5 * 1024 * 1024;
            if (!isValidType) toast(`${file.name}: Invalid format`, 'error');
            if (!isValidSize) toast(`${file.name}: Exceeds 5MB`, 'error');
            return isValidType && isValidSize;
        });

        validFiles.forEach(file => {
            const reader = new FileReader();
            reader.onload = (e) => {
                const base64 = e.target.result.split(',')[1];
                uploadedImages.push({
                    file: file,
                    base64: base64,
                    previewUrl: e.target.result,
                    extractedData: null
                });
                renderImageGallery();
            };
            reader.readAsDataURL(file);
        });

        if (validFiles.length === 0 && files.length > 0) {
            toast('No valid images added. Max 5 images, 5MB each, JPG/PNG/GIF/WEBP only.', 'warning');
        }
    }

    function renderImageGallery() {
        const container = document.getElementById('galleryContainer');
        if (!container) return;

        if (uploadedImages.length === 0) {
            container.innerHTML = '<div class="text-muted small p-2">No images uploaded. Click or drag to add images.</div>';
            return;
        }

        container.innerHTML = uploadedImages.map((img, idx) => `
            <div class="gallery-image-item ${selectedImageIndex === idx ? 'selected' : ''}" 
                 onclick="selectGalleryImage(${idx})">
                <img src="${img.previewUrl}" alt="Image ${idx + 1}">
                <div class="remove-btn" onclick="event.stopPropagation(); removeImage(${idx})">
                    <i class="ti ti-x"></i>
                </div>
                ${img.extractedData ? '<div class="position-absolute bottom-0 end-0 m-1"><i class="ti ti-check-circle text-success bg-white rounded-circle"></i></div>' : ''}
            </div>
        `).join('');
    }

    function selectGalleryImage(index) {
        selectedImageIndex = index;
        renderImageGallery();
        
        const preview = document.getElementById('imgPreview');
        if (preview && uploadedImages[index]) {
            preview.src = uploadedImages[index].previewUrl;
            document.getElementById('imgPreviewWrap').style.display = 'block';
        }
    }

    function removeImage(index) {
        if (uploadedImages[index].previewUrl && uploadedImages[index].previewUrl.startsWith('blob:')) {
            URL.revokeObjectURL(uploadedImages[index].previewUrl);
        }
        uploadedImages.splice(index, 1);
        if (selectedImageIndex >= uploadedImages.length) {
            selectedImageIndex = uploadedImages.length - 1;
        }
        if (uploadedImages.length === 0) {
            document.getElementById('imgPreviewWrap').style.display = 'none';
        } else if (selectedImageIndex >= 0) {
            selectGalleryImage(selectedImageIndex);
        }
        renderImageGallery();
    }

    function clearAllImages() {
        uploadedImages.forEach(img => {
            if (img.previewUrl && img.previewUrl.startsWith('blob:')) {
                URL.revokeObjectURL(img.previewUrl);
            }
        });
        uploadedImages = [];
        selectedImageIndex = null;
        renderImageGallery();
        document.getElementById('imgPreviewWrap').style.display = 'none';
        document.getElementById('multiImgFileInput').value = '';
    }

    // Helper function to extract error message from API response
    function extractErrorMessage(error) {
        // console.log('Extracting error from:', error);
        
        // If it's a string, return it
        if (typeof error === 'string') {
            return error;
        }
        
        // If it has a message property
        if (error.message) {
            return error.message;
        }
        
        // If it has an error property (Laravel/API format)
        if (error.error) {
            if (typeof error.error === 'string') return error.error;
            if (error.error.message) return error.error.message;
        }
        
        // If it has errors object (validation errors)
        if (error.errors) {
            const firstError = Object.values(error.errors)[0];
            if (Array.isArray(firstError) && firstError.length > 0) {
                return firstError[0];
            }
            if (typeof firstError === 'string') return firstError;
        }
        
        // Try to get the first string value from the object
        for (const key in error) {
            if (typeof error[key] === 'string' && error[key].length > 0) {
                return error[key];
            }
        }
        
        // Fallback
        return 'Extraction failed. Please try again.';
    }



    async function extractFromMultipleImages() {
        if (uploadedImages.length === 0) {
            toast('Please upload at least one image first.', 'error');
            return;
        }

        const applyBtn = document.getElementById('applyImgBtn');
        if (applyBtn) {
            applyBtn.style.display = 'inline-flex'; // or 'block' or remove the inline style
            applyBtn.style.removeProperty('display');
        }

        const model = document.getElementById('imgModel').value;
        const preview = document.getElementById('imgPreviewPanel');
        const btn = document.getElementById('multiImgExtractBtn');
        const spinner = document.getElementById('multiImgExtractBtnSpinner');
        const btnText = document.getElementById('multiImgExtractBtnText');

        btn.disabled = true;
        spinner.classList.remove('d-none');
        btnText.innerHTML = '<i class="ti ti-loader me-1"></i>Extracting...';
        preview.innerHTML = `<div class="text-center py-5"><div class="spinner-border text-primary mb-3"></div><p class="text-muted">Analyzing ${uploadedImages.length} image(s)...</p></div>`;

        try {
            let combinedData = {};
            
            for (let i = 0; i < uploadedImages.length; i++) {
                const img = uploadedImages[i];
                preview.innerHTML = `<div class="text-center py-5"><div class="spinner-border text-primary mb-3"></div><p class="text-muted">Processing image ${i + 1} of ${uploadedImages.length}...</p></div>`;
                
                const result = await callImageAiApi(model, img.base64);
                img.extractedData = result;
                combinedData = { ...combinedData, ...result };
            }
            
            extractedData = combinedData;
            renderImageExtractPreview(combinedData);
            
            const applyBtn = document.getElementById('applyImgBtn');
            if (applyBtn) applyBtn.style.display = 'block';
            
            const tokenInfo = document.getElementById('imgTokenInfo');
            if (tokenInfo) tokenInfo.textContent = `${model.toUpperCase()} — extracted from ${uploadedImages.length} image(s)`;
            
            if (document.getElementById('imgAutoApplyToggle')?.checked) {
                applyImageData();
                bsModal('imageExtractModal').hide();
                toast('Job data extracted and applied to form!', 'success');
            } else {
                toast(`Extracted data from ${uploadedImages.length} image(s). Review then apply.`, 'success');
            }
            
        } catch (e) {
            console.error('Extraction error:', e);
            
            // Extract the actual error message
            const errorMessage = extractErrorMessage(e);
            
            // Display the error in the preview panel
            preview.innerHTML = `
                <div class="alert alert-danger">
                    <i class="ti ti-alert-circle me-2"></i>
                    <strong>Extraction failed:</strong>
                    <div class="mt-1 small">${escapeHtml(errorMessage)}</div>
                    ${errorMessage.includes('API key') ? '<div class="mt-2 small text-muted">Please check your API key configuration in the .env file.</div>' : ''}
                </div>`;
            
            // Show toast with the same message
            toast(errorMessage, 'error');
        } finally {
            btn.disabled = false;
            spinner.classList.add('d-none');
            btnText.innerHTML = '<i class="ti ti-photo me-1"></i>Extract from All Images';
        }
    }

    async function callImageAiApi(model, imageBase64) {
        const response = await fetch('/ai/extract-image', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': CSRF_TOKEN
            },
            body: JSON.stringify({ model, image_base64: imageBase64 })
        });
        
        const result = await response.json();
        
        if (!response.ok) {
            // Throw the error object with the message
            throw result;
        }
        
        return result.data;
    }

    function renderImageExtractPreview(data) {
        // Reuse the same rendering function
        renderExtractedPreview(data);
        
        // Then copy to the image panel if needed
        const sourcePanel = document.getElementById('aiPreviewPanel');
        const targetPanel = document.getElementById('imgPreviewPanel');
        if (sourcePanel && targetPanel) {
            targetPanel.innerHTML = sourcePanel.innerHTML;
        }
    }

    function applyImageData() {
        if (!extractedData) {
            toast('No extracted data to apply.', 'warning');
            return;
        }
        
        // Call the main apply function
        if (typeof applyExtractedData === 'function') {
            applyExtractedData();
        } else {
            console.error('applyExtractedData function not found');
            toast('Error: Could not apply data to form.', 'error');
        }
    }

    function openImageExtractModal() {
        clearAllImages();
        bsModal('imageExtractModal').show();
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
</script>