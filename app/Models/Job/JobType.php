<?php

namespace App\Models\Job;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class JobType extends Model
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

        static::creating(function ($jobType) {
            if (empty($jobType->slug)) {
                $jobType->slug = Str::slug($jobType->name);
            }
            if (empty($jobType->meta_title)) {
                $jobType->meta_title = "{$jobType->name} in Uganda - Employment Opportunities";
            }
            if (empty($jobType->meta_description)) {
                $jobType->meta_description = "Find {$jobType->name} in Uganda. Browse employment opportunities and career positions across various industries.";
            }
        });
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopePopular($query)
    {
        return $query->where('sort_order', '<=', 5);
    }

    public function getSeoAttributes()
    {
        return [
            'title' => $this->meta_title,
            'description' => $this->meta_description,
            'keywords' => "{$this->name} uganda, {$this->name} jobs, {$this->name} opportunities"
        ];
    }

    public function getUrlAttribute()
    {
        return url("/{$this->slug}");
    }
}
