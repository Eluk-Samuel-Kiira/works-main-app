<?php

namespace App\Http\Requests\Api\Jobs;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\ValidationException;

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
            // Relationships
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
            // ----------------------------------------------------------------
            'location_type'   => 'nullable|string|in:remote,hybrid,on-site',
            'work_hours'      => 'nullable|string|max:255',
            'employment_type' => 'nullable|string|in:full-time,part-time,contract,internship,volunteer,temporary',

            // ----------------------------------------------------------------
            // SEO
            // ----------------------------------------------------------------
            'meta_title'       => 'nullable|string|max:100',
            'meta_description' => 'nullable|string|max:200',
            'keywords'         => 'nullable|string',
            'canonical_url'    => 'nullable|url|max:255',
            'focus_keyphrase'  => 'nullable|string',
            'seo_synonyms'     => 'nullable|string',

            // ----------------------------------------------------------------
            // Boolean Flags
            // ----------------------------------------------------------------
            'is_pinged'           => 'nullable|boolean',
            'is_indexed'          => 'nullable|boolean',
            'is_whatsapp_contact' => 'nullable|boolean',
            'is_telephone_call'   => 'nullable|boolean',
            'is_featured'         => 'nullable|boolean',
            'is_urgent'           => 'nullable|boolean',
            'is_active'           => 'nullable|boolean',
            'is_verified'         => 'nullable|boolean',
            'is_simple_job'       => 'nullable|boolean',
            'is_quick_gig'        => 'nullable|boolean',

            // ----------------------------------------------------------------
            // Application Requirements
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

    /**
     * After validation hook for custom validation
     */
    public function withValidator($validator)
    {
        $validator->after(function ($validator) {
            $this->validateJobDescription($validator);
            $this->validateContactMethods($validator);
            $this->validateApplicationProcedure($validator);
        });
    }

    /**
     * Validate job description doesn't contain phone numbers or emails
     */
    protected function validateJobDescription($validator)
    {
        $description = $this->input('job_description');
        $email = $this->input('email');
        $telephone = $this->input('telephone');
        
        if (empty($description)) {
            return;
        }
        
        // Pattern to detect email addresses
        $emailPattern = '/[a-zA-Z0-9._%+-]+@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,}/';
        
        // Pattern to detect phone numbers (various formats)
        $phonePattern = '/(?:\+?\d{1,3}[-.\s]?)?\(?\d{3}\)?[-.\s]?\d{3}[-.\s]?\d{4}|' .
                        '\+?\d{1,3}[-.\s]?\d{3}[-.\s]?\d{3}[-.\s]?\d{3,4}|' .
                        '\d{10,}/';
        
        $hasEmailInDesc = preg_match($emailPattern, $description);
        $hasPhoneInDesc = preg_match($phonePattern, $description);
        
        // Case 1: No email or phone provided in contact fields
        if (empty($email) && empty($telephone)) {
            if ($hasEmailInDesc || $hasPhoneInDesc) {
                $validator->errors()->add(
                    'job_description',
                    'Job description should not contain email addresses or phone numbers. ' .
                    'Please provide them in the designated contact fields above.'
                );
            }
        }
        
        // Case 2: Email not provided but found in description
        if (empty($email) && $hasEmailInDesc) {
            $validator->errors()->add(
                'email',
                'Email address found in job description. Please provide it in the contact email field.'
            );
        }
        
        // Case 3: Phone not provided but found in description
        if (empty($telephone) && $hasPhoneInDesc) {
            $validator->errors()->add(
                'telephone',
                'Phone number found in job description. Please provide it in the telephone field.'
            );
        }
    }

    /**
     * Validate contact methods based on provided phone number
     */
    protected function validateContactMethods($validator)
    {
        $telephone = $this->input('telephone');
        $isWhatsappContact = $this->input('is_whatsapp_contact');
        $isTelephoneCall = $this->input('is_telephone_call');
        
        // If phone number is provided, at least one contact method must be enabled
        if (!empty($telephone)) {
            if (!$isWhatsappContact && !$isTelephoneCall) {
                $validator->errors()->add(
                    'is_whatsapp_contact',
                    'When a telephone number is provided, you must specify if it\'s for WhatsApp contact and/or phone calls.'
                );
                $validator->errors()->add(
                    'is_telephone_call',
                    'When a telephone number is provided, you must specify if it\'s for WhatsApp contact and/or phone calls.'
                );
            }
        } else {
            // If no phone number, contact method flags should be false or null
            if ($isWhatsappContact || $isTelephoneCall) {
                $validator->errors()->add(
                    'telephone',
                    'Telephone number is required when enabling WhatsApp contact or phone call options.'
                );
            }
        }
    }

    /**
     * Validate application procedure based on job type
     */
    protected function validateApplicationProcedure($validator)
    {
        $isSimpleJob = $this->input('is_simple_job');
        $applicationProcedure = $this->input('application_procedure');
        $email = $this->input('email');
        $telephone = $this->input('telephone');
        $isWhatsappContact = $this->input('is_whatsapp_contact');
        $isTelephoneCall = $this->input('is_telephone_call');
        
        // Pattern to detect URLs
        $urlPattern = '/(https?:\/\/[^\s]+|www\.[^\s]+|[a-zA-Z0-9.-]+\.[a-zA-Z]{2,}\/[^\s]*)/';
        
        // Check if any contact method is provided
        $hasEmail = !empty($email);
        $hasPhone = !empty($telephone);
        $hasWhatsapp = $isWhatsappContact;
        $hasCall = $isTelephoneCall;
        $hasAnyContact = $hasEmail || $hasPhone || $hasWhatsapp || $hasCall;
        
        // Case 1: Simple Job (is_simple_job = true)
        if ($isSimpleJob) {
            // If NO contact methods are provided (email, phone, WhatsApp, call), then description MUST have a link
            if (!$hasAnyContact) {
                $description = $this->input('job_description');
                $hasLinkInDesc = !empty($description) && preg_match($urlPattern, $description);
                
                if (!$hasLinkInDesc) {
                    $validator->errors()->add(
                        'job_description',
                        'For simple jobs with no contact information (email, phone, WhatsApp, or call), the job description must include a link where applicants can apply.'
                    );
                }
            }
            
            // Application procedure should be empty for simple jobs
            if (!empty($applicationProcedure)) {
                $validator->errors()->add(
                    'application_procedure',
                    'For simple job posts, the application procedure field should be left empty. Application link should be in the job description.'
                );
            }
        } 
        // Case 2: Regular Job (is_simple_job = false or null)
        else {
            // If no contact methods (email, phone, whatsapp, call) are provided
            if (!$hasAnyContact) {
                // Then application_procedure MUST have a link
                $hasLinkInProcedure = !empty($applicationProcedure) && preg_match($urlPattern, $applicationProcedure);
                
                if (!$hasLinkInProcedure) {
                    $validator->errors()->add(
                        'application_procedure',
                        'When no contact email, phone, WhatsApp, or call options are provided, the application procedure must include a link where applicants can apply.'
                    );
                }
            }
            
            // Also ensure job description doesn't have links for regular jobs
            // (Links should be in application_procedure, not job description)
            $description = $this->input('job_description');
            if (!empty($description) && preg_match($urlPattern, $description)) {
                $validator->errors()->add(
                    'job_description',
                    'For regular job posts, please do not include application links in the job description. Use the "Application Procedure" field instead.'
                );
            }
        }
    }

    public function attributes(): array
    {
        return [
            'company_id' => 'company',
            'job_category_id' => 'job category',
            'industry_id' => 'industry',
            'job_location_id' => 'job location',
            'job_type_id' => 'job type',
            'experience_level_id' => 'experience level',
            'education_level_id' => 'education level',
            'salary_range_id' => 'salary range',
            'poster_id' => 'poster',
            'job_title' => 'job title',
            'job_description' => 'job description',
            'deadline' => 'application deadline',
            'email' => 'contact email',
            'telephone' => 'telephone number',
            'location_type' => 'location type',
            'employment_type' => 'employment type',
            'payment_period' => 'payment period',
            'meta_title' => 'meta title',
            'meta_description' => 'meta description',
            'canonical_url' => 'canonical URL',
            'work_hours' => 'work hours',
            'duty_station' => 'duty station',
            'salary_amount' => 'salary amount',
            'base_salary' => 'base salary',
            'applicant_location_requirements' => 'applicant location requirements',
            'application_procedure' => 'application procedure',
            'is_simple_job' => 'simple job',
            'is_whatsapp_contact' => 'whatsapp contact',
            'is_telephone_call' => 'phone call',
        ];
    }

    public function messages(): array
    {
        return [
            // Custom messages that override the default ones
            'deadline.after_or_equal' => 'The :attribute must be today or a future date.',
            'featured_until.after_or_equal' => 'The :attribute must be today or a future date.',
            'job_title.max' => 'The :attribute cannot exceed 255 characters.',
            'meta_title.max' => 'The :attribute cannot exceed 100 characters for SEO optimization.',
            'meta_description.max' => 'The :attribute cannot exceed 200 characters for SEO optimization.',
            'salary_amount.numeric' => 'The :attribute must be a valid number.',
            'salary_amount.min' => 'The :attribute must be at least 0.',
            'base_salary.numeric' => 'The :attribute must be a valid number.',
            'base_salary.min' => 'The :attribute must be at least 0.',
        ];
    }

    /**
     * Prepare the data for validation
     */
    protected function prepareForValidation()
    {
        // Sanitize phone number (remove spaces and special characters for validation)
        if ($this->has('telephone')) {
            $this->merge([
                'telephone' => preg_replace('/[^0-9+]/', '', $this->telephone)
            ]);
        }
        
        // Ensure boolean flags are properly cast
        $booleanFields = [
            'is_whatsapp_contact', 'is_telephone_call', 'is_featured',
            'is_urgent', 'is_active', 'is_verified', 'is_pinged', 'is_indexed',
            'is_application_required', 'is_academic_documents_required',
            'is_cover_letter_required', 'is_resume_required', 'is_simple_job', 'is_quick_gig'
        ];
        
        foreach ($booleanFields as $field) {
            if ($this->has($field)) {
                $this->merge([
                    $field => filter_var($this->$field, FILTER_VALIDATE_BOOLEAN)
                ]);
            }
        }
    }
}