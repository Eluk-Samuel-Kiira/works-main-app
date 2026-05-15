@extends('layouts.app')
@section('title', 'AI Job Posting - Stardena Works')

@section('app-content')
<div class="body-wrapper">
<div class="container-fluid">

{{-- Page Header --}}
<div class="mb-4">
    <div class="d-flex justify-content-between align-items-center">
        <div>
            <h4 class="fw-bold mb-0 d-flex align-items-center gap-2">
                <span class="badge bg-primary-subtle text-primary rounded-2 p-2">
                    <i class="ti ti-robot fs-5"></i>
                </span>
                AI Job Posting
            </h4>
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb mb-0 small">
                    <li class="breadcrumb-item"><a class="text-muted text-decoration-none" href="{{ route('dashboard') }}">Home</a></li>
                    <li class="breadcrumb-item text-muted">AI Job Posting</li>
                </ol>
            </nav>
        </div>
        <div class="d-flex gap-2">
            <button class="btn btn-outline-secondary" onclick="clearForm()">
                <i class="ti ti-trash me-1"></i> Clear Form
            </button>
            <button class="btn btn-outline-primary" onclick="openAiExtractModal()">
                <i class="ti ti-sparkles me-1"></i> AI Extract
            </button>
            <button class="btn btn-primary" id="submitJobBtn" onclick="submitJobPost()">
                <span id="submitJobBtnText"><i class="ti ti-send me-1"></i> Post Job</span>
                <span id="submitJobBtnSpinner" class="spinner-border spinner-border-sm ms-2 d-none"></span>
            </button>
        </div>
    </div>
</div>

{{-- AI Status Banner --}}
<div id="aiBanner" class="alert alert-info d-none align-items-center gap-2 mb-4" role="alert">
    <div class="spinner-border spinner-border-sm text-info flex-shrink-0"></div>
    <span id="aiBannerText">AI is processing your content...</span>
</div>

<form id="aiJobForm">
<input type="hidden" name="poster_id" value="{{ auth()->id() }}">
<input type="hidden" name="is_simple_job" value="0">

<div class="row g-4">

