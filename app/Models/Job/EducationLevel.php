<?php

namespace App\Models\Job;

use App\Models\Auth\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class EducationLevel extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
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

        static::creating(function ($educationLevel) {
            if (empty($educationLevel->slug)) {
                $educationLevel->slug = Str::slug($educationLevel->name);
            }
            if (empty($educationLevel->meta_title)) {
                $educationLevel->meta_title = "{$educationLevel->name} Jobs in Uganda - Education Requirements";
            }
            if (empty($educationLevel->created_by) && auth()->check()) {
                $educationLevel->created_by = auth()->id();
            }
        });
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
            'keywords' => "{$this->name} jobs uganda, {$this->name} required, {$this->name} qualifications"
        ];
    }

    public function getUrlAttribute()
    {
        return url("/education/{$this->slug}");
    }

    public function jobPosts()
    {
        return $this->hasMany(JobPost::class, 'education_level_id');
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
