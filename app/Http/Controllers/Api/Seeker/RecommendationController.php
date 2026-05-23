<?php
// MAIN APP: app/Http/Controllers/Api/Seeker/RecommendationController.php

namespace App\Http\Controllers\Api\Seeker;

use App\Http\Concerns\ApiResponse;
use App\Http\Controllers\Controller;
use App\Services\JobRecommendationService;
use Illuminate\Http\{Request, JsonResponse};

class RecommendationController extends Controller
{
    use ApiResponse;

    public function __construct(private JobRecommendationService $service) {}

    // GET /api/v1/seeker/recommendations?limit=6
    public function getRecommendations(Request $request): JsonResponse
    {
        $limit  = min((int) $request->get('limit', 6), 12); // cap at 12
        $result = $this->service->getRecommendations($request->user()->id, $limit);

        // Strip internal _score key before sending to client
        $jobs = array_map(function ($job) {
            unset($job['_score']);
            return $job;
        }, $result['jobs'] instanceof \Illuminate\Support\Collection
            ? $result['jobs']->toArray()
            : (array) $result['jobs']
        );

        return response()->json([
            'data'        => $jobs,
            'has_profile' => $result['has_profile'],
            'mode'        => $result['mode'],
            'message'     => $result['message'],
            'total'       => count($jobs),
        ]);
    }

    // POST /api/v1/seeker/recommendations/refresh
    // Call this after a CV save to bust the cache
    public function refresh(Request $request): JsonResponse
    {
        $this->service->clearCache($request->user()->id);
        return $this->success(null, 'Recommendations refreshed.');
    }
}