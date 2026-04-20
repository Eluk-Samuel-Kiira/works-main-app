<?php

namespace App\Http\Controllers\Api\Jobs;

use App\Http\Concerns\ApiResponse;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\Jobs\JobPostRequest;
use App\Models\Job\{ JobPost, Company, JobLocation, ExperienceLevel, EducationLevel };
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use App\Services\SearchEnginePingService;

class JobPostController extends Controller
{
    use ApiResponse;

    /**
     * GET /api/v1/job-posts
     */
    public function index(Request $request): JsonResponse
    {
        $query = JobPost::select([
            'id', 'slug', 'job_title', 'employment_type', 'location_type',
            'duty_station', 'salary_amount', 'currency', 'payment_period',
            'is_active', 'is_verified', 'is_featured', 'is_urgent',
            'is_pinged', 'is_indexed',
            'published_at', 'deadline', 'view_count', 'application_count',
            'click_count', 'seo_score','created_at',
            'company_id', 'job_location_id', 'job_category_id',
            'industry_id', 'job_type_id', 'poster_id',
        ])
        ->with([
            'company:id,name,logo',
            'jobLocation:id,district,country',
            'jobCategory:id,name',
            'industry:id,name',
            'jobType:id,name',
            'poster:id,first_name,last_name,email',
        ])->latest();

        if ($request->filled('search')) {
            $query->where('job_title', 'like', "%{$request->search}%");
        }

        foreach ([
            'company_id', 'job_category_id', 'industry_id',
            'job_location_id', 'job_type_id', 'poster_id', 
            'experience_level_id', 'education_level_id', 'salary_range_id',
        ] as $filter) {
            if ($request->filled($filter)) {
                $query->where($filter, $request->integer($filter));
            }
        }

        if ($request->filled('location_type'))  $query->locationType($request->location_type);
        if ($request->filled('employment_type')) $query->employmentType($request->employment_type);
        if ($request->filled('salary_min'))      $query->salaryMin((float) $request->salary_min);
        if ($request->filled('salary_max'))      $query->salaryMax((float) $request->salary_max);

        foreach (['is_active', 'is_verified', 'is_featured', 'is_urgent'] as $flag) {
            if ($request->has($flag)) {
                $query->where($flag, filter_var($request->$flag, FILTER_VALIDATE_BOOLEAN));
            }
        }

        if ($request->filled('posted_after'))    $query->postedAfter($request->posted_after);
        if ($request->filled('deadline_before')) $query->deadlineBefore($request->deadline_before);

        $sortBy = in_array($request->sort_by, [
            'published_at', 'deadline', 'salary_amount',
            'seo_score', 'view_count', 'created_at',
        ]) ? $request->sort_by : 'published_at';

        $query->orderBy($sortBy, $request->sort_dir === 'asc' ? 'asc' : 'desc');

        $paginated = $query->paginate($request->integer('per_page', 15));

        // Format each item lightly (no detailed flag)
        $paginated->getCollection()->transform(fn($job) => $this->formatJobData($job, false));

        return $this->paginated($paginated, 'Job posts retrieved successfully');
    }

    /**
     * Check for duplicate jobs
     */
    public function checkDuplicate(Request $request): JsonResponse
    {
        $request->validate([
            'job_title' => 'required|string|max:255',
            'company_id' => 'required|integer|exists:companies,id',
        ]);

        $jobTitle = $request->job_title;
        $companyId = $request->company_id;

        // Get existing jobs for this company
        $existingJobs = JobPost::where('company_id', $companyId)
            ->where('created_at', '>=', now()->subDays(30)) // Only check last 30 days
            ->get(['id', 'job_title', 'created_at']);

        $duplicates = [];
        
        foreach ($existingJobs as $existing) {
            $similarity = $this->calculateSimilarity($jobTitle, $existing->job_title);
            
            if ($similarity >= 75) {
                $duplicates[] = [
                    'id' => $existing->id,
                    'job_title' => $existing->job_title,
                    'similarity' => round($similarity, 2),
                    'posted_at' => $existing->created_at->format('Y-m-d H:i:s'),
                    'posted_date' => $existing->created_at->diffForHumans(),
                ];
            }
        }

        if (count($duplicates) > 0) {
            return $this->success([
                'is_duplicate' => true,
                'similarity' => $duplicates[0]['similarity'],
                'existing_job_title' => $duplicates[0]['job_title'],
                'existing_job_date' => $duplicates[0]['posted_date'],
                'duplicates' => $duplicates,
                'message' => "A similar job already exists for this company."
            ], 'Duplicate job detected');
        }

        return $this->success([
            'is_duplicate' => false,
            'message' => 'No duplicate found'
        ], 'Job title is unique');
    }

    /**
     * Calculate similarity between two strings using Levenshtein distance
     */
    private function calculateSimilarity(string $str1, string $str2): float
    {
        // Normalize strings: lowercase, remove special characters, trim
        $normalize = function($str) {
            $str = strtolower($str);
            $str = preg_replace('/[^\w\s]/', '', $str);
            $str = preg_replace('/\s+/', ' ', $str);
            return trim($str);
        };
        
        $str1 = $normalize($str1);
        $str2 = $normalize($str2);
        
        // If strings are identical
        if ($str1 === $str2) {
            return 100;
        }
        
        // Calculate Levenshtein distance
        $distance = levenshtein($str1, $str2);
        $maxLength = max(strlen($str1), strlen($str2));
        
        if ($maxLength === 0) {
            return 0;
        }
        
        // Convert distance to similarity percentage
        $similarity = (1 - ($distance / $maxLength)) * 100;
        
        // Also check for keyword overlap
        $words1 = explode(' ', $str1);
        $words2 = explode(' ', $str2);
        
        $commonWords = array_intersect($words1, $words2);
        $totalUniqueWords = count(array_unique(array_merge($words1, $words2)));
        
        $wordSimilarity = $totalUniqueWords > 0 
            ? (count($commonWords) / $totalUniqueWords) * 100 
            : 0;
        
        // Weighted average: 70% Levenshtein, 30% word overlap
        $finalSimilarity = ($similarity * 0.7) + ($wordSimilarity * 0.3);
        
        return min(100, $finalSimilarity);
    }

