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
        Schema::create('notifications', function (Blueprint $table) {
            $table->id();
            
            // Notification type (e.g., 'missing_application_link', 'job_report', 'user_feedback')
            $table->string('type')->index();
            
            // Title and message
            $table->string('title');
            $table->text('message');
            
            // JSON data for additional information
            $table->json('data')->nullable();
            
            // Status tracking
            $table->string('status')->default('unread')->index(); // unread, read, resolved, archived
            $table->string('priority')->default('medium')->index(); // low, medium, high, urgent
            
            // Timestamps for read/resolved actions
            $table->timestamp('read_at')->nullable();
            $table->timestamp('resolved_at')->nullable();
            
            // User who reported/created (optional)
            $table->foreignId('user_id')->nullable()->constrained()->onDelete('set null');
            
            // Admin who resolved the notification
            $table->foreignId('resolved_by')->nullable()->constrained('users')->onDelete('set null');
            
            // For job-specific notifications
            $table->foreignId('job_id')->nullable()->constrained()->onDelete('cascade');
            
            // For company-specific notifications
            $table->foreignId('company_id')->nullable()->constrained()->onDelete('cascade');
            
            $table->timestamps();
            $table->softDeletes(); // Soft delete for archiving
            
            // Additional indexes for better performance
            $table->index(['type', 'status']);
            $table->index(['priority', 'status']);
            $table->index(['created_at', 'status']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('notifications');
    }
};