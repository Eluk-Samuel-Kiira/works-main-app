<?php
// MAIN APP: app/Services/CVEnhancementService.php

namespace App\Services;

use App\Models\CV\CvEnhancement;
use App\Models\CV\CoverLetter;
use App\Models\CV\CvUsageCounter;
use App\Models\Seeker\SeekerCV;
use App\Models\Auth\User;
use Illuminate\Support\Facades\{Http, Log, Storage, Mail};
use Illuminate\Http\UploadedFile;
use App\Mail\CVEnhancementMail;
use Smalot\PdfParser\Parser;
use PhpOffice\PhpWord\IOFactory;

class CVEnhancementService
{
    private string $cohereKey;
    
    // HR System Prompt - Professional CV writing standards
    private const HR_SYSTEM_PROMPT = <<<PROMPT
        You are a senior HR professional and certified CV writer...
    PROMPT;

    public function __construct()
    {
        $this->cohereKey = config('services.cohere.api_key', env('COHERE_API_KEY'));
    }

    /**
     * Review CV and return structured HR feedback
     */
    public function reviewCV(int $userId, ?UploadedFile $file = null, ?string $targetRole = null): array
    {
        // Get CV content
        $cvResult = $this->getCVContent($userId, $file);
        
        if (!$cvResult['content']) {
            return [
                'success' => false,
                'error' => $cvResult['error'],
                'enhancement' => null
            ];
        }

        $cvText = $cvResult['content'];
        $source = $cvResult['source'];

        $enhancement = CvEnhancement::create([
            'user_id'        => $userId,
            'type'           => 'review',
            'status'         => 'processing',
            'extracted_text' => substr($cvText, 0, 15000),
        ]);

        try {
            $start = microtime(true);
            $prompt = $this->buildReviewPrompt($cvText, $targetRole);
            $response = $this->callCohere($prompt, 3000);
            $feedback = $this->parseJsonResponse($response);

            $enhancement->update([
                'status'            => 'completed',
                'review_feedback'   => $feedback,
                'ats_score'         => $feedback['ats_score'] ?? null,
                'keyword_gaps'      => $feedback['keyword_gaps'] ?? [],
                'improvement_areas' => $feedback['improvement_areas'] ?? [],
                'strengths'         => $feedback['strengths'] ?? [],
                'recommended_actions'=> $feedback['recommended_actions'] ?? [],
                'ai_model'          => 'cohere-command-a-03-2025',
                'processing_ms'     => (int)((microtime(true) - $start) * 1000),
            ]);

            $this->incrementCounter($userId, 'cv_reviews_count');
            $this->sendReviewEmail($userId, $enhancement);

            return [
                'success' => true,
                'error' => null,
                'enhancement' => $enhancement->fresh()
            ];

        } catch (\Exception $e) {
            Log::error('[CVEnhancement] Review failed', ['user_id' => $userId, 'error' => $e->getMessage()]);
            $enhancement->update(['status' => 'failed', 'error_message' => $e->getMessage()]);
            
            return [
                'success' => false,
                'error' => 'AI processing failed: ' . $e->getMessage(),
                'enhancement' => $enhancement
            ];
        }
    }

    /**
     * Validate that the content looks like a CV
     */
    private function validateCVContent(string $content): bool
    {
        // Check for minimum length (CV should be at least 200 chars)
        if (strlen($content) < 200) {
            return false;
        }
        
        // Keywords that indicate this is a CV/resume
        $cvKeywords = [
            'experience', 'education', 'skills', 'employment', 
            'professional', 'work', 'certification', 'qualification',
            'profile', 'summary', 'background', 'achievement',
            'responsibilities', 'reference', 'graduate', 'degree',
            'bachelor', 'master', 'diploma', 'certificate'
        ];
        
        $lowerContent = strtolower($content);
        $keywordCount = 0;
        
        foreach ($cvKeywords as $keyword) {
            if (str_contains($lowerContent, $keyword)) {
                $keywordCount++;
            }
        }
        
        // Need at least 3 CV-related keywords
        return $keywordCount >= 3;
    }