{{-- ═══════════════════════════════════════════════
     LEFT COLUMN — Main content
═══════════════════════════════════════════════ --}}
<div class="col-12 col-xl-8">

    {{-- ── BASIC INFO ── --}}
    <div class="card border-0 shadow-sm mb-4">
        <div class="card-header bg-body border-bottom d-flex align-items-center gap-2 py-3">
            <span class="badge bg-primary rounded-circle p-1" style="width:28px;height:28px;display:flex;align-items:center;justify-content:center">
                <i class="ti ti-info-circle" style="font-size:14px"></i>
            </span>
            <h6 class="mb-0 fw-semibold">Basic Information</h6>
        </div>
        <div class="card-body">
            <div class="row g-3">
                <div class="col-12">
                    <label class="form-label fw-semibold">Job Title <span class="text-danger">*</span></label>
                    <input type="text" name="job_title" id="f_job_title" class="form-control form-control-lg"
                           placeholder="e.g. Senior Software Engineer">
                </div>

                <div class="col-md-7">
                    <label class="form-label fw-semibold">Company <span class="text-danger">*</span></label>
                    <div class="position-relative">
                        <div class="input-group">
                            <input type="text" id="f_company_input" class="form-control" placeholder="Type to search..." autocomplete="off">
                            <button class="btn btn-outline-primary" type="button" onclick="openQuickCompanyModal('f_company_input', 'f_company_id')" title="Add new company">
                                <i class="ti ti-plus"></i>
                            </button>
                        </div>
                        <input type="hidden" name="company_id" id="f_company_id">
                        <ul class="dropdown-menu w-100" id="f_company_list" style="max-height:220px;overflow-y:auto"></ul>
                    </div>
                </div>
                <div class="col-md-5">
                    <label class="form-label fw-semibold">Category <span class="text-danger">*</span></label>
                    <div class="position-relative">
                        <input type="text" id="f_category_input" class="form-control" placeholder="Type to search..." autocomplete="off">
                        <input type="hidden" name="job_category_id" id="f_category_id">
                        <ul class="dropdown-menu w-100" id="f_category_list" style="max-height:220px;overflow-y:auto"></ul>
                    </div>
                </div>
                <div class="col-md-6">
                    <label class="form-label fw-semibold">Industry <span class="text-danger">*</span></label>
                    <div class="position-relative">
                        <input type="text" id="f_industry_input" class="form-control" placeholder="Type to search..." autocomplete="off">
                        <input type="hidden" name="industry_id" id="f_industry_id">
                        <ul class="dropdown-menu w-100" id="f_industry_list" style="max-height:220px;overflow-y:auto"></ul>
                    </div>
                </div>
                <div class="col-md-6">
                    <label class="form-label fw-semibold">Location <span class="text-danger">*</span></label>
                    <div class="position-relative">
                        <div class="input-group">
                            <input type="text" id="f_location_input" class="form-control" placeholder="Type to search..." autocomplete="off">
                            <button class="btn btn-outline-primary" type="button" onclick="openQuickLocationModal('f_location_input', 'f_location_id')" title="Add new location">
                                <i class="ti ti-plus"></i>
                            </button>
                        </div>
                        <input type="hidden" name="job_location_id" id="f_location_id">
                        <ul class="dropdown-menu w-100" id="f_location_list" style="max-height:220px;overflow-y:auto"></ul>
                    </div>
                </div>
                <div class="col-md-4">
                    <label class="form-label fw-semibold">Job Type <span class="text-danger">*</span></label>
                    <div class="position-relative">
                        <input type="text" id="f_jobtype_input" class="form-control" placeholder="Type to search..." autocomplete="off">
                        <input type="hidden" name="job_type_id" id="f_jobtype_id">
                        <ul class="dropdown-menu w-100" id="f_jobtype_list" style="max-height:220px;overflow-y:auto"></ul>
                    </div>
                </div>
                <div class="col-md-4">
                    <label class="form-label fw-semibold">Employment Type <span class="text-danger">*</span></label>
                    <select name="employment_type" id="f_employment_type" class="form-select">
                        <option value="full-time">Full-time</option>
                        <option value="part-time">Part-time</option>
                        <option value="contract">Contract</option>
                        <option value="internship">Internship</option>
                        <option value="volunteer">Volunteer</option>
                        <option value="temporary">Temporary</option>
                    </select>
                </div>
                <div class="col-md-4">
                    <label class="form-label fw-semibold">Location Type</label>
                    <select name="location_type" id="f_location_type" class="form-select">
                        <option value="on-site">On-site</option>
                        <option value="remote">Remote</option>
                        <option value="hybrid">Hybrid</option>
                    </select>
                </div>
                <div class="col-md-6">
                    <label class="form-label fw-semibold">Duty Station</label>
                    <input type="text" name="duty_station" id="f_duty_station" class="form-control" placeholder="e.g. Kampala Head Office">
                </div>
                <div class="col-md-6">
                    <label class="form-label fw-semibold">Application Deadline <span class="text-danger">*</span></label>
                    <div class="input-group">
                        <input type="text" name="deadline" id="f_deadline" class="form-control datepicker-autoclose" placeholder="mm/dd/yyyy">
                        <span class="input-group-text"><i class="ti ti-calendar fs-5"></i></span>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- ── JOB DESCRIPTION ── --}}
    <div class="card border-0 shadow-sm mb-4">
        <div class="card-header bg-body border-bottom d-flex align-items-center justify-content-between py-3">
            <div class="d-flex align-items-center gap-2">
                <span class="badge bg-success rounded-circle p-1" style="width:28px;height:28px;display:flex;align-items:center;justify-content:center">
                    <i class="ti ti-file-description" style="font-size:14px"></i>
                </span>
                <h6 class="mb-0 fw-semibold">Job Description</h6>
            </div>
            <button type="button"
                    id="btn-enhance-job_description"
                    class="btn btn-sm btn-outline-primary"
                    onclick="aiEnhanceField('job_description','Enhance and professionally rewrite this job description for SEO and clarity. Use clean HTML with <p> tags.')">
                <i class="ti ti-sparkles me-1"></i>AI Enhance
            </button>
        </div>
        <div class="card-body">
            <x-rich-editor id="f_job_description_editor" name="job_description" :height="220"/>
            <div class="d-flex justify-content-between mt-2">
                <small class="text-muted">Describe the role, company culture, and what makes this opportunity special.</small>
                <small id="descCharCount" class="text-muted">0 chars</small>
            </div>
        </div>
    </div>

    {{-- ── RESPONSIBILITIES ── --}}
    <div class="card border-0 shadow-sm mb-4">
        <div class="card-header bg-body border-bottom d-flex align-items-center justify-content-between py-3">
            <div class="d-flex align-items-center gap-2">
                <span class="badge bg-warning rounded-circle p-1" style="width:28px;height:28px;display:flex;align-items:center;justify-content:center">
                    <i class="ti ti-list-check" style="font-size:14px"></i>
                </span>
                <h6 class="mb-0 fw-semibold">Key Responsibilities</h6>
            </div>
            <button type="button"
                    id="btn-enhance-responsibilities"
                    class="btn btn-sm btn-outline-primary"
                    onclick="aiEnhanceField('responsibilities','Rewrite as a clear, action-oriented HTML <ul><li> list of 6-8 key responsibilities. Each item should start with a strong verb.')">
                <i class="ti ti-sparkles me-1"></i>AI Format
            </button>
        </div>
        <div class="card-body">
            <x-rich-editor id="f_responsibilities_editor" name="responsibilities_display" :height="180"/>
            <input type="hidden" name="responsibilities" id="f_responsibilities">
        </div>
    </div>

    {{-- ── QUALIFICATIONS ── --}}
    <div class="card border-0 shadow-sm mb-4">
        <div class="card-header bg-body border-bottom d-flex align-items-center justify-content-between py-3">
            <div class="d-flex align-items-center gap-2">
                <span class="badge bg-info rounded-circle p-1" style="width:28px;height:28px;display:flex;align-items:center;justify-content:center">
                    <i class="ti ti-certificate" style="font-size:14px"></i>
                </span>
                <h6 class="mb-0 fw-semibold">Qualifications</h6>
            </div>
            <button type="button"
                    id="btn-enhance-qualifications"
                    class="btn btn-sm btn-outline-primary"
                    onclick="aiEnhanceField('qualifications','Rewrite as a professional HTML <ul><li> list with Required and Preferred sections. Be specific and clear.')">
                <i class="ti ti-sparkles me-1"></i>AI Format
            </button>
        </div>
        <div class="card-body">
            <x-rich-editor id="f_qualifications_editor" name="qualifications_display" :height="160"/>
            <input type="hidden" name="qualifications" id="f_qualifications">
        </div>
    </div>

    {{-- ── SKILLS ── --}}
    <div class="card border-0 shadow-sm mb-4">
        <div class="card-header bg-body border-bottom d-flex align-items-center justify-content-between py-3">
            <div class="d-flex align-items-center gap-2">
                <span class="badge bg-purple rounded-circle p-1" style="width:28px;height:28px;display:flex;align-items:center;justify-content:center;background:#7c3aed!important">
                    <i class="ti ti-tools" style="font-size:14px"></i>
                </span>
                <h6 class="mb-0 fw-semibold">Required Skills</h6>
            </div>
            <button type="button"
                    id="btn-enhance-skills"
                    class="btn btn-sm btn-outline-primary"
                    onclick="aiEnhanceField('skills','Extract and list all relevant technical and soft skills as a clean comma-separated list. Include 8-12 skills total.')">
                <i class="ti ti-sparkles me-1"></i>AI Extract
            </button>
        </div>
        <div class="card-body">
            <x-rich-editor id="f_skills_editor" name="skills_display" :height="120"/>
            <input type="hidden" name="skills" id="f_skills">
            <small class="text-muted">Tip: Separate skills with commas for proper display as tags on the frontend.</small>
        </div>
    </div>

    {{-- ── APPLICATION PROCEDURE ── --}}
    <div class="card border-0 shadow-sm mb-4">
        <div class="card-header bg-body border-bottom d-flex align-items-center justify-content-between py-3">
            <div class="d-flex align-items-center gap-2">
                <span class="badge bg-danger rounded-circle p-1" style="width:28px;height:28px;display:flex;align-items:center;justify-content:center">
                    <i class="ti ti-send" style="font-size:14px"></i>
                </span>
                <h6 class="mb-0 fw-semibold">Application Procedure</h6>
            </div>
            <button type="button"
                    id="btn-enhance-application_procedure"
                    class="btn btn-sm btn-outline-primary"
                    onclick="aiEnhanceField('application_procedure','Rewrite these application instructions clearly and professionally. Include any email, URL, or deadline mentioned.')">
                <i class="ti ti-sparkles me-1"></i>AI Rewrite
            </button>
        </div>
        <div class="card-body">
            <input type="text" name="application_procedure" id="f_application_procedure"
                   class="form-control"
                   placeholder="e.g. Send CV to hr@company.com or visit https://apply.company.com">
            <small class="text-muted mt-1 d-block">Include a URL where candidates should apply.</small>
        </div>
    </div>

    {{-- ── SEO METADATA ── --}}
    <div class="card border-0 shadow-sm mb-4">
        <div class="card-header bg-body border-bottom d-flex align-items-center justify-content-between py-3">
            <div class="d-flex align-items-center gap-2">
                <span class="badge bg-secondary rounded-circle p-1" style="width:28px;height:28px;display:flex;align-items:center;justify-content:center">
                    <i class="ti ti-seo" style="font-size:14px"></i>
                </span>
                <h6 class="mb-0 fw-semibold">SEO Metadata <small class="text-muted fw-normal">(auto-generated if empty)</small></h6>
            </div>
            <button type="button" class="btn btn-sm btn-outline-secondary" onclick="toggleSeo()">
                <i class="ti ti-chevron-down" id="seoChevron"></i>
            </button>
        </div>
        <div class="card-body" id="seoBody" style="display:none">
            <div class="row g-3">
                <div class="col-12">
                    <label class="form-label small fw-semibold">Meta Title <span class="text-muted">(50-60 chars)</span></label>
                    <input type="text" name="meta_title" id="f_meta_title" class="form-control"
                           placeholder="Auto-generated from job title + company + location" maxlength="60">
                    <div class="d-flex justify-content-end mt-1">
                        <small id="metaTitleCount" class="text-muted">0/60</small>
                    </div>
                </div>
                <div class="col-12">
                    <label class="form-label small fw-semibold">Meta Description <span class="text-muted">(150-160 chars)</span></label>
                    <textarea name="meta_description" id="f_meta_description" class="form-control" rows="2"
                              placeholder="Auto-generated from job description" maxlength="160"></textarea>
                    <div class="d-flex justify-content-end mt-1">
                        <small id="metaDescCount" class="text-muted">0/160</small>
                    </div>
                </div>
                <div class="col-12">
                    <label class="form-label small fw-semibold">Keywords</label>
                    <input type="text" name="keywords" id="f_keywords" class="form-control"
                           placeholder="Auto-generated keywords">
                </div>
            </div>
        </div>
    </div>

