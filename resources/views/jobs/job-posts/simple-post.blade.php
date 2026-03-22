{{-- ============================================================ CREATE MODAL ============================================================ --}}
<div class="modal fade" id="createModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-xl modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><i class="ti ti-plus me-2"></i>Post a New Job</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <form id="createForm">
                    <div class="row g-3">

                        {{-- Job Title --}}
                        <div class="col-12">
                            <label class="form-label fw-semibold">Job Title <span class="text-danger">*</span></label>
                            <input type="text" name="job_title" id="c_job_title" class="form-control"
                                placeholder="e.g. Senior Software Engineer" required>
                        </div>

                        {{-- Description with lightweight formatter --}}
                        <div class="col-12">
                            <label class="form-label fw-semibold">Job Description <span class="text-danger">*</span></label>
                            <div class="border rounded overflow-hidden">
                                {{-- Toolbar --}}
                                <div class="d-flex flex-wrap gap-1 p-2 border-bottom bg-light" id="descToolbar">
                                    <button type="button" class="btn btn-sm btn-light border" onclick="descFmt('bold')" title="Bold"><b>B</b></button>
                                    <button type="button" class="btn btn-sm btn-light border fst-italic" onclick="descFmt('italic')" title="Italic"><i>I</i></button>
                                    <button type="button" class="btn btn-sm btn-light border" onclick="descFmt('underline')" title="Underline"><u>U</u></button>
                                    <div class="vr mx-1"></div>
                                    <button type="button" class="btn btn-sm btn-light border" onclick="descFmt('insertUnorderedList')" title="Bullet list">&#8226; List</button>
                                    <button type="button" class="btn btn-sm btn-light border" onclick="descFmt('insertOrderedList')" title="Numbered list">1. List</button>
                                    <div class="vr mx-1"></div>
                                    <button type="button" class="btn btn-sm btn-light border" onclick="descFmt('formatBlock','h3')" title="Heading">H</button>
                                    <button type="button" class="btn btn-sm btn-light border" onclick="descFmt('formatBlock','p')" title="Paragraph">P</button>
                                    <div class="vr mx-1"></div>
                                    <button type="button" class="btn btn-sm btn-light border" onclick="descFmt('removeFormat')" title="Clear format">✕</button>
                                </div>
                                {{-- Editable area --}}
                                <div id="descEditor"
                                    contenteditable="true"
                                    class="p-3"
                                    style="min-height:160px;max-height:300px;overflow-y:auto;outline:none;font-size:14px;line-height:1.6"
                                    data-placeholder="Describe the role, responsibilities, and what makes this opportunity exciting…">
                                </div>
                            </div>
                            <input type="hidden" name="job_description" id="c_job_description">
                        </div>

                        {{-- Contact row --}}
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Contact Email</label>
                            <input type="email" name="email" class="form-control" placeholder="hr@company.com">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Telephone</label>
                            <input type="text" name="telephone" class="form-control" placeholder="+256 700 000 000">
                        </div>

                        <div class="col-12"><hr class="my-1"></div>

                        {{-- Dropdowns row 1 --}}
                        <div class="col-md-4">
                            <label class="form-label fw-semibold">Company <span class="text-danger">*</span></label>
                            <select name="company_id" id="c_company_id" class="form-select" required>
                                <option value="">— Select company —</option>
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label fw-semibold">Category <span class="text-danger">*</span></label>
                            <select name="job_category_id" id="c_job_category_id" class="form-select" required>
                                <option value="">— Select category —</option>
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label fw-semibold">Industry <span class="text-danger">*</span></label>
                            <select name="industry_id" id="c_industry_id" class="form-select" required>
                                <option value="">— Select industry —</option>
                            </select>
                        </div>

                        {{-- Dropdowns row 2 --}}
                        <div class="col-md-4">
                            <label class="form-label fw-semibold">Location <span class="text-danger">*</span></label>
                            <select name="job_location_id" id="c_job_location_id" class="form-select" required>
                                <option value="">— Select location —</option>
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label fw-semibold">Job Type <span class="text-danger">*</span></label>
                            <select name="job_type_id" id="c_job_type_id" class="form-select" required>
                                <option value="">— Select type —</option>
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label fw-semibold">Employment <span class="text-danger">*</span></label>
                            <select name="employment_type" class="form-select">
                                <option value="full-time" selected>Full-time</option>
                                <option value="part-time">Part-time</option>
                                <option value="contract">Contract</option>
                                <option value="internship">Internship</option>
                                <option value="volunteer">Volunteer</option>
                                <option value="temporary">Temporary</option>
                            </select>
                        </div>

                        {{-- Dropdowns row 3 --}}
                        <div class="col-md-4">
                            <label class="form-label fw-semibold">Experience Level <span class="text-danger">*</span></label>
                            <select name="experience_level_id" id="c_experience_level_id" class="form-select" required>
                                <option value="">— Select level —</option>
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label fw-semibold">Education Level <span class="text-danger">*</span></label>
                            <select name="education_level_id" id="c_education_level_id" class="form-select" required>
                                <option value="">— Select education —</option>
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label fw-semibold">Location Type</label>
                            <select name="location_type" class="form-select">
                                <option value="on-site" selected>On-site</option>
                                <option value="remote">Remote</option>
                                <option value="hybrid">Hybrid</option>
                            </select>
                        </div>

                        {{-- Deadline + duty station --}}
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Application Deadline <span class="text-danger">*</span></label>
                            <input type="date" name="deadline" class="form-control" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Duty Station</label>
                            <input type="text" name="duty_station" class="form-control" placeholder="e.g. Kampala Head Office">
                        </div>

                        <div class="col-12"><hr class="my-1"></div>

                        {{-- Flags --}}
                        <div class="col-12">
                            <label class="form-label fw-semibold d-block mb-2">Application Requirements & Flags</label>
                            <div class="row g-2">
                                <div class="col-6 col-md-3">
                                    <div class="form-check form-switch">
                                        <input class="form-check-input" type="checkbox" name="is_resume_required" id="c_resume" checked>
                                        <label class="form-check-label" for="c_resume">Resume required</label>
                                    </div>
                                </div>
                                <div class="col-6 col-md-3">
                                    <div class="form-check form-switch">
                                        <input class="form-check-input" type="checkbox" name="is_cover_letter_required" id="c_cover">
                                        <label class="form-check-label" for="c_cover">Cover letter</label>
                                    </div>
                                </div>
                                <div class="col-6 col-md-3">
                                    <div class="form-check form-switch">
                                        <input class="form-check-input" type="checkbox" name="is_academic_documents_required" id="c_academic">
                                        <label class="form-check-label" for="c_academic">Academic docs</label>
                                    </div>
                                </div>
                                <div class="col-6 col-md-3">
                                    <div class="form-check form-switch">
                                        <input class="form-check-input" type="checkbox" name="is_application_required" id="c_applic">
                                        <label class="form-check-label" for="c_applic">Application letter</label>
                                    </div>
                                </div>
                                <div class="col-6 col-md-3">
                                    <div class="form-check form-switch">
                                        <input class="form-check-input" type="checkbox" name="is_whatsapp_contact" id="c_wa">
                                        <label class="form-check-label" for="c_wa">WhatsApp contact</label>
                                    </div>
                                </div>
                                <div class="col-6 col-md-3">
                                    <div class="form-check form-switch">
                                        <input class="form-check-input" type="checkbox" name="is_telephone_call" id="c_tel">
                                        <label class="form-check-label" for="c_tel">Phone call OK</label>
                                    </div>
                                </div>
                                <div class="col-6 col-md-3">
                                    <div class="form-check form-switch">
                                        <input class="form-check-input" type="checkbox" name="is_featured" id="c_featured">
                                        <label class="form-check-label" for="c_featured">Featured</label>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div id="createFormErrors"></div>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button class="btn btn-primary" id="createSaveBtn" onclick="submitCreate()">
                    <span id="createBtnText">Post Job</span>
                    <span id="createBtnSpinner" class="spinner-border spinner-border-sm ms-1 d-none"></span>
                </button>
            </div>
        </div>
    </div>
</div>

@include('jobs.job-posts.simple-post-js')