<?php
// app/Http/Controllers/Api/Seeker/SeekerCVController.php

namespace App\Http\Controllers\Api\Seeker;

use App\Http\Concerns\ApiResponse;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\Seeker\SeekerCVRequest;
use App\Models\Seeker\SeekerCV;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\{Storage, Log, Http};
use Smalot\PdfParser\Parser;
use PhpOffice\PhpWord\IOFactory;

class SeekerCVController extends Controller
{
    use ApiResponse;

    // ─────────────────────────────────────────────────────────────────────
    // JSON fields sent from the blade as JSON strings via FormData.
    // We decode them before the FormRequest validates so Laravel sees
    // real arrays, not strings — otherwise array validation rules fail.
    // ─────────────────────────────────────────────────────────────────────
    private const JSON_FIELDS = [
        'skills',
        'languages',
        'work_experience',
        'education',
        'certifications',
        'projects',
        'job_preferences',
    ];

    /**
     * Decode JSON string fields into real arrays on the request.
     * Call this at the top of any action that receives FormData from the blade.
     */
    private function decodeJsonFields(Request $request): void
    {
        foreach (self::JSON_FIELDS as $field) {
            $value = $request->input($field);
            if (is_string($value) && $value !== '') {
                $decoded = json_decode($value, true);
                if (json_last_error() === JSON_ERROR_NONE) {
                    $request->merge([$field => $decoded]);
                } else {
                    Log::warning("[SeekerCVController] Failed to decode JSON field: {$field}", [
                        'raw' => substr($value, 0, 200),
                    ]);
                    // Merge empty so validation gets an empty array, not the raw string
                    $request->merge([$field => []]);
                }
            }
        }
    }

    // ─────────────────────────────────────────────────────────────────────
    // GET /api/v1/seeker/cv
    // ─────────────────────────────────────────────────────────────────────
    public function show(Request $request): JsonResponse
    {
        $cv = SeekerCV::where('user_id', $request->user()->id)->first();

        if (!$cv) {
            return $this->success(null, 'No CV found. Create one to get started.');
        }

        return $this->success($cv, 'CV retrieved successfully');
    }

    // ─────────────────────────────────────────────────────────────────────
    // POST /api/v1/seeker/cv   (create or update)
    // ─────────────────────────────────────────────────────────────────────
    public function store(SeekerCVRequest $request): JsonResponse
    {
        // $request->validated() now contains real arrays — prepareForValidation()
        // already decoded the JSON strings before Laravel ran the rules.
        $validated = $request->validated();

        $user = $request->user();
        $cv   = SeekerCV::where('user_id', $user->id)->first();

        Log::info('[SeekerCVController] store called', [
            'user_id'    => $user->id,
            'is_update'  => (bool) $cv,
            'has_file'   => $request->hasFile('cv_file'),
            'skills_cnt' => count($validated['skills'] ?? []),
            'exp_cnt'    => count($validated['work_experience'] ?? []),
        ]);

        // Auto-fill from user when creating fresh
        if (!$cv) {
            $validated['email']      = $validated['email']      ?? $user->email;
            $validated['first_name'] = $validated['first_name'] ?? ($user->first_name ?? '');
            $validated['last_name']  = $validated['last_name']  ?? ($user->last_name  ?? '');
        }
        $validated['user_id'] = $user->id;

        // Handle CV file upload
        if ($request->hasFile('cv_file')) {
            if ($cv?->cv_file_path) {
                Storage::disk('public')->delete($cv->cv_file_path);
            }
            $file = $request->file('cv_file');
            $validated['cv_file_path']     = $file->store('cvs/' . $user->id, 'public');
            $validated['cv_original_name'] = $file->getClientOriginalName();
        }

        // Handle remove CV
        if ($request->boolean('remove_cv') && $cv?->cv_file_path) {
            Storage::disk('public')->delete($cv->cv_file_path);
            $validated['cv_file_path']     = null;
            $validated['cv_original_name'] = null;
        }

        // Null-out empty arrays rather than storing []
        $jsonFields = ['skills','languages','certifications','education','work_experience','projects','job_preferences'];
        foreach ($jsonFields as $field) {
            if (isset($validated[$field]) && empty($validated[$field])) {
                $validated[$field] = null;
            }
        }

        if ($cv) {
            $cv->update($validated);
            app(\App\Services\JobRecommendationService::class)->clearCache($user->id);
            $message = 'CV updated successfully';
        } else {
            $cv = SeekerCV::create($validated);
            app(\App\Services\JobRecommendationService::class)->clearCache($user->id);
            $message = 'CV created successfully';
        }

        return $this->success($cv->fresh(), $message);
    }