    /**
     * Get CV content from either uploaded file or database
     * Returns array with [content, source, error]
     */
    public function getCVContent(int $userId, ?UploadedFile $uploadedFile = null): array
    {
        // Priority 1: Uploaded file
        if ($uploadedFile && $uploadedFile->isValid()) {
            $content = $this->extractTextFromFile($uploadedFile);
            if (!empty($content)) {
                // Validate that it looks like a CV
                $isValidCV = $this->validateCVContent($content);
                if ($isValidCV) {
                    return [
                        'content' => $content,
                        'source' => 'upload',
                        'error' => null
                    ];
                } else {
                    return [
                        'content' => null,
                        'source' => null,
                        'error' => 'The uploaded file does not appear to be a valid CV. Please ensure it contains professional experience, education, or skills information.'
                    ];
                }
            }
            return [
                'content' => null,
                'source' => null,
                'error' => 'Could not extract text from the uploaded file. Please ensure the file is not corrupted.'
            ];
        }

        // Priority 2: SeekerCV from database
        $seekerCv = SeekerCV::where('user_id', $userId)->first();
        
        if ($seekerCv) {
            // Check if they have structured data
            $structuredContent = $this->buildTextFromProfile($seekerCv);
            if (!empty($structuredContent) && strlen($structuredContent) > 100) {
                return [
                    'content' => $structuredContent,
                    'source' => 'database',
                    'error' => null
                ];
            }
            
            // Check if they have an uploaded CV file
            if ($seekerCv->cv_file_path) {
                $fullPath = Storage::disk('public')->path($seekerCv->cv_file_path);
                if (file_exists($fullPath)) {
                    $fakeFile = new \Illuminate\Http\UploadedFile($fullPath, basename($fullPath));
                    $content = $this->extractTextFromFile($fakeFile);
                    if (!empty($content)) {
                        $isValidCV = $this->validateCVContent($content);
                        if ($isValidCV) {
                            return [
                                'content' => $content,
                                'source' => 'database_file',
                                'error' => null
                            ];
                        }
                    }
                }
            }
        }

        // No valid CV found
        return [
            'content' => null,
            'source' => null,
            'error' => 'No CV found. Please upload your CV in the Update CV tab or complete your profile information first.'
        ];
    }


    /**
     * Extract text from uploaded file (PDF, DOC, DOCX)
     */
    private function extractTextFromFile(UploadedFile $file): string
    {
        $ext = strtolower($file->getClientOriginalExtension());
        $content = '';

        try {
            if ($ext === 'pdf') {
                $parser = new Parser();
                $pdf = $parser->parseFile($file->getRealPath());
                $content = $pdf->getText();
            } elseif (in_array($ext, ['doc', 'docx'])) {
                $phpWord = IOFactory::load($file->getRealPath());
                foreach ($phpWord->getSections() as $section) {
                    foreach ($section->getElements() as $element) {
                        if (method_exists($element, 'getText')) {
                            $content .= $element->getText() . "\n";
                        }
                    }
                }
            }
        } catch (\Exception $e) {
            Log::error('[CVEnhancement] Text extraction failed: ' . $e->getMessage());
            return '';
        }

        return trim($content);
    }