</div>

{{-- ═══════════════════════════════════════════════
     RIGHT SIDEBAR
═══════════════════════════════════════════════ --}}
<div class="col-12 col-xl-4">

    {{-- ── AI QUICK ACTIONS ── --}}
    <div class="card border-0 shadow-sm mb-4" style="background:linear-gradient(135deg,#667eea 0%,#764ba2 100%)">
        <div class="card-body text-white p-4">
            <div class="d-flex align-items-center gap-2 mb-3">
                <i class="ti ti-robot fs-4"></i>
                <h6 class="mb-0 fw-bold">AI Assistant</h6>
            </div>
            <p class="small mb-3 opacity-75">Use AI to extract job data from any source — website content, PDF text, or images.</p>
            <div class="d-grid gap-2">
                <button type="button" class="btn btn-white text-dark fw-semibold"
                        onclick="openAiExtractModal()"
                        style="background:#fff">
                    <i class="ti ti-clipboard-text me-2"></i>Paste & Extract
                </button>
                <button type="button" class="btn btn-outline-light fw-semibold"
                        onclick="openImageExtractModal()">
                    <i class="ti ti-photo me-2"></i>Image Extract
                </button>
                <button type="button" class="btn btn-outline-light fw-semibold"
                        onclick="aiGenerateFullPost()">
                    <i class="ti ti-sparkles me-2"></i>Generate from Title
                </button>
            </div>
        </div>
    </div>

    {{-- ── SALARY ── --}}
    <div class="card border-0 shadow-sm mb-4">
        <div class="card-header bg-body border-bottom py-3">
            <h6 class="mb-0 fw-semibold d-flex align-items-center gap-2">
                <i class="ti ti-coin text-success"></i> Salary Information
            </h6>
        </div>
        <div class="card-body">
            <div class="row g-3">
                <div class="col-12">
                    <label class="form-label small fw-semibold">Salary Range</label>
                    <div class="position-relative">
                        <input type="text" id="f_salaryrange_input" class="form-control" placeholder="Select salary range..." autocomplete="off">
                        <input type="hidden" name="salary_range_id" id="f_salaryrange_id">
                        <ul class="dropdown-menu w-100" id="f_salaryrange_list" style="max-height:200px;overflow-y:auto"></ul>
                    </div>
                </div>
                <div class="col-7">
                    <label class="form-label small fw-semibold">Amount</label>
                    <input type="number" name="salary_amount" id="f_salary_amount" class="form-control" placeholder="0">
                </div>
                <div class="col-5">
                    <label class="form-label small fw-semibold">Currency</label>
                    <input type="text" name="currency" id="f_currency" class="form-control" value="UGX">
                </div>
                <div class="col-12">
                    <label class="form-label small fw-semibold">Payment Period</label>
                    <select name="payment_period" id="f_payment_period" class="form-select">
                        <option value="">— Select —</option>
                        <option value="monthly" selected>Monthly</option>
                        <option value="yearly">Yearly</option>
                        <option value="weekly">Weekly</option>
                        <option value="daily">Daily</option>
                        <option value="hourly">Hourly</option>
                    </select>
                </div>
            </div>
        </div>
    </div>

    {{-- ── EXPERIENCE & EDUCATION ── --}}
    <div class="card border-0 shadow-sm mb-4">
        <div class="card-header bg-body border-bottom py-3">
            <h6 class="mb-0 fw-semibold d-flex align-items-center gap-2">
                <i class="ti ti-school text-info"></i> Requirements
            </h6>
        </div>
        <div class="card-body">
            <div class="row g-3">
                <div class="col-12">
                    <label class="form-label small fw-semibold">Experience Level <span class="text-danger">*</span></label>
                    <div class="position-relative">
                        <input type="text" id="f_experience_input" class="form-control" placeholder="Type to search..." autocomplete="off">
                        <input type="hidden" name="experience_level_id" id="f_experience_id">
                        <ul class="dropdown-menu w-100" id="f_experience_list" style="max-height:200px;overflow-y:auto"></ul>
                    </div>
                </div>
                <div class="col-12">
                    <label class="form-label small fw-semibold">Education Level <span class="text-danger">*</span></label>
                    <div class="position-relative">
                        <input type="text" id="f_education_input" class="form-control" placeholder="Type to search..." autocomplete="off">
                        <input type="hidden" name="education_level_id" id="f_education_id">
                        <ul class="dropdown-menu w-100" id="f_education_list" style="max-height:200px;overflow-y:auto"></ul>
                    </div>
                </div>
                <div class="col-12">
                    <label class="form-label small fw-semibold">Work Hours</label>
                    <input type="text" name="work_hours" id="f_work_hours" class="form-control" placeholder="e.g. 8am–5pm Mon–Fri">
                </div>
            </div>
        </div>
    </div>

    {{-- ── CONTACT ── --}}
    <div class="card border-0 shadow-sm mb-4">
        <div class="card-header bg-body border-bottom py-3">
            <h6 class="mb-0 fw-semibold d-flex align-items-center gap-2">
                <i class="ti ti-phone text-primary"></i> Contact Information
            </h6>
        </div>
        <div class="card-body">
            <div class="row g-3">
                <div class="col-12">
                    <label class="form-label small fw-semibold">Contact Email</label>
                    <input type="email" name="email" id="f_email" class="form-control" placeholder="hr@company.com">
                </div>
                <div class="col-12">
                    <label class="form-label small fw-semibold">Telephone</label>
                    <input type="text" name="telephone" id="f_telephone" class="form-control" placeholder="+256 700 000 000">
                </div>
                <div class="col-12">
                    <div class="d-flex flex-column gap-2">
                        <div class="form-check form-switch">
                            <input class="form-check-input" type="checkbox" name="is_whatsapp_contact" id="f_whatsapp">
                            <label class="form-check-label small" for="f_whatsapp">WhatsApp contact</label>
                        </div>
                        <div class="form-check form-switch">
                            <input class="form-check-input" type="checkbox" name="is_telephone_call" id="f_telcall">
                            <label class="form-check-label small" for="f_telcall">Phone call OK</label>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- ── FLAGS & REQUIREMENTS ── --}}
    <div class="card border-0 shadow-sm mb-4">
        <div class="card-header bg-body border-bottom py-3">
            <h6 class="mb-0 fw-semibold d-flex align-items-center gap-2">
                <i class="ti ti-flag text-warning"></i> Flags & Requirements
            </h6>
        </div>
        <div class="card-body">
            <div class="row g-2">
                <div class="col-6">
                    <div class="form-check form-switch">
                        <input class="form-check-input" type="checkbox" name="is_featured" id="f_featured">
                        <label class="form-check-label small" for="f_featured">Featured</label>
                    </div>
                </div>
                <div class="col-6">
                    <div class="form-check form-switch">
                        <input class="form-check-input" type="checkbox" name="is_urgent" id="f_urgent">
                        <label class="form-check-label small" for="f_urgent">Urgent</label>
                    </div>
                </div>
                <div class="col-6">
                    <div class="form-check form-switch">
                        <input class="form-check-input" type="checkbox" name="is_quick_gig" id="f_quickgig">
                        <label class="form-check-label small" for="f_quickgig">Quick Gig</label>
                    </div>
                </div>
                <div class="col-6">
                    <div class="form-check form-switch">
                        <input class="form-check-input" type="checkbox" name="is_verified" id="f_verified">
                        <label class="form-check-label small" for="f_verified">Pre-verify</label>
                    </div>
                </div>
                <div class="col-12"><hr class="my-2"></div>
                <div class="col-12">
                    <p class="small fw-semibold text-muted mb-2">Application Requirements</p>
                </div>
                <div class="col-6">
                    <div class="form-check form-switch">
                        <input class="form-check-input" type="checkbox" name="is_resume_required" id="f_resume" checked>
                        <label class="form-check-label small" for="f_resume">Resume/CV</label>
                    </div>
                </div>
                <div class="col-6">
                    <div class="form-check form-switch">
                        <input class="form-check-input" type="checkbox" name="is_cover_letter_required" id="f_cover">
                        <label class="form-check-label small" for="f_cover">Cover Letter</label>
                    </div>
                </div>
                <div class="col-6">
                    <div class="form-check form-switch">
                        <input class="form-check-input" type="checkbox" name="is_academic_documents_required" id="f_academic">
                        <label class="form-check-label small" for="f_academic">Academic Docs</label>
                    </div>
                </div>
                <div class="col-6">
                    <div class="form-check form-switch">
                        <input class="form-check-input" type="checkbox" name="is_application_required" id="f_appletter">
                        <label class="form-check-label small" for="f_appletter">App. Letter</label>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- ── POSTING TIPS ── --}}
    <div class="card border-0 bg-body-secondary mb-4">
        <div class="card-body p-3">
            <h6 class="fw-semibold small mb-2 d-flex align-items-center gap-2">
                <i class="ti ti-bulb text-warning"></i> Posting Tips
            </h6>
            <ul class="list-unstyled mb-0 small text-muted">
                <li class="mb-1"><i class="ti ti-check text-success me-1"></i>Use specific job titles for better SEO</li>
                <li class="mb-1"><i class="ti ti-check text-success me-1"></i>Include salary to get 3x more applicants</li>
                <li class="mb-1"><i class="ti ti-check text-success me-1"></i>List 5-8 key responsibilities</li>
                <li class="mb-1"><i class="ti ti-check text-success me-1"></i>Mention company culture and benefits</li>
                <li class="mb-1"><i class="ti ti-check text-success me-1"></i>Set a clear application deadline</li>
                <li><i class="ti ti-check text-success me-1"></i>Verify before posting for instant visibility</li>
            </ul>
        </div>
    </div>

    {{-- ── SUBMIT ── --}}
    <div class="d-grid gap-2">
        <button type="button" class="btn btn-primary btn-lg fw-semibold" id="submitJobBtn" onclick="submitJobPost()">
            <span id="submitJobBtnText"><i class="ti ti-send me-2"></i>Post Job Now</span>
            <span id="submitJobBtnSpinner" class="spinner-border spinner-border-sm ms-2 d-none"></span>
        </button>
        <button type="button" class="btn btn-outline-secondary" id="submitDraftBtn" onclick="submitJobPost('draft')">
            <span id="submitDraftBtnText"><i class="ti ti-device-floppy me-2"></i>Save as Draft</span>
            <span id="submitDraftBtnSpinner" class="spinner-border spinner-border-sm ms-2 d-none"></span>
        </button>
    </div>

    <div id="formErrors" class="mt-3"></div>

