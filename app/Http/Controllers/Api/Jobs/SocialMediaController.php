<?php

namespace App\Http\Controllers\Api\Jobs;

use App\Http\Concerns\ApiResponse;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\Jobs\SocialMediaRequest;
use App\Models\Job\SocialMediaPlatform;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class SocialMediaController extends Controller
{
    use ApiResponse;

    /**
     * GET /api/v1/social-media
     *
     * Query params:
     *   location_id  (int)    - filter by location
     *   platform     (string) - filter by platform type
     *   is_active    (bool)
     *   is_featured  (bool)
     *   search       (string) - filter by name/handle
     *   per_page     (int)    - default 20
     */
    public function index(Request $request): JsonResponse
    {
        $query = SocialMediaPlatform::with(['location:id,district,country', 'creator:id,first_name,last_name']);

        if ($request->filled('search')) {
            $query->where(function ($q) use ($request) {
                $q->where('name', 'like', "%{$request->search}%")
                  ->orWhere('handle', 'like', "%{$request->search}%");
            });
        }

        if ($request->filled('location_id')) {
            $query->where('location_id', $request->integer('location_id'));
        }

        if ($request->filled('platform')) {
            $query->where('platform', $request->platform);
        }

        if ($request->has('is_active')) {
            $query->where('is_active', filter_var($request->is_active, FILTER_VALIDATE_BOOLEAN));
        }

        if ($request->has('is_featured')) {
            $query->where('is_featured', filter_var($request->is_featured, FILTER_VALIDATE_BOOLEAN));
        }

        $items = $query->ordered()
                       ->paginate($request->integer('per_page', 20));

        // Append computed accessors to each item
        $items->getCollection()->transform(fn($item) => $this->format($item));

        return $this->paginated($items, 'Social media platforms retrieved successfully');
    }

    /**
     * POST /api/v1/social-media
     */
    public function store(SocialMediaRequest $request): JsonResponse
    {
        $platform = SocialMediaPlatform::create($request->validated());

        return $this->created(
            $this->format($platform->load(['location:id,district,country', 'creator:id,first_name,last_name'])),
            'Social media platform created successfully'
        );
    }

    /**
     * GET /api/v1/social-media/{social_media_platform}
     */
    public function show(SocialMediaPlatform $socialMediaPlatform): JsonResponse
    {
        return $this->success(
            $this->format($socialMediaPlatform->load(['location:id,district,country', 'creator:id,first_name,last_name'])),
            'Social media platform retrieved successfully'
        );
    }

    /**
     * PATCH /api/v1/social-media/{social_media_platform}
     */
    public function update(SocialMediaRequest $request, SocialMediaPlatform $socialMediaPlatform): JsonResponse
    {
        $socialMediaPlatform->update($request->validated());

        return $this->success(
            $this->format($socialMediaPlatform->fresh()->load(['location:id,district,country', 'creator:id,first_name,last_name'])),
            'Social media platform updated successfully'
        );
    }

    /**
     * DELETE /api/v1/social-media/{social_media_platform}
     */
    public function destroy(SocialMediaPlatform $socialMediaPlatform): JsonResponse
    {
        $socialMediaPlatform->delete();
        return $this->deleted('Social media platform deleted successfully');
    }

    /**
     * GET /api/v1/social-media/by-location/{locationId}
     * Convenience endpoint — returns all active platforms for a given location.
     * Used by the frontend to render social icons per country/region.
     */
    public function byLocation(int $locationId): JsonResponse
    {
        $items = SocialMediaPlatform::with(['location:id,district,country'])
            ->where('location_id', $locationId)
            ->where('is_active', true)
            ->ordered()
            ->get()
            ->map(fn($item) => $this->format($item));

        return $this->success($items, 'Social media platforms for location retrieved successfully');
    }

    /**
     * GET /api/v1/social-media/platforms
     * Returns the list of supported platform types with labels and colors.
     * Used to populate the platform dropdown in the admin form.
     */
    public function platforms(): JsonResponse
    {
        $platforms = collect(SocialMediaPlatform::PLATFORMS)
            ->map(fn($meta, $key) => [
                'value' => $key,
                'label' => $meta['label'],
                'color' => $meta['color'],
                'icon'  => $meta['icon'],
            ])
            ->values();

        return $this->success($platforms, 'Platforms retrieved successfully');
    }

    // -------------------------------------------------------------------------
    // Format helper — appends computed fields
    // -------------------------------------------------------------------------
    private function format(SocialMediaPlatform $item): array
    {
        return [
            'id'                  => $item->id,
            'name'                => $item->name,
            'slug'                => $item->slug,
            'platform'            => $item->platform,
            'platform_label'      => $item->platform_label,
            'platform_color'      => $item->platform_color,
            'platform_icon'       => $item->platform_icon,
            'url'                 => $item->url,
            'handle'              => $item->handle,
            'description'         => $item->description,
            'followers_count'     => $item->followers_count,
            'followers_formatted' => $item->followers_formatted,
            'is_active'           => $item->is_active,
            'is_verified'         => $item->is_verified,
            'is_featured'         => $item->is_featured,
            'sort_order'          => $item->sort_order,
            'meta_title'          => $item->meta_title,
            'meta_description'    => $item->meta_description,
            'location'            => $item->location ? [
                'id'       => $item->location->id,
                'district' => $item->location->district ?? '',
                'country'  => $item->location->country  ?? '',
                'name'     => $item->location->district ?? $item->location->country ?? '',
            ] : null,
            'creator'             => $item->creator ? [
                'id'   => $item->creator->id,
                'name' => trim(($item->creator->first_name ?? '') . ' ' . ($item->creator->last_name ?? '')),
            ] : null,
            'created_at'          => $item->created_at?->format('Y-m-d H:i:s'),
            'updated_at'          => $item->updated_at?->format('Y-m-d H:i:s'),
        ];
    }

    public function indexPublic(Request $request): JsonResponse
    {
        $items = SocialMediaPlatform::with('location')
            ->where('is_active', true)
            ->orderBy('sort_order')
            ->orderBy('platform')
            ->get()
            ->map(fn($item) => [
                'id'               => $item->id,
                'name'             => $item->name,
                'slug'             => $item->slug,
                'platform'         => $item->platform,
                'platform_label'   => $item->platform_label,
                'platform_color'   => $item->platform_color,
                'platform_icon'    => $item->platform_icon,
                'handle'           => $item->handle,
                'url'              => $item->url,
                'followers_count'  => $item->followers_count,
                'followers_formatted' => $item->followers_formatted,
                'is_verified'      => $item->is_verified,
                'is_featured'      => $item->is_featured,
                'location'         => $item->location ? [
                    'id'       => $item->location->id,
                    'district' => $item->location->district,
                    'country'  => $item->location->country,
                ] : null,
            ]);

        return response()->json(['data' => $items]);
    }
}