    /**
     * Build text from structured SeekerCV profile
     */
    private function buildTextFromProfile(SeekerCV $cv): string
    {
        $lines = [];

        // Personal Information
        $lines[] = strtoupper(trim($cv->first_name . ' ' . $cv->last_name));
        if ($cv->phone) $lines[] = $cv->phone;
        if ($cv->email) $lines[] = $cv->email;
        if ($cv->city || $cv->country) $lines[] = implode(', ', array_filter([$cv->city, $cv->country]));
        $lines[] = '';

        // Professional Summary
        if ($cv->professional_summary) {
            $lines[] = 'PROFILE SUMMARY';
            $lines[] = $cv->professional_summary;
            $lines[] = '';
        }

        // Skills
        if (!empty($cv->skills)) {
            $lines[] = 'SKILLS';
            foreach ($cv->skills as $s) {
                $lines[] = '- ' . $s;
            }
            $lines[] = '';
        }

        // Work Experience
        if (!empty($cv->work_experience)) {
            $lines[] = 'WORK EXPERIENCE';
            foreach ($cv->work_experience as $exp) {
                $jobTitle = $exp['job_title'] ?? '';
                $company = $exp['company'] ?? '';
                $startDate = $exp['start_date'] ?? '';
                $endDate = $exp['current'] ? 'Present' : ($exp['end_date'] ?? '');
                $location = $exp['location'] ?? '';
                
                $lines[] = "{$jobTitle} | {$company} | {$startDate} - {$endDate}";
                if ($location) {
                    $lines[] = "Location: {$location}";
                }
                if (!empty($exp['description'])) {
                    $lines[] = $exp['description'];
                }
                $lines[] = '';
            }
        }

        // Education
        if (!empty($cv->education)) {
            $lines[] = 'EDUCATION';
            foreach ($cv->education as $edu) {
                $degree = $edu['degree'] ?? '';
                $institution = $edu['institution'] ?? '';
                $year = $edu['end_date'] ?? $edu['start_date'] ?? '';
                $fieldOfStudy = $edu['field_of_study'] ?? '';
                
                $lines[] = "{$degree} | {$institution} | {$year}";
                if ($fieldOfStudy) {
                    $lines[] = "Field: {$fieldOfStudy}";
                }
            }
            $lines[] = '';
        }

        // Certifications
        if (!empty($cv->certifications)) {
            $lines[] = 'CERTIFICATIONS';
            foreach ($cv->certifications as $cert) {
                $name = $cert['name'] ?? '';
                $issuer = $cert['issuer'] ?? '';
                $lines[] = "{$name} | {$issuer}";
            }
            $lines[] = '';
        }

        // Languages
        if (!empty($cv->languages)) {
            $lines[] = 'LANGUAGES';
            foreach ($cv->languages as $lang) {
                $name = $lang['name'] ?? '';
                $proficiency = $lang['proficiency'] ?? '';
                $lines[] = "{$name} ({$proficiency})";
            }
        }

        return implode("\n", $lines);
    }

    /**
     * Rewrite CV to professional standards
     */
    public function rewriteCV(int $userId, ?UploadedFile $file = null, ?string $targetRole = null): array
    {
        // Get CV content
        $cvResult = $this->getCVContent($userId, $file);
        
        if (!$cvResult['content']) {
            return [
                'success' => false,
                'error' => $cvResult['error'],
                'enhancement' => null
            ];
        }

        $cvText = $cvResult['content'];

        $enhancement = CvEnhancement::create([
            'user_id'        => $userId,
            'type'           => 'rewrite',
            'status'         => 'processing',
            'extracted_text' => substr($cvText, 0, 15000),
        ]);

        try {
            $start = microtime(true);
            $prompt = $this->buildRewritePrompt($cvText, $targetRole);
            $rewrittenText = $this->callCohere($prompt, 4000, true);

            $enhancement->update([
                'status'            => 'completed',
                'rewritten_cv_text' => $rewrittenText,
                'ai_model'          => 'cohere-command-a-03-2025',
                'processing_ms'     => (int)((microtime(true) - $start) * 1000),
            ]);

            $this->incrementCounter($userId, 'cv_rewrites_count');
            $this->sendRewriteEmail($userId, $enhancement);

            return [
                'success' => true,
                'error' => null,
                'enhancement' => $enhancement->fresh()
            ];

        } catch (\Exception $e) {
            Log::error('[CVEnhancement] Rewrite failed', ['user_id' => $userId, 'error' => $e->getMessage()]);
            $enhancement->update(['status' => 'failed', 'error_message' => $e->getMessage()]);
            
            return [
                'success' => false,
                'error' => 'AI processing failed: ' . $e->getMessage(),
                'enhancement' => $enhancement
            ];
        }
    }

