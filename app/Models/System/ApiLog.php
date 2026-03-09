<?php

namespace App\Models\System;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use App\Models\Auth\User;
use App\Models\Job\JobPost;
use App\Models\Job\Company;

class ApiLog extends Model
{
    use HasFactory;

    protected $table = 'api_logs';

    protected $fillable = [
        'api_key_id',
        'service',
        'endpoint',
        'method',
        'request_data',
        'response_data',
        'response_code',
        'duration_ms',
        'ip_address',
        'user_agent',
        'is_success',
        'error_message',
        'error_details',
        'request_id',
        'metadata',
        'user_id',
        'job_post_id',
        'company_id',
    ];

    protected $casts = [
        'request_data' => 'array',
        'response_data' => 'array',
        'error_details' => 'array',
        'metadata' => 'array',
        'is_success' => 'boolean',
        'duration_ms' => 'float',
    ];

    /**
     * Get the API key used for this request
     */
    public function apiKey()
    {
        return $this->belongsTo(ApiKey::class);
    }

    /**
     * Get the user who made this request
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the job post related to this request
     */
    public function jobPost()
    {
        return $this->belongsTo(JobPost::class);
    }

    /**
     * Get the company related to this request
     */
    public function company()
    {
        return $this->belongsTo(Company::class);
    }

    /**
     * Scope successful requests
     */
    public function scopeSuccessful($query)
    {
        return $query->where('is_success', true);
    }

    /**
     * Scope failed requests
     */
    public function scopeFailed($query)
    {
        return $query->where('is_success', false);
    }

    /**
     * Scope by service
     */
    public function scopeService($query, $service)
    {
        return $query->where('service', $service);
    }

    /**
     * Scope by date range
     */
    public function scopeDateRange($query, $startDate, $endDate)
    {
        return $query->whereBetween('created_at', [$startDate, $endDate]);
    }

    /**
     * Get formatted duration
     */
    public function getFormattedDurationAttribute(): string
    {
        if ($this->duration_ms < 1000) {
            return round($this->duration_ms) . 'ms';
        }
        
        return round($this->duration_ms / 1000, 2) . 's';
    }

    /**
     * Get status badge class
     */
    public function getStatusBadgeAttribute(): string
    {
        if ($this->is_success) {
            return 'bg-green-100 text-green-800';
        }
        
        return 'bg-red-100 text-red-800';
    }
}