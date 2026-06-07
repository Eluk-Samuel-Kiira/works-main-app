<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Job\{ JobPost, Company, JobLocation };
use App\Models\{ Notification };
use Illuminate\Support\Facades\{ Log, Http, DB, Mail  };

class JobsCategoryController extends Controller
{
    
    public function companyJobs(Request $request)
    {
        $query = Company::where('is_active', true)
            ->withCount([
                'jobPosts' => fn($q) => $q
                    ->where('is_active', true)
                    ->where('deadline', '>=', now())
            ])
            ->with(['industry:id,name'])
            ->with(['jobPosts' => function($q) {
                $q->where('is_active', true)
                ->where('deadline', '>=', now())
                ->with('jobLocation')
                ->limit(1);
            }]);

        // Search by name
        if ($request->filled('search')) {
            $query->where('name', 'LIKE', '%' . $request->search . '%');
        }

        // Only companies with active jobs (optional filter)
        if ($request->boolean('with_jobs_only')) {
            $query->whereHas('jobPosts', fn($q) => $q
                ->where('is_active', true)
                ->where('deadline', '>=', now())
            );
        }

        $perPage = min((int) $request->get('per_page', 24), 100);

        $companies = $query
            ->orderByDesc('job_posts_count')
            ->orderBy('name')
            ->paginate($perPage);

        return response()->json([
            'data'         => $companies->map(fn($c) => [
                'id'          => $c->id,
                'name'        => $c->name,
                'slug'        => $c->slug,
                'logo'        => $c->logo_url,
                'description' => $c->description,
                'website'     => $c->website,
                'company_size'=> $c->company_size,
                'is_verified' => $c->is_verified,
                'jobs_count'  => $c->job_posts_count,
                'industry'    => $c->industry ? ['name' => $c->industry->name] : null,
                // Get location from the first active job post
                'location'    => $c->jobPosts->first()?->jobLocation ? [
                    'district' => $c->jobPosts->first()->jobLocation->district,
                    'country'  => $c->jobPosts->first()->jobLocation->country,
                ] : null,
            ]),
            'total'        => $companies->total(),
            'current_page' => $companies->currentPage(),
            'last_page'    => $companies->lastPage(),
            'per_page'     => $companies->perPage(),
        ]);
    }


    /**
     * Get companies filtered by country
     * Endpoint: /v2/company-jobs-by-country
     */
    public function companyJobsByCountry(Request $request)
    {
        $country = $request->get('country'); // KE, UG, NG
        $search = $request->get('search');
        $perPage = min((int) $request->get('per_page', 24), 100);
        
        $query = Company::where('is_active', true)
            ->withCount([
                'jobPosts' => function($q) use ($country) {
                    $q->where('is_active', true)
                    ->where('deadline', '>=', now());
                    
                    if ($country) {
                        $q->whereHas('jobLocation', function($locQ) use ($country) {
                            $locQ->where('country', $country);
                        });
                    }
                }
            ])
            ->with(['industry:id,name'])
            ->having('job_posts_count', '>', 0); // Only companies with active jobs
        
        // Search by name
        if ($search) {
            $query->where('name', 'LIKE', '%' . $search . '%');
        }
        
        $companies = $query
            ->orderByDesc('job_posts_count')
            ->orderBy('name')
            ->paginate($perPage);
        
        return response()->json([
            'data' => $companies->map(function($c) {
                // Get location from first active job post
                $firstJob = $c->jobPosts()
                    ->where('is_active', true)
                    ->where('deadline', '>=', now())
                    ->with('jobLocation')
                    ->first();
                
                return [
                    'id'          => $c->id,
                    'name'        => $c->name,
                    'slug'        => $c->slug,
                    'logo'        => $c->logo_url,
                    'description' => $c->description,
                    'website'     => $c->website,
                    'company_size'=> $c->company_size,
                    'is_verified' => $c->is_verified,
                    'jobs_count'  => $c->job_posts_count,
                    'industry'    => $c->industry ? ['name' => $c->industry->name] : null,
                    'location'    => $firstJob?->jobLocation ? [
                        'district' => $firstJob->jobLocation->district,
                        'country'  => $firstJob->jobLocation->country,
                    ] : null,
                ];
            }),
            'total'        => $companies->total(),
            'current_page' => $companies->currentPage(),
            'last_page'    => $companies->lastPage(),
            'per_page'     => $companies->perPage(),
        ]);
    }


