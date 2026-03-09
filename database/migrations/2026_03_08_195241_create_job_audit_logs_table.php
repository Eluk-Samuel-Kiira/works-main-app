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
        Schema::create('job_audit_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('job_post_id')->constrained()->onDelete('cascade');
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('action'); // created, updated, deleted, featured, urgent, verified, expired, etc.
            $table->string('event'); // create, update, delete, publish, unpublish, etc.
            $table->json('old_data')->nullable(); // Previous state
            $table->json('new_data')->nullable(); // New state
            $table->json('changes')->nullable(); // Specific changes
            $table->ipAddress('ip_address')->nullable();
            $table->text('user_agent')->nullable();
            $table->string('source')->default('web'); // web, api, cli, cron, etc.
            $table->text('notes')->nullable();
            $table->json('metadata')->nullable(); // Additional context
            $table->timestamps();
            
            // Indexes
            $table->index('action');
            $table->index('event');
            $table->index('source');
            $table->index('created_at');
            $table->index(['job_post_id', 'created_at']);
            $table->index(['user_id', 'created_at']);
            $table->index(['action', 'created_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('job_audit_logs');
    }
};