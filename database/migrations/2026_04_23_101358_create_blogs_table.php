<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('blogs', function (Blueprint $table) {
            $table->id();

            // ── Core content ─────────────────────────────────────────
            $table->string('title', 500);  // Increased length
            $table->string('slug', 500)->unique();  // Increased length
            $table->text('excerpt')->nullable();  // Changed from string to text
            $table->longText('content');
            $table->string('cover_image', 1000)->nullable();  // Increased length
            $table->string('cover_image_alt', 500)->nullable();
            $table->string('cover_image_caption', 500)->nullable();

            // ── Categorisation ────────────────────────────────────────
            $table->string('category', 100)->default('general');
            $table->json('tags')->nullable();
            $table->string('reading_time', 50)->nullable();

            // ── Author ────────────────────────────────────────────────
            $table->foreignId('author_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('author_name', 200)->nullable();
            $table->string('author_title', 300)->nullable();
            $table->string('author_avatar', 1000)->nullable();

            // ── Status & visibility ───────────────────────────────────
            $table->boolean('is_active')->default(true);
            $table->boolean('is_featured')->default(false);
            $table->boolean('is_published')->default(false);
            $table->timestamp('published_at')->nullable();

            // ── SEO Metadata (Increased lengths for all) ──────────────
            $table->text('meta_title')->nullable();  // Changed from string to text
            $table->text('meta_description')->nullable();  // Changed from string to text
            $table->text('keywords')->nullable();
            $table->string('canonical_url', 1000)->nullable();  // Increased length
            $table->string('og_image', 1000)->nullable();
            $table->text('og_title')->nullable();  // Changed from string to text
            $table->text('og_description')->nullable();  // Changed from string to text
            $table->enum('robots', ['index,follow', 'noindex,follow', 'noindex,nofollow'])
                  ->default('index,follow');

            // ── IndexNow / Search engine ping ─────────────────────────
            $table->boolean('is_pinged')->default(false);
            $table->timestamp('last_pinged_at')->nullable();

            // ── Google Indexing API ───────────────────────────────────
            $table->boolean('submitted_to_indexing')->default(false);
            $table->timestamp('indexing_submitted_at')->nullable();
            $table->string('indexing_status', 50)->nullable();
            $table->json('indexing_response')->nullable();
            $table->boolean('is_indexed')->default(false);

            // ── Performance ───────────────────────────────────────────
            $table->unsignedBigInteger('view_count')->default(0);
            $table->unsignedBigInteger('share_count')->default(0);
            $table->unsignedBigInteger('like_count')->default(0);
            $table->unsignedBigInteger('comment_count')->default(0);

            // ── Content quality ───────────────────────────────────────
            $table->unsignedTinyInteger('seo_score')->nullable();
            $table->unsignedTinyInteger('content_quality_score')->nullable();

            // ── Sort & scheduling ─────────────────────────────────────
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamp('featured_until')->nullable();

            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->softDeletes();
            $table->timestamps();

            // ── Indexes ───────────────────────────────────────────────
            $table->index(['is_active', 'is_published', 'published_at']);
            $table->index(['is_featured', 'published_at']);
            $table->index(['category', 'is_active']);
            $table->index('is_pinged');
            $table->index('submitted_to_indexing');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('blogs');
    }
};