<?php

namespace App\Models\Job;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class ExperienceLevel extends Model
{
    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'slug',
        'description',
        'min_years',
        'max_years',
        'meta_title',
        'meta_description',
        'is_active',
        'sort_order',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'min_years' => 'integer',
        'max_years' => 'integer',
        'is_active' => 'boolean',
        'sort_order' => 'integer',
    ];

    /**
     * Bootstrap the model and its traits.
     */
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($experienceLevel) {
            if (empty($experienceLevel->slug)) {
                $experienceLevel->slug = Str::slug($experienceLevel->name);
            }
        });

        static::updating(function ($experienceLevel) {
            if ($experienceLevel->isDirty('name') && !$experienceLevel->isDirty('slug')) {
                $experienceLevel->slug = Str::slug($experienceLevel->name);
            }
        });
    }

    /**
     * Get the display name with years range.
     */
    public function getDisplayNameAttribute(): string
    {
        if ($this->min_years && $this->max_years) {
            return "{$this->name} ({$this->min_years}-{$this->max_years} years)";
        } elseif ($this->min_years) {
            return "{$this->name} ({$this->min_years}+ years)";
        } elseif ($this->max_years) {
            return "{$this->name} (Up to {$this->max_years} years)";
        }
        
        return $this->name;
    }

    /**
     * Get the years range as string.
     */
    public function getYearsRangeAttribute(): ?string
    {
        if ($this->min_years && $this->max_years) {
            return "{$this->min_years} - {$this->max_years} years";
        } elseif ($this->min_years) {
            return "{$this->min_years}+ years";
        } elseif ($this->max_years) {
            return "0 - {$this->max_years} years";
        }
        
        return null;
    }

    /**
     * Check if this experience level is entry level.
     */
    public function getIsEntryLevelAttribute(): bool
    {
        return $this->min_years === null || $this->min_years <= 1;
    }

    /**
     * Check if this experience level is senior level.
     */
    public function getIsSeniorLevelAttribute(): bool
    {
        return $this->min_years !== null && $this->min_years >= 5;
    }

    /**
     * Get SEO attributes for the experience level.
     */
    public function getSeoAttributes(): array
    {
        return [
            'title' => $this->meta_title ?? "{$this->name} Jobs",
            'description' => $this->meta_description ?? "Find {$this->name} positions. Browse job opportunities requiring {$this->years_range} of experience.",
            'keywords' => "{$this->name} jobs, {$this->years_range} experience jobs, career opportunities",
        ];
    }

    /**
     * Get the URL for this experience level.
     */
    public function getUrlAttribute(): string
    {
        return url("/experience/{$this->slug}");
    }

    /**
     * Scope a query to only include active experience levels.
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope a query to order by sort_order.
     */
    public function scopeOrdered($query)
    {
        return $query->orderBy('sort_order')->orderBy('name');
    }

    /**
     * Scope a query to only include entry level positions.
     */
    public function scopeEntryLevel($query)
    {
        return $query->where(function($q) {
            $q->whereNull('min_years')
              ->orWhere('min_years', '<=', 1);
        });
    }

    /**
     * Scope a query to only include mid level positions.
     */
    public function scopeMidLevel($query)
    {
        return $query->where('min_years', '>=', 2)
                     ->where('min_years', '<=', 4);
    }

    /**
     * Scope a query to only include senior level positions.
     */
    public function scopeSeniorLevel($query)
    {
        return $query->where('min_years', '>=', 5);
    }

    /**
     * Scope a query to filter by minimum years.
     */
    public function scopeMinYears($query, $years)
    {
        return $query->where('min_years', '<=', $years)
                     ->where(function($q) use ($years) {
                         $q->whereNull('max_years')
                           ->orWhere('max_years', '>=', $years);
                     });
    }

    /**
     * Scope a query to filter by maximum years.
     */
    public function scopeMaxYears($query, $years)
    {
        return $query->where('max_years', '<=', $years);
    }

    /**
     * Get all jobs for this experience level.
     */
    public function jobs()
    {
        return $this->hasMany(JobPost::class, 'experience_level_id');
    }

    /**
     * Get active jobs count for this experience level.
     */
    public function getActiveJobsCountAttribute(): int
    {
        return $this->jobs()->where('is_active', true)->count();
    }

    /**
     * Get the minimum years value with a default.
     */
    public function getMinYearsValueAttribute(): int
    {
        return $this->min_years ?? 0;
    }

    /**
     * Get the maximum years value with a default.
     */
    public function getMaxYearsValueAttribute(): int
    {
        return $this->max_years ?? 99;
    }

    /**
     * Determine if this experience level matches given years.
     */
    public function matchesYears(int $years): bool
    {
        $minYears = $this->min_years ?? 0;
        $maxYears = $this->max_years ?? 99;
        
        return $years >= $minYears && $years <= $maxYears;
    }

    /**
     * Get the experience level as an array for API responses.
     */
    public function toApiArray(): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'slug' => $this->slug,
            'description' => $this->description,
            'years_range' => $this->years_range,
            'display_name' => $this->display_name,
            'min_years' => $this->min_years,
            'max_years' => $this->max_years,
            'jobs_count' => $this->whenLoaded('jobs', function() {
                return $this->jobs->count();
            }, 0),
            'url' => $this->url,
        ];
    }
}