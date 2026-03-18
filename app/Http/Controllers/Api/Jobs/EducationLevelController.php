<?php

namespace App\Http\Controllers\Api\Jobs;

use App\Http\Concerns\ApiResponse;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\Jobs\EducationLevelRequest;
use App\Models\Job\EducationLevel;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class EducationLevelController extends Controller
{
    use ApiResponse;

    /**
     * GET /api/v1/education-levels
     * List all education levels with optional filters and pagination.
     *
     * Query params:
     *   search    (string) - filter by name
     *   is_active (bool)   - filter by active status
     *   per_page  (int)    - records per page (default 15)
     */
    public function index(Request $request): JsonResponse
    {
        $query = EducationLevel::query();

        if ($request->filled('search')) {
            $query->where('name', 'like', "%{$request->search}%");
        }

        if ($request->has('is_active')) {
            $query->where('is_active', filter_var($request->is_active, FILTER_VALIDATE_BOOLEAN));
        }

        $levels = $query->orderBy('sort_order')
                        ->orderBy('name')
                        ->paginate($request->integer('per_page', 15));

        return $this->paginated($levels, 'Education levels retrieved successfully');
    }

    /**
     * POST /api/v1/education-levels
     * Create a new education level.
     */
    public function store(EducationLevelRequest $request): JsonResponse
    {
        $level = EducationLevel::create($request->validated());

        return $this->created($level, 'Education level created successfully');
    }

    /**
     * GET /api/v1/education-levels/{education_level}
     * Show a single education level by ID.
     */
    public function show(EducationLevel $educationLevel): JsonResponse
    {
        return $this->success($educationLevel, 'Education level retrieved successfully');
    }

    /**
     * PATCH /api/v1/education-levels/{education_level}
     * Update an existing education level.
     */
    public function update(EducationLevelRequest $request, EducationLevel $educationLevel): JsonResponse
    {
        $educationLevel->update($request->validated());

        return $this->success($educationLevel->fresh(), 'Education level updated successfully');
    }

    /**
     * DELETE /api/v1/education-levels/{education_level}
     * Delete an education level.
     */
    public function destroy(EducationLevel $educationLevel): JsonResponse
    {
        $educationLevel->delete();

        return $this->deleted('Education level deleted successfully');
    }
}