    /**
     * Generate a unique SEO-friendly slug
     */
    private function generateSlug(string $title, ?int $companyId = null, ?int $locationId = null): string
    {
        $baseSlug = Str::slug($title);
        $slug = $baseSlug;
        
        // Try to get company and location names for better uniqueness
        if ($companyId) {
            // \Log::info('Generating slug - Company ID: ' . $companyId);
            $company = Company::find($companyId);
            
            if ($company && $company->name) {
                \Log::info('Company found: ' . $company->name);
                $slug = Str::slug($title . ' at ' . $company->name);
                \Log::info('Slug after adding company: ' . $slug);
            } else {
                \Log::warning('Company not found for ID: ' . $companyId);
            }
        }
        
        if ($locationId) {
            \Log::info('Generating slug - Location ID: ' . $locationId);
            $location = JobLocation::find($locationId);
            
            if ($location && ($location->district || $location->country)) {
                $locationName = $location->district ?? $location->country;
                \Log::info('Location found: ' . $locationName);
                
                // If we already have company in slug, add location with "in"
                if ($slug !== $baseSlug) {
                    $slug = Str::slug($title . ' at ' . $company->name . ' in ' . $locationName);
                } else {
                    $slug = Str::slug($title . ' in ' . $locationName);
                }
                // \Log::info('Slug after adding location: ' . $slug);
            } else {
                \Log::warning('Location not found for ID: ' . $locationId);
            }
        }
        
        // Ensure uniqueness
        $originalSlug = $slug;
        $counter = 1;
        
        while (JobPost::where('slug', $slug)->exists()) {
            $slug = $originalSlug . '-' . $counter;
            $counter++;
            \Log::info('Duplicate slug found, trying: ' . $slug);
        }
        
        // \Log::info('Final slug generated: ' . $slug);
        
        return $slug;
    }

    /**
     * POST /api/v1/job-posts
     */
    public function store(JobPostRequest $request): JsonResponse
    {
        try {
            $validated = $request->validated();
            
            // Check for duplicate job before creating
            $existingJob = JobPost::where('company_id', $validated['company_id'])
                ->where('job_title', 'LIKE', '%' . $validated['job_title'] . '%')
                ->where('created_at', '>=', now()->subDays(30))
                ->first();
            
            if ($existingJob) {
                $similarity = $this->calculateSimilarity($validated['job_title'], $existingJob->job_title);
                
                if ($similarity >= 75) {
                    return $this->error(
                        "A similar job already exists for this company. " .
                        "Existing job: '{$existingJob->job_title}' ({$similarity}% similar). " .
                        "Please check before posting.",
                        409,
                        [
                            'is_duplicate' => true,
                            'similarity' => round($similarity, 2),
                            'existing_job' => [
                                'id' => $existingJob->id,
                                'title' => $existingJob->job_title,
                                'slug' => $existingJob->slug,
                                'posted_at' => $existingJob->created_at->format('Y-m-d H:i:s'),
                            ]
                        ]
                    );
                }
            }
            
            // Generate SEO-friendly slug
            $validated['slug'] = $this->generateSlug(
                $validated['job_title'],
                $validated['company_id'] ?? null,
                $validated['job_location_id'] ?? null
            );

            $location = JobLocation::find($validated['job_location_id']);
            $company = Company::find($validated['company_id']);
            
            // Set default values if not provided
            $validated['is_active'] = $validated['is_active'] ?? true;
            $validated['is_verified'] = $validated['is_verified'] ?? false;
            $validated['is_simple_job'] = $validated['is_simple_job'] ?? false;
            $validated['is_quick_gig'] = $validated['is_quick_gig'] ?? false;
            $validated['is_featured'] = $validated['is_featured'] ?? false;
            $validated['is_urgent'] = $validated['is_urgent'] ?? false;
            $validated['is_pinged'] = $validated['is_pinged'] ?? false;
            $validated['is_indexed'] = $validated['is_indexed'] ?? false;
            $validated['is_whatsapp_contact'] = $validated['is_whatsapp_contact'] ?? false;
            $validated['is_telephone_call'] = $validated['is_telephone_call'] ?? false;
            $validated['is_application_required'] = $validated['is_application_required'] ?? false;
            $validated['is_academic_documents_required'] = $validated['is_academic_documents_required'] ?? false;
            $validated['is_cover_letter_required'] = $validated['is_cover_letter_required'] ?? false;
            $validated['is_resume_required'] = $validated['is_resume_required'] ?? true;
            
            // ============================================================
            // DYNAMIC SEO META DATA GENERATION
            // ============================================================
            
            // Generate dynamic meta title (50-60 characters optimal)
            if (empty($validated['meta_title'])) {
                $validated['meta_title'] = $this->generateDynamicMetaTitle($validated, $company, $location);
            }
            
            // Generate dynamic meta description (150-160 characters optimal)
            if (empty($validated['meta_description'])) {
                $validated['meta_description'] = $this->generateDynamicMetaDescription($validated, $company, $location);
            }
            
            // Generate dynamic keywords
            if (empty($validated['keywords'])) {
                $validated['keywords'] = $this->generateDynamicKeywords($validated, $company, $location);
            }
            
            // Create the job post
            $job = JobPost::create($validated);
            $job->load($this->eagerRelations(true));

            return $this->created(
                $this->formatJobData($job, true),
                'Job post created successfully'
            );
            
        } catch (\Exception $e) {
            Log::error('Job post creation failed: ' . $e->getMessage());
            return $this->error('Failed to create job post: ' . $e->getMessage(), 500);
        }
    }

