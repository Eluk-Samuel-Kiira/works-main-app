<?php

namespace App\Http\Controllers\Main;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\{ Http, Log };

class AiJobController extends Controller
{
    protected $apiKeys = [];

    public function __construct()
    {
        $this->apiKeys = [
            'openai' => config('services.openai.api_key'),
            'claude' => config('services.anthropic.api_key'),
            'gemini' => config('services.gemini.api_key'),
            'cohere' => config('services.cohere.api_key'),
        ];
    }

    /**
     * Extract job data from text/URL using AI
     */
    public function extractJobData(Request $request)
    {
        $request->validate([
            'model' => 'required|in:openai,claude,gemini,cohere,grok,mistral',
            'content' => 'required|string',
            'source_type' => 'required|in:text,url',
        ]);

        $model = $request->model;
        $content = $request->content;
        $apiKey = $this->apiKeys[$model] ?? null;

        if (!$apiKey && !in_array($model, ['grok', 'mistral'])) {
            return response()->json(['error' => "API key not configured for {$model}"], 400);
        }

        try {
            $prompt = $this->buildExtractionPrompt($content);
            $result = $this->callAiApi($model, $apiKey, $prompt);
            
            return response()->json([
                'success' => true,
                'data' => $result
            ]);
        } catch (\Exception $e) {
            Log::error('AI extraction failed', ['model' => $model, 'error' => $e->getMessage()]);
            return response()->json([
                'success' => false,
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Extract job data from image
     */
    public function extractFromImage(Request $request)
    {
        $request->validate([
            'model' => 'required|in:openai,claude,gemini',
            'image_base64' => 'required|string',
        ]);

        $model = $request->model;
        $imageBase64 = $request->image_base64;
        $apiKey = $this->apiKeys[$model] ?? null;

        if (!$apiKey) {
            return response()->json(['error' => "API key not configured for {$model}"], 400);
        }

        try {
            $prompt = $this->buildExtractionPrompt('Extract all job information visible in this image.');
            $result = $this->callAiApi($model, $apiKey, $prompt, $imageBase64);
            
            return response()->json([
                'success' => true,
                'data' => $result
            ]);
        } catch (\Exception $e) {
            Log::error('Image extraction failed', ['model' => $model, 'error' => $e->getMessage()]);
            return response()->json([
                'success' => false,
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Enhance a specific field
     */
    public function enhanceField(Request $request)
    {
        $request->validate([
            'model' => 'required|string',
            'field_name' => 'required|string',
            'content' => 'required|string',
            'instruction' => 'required|string',
        ]);

        $model = $request->model;
        $content = $request->content;
        $instruction = $request->instruction;
        $apiKey = $this->apiKeys[$model] ?? null;

        if (!$apiKey && !in_array($model, ['grok', 'mistral'])) {
            return response()->json(['error' => "API key not configured for {$model}"], 400);
        }

        try {
            $prompt = "{$instruction}. Return ONLY the improved HTML content using <p> and <ul><li> tags, no explanations, no markdown:\n\n{$content}";
            $result = $this->callAiApi($model, $apiKey, $prompt);
            
            $enhanced = is_array($result) ? json_encode($result) : $result;
            
            return response()->json([
                'success' => true,
                'enhanced' => $enhanced
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Generate full job post from title
     */
    public function generateFromTitle(Request $request)
    {
        $request->validate([
            'model' => 'required|string',
            'title' => 'required|string',
            'company' => 'nullable|string',
        ]);

        $model = $request->model;
        $title = $request->title;
        $company = $request->company;
        $apiKey = $this->apiKeys[$model] ?? null;

        if (!$apiKey && !in_array($model, ['grok', 'mistral'])) {
            return response()->json(['error' => "API key not configured for {$model}"], 400);
        }

        $prompt = "Generate a professional job posting for a \"{$title}\"" . ($company ? " at {$company}" : "") . " in Uganda.
            Return ONLY a JSON object with these fields:
            {
            \"job_description\": \"professional job description as HTML with <p> and <ul><li>\",
            \"responsibilities\": \"5-8 key responsibilities as HTML <ul><li> list\",
            \"qualifications\": \"required qualifications as HTML <ul><li> list\",
            \"skills\": \"comma-separated list of 8-12 relevant skills\",
            \"meta_description\": \"155-char SEO meta description\",
            \"keywords\": \"comma-separated SEO keywords\"
        }";

        try {
            $result = $this->callAiApi($model, $apiKey, $prompt);
            
            return response()->json([
                'success' => true,
                'data' => $result
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => $e->getMessage()
            ], 500);
        }
    }

    private function buildExtractionPrompt($content)
    {
        return "You are a job data extraction specialist. Extract all job posting information from the following content and return ONLY a valid JSON object with no explanation, no markdown, no code blocks.

            Extract these fields (use null for missing fields):
            {
            \"job_title\": \"exact job title\",
            \"company_name\": \"company name\",
            \"job_description\": \"full job description as HTML with <p> and <ul><li> tags\",
            \"responsibilities\": \"key responsibilities as HTML <ul><li> list\",
            \"qualifications\": \"required qualifications as HTML <ul><li> list\",
            \"skills\": \"comma-separated list of required skills\",
            \"application_procedure\": \"how to apply - email, URL, or instructions\",
            \"email\": \"contact email if mentioned\",
            \"telephone\": \"phone number if mentioned\",
            \"deadline\": \"application deadline in YYYY-MM-DD format or null\",
            \"duty_station\": \"duty station or office location\",
            \"location_type\": \"remote|hybrid|on-site\",
            \"employment_type\": \"full-time|part-time|contract|internship|volunteer|temporary\",
            \"salary_amount\": numeric salary amount or null,
            \"currency\": \"UGX|USD|EUR|KES or null\",
            \"payment_period\": \"monthly|yearly|weekly|daily|hourly or null\",
            \"meta_description\": \"150-character SEO description of this job\",
            \"keywords\": \"comma-separated SEO keywords for this job\",
            \"experience_level_name\": \"entry|junior|mid|senior|executive or description\",
            \"education_level_name\": \"degree level required e.g. Bachelor's Degree\",
            \"industry_name\": \"industry sector name\",
            \"category_name\": \"job category name\",
            \"is_urgent\": false,
            \"work_hours\": \"work hours if mentioned or null\"
            }

            Rules:
            - Return ONLY the JSON object, nothing else
            - For HTML fields (description, responsibilities, qualifications), use proper HTML formatting
            - Convert bullet points to <ul><li> lists
            - Preserve paragraph breaks as <p> tags
            - If salary is a range like \"500,000 - 800,000\", use the midpoint for salary_amount
            - deadline must be in YYYY-MM-DD format

            Content to extract from:
            ---
            {$content}
            ---";
    }

    private function callAiApi($model, $apiKey, $prompt, $imageBase64 = null)
    {
        $endpoints = [
            'openai' => 'https://api.openai.com/v1/chat/completions',
            'claude' => 'https://api.anthropic.com/v1/messages',
            'gemini' => 'https://generativelanguage.googleapis.com/v1beta/models/gemini-1.5-pro:generateContent',
            'cohere' => 'https://api.cohere.ai/v2/chat',
        ];

        if (!isset($endpoints[$model])) {
            throw new \Exception("Unknown model: {$model}");
        }

        $endpoint = $endpoints[$model];
        $headers = [];
        $body = [];

        switch ($model) {
            case 'openai':
                $headers = [
                    'Content-Type' => 'application/json',
                    'Authorization' => "Bearer {$apiKey}",
                ];
                $content = $imageBase64 
                    ? [
                        ['type' => 'image_url', 'image_url' => ['url' => "data:image/jpeg;base64,{$imageBase64}"]],
                        ['type' => 'text', 'text' => $prompt]
                    ]
                    : $prompt;
                $body = [
                    'model' => 'gpt-4o',
                    'max_tokens' => 4096,
                    'messages' => [['role' => 'user', 'content' => $content]]
                ];
                break;

            case 'claude':
                $headers = [
                    'Content-Type' => 'application/json',
                    'x-api-key' => $apiKey,
                    'anthropic-version' => '2023-06-01',
                ];
                $messages = $imageBase64
                    ? [
                        ['role' => 'user', 'content' => [
                            ['type' => 'image', 'source' => ['type' => 'base64', 'media_type' => 'image/jpeg', 'data' => $imageBase64]],
                            ['type' => 'text', 'text' => $prompt]
                        ]]
                    ]
                    : [['role' => 'user', 'content' => $prompt]];
                $body = [
                    'model' => 'claude-3-5-sonnet-20241022',
                    'max_tokens' => 4096,
                    'messages' => $messages
                ];
                break;

            case 'gemini':
                $endpoint .= "?key={$apiKey}";
                $headers = ['Content-Type' => 'application/json'];
                $parts = $imageBase64
                    ? [
                        ['inline_data' => ['mime_type' => 'image/jpeg', 'data' => $imageBase64]],
                        ['text' => $prompt]
                    ]
                    : [['text' => $prompt]];
                $body = [
                    'contents' => [['parts' => $parts]],
                    'generationConfig' => ['maxOutputTokens' => 4096]
                ];
                break;

            case 'cohere':
                $headers = [
                    'Content-Type' => 'application/json',
                    'Authorization' => "Bearer {$apiKey}",
                ];
                $body = [
                    'model' => 'command-a-03-2025',
                    'max_tokens' => 4096,
                    'messages' => [['role' => 'user', 'content' => $prompt]]
                ];
                break;
        }

        // $response = Http::withHeaders($headers)->post($endpoint, $body);
        $response = Http::timeout(60)
        ->retry(2, 100) // Retry twice with 100ms delay
        ->withHeaders($headers)
        ->post($endpoint, $body);
        
        if (!$response->successful()) {
            throw new \Exception("API error: " . $response->body());
        }

        $data = $response->json();

        // Extract text based on model
        $text = '';
        switch ($model) {
            case 'openai':
                $text = $data['choices'][0]['message']['content'] ?? '';
                break;
            case 'claude':
                $text = $data['content'][0]['text'] ?? '';
                break;
            case 'gemini':
                $text = $data['candidates'][0]['content']['parts'][0]['text'] ?? '';
                break;
            case 'cohere':
                $text = $data['message']['content'][0]['text'] ?? '';
                break;
        }

        // Clean and parse JSON
        $clean = preg_replace('/```json\n?|```\n?/', '', $text);
        return json_decode(trim($clean), true);
    }

}
