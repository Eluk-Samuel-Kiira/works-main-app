<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('social_media_platforms', function (Blueprint $table) {
            $table->id();

            // Identity
            $table->string('name');                          // e.g. "Jobs Uganda WhatsApp Group"
            $table->string('slug')->unique();
            $table->string('platform');                      // facebook | twitter | linkedin | whatsapp | telegram | instagram | youtube | tiktok | other
            $table->string('url');                           // full link to the group/page/channel
            $table->string('icon')->nullable();              // optional custom icon path or class override
            $table->text('description')->nullable();

            // Stats
            $table->unsignedBigInteger('followers_count')->default(0);
            $table->string('handle')->nullable();            // @username or group name

            // Location — country-unique per platform
            $table->foreignId('location_id')
                  ->constrained('job_locations')
                  ->onDelete('cascade');

            // Unique: one platform type per country (location)
            // e.g. only one Facebook page per Uganda
            $table->unique(['platform', 'location_id'], 'unique_platform_per_location');

            // Flags
            $table->boolean('is_active')->default(true);
            $table->boolean('is_verified')->default(false);  // officially verified account
            $table->boolean('is_featured')->default(false);  // shown prominently on frontend
            $table->integer('sort_order')->default(0);

            // SEO
            $table->string('meta_title')->nullable();
            $table->string('meta_description')->nullable();

            // Audit
            $table->foreignId('created_by')
                  ->nullable()
                  ->constrained('users')
                  ->onDelete('set null');

            $table->timestamps();

            // Indexes
            $table->index('platform');
            $table->index('location_id');
            $table->index(['is_active', 'is_featured', 'sort_order']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('social_media_platforms');
    }
};