    /**
     * Generate dynamic SEO-optimized meta title
     */
    private function generateDynamicMetaTitle(array $validated, $company, $location): string
    {
        $title = $validated['job_title'];
        $parts = [];
        
        // Add job title first
        $parts[] = $title;
        
        // Add company name
        if ($company && $company->name) {
            $parts[] = "at {$company->name}";
        }
        
        // Add location
        if ($location) {
            $locationName = $location->district ?? $location->country ?? '';
            if ($locationName) {
                $parts[] = "in {$locationName}";
            }
        }
        
        // Add salary if available (great for click-through rate)
        if (!empty($validated['salary_amount'])) {
            $salary = number_format($validated['salary_amount']);
            $currency = $validated['currency'] ?? 'UGX';
            $period = $validated['payment_period'] ?? 'monthly';
            
            $periodMap = [
                'hourly' => '/hr',
                'daily' => '/day', 
                'weekly' => '/week',
                'monthly' => '/month',
                'yearly' => '/year'
            ];
            
            $suffix = $periodMap[$period] ?? '';
            $parts[] = "{$currency} {$salary}{$suffix}";
        }
        
        // Add job type for better targeting
        if (!empty($validated['employment_type'])) {
            $typeMap = [
                'full-time' => 'Full Time',
                'part-time' => 'Part Time',
                'contract' => 'Contract',
                'internship' => 'Internship',
                'volunteer' => 'Volunteer',
                'temporary' => 'Temporary'
            ];
            $parts[] = $typeMap[$validated['employment_type']] ?? $validated['employment_type'];
        }
        
        // Add urgency for featured/urgent jobs
        if (!empty($validated['is_urgent']) || !empty($validated['is_featured'])) {
            $parts[] = 'Hiring Now';
        }
        
        // Add location type for remote jobs
        if (!empty($validated['location_type']) && $validated['location_type'] === 'remote') {
            $parts[] = 'Remote';
        }
        
        // Add year for freshness signal
        $parts[] = now()->year;
        
        // Combine and limit to 60 characters
        $metaTitle = implode(' | ', $parts);
        
        return Str::limit($metaTitle, 60);
    }

    /**
     * Generate dynamic SEO-optimized meta description
     */
    private function generateDynamicMetaDescription(array $validated, $company, $location): string
    {
        $description = '';
        
        // Start with job title and company
        $description .= "{$validated['job_title']} position";
        
        if ($company && $company->name) {
            $description .= " at {$company->name}";
        }
        
        if ($location) {
            $locationName = $location->district ?? $location->country ?? '';
            if ($locationName) {
                $description .= " in {$locationName}";
            }
        }
        
        $description .= ". ";
        
        // Add key benefits/highlights
        $highlights = [];
        
        if (!empty($validated['salary_amount'])) {
            $salary = number_format($validated['salary_amount']);
            $currency = $validated['currency'] ?? 'UGX';
            $period = $validated['payment_period'] ?? 'monthly';
            $periodMap = ['hourly' => 'hour', 'daily' => 'day', 'weekly' => 'week', 'monthly' => 'month', 'yearly' => 'year'];
            $highlights[] = "{$currency} {$salary} per {$periodMap[$period]}";
        }
        
        if (!empty($validated['employment_type'])) {
            $typeMap = [
                'full-time' => 'Full-time position',
                'part-time' => 'Part-time opportunity',
                'contract' => 'Contract role',
                'internship' => 'Internship opportunity',
                'volunteer' => 'Volunteer position',
                'temporary' => 'Temporary role'
            ];
            $highlights[] = $typeMap[$validated['employment_type']] ?? $validated['employment_type'];
        }
        
        if (!empty($validated['location_type']) && $validated['location_type'] === 'remote') {
            $highlights[] = 'Work from home';
        } elseif (!empty($validated['location_type']) && $validated['location_type'] === 'hybrid') {
            $highlights[] = 'Hybrid work model';
        }
        
        if (!empty($validated['is_urgent'])) {
            $highlights[] = 'Urgent hiring';
        }
        
        if (!empty($validated['is_featured'])) {
            $highlights[] = 'Featured job';
        }
        
        if (!empty($validated['experience_level_id'])) {
            $experience = ExperienceLevel::find($validated['experience_level_id']);
            if ($experience) {
                $highlights[] = $experience->name . ' level';
            }
        }
        
        if (!empty($validated['education_level_id'])) {
            $education = EducationLevel::find($validated['education_level_id']);
            if ($education) {
                $highlights[] = $education->name . ' required';
            }
        }
        
        if (!empty($highlights)) {
            $description .= implode(' • ', $highlights) . ". ";
        }
        
        // Add application call to action
        $deadline = !empty($validated['deadline']) ? \Carbon\Carbon::parse($validated['deadline'])->format('M d, Y') : 'soon';
        $description .= "Apply now before {$deadline}. ";
        
        // Add platform name
        $description .= "Find the best jobs on Stardena Works. ";
        
        // Add unique selling point based on job type
        if (!empty($validated['is_simple_job'])) {
            $description .= "Quick application process. ";
        }
        
        if (!empty($validated['is_quick_gig'])) {
            $description .= "Short-term opportunity. ";
        }
        
        // ✅ Limit to 200 characters (not 160)
        return Str::limit($description, 200);
    }

