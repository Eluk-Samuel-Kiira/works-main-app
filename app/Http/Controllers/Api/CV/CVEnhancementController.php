<?php
// MAIN APP: app/Http/Controllers/Api/CV/CVEnhancementController.php

namespace App\Http\Controllers\Api\CV;

use App\Http\Concerns\ApiResponse;
use App\Http\Controllers\Controller;
use App\Models\CV\CvEnhancement;
use App\Models\CV\CoverLetter;
use App\Models\CV\CvUsageCounter;
use App\Models\Seeker\SeekerCV;
use App\Services\CVEnhancementService;
use Illuminate\Http\{Request, JsonResponse};
use Illuminate\Support\Facades\{Storage, Log, Mail};
use PhpOffice\PhpWord\PhpWord;
use PhpOffice\PhpWord\IOFactory;
use PhpOffice\PhpWord\SimpleType\Jc;
use PhpOffice\PhpWord\Style\Paragraph;
use App\Models\Notification;
use App\Mail\ContactNotification;


class CVEnhancementController extends Controller
{
    use ApiResponse;

    public function __construct(private CVEnhancementService $service) {}

    /**
     * POST /api/v1/cv-enhancement/review
     */
    public function review(Request $request): JsonResponse
    {
        $request->validate([
            'cv_file'     => 'nullable|file|mimes:pdf,doc,docx|max:5120',
            'target_role' => 'nullable|string|max:255',
        ]);

        $user = $request->user();

        // Check usage limit first
        $limit = $this->service->checkLimit($user->id, 'review');
        if (!$limit['allowed']) {
            return $this->error("You've used {$limit['used']} of {$limit['limit']} CV reviews this month. Upgrade to continue.", 403);
        }

        $result = $this->service->reviewCV(
            $user->id,
            $request->hasFile('cv_file') ? $request->file('cv_file') : null,
            $request->target_role
        );

        if (!$result['success']) {
            return $this->error($result['error'], 422);
        }

        return $this->success($this->formatEnhancement($result['enhancement']), 'CV review completed');
    }

    /**
     * POST /api/v1/cv-enhancement/rewrite
     */
    public function rewrite(Request $request): JsonResponse
    {
        $request->validate([
            'cv_file'     => 'nullable|file|mimes:pdf,doc,docx|max:5120',
            'target_role' => 'nullable|string|max:255',
        ]);

        $user = $request->user();

        $limit = $this->service->checkLimit($user->id, 'rewrite');
        if (!$limit['allowed']) {
            return $this->error("You've used {$limit['used']} of {$limit['limit']} CV rewrites this month. Upgrade to continue.", 403);
        }

        $result = $this->service->rewriteCV(
            $user->id,
            $request->hasFile('cv_file') ? $request->file('cv_file') : null,
            $request->target_role
        );

        if (!$result['success']) {
            return $this->error($result['error'], 422);
        }

        return $this->success($this->formatEnhancement($result['enhancement']), 'CV rewrite completed');
    }

    /**
     * POST /api/v1/cv-enhancement/cover-letter
     */
    public function coverLetter(Request $request): JsonResponse
    {
        $request->validate([
            'job_title'       => 'required|string|max:255',
            'job_description' => 'required|string|min:50',
            'cv_file'         => 'nullable|file|mimes:pdf,doc,docx|max:5120',
            'responsibilities'=> 'nullable|string',
            'required_skills' => 'nullable|string',
            'company_name'    => 'nullable|string|max:255',
            'hiring_manager'  => 'nullable|string|max:255',
        ]);

        $user = $request->user();

        $limit = $this->service->checkLimit($user->id, 'cover_letter');
        if (!$limit['allowed']) {
            return $this->error("You've used {$limit['used']} of {$limit['limit']} cover letters this month. Upgrade to continue.", 403);
        }

        $result = $this->service->generateCoverLetter(
            $user->id,
            $request->job_title,
            $request->job_description,
            $request->hasFile('cv_file') ? $request->file('cv_file') : null,
            $request->responsibilities,
            $request->required_skills,
            $request->company_name,
            $request->hiring_manager
        );

        if (!$result['success']) {
            return $this->error($result['error'], 422);
        }

        $letter = $result['letter'];

        return $this->success([
            'id'               => $letter->id,
            'status'           => $letter->status,
            'job_title'        => $letter->job_title,
            'match_score'      => $letter->match_score,
            'matched_skills'   => $letter->matched_skills,
            'missing_skills'   => $letter->missing_skills,
            'generated_letter' => $letter->generated_letter,
        ], 'Cover letter generated');
    }

