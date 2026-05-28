<?php
// database/migrations/2024_01_01_000001_create_cv_enhancements_table.php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('cv_enhancements', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            
            // Input tracking
            $table->string('original_filename')->nullable();
            $table->string('original_file_path')->nullable();
            $table->longText('extracted_text')->nullable();
            
            // Type and status
            $table->enum('type', ['review', 'rewrite'])->default('review');
            $table->enum('status', ['pending', 'processing', 'completed', 'failed'])->default('pending');
            
            // Review-specific fields
            $table->json('review_feedback')->nullable();      // Structured HR feedback
            $table->decimal('ats_score', 5, 2)->nullable();   // 0-100 score
            $table->json('keyword_gaps')->nullable();         // Missing keywords
            $table->json('improvement_areas')->nullable();    // Categorized issues
            $table->json('strengths')->nullable();            // CV strengths
            $table->json('recommended_actions')->nullable();  // Actionable steps
            
            // Rewrite-specific fields
            $table->longText('rewritten_cv_text')->nullable();
            $table->string('rewritten_cv_path')->nullable();   // PDF storage path
            
            // Delivery tracking
            $table->boolean('email_sent')->default(false);
            $table->timestamp('email_sent_at')->nullable();
            
            // AI metadata
            $table->string('ai_model')->nullable();
            $table->integer('tokens_used')->nullable();
            $table->integer('processing_ms')->nullable();      // Processing time in milliseconds
            $table->text('error_message')->nullable();
            
            $table->timestamps();
            
            $table->index(['user_id', 'type', 'status']);
            $table->index(['user_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('cv_enhancements');
    }
};