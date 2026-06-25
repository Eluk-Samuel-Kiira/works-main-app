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
use Barryvdh\DomPDF\Facade\Pdf;

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
            $response = $this->callCohere($prompt, 3000, false, true);
            $feedback = $this->parseJsonResponse($response);
            
            if (!isset($feedback['ats_score']) || !is_numeric($feedback['ats_score'])) {
                Log::warning('[CVEnhancement] Review response missing ats_score', [
                    'user_id' => $userId,
                    'feedback_keys' => array_keys($feedback),
                ]);
                throw new \Exception('AI review did not return a valid score. Please try again.');
            }
            
            $enhancement->update([
                'status'              => 'completed',
                'review_feedback'     => $feedback,
                'ats_score'           => (int) $feedback['ats_score'],
                'keyword_gaps'        => $feedback['keyword_gaps'] ?? [],
                'improvement_areas'   => $feedback['improvement_areas'] ?? [],
                'strengths'           => $feedback['strengths'] ?? [],
                'recommended_actions' => $feedback['recommended_actions'] ?? [],
                'ai_model'            => 'cohere-command-a-03-2025',
                'processing_ms'       => (int)((microtime(true) - $start) * 1000),
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
            $matchData = $this->parseJsonResponse($this->callCohere($matchPrompt, 1000, false, true));

            $letterPrompt = $this->buildCoverLetterPrompt(
                $cvText, $jobTitle, $jobDescription, $responsibilities,
                $requiredSkills, $companyName, $hiringManager, $matchData
            );
            $generatedLetter = $this->callCohere($letterPrompt, 1500, true);
            // Clean the generated letter to remove markdown before storing
            $generatedLetter = $this->cleanMarkdown($generatedLetter);

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
     * Clean markdown formatting from AI responses
     */
    private function cleanMarkdown(string $text): string
    {
        // Remove **bold** markers but keep the text
        $text = preg_replace('/\*\*(.*?)\*\*/', '$1', $text);
        
        // Remove *italic* markers but keep the text
        $text = preg_replace('/\*(.*?)\*/', '$1', $text);
        
        // Remove markdown links [text](url) - keep just the text
        $text = preg_replace('/\[(.*?)\]\(.*?\)/', '$1', $text);
        
        // Remove code blocks
        $text = preg_replace('/```.*?```/s', '', $text);
        
        // Remove inline code
        $text = preg_replace('/`(.*?)`/', '$1', $text);
        
        // Remove extra whitespace
        $text = preg_replace('/\n{3,}/', "\n\n", $text);
        
        return trim($text);
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

            // 🔥 CLEAN THE MARKDOWN BEFORE STORING
            $rewrittenText = $this->cleanMarkdown($rewrittenText);

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
    private function callCohere(string $prompt, int $maxTokens = 2000, bool $rawText = false, bool $expectJson = false): string
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
    
        if ($expectJson) {
            // JSON path: ONLY strip markdown code fences. Do NOT run the
            // prose footer-stripper — it can corrupt JSON string values.
            $text = preg_replace('/^```(?:json)?\s*/i', '', trim($text));
            $text = preg_replace('/\s*```\s*$/i', '', $text);
            return trim($text);
        }
    
        // Prose path (rewrite / cover letter): existing behaviour unchanged.
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
    
        if (!is_array($decoded)) {
            Log::error('[CVEnhancement] Failed to parse AI JSON response', [
                'json_error' => json_last_error_msg(),
                'raw_snippet' => substr($raw, 0, 500),
            ]);
            throw new \Exception('AI returned an unreadable response. Please try again.');
        }
    
        return $decoded;
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
     * Check user limits based on their subscription plan from transactions table
     */
    public function checkLimit(int $userId, string $type): array
    {
        // Get user's active subscription from transactions table
        $subscription = $this->getUserSubscription($userId);
        
        // Default limits (for users with no subscription or expired)
        $limits = [
            'review' => 0,
            'rewrite' => 0,
            'cover_letter' => 0,
        ];
        
        // If user has an active subscription, set limits based on plan
        if ($subscription) {
            $plan = $subscription->subscription_plan;
            
            if ($plan === 'seeker_trial') {
                $limits = [
                    'review' => 3,
                    'rewrite' => 3,
                    'cover_letter' => 6,
                ];
            } elseif ($plan === 'seeker_basic') {
                $limits = [
                    'review' => 5,
                    'rewrite' => 5,
                    'cover_letter' => 10,
                ];
            } elseif (in_array($plan, ['seeker_pro', 'seeker_elite'])) {
                // Unlimited for Pro and Elite plans
                return ['allowed' => true, 'used' => 0, 'limit' => PHP_INT_MAX];
            }
        } else {
            // No active subscription - no usage allowed
            return ['allowed' => false, 'used' => 0, 'limit' => 0, 'message' => 'Please subscribe to continue using CV enhancement features.'];
        }

        // Get the user's usage counter
        $counter = CvUsageCounter::firstOrCreate(
            ['user_id' => $userId],
            [
                'cv_reviews_count' => 0,
                'cv_rewrites_count' => 0,
                'cover_letters_count' => 0,
                'period_start' => now()->startOfMonth(),
            ]
        );
        
        // Map the type to the correct field
        $field = match($type) {
            'review' => 'cv_reviews_count',
            'rewrite' => 'cv_rewrites_count',
            'cover_letter' => 'cover_letters_count',
            default => 'cv_reviews_count',
        };
        
        $used = $counter->$field ?? 0;
        $limit = $limits[$type] ?? 0;

        return [
            'allowed' => $used < $limit,
            'used' => $used,
            'limit' => $limit,
            'remaining' => max(0, $limit - $used),
            'plan' => $subscription ? $subscription->subscription_plan : null,
        ];
    }

    /**
     * Get user's active subscription from transactions table
     */
    private function getUserSubscription(int $userId)
    {
        // First, check for an active subscription (successful status)
        $subscription = \App\Models\Payments\Transaction::where('user_id', $userId)
            ->where('transaction_type', 'subscription')
            ->where('status', 'successful')
            ->where(function($query) {
                $query->whereNull('confirmed_at')
                    ->orWhere('confirmed_at', '<=', now());
            })
            ->orderByDesc('confirmed_at')
            ->first();
        
        // If found, check if it's still valid (within 30 days of confirmed_at)
        // For monthly subscriptions, they last 30 days from confirmation
        if ($subscription && $subscription->confirmed_at) {
            $expiryDate = $subscription->confirmed_at->addDays(30);
            if ($expiryDate->isPast()) {
                // Subscription expired - return null so user sees no active subscription
                return null;
            }
        }
        
        return $subscription;
    }

    /**
     * Get user's current plan with details
     */
    public function getUserPlanDetails(int $userId): array
    {
        $subscription = $this->getUserSubscription($userId);
        
        if (!$subscription) {
            return [
                'has_active_subscription' => false,
                'plan' => null,
                'plan_display_name' => null,
                'expiry_date' => null,
                'limits' => [
                    'review' => 0,
                    'rewrite' => 0,
                    'cover_letter' => 0,
                ],
                'usage' => [
                    'review' => 0,
                    'rewrite' => 0,
                    'cover_letter' => 0,
                ],
            ];
        }
        
        // Get usage counts
        $counter = CvUsageCounter::firstOrCreate(['user_id' => $userId]);
        
        // Get limits based on plan
        $limits = match($subscription->subscription_plan) {
            'seeker_trial' => ['review' => 3, 'rewrite' => 3, 'cover_letter' => 6],
            'seeker_basic' => ['review' => 5, 'rewrite' => 5, 'cover_letter' => 10],
            'seeker_pro', 'seeker_elite' => ['review' => PHP_INT_MAX, 'rewrite' => PHP_INT_MAX, 'cover_letter' => PHP_INT_MAX],
            default => ['review' => 0, 'rewrite' => 0, 'cover_letter' => 0],
        };
        
        // Plan display names
        $planNames = [
            'seeker_trial' => 'Free Trial',
            'seeker_basic' => 'Basic',
            'seeker_pro' => 'Pro',
            'seeker_elite' => 'Elite',
        ];
        
        return [
            'has_active_subscription' => true,
            'plan' => $subscription->subscription_plan,
            'plan_display_name' => $planNames[$subscription->subscription_plan] ?? ucfirst($subscription->subscription_plan),
            'expiry_date' => $subscription->confirmed_at ? $subscription->confirmed_at->addDays(30)->toIso8601String() : null,
            'limits' => $limits,
            'usage' => [
                'review' => (int) $counter->cv_reviews_count,
                'rewrite' => (int) $counter->cv_rewrites_count,
                'cover_letter' => (int) $counter->cover_letters_count,
            ],
            'remaining' => [
                'review' => max(0, $limits['review'] - (int) $counter->cv_reviews_count),
                'rewrite' => max(0, $limits['rewrite'] - (int) $counter->cv_rewrites_count),
                'cover_letter' => max(0, $limits['cover_letter'] - (int) $counter->cover_letters_count),
            ],
        ];
    }

    /**
     * Send review email with PDF attachment
     */
    private function sendReviewEmail(int $userId, CvEnhancement $enhancement): void
    {
        $user = User::find($userId);
        if (!$user) return;

        $feedback = $enhancement->review_feedback ?? [];
        
        // Generate PDF content from review feedback
        $pdfContent = $this->generatePDF(
            $this->formatReviewForPDF($feedback, $enhancement),
            'review',
            'cv-review.pdf'
        );
        
        Mail::to($user->email)->send(new CVEnhancementMail(
            user: ['id' => $user->id, 'email' => $user->email, 'first_name' => $user->first_name, 'full_name' => $user->full_name],
            type: 'review',
            content: json_encode($feedback, JSON_PRETTY_PRINT),
            atsScore: $enhancement->ats_score,
            strengths: $feedback['strengths'] ?? [],
            criticalIssues: $feedback['critical_issues'] ?? [],
            keywordGaps: $enhancement->keyword_gaps ?? [],
            recommendedActions: $feedback['recommended_actions'] ?? [],
            pdfContent: $pdfContent,
            pdfFilename: 'cv-review-report.pdf'
        ));

        $enhancement->update(['email_sent' => true, 'email_sent_at' => now()]);
    }

    /**
     * Send rewrite email with PDF attachment
     */
    private function sendRewriteEmail(int $userId, CvEnhancement $enhancement): void
    {
        $user = User::find($userId);
        if (!$user) return;

        // Generate PDF from rewritten CV text
        $pdfContent = $this->generatePDF(
            $enhancement->rewritten_cv_text ?? '',
            'cv'
        );

        Mail::to($user->email)->send(new CVEnhancementMail(
            user: ['id' => $user->id, 'email' => $user->email, 'first_name' => $user->first_name, 'full_name' => $user->full_name],
            type: 'rewrite',
            content: $enhancement->rewritten_cv_text ?? '',
            pdfContent: $pdfContent,
            pdfFilename: 'rewritten-cv.pdf'
        ));

        $enhancement->update(['email_sent' => true, 'email_sent_at' => now()]);
    }

    /**
     * Send cover letter email with PDF attachment
     */
    private function sendCoverLetterEmail(int $userId, CoverLetter $letter): void
    {
        $user = User::find($userId);
        if (!$user) return;

        // Generate PDF from cover letter
        $pdfContent = $this->generatePDF(
            $letter->generated_letter ?? '',
            'cover_letter'
        );

        Mail::to($user->email)->send(new CVEnhancementMail(
            user: ['id' => $user->id, 'email' => $user->email, 'first_name' => $user->first_name, 'full_name' => $user->full_name],
            type: 'cover_letter',
            content: $letter->generated_letter ?? '',
            matchScore: $letter->match_score,
            matchedSkills: $letter->matched_skills ?? [],
            missingSkills: $letter->missing_skills ?? [],
            pdfContent: $pdfContent,
            pdfFilename: 'cover-letter.pdf'
        ));

        $letter->update(['email_sent' => true, 'email_sent_at' => now()]);
    }

    /**
     * Format review feedback for PDF
     */
    private function formatReviewForPDF(array $feedback, CvEnhancement $enhancement): string
    {
        $lines = [];
        
        $lines[] = 'CV REVIEW REPORT';
        $lines[] = '================';
        $lines[] = '';
        $lines[] = "ATS Score: {$enhancement->ats_score}%";
        $lines[] = '';
        
        if (!empty($feedback['overall_impression'])) {
            $lines[] = 'OVERALL IMPRESSION';
            $lines[] = '-----------------';
            $lines[] = $feedback['overall_impression'];
            $lines[] = '';
        }
        
        if (!empty($feedback['strengths']) && is_array($feedback['strengths'])) {
            $lines[] = 'STRENGTHS';
            $lines[] = '---------';
            foreach ($feedback['strengths'] as $strength) {
                $lines[] = "• {$strength}";
            }
            $lines[] = '';
        }
        
        // Handle critical_issues properly - it's an array of objects with 'section', 'issue', 'fix'
        if (!empty($feedback['critical_issues']) && is_array($feedback['critical_issues'])) {
            $lines[] = 'CRITICAL ISSUES';
            $lines[] = '--------------';
            foreach ($feedback['critical_issues'] as $issue) {
                if (is_array($issue)) {
                    // Handle the structure: ['section' => 'Work Experience', 'issue' => '...', 'fix' => '...']
                    $section = $issue['section'] ?? '';
                    $issueText = $issue['issue'] ?? '';
                    $fix = $issue['fix'] ?? '';
                    
                    if ($section) {
                        $lines[] = "• [{$section}] {$issueText}";
                    } else {
                        $lines[] = "• {$issueText}";
                    }
                    
                    if ($fix) {
                        $lines[] = "  ✓ Fix: {$fix}";
                    }
                } else {
                    // If it's just a string
                    $lines[] = "• {$issue}";
                }
                $lines[] = '';
            }
        }
        
        // Handle improvement_areas if present
        if (!empty($feedback['improvement_areas']) && is_array($feedback['improvement_areas'])) {
            $lines[] = 'IMPROVEMENT AREAS';
            $lines[] = '-----------------';
            
            // Check if it's an associative array with keys like 'formatting', 'content', 'language'
            if (isset($feedback['improvement_areas']['formatting']) || 
                isset($feedback['improvement_areas']['content']) || 
                isset($feedback['improvement_areas']['language'])) {
                
                foreach ($feedback['improvement_areas'] as $area => $items) {
                    if (!empty($items) && is_array($items)) {
                        $lines[] = ucfirst($area) . ':';
                        foreach ($items as $item) {
                            $lines[] = "  • {$item}";
                        }
                    }
                }
            } else {
                // If it's a simple array
                foreach ($feedback['improvement_areas'] as $area) {
                    if (is_string($area)) {
                        $lines[] = "• {$area}";
                    }
                }
            }
            $lines[] = '';
        }
        
        if (!empty($enhancement->keyword_gaps) && is_array($enhancement->keyword_gaps)) {
            $lines[] = 'KEYWORD GAPS';
            $lines[] = '------------';
            foreach ($enhancement->keyword_gaps as $keyword) {
                $lines[] = "• {$keyword}";
            }
            $lines[] = '';
        }
        
        if (!empty($feedback['recommended_actions']) && is_array($feedback['recommended_actions'])) {
            $lines[] = 'RECOMMENDED ACTIONS';
            $lines[] = '------------------';
            foreach ($feedback['recommended_actions'] as $action) {
                $lines[] = "• {$action}";
            }
        }
        
        return implode("\n", $lines);
    }

    /**
     * Store PDF permanently and return path
     */
    private function storePDF(string $content, int $userId, string $type): ?string
    {
        try {
            $pdfContent = $this->generatePDF($content, $type);
            
            if (!$pdfContent) {
                return null;
            }
            
            $path = "cv_enhancements/user_{$userId}/" . date('Y/m/d') . "/{$type}_" . time() . ".pdf";
            
            Storage::disk('public')->put($path, $pdfContent);
            
            return $path;
            
        } catch (\Exception $e) {
            Log::error('[CVEnhancement] PDF storage failed', ['error' => $e->getMessage()]);
            return null;
        }
    }

    /**
     * Generate PDF for rewritten CV or cover letter
     */
        private function generatePDF(string $content, string $type = 'cv'): ?string
    {
        try {
            // Convert content to HTML
            $bodyHtml = $this->convertToHtml($content);
            
            // Extract body content if wrapped
            if (preg_match('/<body[^>]*>(.*)<\/body>/is', $bodyHtml, $m)) {
                $bodyHtml = $m[1];
            }
            
            // Use the PDF view
            $html = view('pdf.cv-document', [
                'bodyHtml' => $bodyHtml,
            ])->render();
            
            // Generate PDF
            $pdf = Pdf::loadHTML($html)->setPaper('a4');
            return $pdf->output();
            
        } catch (\Exception $e) {
            Log::error('[CVEnhancement] PDF generation failed', ['error' => $e->getMessage()]);
            return null;
        }
    }

    /**
     * Convert content to HTML for PDF
     */
    private function convertToHtml(string $text): string
    {
        // Convert markdown bold
        $text = preg_replace('/\*\*(.*?)\*\*/', '<strong>$1</strong>', $text);
        
        // Convert bullet points with • or -
        $text = preg_replace('/^[•\-]\s+(.*?)$/m', '<li>$1</li>', $text);
        $text = preg_replace('/(<li>.*?<\/li>\n?)+/s', '<ul style="margin:8px 0;padding-left:20px;">$0</ul>', $text);
        
        // Convert email addresses to mailto links
        $text = preg_replace('/([a-zA-Z0-9._%+-]+@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,})/', '<a href="mailto:$1">$1</a>', $text);
        
        // Convert phone numbers (East African format)
        $text = preg_replace('/(\+256\s?\d{3}\s?\d{3}\s?\d{4})/', '<a href="tel:$1">$1</a>', $text);
        
        // Convert line breaks
        $text = nl2br($text);
        
        // Detect and style headings
        $headings = ['PROFILE SUMMARY', 'CORE COMPETENCIES', 'PROFESSIONAL EXPERIENCE', 'EDUCATION', 'CERTIFICATIONS', 'TECHNICAL SKILLS', 'PROJECTS', 'REFERENCES', 'LANGUAGES'];
        foreach ($headings as $heading) {
            $text = preg_replace('/' . $heading . '/', '<strong style="color:#1e3a8a; font-size:14px;">' . $heading . '</strong>', $text);
        }
        
        return $text;
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