    // ─────────────────────────────────────────────────────────────────────
    // DELETE /api/v1/seeker/cv
    // ─────────────────────────────────────────────────────────────────────
    public function destroy(Request $request): JsonResponse
    {
        $cv = SeekerCV::where('user_id', $request->user()->id)->first();

        if (!$cv) {
            return $this->error('No CV found to delete', 404);
        }

        if ($cv->cv_file_path) {
            Storage::disk('public')->delete($cv->cv_file_path);
        }

        $cv->delete();

        return $this->success(null, 'CV deleted successfully');
    }

    // ─────────────────────────────────────────────────────────────────────
    // POST /api/v1/seeker/cv/upload  (file-only upload)
    // ─────────────────────────────────────────────────────────────────────
    public function uploadFile(Request $request): JsonResponse
    {
        $request->validate([
            'cv_file' => ['required', 'file', 'mimes:pdf,doc,docx', 'max:5120'],
        ]);

        $user = $request->user();
        $cv   = SeekerCV::where('user_id', $user->id)->first();

        if ($cv?->cv_file_path) {
            Storage::disk('public')->delete($cv->cv_file_path);
        }

        $file = $request->file('cv_file');
        $path = $file->store('cvs/' . $user->id, 'public');

        $data = [
            'cv_file_path'     => $path,
            'cv_original_name' => $file->getClientOriginalName(),
        ];

        if ($cv) {
            $cv->update($data);
            app(\App\Services\JobRecommendationService::class)->clearCache($user->id);
        } else {
            $data['user_id']    = $user->id;
            $data['first_name'] = $user->first_name ?? '';
            $data['last_name']  = $user->last_name  ?? '';
            $data['email']      = $user->email;
            $cv = SeekerCV::create($data);
            app(\App\Services\JobRecommendationService::class)->clearCache($user->id);
        }

        return $this->success([
            'cv_url'           => $cv->cv_url,
            'cv_original_name' => $cv->cv_original_name,
        ], 'CV uploaded successfully');
    }

    // ═════════════════════════════════════════════════════════════════════
    // NEW: AI-Powered CV Parsing
    // ═════════════════════════════════════════════════════════════════════


    /**
     * POST /api/v1/seeker/cv/parse
     * Upload CV file, extract text, parse with AI (Cohere), and save
     */
    public function parseCV(Request $request): JsonResponse
    {
        $request->validate([
            'cv_file' => ['required', 'file', 'mimes:pdf,doc,docx', 'max:5120'],
        ]);

        $user = $request->user();
        $file = $request->file('cv_file');

        Log::info('[SeekerCVController] parseCV started', [
            'user_id' => $user->id,
            'filename' => $file->getClientOriginalName(),
            'size' => $file->getSize(),
        ]);

        // Step 1: Extract text from file
        $extractedText = $this->extractTextFromFile($file);

        if (empty($extractedText)) {
            Log::error('[SeekerCVController] Text extraction failed', ['user_id' => $user->id]);
            return $this->error('Could not extract text from CV. Please ensure the file is not corrupted.', 422);
        }

        Log::info('[SeekerCVController] Text extracted', [
            'user_id' => $user->id,
            'length' => strlen($extractedText),
        ]);

        // Step 2: Parse with Cohere AI (changed from parseWithGemini to parseWithCohere)
        $parsedData = $this->parseWithCohere($extractedText);

        if (empty($parsedData) || !is_array($parsedData)) {
            Log::error('[SeekerCVController] AI parsing failed', ['user_id' => $user->id]);
            return $this->error('AI could not parse the CV. Please try manual entry or check the file format.', 422);
        }

        Log::info('[SeekerCVController] AI parsing successful', [
            'user_id' => $user->id,
            'first_name' => $parsedData['first_name'] ?? null,
            'skills_count' => count($parsedData['skills'] ?? []),
        ]);

        // Step 3: Format and prepare data for database
        $formattedData = $this->formatParsedDataForDatabase($parsedData);
        $formattedData['user_id'] = $user->id;

        // Step 4: Save the CV data
        $cv = SeekerCV::updateOrCreate(
            ['user_id' => $user->id],
            $formattedData
        );

        // Step 5: Save the original uploaded file
        $path = $file->store('cvs/' . $user->id, 'public');
        $cv->update([
            'cv_file_path' => $path,
            'cv_original_name' => $file->getClientOriginalName(),
        ]);
        app(\App\Services\JobRecommendationService::class)->clearCache($user->id);
        $cv->refresh();

        Log::info('[SeekerCVController] parseCV completed successfully', [
            'user_id' => $user->id,
            'cv_id' => $cv->id,
        ]);

        return $this->success([
            'cv' => $cv,
            'parsed_data' => $formattedData,
        ], 'CV parsed and saved successfully! Your profile has been updated.');
    }