    /**
     * Generate dynamic SEO keywords
     */
    private function generateDynamicKeywords(array $validated, $company, $location): string
    {
        $keywords = [];
        
        // Add job title variations
        $keywords[] = $validated['job_title'];
        $keywords[] = $validated['job_title'] . ' jobs';
        $keywords[] = $validated['job_title'] . ' vacancy';
        
        // Add company
        if ($company && $company->name) {
            $keywords[] = $company->name;
            $keywords[] = $company->name . ' careers';
        }
        
        // Add location
        if ($location) {
            $locationName = $location->district ?? $location->country ?? '';
            if ($locationName) {
                $keywords[] = "jobs in {$locationName}";
                $keywords[] = "{$locationName} careers";
            }
        }
        
        // Add job type keywords
        if (!empty($validated['employment_type'])) {
            $typeMap = [
                'full-time' => ['full time', 'full-time jobs', 'permanent jobs'],
                'part-time' => ['part time', 'part-time jobs', 'flexible hours'],
                'contract' => ['contract jobs', 'contract work', 'temporary contract'],
                'internship' => ['internship', 'intern', 'graduate program'],
                'volunteer' => ['volunteer', 'volunteering', 'community service'],
                'temporary' => ['temp jobs', 'temporary work', 'short term']
            ];
            
            if (isset($typeMap[$validated['employment_type']])) {
                $keywords = array_merge($keywords, $typeMap[$validated['employment_type']]);
            }
        }
        
        // Add location type
        if (!empty($validated['location_type'])) {
            $keywords[] = $validated['location_type'] . ' jobs';
            if ($validated['location_type'] === 'remote') {
                $keywords[] = 'work from home';
                $keywords[] = 'remote work';
            }
        }
        
        // Add salary range keywords
        if (!empty($validated['salary_amount'])) {
            $salary = number_format($validated['salary_amount']);
            $keywords[] = "{$salary} salary";
            $keywords[] = "jobs paying {$salary}";
        }
        
        // Add urgency keywords
        if (!empty($validated['is_urgent'])) {
            $keywords[] = 'urgent hiring';
            $keywords[] = 'immediate hiring';
            $keywords[] = 'apply now';
        }
        
        // Add platform
        $keywords[] = 'Stardena Works';
        $keywords[] = 'job portal';
        $keywords[] = 'career opportunities';
        
        // Add country
        if ($location && $location->country) {
            $keywords[] = "jobs in {$location->country}";
            $keywords[] = "{$location->country} careers";
        }
        
        // Remove duplicates and limit to 10-15 keywords
        $keywords = array_unique($keywords);
        $keywords = array_slice($keywords, 0, 15);
        
        return implode(', ', $keywords);
    }

    /**
     * Generate canonical URL
     */
    private function generateCanonicalUrl(string $slug): string
    {
        return url("/jobs/{$slug}");
    }

    /**
     * Generate focus keyphrase for SEO
     */
    private function generateFocusKeyphrase(array $validated, $location): string
    {
        $keyphrase = $validated['job_title'];
        
        if ($location && $location->district) {
            $keyphrase .= " in {$location->district}";
        } elseif ($location && $location->country) {
            $keyphrase .= " in {$location->country}";
        }
        
        if (!empty($validated['employment_type']) && $validated['employment_type'] === 'full-time') {
            $keyphrase .= " Full Time";
        }
        
        return $keyphrase;
    }

    /**
     * Generate SEO synonyms
     */
    private function generateSeoSynonyms(array $validated, $company, $location): string
    {
        $synonyms = [];
        
        // Job title variations
        $titleWords = explode(' ', $validated['job_title']);
        if (count($titleWords) > 2) {
            $synonyms[] = implode(' ', array_slice($titleWords, 0, 2));
        }
        
        // Industry synonyms
        if (!empty($validated['industry_id'])) {
            $industry = Industry::find($validated['industry_id']);
            if ($industry) {
                $synonyms[] = $industry->name;
                $synonyms[] = $industry->name . ' industry';
            }
        }
        
        // Location synonyms
        if ($location) {
            if ($location->district) {
                $synonyms[] = $location->district . ' jobs';
                $synonyms[] = 'work in ' . $location->district;
            }
            if ($location->country) {
                $synonyms[] = $location->country . ' employment';
            }
        }
        
        // Company synonyms
        if ($company && $company->name) {
            $synonyms[] = $company->name . ' careers';
            $synonyms[] = 'join ' . $company->name;
        }
        
        return implode(', ', array_slice($synonyms, 0, 10));
    }

    /**
     * GET /api/v1/job-posts/{slug}
     */
    public function show($slug): JsonResponse
    {
        $jobPost = JobPost::where('slug', $slug)
            ->with($this->eagerRelations(true))
            ->firstOrFail();

        return $this->success(
            $this->formatJobData($jobPost, true),
            'Job post retrieved successfully'
        );
    }

