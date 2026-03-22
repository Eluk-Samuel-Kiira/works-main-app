<?php

namespace App\Http\Controllers\Api\Jobs;

use App\Http\Concerns\ApiResponse;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\Jobs\JobCategoryRequest;
use App\Models\Job\JobCategory;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class JobCategoryController extends Controller
{
    use ApiResponse;

    /**
     * GET /api/v1/job-categories
     * List all job categories with optional filters and pagination.
     *
     * Query params:
     *   search    (string) - filter by name
     *   is_active (bool)   - filter by active status
     *   per_page  (int)    - records per page (default 15)
     */
    public function index(Request $request): JsonResponse
    {
        $query = JobCategory::query();

        if ($request->filled('search')) {
            $query->where('name', 'like', "%{$request->search}%");
        }

        if ($request->has('is_active')) {
            $query->where('is_active', filter_var($request->is_active, FILTER_VALIDATE_BOOLEAN));
        }

        $categories = $query->orderBy('sort_order')
                            ->orderBy('name')
                            ->paginate($request->integer('per_page', 15));

        return $this->paginated($categories, 'Job categories retrieved successfully');
    }

    /**
     * POST /api/v1/job-categories
     * Create a new job category.
     */
    public function store(JobCategoryRequest $request): JsonResponse
    {
        $category = JobCategory::create($request->validated());

        return $this->created($category, 'Job category created successfully');
    }

    /**
     * GET /api/v1/job-categories/{job_category}
     * Show a single job category by ID.
     */
    public function show(JobCategory $jobCategory): JsonResponse
    {
        return $this->success($jobCategory, 'Job category retrieved successfully');
    }

    /**
     * PATCH /api/v1/job-categories/{job_category}
     * Update an existing job category.
     */
    public function update(JobCategoryRequest $request, JobCategory $jobCategory): JsonResponse
    {
        $jobCategory->update($request->validated());

        return $this->success($jobCategory->fresh(), 'Job category updated successfully');
    }

    /**
     * DELETE /api/v1/job-categories/{job_category}
     * Delete a job category.
     */
    public function destroy(JobCategory $jobCategory): JsonResponse
    {
        $jobCategory->delete();

        return $this->deleted('Job category deleted successfully');
    }
}
