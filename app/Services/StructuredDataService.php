<?php

namespace App\Services;

use App\Models\Job\JobPost;

class StructuredDataService
{
    public function jobPosting(JobPost $job): array
    {
        $location = $job->jobLocation;
        $company  = $job->company;
        
        $data = [
            '@context' => 'https://schema.org/',
            '@type' => 'JobPosting',
            'title' => $job->job_title,
            'description' => strip_tags($job->job_description ?? ''),
            'datePosted' => $job->published_at ? $job->published_at->toAtomString() : $job->created_at->toAtomString(),
            'validThrough' => $job->deadline ? $job->deadline->endOfDay()->toAtomString() : null,
            'employmentType' => $this->mapEmploymentType($job->employment_type),
            'jobLocationType' => $job->location_type === 'remote' ? 'TELECOMMUTE' : null,
            'url' => config('api.web_app.url') . '/jobs/' . $job->slug,
            
            // ============================================================
            // ADD THESE FOR BETTER DISPLAY
            // ============================================================
            
            // 1. Add identifier for better indexing
            'identifier' => [
                '@type' => 'PropertyValue',
                'name' => 'Job ID',
                'value' => $job->id
            ],
            
            // 2. Add job benefits (shows as extra badge)
            'jobBenefits' => $this->extractBenefits($job->job_description),
            
            // 3. Add work hours
            'workHours' => $job->work_hours ?? null,
            
            // 4. Add industry
            'industry' => $job->industry ? $job->industry->name : null,
            
            // 5. Add occupational category
            'occupationalCategory' => $job->jobCategory ? $job->jobCategory->name : null,
            
            // 6. Add hiring organization details
            'hiringOrganization' => [
                '@type' => 'Organization',
                'name' => $company->name ?? '',
                'sameAs' => $company->website ?? null,
                'logo' => $company->logo_url ?? null,
                'url' => $company->website ?? null,
                'description' => $company->description ?? null,
                'email' => $company->contact_email ?? null,
                'telephone' => $company->phone ?? null,
                'foundingDate' => $company->founded_year ?? null,
                'numberOfEmployees' => $company->company_size ? [
                    '@type' => 'QuantitativeValue',
                    'value' => $company->company_size
                ] : null,
            ],
            
            // 7. Add job location with full address
            'jobLocation' => [
                '@type' => 'Place',
                'name' => $location ? $location->district : $job->duty_station,
                'address' => [
                    '@type' => 'PostalAddress',
                    'streetAddress' => $job->street_address ?? $job->duty_station ?? '',
                    'addressLocality' => $location->district ?? '',
                    'addressRegion' => $location->district ?? '',
                    'addressCountry' => $location->country ?? 'UG',
                    'postalCode' => $location->postal_code ?? null,
                ],
                'geo' => $location && ($location->latitude && $location->longitude) ? [
                    '@type' => 'GeoCoordinates',
                    'latitude' => $location->latitude,
                    'longitude' => $location->longitude,
                ] : null,
            ],
            
            // 8. Add applicant location requirements
            'applicantLocationRequirements' => $job->location_type === 'remote' ? [
                '@type' => 'Country',
                'name' => 'Uganda',
            ] : null,
            
            // 9. Add direct apply URL
            'directApply' => true,
            
            // 10. Add application URL if provided
            'applicationUrl' => $job->application_procedure ?? null,
            
            // 11. Add hiring contact
            'hiringContact' => ($job->email || $job->telephone) ? [
                '@type' => 'ContactPoint',
                'email' => $job->email,
                'telephone' => $job->telephone,
                'contactType' => 'hiring',
                'availableLanguage' => ['English', 'Swahili'],
            ] : null,
            
            // 12. Add salary currency and period
            'salaryCurrency' => $job->currency ?? 'UGX',
            
            // 13. Add employment unit text
            'employmentUnitText' => $this->getEmploymentUnitText($job->employment_type),
        ];
        
        // Salary handling
        if ($job->salary_amount) {
            $data['baseSalary'] = [
                '@type' => 'MonetaryAmount',
                'currency' => $job->currency ?? 'UGX',
                'value' => [
                    '@type' => 'QuantitativeValue',
                    'value' => (float) $job->salary_amount,
                    'unitText' => strtoupper($job->payment_period ?? 'MONTH'),
                ],
            ];
        } elseif ($job->salaryRange) {
            $data['baseSalary'] = [
                '@type' => 'MonetaryAmount',
                'currency' => $job->salaryRange->currency ?? 'UGX',
                'value' => [
                    '@type' => 'QuantitativeValue',
                    'minValue' => (float) $job->salaryRange->min_salary,
                    'maxValue' => (float) $job->salaryRange->max_salary,
                    'unitText' => 'MONTH',
                ],
            ];
        }
        
        // Education & Experience
        if ($job->educationLevel) {
            $data['educationRequirements'] = [
                '@type' => 'EducationalOccupationalCredential',
                'credentialCategory' => $job->educationLevel->name,
            ];
        }
        
        if ($job->experienceLevel) {
            $data['experienceRequirements'] = [
                '@type' => 'OccupationalExperienceRequirements',
                'monthsOfExperience' => ($job->experienceLevel->min_years ?? 0) * 12,
            ];
        }
        
        // Skills & Responsibilities
        if ($job->skills) {
            $data['skills'] = strip_tags($job->skills);
        }
        
        if ($job->qualifications) {
            $data['qualifications'] = strip_tags($job->qualifications);
        }
        
        if ($job->responsibilities) {
            $data['responsibilities'] = strip_tags($job->responsibilities);
        }
        
        // Add estimated salary (for jobs without explicit salary)
        if (!$job->salary_amount && !$job->salaryRange && $job->industry) {
            $data['estimatedSalary'] = [
                '@type' => 'MonetaryAmountDistribution',
                'currency' => 'UGX',
                'percentile10' => 500000,
                'percentile90' => 2000000,
            ];
        }
        
        return array_filter($data, fn($v) => $v !== null);
    }

    private function extractBenefits(?string $description): ?array
    {
        if (!$description) return null;
        
        $benefits = [];
        $keywords = ['benefit', 'bonus', 'insurance', 'leave', 'holiday', 'training', 'development'];
        
        foreach ($keywords as $keyword) {
            if (stripos($description, $keyword) !== false) {
                $benefits[] = ucfirst($keyword);
            }
        }
        
        return $benefits ?: null;
    }

    private function getEmploymentUnitText(?string $type): ?string
    {
        return match($type) {
            'full-time' => 'Full Time',
            'part-time' => 'Part Time',
            'contract' => 'Contract',
            'internship' => 'Internship',
            'temporary' => 'Temporary',
            'volunteer' => 'Volunteer',
            default => null,
        };
    }

    private function mapEmploymentType(?string $type): string
    {
        return match($type) {
            'full-time'  => 'FULL_TIME',
            'part-time'  => 'PART_TIME',
            'contract'   => 'CONTRACTOR',
            'internship' => 'INTERN',
            'temporary'  => 'TEMPORARY',
            'volunteer'  => 'VOLUNTEER',
            default      => 'OTHER',
        };
    }
}