    /**
     * PATCH /api/v1/job-posts/{slug}
     */
    public function update(JobPostRequest $request, $slug): JsonResponse
    {
        try {
            $jobPost = JobPost::where('slug', $slug)->firstOrFail();
            
            $allowed = [
                'job_title', 'job_description', 'responsibilities', 'skills',
                'qualifications', 'deadline', 'application_procedure', 'email',
                'telephone', 'duty_station', 'street_address', 'salary_amount',
                'currency', 'payment_period', 'base_salary', 'location_type',
                'work_hours', 'employment_type', 'applicant_location_requirements',
                'meta_title', 'meta_description', 'keywords', 'canonical_url',
                'focus_keyphrase', 'seo_synonyms',
                'is_whatsapp_contact', 'is_telephone_call', 'is_featured',
                'is_urgent', 'is_active', 'is_verified', 'is_pinged', 'is_indexed',
                'is_cover_letter_required', 'is_resume_required',
                'is_academic_documents_required', 'is_application_required',
                'published_at', 'featured_until',
                'company_id', 'job_category_id', 'industry_id', 'job_location_id',
                'job_type_id', 'experience_level_id', 'education_level_id',
                'salary_range_id', 'poster_id',
            ];

            $data = collect($request->validated())->only($allowed)->toArray();
            
            // Update slug if job title changed
            if (isset($data['job_title']) && $data['job_title'] !== $jobPost->job_title) {
                $data['slug'] = $this->generateSlug(
                    $data['job_title'],
                    $data['company_id'] ?? $jobPost->company_id,
                    $data['job_location_id'] ?? $jobPost->job_location_id
                );
            }

            \DB::table('job_posts')
                ->where('id', $jobPost->id)
                ->update($data + ['updated_at' => now()]);

            // Fetch fresh clean instance
            $fresh = JobPost::select($this->safeSelect())
                ->with($this->eagerRelations(true))
                ->where('id', $jobPost->id)
                ->first();

            $formatted = $this->formatJobData($fresh, false);

            return $this->success($formatted, 'Job post updated successfully');

        } catch (\Exception $e) {
            Log::error('UPDATE EXCEPTION: ' . $e->getMessage());
            return response()->json([
                'message' => $e->getMessage(),
                'file'    => $e->getFile(),
                'line'    => $e->getLine(),
            ], 500);
        }
    }

    private function safeSelect(): array
    {
        return [
            'id', 'slug', 'job_title', 'job_description', 'responsibilities',
            'skills', 'qualifications', 'deadline', 'application_procedure',
            'email', 'telephone', 'company_id', 'job_category_id', 'industry_id',
            'job_location_id', 'job_type_id', 'experience_level_id',
            'education_level_id', 'salary_range_id', 'poster_id',
            'duty_station', 'street_address', 'salary_amount', 'currency',
            'payment_period', 'base_salary', 'location_type', 'work_hours',
            'employment_type', 'meta_title', 'meta_description', 'keywords',
            'canonical_url', 'focus_keyphrase', 'seo_synonyms',
            'is_pinged', 'is_indexed', 'is_whatsapp_contact', 'is_telephone_call',
            'is_featured', 'is_urgent', 'is_active', 'is_verified',
            'is_cover_letter_required', 'is_resume_required',
            'is_academic_documents_required',
            'view_count', 'application_count', 'click_count',
            'seo_score', 'content_quality_score',
            'last_pinged_at', 'last_indexed_at', 'published_at',
            'featured_until', 'created_at', 'updated_at',
        ];
    }

    /**
     * DELETE /api/v1/job-posts/{slug}
     */
    public function destroy($slug): JsonResponse
    {
        $jobPost = JobPost::where('slug', $slug)->delete();
        return $this->deleted('Job post deleted successfully');
    }

    // -------------------------------------------------------------------------
    // Status / Action endpoints
    // -------------------------------------------------------------------------

    public function activate($slug): JsonResponse
    {
        // Try to update only inactive jobs
        $updated = JobPost::where('slug', $slug)
            ->where('is_active', false)
            ->update([
                'is_active' => true,
                'updated_at' => now(),
                'published_at' => now()
            ]);
        
        if ($updated) {
            // Success - job was activated
            $job = JobPost::where('slug', $slug)->first();
            return $this->success(
                $this->formatJobData($job->fresh(), false), 
                'Job post activated successfully'
            );
        }
        
        // Check if job exists at all
        $exists = JobPost::where('slug', $slug)->exists();
        
        if (!$exists) {
            return $this->error('Job post not found', 404);
        }
        
        // Job exists but is already active
        $job = JobPost::where('slug', $slug)->first();
        return $this->warning(
            'Job is already active',
            $this->formatJobData($job, false),
            200,
            [
                'current_status' => 'active',
                'suggestion' => 'No action needed'
            ]
        );
    }

    public function deactivate($slug): JsonResponse
    {
        // Try to update only active jobs
        $updated = JobPost::where('slug', $slug)
            ->where('is_active', true)
            ->update([
                'is_active' => false,
                'updated_at' => now()
            ]);
        
        if ($updated) {
            $job = JobPost::where('slug', $slug)->first();
            return $this->success(
                $this->formatJobData($job->fresh(), false), 
                'Job post deactivated successfully'
            );
        }
        
        $exists = JobPost::where('slug', $slug)->exists();
        
        if (!$exists) {
            return $this->error('Job post not found', 404);
        }
        
        $job = JobPost::where('slug', $slug)->first();
        return $this->warning(
            'Job is already inactive',
            $this->formatJobData($job, false),
            200,
            [
                'current_status' => 'inactive',
                'suggestion' => 'Use activate to publish the job'
            ]
        );
    }

