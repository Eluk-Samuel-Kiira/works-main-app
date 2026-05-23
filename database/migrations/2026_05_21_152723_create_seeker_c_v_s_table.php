<?php
// database/migrations/2025_01_xx_000001_create_seeker_cvs_table.php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('seeker_cvs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            
            // Personal Information
            $table->string('first_name');
            $table->string('last_name');
            $table->string('email');
            $table->string('phone')->nullable();
            $table->string('address')->nullable();
            $table->string('city')->nullable();
            $table->string('country')->nullable();
            $table->string('postal_code')->nullable();
            $table->date('date_of_birth')->nullable();
            $table->string('nationality')->nullable();
            
            // Professional Summary
            $table->text('professional_summary')->nullable();
            $table->string('professional_title')->nullable();
            $table->integer('years_of_experience')->default(0);
            
            // Social Links
            $table->string('linkedin_url')->nullable();
            $table->string('github_url')->nullable();
            $table->string('portfolio_url')->nullable();
            
            // Skills (JSON)
            $table->json('skills')->nullable();
            $table->json('languages')->nullable();
            
            // Certifications (JSON)
            $table->json('certifications')->nullable();
            
            // Education (JSON)
            $table->json('education')->nullable();
            
            // Work Experience (JSON)
            $table->json('work_experience')->nullable();
            
            // Projects (JSON)
            $table->json('projects')->nullable();
            
            // CV File
            $table->string('cv_file_path')->nullable();
            $table->string('cv_original_name')->nullable();
            
            // Preferences
            $table->json('job_preferences')->nullable();
            $table->boolean('is_public')->default(true);
            $table->boolean('is_active')->default(true);
            
            $table->timestamps();
            
            // Indexes
            $table->index(['user_id']);
            $table->index(['first_name', 'last_name']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('seeker_cvs');
    }
};