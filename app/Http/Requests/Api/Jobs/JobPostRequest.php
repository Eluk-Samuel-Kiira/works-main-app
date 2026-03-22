<?php

namespace App\Http\Requests\Api\Jobs;

use Illuminate\Foundation\Http\FormRequest;

class JobPostRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $isUpdate = $this->isMethod('PATCH') || $this->isMethod('PUT');
        $required = $isUpdate ? 'sometimes|required' : 'required';

        return [
            // ----------------------------------------------------------------
            // Core Job Information
            // ----------------------------------------------------------------
            'job_title'             => "{$required}|string|max:255",
            'job_description'       => "{$required}|string",
            'responsibilities'      => 'nullable|string',
            'skills'                => 'nullable|string',
            'qualifications'        => 'nullable|string',
            'deadline'              => "{$required}|date|after_or_equal:today",
            'application_procedure' => 'nullable|string|max:255',
            'email'                 => 'nullable|email|max:255',
            'telephone'             => 'nullable|string|max:50',

            // ----------------------------------------------------------------
            // Relationships — migration: foreignId columns
            // ----------------------------------------------------------------
            'company_id'          => "{$required}|integer|exists:companies,id",
            'job_category_id'     => "{$required}|integer|exists:job_categories,id",
            'industry_id'         => "{$required}|integer|exists:industries,id",
            'job_location_id'     => "{$required}|integer|exists:job_locations,id",
            'job_type_id'         => "{$required}|integer|exists:job_types,id",
            'experience_level_id' => "{$required}|integer|exists:experience_levels,id",
            'education_level_id'  => "{$required}|integer|exists:education_levels,id",
            'salary_range_id'     => 'nullable|integer|exists:salary_ranges,id',
            'poster_id'           => 'nullable|integer|exists:users,id',

            // ----------------------------------------------------------------
            // Location Details
            // ----------------------------------------------------------------
            'duty_station'                    => 'nullable|string|max:255',
            'street_address'                  => 'nullable|string',
            'applicant_location_requirements' => 'nullable|string',

            // ----------------------------------------------------------------
            // Salary Information
            // ----------------------------------------------------------------
            'salary_amount'  => 'nullable|numeric|min:0',
            'currency'       => 'nullable|string|max:10',
            'payment_period' => 'nullable|string|in:hourly,daily,weekly,monthly,yearly',
            'base_salary'    => 'nullable|numeric|min:0',

            // ----------------------------------------------------------------
            // Job Specifications
            // migration: location_type default on-site, employment_type default full-time
            // ----------------------------------------------------------------
            'location_type'   => 'nullable|string|in:remote,hybrid,on-site',
            'work_hours'      => 'nullable|string|max:255',
            'employment_type' => 'nullable|string|in:full-time,part-time,contract,internship,volunteer,temporary',

            // ----------------------------------------------------------------
            // SEO — migration: meta_title(string), meta_description(text),
            //        keywords(text), canonical_url(string), structured_data(json),
            //        focus_keyphrase(text), seo_synonyms(text)
            // ----------------------------------------------------------------
            'meta_title'       => 'nullable|string|max:70',
            'meta_description' => 'nullable|string|max:170',
            'keywords'         => 'nullable|string',
            'canonical_url'    => 'nullable|url|max:255',
            'focus_keyphrase'  => 'nullable|string',
            'seo_synonyms'     => 'nullable|string',

            // ----------------------------------------------------------------
            // Boolean Flags — exactly matching migration column names
            // ----------------------------------------------------------------
            'is_pinged'          => 'nullable|boolean',
            'is_indexed'         => 'nullable|boolean',
            'is_whatsapp_contact'=> 'nullable|boolean',
            'is_telephone_call'  => 'nullable|boolean',
            'is_featured'        => 'nullable|boolean',
            'is_urgent'          => 'nullable|boolean',
            'is_active'          => 'nullable|boolean',
            'is_verified'        => 'nullable|boolean',

            // ----------------------------------------------------------------
            // Application Requirements — migration column names:
            //   is_application_required (boolean)
            //   is_academic_documents_required (boolean)
            //   is_cover_letter_required (boolean)
            //   is_resume_required (boolean)
            // ----------------------------------------------------------------
            'is_application_required'        => 'nullable|boolean',
            'is_academic_documents_required' => 'nullable|boolean',
            'is_cover_letter_required'       => 'nullable|boolean',
            'is_resume_required'             => 'nullable|boolean',

            // ----------------------------------------------------------------
            // Timestamps
            // ----------------------------------------------------------------
            'published_at'  => 'nullable|date',
            'featured_until' => 'nullable|date|after_or_equal:today',
        ];
    }

    public function attributes(): array
    {
        return [
            'job_title'                      => 'job title',
            'job_description'                => 'job description',
            'company_id'                     => 'company',
            'job_category_id'                => 'job category',
            'industry_id'                    => 'industry',
            'job_location_id'                => 'job location',
            'job_type_id'                    => 'job type',
            'experience_level_id'            => 'experience level',
            'education_level_id'             => 'education level',
            'salary_range_id'                => 'salary range',
            'poster_id'                      => 'poster',
            'location_type'                  => 'location type',
            'employment_type'                => 'employment type',
            'payment_period'                 => 'payment period',
            'is_application_required'        => 'application letter requirement',
            'is_cover_letter_required'       => 'cover letter requirement',
            'is_resume_required'             => 'resume requirement',
            'is_academic_documents_required' => 'academic documents requirement',
            'is_whatsapp_contact'            => 'whatsapp contact',
            'is_telephone_call'              => 'telephone call',
            'applicant_location_requirements'=> 'applicant location requirements',
        ];
    }

    public function messages(): array
    {
        return [
            'deadline.after_or_equal'       => 'The deadline must be today or a future date.',
            'featured_until.after_or_equal' => 'The featured until date must be today or a future date.',
        ];
    }
}