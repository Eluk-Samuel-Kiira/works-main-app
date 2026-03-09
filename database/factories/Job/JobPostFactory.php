<?php

namespace Database\Factories\Job;

use App\Models\Job\JobPost;
use App\Models\Job\Company;
use App\Models\Job\JobCategory;
use App\Models\Job\Industry;
use App\Models\Job\JobLocation;
use App\Models\Job\JobType;
use App\Models\Job\ExperienceLevel;
use App\Models\Job\EducationLevel;
use App\Models\Job\SalaryRange;
use App\Models\Auth\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

class JobPostFactory extends Factory
{
    protected $model = JobPost::class;

    public function definition(): array
    {
        // Get random related models - USE EXISTING DATA ONLY
        $company = Company::inRandomOrder()->first();
        $jobCategory = JobCategory::inRandomOrder()->first();
        $industry = Industry::inRandomOrder()->first();
        $location = JobLocation::inRandomOrder()->first();
        $jobType = JobType::inRandomOrder()->first();
        $experienceLevel = ExperienceLevel::inRandomOrder()->first();
        $educationLevel = EducationLevel::inRandomOrder()->first();
        $salaryRange = SalaryRange::where('currency', 'UGX')->inRandomOrder()->first();
        $poster = User::whereHas('roles', function($query) {
            $query->where('name', 'employer');
        })->inRandomOrder()->first();

        // If any required related data is missing, we'll handle it gracefully
        if (!$company || !$jobCategory || !$industry || !$location || !$jobType || !$experienceLevel || !$educationLevel || !$poster) {
            throw new \Exception('Required related data missing. Please run related seeders first.');
        }

        // Generate job title based on category
        $jobTitle = $this->generateJobTitle($jobCategory->name);

        // Generate description paragraphs
        $description = $this->generateDescription($jobTitle);
        $responsibilities = $this->generateResponsibilities();
        $skills = $this->generateSkills();
        $qualifications = $this->generateQualifications($educationLevel->name);

        // Calculate SEO score based on content quality
        $seoScore = $this->calculateSEOScore($jobTitle, $description, $skills);
        $contentQualityScore = $this->calculateContentQualityScore($description, $responsibilities, $qualifications);

        // SAFE DATE HANDLING - Fixed the error
        // Determine publication date (between 60 days ago and now)
        $publishedAt = $this->faker->dateTimeBetween('-60 days', 'now');
        
        // Ensure deadline is always after publishedAt
        $deadlineDays = rand(7, 60);
        $deadline = (clone $publishedAt)->modify('+' . $deadlineDays . ' days');
        
        // Featured until - make sure it's between publishedAt and deadline
        $featuredUntil = null;
        if ($this->faker->boolean(20)) {
            // Ensure featured days doesn't exceed deadline days
            $maxFeaturedDays = min(30, $deadlineDays - 1);
            if ($maxFeaturedDays > 0) {
                $featuredDays = rand(3, $maxFeaturedDays);
                $featuredUntil = (clone $publishedAt)->modify('+' . $featuredDays . ' days');
            }
        }

        return [
            'job_title' => $jobTitle,
            'slug' => Str::slug($jobTitle) . '-' . Str::random(6),
            'job_description' => $description,
            'responsibilities' => $responsibilities,
            'skills' => $skills,
            'qualifications' => $qualifications,
            'deadline' => $deadline,
            'application_procedure' => $this->generateApplicationProcedure(),
            'email' => $this->faker->boolean(70) ? ($company->contact_email ?? $this->faker->companyEmail()) : null,
            'telephone' => $this->faker->boolean(40) ? ($company->contact_phone ?? $this->faker->phoneNumber()) : null,

            // Relationships - USE EXISTING IDs ONLY, NO FACTORY CREATION
            'company_id' => $company->id,
            'job_category_id' => $jobCategory->id,
            'industry_id' => $industry->id,
            'job_location_id' => $location->id,
            'job_type_id' => $jobType->id,
            'experience_level_id' => $experienceLevel->id,
            'education_level_id' => $educationLevel->id,
            'salary_range_id' => $this->faker->boolean(70) ? ($salaryRange?->id ?? null) : null,
            'poster_id' => $poster->id,

            // Location Details
            'duty_station' => $location->name ?? $this->faker->city(),
            'street_address' => $this->faker->boolean(50) ? $this->faker->streetAddress() : null,

            // Salary Information
            'salary_amount' => $salaryRange ? $this->faker->numberBetween(
                $salaryRange->min_salary ?? 500000, 
                $salaryRange->max_salary ?? 3000000
            ) : null,
            'currency' => $salaryRange?->currency ?? 'UGX',
            'payment_period' => $this->faker->randomElement(['monthly', 'yearly', 'hourly', 'daily']),
            'base_salary' => $this->faker->boolean(30) ? $this->faker->numberBetween(300000, 1000000) : null,

            // Job Specifications
            'location_type' => $this->faker->randomElement(['on-site', 'remote', 'hybrid']),
            'applicant_location_requirements' => $this->faker->boolean(30) ? $this->faker->sentence() : null,
            'work_hours' => $this->faker->boolean(60) ? $this->faker->randomElement(['Full time', 'Part time', 'Flexible', '8am - 5pm', '9am - 6pm']) : null,
            'employment_type' => $this->faker->randomElement(['full-time', 'part-time', 'contract', 'temporary', 'internship', 'volunteer']),

            // SEO & AI Optimization
            'meta_title' => $jobTitle . ' at ' . ($company->name ?? 'Company') . ' - ' . $this->faker->city(),
            'meta_description' => Str::limit($description, 160),
            'keywords' => $this->generateKeywords($jobTitle, $jobCategory->name, $industry->name),
            'canonical_url' => $this->faker->boolean(20) ? $this->faker->url() : null,
            'structured_data' => $this->generateStructuredData($jobTitle, $company, $location),
            'focus_keyphrase' => ($jobCategory->name ?? 'Job') . ' jobs',
            'seo_synonyms' => $this->generateSeoSynonyms($jobCategory->name),

            // Advanced SEO Features
            'is_pinged' => $this->faker->boolean(70),
            'last_pinged_at' => $this->faker->dateTimeBetween('-30 days', 'now'),
            'is_indexed' => $this->faker->boolean(80),
            'last_indexed_at' => $this->faker->dateTimeBetween('-30 days', 'now'),
            'is_featured' => $this->faker->boolean(15),
            'is_urgent' => $this->faker->boolean(20),
            'is_active' => $this->faker->boolean(90),
            'is_verified' => $this->faker->boolean(70),
            'view_count' => $this->faker->numberBetween(0, 5000),
            'application_count' => $this->faker->numberBetween(0, 200),
            'click_count' => $this->faker->numberBetween(0, 1000),
            'is_cover_letter_required' => $this->faker->boolean(60),
            'is_cover_application_required' => $this->faker->boolean(50),
            'is_cover_academic_documents_required' => $this->faker->boolean(40),
            'is_cover_resume_required' => $this->faker->boolean(90),

            // AI Optimization
            'ai_optimized_title' => $this->generateAIOptimizedTitle($jobTitle),
            'ai_optimized_description' => $this->generateAIOptimizedDescription($jobTitle, $company->name),
            'ai_content_analysis' => json_encode([
                'readability_score' => $this->faker->numberBetween(60, 95),
                'keyword_density' => $this->faker->randomFloat(2, 1, 3),
                'suggested_improvements' => $this->faker->sentences(3)
            ]),
            'seo_score' => $seoScore,
            'content_quality_score' => $contentQualityScore,
            'search_terms' => json_encode($this->faker->words(10)),
            'competitor_analysis' => $this->faker->boolean(30) ? json_encode([
                'competitors' => $this->faker->words(5),
                'average_salary' => $this->faker->numberBetween(500000, 5000000),
                'market_demand' => $this->faker->randomElement(['High', 'Medium', 'Low'])
            ]) : null,
            'ai_recommendations' => $this->faker->boolean(50) ? $this->faker->paragraphs(2, true) : null,

            // Performance Tracking
            'search_impressions' => $this->faker->numberBetween(0, 10000),
            'search_clicks' => $this->faker->numberBetween(0, 2000),
            'click_through_rate' => $this->faker->randomFloat(2, 0, 15),
            'google_rank' => $this->faker->boolean(40) ? $this->faker->numberBetween(1, 100) : null,
            'ranking_keywords' => $this->faker->boolean(50) ? json_encode($this->faker->words(8)) : null,

            // Social Signals
            'social_shares' => $this->faker->numberBetween(0, 500),
            'backlinks_count' => $this->faker->numberBetween(0, 50),
            'social_metrics' => $this->faker->boolean(30) ? json_encode([
                'facebook' => $this->faker->numberBetween(0, 100),
                'twitter' => $this->faker->numberBetween(0, 100),
                'linkedin' => $this->faker->numberBetween(0, 100),
                'whatsapp' => $this->faker->numberBetween(0, 100)
            ]) : null,

            'published_at' => $publishedAt,
            'featured_until' => $featuredUntil,
            'created_at' => $publishedAt,
            'updated_at' => $this->faker->dateTimeBetween($publishedAt, 'now'),
        ];
    }

