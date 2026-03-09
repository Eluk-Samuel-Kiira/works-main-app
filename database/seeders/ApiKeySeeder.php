<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\System\ApiKey;
use App\Models\Auth\User;

class ApiKeySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $admin = User::where('email', 'admin@stardena.com')->first();

        $apiKeys = [
            // OpenAI Keys
            [
                'name' => 'OpenAI GPT-4 Production',
                'service' => 'openai',
                'provider' => 'OpenAI',
                'key' => env('OPENAI_API_KEY', 'sk-placeholder'),
                'version' => 'v1',
                'config' => [
                    'models' => ['gpt-4-turbo-preview', 'gpt-4', 'gpt-3.5-turbo'],
                    'max_tokens' => 4096,
                    'temperature' => 0.7,
                    'capabilities' => ['chat', 'completions', 'embeddings', 'image_generation'],
                ],
                'rate_limits' => [
                    'max_per_minute' => 60,
                    'max_per_day' => 10000,
                    'tokens_per_minute' => 90000,
                ],
                'usage_quota' => [
                    'monthly_limit_usd' => 1000,
                    'current_month_usage' => 0,
                ],
                'is_default' => true,
                'is_active' => true,
                'environment' => 'production',
                'notes' => 'Main OpenAI API key with GPT-4 access for production use',
                'created_by' => $admin?->id,
            ],
            [
                'name' => 'OpenAI Development',
                'service' => 'openai',
                'provider' => 'OpenAI',
                'key' => env('OPENAI_API_KEY_DEV', 'sk-placeholder-dev'),
                'version' => 'v1',
                'config' => [
                    'models' => ['gpt-3.5-turbo'],
                    'max_tokens' => 2048,
                    'temperature' => 0.8,
                    'capabilities' => ['chat', 'completions'],
                ],
                'rate_limits' => [
                    'max_per_minute' => 20,
                    'max_per_day' => 200,
                    'tokens_per_minute' => 30000,
                ],
                'is_default' => false,
                'is_active' => true,
                'environment' => 'development',
                'notes' => 'OpenAI key with reduced limits for development/testing',
                'created_by' => $admin?->id,
            ],

            // Anthropic Claude Keys
            [
                'name' => 'Claude 3 Production',
                'service' => 'anthropic',
                'provider' => 'Anthropic',
                'key' => env('ANTHROPIC_API_KEY', 'sk-ant-placeholder'),
                'version' => '2023-06-01',
                'config' => [
                    'models' => ['claude-3-opus-20240229', 'claude-3-sonnet-20240229', 'claude-3-haiku-20240307'],
                    'max_tokens' => 4096,
                    'temperature' => 0.7,
                    'capabilities' => ['chat', 'completions', 'analysis'],
                ],
                'rate_limits' => [
                    'max_per_minute' => 50,
                    'max_per_day' => 5000,
                    'tokens_per_minute' => 80000,
                ],
                'usage_quota' => [
                    'monthly_limit_usd' => 500,
                    'current_month_usage' => 0,
                ],
                'is_default' => true,
                'is_active' => true,
                'environment' => 'production',
                'notes' => 'Claude 3 AI for advanced content generation and analysis',
                'created_by' => $admin?->id,
            ],
            [
                'name' => 'Claude Development',
                'service' => 'anthropic',
                'provider' => 'Anthropic',
                'key' => env('ANTHROPIC_API_KEY_DEV', 'sk-ant-placeholder-dev'),
                'version' => '2023-06-01',
                'config' => [
                    'models' => ['claude-3-haiku-20240307'],
                    'max_tokens' => 2048,
                    'temperature' => 0.8,
                    'capabilities' => ['chat'],
                ],
                'rate_limits' => [
                    'max_per_minute' => 10,
                    'max_per_day' => 100,
                ],
                'is_default' => false,
                'is_active' => true,
                'environment' => 'development',
                'notes' => 'Claude key for development/testing',
                'created_by' => $admin?->id,
            ],

            // Google Gemini Keys
            [
                'name' => 'Gemini Pro Production',
                'service' => 'google',
                'provider' => 'Google AI',
                'key' => env('GEMINI_API_KEY', 'AIza-placeholder'),
                'version' => 'v1beta',
                'config' => [
                    'models' => ['gemini-1.5-pro', 'gemini-1.0-pro'],
                    'capabilities' => ['chat', 'text_generation', 'vision', 'embedding'],
                    'temperature' => 0.7,
                    'max_output_tokens' => 2048,
                ],
                'rate_limits' => [
                    'max_per_minute' => 60,
                    'max_per_day' => 10000,
                    'characters_per_minute' => 100000,
                ],
                'usage_quota' => [
                    'monthly_limit_requests' => 10000,
                ],
                'is_default' => true,
                'is_active' => true,
                'environment' => 'production',
                'notes' => 'Google Gemini Pro for multimodal AI tasks',
                'created_by' => $admin?->id,
            ],
            [
                'name' => 'Gemini Flash Production',
                'service' => 'google',
                'provider' => 'Google AI',
                'key' => env('GEMINI_API_KEY', 'AIza-placeholder'),
                'version' => 'v1beta',
                'config' => [
                    'models' => ['gemini-1.5-flash'],
                    'capabilities' => ['chat', 'text_generation'],
                    'temperature' => 0.8,
                ],
                'rate_limits' => [
                    'max_per_minute' => 100,
                    'max_per_day' => 20000,
                ],
                'is_default' => false,
                'is_active' => true,
                'environment' => 'production',
                'notes' => 'Gemini Flash for faster, lighter tasks',
                'created_by' => $admin?->id,
            ],

            // Cohere Keys
            [
                'name' => 'Cohere Command Production',
                'service' => 'cohere',
                'provider' => 'Cohere',
                'key' => env('COHERE_API_KEY', 'co-placeholder'),
                'version' => '2022-12-06',
                'config' => [
                    'models' => ['command', 'command-light', 'embed-english-v3.0'],
                    'capabilities' => ['generate', 'embed', 'classify'],
                    'temperature' => 0.7,
                ],
                'rate_limits' => [
                    'max_per_minute' => 100,
                    'max_per_day' => 10000,
                ],
                'usage_quota' => [
                    'monthly_limit_credits' => 1000,
                ],
                'is_default' => true,
                'is_active' => true,
                'environment' => 'production',
                'notes' => 'Cohere Command for text generation and embeddings',
                'created_by' => $admin?->id,
            ],
            [
                'name' => 'Cohere Embed Production',
                'service' => 'cohere',
                'provider' => 'Cohere',
                'key' => env('COHERE_API_KEY', 'co-placeholder'),
                'version' => '2022-12-06',
                'config' => [
                    'models' => ['embed-english-v3.0', 'embed-multilingual-v3.0'],
                    'capabilities' => ['embed'],
                ],
                'rate_limits' => [
                    'max_per_minute' => 200,
                    'max_per_day' => 50000,
                ],
                'is_default' => false,
                'is_active' => true,
                'environment' => 'production',
                'notes' => 'Cohere Embed for vector embeddings',
                'created_by' => $admin?->id,
            ],

            // Additional AI Services
            [
                'name' => 'Hugging Face Inference',
                'service' => 'huggingface',
                'provider' => 'Hugging Face',
                'key' => env('HUGGINGFACE_API_KEY', 'hf-placeholder'),
                'config' => [
                    'models' => ['mistralai/Mistral-7B-Instruct-v0.1', 'meta-llama/Llama-2-7b-chat-hf'],
                    'capabilities' => ['inference', 'embeddings'],
                ],
                'rate_limits' => [
                    'max_per_minute' => 30,
                ],
                'is_default' => true,
                'is_active' => true,
                'environment' => 'production',
                'notes' => 'Hugging Face Inference API for open source models',
                'created_by' => $admin?->id,
            ],
            [
                'name' => 'DeepL Translate',
                'service' => 'deepl',
                'provider' => 'DeepL',
                'key' => env('DEEPL_API_KEY', 'deepl-placeholder'),
                'config' => [
                    'capabilities' => ['translate'],
                    'formality' => 'default',
                ],
                'rate_limits' => [
                    'max_characters_per_month' => 500000,
                ],
                'is_default' => true,
                'is_active' => true,
                'environment' => 'production',
                'notes' => 'DeepL for high-quality translation of job posts',
                'created_by' => $admin?->id,
            ],

            // Azure OpenAI (if using)
            [
                'name' => 'Azure OpenAI Production',
                'service' => 'azure_openai',
                'provider' => 'Microsoft Azure',
                'key' => env('AZURE_OPENAI_KEY', 'azure-placeholder'),
                'endpoint' => env('AZURE_OPENAI_ENDPOINT', 'https://your-resource.openai.azure.com'),
                'version' => '2023-12-01-preview',
                'config' => [
                    'deployments' => [
                        'gpt-4' => 'gpt-4-deployment',
                        'gpt-35-turbo' => 'gpt-35-turbo-deployment',
                    ],
                    'capabilities' => ['chat', 'completions'],
                ],
                'rate_limits' => [
                    'tokens_per_minute' => 80000,
                ],
                'is_default' => false,
                'is_active' => true,
                'environment' => 'production',
                'notes' => 'Azure OpenAI service for enterprise workloads',
                'created_by' => $admin?->id,
            ],

            // Groq (fast inference)
            [
                'name' => 'Groq Production',
                'service' => 'groq',
                'provider' => 'Groq',
                'key' => env('GROQ_API_KEY', 'gsk-placeholder'),
                'config' => [
                    'models' => ['mixtral-8x7b-32768', 'llama2-70b-4096'],
                    'capabilities' => ['chat'],
                ],
                'rate_limits' => [
                    'max_per_minute' => 30,
                    'tokens_per_minute' => 5000,
                ],
                'is_default' => true,
                'is_active' => true,
                'environment' => 'production',
                'notes' => 'Groq for ultra-fast LLM inference',
                'created_by' => $admin?->id,
            ],

            // Perplexity API
            [
                'name' => 'Perplexity API',
                'service' => 'perplexity',
                'provider' => 'Perplexity AI',
                'key' => env('PERPLEXITY_API_KEY', 'pplx-placeholder'),
                'config' => [
                    'models' => ['pplx-7b-online', 'pplx-70b-online'],
                    'capabilities' => ['chat', 'online_search'],
                ],
                'rate_limits' => [
                    'max_per_minute' => 10,
                ],
                'is_default' => true,
                'is_active' => true,
                'environment' => 'production',
                'notes' => 'Perplexity API for online search augmented generation',
                'created_by' => $admin?->id,
            ],

            // Together AI
            [
                'name' => 'Together AI',
                'service' => 'together',
                'provider' => 'Together AI',
                'key' => env('TOGETHER_API_KEY', 'tog-placeholder'),
                'config' => [
                    'models' => ['mistralai/Mixtral-8x7B-Instruct-v0.1', 'meta-llama/Llama-3-70b-chat-hf'],
                    'capabilities' => ['chat', 'completions'],
                ],
                'rate_limits' => [
                    'max_per_minute' => 30,
                ],
                'is_default' => true,
                'is_active' => true,
                'environment' => 'production',
                'notes' => 'Together AI for open source model inference',
                'created_by' => $admin?->id,
            ],

            // Replicate
            [
                'name' => 'Replicate',
                'service' => 'replicate',
                'provider' => 'Replicate',
                'key' => env('REPLICATE_API_KEY', 'r8-placeholder'),
                'config' => [
                    'capabilities' => ['text_generation', 'image_generation'],
                ],
                'rate_limits' => [
                    'max_per_minute' => 20,
                ],
                'is_default' => true,
                'is_active' => true,
                'environment' => 'production',
                'notes' => 'Replicate for running AI models',
                'created_by' => $admin?->id,
            ],

            // Development/Testing Keys
            [
                'name' => 'Gemini Development',
                'service' => 'google',
                'provider' => 'Google AI',
                'key' => env('GEMINI_API_KEY_DEV', 'AIza-placeholder-dev'),
                'version' => 'v1beta',
                'config' => [
                    'models' => ['gemini-1.0-pro'],
                    'capabilities' => ['chat'],
                ],
                'rate_limits' => [
                    'max_per_minute' => 10,
                    'max_per_day' => 100,
                ],
                'environment' => 'development',
                'notes' => 'Gemini key for development/testing',
                'created_by' => $admin?->id,
            ],
            [
                'name' => 'Cohere Development',
                'service' => 'cohere',
                'provider' => 'Cohere',
                'key' => env('COHERE_API_KEY_DEV', 'co-placeholder-dev'),
                'config' => [
                    'models' => ['command-light'],
                    'capabilities' => ['generate'],
                ],
                'rate_limits' => [
                    'max_per_minute' => 10,
                ],
                'environment' => 'development',
                'notes' => 'Cohere key for development/testing',
                'created_by' => $admin?->id,
            ],
        ];

        foreach ($apiKeys as $keyData) {
            ApiKey::create($keyData);
        }

        $this->command->info('====================================');
        $this->command->info('AI API KEY SEEDER COMPLETED SUCCESSFULLY!');
        $this->command->info('====================================');
        $this->command->info('Total AI API keys created: ' . count($apiKeys));
        
        // Count by provider
        $counts = [
            'OpenAI' => count(array_filter($apiKeys, fn($k) => $k['service'] === 'openai')),
            'Anthropic' => count(array_filter($apiKeys, fn($k) => $k['service'] === 'anthropic')),
            'Google' => count(array_filter($apiKeys, fn($k) => $k['service'] === 'google')),
            'Cohere' => count(array_filter($apiKeys, fn($k) => $k['service'] === 'cohere')),
            'Others' => count(array_filter($apiKeys, fn($k) => !in_array($k['service'], ['openai', 'anthropic', 'google', 'cohere']))),
        ];
        
        $this->command->info('------------------------------------');
        $this->command->info('Breakdown by provider:');
        foreach ($counts as $provider => $count) {
            $this->command->info("- {$provider}: {$count}");
        }
        $this->command->info('====================================');
    }
}