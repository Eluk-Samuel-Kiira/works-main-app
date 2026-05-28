<?php
// database/migrations/2024_01_01_000002_create_cover_letters_table.php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('cover_letters', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('cv_enhancement_id')->nullable()->constrained('cv_enhancements')->nullOnDelete();
            
            // Job details
            $table->string('job_title');
            $table->text('job_description');
            $table->text('responsibilities')->nullable();
            $table->text('required_skills')->nullable();
            $table->string('company_name')->nullable();
            $table->string('hiring_manager')->nullable();
            
            // Match analysis
            $table->decimal('match_score', 5, 2)->nullable();
            $table->json('matched_skills')->nullable();
            $table->json('missing_skills')->nullable();
            
            // Output
            $table->enum('status', ['pending', 'processing', 'completed', 'failed'])->default('pending');
            $table->longText('generated_letter')->nullable();
            $table->string('letter_file_path')->nullable();      // PDF storage path
            
            // Delivery
            $table->boolean('email_sent')->default(false);
            $table->timestamp('email_sent_at')->nullable();
            
            // Metadata
            $table->string('ai_model')->nullable();
            $table->text('error_message')->nullable();
            
            $table->timestamps();
            
            $table->index(['user_id', 'status']);
            $table->index(['user_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('cover_letters');
    }
};