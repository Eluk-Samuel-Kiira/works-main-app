<?php

namespace App\Models\Job;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;
use Carbon\Carbon;
use App\Traits\LogsJobActivities;

class JobPost extends Model
{
    use HasFactory, SoftDeletes, LogsJobActivities;
    protected static bool $skipSeoOnUpdate = false;

    protected $fillable = [
        // Core Job Information
        'job_title',
        'slug',
        'job_description',
        'responsibilities',
        'skills',
        'qualifications',
        'deadline',
        'application_procedure',
        'email',
        'telephone',
        
        // Relationships
        'company_id',
        'job_category_id',
        'industry_id',
        'job_location_id',
        'job_type_id',
        'experience_level_id',
        'education_level_id',
        'salary_range_id',
        'poster_id',
        
        // Location Details
        'duty_station',
        'street_address',
        
        // Salary Information
        'salary_amount',
        'currency',
        'payment_period',
        'base_salary',
        
        // Job Specifications
        'location_type',
        'applicant_location_requirements',
        'work_hours',
        'employment_type',
        
        // SEO & AI Optimization
        'meta_title',
        'meta_description',
        'keywords',
        'canonical_url',
        'structured_data',
        'focus_keyphrase',
        'seo_synonyms',
        
        // Advanced SEO Features
        'is_pinged',
        'is_whatsapp_contact',
        'is_telephone_call',
        'last_pinged_at',
        'is_indexed',
        'last_indexed_at',
        'is_featured',
        'is_urgent',
        'is_active',
        'is_verified',
        'is_simple_job',
        'is_quick_gig',
        'view_count',
        'application_count',
        'click_count',
        'indexing_response',
        'indexing_status',
        'submitted_to_indexing',
        'indexing_submitted_at',
        
        // Application Requirements
        'is_cover_letter_required',
        'is_resume_required',
        'is_application_required',
        'is_academic_documents_required',
        
        // AI Optimization
        'ai_optimized_title',
        'ai_optimized_description',
        'ai_content_analysis',
        'seo_score',
        'content_quality_score',
        'search_terms',
        'competitor_analysis',
        'ai_recommendations',
        
        // Performance Tracking
        'search_impressions',
        'search_clicks',
        'click_through_rate',
        'google_rank',
        'ranking_keywords',
        
        // Social Signals
        'social_shares',
        'backlinks_count',
        'social_metrics',
        
        // Timestamps
        'published_at',
        'featured_until'
    ];