    /**
     * Get single company details with its jobs (for country-specific company page)
     * Endpoint: /v2/company/{slug}?country=KE
     */
    public function companyDetails(Request $request, $slug)
    {
        try {
            $country = $request->get('country');
            
            $company = Company::where('slug', $slug)
                ->where('is_active', true)
                ->with(['industry'])
                ->first();
            
            if (!$company) {
                return response()->json(['error' => 'Company not found'], 404);
            }
            
            // Get similar companies (first 5) - same industry, different company
            $similarCompanies = [];
            if ($company->industry_id) {
                $similarCompanies = Company::where('industry_id', $company->industry_id)
                    ->where('is_active', true)
                    ->where('id', '!=', $company->id)
                    ->whereHas('jobPosts', function($q) use ($country) {
                        $q->where('is_active', true)
                        ->where('deadline', '>=', now());
                        
                        if ($country) {
                            $q->whereHas('jobLocation', function($locQ) use ($country) {
                                $locQ->where('country', $country);
                            });
                        }
                    })
                    ->withCount(['jobPosts' => function($q) use ($country) {
                        $q->where('is_active', true)
                        ->where('deadline', '>=', now());
                        
                        if ($country) {
                            $q->whereHas('jobLocation', function($locQ) use ($country) {
                                $locQ->where('country', $country);
                            });
                        }
                    }])
                    ->orderBy('job_posts_count', 'desc')
                    ->limit(5)
                    ->get()
                    ->map(function($c) {
                        return [
                            'id' => $c->id,
                            'name' => $c->name,
                            'slug' => $c->slug,
                            'logo' => $c->logo_url,
                            'jobs_count' => $c->job_posts_count,
                            'industry' => $c->industry ? ['name' => $c->industry->name] : null,
                        ];
                    });
            }
            
            // Get jobs for this company filtered by country
            $jobsQuery = JobPost::with([
                'company', 'jobCategory', 'jobLocation', 'jobType', 
                'experienceLevel', 'salaryRange'
            ])
            ->where('company_id', $company->id)
            ->where('is_active', true)
            ->where('deadline', '>=', now())
            ->orderBy('published_at', 'desc');
            
            if ($country) {
                $jobsQuery->whereHas('jobLocation', function($q) use ($country) {
                    $q->where('country', $country);
                });
            }
            
            $jobs = $jobsQuery->paginate(15);
            
            // Format jobs
            $formattedJobs = $jobs->getCollection()->map(function($job) {
                return [
                    'id' => $job->id,
                    'job_title' => $job->job_title,
                    'slug' => $job->slug,
                    'job_description' => substr(strip_tags($job->job_description), 0, 150),
                    'duty_station' => $job->duty_station,
                    'created_at' => $job->created_at,
                    'deadline' => $job->deadline,
                    'is_featured' => $job->is_featured,
                    'is_urgent' => $job->is_urgent,
                    'job_type' => $job->jobType ? ['name' => $job->jobType->name] : null,
                    'job_location' => $job->jobLocation ? [
                        'district' => $job->jobLocation->district,
                        'country' => $job->jobLocation->country,
                    ] : null,
                    'formatted_salary' => $this->formatSalaryHelper($job),
                ];
            });
            
            return response()->json([
                'company' => [
                    'id' => $company->id,
                    'name' => $company->name,
                    'slug' => $company->slug,
                    'logo' => $company->logo_url,
                    'description' => $company->description,
                    'website' => $company->website,
                    'company_size' => $company->company_size,
                    'is_verified' => $company->is_verified,
                    'industry' => $company->industry ? ['id' => $company->industry->id, 'name' => $company->industry->name] : null,
                    'established_year' => $company->established_year,
                    'headquarters' => $company->headquarters,
                ],
                'similar_companies' => $similarCompanies,
                'jobs' => [
                    'data' => $formattedJobs,
                    'pagination' => [
                        'current_page' => $jobs->currentPage(),
                        'last_page' => $jobs->lastPage(),
                        'per_page' => $jobs->perPage(),
                        'total' => $jobs->total(),
                    ],
                ],
            ]);
            
        } catch (\Exception $e) {
            \Log::error('Error fetching company details: ' . $e->getMessage());
            return response()->json(['error' => 'Failed to fetch company'], 500);
        }
    }