    /**
     * Generate cover letter for a specific job
     */
    public function generateCoverLetter(
        int $userId,
        string $jobTitle,
        string $jobDescription,
        ?UploadedFile $file = null,
        ?string $responsibilities = null,
        ?string $requiredSkills = null,
        ?string $companyName = null,
        ?string $hiringManager = null
    ): array {
        
        // Get CV content
        $cvResult = $this->getCVContent($userId, $file);
        
        if (!$cvResult['content']) {
            return [
                'success' => false,
                'error' => $cvResult['error'],
                'letter' => null
            ];
        }

        $cvText = $cvResult['content'];

        $letter = CoverLetter::create([
            'user_id'          => $userId,
            'job_title'        => $jobTitle,
            'job_description'  => $jobDescription,
            'responsibilities' => $responsibilities,
            'required_skills'  => $requiredSkills,
            'company_name'     => $companyName,
            'hiring_manager'   => $hiringManager,
            'status'           => 'processing',
        ]);

        try {
            $start = microtime(true);

            $matchPrompt = $this->buildMatchPrompt($cvText, $jobDescription, $requiredSkills);
            $matchData = $this->parseJsonResponse($this->callCohere($matchPrompt, 1000));

            $letterPrompt = $this->buildCoverLetterPrompt(
                $cvText, $jobTitle, $jobDescription, $responsibilities,
                $requiredSkills, $companyName, $hiringManager, $matchData
            );
            $generatedLetter = $this->callCohere($letterPrompt, 1500, true);

            $letter->update([
                'status'           => 'completed',
                'match_score'      => $matchData['match_score'] ?? null,
                'matched_skills'   => $matchData['matched'] ?? [],
                'missing_skills'   => $matchData['missing'] ?? [],
                'generated_letter' => $generatedLetter,
                'ai_model'         => 'cohere-command-a-03-2025',
            ]);

            $this->incrementCounter($userId, 'cover_letters_count');
            $this->sendCoverLetterEmail($userId, $letter);

            return [
                'success' => true,
                'error' => null,
                'letter' => $letter->fresh()
            ];

        } catch (\Exception $e) {
            Log::error('[CVEnhancement] Cover letter failed', ['user_id' => $userId, 'error' => $e->getMessage()]);
            $letter->update(['status' => 'failed', 'error_message' => $e->getMessage()]);
            
            return [
                'success' => false,
                'error' => 'AI processing failed: ' . $e->getMessage(),
                'letter' => $letter
            ];
        }
    }

    /**
     * Build review prompt
     */
    private function buildReviewPrompt(string $cvText, ?string $targetRole): string
    {
        $roleContext = $targetRole ? "Target role: {$targetRole}." : '';

        return self::HR_SYSTEM_PROMPT . "\n\n" . <<<PROMPT
            {$roleContext}

            Review this CV as a senior HR professional. Return ONLY valid JSON:

            {
            "overall_impression": "2-3 sentence executive summary",
            "ats_score": 72,
            "strengths": ["strength 1", "strength 2"],
            "critical_issues": [
                {"section": "Work Experience", "issue": "problem description", "fix": "specific solution"}
            ],
            "improvement_areas": {"formatting": [], "content": [], "language": []},
            "keyword_gaps": ["keyword1", "keyword2"],
            "recommended_actions": ["action 1", "action 2"]
            }

            CV TO REVIEW:
            {$cvText}
            PROMPT;
    }

    /**
     * Build rewrite prompt - instruct AI not to add footers
     */
    private function buildRewritePrompt(string $cvText, ?string $targetRole): string
    {
        $roleContext = $targetRole ? "Tailor this CV for: {$targetRole}." : '';

        return self::HR_SYSTEM_PROMPT . "\n\n" . <<<PROMPT
        {$roleContext}

        Rewrite this CV to professional East African standards. Keep all factual information. Max 2 pages.

        IMPORTANT RULES:
        - Do NOT add any footer text, notes, or "This CV is tailored for..." messages
        - Do NOT add "---" separators
        - Do NOT add any commentary about the CV
        - Return ONLY the CV content itself, nothing else
        - Start directly with the candidate's name

        Use this structure:

        [FULL NAME]
        [Phone] | [Email] | [Location]

        PROFILE SUMMARY
        [3-5 sentences, strong opening, no "I" statements]

        CORE COMPETENCIES
        • Skill 1 • Skill 2 • Skill 3 • Skill 4 • Skill 5 • Skill 6

        PROFESSIONAL EXPERIENCE
        [Job Title] | [Employer] | [Dates]
        • [Quantified achievement]
        • [Achievement]
        • [Achievement]

        EDUCATION
        [Degree] | [Institution] | [Year]

        CERTIFICATIONS (if any)
        • [Certification] | [Issuer] | [Year]

        TECHNICAL SKILLS
        • Skill 1 • Skill 2 • Skill 3

        PROJECTS (if any)
        • [Project name] - [Brief description]

        REFERENCES
        Available upon request.

        ORIGINAL CV:
        {$cvText}
        PROMPT;
    }