    public function verify($slug): JsonResponse
    {
        $updated = JobPost::where('slug', $slug)
            ->where('is_verified', false)
            ->update([
                'is_verified' => true,
                'updated_at' => now()
            ]);
        
        if ($updated) {
            $job = JobPost::where('slug', $slug)->first();
            return $this->success(
                $this->formatJobData($job->fresh(), false), 
                'Job post verified successfully'
            );
        }
        
        $exists = JobPost::where('slug', $slug)->exists();
        
        if (!$exists) {
            return $this->error('Job post not found', 404);
        }
        
        $job = JobPost::where('slug', $slug)->first();
        return $this->warning(
            'Job is already verified',
            $this->formatJobData($job, false),
            200,
            [
                'current_status' => 'verified',
                'verified_at' => $job->updated_at?->toDateTimeString()
            ]
        );
    }

    public function feature(Request $request, $slug): JsonResponse
    {
        $days = $request->integer('days', 7);
        
        // Direct update without loading the model first
        $updated = JobPost::where('slug', $slug)
            ->update([
                'is_featured' => true,
                'featured_until' => now()->addDays($days),
                'updated_at' => now()
            ]);
        
        if (!$updated) {
            return $this->error('Job post not found', 404);
        }
        
        // Only load if needed for response
        $job = JobPost::where('slug', $slug)->first();
        
        return $this->success(
            $this->formatJobData($job, false), 
            "Job post featured for {$days} days"
        );
    }

    public function markUrgent($slug): JsonResponse
    {
        $updated = JobPost::where('slug', $slug)
            ->where('is_urgent', false)
            ->update([
                'is_urgent' => true,
                'updated_at' => now()
            ]);
        
        if ($updated) {
            $job = JobPost::where('slug', $slug)->first();
            return $this->success(
                $this->formatJobData($job->fresh(), false), 
                'Job post marked as urgent successfully'
            );
        }
        
        $exists = JobPost::where('slug', $slug)->exists();
        
        if (!$exists) {
            return $this->error('Job post not found', 404);
        }
        
        $job = JobPost::where('slug', $slug)->first();
        return $this->warning(
            'Job is already marked as urgent',
            $this->formatJobData($job, false),
            200,
            [
                'current_status' => 'urgent',
                'suggestion' => 'Job already has urgent priority'
            ]
        );
    }

    // -------------------------------------------------------------------------
    // Eager load lists
    // -------------------------------------------------------------------------
    private function eagerRelations(bool $detailed = false): array
    {
        $base = [
            'company',
            'jobLocation',
            'jobType',
            'experienceLevel',
            'educationLevel',
            'salaryRange',
        ];

        if ($detailed) {
            $base[] = 'jobCategory';
            $base[] = 'industry';
            $base[] = 'poster';
        }

        return $base;
    }