    /**
     * Helper to format salary
     */
    private function formatSalaryHelper($job)
    {
        if ($job->salary_amount) {
            $period = $job->payment_period ?? 'monthly';
            $periodText = $period === 'daily' ? '/day' : ($period === 'weekly' ? '/week' : ($period === 'monthly' ? '/month' : '/year'));
            return number_format($job->salary_amount) . ' ' . ($job->currency ?? 'UGX') . $periodText;
        }
        return 'Negotiable';
    }

    /**
     * Format salary helper
     */
    private function formatSalary($job)
    {
        if ($job->salary_amount) {
            $period = $job->payment_period ?? 'monthly';
            $periodText = $period === 'daily' ? '/day' : ($period === 'weekly' ? '/week' : ($period === 'monthly' ? '/month' : '/year'));
            return number_format($job->salary_amount) . ' ' . ($job->currency ?? 'UGX') . $periodText;
        }
        return 'Negotiable';
    }



    public function locationsByCountry(Request $request)
    {
        try {
            $country = $request->get('country');
            
            $query = JobLocation::where('is_active', true)
                ->withCount(['jobPosts' => function($q) {
                    $q->where('is_active', true)
                    ->where('deadline', '>=', now());
                }])
                ->having('job_posts_count', '>', 0);
            
            if ($country) {
                $query->where('country', $country);
            }
            
            $locations = $query->orderBy('job_posts_count', 'desc')
                ->limit(20)  // ← Keep for other uses
                ->get()
                ->map(fn($loc) => [
                    'id' => $loc->id,
                    'name' => $loc->district ?? $loc->city ?? $loc->country,
                    'district' => $loc->district,
                    'city' => $loc->city,
                    'slug' => $loc->slug,
                    'jobs_count' => $loc->job_posts_count,
                ]);
            
            return response()->json($locations);
            
        } catch (\Exception $e) {
            \Log::error('Error fetching locations by country: ' . $e->getMessage());
            return response()->json([]);
        }
    }


