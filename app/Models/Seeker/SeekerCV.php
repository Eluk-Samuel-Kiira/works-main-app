<?php
// app/Models/Seeker/SeekerCV.php

namespace App\Models\Seeker;

use App\Models\Auth\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;

class SeekerCV extends Model
{
    use HasFactory;

    protected $table = 'seeker_cvs';

    protected $fillable = [
        'user_id',
        'first_name',
        'last_name',
        'email',
        'phone',
        'address',
        'city',
        'country',
        'postal_code',
        'date_of_birth',
        'nationality',
        'professional_summary',
        'professional_title',
        'years_of_experience',
        'linkedin_url',
        'github_url',
        'portfolio_url',
        'skills',
        'languages',
        'certifications',
        'education',
        'work_experience',
        'projects',
        'cv_file_path',
        'cv_original_name',
        'job_preferences',
        'is_public',
        'is_active'
    ];

    protected $casts = [
        'skills' => 'array',
        'languages' => 'array',
        'certifications' => 'array',
        'education' => 'array',
        'work_experience' => 'array',
        'projects' => 'array',
        'job_preferences' => 'array',
        'date_of_birth' => 'date',
        'is_public' => 'boolean',
        'is_active' => 'boolean',
        'years_of_experience' => 'integer'
    ];

    protected $appends = ['full_name', 'cv_url'];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function getFullNameAttribute(): string
    {
        return "{$this->first_name} {$this->last_name}";
    }

    public function getCvUrlAttribute(): ?string
    {
        if (!$this->cv_file_path) {
            return null;
        }
        return Storage::url($this->cv_file_path);
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopePublic($query)
    {
        return $query->where('is_public', true);
    }
}