<?php

namespace App\Http\Controllers\Api\Blog;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\Blog\BlogRequest;
use App\Http\Resources\Blog\BlogResource;
use App\Models\Blog;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\{ Auth, DB, Storage };

class BlogController extends Controller
{

    public function index(Request $request): JsonResponse
    {
        $query = Blog::with('author')
            ->when($request->search, fn($q, $s) =>
                $q->where('title', 'like', "%{$s}%")
                ->orWhere('excerpt', 'like', "%{$s}%")
                ->orWhere('category', 'like', "%{$s}%"))
            ->when($request->category, fn($q, $c) => $q->where('category', $c))
            ->when($request->filled('is_active'), fn($q) => $q->where('is_active', $request->boolean('is_active')))
            ->when($request->filled('is_published') && $request->is_published == 1 && !$request->has('published_future'), 
                fn($q) => $q->where('is_published', true)->where('published_at', '<=', now()))
            ->when($request->filled('is_published') && $request->is_published == 0, 
                fn($q) => $q->where('is_published', false))
            ->when($request->has('published_future'), 
                fn($q) => $q->where('is_published', true)->where('published_at', '>', now()))
            ->when($request->filled('is_featured'), fn($q) => $q->where('is_featured', $request->boolean('is_featured')))
            ->when($request->author_id, fn($q, $id) => $q->where('author_id', $id))
            ->when($request->tag, fn($q, $tag) =>
                $q->whereJsonContains('tags', $tag));

        $sortField = match($request->sort) {
            'oldest'    => ['published_at', 'asc'],
            'views'     => ['view_count', 'desc'],
            'featured'  => ['is_featured', 'desc'],
            default     => ['published_at', 'desc'],
        };

        $blogs = $query->orderBy($sortField[0], $sortField[1])
                    ->orderBy('id', 'desc')
                    ->paginate($request->per_page ?? 15);

        return response()->json([
            'data' => BlogResource::collection($blogs->items()),
            'meta' => [
                'current_page' => $blogs->currentPage(),
                'last_page'    => $blogs->lastPage(),
                'per_page'     => $blogs->perPage(),
                'total'        => $blogs->total(),
                'from'         => $blogs->firstItem(),
                'to'           => $blogs->lastItem(),
            ],
        ]);
    }

    // ── GET /api/v1/blogs/public ─────────────────────────────────────────────
    public function publicIndex(Request $request): JsonResponse
    {
        $query = Blog::with('author')
            ->published()
            ->when($request->category, fn($q, $c) => $q->where('category', $c))
            ->when($request->search, fn($q, $s) =>
                $q->where('title', 'like', "%{$s}%")
                  ->orWhere('excerpt', 'like', "%{$s}%"))
            ->when($request->tag, fn($q, $tag) =>
                $q->whereJsonContains('tags', $tag))
            ->when($request->filled('featured'), fn($q) => $q->featured());

        $blogs = $query->orderByDesc('published_at')
                       ->paginate($request->per_page ?? 12);

        return response()->json([
            'data' => BlogResource::collection($blogs->items()),
            'meta' => [
                'current_page' => $blogs->currentPage(),
                'last_page'    => $blogs->lastPage(),
                'per_page'     => $blogs->perPage(),
                'total'        => $blogs->total(),
                'from'         => $blogs->firstItem(),
                'to'           => $blogs->lastItem(),
            ],
        ]);
    }

    // ── GET /api/v1/blogs/{slug} ─────────────────────────────────────────────
    public function show(string $slug): JsonResponse
    {
        $blog = Blog::with('author')->where('slug', $slug)->firstOrFail();
        return response()->json(['data' => new BlogResource($blog)]);
    }

