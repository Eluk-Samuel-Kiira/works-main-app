<?php

namespace App\Models\Job;

use App\Models\Auth\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class JobLocation extends Model
{
    use HasFactory;

    protected $fillable = [
        'country',
        'district',
        'slug',
        'description',
        'meta_title',
        'meta_description',
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

        static::creating(function ($location) {
            if (empty($location->slug)) {
                $location->slug = Str::slug($location->name);
            }
            if (empty($location->meta_title)) {
                $location->meta_title = "Jobs in {$location->name} Uganda - Latest Career Opportunities";
            }
            if (empty($location->meta_description)) {
                $location->meta_description = "Find latest jobs in {$location->name} Uganda. Browse career opportunities, vacancies, and employment in {$location->name}.";
            }
            if (empty($location->created_by) && auth()->check()) {
                $location->created_by = auth()->id();
            }
        });
    }

    public function parent()
    {
        return $this->belongsTo(Location::class, 'parent_id');
    }

    public function children()
    {
        return $this->hasMany(Location::class, 'parent_id');
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function getSeoAttributes()
    {
        return [
            'title' => $this->meta_title,
            'description' => $this->meta_description,
            'keywords' => "jobs in {$this->name}, {$this->name} uganda jobs, employment {$this->name}, careers {$this->name}"
        ];
    }

    public function getUrlAttribute()
    {
        return url("/jobs-in-{$this->slug}");
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