    // ... rest of your methods remain the same ..

    /**
     * Generate a job title based on category
     */
    private function generateJobTitle(?string $category): string
    {
        $titles = [
            'Software Development' => [
                'Software Engineer', 'Senior Developer', 'Junior Developer', 'Full Stack Developer',
                'Backend Engineer', 'Frontend Developer', 'Mobile App Developer', 'DevOps Engineer'
            ],
            'Sales' => [
                'Sales Representative', 'Sales Manager', 'Account Executive', 'Business Development Officer',
                'Sales Associate', 'Regional Sales Manager', 'Sales Consultant'
            ],
            'Marketing' => [
                'Marketing Manager', 'Digital Marketing Specialist', 'Content Writer', 'SEO Specialist',
                'Social Media Manager', 'Brand Manager', 'Marketing Coordinator'
            ],
            'Accounting' => [
                'Accountant', 'Senior Accountant', 'Finance Manager', 'Auditor',
                'Tax Consultant', 'Accounts Assistant', 'Financial Controller'
            ],
            'Human Resources' => [
                'HR Manager', 'Recruitment Specialist', 'HR Assistant', 'Training Officer',
                'Payroll Administrator', 'HR Business Partner', 'Talent Acquisition Specialist'
            ],
            'Healthcare' => [
                'Registered Nurse', 'Doctor', 'Clinical Officer', 'Pharmacist',
                'Laboratory Technician', 'Medical Assistant', 'Dentist'
            ],
            'Education' => [
                'Teacher', 'Lecturer', 'Tutor', 'School Administrator',
                'Education Officer', 'Curriculum Developer', 'Head Teacher'
            ],
            'Engineering' => [
                'Civil Engineer', 'Mechanical Engineer', 'Electrical Engineer', 'Structural Engineer',
                'Project Engineer', 'Site Engineer', 'Engineering Manager'
            ],
        ];

        $defaultTitles = [
            'Officer', 'Manager', 'Supervisor', 'Coordinator', 'Assistant', 'Specialist', 'Consultant'
        ];

        if ($category && isset($titles[$category])) {
            return $this->faker->randomElement($titles[$category]);
        }

        return $this->faker->jobTitle() . ' ' . $this->faker->randomElement($defaultTitles);
    }