    /**
     * Extract text from PDF or Word document
     */
    private function extractTextFromFile($file): string
    {
        $extension = strtolower($file->getClientOriginalExtension());
        $content = '';

        try {
            if ($extension === 'pdf') {
                $parser = new Parser();
                $pdf = $parser->parseFile($file->getPathname());
                $content = $pdf->getText();
            } elseif (in_array($extension, ['doc', 'docx'])) {
                $phpWord = IOFactory::load($file->getPathname());
                foreach ($phpWord->getSections() as $section) {
                    foreach ($section->getElements() as $element) {
                        if (method_exists($element, 'getText')) {
                            $content .= $element->getText() . "\n";
                        }
                    }
                }
            }
        } catch (\Exception $e) {
            Log::error('[SeekerCVController] File extraction error: ' . $e->getMessage());
            return '';
        }

        // Clean up the text
        $content = preg_replace('/\s+/', ' ', $content);
        $content = trim($content);

        return $content;
    }

    /**
     * Parse CV content using Cohere AI
     */
    private function parseWithCohere(string $content): array
    {
        $apiKey = config('services.cohere.api_key');

        if (!$apiKey) {
            Log::error('[SeekerCVController] Cohere API key not configured');
            return [];
        }

        $prompt = $this->buildCoherePrompt($content);

        try {
            $response = Http::timeout(60)
                ->retry(2, 1000)
                ->withHeaders([
                    'Content-Type' => 'application/json',
                    'Authorization' => 'Bearer ' . $apiKey,
                ])
                ->post('https://api.cohere.ai/v2/chat', [
                    'model' => 'command-a-03-2025',
                    'messages' => [
                        [
                            'role' => 'user',
                            'content' => $prompt
                        ]
                    ],
                    'temperature' => 0.1,
                    'max_tokens' => 4096,
                ]);

            if (!$response->successful()) {
                Log::error('[SeekerCVController] Cohere API error', [
                    'status' => $response->status(),
                    'body' => $response->body(),
                ]);
                return [];
            }

            $data = $response->json();
            $text = $data['message']['content'][0]['text'] ?? '';

            if (empty($text)) {
                Log::error('[SeekerCVController] Cohere returned empty response');
                return [];
            }

            // Clean markdown fences
            $clean = preg_replace('/^```(?:json)?\s*/i', '', $text);
            $clean = preg_replace('/\s*```\s*$/i', '', $clean);
            $clean = trim($clean);

            $result = json_decode($clean, true);

            if (json_last_error() !== JSON_ERROR_NONE) {
                Log::error('[SeekerCVController] JSON decode error', [
                    'error' => json_last_error_msg(),
                    'clean_text' => substr($clean, 0, 500),
                ]);
                return [];
            }

            return is_array($result) ? $result : [];

        } catch (\Exception $e) {
            Log::error('[SeekerCVController] Cohere request exception: ' . $e->getMessage());
            return [];
        }
    }

