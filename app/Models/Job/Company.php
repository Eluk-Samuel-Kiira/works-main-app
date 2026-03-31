<?php

namespace App\Models\Job;

use App\Models\Auth\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Storage;

class Company extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'slug',
        'logo',
        'description',
        'website',
        'contact_name',
        'contact_email',
        'contact_phone',
        'address1',
        'company_size',
        'industry_id',
        'location_id',
        'is_active',
        'is_verified',
        'created_by'
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'is_verified' => 'boolean',
    ];

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($company) {
            if (empty($company->slug)) {
                $company->slug = Str::slug($company->name);
            }
            if (empty($company->created_by) && auth()->check()) {
                $company->created_by = auth()->id();
            }
        });

        static::updating(function ($company) {
            if ($company->isDirty('name') && empty($company->slug)) {
                $company->slug = Str::slug($company->name);
            }
        });
    }

    public function industry()
    {
        return $this->belongsTo(Industry::class);
    }

    public function location()
    {
        return $this->belongsTo(JobLocation::class, 'location_id');
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function jobs()
    {
        return $this->hasMany(Job::class);
    }

    public function getLogoUrlAttribute()
    {
        if (!$this->logo) {
            return asset('images/default-company-logo.png');
        }

        if (filter_var($this->logo, FILTER_VALIDATE_URL)) {
            return $this->logo;
        }

        return Storage::url($this->logo);
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeVerified($query)
    {
        return $query->where('is_verified', true);
    }

    public function getRouteKeyName()
    {
        return 'slug';
    }
}