    /**
     * Generate job description
     */
    private function generateDescription(string $jobTitle): string
    {
        $paragraphs = [];
        $paragraphs[] = "We are seeking a talented and motivated {$jobTitle} to join our dynamic team. The ideal candidate will be passionate about their work and committed to delivering exceptional results.";
        $paragraphs[] = $this->faker->paragraph(5);
        $paragraphs[] = $this->faker->paragraph(4);
        $paragraphs[] = "If you are looking for an opportunity to grow your career and make a meaningful impact, we would love to hear from you.";

        return implode("\n\n", $paragraphs);
    }

    /**
     * Generate responsibilities list
     */
    private function generateResponsibilities(): string
    {
        $responsibilities = [];
        for ($i = 0; $i < rand(5, 10); $i++) {
            $responsibilities[] = $this->faker->sentence(8);
        }
        return implode("\n", $responsibilities);
    }

    /**
     * Generate skills list
     */
    private function generateSkills(): string
    {
        $skills = [];
        for ($i = 0; $i < rand(5, 8); $i++) {
            $skills[] = $this->faker->word() . ', ' . $this->faker->word() . ', ' . $this->faker->word();
        }
        return implode("\n", $skills);
    }

    /**
     * Generate qualifications based on education level
     */
    private function generateQualifications(?string $educationLevel): string
    {
        $qualifications = [];

        if ($educationLevel) {
            $qualifications[] = $educationLevel . ' in relevant field';
        } else {
            $qualifications[] = $this->faker->randomElement([
                "Bachelor's Degree", "Master's Degree", "Diploma", "Certificate", "High School Diploma"
            ]) . ' in ' . $this->faker->word();
        }

        $qualifications[] = $this->faker->numberBetween(1, 5) . '+ years of relevant experience';
        $qualifications[] = 'Excellent communication and interpersonal skills';

        for ($i = 0; $i < rand(2, 4); $i++) {
            $qualifications[] = $this->faker->sentence(6);
        }

        return implode("\n", $qualifications);
    }