    /**
     * GET /api/v1/cv-enhancement/history
     */
    public function history(Request $request): JsonResponse
    {
        $userId = $request->user()->id;

        // Get all enhancements for this user
        $enhancements = CvEnhancement::where('user_id', $userId)
            ->orderByDesc('created_at')
            ->get()
            ->map(fn($e) => [
                'id' => $e->id,
                'type' => $e->type,
                'status' => $e->status,
                'ats_score' => $e->ats_score,
                'created_at' => $e->created_at?->format('d M Y H:i'),
                'has_feedback' => !is_null($e->review_feedback),
                'has_rewrite' => !is_null($e->rewritten_cv_text),
            ]);

        // Get all cover letters for this user
        $letters = CoverLetter::where('user_id', $userId)
            ->orderByDesc('created_at')
            ->get()
            ->map(fn($l) => [
                'id' => $l->id,
                'type' => 'cover_letter',
                'job_title' => $l->job_title,
                'company_name' => $l->company_name,
                'status' => $l->status,
                'match_score' => $l->match_score,
                'created_at' => $l->created_at?->format('d M Y H:i'),
                'has_letter' => !is_null($l->generated_letter),
            ]);

        // Get usage counters
        $counter = CvUsageCounter::firstOrCreate(
            ['user_id' => $userId],
            [
                'cv_reviews_count' => 0,
                'cv_rewrites_count' => 0,
                'cover_letters_count' => 0,
                'period_start' => now()->startOfMonth(),
            ]
        );

        // Merge and sort all items
        $allItems = collect();
        
        foreach ($enhancements as $e) {
            $allItems->push($e);
        }
        foreach ($letters as $l) {
            $allItems->push($l);
        }
        
        $sortedItems = $allItems->sortByDesc('created_at')->values();

        return $this->success([
            'enhancements' => $enhancements,
            'cover_letters' => $letters,
            'all_history' => $sortedItems,
            'usage' => [
                'cv_reviews_count' => (int) $counter->cv_reviews_count,
                'cv_rewrites_count' => (int) $counter->cv_rewrites_count,
                'cover_letters_count' => (int) $counter->cover_letters_count,
            ],
        ]);
    }


    /**
     * Extract CV text from uploaded file or user profile
     */
    private function extractCvText(Request $request, int $userId): ?string
    {
        // 1. Check uploaded file
        if ($request->hasFile('cv_file')) {
            $file = $request->file('cv_file');
            return $this->extractTextFromFile($file);
        }

        // 2. Check user's stored CV
        $seekerCv = SeekerCV::where('user_id', $userId)->first();
        if ($seekerCv && $seekerCv->cv_file_path) {
            $fullPath = Storage::disk('public')->path($seekerCv->cv_file_path);
            if (file_exists($fullPath)) {
                $file = new \Illuminate\Http\UploadedFile($fullPath, basename($fullPath));
                return $this->extractTextFromFile($file);
            }
        }

        // 3. Build text from structured profile
        if ($seekerCv) {
            return $this->buildTextFromProfile($seekerCv);
        }

        return null;
    }

    /**
     * Extract text from PDF or DOCX file
     */
    private function extractTextFromFile($file): string
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

        $lines[] = strtoupper(trim($cv->first_name . ' ' . $cv->last_name));
        if ($cv->phone) $lines[] = $cv->phone;
        if ($cv->email) $lines[] = $cv->email;
        if ($cv->city || $cv->country) $lines[] = implode(', ', array_filter([$cv->city, $cv->country]));
        $lines[] = '';

        if ($cv->professional_summary) {
            $lines[] = 'PROFILE SUMMARY';
            $lines[] = $cv->professional_summary;
            $lines[] = '';
        }

