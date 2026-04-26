<?php

namespace App\Http\Resources\Blog;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class BlogResource extends JsonResource
{
    /**
     * Get full URL for an image path
     */
    private function getFullImageUrl(?string $path): ?string
    {
        if (empty($path)) {
            return null;
        }
        
        // If it's already a full URL, fix double storage and return
        if (filter_var($path, FILTER_VALIDATE_URL)) {
            // Fix double storage issue
            $path = str_replace('/storage/storage/', '/storage/', $path);
            return $path;
        }
        
        // Get the main app URL from config
        $mainAppUrl = rtrim(config('api.main_app.url', env('MAIN_APP_URL', 'http://127.0.0.1:8000')), '/');
        
        // Clean the path - remove leading slash and any 'storage/' prefix
        $path = ltrim($path, '/');
        $path = preg_replace('/^storage\//', '', $path);
        
        // Return full URL
        return $mainAppUrl . '/storage/' . $path;
    }

    public function toArray(Request $request): array
    {
        return [
            'id'                      => $this->id,
            'title'                   => $this->title,
            'slug'                    => $this->slug,
            'excerpt'                 => $this->excerpt_or_auto,
            'content'                 => $this->content,
            'cover_image'             => $this->getFullImageUrl($this->cover_image),
            'cover_image_alt'         => $this->cover_image_alt,
            'cover_image_caption'     => $this->cover_image_caption,
            'category'                => $this->category,
            'tags'                    => $this->tags ?? [],
            'reading_time'            => $this->formatted_reading_time,
            'author' => [
                'id'     => $this->author?->id ?? $this->author_id,
                'name'   => $this->display_author_name,
                'title'  => $this->author_title,
                'avatar' => $this->getFullImageUrl($this->author_avatar ?? $this->author?->profile_photo_url ?? null),
            ],
            'is_active'               => $this->is_active,
            'is_featured'             => $this->is_featured,
            'is_published'            => $this->is_published,
            'published_at'            => $this->published_at?->toIso8601String(),
            'meta_title'              => $this->meta_title ?? $this->title,
            'meta_description'        => $this->meta_description ?? $this->excerpt_or_auto,
            'keywords'                => $this->keywords,
            'canonical_url'           => $this->canonical_url,
            'og_image'                => $this->getFullImageUrl($this->og_image ?? $this->cover_image),
            'og_title'                => $this->og_title ?? $this->title,
            'og_description'          => $this->og_description ?? $this->excerpt_or_auto,
            'robots'                  => $this->robots,
            'is_pinged'               => $this->is_pinged,
            'last_pinged_at'          => $this->last_pinged_at?->toIso8601String(),
            'submitted_to_indexing'   => $this->submitted_to_indexing,
            'indexing_submitted_at'   => $this->indexing_submitted_at?->toIso8601String(),
            'indexing_status'         => $this->indexing_status,
            'is_indexed'              => $this->is_indexed,
            'view_count'              => $this->view_count,
            'share_count'             => $this->share_count,
            'like_count'              => $this->like_count,
            'comment_count'           => $this->comment_count,
            'seo_score'               => $this->seo_score,
            'sort_order'              => $this->sort_order,
            'created_at'              => $this->created_at?->toIso8601String(),
            'updated_at'              => $this->updated_at?->toIso8601String(),
        ];
    }
}