    /**
     * Generate application procedure
     */
    private function generateApplicationProcedure(): string
    {
        $procedures = [
            "Interested candidates should submit their CV and cover letter to the email address below.",
            "Please apply online through our careers portal with your updated resume.",
            "Send your application including CV, academic documents, and three referees.",
            "Apply by filling out the online application form and attaching your documents.",
            "Submit your application through our website. Only shortlisted candidates will be contacted."
        ];

        return $this->faker->randomElement($procedures);
    }

    /**
     * Generate keywords for SEO
     */
    private function generateKeywords(string $jobTitle, ?string $category, ?string $industry): string
    {
        $keywords = [
            $jobTitle,
            $category ?? 'job',
            $industry ?? 'career',
            'employment',
            'vacancy',
            'career opportunity',
            'hiring',
            'jobs in ' . $this->faker->country()
        ];

        return implode(', ', $keywords);
    }

    /**
     * Generate SEO synonyms
     */
    private function generateSeoSynonyms(?string $category): string
    {
        $synonyms = [
            ($category ?? 'Job') . ' positions',
            ($category ?? 'Job') . ' careers',
            ($category ?? 'Job') . ' opportunities',
            ($category ?? 'Job') . ' employment'
        ];

        return implode(', ', $synonyms);
    }

    /**
     * Generate structured data for SEO
     */
    private function generateStructuredData($jobTitle, $company, $location): ?string
    {
        if (!$this->faker->boolean(60)) {
            return null;
        }

        $companyName = $company?->name ?? 'Company';
        $companyWebsite = $company?->website ?? $this->faker->url();
        $locationName = $location?->name ?? $this->faker->city();

        $data = [
            '@context' => 'https://schema.org/',
            '@type' => 'JobPosting',
            'title' => $jobTitle,
            'description' => $this->faker->paragraph(3),
            'datePosted' => $this->faker->date('Y-m-d'),
            'validThrough' => $this->faker->date('Y-m-d', '+30 days'),
            'employmentType' => $this->faker->randomElement(['FULL_TIME', 'PART_TIME', 'CONTRACTOR']),
            'hiringOrganization' => [
                '@type' => 'Organization',
                'name' => $companyName,
                'sameAs' => $companyWebsite
            ],
            'jobLocation' => [
                '@type' => 'Place',
                'address' => [
                    '@type' => 'PostalAddress',
                    'addressLocality' => $locationName,
                    'addressCountry' => 'UG'
                ]
            ]
        ];

        return json_encode($data);
    }

