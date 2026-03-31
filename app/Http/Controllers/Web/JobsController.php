<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Job\{ JobPost };
use App\Models\{ Notification };
use Illuminate\Support\Facades\{ Log, Http, DB, Mail  };

class JobsController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        try {
            // Log request for debugging
            // Log::info('Main App JobsController index called');
            // Log::info('Request URL: ' . $request->fullUrl());
            // Log::info('All request parameters:', $request->all());
            
            // Start query with relationships
            $query = JobPost::with([
                'company',
                'jobCategory',
                'industry',
                'jobLocation',
                'jobType',
                'experienceLevel',
                'educationLevel',
                'salaryRange',
                'poster'
            ])
            ->where('is_active', true)
            ->latest();
            
            // Apply keyword search if provided
            if ($request->has('keyword') && !empty($request->keyword)) {
                $keyword = $request->keyword;
                // Log::info('Searching for keyword: ' . $keyword);
                
                $query->where(function($q) use ($keyword) {
                    $q->where('job_title', 'LIKE', "%{$keyword}%")
                      ->orWhere('job_description', 'LIKE', "%{$keyword}%")
                      ->orWhere('skills', 'LIKE', "%{$keyword}%")
                      ->orWhere('qualifications', 'LIKE', "%{$keyword}%")
                      ->orWhere('responsibilities', 'LIKE', "%{$keyword}%")
                      // Search in company name (from Company model)
                      ->orWhereHas('company', function($companyQuery) use ($keyword) {
                          $companyQuery->where('name', 'LIKE', "%{$keyword}%");
                      })
                      // Search in job location (from JobLocation model)
                      ->orWhereHas('jobLocation', function($locationQuery) use ($keyword) {
                          $locationQuery->where('country', 'LIKE', "%{$keyword}%")
                                       ->orWhere('district', 'LIKE', "%{$keyword}%");
                      })
                      // Search in job category
                      ->orWhereHas('jobCategory', function($categoryQuery) use ($keyword) {
                          $categoryQuery->where('name', 'LIKE', "%{$keyword}%");
                      })
                      // Search in industry
                      ->orWhereHas('industry', function($industryQuery) use ($keyword) {
                          $industryQuery->where('name', 'LIKE', "%{$keyword}%");
                      });
                });
            }
            
            // Apply location search if provided
            if ($request->has('location') && !empty($request->location)) {
                $location = $request->location;
                // Log::info('Searching for location: ' . $location);
                
                $query->where(function($q) use ($location) {
                    $q->where('duty_station', 'LIKE', "%{$location}%")
                      ->orWhereHas('jobLocation', function($locationQuery) use ($location) {
                          $locationQuery->where('country', 'LIKE', "%{$location}%")
                                       ->orWhere('district', 'LIKE', "%{$location}%");
                      });
                });
            }
            
            // Log the SQL query for debugging
            // Log::info('SQL Query: ' . $query->toSql());
            // Log::info('Query Bindings: ', $query->getBindings());
            
            // Apply sorting
            if ($request->has('sort')) {
                switch ($request->sort) {
                    case 'oldest':
                        $query->orderBy('created_at', 'asc');
                        break;
                    case 'salary_high':
                        $query->orderBy('salary_amount', 'desc');
                        break;
                    case 'salary_low':
                        $query->orderBy('salary_amount', 'asc');
                        break;
                    default:
                        $query->orderBy('created_at', 'desc');
                }
            } else {
                $query->orderBy('created_at', 'desc');
            }
            
            // Paginate results
            $jobs = $query->paginate(20);
            
            // Log::info('Jobs fetched successfully: ' . $jobs->total() . ' total, showing ' . $jobs->count());
            
            // Transform the data to include formatted fields
            $jobs->getCollection()->transform(function ($job) {
                return $this->formatJobData($job);
            });
            // Log::info($jobs);
            