        if (!empty($cv->skills)) {
            $lines[] = 'SKILLS';
            foreach ($cv->skills as $s) $lines[] = '- ' . $s;
            $lines[] = '';
        }

        if (!empty($cv->work_experience)) {
            $lines[] = 'WORK EXPERIENCE';
            foreach ($cv->work_experience as $exp) {
                $lines[] = ($exp['job_title'] ?? '') . ' | ' . ($exp['company'] ?? '');
                if (!empty($exp['description'])) $lines[] = $exp['description'];
                $lines[] = '';
            }
        }

        if (!empty($cv->education)) {
            $lines[] = 'EDUCATION';
            foreach ($cv->education as $edu) {
                $lines[] = ($edu['degree'] ?? '') . ' | ' . ($edu['institution'] ?? '');
            }
        }

        return implode("\n", $lines);
    }

    /**
     * Format enhancement for API response
     */
    private function formatEnhancement(CvEnhancement $e): array
    {
        return [
            'id'                 => $e->id,
            'type'               => $e->type,
            'status'             => $e->status,
            'ats_score'          => $e->ats_score,
            'feedback'           => $e->review_feedback,
            'keyword_gaps'       => $e->keyword_gaps,
            'improvement_areas'  => $e->improvement_areas,
            'strengths'          => $e->strengths,
            'recommended_actions'=> $e->recommended_actions,
            'rewritten_cv_text'  => $e->rewritten_cv_text,
            'rewritten_cv_path'  => $e->rewritten_cv_path ? Storage::url($e->rewritten_cv_path) : null,
            'error'              => $e->error_message,
            'created_at'         => $e->created_at?->format('d M Y H:i'),
            'processing_ms'      => $e->processing_ms,
        ];
    }


    /**
     * GET /api/v1/cv-enhancement/download/{id}
     * Supports format: word, text, html
     */
    public function download(Request $request, int $id): \Symfony\Component\HttpFoundation\BinaryFileResponse|JsonResponse|\Illuminate\Http\Response
    {
        try {
            $enhancement = CvEnhancement::where('user_id', $request->user()->id)
                ->where('id', $id)
                ->first();

            if (!$enhancement || !$enhancement->rewritten_cv_text) {
                return response()->json(['error' => 'File not found'], 404);
            }

            $format = $request->get('format', 'word');
            $content = $this->cleanCVContent($enhancement->rewritten_cv_text);
            $filename = 'cv_' . $request->user()->id . '_' . time();
            
            switch ($format) {
                case 'word':
                    return $this->downloadAsWord($content, $filename);
                case 'html':
                    return $this->downloadAsHtml($content, $filename);
                case 'text':
                default:
                    return $this->downloadAsText($content, $filename);
            }
                
        } catch (\Exception $e) {
            Log::error('[CVEnhancement] Download failed', ['error' => $e->getMessage()]);
            return response()->json(['error' => 'Download failed: ' . $e->getMessage()], 500);
        }
    }

    /**
     * Download as Word document (editable)
     */
    private function downloadAsWord(string $content, string $filename): \Symfony\Component\HttpFoundation\BinaryFileResponse
    {
        $phpWord = new PhpWord();
        
        // Set document properties
        $phpWord->getDocumentProperties()
            ->setCreator('Stardena Works')
            ->setTitle('CV')
            ->setSubject('Professional CV');
        
        // Add a section
        $section = $phpWord->addSection([
            'margin' => ['top' => 720, 'right' => 720, 'bottom' => 720, 'left' => 720]
        ]);
        
        // Parse and format content
        $lines = explode("\n", $content);
        $inList = false;
        
        foreach ($lines as $line) {
            $line = trim($line);
            if (empty($line)) {
                if ($inList) {
                    $inList = false;
                }
                $section->addTextBreak();
                continue;
            }
            
            // Check for markdown bold
            $hasBold = preg_match('/\*\*(.*?)\*\*/', $line);
            $cleanLine = preg_replace('/\*\*(.*?)\*\*/', '$1', $line);
            
            // Check if it's a heading (all caps or contains section marker)
            $isHeading = preg_match('/^(PROFILE SUMMARY|CORE COMPETENCIES|PROFESSIONAL EXPERIENCE|EDUCATION|CERTIFICATIONS|TECHNICAL SKILLS|PROJECTS|REFERENCES|LANGUAGES)/i', $cleanLine);
            
            // Check for bullet points
            $isBullet = preg_match('/^[•\-*]\s/', $cleanLine) || preg_match('/^\d+\./', $cleanLine);
            
            if ($isBullet) {
                // Remove bullet character
                $bulletText = preg_replace('/^[•\-*\d+\.]\s*/', '', $cleanLine);
                $inList = true;
                $section->addText('  • ' . $bulletText, ['size' => 11], ['spaceAfter' => 60]);
            } else {
                if ($inList) {
                    $inList = false;
                }
                
                // Different styles for different line types
                if ($isHeading) {
                    // Section headings
                    $section->addText($cleanLine, ['bold' => true, 'size' => 13, 'color' => '1e3a8a'], ['spaceBefore' => 240, 'spaceAfter' => 120]);
                } elseif ($hasBold && !$isHeading) {
                    // Bold text (like job titles)
                    $section->addText($cleanLine, ['bold' => true, 'size' => 11], ['spaceAfter' => 60]);
                } else {
                    // Normal text
                    $section->addText($cleanLine, ['size' => 11], ['spaceAfter' => 60]);
                }
            }
        }
        
        // Save to temp file
        $tempFile = tempnam(sys_get_temp_dir(), 'cv_');
        $objWriter = IOFactory::createWriter($phpWord, 'Word2007');
        $objWriter->save($tempFile);
        
        return response()->download($tempFile, $filename . '.docx')->deleteFileAfterSend(true);
    }

    /**
     * Download as HTML (can be opened in browser)
     */
    private function downloadAsHtml(string $content, string $filename): \Illuminate\Http\Response
    {
        $html = $this->convertToHtml($content);
        
        return response($html, 200)
            ->header('Content-Type', 'text/html')
            ->header('Content-Disposition', 'attachment; filename="' . $filename . '.html"');
    }

    /**
     * Download as plain text
     */
    private function downloadAsText(string $content, string $filename): \Illuminate\Http\Response
    {
        // Remove markdown bold markers
        $plainText = preg_replace('/\*\*(.*?)\*\*/', '$1', $content);
        
        return response($plainText, 200)
            ->header('Content-Type', 'text/plain')
            ->header('Content-Disposition', 'attachment; filename="' . $filename . '.txt"');
    }

    /**
     * Convert plain text to HTML for email and preview
     */
    private function convertToHtml(string $text): string
    {
        // Convert markdown bold
        $text = preg_replace('/\*\*(.*?)\*\*/', '<strong>$1</strong>', $text);
        
        // Convert bullet points with • or -
        $text = preg_replace('/^[•\-]\s+(.*?)$/m', '<li>$1</li>', $text);
        $text = preg_replace('/(<li>.*?<\/li>\n?)+/s', '<ul>$0</ul>', $text);
        
        // Convert email addresses to mailto links
        $text = preg_replace('/([a-zA-Z0-9._%+-]+@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,})/', '<a href="mailto:$1">$1</a>', $text);
        
        // Convert phone numbers
        $text = preg_replace('/(\+256\s?\d{3}\s?\d{3}\s?\d{4})/', '<a href="tel:$1">$1</a>', $text);
        
        // Convert line breaks
        $text = nl2br($text);
        
        // Detect headings
        $headings = ['PROFILE SUMMARY', 'CORE COMPETENCIES', 'PROFESSIONAL EXPERIENCE', 'EDUCATION', 'CERTIFICATIONS', 'TECHNICAL SKILLS', 'PROJECTS', 'REFERENCES', 'LANGUAGES'];
        foreach ($headings as $heading) {
            $text = preg_replace('/' . $heading . '/', '<strong style="color:#1e3a8a; font-size:14px;">' . $heading . '</strong>', $text);
        }
        
        return '<!DOCTYPE html>
        <html>
        <head>
            <meta charset="UTF-8">
            <title>CV</title>
            <style>
                body {
                    font-family: Arial, sans-serif;
                    font-size: 12px;
                    line-height: 1.5;
                    margin: 40px;
                    color: #1a202c;
                }
                strong {
                    color: #1e3a8a;
                    font-weight: 700;
                }
                ul {
                    margin: 8px 0;
                    padding-left: 20px;
                }
                li {
                    margin: 4px 0;
                }
                a {
                    color: #2563eb;
                    text-decoration: none;
                }
                .header {
                    text-align: center;
                    margin-bottom: 20px;
                }
            </style>
        </head>
        <body>
            ' . $text . '
        </body>
        </html>';
    }

    /**
     * Clean CV content by removing footers
     */
    private function cleanCVContent(string $content): string
    {
        // Remove anything after "---"
        $parts = preg_split('/\n---\s*\n/', $content);
        $content = $parts[0];
        
        // Remove footer phrases
        $footerPhrases = [
            '/This CV is tailored for.*$/i',
            '/The structure adheres to.*$/i',
            '/---$/m',
            '/^\s*---\s*$/m',
            '/This resume is.*$/i',
        ];
        
        foreach ($footerPhrases as $pattern) {
            $content = preg_replace($pattern, '', $content);
        }
        
        return trim($content);
    }





    public function store(Request $request)
    {
        $validated = $request->validate([
            'name'    => 'required|string|max:100',
            'email'   => 'required|email',
            'subject' => 'nullable|string|max:200',
            'message' => 'required|string|min:10|max:2000',
        ]);

        try {
            // Build the message content
            $messageContent = "Name: {$validated['name']}\n";
            $messageContent .= "Email: {$validated['email']}\n";
            $messageContent .= "Subject: " . ($validated['subject'] ?? 'General Inquiry') . "\n";
            $messageContent .= "Message:\n" . $validated['message'] . "\n";
            $messageContent .= "IP: " . $request->ip() . "\n";
            $messageContent .= "User Agent: " . $request->userAgent() . "\n";
            $messageContent .= "Submitted At: " . now()->format('Y-m-d H:i:s');

            // Store in notifications table using your model's structure
            $notification = Notification::create([
                'type' => 'contact_form',
                'title' => 'New Contact Form Submission',
                'message' => $messageContent,  // ← Using 'message' field, not 'content'
                'data' => [  // ← Using 'data' field for structured data
                    'name' => $validated['name'],
                    'email' => $validated['email'],
                    'subject' => $validated['subject'] ?? 'General Inquiry',
                    'message_text' => $validated['message'],
                    'ip_address' => $request->ip(),
                    'user_agent' => $request->userAgent(),
                ],
                'status' => 'unread',
                'priority' => 'medium',
                'read_at' => null,
                'user_id' => null,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            // Log::info('Contact form stored in notifications', [
            //     'notification_id' => $notification->id,
            //     'email' => $validated['email']
            // ]);
                    

            // Send notification email to admins
            try {
                $adminEmails = array_filter(array_map('trim', explode(',', env('ADMIN_EMAILS', ''))));
                if (!empty($adminEmails)) {
                    Mail::to('jobpost@stardenaworks.com')->cc('stardenaworks26@gmail.com')->send(new ContactNotification($validated, 'admin'));
                    Log::info('Admin notification emails sent', ['emails' => $adminEmails]);
                } else {
                    Mail::to(config('mail.from.address'))->send(new ContactNotification($validated, 'admin'));
                }
            } catch (\Exception $e) {
                Log::error('Failed to send admin notification email', ['error' => $e->getMessage()]);
            }

            return response()->json([
                'success' => true,
                'message' => 'Contact form received successfully',
                'data' => [
                    'notification_id' => $notification->id,
                    'name' => $validated['name'],
                    'email' => $validated['email'],
                ]
            ], 201);

        } catch (\Exception $e) {
            Log::error('Failed to process contact form', [
                'error' => $e->getMessage(),
                'data' => $validated
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to process contact form: ' . $e->getMessage()
            ], 500);
        }
    }
}
