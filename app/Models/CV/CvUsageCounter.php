<?php
// MAIN APP: app/Models/CV/CvUsageCounter.php

namespace App\Models\CV;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\Auth\User;

class CvUsageCounter extends Model
{
    use HasFactory;

    protected $table = 'cv_usage_counters';

    protected $fillable = [
        'user_id', 'cv_reviews_count', 'cv_rewrites_count',
        'cover_letters_count', 'period_start'
    ];

    protected $casts = [
        'period_start' => 'datetime',
        'cv_reviews_count' => 'integer',
        'cv_rewrites_count' => 'integer',
        'cover_letters_count' => 'integer'
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Increment a specific counter field
     * Using a custom method name to avoid conflict with Laravel's increment
     */
    public function incrementCounter(string $field): void
    {
        if (in_array($field, ['cv_reviews_count', 'cv_rewrites_count', 'cover_letters_count'])) {
            $this->increment($field);
        }
    }
}