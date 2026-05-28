<?php
// MAIN APP: app/Models/CV/CoverLetter.php

namespace App\Models\CV;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\Auth\User;

class CoverLetter extends Model
{
    use HasFactory;

    protected $table = 'cover_letters';

    protected $fillable = [
        'user_id', 'cv_enhancement_id', 'job_title', 'job_description',
        'responsibilities', 'required_skills', 'company_name', 'hiring_manager',
        'match_score', 'matched_skills', 'missing_skills', 'status',
        'generated_letter', 'letter_file_path', 'email_sent', 'email_sent_at',
        'ai_model', 'error_message'
    ];

    protected $casts = [
        'matched_skills' => 'array',
        'missing_skills' => 'array',
        'match_score' => 'decimal:2',
        'email_sent' => 'boolean',
        'email_sent_at' => 'datetime'
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function cvEnhancement()
    {
        return $this->belongsTo(CvEnhancement::class);
    }
}