<?php

namespace App\Http\Controllers\Api\Jobs;

use App\Http\Concerns\ApiResponse;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\Jobs\JobTypeRequest;
use App\Models\Job\JobType;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class JobTypeController extends Controller
{
    use ApiResponse;

    /**
     * GET /api/v1/job-types
     * List all job types with optional filters and pagination.
     *
     * Query params:
     *   search    (string) - filter by name
     *   is_active (bool)   - filter by active status
     *   per_page  (int)    - records per page (default 15)
     */
    public function index(Request $request): JsonResponse
    {
        $query = JobType::query();

        if ($request->filled('search')) {
            $query->where('name', 'like', "%{$request->search}%");
        }

        if ($request->has('is_active')) {
            $query->where('is_active', filter_var($request->is_active, FILTER_VALIDATE_BOOLEAN));
        }

        $jobTypes = $query->orderBy('sort_order')
                          ->orderBy('name')
                          ->paginate($request->integer('per_page', 15));

        return $this->paginated($jobTypes, 'Job types retrieved successfully');
    }

    /**
     * POST /api/v1/job-types
     * Create a new job type.
     */
    public function store(JobTypeRequest $request): JsonResponse
    {
        $jobType = JobType::create($request->validated());

        return $this->created($jobType, 'Job type created successfully');
    }

    /**
     * GET /api/v1/job-types/{job_type}
     * Show a single job type by ID.
     */
    public function show(JobType $jobType): JsonResponse
    {
        return $this->success($jobType, 'Job type retrieved successfully');
    }

    /**
     * PATCH /api/v1/job-types/{job_type}
     * Update an existing job type.
     */
    public function update(JobTypeRequest $request, JobType $jobType): JsonResponse
    {
        $jobType->update($request->validated());

        return $this->success($jobType->fresh(), 'Job type updated successfully');
    }

    /**
     * DELETE /api/v1/job-types/{job_type}
     * Delete a job type.
     */
    public function destroy(JobType $jobType): JsonResponse
    {
        $jobType->delete();

        return $this->deleted('Job type deleted successfully');
    }
}