    // ── POST /api/v1/blogs ───────────────────────────────────────────────────
    public function store(BlogRequest $request): JsonResponse
    {
        try {
            DB::beginTransaction();
            
            $data = $request->validated();
            
            // Set author_id from authenticated user if not provided
            if (empty($data['author_id']) && auth()->check()) {
                $data['author_id'] = auth()->id();
            }
            
            $data['created_by'] = auth()->id();
            $data['updated_by'] = auth()->id();

            // Handle tags (convert string to array if needed)
            if (isset($data['tags']) && is_string($data['tags'])) {
                $data['tags'] = array_map('trim', explode(',', $data['tags']));
            }

            // Process cover image if it's a base64 or temporary file
            if (isset($data['cover_image']) && !empty($data['cover_image'])) {
                $data['cover_image'] = $this->processCoverImage($data['cover_image']);
            }

            // Process content images (replace temporary URLs with permanent ones)
            if (isset($data['content']) && !empty($data['content'])) {
                $data['content'] = $this->processContentImages($data['content']);
            }

            // Set published_at if publishing
            if (!empty($data['is_published']) && empty($data['published_at'])) {
                $data['published_at'] = now();
            }

            $blog = Blog::create($data);
            
            DB::commit();

            return response()->json([
                'data' => new BlogResource($blog), 
                'message' => 'Blog created successfully.'
            ], 201);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'message' => 'Failed to create blog: ' . $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ], 500);
        }
    }

    // ── PATCH /api/v1/blogs/{slug} ───────────────────────────────────────────
    public function update(BlogRequest $request, string $slug): JsonResponse
    {
        try {
            DB::beginTransaction();
            
            $blog = Blog::where('slug', $slug)->firstOrFail();
            $data = $request->validated();
            
            $data['updated_by'] = auth()->id();

            // Handle tags (convert string to array if needed)
            if (isset($data['tags']) && is_string($data['tags'])) {
                $data['tags'] = array_map('trim', explode(',', $data['tags']));
            }

            // Process cover image
            if (isset($data['cover_image']) && !empty($data['cover_image'])) {
                // Delete old cover image if exists
                if ($blog->cover_image && !filter_var($blog->cover_image, FILTER_VALIDATE_URL)) {
                    $oldPath = str_replace('/storage/', '', $blog->cover_image);
                    if (Storage::disk('public')->exists($oldPath)) {
                        Storage::disk('public')->delete($oldPath);
                    }
                }
                $data['cover_image'] = $this->processCoverImage($data['cover_image']);
            }

            // Process content images
            if (isset($data['content']) && !empty($data['content'])) {
                $data['content'] = $this->processContentImages($data['content'], $blog->content ?? null);
            }

            // Set published_at if publishing and not previously published
            if (!empty($data['is_published']) && empty($blog->published_at) && empty($data['published_at'])) {
                $data['published_at'] = now();
            }

            $blog->update($data);
            
            DB::commit();

            return response()->json([
                'data' => new BlogResource($blog->fresh()), 
                'message' => 'Blog updated successfully.'
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'message' => 'Failed to update blog: ' . $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ], 500);
        }
    }

    // ── DELETE /api/v1/blogs/{slug} ──────────────────────────────────────────
    public function destroy(string $slug): JsonResponse
    {
        $blog = Blog::where('slug', $slug)->firstOrFail();
        
        // Delete associated images
        if ($blog->cover_image && !filter_var($blog->cover_image, FILTER_VALIDATE_URL)) {
            $coverPath = str_replace('/storage/', '', $blog->cover_image);
            if (Storage::disk('public')->exists($coverPath)) {
                Storage::disk('public')->delete($coverPath);
            }
        }
        
        $blog->delete();
        
        return response()->json(['message' => 'Blog deleted successfully.']);
    }

    /**
     * Process cover image - handle base64 or URL, save to storage
     */
    private function processCoverImage($coverImage): string
    {
        // If it's already a valid URL, return as is
        if (filter_var($coverImage, FILTER_VALIDATE_URL)) {
            // Check if it's a temporary file that needs to be moved
            if (strpos($coverImage, '/storage/temp/') !== false) {
                return $this->moveTempImageToPermanent($coverImage, 'covers');
            }
            return $coverImage;
        }
        
        // If it's base64 encoded image
        if (strpos($coverImage, 'data:image') === 0) {
            return $this->saveBase64Image($coverImage, 'covers');
        }
        
        return $coverImage;
    }

    /**
     * Process content images - replace temporary URLs with permanent ones
     */
    private function processContentImages(string $content, ?string $oldContent = null): string
    {
        // Extract all image URLs from content
        preg_match_all('/<img[^>]+src=["\']([^"\']+)["\']/i', $content, $matches);
        
        if (empty($matches[1])) {
            return $content;
        }
        
        foreach ($matches[1] as $imageUrl) {
            // Check if image is from temporary upload
            if (strpos($imageUrl, '/storage/temp/') !== false) {
                $newUrl = $this->moveTempImageToPermanent($imageUrl, 'images');
                $content = str_replace($imageUrl, $newUrl, $content);
            }
        }
        
        return $content;
    }

    /**
     * Move temporary image to permanent storage
     */
    private function moveTempImageToPermanent(string $tempUrl, string $subfolder): string
    {
        $tempPath = str_replace('/storage/', '', $tempUrl);
        $extension = pathinfo($tempPath, PATHINFO_EXTENSION);
        $newFilename = 'blog_' . time() . '_' . Str::random(10) . '.' . $extension;
        $newPath = "blog/{$subfolder}/" . $newFilename;
        
        if (Storage::disk('public')->exists($tempPath)) {
            Storage::disk('public')->move($tempPath, $newPath);
            return Storage::url($newPath);
        }
        
        return $tempUrl;
    }

    /**
     * Save base64 encoded image to storage
     */
    private function saveBase64Image(string $base64, string $subfolder): string
    {
        // Extract image data
        if (preg_match('/^data:image\/(\w+);base64,/', $base64, $matches)) {
            $extension = $matches[1];
            $base64Data = substr($base64, strpos($base64, ',') + 1);
            $imageData = base64_decode($base64Data);
            
            $filename = 'blog_' . time() . '_' . Str::random(10) . '.' . $extension;
            $path = "blog/{$subfolder}/" . $filename;
            
            Storage::disk('public')->put($path, $imageData);
            
            return Storage::url($path);
        }
        
        return $base64;
    }

    // ── PATCH /api/v1/blogs/{slug}/publish ───────────────────────────────────
    public function publish(string $slug): JsonResponse
    {
        $blog = Blog::where('slug', $slug)->firstOrFail();
        $blog->update([
            'is_published' => true,
            'is_active'    => true,
            'published_at' => $blog->published_at ?? now(),
            'updated_by'   => auth()->id(),
        ]);
        return response()->json(['data' => new BlogResource($blog->fresh()), 'message' => 'Blog published.']);
    }

    // ── PATCH /api/v1/blogs/{slug}/unpublish ─────────────────────────────────
    public function unpublish(string $slug): JsonResponse
    {
        $blog = Blog::where('slug', $slug)->firstOrFail();
        $blog->update([
            'is_published' => false,
            'updated_by'   => auth()->id(),
        ]);
        return response()->json(['data' => new BlogResource($blog->fresh()), 'message' => 'Blog unpublished.']);
    }

    // ── PATCH /api/v1/blogs/{slug}/feature ───────────────────────────────────
    public function feature(Request $request, string $slug): JsonResponse
    {
        $blog = Blog::where('slug', $slug)->firstOrFail();
        $days = max(1, min(365, (int) $request->input('days', 30)));
        $blog->update([
            'is_featured'    => true,
            'featured_until' => now()->addDays($days),
            'updated_by'     => auth()->id(),
        ]);
        return response()->json(['message' => "Featured for {$days} days."]);
    }

    // ── POST /api/v1/blogs/{slug}/increment-view ─────────────────────────────
    public function incrementView(string $slug): JsonResponse
    {
        Blog::where('slug', $slug)->increment('view_count');
        return response()->json(['message' => 'View counted.']);
    }

    // ── POST /api/v1/blogs/{blog}/increment-share ────────────────────────────
    public function incrementShare(Blog $blog): JsonResponse
    {
        $blog->increment('share_count');
        return response()->json(['message' => 'Share counted.']);
    }

    // ── GET /api/v1/blogs/categories ─────────────────────────────────────────
    public function categories(): JsonResponse
    {
        $cats = Blog::published()
            ->selectRaw('category, count(*) as posts_count')
            ->groupBy('category')
            ->orderByDesc('posts_count')
            ->get();

        return response()->json(['data' => $cats]);
    }

    // ── GET /api/v1/blogs/related/{slug} ────────────────────────────────────
    public function related(string $slug): JsonResponse
    {
        $blog    = Blog::where('slug', $slug)->firstOrFail();
        $related = Blog::published()
            ->where('id', '!=', $blog->id)
            ->where(fn($q) =>
                $q->where('category', $blog->category)
                  ->orWhereJsonContains('tags', $blog->tags[0] ?? '__none__'))
            ->orderByDesc('published_at')
            ->limit(4)
            ->get();

        return response()->json(['data' => BlogResource::collection($related)]);
    }


    public function categoriesList(): JsonResponse
    {
        $categories = Blog::select('category')
            ->whereNotNull('category')
            ->where('category', '!=', '')
            ->distinct()
            ->orderBy('category')
            ->pluck('category');
        
        return response()->json(['data' => $categories]);
    }

    public function tagsList(): JsonResponse
    {
        $tags = Blog::whereNotNull('tags')
            ->get()
            ->pluck('tags')
            ->flatten()
            ->unique()
            ->sort()
            ->values();
        
        return response()->json(['data' => $tags]);
    }

}