    /**
     * Build match analysis prompt
     */
    private function buildMatchPrompt(string $cvText, string $jd, ?string $skills): string
    {
        return <<<PROMPT
            Analyse CV vs Job Description match. Return ONLY JSON:
            {
            "match_score": 72,
            "matched": ["skill1", "skill2"],
            "missing": ["skill3", "skill4"],
            "summary": "One sentence on overall fit"
            }

            CV:
            {$cvText}

            JOB DESCRIPTION:
            {$jd}

            REQUIRED SKILLS:
            {$skills}
            PROMPT;
    }

    /**
     * Build cover letter prompt
     */
    private function buildCoverLetterPrompt(
        string $cvText, string $jobTitle, string $jd,
        ?string $responsibilities, ?string $skills,
        ?string $company, ?string $hiringManager, array $matchData
    ): string {
        $salutation = $hiringManager ? "Dear {$hiringManager}," : 'Dear Hiring Manager,';
        $companyName = $company ?? 'your organisation';
        $matched = implode(', ', $matchData['matched'] ?? []);
        $missing = implode(', ', $matchData['missing'] ?? []);

        return self::HR_SYSTEM_PROMPT . "\n\n" . <<<PROMPT
            Write a professional cover letter. Requirements:
            - 3-4 paragraphs, max 400 words
            - Specific, not generic
            - Opening: strong hook connecting candidate's key strength to the role
            - Body: 2-3 specific achievements from CV
            - Closing: confident call to action
            - No clichés

            Matched skills: {$matched}
            Skills gap (address positively): {$missing}

            SALUTATION: {$salutation}
            COMPANY: {$companyName}
            JOB TITLE: {$jobTitle}

            JOB DESCRIPTION:
            {$jd}

            CANDIDATE CV:
            {$cvText}
        PROMPT;
    }


    /**
     * Call Cohere API and clean the response
     */
    private function callCohere(string $prompt, int $maxTokens = 2000, bool $rawText = false): string
    {
        if (empty($this->cohereKey)) {
            throw new \Exception('Cohere API key not configured');
        }

        $response = Http::withHeaders([
            'Authorization' => 'Bearer ' . $this->cohereKey,
            'Content-Type' => 'application/json',
        ])
        ->timeout(120)
        ->post('https://api.cohere.ai/v2/chat', [
            'model' => 'command-a-03-2025',
            'max_tokens' => $maxTokens,
            'temperature' => 0.2,
            'messages' => [['role' => 'user', 'content' => $prompt]],
        ]);

        if (!$response->successful()) {
            throw new \Exception('Cohere API error: ' . $response->body());
        }

        $text = $response->json('message.content.0.text') ?? '';
        if (empty($text)) {
            throw new \Exception('Empty response from Cohere');
        }

        // Clean the response
        $text = $this->cleanAIResponse($text);
        
        return $rawText ? trim($text) : $text;
    }

    /**
     * Clean AI response by removing markdown, footers, and extra content
     */
    private function cleanAIResponse(string $text): string
    {
        // Remove markdown code blocks
        $text = preg_replace('/```(?:markdown|text|html)?\s*/i', '', $text);
        $text = preg_replace('/\s*```\s*$/i', '', $text);
        
        // Remove anything after "---" (footer content)
        $parts = preg_split('/\n---\s*\n/', $text);
        $text = $parts[0];
        
        // Remove lines that are just dashes
        $text = preg_replace('/^---+$/m', '', $text);
        
        // Remove common footer phrases
        $footerPhrases = [
            '/This CV is tailored for.*$/i',
            '/This resume is tailored for.*$/i',
            '/The structure adheres to.*$/i',
            '/This document is.*$/i',
            '/---$/m',
            '/^\s*---\s*$/m',
        ];
        
        foreach ($footerPhrases as $pattern) {
            $text = preg_replace($pattern, '', $text);
        }
        
        // Clean up extra whitespace
        $text = preg_replace('/\n{3,}/', "\n\n", $text);
        $text = trim($text);
        
        return $text;
    }

