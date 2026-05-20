<?php

namespace App\Http\Controllers\Main;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\{ Http, Log };
use Carbon\Carbon;

class AiJobController extends Controller
{
    protected $apiKeys = [];

    public function __construct()
    {
        $this->apiKeys = [
            'openai'   => config('services.openai.api_key'),
            'claude'   => config('services.anthropic.api_key'),
            'gemini'   => config('services.gemini.api_key'),
            'cohere'   => config('services.cohere.api_key'),
        ];
    }

    // =========================================================
    // EXTRACT JOB DATA (text or URL)
    // =========================================================
    public function extractJobData(Request $request)
    {
        $request->validate([
            'model'       => 'required|in:openai,claude,gemini,cohere,grok,mistral',
            'content'     => 'required|string',
            'source_type' => 'required|in:text,url',
        ]);

        $model  = $request->model;
        $apiKey = $this->apiKeys[$model] ?? null;

        if (!$apiKey && !in_array($model, ['grok', 'mistral'])) {
            return response()->json(['error' => "API key not configured for {$model}"], 400);
        }

        try {
            $prompt = $this->buildExtractionPrompt($request->content);
            $result = $this->callAiApi($model, $apiKey, $prompt);
            $result = $this->applySmartDefaults($result, $model, $apiKey);

            return response()->json(['success' => true, 'data' => $result]);
        } catch (\Exception $e) {
            Log::error('AI extraction failed', ['model' => $model, 'error' => $e->getMessage()]);
            return response()->json(['success' => false, 'error' => $e->getMessage()], 500);
        }
    }

    // =========================================================
    // EXTRACT FROM IMAGE
    // =========================================================
    public function extractFromImage(Request $request)
    {
        $request->validate([
            'model'        => 'required|in:openai,claude,gemini',
            'image_base64' => 'required|string',
        ]);

        $model  = $request->model;
        $apiKey = $this->apiKeys[$model] ?? null;

        if (!$apiKey) {
            return response()->json(['error' => "API key not configured for {$model}"], 400);
        }

        try {
            $prompt = $this->buildExtractionPrompt('Extract all job information visible in this image.');
            $result = $this->callAiApi($model, $apiKey, $prompt, $request->image_base64);
            $result = $this->applySmartDefaults($result, $model, $apiKey);

            return response()->json(['success' => true, 'data' => $result]);
        } catch (\Exception $e) {
            Log::error('Image extraction failed', ['model' => $model, 'error' => $e->getMessage()]);
            return response()->json(['success' => false, 'error' => $e->getMessage()], 500);
        }
    }

    // =========================================================
    // ENHANCE A FIELD
    // =========================================================
    public function enhanceField(Request $request)
    {
        $request->validate([
            'model'       => 'required|string',
            'field_name'  => 'required|string',
            'content'     => 'required|string',
            'instruction' => 'required|string',
        ]);

        $model  = $request->model;
        $apiKey = $this->apiKeys[$model] ?? null;

        if (!$apiKey && !in_array($model, ['grok', 'mistral'])) {
            return response()->json(['success' => false, 'error' => "API key not configured for {$model}"], 400);
        }

        $prompt = <<<PROMPT
You are an expert HR copywriter. Your task: {$request->instruction}

RULES:
- Return ONLY the improved content as clean HTML.
- Use <p> for paragraphs and <ul><li> for lists.
- Do NOT include explanations, markdown fences, or code blocks.
- Do NOT repeat the instructions back.
- Write professionally, clearly, and concisely.
- Keep it suitable for a job board in Uganda/East Africa context.

CURRENT CONTENT:
{$request->content}
PROMPT;

        try {
            $result   = $this->callAiApi($model, $apiKey, $prompt);
            $enhanced = $this->extractTextFromResult($result);
            $enhanced = preg_replace('/```html\n?|```\n?/', '', $enhanced);
            $enhanced = trim($enhanced);

            if (empty($enhanced)) {
                $enhanced = $request->content;
            }

            return response()->json(['success' => true, 'enhanced' => $enhanced]);

        } catch (\Exception $e) {
            Log::error('AI enhance field failed', [
                'model'      => $model,
                'field_name' => $request->field_name,
                'error'      => $e->getMessage(),
            ]);
            return response()->json(['success' => false, 'error' => $e->getMessage()], 500);
        }
    }