    /**
     * Get jobs by location slug (with country filter)
     * Endpoint: /v2/jobs-by-location/{slug}?country=KE
     */
    public function jobsByLocation(Request $request, $slug)
    {
        try {
            $country = $request->get('country');
            
            // Try to find location by slug (supports both formats)
            $location = JobLocation::where('slug', $slug)
                ->where('is_active', true)
                ->first();
            
            // If not found by exact slug, try to extract district from slug
            if (!$location) {
                $district = $this->extractDistrictFromSlug($slug);
                if ($district) {
                    $location = JobLocation::where('district', 'LIKE', $district)
                        ->where('is_active', true);
                    
                    if ($country) {
                        $location->where('country', $country);
                    }
                    
                    $location = $location->first();
                }
            }
            
            // If still not found, try by district name
            if (!$location) {
                $districtName = str_replace(['-jobs-in-', '-jobs-'], ' ', $slug);
                $districtName = ucwords(str_replace('-', ' ', $districtName));
                
                $location = JobLocation::where('district', 'LIKE', '%' . $districtName . '%')
                    ->where('is_active', true);
                
                if ($country) {
                    $location->where('country', $country);
                }
                
                $location = $location->first();
            }
            
            if (!$location) {
                return response()->json(['error' => 'Location not found'], 404);
            }
            
            // Verify location belongs to requested country if specified
            if ($country && $location->country !== $country) {
                return response()->json(['error' => 'Location not found in this country'], 404);
            }
            
            // Get similar locations (first 5) - same country, different district
            $similarLocations = JobLocation::where('country', $location->country)
                ->where('is_active', true)
                ->where('id', '!=', $location->id)
                ->withCount(['jobPosts' => function($q) {
                    $q->where('is_active', true)->where('deadline', '>=', now());
                }])
                ->having('job_posts_count', '>', 0)
                ->orderBy('job_posts_count', 'desc')
                ->limit(5)  // ← Make sure this is before get()
                ->get()
                ->map(fn($loc) => [
                    'id' => $loc->id,
                    'name' => $loc->district ?? $loc->city ?? $loc->country,
                    'district' => $loc->district,
                    'city' => $loc->city,
                    'slug' => $loc->slug,
                    'jobs_count' => $loc->job_posts_count,
                ]);
            
            // Build query for jobs in this location
            $query = JobPost::with([
                'company', 'jobCategory', 'jobLocation', 'jobType', 
                'experienceLevel', 'salaryRange'
            ])
            ->where('is_active', true)
            ->where('deadline', '>=', now())
            ->where(function($q) use ($location) {
                $q->where('job_location_id', $location->id)
                ->orWhere('duty_station', 'LIKE', '%' . $location->district . '%');
                
                if ($location->city) {
                    $q->orWhere('duty_station', 'LIKE', '%' . $location->city . '%');
                }
            });
            
            // Apply sorting
            $sort = $request->get('sort', 'newest');
            switch ($sort) {
                case 'oldest':
                    $query->orderBy('published_at', 'asc');
                    break;
                case 'salary_high':
                    $query->orderBy('salary_amount', 'desc');
                    break;
                case 'salary_low':
                    $query->orderBy('salary_amount', 'asc');
                    break;
                default:
                    $query->orderBy('published_at', 'desc');
            }
            
            $page = $request->get('page', 1);
            $jobs = $query->paginate(18, ['*'], 'page', $page);
            
            $formattedJobs = $jobs->getCollection()->map(function($job) {
                return $this->formatJobData($job);
            });
            
            // Get location display name
            $locationName = $location->district ?? $location->city ?? $location->country;
            
            return response()->json([
                'location' => [
                    'id' => $location->id,
                    'name' => $locationName,
                    'district' => $location->district,
                    'city' => $location->city,
                    'country' => $location->country,
                    'country_name' => $location->country_name,
                    'slug' => $location->slug,
                    'description' => $location->description,
                    'meta_title' => $location->meta_title,
                    'meta_description' => $location->meta_description,
                    'jobs_count' => $jobs->total(),
                ],
                'similar_locations' => $similarLocations,
                'jobs' => [
                    'data' => $formattedJobs,
                    'pagination' => [
                        'current_page' => $jobs->currentPage(),
                        'last_page' => $jobs->lastPage(),
                        'per_page' => $jobs->perPage(),
                        'total' => $jobs->total(),
                    ],
                ],
            ]);
            
        } catch (\Exception $e) {
            \Log::error('Error fetching jobs by location: ' . $e->getMessage());
            return response()->json(['error' => 'Failed to fetch jobs'], 500);
        }
    }

    /**
     * Extract district name from slug pattern
     * Examples: 
     * - "nairobi-jobs-in-ke" -> "Nairobi"
     * - "kampala-jobs-uganda" -> "Kampala"
     * - "kisumu-jobs-in-ke" -> "Kisumu"
     */
    private function extractDistrictFromSlug($slug)
    {
        // Remove the suffix
        $patterns = [
            '/-jobs-in-[a-z]{2}$/i',  // Matches -jobs-in-ke, -jobs-in-ug
            '/-jobs-[a-z]{2}$/i',      // Matches -jobs-ug, -jobs-ke
            '/-jobs-in-[a-z]+$/i',     // Matches -jobs-in-uganda
            '/-jobs-[a-z]+$/i',        // Matches -jobs-uganda
        ];
        
        $district = $slug;
        foreach ($patterns as $pattern) {
            $district = preg_replace($pattern, '', $district);
            if ($district !== $slug) {
                break;
            }
        }
        
        // Convert hyphens to spaces and capitalize
        $district = str_replace('-', ' ', $district);
        $district = ucwords($district);
        
        return $district;
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
}