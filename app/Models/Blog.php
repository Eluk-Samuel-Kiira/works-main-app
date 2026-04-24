<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

class Blog extends Model
{
    use SoftDeletes;

    protected $table = 'blogs';

    protected $fillable = [
        'title', 'slug', 'excerpt', 'content',
        'cover_image', 'cover_image_alt', 'cover_image_caption',
        'category', 'tags', 'reading_time',
        'author_id', 'author_name', 'author_title', 'author_avatar',
        'is_active', 'is_featured', 'is_published', 'published_at',
        'meta_title', 'meta_description', 'keywords',
        'canonical_url', 'og_image', 'og_title', 'og_description', 'robots',
        'is_pinged', 'last_pinged_at',
        'submitted_to_indexing', 'indexing_submitted_at',
        'indexing_status', 'indexing_response', 'is_indexed',
        'view_count', 'share_count', 'like_count', 'comment_count',
        'seo_score', 'content_quality_score',
        'sort_order', 'featured_until',
        'created_by', 'updated_by',
    ];

    protected $casts = [
        'tags'                  => 'array',
        'indexing_response'     => 'array',
        'is_active'             => 'boolean',
        'is_featured'           => 'boolean',
        'is_published'          => 'boolean',
        'is_pinged'             => 'boolean',
        'submitted_to_indexing' => 'boolean',
        'is_indexed'            => 'boolean',
        'published_at'          => 'datetime',
        'last_pinged_at'        => 'datetime',
        'indexing_submitted_at' => 'datetime',
        'featured_until'        => 'datetime',
    ];

    // ── Relationships ──────────────────────────────────────────────────────────
    public function author(): BelongsTo
    {
        return $this->belongsTo(\App\Models\Auth\User::class, 'author_id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(\App\Models\Auth\User::class, 'created_by');
    }

    // ── Accessors ──────────────────────────────────────────────────────────────
    public function getFormattedReadingTimeAttribute(): string
    {
        if ($this->reading_time) return $this->reading_time;
        $words = str_word_count(strip_tags($this->content ?? ''));
        $mins  = max(1, (int) ceil($words / 200));
        return "{$mins} min read";
    }

    public function getDisplayAuthorNameAttribute(): string
    {
        return $this->author?->name ?? $this->author_name ?? 'Stardena Works';
    }

    public function getIsExpiredFeaturedAttribute(): bool
    {
        return $this->featured_until && $this->featured_until->isPast();
    }

    public function getExcerptOrAutoAttribute(): string
    {
        if ($this->excerpt) return $this->excerpt;
        return Str::limit(strip_tags($this->content ?? ''), 160);
    }

    // ── Scopes ─────────────────────────────────────────────────────────────────
    public function scopePublished($query)
    {
        return $query->where('is_active', true)
                     ->where('is_published', true)
                     ->whereNotNull('published_at')
                     ->where('published_at', '<=', now());
    }

    public function scopeFeatured($query)
    {
        return $query->where('is_featured', true)
                     ->where(fn($q) => $q->whereNull('featured_until')
                                         ->orWhere('featured_until', '>=', now()));
    }

    // ── Slug boot ──────────────────────────────────────────────────────────────
    protected static function booted(): void
    {
        static::creating(function (self $blog) {
            if (empty($blog->slug)) {
                $blog->slug = static::generateUniqueSlug($blog->title);
            }
            if (empty($blog->reading_time)) {
                $words = str_word_count(strip_tags($blog->content ?? ''));
                $mins  = max(1, (int) ceil($words / 200));
                $blog->reading_time = "{$mins} min read";
            }
        });

        static::updating(function (self $blog) {
            if ($blog->isDirty('content')) {
                $words = str_word_count(strip_tags($blog->content ?? ''));
                $mins  = max(1, (int) ceil($words / 200));
                $blog->reading_time = "{$mins} min read";
            }
        });
    }

    public static function generateUniqueSlug(string $title): string
    {
        $base = Str::slug($title);
        $slug = $base;
        $i    = 1;
        while (static::withTrashed()->where('slug', $slug)->exists()) {
            $slug = "{$base}-{$i}";
            $i++;
        }
        return $slug;
    }
}