    /**
     * Build the prompt for Cohere AI
     */
    private function buildCoherePrompt(string $content): string
    {
        return "You are a professional CV parser. Extract information from the following CV/Resume and return ONLY valid JSON.

        IMPORTANT RULES:
        1. Return ONLY valid JSON - no explanations, no markdown, no code blocks, no extra text
        2. Use null for missing values
        3. Dates must be in YYYY-MM-DD format (use first day of month if only month/year given)
        4. Remove any special characters from names
        5. Extract email addresses and phone numbers accurately

        Required JSON structure:
        {
            \"first_name\": \"string or null\",
            \"last_name\": \"string or null\",
            \"email\": \"string or null\",
            \"phone\": \"string or null\",
            \"professional_title\": \"string or null\",
            \"city\": \"string or null\",
            \"country\": \"string or null\",
            \"professional_summary\": \"string or null\",
            \"linkedin_url\": \"string or null\",
            \"github_url\": \"string or null\",
            \"portfolio_url\": \"string or null\",
            \"skills\": [\"skill1\", \"skill2\"],
            \"languages\": [
                {\"name\": \"English\", \"proficiency\": \"professional\"}
            ],
            \"work_experience\": [
                {
                    \"job_title\": \"string\",
                    \"company\": \"string\",
                    \"location\": \"string or null\",
                    \"employment_type\": \"full-time|part-time|contract|internship|freelance\",
                    \"start_date\": \"YYYY-MM-DD or null\",
                    \"end_date\": \"YYYY-MM-DD or null\",
                    \"current\": false,
                    \"description\": \"string or null\"
                }
            ],
            \"education\": [
                {
                    \"degree\": \"string\",
                    \"institution\": \"string\",
                    \"field_of_study\": \"string or null\",
                    \"start_date\": \"YYYY-MM-DD or null\",
                    \"end_date\": \"YYYY-MM-DD or null\",
                    \"current\": false,
                    \"grade\": \"string or null\",
                    \"description\": \"string or null\"
                }
            ],
            \"certifications\": [
                {
                    \"name\": \"string\",
                    \"issuer\": \"string or null\",
                    \"date\": \"YYYY-MM-DD or null\",
                    \"credential_id\": \"string or null\"
                }
            ],
            \"projects\": [
                {
                    \"name\": \"string\",
                    \"url\": \"string or null\",
                    \"description\": \"string or null\",
                    \"technologies\": [\"tech1\", \"tech2\"]
                }
            ],
            \"years_of_experience\": 0
        }

        CV Content:
        " . substr($content, 0, 15000);
    }

    /**
     * Parse CV using Cohere (main entry point)
     */
    private function parseCVWithAI(string $content): array
    {
        return $this->parseWithCohere($content);
    }

    /**
     * Format AI parsed data to match database schema
     */
    private function formatParsedDataForDatabase(array $parsed): array
    {
        return [
            'first_name' => $this->sanitizeString($parsed['first_name'] ?? ''),
            'last_name' => $this->sanitizeString($parsed['last_name'] ?? ''),
            'email' => $this->sanitizeString($parsed['email'] ?? ''),
            'phone' => $this->sanitizeString($parsed['phone'] ?? ''),
            'professional_title' => $this->sanitizeString($parsed['professional_title'] ?? ''),
            'city' => $this->sanitizeString($parsed['city'] ?? ''),
            'country' => $this->sanitizeString($parsed['country'] ?? ''),
            'professional_summary' => $this->sanitizeString($parsed['professional_summary'] ?? ''),
            'linkedin_url' => $this->sanitizeUrl($parsed['linkedin_url'] ?? ''),
            'github_url' => $this->sanitizeUrl($parsed['github_url'] ?? ''),
            'portfolio_url' => $this->sanitizeUrl($parsed['portfolio_url'] ?? ''),
            'skills' => $parsed['skills'] ?? [],
            'languages' => $this->sanitizeLanguages($parsed['languages'] ?? []),
            'work_experience' => $this->sanitizeWorkExperience($parsed['work_experience'] ?? []),
            'education' => $this->sanitizeEducation($parsed['education'] ?? []),
            'certifications' => $this->sanitizeCertifications($parsed['certifications'] ?? []),
            'projects' => $this->sanitizeProjects($parsed['projects'] ?? []),
            'years_of_experience' => intval($parsed['years_of_experience'] ?? 0),
        ];
    }

    /**
     * Sanitize string fields
     */
    private function sanitizeString(?string $value): string
    {
        if (empty($value)) return '';
        $value = strip_tags($value);
        $value = preg_replace('/[^\p{L}\p{N}\s\-\_\.@]/u', '', $value);
        return trim($value);
    }

    /**
     * Sanitize URL fields
     */
    private function sanitizeUrl(?string $url): string
    {
        if (empty($url)) return '';
        if (!filter_var($url, FILTER_VALIDATE_URL)) return '';
        return $url;
    }

    /**
     * Sanitize languages array
     */
    private function sanitizeLanguages(array $languages): array
    {
        $validProficiencies = ['basic', 'conversational', 'professional', 'native'];
        $sanitized = [];
        
        foreach ($languages as $lang) {
            if (empty($lang['name'])) continue;
            $sanitized[] = [
                'name' => $this->sanitizeString($lang['name']),
                'proficiency' => in_array($lang['proficiency'] ?? '', $validProficiencies) 
                    ? $lang['proficiency'] 
                    : 'basic',
            ];
        }
        
        return $sanitized;
    }

    /**
     * Sanitize work experience array
     */
    private function sanitizeWorkExperience(array $experiences): array
    {
        $validTypes = ['full-time', 'part-time', 'contract', 'internship', 'freelance'];
        $sanitized = [];
        
        foreach ($experiences as $exp) {
            if (empty($exp['job_title']) || empty($exp['company'])) continue;
            
            $sanitized[] = [
                'job_title' => $this->sanitizeString($exp['job_title']),
                'company' => $this->sanitizeString($exp['company']),
                'location' => $this->sanitizeString($exp['location'] ?? ''),
                'employment_type' => in_array($exp['employment_type'] ?? '', $validTypes) 
                    ? $exp['employment_type'] 
                    : null,
                'start_date' => $this->sanitizeDate($exp['start_date'] ?? null),
                'end_date' => ($exp['current'] ?? false) ? null : $this->sanitizeDate($exp['end_date'] ?? null),
                'current' => (bool)($exp['current'] ?? false),
                'description' => $this->sanitizeString($exp['description'] ?? ''),
            ];
        }
        
        return $sanitized;
    }

    /**
     * Sanitize education array
     */
    private function sanitizeEducation(array $educations): array
    {
        $sanitized = [];
        
        foreach ($educations as $edu) {
            if (empty($edu['degree']) || empty($edu['institution'])) continue;
            
            $sanitized[] = [
                'degree' => $this->sanitizeString($edu['degree']),
                'institution' => $this->sanitizeString($edu['institution']),
                'field_of_study' => $this->sanitizeString($edu['field_of_study'] ?? ''),
                'start_date' => $this->sanitizeDate($edu['start_date'] ?? null),
                'end_date' => ($edu['current'] ?? false) ? null : $this->sanitizeDate($edu['end_date'] ?? null),
                'current' => (bool)($edu['current'] ?? false),
                'grade' => $this->sanitizeString($edu['grade'] ?? ''),
                'description' => $this->sanitizeString($edu['description'] ?? ''),
            ];
        }
        
        return $sanitized;
    }

    /**
     * Sanitize certifications array
     */
    private function sanitizeCertifications(array $certifications): array
    {
        $sanitized = [];
        
        foreach ($certifications as $cert) {
            if (empty($cert['name'])) continue;
            
            $sanitized[] = [
                'name' => $this->sanitizeString($cert['name']),
                'issuer' => $this->sanitizeString($cert['issuer'] ?? ''),
                'date' => $this->sanitizeDate($cert['date'] ?? null),
                'credential_id' => $this->sanitizeString($cert['credential_id'] ?? ''),
            ];
        }
        
        return $sanitized;
    }

    /**
     * Sanitize projects array
     */
    private function sanitizeProjects(array $projects): array
    {
        $sanitized = [];
        
        foreach ($projects as $project) {
            if (empty($project['name'])) continue;
            
            $sanitized[] = [
                'name' => $this->sanitizeString($project['name']),
                'url' => $this->sanitizeUrl($project['url'] ?? ''),
                'description' => $this->sanitizeString($project['description'] ?? ''),
                'technologies' => $project['technologies'] ?? [],
            ];
        }
        
        return $sanitized;
    }

    /**
     * Sanitize date to YYYY-MM-DD format
     */
    private function sanitizeDate(?string $date): ?string
    {
        if (empty($date)) return null;
        
        $timestamp = strtotime($date);
        if ($timestamp === false) return null;
        
        return date('Y-m-d', $timestamp);
    }
}