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
        Schema::create('api_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('api_key_id')->nullable()->constrained()->nullOnDelete();
            $table->string('service'); // e.g., 'openai', 'stripe', etc.
            $table->string('endpoint'); // API endpoint called
            $table->string('method'); // GET, POST, PUT, DELETE
            $table->json('request_data')->nullable(); // Data sent to API
            $table->json('response_data')->nullable(); // Response from API
            $table->integer('response_code')->nullable(); // HTTP response code
            $table->float('duration_ms')->nullable(); // Request duration in milliseconds
            $table->ipAddress('ip_address')->nullable();
            $table->text('user_agent')->nullable();
            $table->boolean('is_success')->default(true);
            $table->text('error_message')->nullable();
            $table->json('error_details')->nullable();
            $table->string('request_id')->nullable(); // External request ID if provided
            $table->json('metadata')->nullable(); // Additional metadata
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('job_post_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('company_id')->nullable()->constrained()->nullOnDelete();
            $table->timestamps();
            
            // Indexes for efficient querying
            $table->index('service');
            $table->index('endpoint');
            $table->index('response_code');
            $table->index('is_success');
            $table->index('created_at');
            $table->index(['service', 'created_at']);
            $table->index(['api_key_id', 'created_at']);
            $table->index('request_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('api_logs');
    }
};