    // -------------------------------------------------------------------------
    // Format job data — safe, flat, no heavy JSON columns
    // -------------------------------------------------------------------------
    private function formatJobData($job, bool $detailed = false): array
    {
        try {
            $base = [
                // Core
                'id'                    => $job->id,
                'slug'                  => $job->slug ?? '',
                'job_title'             => $job->job_title ?? '',
                'employment_type'       => $job->employment_type ?? 'full-time',
                'location_type'         => $job->location_type ?? 'on-site',
                'work_hours'            => $job->work_hours ?? '',
                'duty_station'          => $job->duty_station ?? '',
                'street_address'        => $job->street_address ?? '',

                // Salary
                'salary_amount'         => $job->salary_amount,
                'currency'              => $job->currency ?? 'UGX',
                'payment_period'        => $job->payment_period ?? '',
                'formatted_salary'      => $this->formatSalary($job),

                // Dates
                'deadline'              => $job->deadline
                                            ? $job->deadline->format('Y-m-d') : null,
                'published_at'          => $job->published_at
                                            ? $job->published_at->format('Y-m-d H:i:s') : null,
                'created_at'            => $job->created_at
                                            ? $job->created_at->format('Y-m-d H:i:s') : null,

                // Status flags
                'is_active'             => (bool) ($job->is_active   ?? false),
                'is_verified'           => (bool) ($job->is_verified  ?? false),
                'is_featured'           => (bool) ($job->is_featured  ?? false),
                'is_urgent'             => (bool) ($job->is_urgent    ?? false),
                'is_pinged'             => (bool) ($job->is_pinged    ?? false),
                'is_indexed'            => (bool) ($job->is_indexed   ?? false),
                'is_whatsapp_contact'   => (bool) ($job->is_whatsapp_contact ?? false),
                'is_telephone_call'     => (bool) ($job->is_telephone_call   ?? false),

                // Timestamps for status
                'last_pinged_at'        => $job->last_pinged_at
                                            ? $job->last_pinged_at->format('Y-m-d H:i:s') : null,
                'last_indexed_at'       => $job->last_indexed_at
                                            ? $job->last_indexed_at->format('Y-m-d H:i:s') : null,

                // Application requirements
                'is_resume_required'                => (bool) ($job->is_resume_required               ?? true),
                'is_cover_letter_required'          => (bool) ($job->is_cover_letter_required          ?? false),
                'is_academic_documents_required'    => (bool) ($job->is_academic_documents_required    ?? false),

                // Counters
                'view_count'            => (int) ($job->view_count        ?? 0),
                'application_count'     => (int) ($job->application_count ?? 0),
                'click_count'           => (int) ($job->click_count       ?? 0),

                // SEO (safe scalars only)
                'seo_score'             => $job->seo_score ?? null,

                // Relations — always loaded
                'company'      => $job->company ? [
                    'id'           => $job->company->id,
                    'name'         => $job->company->name         ?? '',
                    'logo'         => $job->company->logo_url     ?? null,
                    'website'      => $job->company->website      ?? null,
                    'company_size' => $job->company->company_size ?? null,
                    'contact_email'=> $job->company->contact_email ?? null,
                ] : null,

                'job_location' => $job->jobLocation ? [
                    'id'       => $job->jobLocation->id,
                    'district' => $job->jobLocation->district ?? '',
                    'country'  => $job->jobLocation->country  ?? '',
                    'name'     => $job->jobLocation->district
                                    ?? $job->jobLocation->country ?? '',
                ] : null,

                'job_type' => $job->jobType ? [
                    'id'   => $job->jobType->id,
                    'name' => $job->jobType->name ?? '',
                ] : ['name' => $job->employment_type ?? 'Full Time'],

                'experience_level' => $job->experienceLevel ? [
                    'id'        => $job->experienceLevel->id,
                    'name'      => $job->experienceLevel->name      ?? '',
                    'min_years' => $job->experienceLevel->min_years ?? null,
                    'max_years' => $job->experienceLevel->max_years ?? null,
                ] : null,

                'education_level' => $job->educationLevel ? [
                    'id'   => $job->educationLevel->id,
                    'name' => $job->educationLevel->name ?? '',
                ] : null,

                'salary_range' => $job->salaryRange ? [
                    'id'       => $job->salaryRange->id,
                    'name'     => $job->salaryRange->name       ?? '',
                    'min'      => $job->salaryRange->min_salary ?? null,
                    'max'      => $job->salaryRange->max_salary ?? null,
                    'currency' => $job->salaryRange->currency   ?? 'UGX',
                ] : null,

                'poster'    => $job->poster ? [
                    'id'    => $job->poster->id,
                    'name'  => trim(($job->poster->first_name ?? '') . ' ' . ($job->poster->last_name ?? '')),
                    'email' => $job->poster->email ?? '',
                ] : null,
            ];

            // Detailed — only on show/store/update
            if ($detailed) {
                $base['job_description']     = $job->job_description     ?? '';
                $base['responsibilities']    = $job->responsibilities    ?? '';
                $base['qualifications']      = $job->qualifications      ?? '';
                $base['skills']              = $job->skills              ?? '';
                $base['application_procedure'] = $job->application_procedure ?? '';
                $base['email']               = $job->email               ?? '';
                $base['telephone']           = $job->telephone           ?? '';
                $base['meta_title']          = $job->meta_title          ?? '';
                $base['meta_description']    = $job->meta_description    ?? '';
                $base['keywords']            = $job->keywords            ?? '';
                $base['content_quality_score'] = $job->content_quality_score ?? null;
                $base['featured_until']      = $job->featured_until
                                                ? $job->featured_until->format('Y-m-d H:i:s') : null;

                $base['job_category'] = $job->jobCategory ? [
                    'id'   => $job->jobCategory->id,
                    'name' => $job->jobCategory->name ?? '',
                ] : null;

                $base['industry'] = $job->industry ? [
                    'id'   => $job->industry->id,
                    'name' => $job->industry->name ?? '',
                ] : null;
            }

            return $base;

        } catch (\Exception $e) {
            Log::error('formatJobData error: ' . $e->getMessage(), ['job_id' => $job->id ?? null]);
            return [
                'id'               => $job->id    ?? null,
                'slug'             => $job->slug   ?? '',
                'job_title'        => $job->job_title ?? 'Unknown Job',
                'formatted_salary' => 'Negotiable',
            ];
        }
    }

    private function formatSalary($job): string
    {
        if ($job->salary_amount) {
            $map = ['daily' => '/day', 'weekly' => '/week', 'monthly' => '/month', 'yearly' => '/year', 'hourly' => '/hr'];
            $suffix = $map[$job->payment_period ?? ''] ?? '';
            return number_format($job->salary_amount) . ' ' . ($job->currency ?? 'UGX') . $suffix;
        }

        if ($job->salaryRange) {
            $cur = $job->salaryRange->currency ?? 'UGX';
            return $cur . ' ' . number_format($job->salaryRange->min_salary)
                       . ' – ' . number_format($job->salaryRange->max_salary);
        }

        return 'Negotiable';
    }


    


    

    /**
     * GET /api/v1/job-posts/indexing-stats
     */
    public function indexingStats(Request $request): JsonResponse
    {
        try {
            $stats = [
                'pinged' => JobPost::where('is_pinged', true)->count(),
                'not_pinged' => JobPost::where('is_pinged', false)
                    ->where('is_active', true)
                    ->where('deadline', '>=', now())
                    ->count(),
                'indexed' => JobPost::where('is_indexed', true)->count(),
                'not_indexed' => JobPost::where('is_indexed', false)
                    ->where('is_active', true)
                    ->where('deadline', '>=', now())
                    ->count(),
                'submitted_to_indexing' => JobPost::where('submitted_to_indexing', true)->count(),
                'total_active' => JobPost::where('is_active', true)
                    ->where('deadline', '>=', now())
                    ->count(),
            ];

            return $this->success($stats, 'Indexing stats retrieved');
            
        } catch (\Exception $e) {
            Log::error('Indexing stats failed: ' . $e->getMessage(), [
                'file' => $e->getFile(), 'line' => $e->getLine()
            ]);
            return $this->error('Failed to load indexing stats: ' . $e->getMessage(), 500);
        }
    }