    /**
     * Parse JSON response from Cohere
     */
    private function parseJsonResponse(string $raw): array
    {
        $clean = preg_replace('/^```(?:json)?\s*/i', '', trim($raw));
        $clean = preg_replace('/\s*```\s*$/i', '', $clean);
        $clean = trim($clean);
        
        $decoded = json_decode($clean, true);
        return is_array($decoded) ? $decoded : [];
    }

    /**
     * Increment usage counter
     */
    private function incrementCounter(int $userId, string $field): void
    {
        $counter = CvUsageCounter::firstOrCreate(
            ['user_id' => $userId],
            [
                'period_start' => now()->startOfMonth(),
                'cv_reviews_count' => 0,
                'cv_rewrites_count' => 0,
                'cover_letters_count' => 0,
            ]
        );
        
        // Use Laravel's built-in increment directly on the model
        $counter->increment($field);
    }

    /**
     * Check user limits
     */
    public function checkLimit(int $userId, string $type): array
    {
        $limits = [
            'review' => 5,
            'rewrite' => 2,
            'cover_letter' => 10,
        ];

        $counter = CvUsageCounter::firstOrCreate(['user_id' => $userId]);
        $field = $type === 'review' ? 'cv_reviews_count' : ($type === 'rewrite' ? 'cv_rewrites_count' : 'cover_letters_count');
        $used = $counter->$field ?? 0;
        $limit = $limits[$type] ?? 5;

        return ['allowed' => $used < $limit, 'used' => $used, 'limit' => $limit];
    }

    /**
     * Send review email
     */
    private function sendReviewEmail(int $userId, CvEnhancement $enhancement): void
    {
        $user = User::find($userId);
        if (!$user) return;

        $feedback = $enhancement->review_feedback ?? [];
        
        Mail::to($user->email)->send(new CVEnhancementMail(
            user: ['id' => $user->id, 'email' => $user->email, 'first_name' => $user->first_name, 'full_name' => $user->full_name],
            type: 'review',
            content: json_encode($feedback, JSON_PRETTY_PRINT),
            atsScore: $enhancement->ats_score,
            strengths: $feedback['strengths'] ?? [],
            criticalIssues: $feedback['critical_issues'] ?? [],
            keywordGaps: $enhancement->keyword_gaps ?? [],
            recommendedActions: $feedback['recommended_actions'] ?? []
        ));

        $enhancement->update(['email_sent' => true, 'email_sent_at' => now()]);
    }

    /**
     * Send rewrite email
     */
    private function sendRewriteEmail(int $userId, CvEnhancement $enhancement): void
    {
        $user = User::find($userId);
        if (!$user) return;

        Mail::to($user->email)->send(new CVEnhancementMail(
            user: ['id' => $user->id, 'email' => $user->email, 'first_name' => $user->first_name, 'full_name' => $user->full_name],
            type: 'rewrite',
            content: $enhancement->rewritten_cv_text ?? ''
        ));

        $enhancement->update(['email_sent' => true, 'email_sent_at' => now()]);
    }

    /**
     * Send cover letter email
     */
    private function sendCoverLetterEmail(int $userId, CoverLetter $letter): void
    {
        $user = User::find($userId);
        if (!$user) return;

        Mail::to($user->email)->send(new CVEnhancementMail(
            user: ['id' => $user->id, 'email' => $user->email, 'first_name' => $user->first_name, 'full_name' => $user->full_name],
            type: 'cover_letter',
            content: $letter->generated_letter ?? '',
            matchScore: $letter->match_score,
            matchedSkills: $letter->matched_skills ?? [],
            missingSkills: $letter->missing_skills ?? []
        ));

        $letter->update(['email_sent' => true, 'email_sent_at' => now()]);
    }

    /**
     * Generate PDF for rewritten CV
     */
    private function generateRewritePDF(CvEnhancement $enhancement): void
    {
        // TODO: Implement PDF generation using DomPDF or similar
        // Store PDF at $path and update $enhancement->rewritten_cv_path
    }
}