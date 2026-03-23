<?php

namespace App\Http\Controllers\Api\Jobs;

use App\Http\Concerns\ApiResponse;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\Jobs\JobPostRequest;
use App\Models\Job\{ JobPost, Company, JobLocation };
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

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
            'click_count', 'seo_score',
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
            $company = Company::find($companyId);
            if ($company && $company->name) {
                $slug = Str::slug($title . ' at ' . $company->name);
            }
        }
        
        if ($locationId) {
            $location = JobLocation::find($locationId);
            if ($location && ($location->district || $location->country)) {
                $locationName = $location->district ?? $location->country;
                $slug = Str::slug($title . ' in ' . $locationName);
            }
        }
        
        // Ensure uniqueness
        $originalSlug = $slug;
        $counter = 1;
        
        while (JobPost::where('slug', $slug)->exists()) {
            $slug = $originalSlug . '-' . $counter;
            $counter++;
        }
        
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
            
            // Set default values if not provided
            $validated['is_active'] = $validated['is_active'] ?? true;
            $validated['is_verified'] = $validated['is_verified'] ?? false;
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
                        
            // Set SEO meta data if not provided
            if (empty($validated['meta_title'])) {
                $validated['meta_title'] = Str::limit($validated['job_title'], 60);
            }
            
            if (empty($validated['meta_description'])) {
                $validated['meta_description'] = Str::limit(strip_tags($validated['job_description'] ?? ''), 160);
            }
            
            if (empty($validated['keywords'])) {
                $keywords = explode(' ', $validated['job_title']);
                $validated['keywords'] = implode(', ', array_slice($keywords, 0, 5));
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

            $formatted = $this->formatJobData($fresh, true);

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
        $jobPost = JobPost::where('slug', $slug)->firstOrFail();
        $jobPost->delete();
        return $this->deleted('Job post deleted successfully');
    }

    // -------------------------------------------------------------------------
    // Status / Action endpoints
    // -------------------------------------------------------------------------

    public function activate($slug): JsonResponse
    {
        $job = JobPost::where('slug', $slug)->firstOrFail();
        $job->activate();
        return $this->success($this->formatJobData($job->fresh(), false), 'Job post activated successfully');
    }

    public function deactivate($slug): JsonResponse
    {
        $job = JobPost::where('slug', $slug)->firstOrFail();
        $job->deactivate();
        return $this->success($this->formatJobData($job->fresh(), false), 'Job post deactivated successfully');
    }

    public function verify($slug): JsonResponse
    {
        $job = JobPost::where('slug', $slug)->firstOrFail();
        $job->verify();
        return $this->success($this->formatJobData($job->fresh(), false), 'Job post verified successfully');
    }

    public function feature(Request $request, $slug): JsonResponse
    {
        $job  = JobPost::where('slug', $slug)->firstOrFail();
        $days = $request->integer('days', 7);
        $job->markAsFeatured($days);
        return $this->success($this->formatJobData($job->fresh(), false), "Job post featured for {$days} days");
    }

    public function markUrgent($slug): JsonResponse
    {
        $job = JobPost::where('slug', $slug)->firstOrFail();
        $job->markAsUrgent();
        return $this->success($this->formatJobData($job->fresh(), false), 'Job post marked as urgent');
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
}