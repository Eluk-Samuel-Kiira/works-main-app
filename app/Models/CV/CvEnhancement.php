<?php
// MAIN APP: app/Models/CV/CvEnhancement.php

namespace App\Models\CV;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\Auth\User;

class CvEnhancement extends Model
{
    use HasFactory;

    protected $table = 'cv_enhancements';

    protected $fillable = [
        'user_id', 'original_filename', 'original_file_path', 'extracted_text',
        'type', 'status', 'review_feedback', 'ats_score', 'keyword_gaps',
        'improvement_areas', 'rewritten_cv_text', 'rewritten_cv_path',
        'email_sent', 'email_sent_at', 'ai_model', 'tokens_used',
        'processing_ms', 'error_message'
    ];

    protected $casts = [
        'review_feedback' => 'array',
        'keyword_gaps' => 'array',
        'improvement_areas' => 'array',
        'email_sent' => 'boolean',
        'email_sent_at' => 'datetime',
        'ats_score' => 'decimal:2',
        'tokens_used' => 'integer',
        'processing_ms' => 'integer'
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}