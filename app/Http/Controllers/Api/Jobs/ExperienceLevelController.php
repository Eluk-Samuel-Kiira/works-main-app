<?php

namespace App\Http\Controllers\Api\Jobs;

use App\Http\Concerns\ApiResponse;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\Jobs\ExperienceLevelRequest;
use App\Models\Job\ExperienceLevel;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ExperienceLevelController extends Controller
{
    use ApiResponse;

    /**
     * GET /api/v1/experience-levels
     * List all experience levels with optional filters and pagination.
     *
     * Query params:
     *   search    (string) - filter by name
     *   is_active (bool)   - filter by active status
     *   per_page  (int)    - records per page (default 15)
     */
    public function index(Request $request): JsonResponse
    {
        $query = ExperienceLevel::query();

        if ($request->filled('search')) {
            $query->where('name', 'like', "%{$request->search}%");
        }

        if ($request->has('is_active')) {
            $query->where('is_active', filter_var($request->is_active, FILTER_VALIDATE_BOOLEAN));
        }

        $levels = $query->orderBy('sort_order')
                        ->orderBy('min_years')
                        ->paginate($request->integer('per_page', 15));

        return $this->paginated($levels, 'Experience levels retrieved successfully');
    }

    /**
     * POST /api/v1/experience-levels
     * Create a new experience level.
     */
    public function store(ExperienceLevelRequest $request): JsonResponse
    {
        $level = ExperienceLevel::create($request->validated());

        return $this->created($level, 'Experience level created successfully');
    }

    /**
     * GET /api/v1/experience-levels/{experience_level}
     * Show a single experience level by ID.
     */
    public function show(ExperienceLevel $experienceLevel): JsonResponse
    {
        return $this->success($experienceLevel, 'Experience level retrieved successfully');
    }

    /**
     * PATCH /api/v1/experience-levels/{experience_level}
     * Update an existing experience level.
     */
    public function update(ExperienceLevelRequest $request, ExperienceLevel $experienceLevel): JsonResponse
    {
        $experienceLevel->update($request->validated());

        return $this->success($experienceLevel->fresh(), 'Experience level updated successfully');
    }

    /**
     * DELETE /api/v1/experience-levels/{experience_level}
     * Delete an experience level.
     */
    public function destroy(ExperienceLevel $experienceLevel): JsonResponse
    {
        $experienceLevel->delete();

        return $this->deleted('Experience level deleted successfully');
    }
}
