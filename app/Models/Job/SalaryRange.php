<?php

namespace App\Models\Job;

use App\Models\Auth\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class SalaryRange extends Model
{

    protected $fillable = [
        'name',
        'slug',
        'min_salary',
        'max_salary',
        'currency',
        'meta_title',
        'meta_description',
        'is_active',
        'sort_order',
        'created_by'
    ];

    protected $casts = [
        'min_salary' => 'decimal:2',
        'max_salary' => 'decimal:2',
        'is_active' => 'boolean',
    ];

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($salaryRange) {
            if (empty($salaryRange->slug)) {
                $salaryRange->slug = Str::slug($salaryRange->name);
            }
            if (empty($salaryRange->created_by) && auth()->check()) {
                $salaryRange->created_by = auth()->id();
            }
        });
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function getDisplayNameAttribute()
    {
        if ($this->min_salary && $this->max_salary) {
            return "{$this->name} {$this->currency}";
        }
        return $this->name;
    }

    public function getSeoAttributes()
    {
        return [
            'title' => $this->meta_title,
            'description' => $this->meta_description,
            'keywords' => "jobs paying {$this->name}, {$this->name} salary jobs, {$this->currency} jobs"
        ];
    }

    public function getUrlAttribute()
    {
        return url("/salary/{$this->slug}");
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