    // =========================================================
    // GENERATE FULL POST FROM TITLE
    // =========================================================
    public function generateFromTitle(Request $request)
    {
        $request->validate([
            'model'   => 'required|string',
            'title'   => 'required|string',
            'company' => 'nullable|string',
        ]);

        $model   = $request->model;
        $apiKey  = $this->apiKeys[$model] ?? null;
        $company = $request->company ? " at {$request->company}" : '';

        if (!$apiKey && !in_array($model, ['grok', 'mistral'])) {
            return response()->json(['error' => "API key not configured for {$model}"], 400);
        }

        $deadline = Carbon::now()->addWeeks(2)->format('Y-m-d');

        $prompt = <<<PROMPT
You are an expert HR professional and job board agent. Generate a complete, professional job posting for a "{$request->title}"{$company} in Uganda/East Africa.

Return ONLY a valid JSON object — no explanation, no markdown, no code blocks.

{
  "job_description": "3-4 paragraph description as HTML with <p> tags — include role overview, company culture, and why someone should apply",
  "responsibilities": "6-8 responsibilities as HTML <ul><li> list — be specific and action-oriented",
  "qualifications": "required and preferred qualifications as HTML <ul><li> list with two sections",
  "skills": "comma-separated list of 8-12 relevant skills",
  "meta_description": "155-character SEO meta description",
  "keywords": "comma-separated SEO keywords",
  "experience_level_name": "entry level|junior|mid level|senior|executive",
  "education_level_name": "Certificate|Diploma|Bachelor's Degree|Master's Degree",
  "employment_type": "full-time|part-time|contract|internship|volunteer|temporary",
  "location_type": "on-site|remote|hybrid",
  "deadline": "{$deadline}"
}
PROMPT;

        try {
            $result = $this->callAiApi($model, $apiKey, $prompt);
            $result = $this->applySmartDefaults($result, $model, $apiKey);

            return response()->json(['success' => true, 'data' => $result]);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'error' => $e->getMessage()], 500);
        }
    }

    // =========================================================
    // SMART DEFAULTS — applied after every extraction
    // Now passes model + apiKey so fallbacks can call the AI
    // =========================================================
    private function applySmartDefaults(array $data, string $model, ?string $apiKey): array
    {
        // 1. Employment type → default full-time
        if (empty($data['employment_type'])) {
            $data['employment_type'] = 'full-time';
        }

        // 2. Deadline → today + 2 weeks
        if (empty($data['deadline'])) {
            $data['deadline'] = Carbon::now()->addWeeks(2)->format('Y-m-d');
        }

        // 3. Experience level → entry level
        if (empty($data['experience_level_name'])) {
            $data['experience_level_name'] = 'entry level';
        }

        // 4. Education level → Certificate
        if (empty($data['education_level_name'])) {
            $data['education_level_name'] = 'Certificate';
        }

        // 5. Currency → UGX
        if (empty($data['currency'])) {
            $data['currency'] = 'UGX';
        }

        // 6. Location type → on-site
        if (empty($data['location_type'])) {
            $data['location_type'] = 'on-site';
        }

        // 7. Phone provided but no explicit WhatsApp mention → telephone call only
        if (!empty($data['telephone']) && empty($data['is_whatsapp_contact'])) {
            $data['is_telephone_call']   = true;
            $data['is_whatsapp_contact'] = false;
        }

        // 8. Job description missing → generate via AI using the actual job title
        $hasDesc = !empty($data['job_description']) &&
                   strip_tags($data['job_description']) !== '';

        if (!$hasDesc && !empty($data['job_title'])) {
            $data['job_description'] = $this->generateFallbackDescription(
                $data['job_title'],
                $data['company_name'] ?? null,
                $data['duty_station'] ?? null,
                $model,
                $apiKey
            );
        }

        // 9. Responsibilities missing → generate via AI using the actual job title
        $hasResp = !empty($data['responsibilities']) &&
                   strip_tags($data['responsibilities']) !== '';

        if (!$hasResp && !empty($data['job_title'])) {
            $data['responsibilities'] = $this->generateFallbackResponsibilities(
                $data['job_title'],
                $data['company_name'] ?? null,
                $model,
                $apiKey
            );
        }

        // 10. Qualifications missing → generate via AI
        $hasQual = !empty($data['qualifications']) &&
                   strip_tags($data['qualifications']) !== '';

        if (!$hasQual && !empty($data['job_title'])) {
            $data['qualifications'] = $this->generateFallbackQualifications(
                $data['job_title'],
                $model,
                $apiKey
            );
        }

        // 11. Skills missing → generate via AI
        if (empty($data['skills']) && !empty($data['job_title'])) {
            $data['skills'] = $this->generateFallbackSkills(
                $data['job_title'],
                $model,
                $apiKey
            );
        }

        // 12. Meta description missing → generate via AI
        if (empty($data['meta_description']) && !empty($data['job_title'])) {
            $data['meta_description'] = $this->generateFallbackMetaDescription(
                $data['job_title'],
                $data['company_name'] ?? null,
                $data['duty_station'] ?? null,
                $model,
                $apiKey
            );
        }

        // 13. Keywords missing → generate via AI
        if (empty($data['keywords']) && !empty($data['job_title'])) {
            $data['keywords'] = $this->generateFallbackKeywords(
                $data['job_title'],
                $data['industry_name'] ?? null,
                $model,
                $apiKey
            );
        }

        return $data;
    }

    // =========================================================
    // FALLBACK GENERATORS — all AI-driven, never static
    // Each one is unique per job title → no duplicate content
    // =========================================================

    /**
     * Generate a unique job description via AI.
     * Falls back to a minimal non-generic sentence only if AI fails.
     */
    private function generateFallbackDescription(
        string $title,
        ?string $company,
        ?string $location,
        string $model,
        ?string $apiKey
    ): string {
        $companyContext  = $company  ? " at {$company}"   : '';
        $locationContext = $location ? " based in {$location}" : ' in Uganda';

        $prompt = <<<PROMPT
You are an expert HR copywriter writing for a Uganda/East Africa job board.

Write a compelling, unique job description for a "{$title}"{$companyContext}{$locationContext}.

RULES:
- Return ONLY clean HTML — no explanations, no markdown, no code blocks.
- Use 3 <p> tags: (1) role overview, (2) what the candidate will do day-to-day, (3) why this is a great opportunity.
- Be specific to this job title. Do NOT use generic filler phrases like "play a key role" or "dynamic environment".
- Keep it between 150-250 words total.
- Write in second/third person, professional tone.
PROMPT;

        return $this->callFallbackAi($prompt, $model, $apiKey)
            ?? "<p>We are recruiting a <strong>{$title}</strong>{$companyContext}{$locationContext}. "
             . "If you have the right qualifications and experience, we encourage you to apply.</p>";
    }

    /**
     * Generate unique, role-specific responsibilities via AI.
     */
    private function generateFallbackResponsibilities(
        string $title,
        ?string $company,
        string $model,
        ?string $apiKey
    ): string {
        $companyContext = $company ? " at {$company}" : '';

        $prompt = <<<PROMPT
You are an expert HR copywriter writing for a Uganda/East Africa job board.

Write 7 specific, action-oriented key responsibilities for a "{$title}"{$companyContext} role.

RULES:
- Return ONLY a clean HTML <ul> with <li> items — nothing else.
- Each <li> must start with a strong action verb (e.g. Manage, Develop, Coordinate, Ensure, Prepare).
- Be specific to this job title — avoid vague generic bullets.
- Do NOT include markdown, code blocks, or any explanation.
PROMPT;

        return $this->callFallbackAi($prompt, $model, $apiKey)
            ?? "<ul><li>Perform all duties related to the <strong>{$title}</strong> role as assigned.</li></ul>";
    }

    /**
     * Generate unique, role-specific qualifications via AI.
     */
    private function generateFallbackQualifications(
        string $title,
        string $model,
        ?string $apiKey
    ): string {
        $prompt = <<<PROMPT
You are an expert HR copywriter writing for a Uganda/East Africa job board.

Write realistic qualifications for a "{$title}" role, split into two sections: Required and Preferred.

RULES:
- Return ONLY clean HTML.
- Use <p><strong>Required Qualifications</strong></p> then a <ul><li> list.
- Use <p><strong>Preferred Qualifications</strong></p> then a <ul><li> list.
- Be specific to this job title — reflect real-world requirements for this role in East Africa.
- Do NOT include markdown, code blocks, or any explanation.
PROMPT;

        return $this->callFallbackAi($prompt, $model, $apiKey)
            ?? "<p><strong>Required Qualifications</strong></p><ul><li>Relevant qualification or experience for the {$title} role.</li></ul>";
    }

    /**
     * Generate unique, role-specific skills as a comma-separated list via AI.
     */
    private function generateFallbackSkills(
        string $title,
        string $model,
        ?string $apiKey
    ): string {
        $prompt = <<<PROMPT
List exactly 10 relevant professional skills for a "{$title}" role in Uganda/East Africa.

RULES:
- Return ONLY a plain comma-separated list of skills — no HTML, no bullets, no explanation.
- Mix technical skills specific to the role AND relevant soft skills.
- Be specific to this job title, not generic.

Example format: Skill One, Skill Two, Skill Three
PROMPT;

        return $this->callFallbackAi($prompt, $model, $apiKey)
            ?? 'Communication, Teamwork, Problem Solving, Time Management, Report Writing, Attention to Detail';
    }

    /**
     * Generate a unique SEO meta description via AI (max 155 chars).
     */
    private function generateFallbackMetaDescription(
        string $title,
        ?string $company,
        ?string $location,
        string $model,
        ?string $apiKey
    ): string {
        $companyPart  = $company  ? " at {$company}"  : '';
        $locationPart = $location ? " in {$location}" : ' in Uganda';

        $prompt = <<<PROMPT
Write a single SEO meta description for a job posting.

Job: {$title}{$companyPart}{$locationPart}

RULES:
- Return ONLY the meta description text — no HTML, no quotes, no explanation.
- Maximum 155 characters.
- Include the job title and location naturally.
- Write in a way that encourages clicks from job seekers.
PROMPT;

        $result = $this->callFallbackAi($prompt, $model, $apiKey);

        if ($result) {
            // Strip any accidental HTML and trim to 155 chars
            $result = strip_tags($result);
            return mb_substr(trim($result), 0, 155);
        }

        return mb_substr("Apply for the {$title} position{$companyPart}{$locationPart} today.", 0, 155);
    }

    /**
     * Generate unique SEO keywords via AI.
     */
    private function generateFallbackKeywords(
        string $title,
        ?string $industry,
        string $model,
        ?string $apiKey
    ): string {
        $industryContext = $industry ? " in the {$industry} industry" : '';

        $prompt = <<<PROMPT
Generate SEO keywords for a "{$title}" job posting{$industryContext} in Uganda/East Africa.

RULES:
- Return ONLY a comma-separated list of 10-15 keywords — no HTML, no explanation.
- Include variations of the job title, relevant skills, and location-based terms (Uganda, Kampala, East Africa).
- Be specific to this role.
PROMPT;

        return $this->callFallbackAi($prompt, $model, $apiKey)
            ?? "{$title}, jobs in Uganda, {$title} Uganda, Kampala jobs";
    }

    // =========================================================
    // SHARED FALLBACK AI CALLER
    // Tries the primary model first, then falls through the
    // remaining configured models. Returns null only if all fail.
    // =========================================================
    private function callFallbackAi(string $prompt, string $preferredModel, ?string $preferredKey): ?string
    {
        // Build priority list: preferred model first, then others
        $order = array_unique(array_merge(
            [$preferredModel],
            array_keys(array_filter($this->apiKeys))
        ));

        foreach ($order as $model) {
            $apiKey = $this->apiKeys[$model] ?? $preferredKey;
            if (empty($apiKey)) continue;

            try {
                $result = $this->callAiApi($model, $apiKey, $prompt);
                $text   = $this->extractTextFromResult($result);
                $text   = trim(preg_replace('/```html\n?|```\n?/', '', $text));

                if (!empty($text)) {
                    return $text;
                }
            } catch (\Exception $e) {
                Log::warning("Fallback AI call failed [{$model}]", ['error' => $e->getMessage()]);
                // Try next model
            }
        }

        return null; // All models failed — caller provides minimal safe default
    }

    // =========================================================
    // BUILD EXTRACTION PROMPT
    // =========================================================
    private function buildExtractionPrompt(string $content): string
    {
        $twoWeeksAhead = Carbon::now()->addWeeks(2)->format('Y-m-d');

        return <<<PROMPT
You are an expert job-board agent. Your job is to extract ALL available information from the provided content and return a complete JSON object ready to populate a job posting form.

CRITICAL RULES:
1. Return ONLY a valid JSON object — no explanation, no markdown, no code blocks.
2. For every field you cannot find, apply the smart defaults listed below — NEVER return null for defaulted fields.
3. For HTML fields, use proper <p> and <ul><li> formatting — convert bullet points and line breaks to HTML.
4. If job_description is empty or vague, write 2-3 professional paragraphs specific to the extracted job title.
5. If responsibilities is empty, draft 6-8 realistic, action-oriented bullet points specific to the job title.
6. If skills is empty, infer 8-10 relevant skills from the job title and description.
7. Never use generic placeholder phrases — everything must reflect the actual job title extracted.

SMART DEFAULTS (apply when field is missing):
- employment_type: "full-time"
- deadline: "{$twoWeeksAhead}"
- experience_level_name: "entry level"
- education_level_name: "Certificate"
- currency: "UGX"
- location_type: "on-site"
- is_telephone_call: true (if telephone is present but WhatsApp is not explicitly mentioned)
- is_whatsapp_contact: false (unless WhatsApp is explicitly mentioned)

FIELDS TO EXTRACT:
{
  "job_title": "exact job title",
  "company_name": "company name",
  "job_description": "full job description as HTML — 2-4 paragraphs using <p> tags, specific to this role",
  "responsibilities": "key responsibilities as HTML <ul><li> list — minimum 5 items, specific to this role",
  "qualifications": "required qualifications as HTML <ul><li> list",
  "skills": "comma-separated list of required skills — minimum 6, specific to this role",
  "application_procedure": "how to apply — email, URL, or clear instructions",
  "email": "contact email if mentioned, else null",
  "telephone": "phone number if mentioned, else null",
  "deadline": "application deadline in YYYY-MM-DD format",
  "duty_station": "office or work location, else null",
  "location_type": "remote|hybrid|on-site",
  "employment_type": "full-time|part-time|contract|internship|volunteer|temporary",
  "salary_amount": "numeric salary amount or null",
  "currency": "UGX|USD|EUR|KES",
  "payment_period": "monthly|yearly|weekly|daily|hourly or null",
  "meta_description": "155-character SEO description — must mention job title and location",
  "keywords": "comma-separated SEO keywords — include job title variations and Uganda/East Africa terms",
  "experience_level_name": "entry level|junior|mid level|senior|executive",
  "education_level_name": "Certificate|Diploma|Bachelor's Degree|Master's Degree",
  "industry_name": "industry sector name",
  "category_name": "job category name",
  "is_urgent": false,
  "is_featured": false,
  "is_resume_required": true,
  "is_cover_letter_required": false,
  "is_academic_documents_required": false,
  "is_application_required": false,
  "is_whatsapp_contact": false,
  "is_telephone_call": false,
  "work_hours": "work schedule if mentioned, else null"
}

CONTENT TO EXTRACT FROM:
---
{$content}
---
PROMPT;
    }

    // =========================================================
    // CALL AI API
    // =========================================================
    private function callAiApi(string $model, ?string $apiKey, string $prompt, ?string $imageBase64 = null): array|string
    {
        $endpoints = [
            'openai'  => 'https://api.openai.com/v1/chat/completions',
            'claude'  => 'https://api.anthropic.com/v1/messages',
            'gemini'  => 'https://generativelanguage.googleapis.com/v1beta/models/gemini-1.5-pro:generateContent',
            'cohere'  => 'https://api.cohere.ai/v2/chat',
        ];

        if (!isset($endpoints[$model])) {
            throw new \Exception("Unknown model: {$model}");
        }

        $endpoint = $endpoints[$model];
        $headers  = [];
        $body     = [];

        switch ($model) {
            case 'openai':
                $headers = [
                    'Content-Type'  => 'application/json',
                    'Authorization' => "Bearer {$apiKey}",
                ];
                $content = $imageBase64
                    ? [
                        ['type' => 'image_url', 'image_url' => ['url' => "data:image/jpeg;base64,{$imageBase64}"]],
                        ['type' => 'text', 'text' => $prompt],
                    ]
                    : $prompt;
                $body = [
                    'model'      => 'gpt-4o',
                    'max_tokens' => 4096,
                    'messages'   => [['role' => 'user', 'content' => $content]],
                ];
                break;

            case 'claude':
                $headers = [
                    'Content-Type'      => 'application/json',
                    'x-api-key'         => $apiKey,
                    'anthropic-version' => '2023-06-01',
                ];
                $messages = $imageBase64
                    ? [[
                        'role'    => 'user',
                        'content' => [
                            ['type' => 'image', 'source' => ['type' => 'base64', 'media_type' => 'image/jpeg', 'data' => $imageBase64]],
                            ['type' => 'text', 'text' => $prompt],
                        ],
                    ]]
                    : [['role' => 'user', 'content' => $prompt]];
                $body = [
                    'model'      => 'claude-sonnet-4-20250514',
                    'max_tokens' => 4096,
                    'messages'   => $messages,
                ];
                break;

            case 'gemini':
                $endpoint .= "?key={$apiKey}";
                $headers   = ['Content-Type' => 'application/json'];
                $parts     = $imageBase64
                    ? [
                        ['inline_data' => ['mime_type' => 'image/jpeg', 'data' => $imageBase64]],
                        ['text' => $prompt],
                    ]
                    : [['text' => $prompt]];
                $body = [
                    'contents'         => [['parts' => $parts]],
                    'generationConfig' => ['maxOutputTokens' => 4096],
                ];
                break;

            case 'cohere':
                $headers = [
                    'Content-Type'  => 'application/json',
                    'Authorization' => "Bearer {$apiKey}",
                ];
                $body = [
                    'model'      => 'command-a-03-2025',
                    'max_tokens' => 4096,
                    'messages'   => [['role' => 'user', 'content' => $prompt]],
                ];
                break;
        }

        $response = Http::timeout(90)
            ->retry(2, 200)
            ->withHeaders($headers)
            ->post($endpoint, $body);

        if (!$response->successful()) {
            throw new \Exception("API error ({$model}): " . $response->body());
        }

        $data = $response->json();
        $text = '';

        switch ($model) {
            case 'openai': $text = $data['choices'][0]['message']['content'] ?? ''; break;
            case 'claude': $text = $data['content'][0]['text'] ?? '';              break;
            case 'gemini': $text = $data['candidates'][0]['content']['parts'][0]['text'] ?? ''; break;
            case 'cohere': $text = $data['message']['content'][0]['text'] ?? '';   break;
        }

        // If the prompt expected JSON, try to parse it
        $clean   = preg_replace('/```json\n?|```\n?/', '', $text);
        $decoded = json_decode(trim($clean), true);

        return $decoded ?? $text;
    }

    // =========================================================
    // HELPER: extract plain text from various result shapes
    // =========================================================
    private function extractTextFromResult(mixed $result): string
    {
        if (is_string($result)) return $result;

        if (is_array($result)) {
            foreach (['text', 'content', 'enhanced', 'job_description'] as $key) {
                if (isset($result[$key]) && is_string($result[$key])) {
                    return $result[$key];
                }
            }
            $first = reset($result);
            if (is_string($first)) return $first;
            return json_encode($result);
        }

        return (string) $result;
    }
}