    protected $casts = [
        'deadline' => 'date',
        'view_count' => 'integer',
        'salary_amount' => 'decimal:2',
        'base_salary' => 'decimal:2',
        'is_pinged' => 'boolean',
        'is_indexed' => 'boolean',
        'is_simple_job' => 'boolean',
        'is_quick_gig' => 'boolean',
        'is_featured' => 'boolean',
        'is_urgent' => 'boolean',
        'is_active' => 'boolean',
        'is_verified' => 'boolean',
        'is_cover_letter_required' => 'boolean',
        'is_resume_required' => 'boolean',
        'is_academic_documents_required' => 'boolean',
        // 'structured_data' => 'array',
        // 'search_terms' => 'array',
        // 'competitor_analysis' => 'array',
        // 'ranking_keywords' => 'array',
        // 'social_metrics' => 'array',
        'seo_score' => 'decimal:2',
        'content_quality_score' => 'decimal:2',
        'click_through_rate' => 'decimal:2',
        'published_at' => 'datetime',
        'featured_until' => 'datetime',
        'last_pinged_at' => 'datetime',
        'last_indexed_at' => 'datetime'
    ];

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($job) {
            // if (empty($job->published_at)) $job->published_at = now();
            $job->currency        = $job->currency        ?? 'UGX';
            $job->location_type   = $job->location_type   ?? 'on-site';
            $job->employment_type = $job->employment_type ?? 'full-time';
            $job->is_resume_required = $job->is_resume_required ?? true;
            $job->view_count = $job->view_count ?? 0;
            $job->application_count = $job->application_count ?? 0;
            $job->click_count = $job->click_count ?? 0;

        });


        }

    // Relationships
    public function company()
    {
        return $this->belongsTo(\App\Models\Job\Company::class);
    }


    public function getRouteKeyName()
    {
        return 'slug';
    }

    public function jobCategory()
    {
        return $this->belongsTo(\App\Models\Job\JobCategory::class);
    }

    public function industry()
    {
        return $this->belongsTo(\App\Models\Job\Industry::class);
    }

    public function jobLocation()
    {
        return $this->belongsTo(\App\Models\Job\JobLocation::class, 'job_location_id');
    }

    public function jobType()
    {
        return $this->belongsTo(\App\Models\Job\JobType::class);
    }

    public function experienceLevel()
    {
        return $this->belongsTo(\App\Models\Job\ExperienceLevel::class);
    }

    public function educationLevel()
    {
        return $this->belongsTo(\App\Models\Job\EducationLevel::class);
    }

    public function salaryRange()
    {
        return $this->belongsTo(\App\Models\Job\SalaryRange::class);
    }

    public function poster()
    {
        return $this->belongsTo(\App\Models\Auth\User::class, 'poster_id');
    }

    public function applications()
    {
        return $this->hasMany(\App\Models\JobApplication::class);
    }




    private function extractKeywords()
    {
        $text = $this->job_title . ' ' . 
                ($this->job_description ?? '') . ' ' . 
                ($this->company->name ?? '') . ' ' . 
                ($this->location->name ?? '');
        
        $commonWords = ['the', 'and', 'for', 'with', 'this', 'that', 'are', 'from', 'have', 'will'];
        
        $words = str_word_count(strtolower($text), 1);
        $wordCount = array_count_values($words);
        
        arsort($wordCount);
        
        $keywords = array_slice(array_keys($wordCount), 0, 20);
        return array_diff($keywords, $commonWords);
    }

    // Scopes
    public function scopeHighSeoScore($query, $minScore = 80)
    {
        return $query->where('seo_score', '>=', $minScore);
    }

    public function scopeAIOptimized($query)
    {
        return $query->where('seo_score', '>=', 75)
                    ->where('content_quality_score', '>=', 70)
                    ->whereNotNull('structured_data');
    }

    public function scopeTrending($query)
    {
        return $query->where('view_count', '>', 100)
                    ->where('created_at', '>=', now()->subDays(7))
                    ->orderBy('view_count', 'desc');
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true)
                    ->where('deadline', '>=', now());
    }

    public function scopeFeatured($query)
    {
        return $query->where('is_featured', true)
                    ->where(function($q) {
                        $q->whereNull('featured_until')
                          ->orWhere('featured_until', '>=', now());
                    });
    }

    public function scopeUrgent($query)
    {
        return $query->where('is_urgent', true);
    }

    public function scopeVerified($query)
    {
        return $query->where('is_verified', true);
    }

    public function scopeByLocation($query, $locationId)
    {
        return $query->where('job_location_id', $locationId);
    }

    public function scopeByCompany($query, $companyId)
    {
        return $query->where('company_id', $companyId);
    }

    public function scopeByCategory($query, $categoryId)
    {
        return $query->where('job_category_id', $categoryId);
    }

    public function scopeByIndustry($query, $industryId)
    {
        return $query->where('industry_id', $industryId);
    }

    public function scopeByJobType($query, $jobTypeId)
    {
        return $query->where('job_type_id', $jobTypeId);
    }

    public function scopeByExperienceLevel($query, $levelId)
    {
        return $query->where('experience_level_id', $levelId);
    }

    public function scopeByEducationLevel($query, $levelId)
    {
        return $query->where('education_level_id', $levelId);
    }

    public function scopeSalaryMin($query, $amount)
    {
        return $query->where('salary_amount', '>=', $amount);
    }

    public function scopeSalaryMax($query, $amount)
    {
        return $query->where('salary_amount', '<=', $amount);
    }

    public function scopeLocationType($query, $type)
    {
        return $query->where('location_type', $type);
    }

    public function scopeEmploymentType($query, $type)
    {
        return $query->where('employment_type', $type);
    }

    public function scopePostedAfter($query, $date)
    {
        return $query->where('published_at', '>=', $date);
    }

    public function scopeDeadlineBefore($query, $date)
    {
        return $query->where('deadline', '<=', $date);
    }

    // Accessors
    public function getIsExpiredAttribute()
    {
        return $this->deadline && $this->deadline->isPast();
    }

    public function getIsFeaturedActiveAttribute()
    {
        return $this->is_featured && (!$this->featured_until || $this->featured_until->isFuture());
    }

    public function getSalaryFormattedAttribute()
    {
        if (!$this->salary_amount) return 'Negotiable';
        return 'UGX ' . number_format($this->salary_amount) . ($this->payment_period ? '/' . $this->payment_period : '');
    }

    public function getUrlAttribute()
    {
        return url("/jobs/{$this->slug}");
    }

    public function getCanonicalUrlAttribute()
    {
        return $this->getOriginal('canonical_url') ?? $this->url;
    }

    public function getDaysRemainingAttribute()
    {
        if (!$this->deadline) return null;
        return now()->diffInDays($this->deadline, false);
    }

    public function getApplicationStatusAttribute()
    {
        if ($this->isExpired) return 'expired';
        if ($this->is_featured_active) return 'featured';
        if ($this->is_urgent) return 'urgent';
        return 'active';
    }


    public function incrementApplicationCount()
    {
        $this->increment('application_count');
    }

    public function incrementClickCount()
    {
        $this->increment('click_count');
        $this->calculateClickThroughRate();
    }

    public function calculateClickThroughRate()
    {
        if ($this->search_impressions > 0) {
            $this->click_through_rate = ($this->click_count / $this->search_impressions) * 100;
            $this->save();
        }
    }


}