    /**
     * Generate AI-optimized title
     */
    private function generateAIOptimizedTitle(string $jobTitle): string
    {
        return $jobTitle . ' - ' . $this->faker->randomElement([
            'Top Talent Wanted', 'Great Career Opportunity', 'Join Our Team',
            'Immediate Opening', 'Excellent Benefits Package'
        ]);
    }

    /**
     * Generate AI-optimized description - FIXED THE SYNTAX ERROR HERE
     */
    private function generateAIOptimizedDescription(string $jobTitle, ?string $companyName): string
    {
        // Fixed: Using ternary operator instead of null coalescing inside string
        $companyText = $companyName ? $companyName : 'Our company';
        return "Looking for a {$jobTitle} position? {$companyText} is hiring! Apply today for this exciting career opportunity with competitive salary and benefits.";
    }

    /**
     * Calculate SEO score based on content quality
     */
    private function calculateSEOScore(string $title, string $description, string $skills): float
    {
        $score = 70; // Base score

        // Title length check (ideal 50-60 characters)
        if (strlen($title) > 50 && strlen($title) < 60) {
            $score += 10;
        } elseif (strlen($title) > 30) {
            $score += 5;
        }

        // Description length check
        if (strlen($description) > 500) {
            $score += 10;
        }

        // Skills presence
        if (strlen($skills) > 50) {
            $score += 5;
        }

        // Add some randomness
        $score += $this->faker->numberBetween(-5, 5);

        return max(0, min(100, $score));
    }

    /**
     * Calculate content quality score
     */
    private function calculateContentQualityScore(string $description, string $responsibilities, string $qualifications): float
    {
        $score = 75; // Base score

        if (strlen($description) > 300) $score += 5;
        if (strlen($responsibilities) > 200) $score += 5;
        if (strlen($qualifications) > 150) $score += 5;

        return max(0, min(100, $score));
    }

    /**
     * Configure the factory with specific states
     */
    public function configure()
    {
        return $this->afterCreating(function (JobPost $jobPost) {
            // Update application count based on view count
            if ($jobPost->view_count > 0) {
                $applicationRate = $this->faker->randomFloat(2, 0.01, 0.2);
                $jobPost->application_count = (int)($jobPost->view_count * $applicationRate);
                $jobPost->click_through_rate = ($jobPost->search_clicks > 0 && $jobPost->search_impressions > 0) 
                    ? round(($jobPost->search_clicks / $jobPost->search_impressions) * 100, 2) 
                    : 0;
                $jobPost->save();
            }
        });
    }

    // State methods for specific job types
    public function featured(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_featured' => true,
            'is_urgent' => $this->faker->boolean(50),
            'seo_score' => $this->faker->numberBetween(85, 100),
        ]);
    }

    public function urgent(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_urgent' => true,
            'published_at' => $this->faker->dateTimeBetween('-7 days', 'now'),
            'deadline' => $this->faker->dateTimeBetween('+1 days', '+14 days'), // Always in the future
        ]);
    }

    public function expired(): static
    {
        return $this->state(fn (array $attributes) => [
            'deadline' => $this->faker->dateTimeBetween('-90 days', '-1 day'),
            'is_active' => false,
            'published_at' => $this->faker->dateTimeBetween('-120 days', '-31 days'), // Ensure published_at is before deadline
        ]);
    }

    public function remote(): static
    {
        return $this->state(fn (array $attributes) => [
            'location_type' => 'remote',
            'duty_station' => 'Remote (Work from Home)',
        ]);
    }

    public function highSalary(): static
    {
        return $this->state(fn (array $attributes) => [
            'salary_amount' => $this->faker->numberBetween(5000000, 20000000),
            'is_featured' => $this->faker->boolean(70),
        ]);
    }
}