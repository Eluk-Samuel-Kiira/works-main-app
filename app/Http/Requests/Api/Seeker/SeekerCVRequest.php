<?php
// app/Http/Requests/Api/Seeker/SeekerCVRequest.php

namespace App\Http\Requests\Api\Seeker;

use Illuminate\Foundation\Http\FormRequest;

class SeekerCVRequest extends FormRequest
{
    // ─────────────────────────────────────────────────────────────────────
    // JSON fields the blade sends as JSON strings via FormData.
    // They must be decoded into real PHP arrays BEFORE validation runs.
    // ─────────────────────────────────────────────────────────────────────
    private const JSON_FIELDS = [
        'skills',
        'languages',
        'work_experience',
        'education',
        'certifications',
        'projects',
        'job_preferences',
    ];

    public function authorize(): bool
    {
        return true;
    }

    // ─────────────────────────────────────────────────────────────────────
    // This runs BEFORE rules() — decode JSON strings into real arrays so
    // Laravel's array validation rules see the correct types.
    // ─────────────────────────────────────────────────────────────────────
    protected function prepareForValidation(): void
    {
        $merges = [];

        foreach (self::JSON_FIELDS as $field) {
            $value = $this->input($field);

            if (!is_string($value) || $value === '') {
                continue;
            }

            $decoded = json_decode($value, true);

            $merges[$field] = (json_last_error() === JSON_ERROR_NONE && is_array($decoded))
                ? $decoded
                : [];   // invalid JSON → empty array so validation can report it cleanly
        }

        if (!empty($merges)) {
            $this->merge($merges);
        }
    }