</div>
</div>
</form>

</div>
</div>

{{-- ═══════════════════════════════════════════════════════════════════
     AI TEXT EXTRACT MODAL
═══════════════════════════════════════════════════════════════════ --}}
<div class="modal fade" id="aiExtractModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-xl modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header" style="background:linear-gradient(135deg,#667eea,#764ba2)">
                <h5 class="modal-title text-white d-flex align-items-center gap-2">
                    <i class="ti ti-sparkles"></i> AI Job Extractor
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="row g-4">

                    {{-- Left: Input --}}
                    <div class="col-md-6">

                        {{-- Model selector --}}
                        <div class="mb-3">
                            <label class="form-label fw-semibold">AI Model</label>
                            <div class="row g-2" id="modelSelector">
                                @php
                                $models = [
                                    ['id'=>'claude','label'=>'Claude','icon'=>'ti-message-2','color'=>'#d97757'],  
                                    ['id'=>'openai','label'=>'OpenAI GPT','icon'=>'ti-cpu','color'=>'#10a37f'],    
                                    ['id'=>'gemini','label'=>'Gemini','icon'=>'ti-planet','color'=>'#4285f4'],     
                                    ['id'=>'grok','label'=>'Grok','icon'=>'ti-rocket','color'=>'#1da1f2'],         
                                    ['id'=>'cohere','label'=>'Cohere','icon'=>'ti-palette','color'=>'#d4a017'],   
                                    ['id'=>'mistral','label'=>'Mistral','icon'=>'ti-cloud','color'=>'#ff7000'],     
                                ];
                                @endphp
                                @foreach($models as $m)
                                <div class="col-6 col-md-4">
                                    <div class="model-card border rounded-2 p-2 text-center cursor-pointer {{ $m['id']==='claude' ? 'border-primary bg-primary-subtle' : '' }}"
                                         data-model="{{ $m['id'] }}"
                                         onclick="selectModel(this,'{{ $m['id'] }}')"
                                         style="cursor:pointer;transition:all .15s">
                                        <i class="ti {{ $m['icon'] }} d-block mb-1" style="color:{{ $m['color'] }};font-size:1.25rem"></i>
                                        <small class="fw-semibold">{{ $m['label'] }}</small>
                                    </div>
                                </div>
                                @endforeach
                            </div>
                            <input type="hidden" id="selectedModel" value="claude">
                        </div>

                        {{-- API Key 
                        <div class="mb-3" id="apiKeyRow">
                            <label class="form-label fw-semibold small">API Key <span class="text-muted">(not stored)</span></label>
                            <div class="input-group">
                                <input type="password" id="aiApiKey" class="form-control form-control-sm"
                                       placeholder="Paste your API key here...">
                                <button class="btn btn-outline-secondary btn-sm" type="button"
                                        onclick="toggleApiKeyVisibility()">
                                    <i class="ti ti-eye" id="apiKeyEyeIcon"></i>
                                </button>
                            </div>
                            <small class="text-muted">Your key is never saved — used only for this session.</small>
                        </div>
                        --}}

                        {{-- Source type tabs --}}
                        <div class="mb-3">
                            <label class="form-label fw-semibold">Source Type</label>
                            <div class="btn-group w-100" role="group">
                                <input type="radio" class="btn-check" name="sourceType" id="srcText" value="text" checked>
                                <label class="btn btn-outline-primary btn-sm" for="srcText">
                                    <i class="ti ti-clipboard-text me-1"></i>Pasted Text
                                </label>
                                <input type="radio" class="btn-check" name="sourceType" id="srcUrl" value="url">
                                <label class="btn btn-outline-primary btn-sm" for="srcUrl">
                                    <i class="ti ti-link me-1"></i>Job URL
                                </label>
                            </div>
                        </div>

                        {{-- Text input --}}
                        <div id="textSourcePanel">
                            <label class="form-label fw-semibold small">Paste Job Content</label>
                            <textarea id="aiSourceText" class="form-control" rows="12"
                                placeholder="Paste the full job posting text here — from a website, email, PDF, WhatsApp message, or anywhere.

                                The AI will extract:
                                • Job title
                                • Company name
                                • Description
                                • Responsibilities
                                • Qualifications
                                • Skills
                                • Salary
                                • Deadline
                                • Contact details
                                • Application procedure">
                            </textarea>
                        </div>

                        {{-- URL input --}}
                        <div id="urlSourcePanel" style="display:none">
                            <label class="form-label fw-semibold small">Job URL</label>
                            <input type="url" id="aiSourceUrl" class="form-control"
                                   placeholder="https://company.com/careers/job-title">
                            <small class="text-muted">The AI will attempt to extract job data from the page content.</small>
                        </div>
                    </div>

                    {{-- Right: Preview --}}
                    <div class="col-md-6">
                        <label class="form-label fw-semibold">Extracted Data Preview</label>
                        <div id="aiPreviewPanel"
                             class="border rounded-2 p-3 bg-body-secondary"
                             style="min-height:420px;max-height:520px;overflow-y:auto">
                            <div class="text-center text-muted py-5">
                                <i class="ti ti-robot d-block fs-1 mb-3 opacity-25"></i>
                                <p class="mb-0">Extracted fields will appear here for review before applying to the form.</p>
                            </div>
                        </div>
                        <div class="mt-2 d-flex justify-content-between align-items-center">
                            <small class="text-muted" id="aiTokenInfo"></small>
                            <div class="form-check form-switch">
                                <input class="form-check-input" type="checkbox" id="autoApplyToggle" checked>
                                <label class="form-check-label small" for="autoApplyToggle">Auto-apply to form</label>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button class="btn btn-outline-primary" onclick="extractJobData()" id="extractBtn">
                    <span id="extractBtnText"><i class="ti ti-sparkles me-1"></i>Extract Data</span>
                    <span id="extractBtnSpinner" class="spinner-border spinner-border-sm ms-1 d-none"></span>
                </button>
                <button class="btn btn-primary" id="applyExtractedBtn" onclick="applyExtractedData()" style="display:none">
                    <i class="ti ti-check me-1"></i>Apply to Form
                </button>
            </div>
        </div>
    </div>
</div>
<script>
    /* Text wrapping utilities */
    .text-break {
        word-break: break-word;
        overflow-wrap: break-word;
    }

    .word-wrap {
        word-wrap: break-word;
        white-space: normal;
    }

    /* For the preview panel */
    #aiPreviewPanel .bg-body-rounded {
        transition: all 0.2s ease;
    }

    #aiPreviewPanel .bg-body-rounded:hover {
        background-color: #f8f9fa;
    }

    /* Ensure long words break properly */
    .min-w-0 {
        min-width: 0;
    }

    .flex-grow-1 {
        flex-grow: 1;
    }

    /* For the description text */
    .description-text {
        display: -webkit-box;
        -webkit-line-clamp: 3;
        -webkit-box-orient: vertical;
        overflow: hidden;
        text-overflow: ellipsis;
    }
</script>


@include('jobs.job-posts.image-extraction')

@include('jobs.job-posts.ai-posting-js')
@include('jobs.job-posts.quick-company')
@include('jobs.job-posts.quick-location')
@endsection