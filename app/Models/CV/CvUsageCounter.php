<?php
// app/Models/CV/CvUsageCounter.php

namespace App\Models\CV;

use Illuminate\Database\Eloquent\Model;

class CvUsageCounter extends Model
{
    protected $fillable = [
        'user_id',
        'cv_reviews_count',
        'cv_rewrites_count',
        'cover_letters_count',
        'period_start',
    ];

    protected $casts = [
        'period_start' => 'datetime',
    ];

    /**
     * Reset usage counters if period has changed (monthly reset)
     */
    public static function resetIfNewPeriod(int $userId): void
    {
        $counter = self::firstOrCreate(
            ['user_id' => $userId],
            [
                'cv_reviews_count' => 0,
                'cv_rewrites_count' => 0,
                'cover_letters_count' => 0,
                'period_start' => now()->startOfMonth(),
            ]
        );

        // If period_start is not this month, reset counters
        if ($counter->period_start->startOfMonth() < now()->startOfMonth()) {
            $counter->update([
                'cv_reviews_count' => 0,
                'cv_rewrites_count' => 0,
                'cover_letters_count' => 0,
                'period_start' => now()->startOfMonth(),
            ]);
        }
    }

    /**
     * Increment usage for a specific type
     */
    public static function incrementUsage(int $userId, string $type): void
    {
        // Reset if new period
        self::resetIfNewPeriod($userId);
        
        $counter = self::firstOrCreate(
            ['user_id' => $userId],
            [
                'cv_reviews_count' => 0,
                'cv_rewrites_count' => 0,
                'cover_letters_count' => 0,
                'period_start' => now()->startOfMonth(),
            ]
        );

        $field = match($type) {
            'review' => 'cv_reviews_count',
            'rewrite' => 'cv_rewrites_count',
            'cover_letter' => 'cover_letters_count',
            default => 'cv_reviews_count',
        };

        $counter->increment($field);
    }
}