    public function rules(): array
    {
        $isUpdate = $this->isMethod('PATCH') || $this->isMethod('PUT');
        $req = fn(string ...$extra) => $isUpdate
            ? ['sometimes', 'required', ...$extra]
            : ['required', ...$extra];

        return [
            // Personal Information
            'first_name'          => $req('string', 'max:100'),
            'last_name'           => $req('string', 'max:100'),
            'email'               => $req('email', 'max:255'),
            'phone'               => ['nullable', 'string', 'max:20'],
            'address'             => ['nullable', 'string', 'max:255'],
            'city'                => ['nullable', 'string', 'max:100'],
            'country'             => ['nullable', 'string', 'max:100'],
            'postal_code'         => ['nullable', 'string', 'max:20'],
            'date_of_birth'       => ['nullable', 'date', 'before:today'],
            'nationality'         => ['nullable', 'string', 'max:100'],

            // Professional
            'professional_summary' => ['nullable', 'string', 'max:5000'],
            'professional_title'   => ['nullable', 'string', 'max:255'],
            'years_of_experience'  => ['nullable', 'integer', 'min:0', 'max:50'],

            // Social Links
            'linkedin_url'  => ['nullable', 'url', 'max:255'],
            'github_url'    => ['nullable', 'url', 'max:255'],
            'portfolio_url' => ['nullable', 'url', 'max:255'],

            // Skills — now a real array after prepareForValidation()
            'skills'   => ['nullable', 'array'],
            'skills.*' => ['string', 'max:100'],

            // Languages
            'languages'               => ['nullable', 'array'],
            'languages.*.name'        => ['required_with:languages', 'string', 'max:100'],
            'languages.*.proficiency' => ['required_with:languages', 'string', 'in:basic,conversational,professional,native'],

            // Certifications
            'certifications'                  => ['nullable', 'array'],
            'certifications.*.name'           => ['required_with:certifications', 'string', 'max:255'],
            'certifications.*.issuer'         => ['nullable', 'string', 'max:255'],
            'certifications.*.date'           => ['nullable', 'date'],
            'certifications.*.expiry'         => ['nullable', 'date'],
            'certifications.*.credential_id'  => ['nullable', 'string', 'max:100'],

            // Education
            'education'                    => ['nullable', 'array'],
            'education.*.degree'           => ['required_with:education', 'string', 'max:255'],
            'education.*.institution'      => ['required_with:education', 'string', 'max:255'],
            'education.*.field_of_study'   => ['nullable', 'string', 'max:255'],
            'education.*.start_date'       => ['required_with:education', 'date'],
            'education.*.end_date'         => ['nullable', 'date'],
            'education.*.current'          => ['nullable', 'boolean'],
            'education.*.grade'            => ['nullable', 'string', 'max:50'],
            'education.*.description'      => ['nullable', 'string', 'max:1000'],

            // Work Experience
            'work_experience'                  => ['nullable', 'array'],
            'work_experience.*.job_title'      => ['required_with:work_experience', 'string', 'max:255'],
            'work_experience.*.company'        => ['required_with:work_experience', 'string', 'max:255'],
            'work_experience.*.location'       => ['nullable', 'string', 'max:255'],
            'work_experience.*.employment_type'=> ['nullable', 'string', 'max:50'],
            'work_experience.*.start_date'     => ['required_with:work_experience', 'date'],
            'work_experience.*.end_date'       => ['nullable', 'date'],
            'work_experience.*.current'        => ['nullable', 'boolean'],
            'work_experience.*.description'    => ['nullable', 'string', 'max:5000'],

            // Projects
            'projects'                  => ['nullable', 'array'],
            'projects.*.name'           => ['required_with:projects', 'string', 'max:255'],
            'projects.*.description'    => ['nullable', 'string', 'max:2000'],
            'projects.*.technologies'   => ['nullable', 'array'],
            'projects.*.technologies.*' => ['string', 'max:100'],
            'projects.*.url'            => ['nullable', 'url'],
            'projects.*.start_date'     => ['nullable', 'date'],
            'projects.*.end_date'       => ['nullable', 'date'],

            // Job Preferences
            'job_preferences'                     => ['nullable', 'array'],
            'job_preferences.job_types'           => ['nullable', 'array'],
            'job_preferences.job_types.*'         => ['string', 'in:full-time,part-time,contract,internship,remote'],
            'job_preferences.locations'           => ['nullable', 'array'],
            'job_preferences.locations.*'         => ['string', 'max:100'],
            'job_preferences.salary_min'          => ['nullable', 'integer', 'min:0'],
            'job_preferences.salary_max'          => ['nullable', 'integer', 'min:0'],
            'job_preferences.remote_only'         => ['nullable', 'boolean'],
            'job_preferences.industries'          => ['nullable', 'array'],
            'job_preferences.industries.*'        => ['string', 'max:100'],

            // CV File
            'cv_file'   => ['nullable', 'file', 'mimes:pdf,doc,docx', 'max:5120'],
            'remove_cv' => ['nullable', 'boolean'],

            // Settings
            'is_public' => ['nullable', 'boolean'],
            'is_active' => ['nullable', 'boolean'],
        ];
    }

    public function attributes(): array
    {
        return [
            'first_name'                   => 'first name',
            'last_name'                    => 'last name',
            'professional_summary'         => 'professional summary',
            'professional_title'           => 'professional title',
            'years_of_experience'          => 'years of experience',
            'work_experience'              => 'work experience',
            'work_experience.*.job_title'  => 'job title',
            'work_experience.*.company'    => 'company name',
            'work_experience.*.start_date' => 'start date',
            'work_experience.*.description'=> 'job description',
            'education.*.degree'           => 'degree',
            'education.*.institution'      => 'institution',
            'education.*.start_date'       => 'start date',
        ];
    }

    public function messages(): array
    {
        return [
            'cv_file.mimes'                        => 'The CV must be a PDF, DOC, or DOCX file.',
            'cv_file.max'                          => 'The CV must not be larger than 5MB.',
            'date_of_birth.before'                 => 'Date of birth must be in the past.',
            'work_experience.*.end_date.after'     => 'End date must be after start date.',
            'education.*.end_date.after'           => 'End date must be after start date.',
            'job_preferences.salary_max.min'       => 'Maximum salary must be a positive number.',
        ];
    }
}