            return response()->json($jobs);
            
        } catch (\Exception $e) {
            Log::error('Error fetching jobs: ' . $e->getMessage());
            Log::error('Error trace: ' . $e->getTraceAsString());
            
            return response()->json([
                'error' => 'Failed to fetch jobs',
                'message' => $e->getMessage()
            ], 500);
        }
    }
    
    /**
     * Get featured jobs
     */
    public function featured(Request $request)
    {
        try {
            $jobs = JobPost::with([
                'company',
                'jobLocation',
                'jobType',
                'experienceLevel',
                'salaryRange'
            ])
            ->where('is_active', true)
            ->where('is_featured', true)
            ->orderBy('created_at', 'desc')
            ->limit(6)
            ->get()
            ->map(function ($job) {
                return $this->formatJobData($job);
            });
            
            return response()->json($jobs);
            
        } catch (\Exception $e) {
            Log::error('Error fetching featured jobs: ' . $e->getMessage());
            
            return response()->json([
                'error' => 'Failed to fetch featured jobs'
            ], 500);
        }
    }
    
    
    /**
     * Format job data for API response - FIXED VERSION
     */
    private function formatJobData($job, $detailed = false)
    {
        try {
            // Base data with safe null checks
            $baseData = [
                'id' => $job->id,
                'job_title' => $job->job_title ?? '',
                'slug' => $job->slug ?? '',
                'job_description' => $job->job_description ?? '',
                'responsibilities' => $job->responsibilities ?? '',
                'qualifications' => $job->qualifications ?? '',
                'skills' => $job->skills ?? '',
                'application_procedure' => $job->application_procedure ?? '',
                'email' => $job->email ?? '',
                'telephone' => $job->telephone ?? '',
                'duty_station' => $job->duty_station ?? '',
                'street_address' => $job->street_address ?? '',
                'location_type' => $job->location_type ?? 'on-site',
                'work_hours' => $job->work_hours ?? '',
                'employment_type' => $job->employment_type ?? 'full-time',
                'salary_amount' => $job->salary_amount,
                'currency' => $job->currency ?? 'UGX',
                'payment_period' => $job->payment_period ?? 'monthly',
                'is_featured' => (bool) ($job->is_featured ?? false),
                'is_urgent' => (bool) ($job->is_urgent ?? false),
                'is_verified' => (bool) ($job->is_verified ?? false),
                'view_count' => (int) ($job->view_count ?? 0),
                'social_shares' => (int) ($job->click_count ?? 0),
                'application_count' => (int) ($job->application_count ?? 0),
                'deadline' => $job->deadline ? $job->deadline->format('Y-m-d') : null,
                'created_at' => $job->created_at ? $job->created_at->format('Y-m-d H:i:s') : now()->format('Y-m-d H:i:s'),
                'published_at' => $job->published_at ? $job->published_at->format('Y-m-d H:i:s') : null,
            ];

            // Add formatted salary
            $baseData['formatted_salary'] = $this->formatSalary($job);

            // Safely add company data
            if ($job->company) {
                $baseData['company'] = [
                    'id' => $job->company->id,
                    'name' => $job->company->name ?? '',
                    'logo' => $job->company->logo_url ?? null,
                    'website' => $job->company->website ?? null,
                ];
            } else {
                $baseData['company'] = null;
            }

            // Safely add job location
            if ($job->jobLocation) {
                $baseData['job_location'] = [
                    'id' => $job->jobLocation->id,
                    'country' => $job->jobLocation->country ?? '',
                    'district' => $job->jobLocation->district ?? '',
                    'name' => $job->jobLocation->district ?? $job->jobLocation->country ?? '',
                ];
            } else {
                $baseData['job_location'] = null;
            }

            // Safely add job type
            if ($job->jobType) {
                $baseData['job_type'] = [
                    'id' => $job->jobType->id,
                    'name' => $job->jobType->name ?? '',
                ];
            } else {
                $baseData['job_type'] = ['name' => $job->employment_type ?? 'Full Time'];
            }

            // Safely add experience level
            if ($job->experienceLevel) {
                $baseData['experience_level'] = [
                    'id' => $job->experienceLevel->id,
                    'name' => $job->experienceLevel->name ?? '',
                ];
            } else {
                $baseData['experience_level'] = null;
            }

            // Safely add education level
            if ($job->educationLevel) {
                $baseData['education_level'] = [
                    'id' => $job->educationLevel->id,
                    'name' => $job->educationLevel->name ?? '',
                ];
            } else {
                $baseData['education_level'] = null;
            }

            // Safely add salary range
            if ($job->salaryRange) {
                $baseData['salary_range'] = [
                    'id' => $job->salaryRange->id,
                    'name' => $job->salaryRange->name ?? '',
                    'min' => $job->salaryRange->min_salary ?? null,
                    'max' => $job->salaryRange->max_salary ?? null,
                    'currency' => $job->salaryRange->currency ?? 'UGX',
                ];
            } else {
                $baseData['salary_range'] = null;
            }

            // Add detailed data if requested
            if ($detailed) {
                $detailedData = [
                    'job_category' => $job->jobCategory ? [
                        'id' => $job->jobCategory->id,
                        'name' => $job->jobCategory->name ?? '',
                    ] : null,
                    'industry' => $job->industry ? [
                        'id' => $job->industry->id,
                        'name' => $job->industry->name ?? '',
                        'estimated_salary' => $job->industry->estimated_salary ?? '',
                    ] : null,
                    'poster' => $job->poster ? [
                        'id' => $job->poster->id,
                        'name' => $job->poster->name ?? '',
                        'email' => $job->poster->email ?? '',
                    ] : null,
                    'meta_title' => $job->meta_title ?? '',
                    'meta_description' => $job->meta_description ?? '',
                    'keywords' => $job->keywords ?? '',
                    'seo_score' => $job->seo_score ?? null,
                    'application_requirements' => [
                        'cover_letter_required' => (bool) ($job->is_cover_letter_required ?? false),
                        'resume_required' => (bool) ($job->is_resume_required ?? true),
                        'academic_documents_required' => (bool) ($job->is_academic_documents_required ?? false),
                    ]
                ];
                
                return array_merge($baseData, $detailedData);
            }

            return $baseData;

        } catch (\Exception $e) {
            Log::error('Error in formatJobData: ' . $e->getMessage());
            Log::error('Job ID: ' . ($job->id ?? 'unknown'));
            
            // Return basic data if formatting fails
            return [
                'id' => $job->id ?? null,
                'job_title' => $job->job_title ?? 'Unknown Job',
                'slug' => $job->slug ?? '',
                'formatted_salary' => 'Negotiable',
                'error' => 'Partial data - some details unavailable'
            ];
        }
    }
    
    /**
     * Format salary for display
     */
    private function formatSalary($job)
    {
        if ($job->salary_amount) {
            $period = $job->payment_period ?? 'monthly';
            $periodText = $period === 'daily' ? '/day' : ($period === 'weekly' ? '/week' : ($period === 'monthly' ? '/month' : '/year'));
            return number_format($job->salary_amount) . ' ' . ($job->currency ?? 'UGX') . $periodText;
        }
        
        if ($job->salaryRange) {
            $min = number_format($job->salaryRange->min_salary);
            $max = number_format($job->salaryRange->max_salary);
            $currency = $job->salaryRange->currency ?? 'UGX';
            return "{$currency} {$min} - {$max}";
        }
        
        return 'Negotiable';
    }
    
    /**
     * Get urgent jobs
     */
    public function urgent(Request $request)
    {
        try {
            $jobs = JobPost::with([
                'company',
                'jobLocation',
                'jobType',
                'experienceLevel',
                'salaryRange'
            ])
            ->where('is_active', true)
            ->where('is_urgent', true)
            ->orderBy('created_at', 'desc')
            ->limit(4)
            ->get()
            ->map(function ($job) {
                return $this->formatJobData($job);
            });
            
            return response()->json($jobs);
            
        } catch (\Exception $e) {
            Log::error('Error fetching urgent jobs: ' . $e->getMessage());
            
            return response()->json([
                'error' => 'Failed to fetch urgent jobs'
            ], 500);
        }
    }

    /**
     * Get popular search keywords from last 3 months
     */
    public function popularSearches(Request $request)
    {
        try {
            // Get jobs from last 3 months
            $threeMonthsAgo = now()->subMonths(3);
            
            // Extract keywords from job titles and skills
            $jobs = JobPost::where('created_at', '>=', $threeMonthsAgo)
                ->where('is_active', true)
                ->select('job_title', 'skills', 'job_description')
                ->limit(100)
                ->get();
            
            $keywords = [];
            $stopWords = ['the', 'and', 'for', 'with', 'this', 'that', 'are', 'from', 'have', 'will', 'jobs', 'job', 'position', 'looking', 'candidate', 'requirements', 'skills', 'experience', 'qualifications', 'responsibilities'];
            
            foreach ($jobs as $job) {
                // Extract from job title
                $titleWords = explode(' ', $job->job_title);
                foreach ($titleWords as $word) {
                    $word = trim(strtolower($word));
                    if (strlen($word) > 3 && !in_array($word, $stopWords) && !is_numeric($word)) {
                        $keywords[$word] = ($keywords[$word] ?? 0) + 3; // Title words weight 3
                    }
                }
                
                // Extract from skills
                if ($job->skills) {
                    $skillItems = explode(',', $job->skills);
                    foreach ($skillItems as $skill) {
                        $skill = trim(strtolower($skill));
                        if (strlen($skill) > 2 && !in_array($skill, $stopWords)) {
                            $keywords[$skill] = ($keywords[$skill] ?? 0) + 2; // Skills weight 2
                        }
                    }
                }
            }
            
            // Sort by frequency and get top 6
            arsort($keywords);
            $topKeywords = array_slice(array_keys($keywords), 0, 6);
            
            // Format for display (capitalize first letter of each word)
            $formattedKeywords = array_map(function($keyword) {
                return ucwords(str_replace('-', ' ', $keyword));
            }, $topKeywords);
            
            return response()->json($formattedKeywords);
            
        } catch (\Exception $e) {
            Log::error('Error fetching popular searches: ' . $e->getMessage());
            return response()->json(['Remote', 'Full Stack', 'Manager', 'Developer', 'Engineer', 'Analyst']); // Fallback
        }
    }

    
    /**
     * Get a single job by slug - COMPLETE WORKING VERSION
     */
    public function show(Request $request, $identifier)
    {
        try {
            // Load job with all relationships
            $job = JobPost::with([
                'company',
                'jobLocation',
                'jobCategory',
                'industry',
                'jobType',
                'experienceLevel',
                'educationLevel',
                'salaryRange',
                'poster'
            ])
            ->where('slug', $identifier)
            ->first();
            
            if (!$job) {
                return response()->json(['error' => 'Job not found'], 404);
            }
            
            // Safely increment view count
            \DB::table('job_posts')->where('id', $job->id)->increment('view_count');
            
            // Get similar jobs
            $similarJobs = $this->getSimilarJobs($job);
            
            // Build the formatted response with ALL fields
            $response = [
                'id' => $job->id,
                'job_title' => $job->job_title,
                'slug' => $job->slug,
                'job_description' => $job->job_description,
                'responsibilities' => $job->responsibilities,
                'qualifications' => $job->qualifications,
                'skills' => $job->skills,
                'application_procedure' => $job->application_procedure,
                'email' => $job->email,
                'telephone' => $job->telephone,
                'duty_station' => $job->duty_station,
                'street_address' => $job->street_address,
                'location_type' => $job->location_type ?? 'on-site',
                'work_hours' => $job->work_hours,
                'employment_type' => $job->employment_type,
                'salary_amount' => $job->salary_amount,
                'currency' => $job->currency,
                'payment_period' => $job->payment_period,
                
                // ============================================================
                // BOOLEAN FLAGS - ADD THESE!
                // ============================================================
                'is_featured' => (bool) $job->is_featured,
                'is_urgent' => (bool) $job->is_urgent,
                'is_verified' => (bool) $job->is_verified,
                'is_active' => (bool) $job->is_active,
                'is_pinged' => (bool) $job->is_pinged,
                'is_indexed' => (bool) $job->is_indexed,
                'is_simple_job' => (bool) $job->is_simple_job,
                'is_quick_gig' => (bool) $job->is_quick_gig,
                
                // Contact method flags - CRITICAL FOR THE MODAL
                'is_whatsapp_contact' => (bool) $job->is_whatsapp_contact,
                'is_telephone_call' => (bool) $job->is_telephone_call,
                
                // Application requirement flags
                'is_resume_required' => (bool) ($job->is_resume_required ?? true),
                'is_cover_letter_required' => (bool) ($job->is_cover_letter_required ?? false),
                'is_academic_documents_required' => (bool) ($job->is_academic_documents_required ?? false),
                'is_application_required' => (bool) ($job->is_application_required ?? false),
                // ============================================================
                
                'view_count' => (int) $job->view_count + 1,
                'application_count' => (int) $job->application_count,
                'social_shares' => (int) ($job->click_count ?? 0),
                'deadline' => $job->deadline ? $job->deadline->format('Y-m-d\TH:i:s.u\Z') : null,
                'created_at' => $job->created_at ? $job->created_at->format('Y-m-d\TH:i:s.u\Z') : null,
                
                // Relationships
                'company' => $job->company ? [
                    'id' => $job->company->id,
                    'name' => $job->company->name,
                    'logo' => $job->company->logo_url ?? null,
                    'website' => $job->company->website ?? null,
                    'description' => $job->company->description ?? '',
                    'industry' => $job->industry ? ['name' => $job->industry->name] : null,
                ] : null,
                
                'job_location' => $job->jobLocation ? [
                    'id' => $job->jobLocation->id,
                    'country' => $job->jobLocation->country,
                    'district' => $job->jobLocation->district,
                ] : null,
                
                'job_category' => $job->jobCategory ? [
                    'id' => $job->jobCategory->id,
                    'name' => $job->jobCategory->name,
                    'slug' => $job->jobCategory->slug,
                ] : null,
                
                'industry' => $job->industry ? [
                    'id' => $job->industry->id,
                    'name' => $job->industry->name,
                    'estimated_salary' => $job->industry->estimated_salary,
                ] : null,
                
                'job_type' => $job->jobType ? [
                    'id' => $job->jobType->id,
                    'name' => $job->jobType->name,
                ] : ['name' => $job->employment_type ?? 'Full Time'],
                
                'experience_level' => $job->experienceLevel ? [
                    'id' => $job->experienceLevel->id,
                    'name' => $job->experienceLevel->name,
                    'min_years' => $job->experienceLevel->min_years,
                    'max_years' => $job->experienceLevel->max_years,
                ] : null,
                
                'education_level' => $job->educationLevel ? [
                    'id' => $job->educationLevel->id,
                    'name' => $job->educationLevel->name,
                ] : null,
                
                'salary_range' => $job->salaryRange ? [
                    'id' => $job->salaryRange->id,
                    'name' => $job->salaryRange->name,
                    'min_salary' => $job->salaryRange->min_salary,
                    'max_salary' => $job->salaryRange->max_salary,
                    'currency' => $job->salaryRange->currency,
                ] : null,
                
                'poster' => $job->poster ? [
                    'id' => $job->poster->id,
                    'name' => $job->poster->name,
                    'email' => $job->poster->email,
                ] : null,
                
                'formatted_salary' => $this->formatSalaryForShow($job),
            ];
            
            // Add formatted similar jobs
            $response['similar_jobs'] = [];
            foreach ($similarJobs as $similarJob) {
                $response['similar_jobs'][] = [
                    'id' => $similarJob->id,
                    'job_title' => $similarJob->job_title,
                    'slug' => $similarJob->slug,
                    'duty_station' => $similarJob->duty_station,
                    'formatted_salary' => $this->formatSalaryForShow($similarJob),
                    'company' => $similarJob->company ? [
                        'name' => $similarJob->company->name,
                        'logo' => $similarJob->company->logo_url ?? null,
                    ] : ['name' => 'Unknown'],
                    'job_type' => $similarJob->jobType ? [
                        'name' => $similarJob->jobType->name,
                    ] : ['name' => $similarJob->employment_type ?? 'Full Time'],
                ];
            }
            
            return response()->json($response);
            
        } catch (\Exception $e) {
            Log::error('Error in show: ' . $e->getMessage());
            Log::error('Line: ' . $e->getLine());
            
            return response()->json([
                'error' => 'Failed to fetch job',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get similar jobs based on category or location
     */
    private function getSimilarJobs($job, $limit = 3)
    {
        try {
            if (!$job || !isset($job->id)) {
                return [];
            }
            
            $query = JobPost::with([
                'company',
                'jobLocation',
                'jobType'
            ])
            ->where('is_active', true)
            ->where('id', '!=', $job->id);
            
            // Prioritize jobs from same category
            if (!empty($job->job_category_id)) {
                $query->where('job_category_id', $job->job_category_id);
            } 
            // Fallback to same location
            elseif (!empty($job->job_location_id)) {
                $query->where('job_location_id', $job->job_location_id);
            }
            // Fallback to same company
            elseif (!empty($job->company_id)) {
                $query->where('company_id', $job->company_id);
            }
            
            return $query->orderBy('created_at', 'desc')
                ->limit($limit)
                ->get();
            
        } catch (\Exception $e) {
            Log::error('Error fetching similar jobs: ' . $e->getMessage());
            return [];
        }
    }

    /**
     * Format salary for show
     */
    private function formatSalaryForShow($job)
    {
        try {
            if (!empty($job->salary_amount) && $job->salary_amount > 0) {
                $amount = number_format((float)$job->salary_amount);
                $currency = $job->currency ?? 'UGX';
                $period = $job->payment_period ?? 'monthly';
                
                $periodText = $period === 'daily' ? '/day' : 
                            ($period === 'weekly' ? '/week' : 
                            ($period === 'monthly' ? '/month' : '/year'));
                
                return $currency . ' ' . $amount . $periodText;
            }
            
            return 'Negotiable';
            
        } catch (\Exception $e) {
            return 'Negotiable';
        }
    }


        /**
     * Report missing application link
     */
    public function reportMissingLink(Request $request)
    {
        try {
            // Validate the request
            $validated = $request->validate([
                'job_id' => 'required|integer',
                'job_title' => 'required|string|max:255',
                'company_name' => 'nullable|string|max:255',
                'url' => 'required|url',
                'user_agent' => 'nullable|string',
                'reported_at' => 'nullable|date',
                'reported_by_email' => 'nullable|email',
                'reported_by_name' => 'nullable|string|max:255'
            ]);

            // Log the report
            // Log::info('Missing application link reported', $validated);
            
            // Get the job if it exists
            $job = JobPost::find($validated['job_id']);
            
            // Store in notifications table
            $notification = Notification::create([
                'type' => 'missing_application_link',
                'title' => 'Missing Application Link Reported',
                'message' => "A user reported that the job \"{$validated['job_title']}\" at {$validated['company_name']} has no application link.",
                'data' => json_encode([
                    'job_id' => $validated['job_id'],
                    'job_title' => $validated['job_title'],
                    'company_name' => $validated['company_name'],
                    'url' => $validated['url'],
                    'user_agent' => $validated['user_agent'] ?? $request->userAgent(),
                    'reported_by_email' => $validated['reported_by_email'] ?? null,
                    'reported_by_name' => $validated['reported_by_name'] ?? null,
                    'reported_at' => $validated['reported_at'] ?? now(),
                    'job_exists' => $job ? true : false
                ]),
                'status' => 'unread',
                'priority' => 'medium',
                'created_at' => now(),
                'updated_at' => now()
            ]);
            
            // Send email notification to admin
            $this->sendAdminNotification($validated, $job);
            
            // Return success response
            return response()->json([
                'success' => true,
                'message' => 'Report submitted successfully. Our team will review and update the job posting.',
                'notification_id' => $notification->id,
                'data' => [
                    'reported_at' => now()->format('Y-m-d H:i:s'),
                    'reference_id' => $notification->id
                ]
            ]);
            
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $e->errors()
            ], 422);
            
        } catch (\Exception $e) {
            Log::error('Failed to report missing link: ' . $e->getMessage());
            Log::error('Stack trace: ' . $e->getTraceAsString());
            
            return response()->json([
                'success' => false,
                'message' => 'Failed to submit report. Please try again or contact support.',
                'error' => config('app.debug') ? $e->getMessage() : null
            ], 500);
        }
    }
    
    /**
     * Send admin notification email
     */
    private function sendAdminNotification($data, $job = null)
    {
        try {
            // Get admin emails from config or database
            $adminEmails = config('mail.admin_emails', ['admin@stardenaworks.com']);
            
            // Convert string to array if needed
            if (is_string($adminEmails)) {
                $adminEmails = array_map('trim', explode(',', $adminEmails));
            }
            
            // Ensure it's an array
            if (!is_array($adminEmails) || empty($adminEmails)) {
                $adminEmails = ['admin@stardenaworks.com'];
            }
            
            // Send email to each admin
            foreach ($adminEmails as $email) {
                if (filter_var($email, FILTER_VALIDATE_EMAIL)) {
                    Mail::send('emails.admin.missing-link-report', [
                        'jobTitle' => $data['job_title'],
                        'companyName' => $data['company_name'],
                        'jobId' => $data['job_id'],
                        'job' => $job,
                        'url' => $data['url'],
                        'reportedBy' => $data['reported_by_name'] ?? $data['reported_by_email'] ?? 'Guest User',
                        'reportedAt' => now()->format('Y-m-d H:i:s'),
                        'userAgent' => $data['user_agent'] ?? 'Not provided',
                        'dashboardLink' => url('/admin/notifications')
                    ], function ($message) use ($email) {
                        $message->to($email)
                                ->subject('⚠️ [URGENT] Missing Application Link Report - Action Required');
                    });
                }
            }
            
            Log::info('Admin notification sent for missing link report', ['job_id' => $data['job_id']]);
            
        } catch (\Exception $e) {
            Log::error('Failed to send admin notification email: ' . $e->getMessage());
            // Don't throw - we still want to return success to the user even if email fails
        }
    }


    public function incrementShare($jobId, Request $request)
    {
        try {
            // Update directly without loading model
            $updated = JobPost::where('id', $jobId)
                ->increment('click_count');

            if ($updated) {
                return response()->json([
                    'success' => (bool) $updated
                ]);
            }
            
            
        } catch (\Exception $e) {
            \Log::error('Share increment failed: ' . $e->getMessage());
            return response()->json(['success' => false], 500);
        }
    }

    public function incrementApplication($job, Request $request)
    {
        try {
            $updated = DB::table('job_posts')
                ->where('id', $job)
                ->increment('application_count');
            
            if (!$updated) {
                return response()->json([
                    'success' => false,
                    'message' => 'Job not found'
                ], 404);
            }
            
            return response()->json([
                'success' => true,
                'message' => 'Application counted'
            ]);
            
        } catch (\Exception $e) {
            \Log::error('Failed to increment application count: ' . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'message' => 'Failed to record application'
            ], 500);
        }
    }





    
}