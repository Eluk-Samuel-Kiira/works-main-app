<?php

namespace App\Models\Job;

use App\Models\Auth\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;
use App\Models\Job\JobPost;

class JobCategory extends Model
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
        'sort_order',
        'created_by'
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($category) {
            if (empty($category->slug)) {
                $category->slug = Str::slug($category->name);
            }
            if (empty($category->meta_title)) {
                $category->meta_title = "{$category->name} Jobs in Uganda - Latest Opportunities";
            }
            if (empty($category->meta_description)) {
                $category->meta_description = "Browse latest {$category->name} job vacancies in Uganda. Find your dream career in {$category->name} sector.";
            }
            if (empty($category->created_by) && auth()->check()) {
                $category->created_by = auth()->id();
            }
        });

        static::updating(function ($category) {
            if ($category->isDirty('name') && empty($category->slug)) {
                $category->slug = Str::slug($category->name);
            }
        });
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeFeatured($query)
    {
        return $query->where('sort_order', '<=', 10);
    }

    public function getSeoAttributes()
    {
        return [
            'title' => $this->meta_title ?? "{$this->name} Jobs in Uganda - Career Opportunities",
            'description' => $this->meta_description,
            'keywords' => "{$this->name} jobs in Uganda, Uganda careers, employment Uganda, vacancies Uganda"
        ];
    }

    public function getUrlAttribute()
    {
        return url("/jobs/{$this->slug}");
    }

    public function jobPosts()
    {
        return $this->hasMany(JobPost::class);
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
