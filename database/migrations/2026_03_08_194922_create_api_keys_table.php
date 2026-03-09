<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('api_keys', function (Blueprint $table) {
            $table->id();
            $table->string('name'); // e.g., 'OpenAI', 'Claude', 'Stripe', etc.
            $table->string('service'); // e.g., 'openai', 'anthropic', 'stripe', 'google', etc.
            $table->string('provider'); // e.g., 'OpenAI', 'Anthropic', 'Stripe', etc.
            $table->string('key')->nullable(); // The actual API key (encrypted)
            $table->string('secret')->nullable(); // Secret key if applicable (encrypted)
            $table->string('endpoint')->nullable(); // Custom endpoint if needed
            $table->string('version')->nullable(); // API version
            $table->json('config')->nullable(); // Additional configuration
            $table->json('permissions')->nullable(); // What this key is allowed to do
            $table->json('rate_limits')->nullable(); // Rate limiting settings
            $table->json('usage_quota')->nullable(); // Usage quotas
            $table->unsignedInteger('usage_count')->default(0); // Current usage count
            $table->timestamp('last_used_at')->nullable();
            $table->timestamp('expires_at')->nullable();
            $table->boolean('is_active')->default(true);
            $table->boolean('is_default')->default(false);
            $table->string('environment')->default('production'); // production, staging, development
            $table->text('notes')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();
            
            // Indexes
            $table->index('service');
            $table->index('provider');
            $table->index('is_active');
            $table->index('environment');
            $table->index(['service', 'is_active', 'environment']);
            $table->index('expires_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('api_keys');
    }
};