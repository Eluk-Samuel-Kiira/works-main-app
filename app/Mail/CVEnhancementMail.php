<?php
// MAIN APP: app/Mail/CVEnhancementMail.php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class CVEnhancementMail extends Mailable
{
    use Queueable, SerializesModels;

    public array $user;
    public string $type;
    public ?string $content;
    public ?float $atsScore;
    public ?float $matchScore;
    public array $strengths;
    public array $criticalIssues;
    public array $keywordGaps;
    public array $recommendedActions;
    public array $matchedSkills;
    public array $missingSkills;
    public array $improvementAreas;

    public function __construct(
        array $user,
        string $type,
        ?string $content = null,
        ?float $atsScore = null,
        ?float $matchScore = null,
        array $strengths = [],
        array $criticalIssues = [],
        array $keywordGaps = [],
        array $recommendedActions = [],
        array $matchedSkills = [],
        array $missingSkills = [],
        array $improvementAreas = []
    ) {
        $this->user = $user;
        $this->type = $type;
        $this->content = $content;
        $this->atsScore = $atsScore;
        $this->matchScore = $matchScore;
        $this->strengths = $strengths;
        $this->criticalIssues = $criticalIssues;
        $this->keywordGaps = $keywordGaps;
        $this->recommendedActions = $recommendedActions;
        $this->matchedSkills = $matchedSkills;
        $this->missingSkills = $missingSkills;
        $this->improvementAreas = $improvementAreas;
    }

    public function build()
    {
        $subject = match($this->type) {
            'review' => 'Your Professional CV Review Results - Stardena Works',
            'rewrite' => 'Your AI-Rewritten CV is Ready - Stardena Works',
            'cover_letter' => 'Your Custom Cover Letter - Stardena Works',
            default => 'Your CV Enhancement Results - Stardena Works'
        };

        return $this->subject($subject)
                    ->view('emails.cv-enhancement')
                    ->with([
                        'user' => $this->user,
                        'type' => $this->type,
                        'content' => $this->content,
                        'atsScore' => $this->atsScore,
                        'matchScore' => $this->matchScore,
                        'strengths' => $this->strengths,
                        'criticalIssues' => $this->criticalIssues,
                        'keywordGaps' => $this->keywordGaps,
                        'recommendedActions' => $this->recommendedActions,
                        'matchedSkills' => $this->matchedSkills,
                        'missingSkills' => $this->missingSkills,
                        'improvementAreas' => $this->improvementAreas,
                        'subject' => $subject,
                    ]);
    }
}