    /**
     * POST /api/v1/job-posts/manual-index
     * Manually trigger indexing for pending jobs
     */
    public function manualIndex(Request $request): JsonResponse
    {
        try {
            $mode = $request->input('mode', 'new'); // 'new' | 'all' | 'failed'
            $limit = min((int) $request->input('limit', 20), 100);

            // Build query for jobs to index
            $query = JobPost::where('is_active', true)
                ->where('deadline', '>=', now());

            if ($mode === 'new') {
                $query->whereNull('indexing_submitted_at')
                    ->orderBy('created_at', 'desc')
                    ->limit($limit);
            } elseif ($mode === 'failed') {
                $query->where('indexing_status', 'failed')
                    ->orderBy('updated_at', 'asc')
                    ->limit($limit);
            }
            // 'all' mode: no extra filters

            $jobs = $query->get(['id', 'job_title', 'slug', 'is_pinged', 'is_indexed']);

            if ($jobs->isEmpty()) {
                return $this->success([
                    'submitted' => 0,
                    'message' => 'No jobs match the criteria',
                    'results' => []
                ], 'Nothing to submit');
            }

            // Call service with explicit error handling
            $service = app(\App\Services\SearchEnginePingService::class);
            $result = $service->manualPingJobs($jobs->pluck('id')->toArray());

            // Ensure result is always an array with expected keys
            $response = [
                'submitted' => $result['submitted'] ?? 0,
                'message' => $result['message'] ?? "{$result['submitted']} jobs processed",
                'results' => array_map(function($r) {
                    return [
                        'job_id' => $r['job_id'] ?? null,
                        'title' => $r['title'] ?? 'Unknown',
                        'url' => $r['url'] ?? '',
                        'google' => [
                            'success' => $r['google']['success'] ?? false,
                            'status' => $r['google']['status'] ?? null,
                            'error' => is_string($r['google']['error'] ?? null) 
                                ? $r['google']['error'] 
                                : (is_array($r['google']['error'] ?? null) 
                                    ? json_encode($r['google']['error']) 
                                    : null),
                        ],
                        'bing' => [
                            'success' => $r['bing']['success'] ?? false,
                            'status' => $r['bing']['status'] ?? null,
                            'error' => is_string($r['bing']['error'] ?? null)
                                ? $r['bing']['error']
                                : (is_array($r['bing']['error'] ?? null)
                                    ? json_encode($r['bing']['error'])
                                    : null),
                        ],
                    ];
                }, $result['results'] ?? []),
            ];

            return $this->success($response, 'Manual indexing completed');

        } catch (\Illuminate\Database\QueryException $e) {
            Log::error('DB error in manualIndex: ' . $e->getMessage());
            return $this->error('Database error: ' . $e->getMessage(), 500);
        } catch (\Exception $e) {
            Log::error('Manual indexing failed: ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString()
            ]);
            return $this->error('Indexing failed: ' . $e->getMessage(), 500);
        }
    }

    /**
     * POST /api/v1/job-posts/single-index/{id}
     * Index a single job by ID
     */
    public function singleIndex(Request $request, $id): JsonResponse
    {
        try {
            $job = JobPost::where('id', $id)
                ->where('is_active', true)
                ->where('deadline', '>=', now())
                ->first();

            if (!$job) {
                return $this->error('Job not found or not eligible for indexing', 404);
            }

            $service = app(\App\Services\SearchEnginePingService::class);
            $result = $service->manualPingJobs([$job->id]);

            $r = $result['results'][0] ?? null;

            return $this->success([
                'job' => [
                    'id' => $job->id,
                    'title' => $job->job_title,
                    'url' => url('/jobs/' . $job->slug),
                ],
                'google' => [
                    'success' => $r['google']['success'] ?? false,
                    'status' => $r['google']['status'] ?? null,
                    'error' => $r['google']['error'] ?? null,
                ],
                'bing' => [
                    'success' => $r['bing']['success'] ?? false,
                    'status' => $r['bing']['status'] ?? null,
                    'error' => $r['bing']['error'] ?? null,
                ],
            ], 'Indexing attempt completed');

        } catch (\Exception $e) {
            Log::error('Single job indexing failed: ' . $e->getMessage());
            return $this->error('Failed: ' . $e->getMessage(), 500);
        }
    }

    /**
     * GET /api/v1/job-posts/indexing-status/{id}
     * Get indexing status for a specific job
     */
    public function indexingStatus($id): JsonResponse
    {
        try {
            $job = JobPost::findOrFail($id);
            
            return response()->json([
                'success' => true,
                'data' => [
                    'id' => $job->id,
                    'title' => $job->job_title,
                    'slug' => $job->slug,
                    'url' => $job->url,
                    'is_active' => $job->is_active,
                    'is_pinged' => $job->is_pinged,
                    'last_pinged_at' => $job->last_pinged_at,
                    'is_indexed' => $job->is_indexed,
                    'last_indexed_at' => $job->last_indexed_at,
                    'submitted_to_indexing' => $job->submitted_to_indexing,
                    'indexing_submitted_at' => $job->indexing_submitted_at,
                    'indexing_status' => $job->indexing_status,
                    'indexing_response' => $job->indexing_response,
                ]
            ]);
            
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 500);
        }
    }

}