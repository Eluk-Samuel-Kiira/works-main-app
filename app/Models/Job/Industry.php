<?php

namespace App\Models\Job;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class Industry extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'slug',
        'description',
        'meta_title',
        'meta_description',
        'icon',
        'is_active',
        'sort_order'
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($industry) {
            if (empty($industry->slug)) {
                $industry->slug = Str::slug($industry->name);
            }
            if (empty($industry->meta_title)) {
                $industry->meta_title = "{$industry->name} Industry Jobs in Uganda - Career Opportunities";
            }
            if (empty($industry->meta_description)) {
                $industry->meta_description = "Find {$industry->name} industry jobs in Uganda. Browse career opportunities in {$industry->name} sector and apply for positions.";
            }
        });

        static::updating(function ($industry) {
            if ($industry->isDirty('name') && empty($industry->slug)) {
                $industry->slug = Str::slug($industry->name);
            }
        });
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopePopular($query)
    {
        return $query->where('sort_order', '<=', 15);
    }

    public function getSeoAttributes()
    {
        return [
            'title' => $this->meta_title ?? "{$this->name} Industry Jobs in Uganda",
            'description' => $this->meta_description,
            'keywords' => "{$this->name} industry Uganda, {$this->name} sector jobs, {$this->name} careers Uganda"
        ];
    }

    public function getUrlAttribute()
    {
        return url("/industry/{$this->slug}");
    }

    // Relationship with jobs (if you have Job model)
    public function jobs()
    {
        return $this->hasMany(Job::class);
    }

    // Get all active industries
    // $industries = Industry::active()->orderBy('sort_order')->get();

    // // Get popular industries
    // $popularIndustries = Industry::active()->popular()->get();

    // // Get jobs by industry
    // $bankingJobs = Job::where('industry_id', $bankingIndustry->id)->get();

    // // Get URL for industry page
    // $industryUrl = $industry->url; // /industry/banking
}
