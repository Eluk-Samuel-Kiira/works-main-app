<?php

namespace App\Http\Controllers\Api\Jobs;

use App\Http\Concerns\ApiResponse;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\Jobs\JobLocationRequest;
use App\Models\Job\JobLocation;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class JobLocationController extends Controller
{
    use ApiResponse;

    /**
     * GET /api/v1/job-locations
     * List all job locations with optional filters and pagination.
     *
     * Query params:
     *   search    (string) - filter by district or country
     *   is_active (bool)   - filter by active status
     *   country   (string) - filter by country code (e.g. UG)
     *   per_page  (int)    - records per page (default 15)
     */
    public function index(Request $request): JsonResponse
    {
        $query = JobLocation::query();

        if ($request->filled('search')) {
            $term = $request->search;
            $query->where(function ($q) use ($term) {
                $q->where('district', 'like', "%{$term}%")
                  ->orWhere('country', 'like', "%{$term}%");
            });
        }

        if ($request->has('is_active')) {
            $query->where('is_active', filter_var($request->is_active, FILTER_VALIDATE_BOOLEAN));
        }

        if ($request->filled('country')) {
            $query->where('country', $request->country);
        }

        $locations = $query->orderBy('sort_order')
                           ->orderBy('district')
                           ->paginate($request->integer('per_page', 15));

        return $this->paginated($locations, 'Job locations retrieved successfully');
    }

    /**
     * POST /api/v1/job-locations
     * Create a new job location.
     */
    public function store(JobLocationRequest $request): JsonResponse
    {
        $location = JobLocation::create($request->validated());

        return $this->created($location, 'Job location created successfully');
    }

    /**
     * GET /api/v1/job-locations/{job_location}
     * Show a single job location by ID.
     */
    public function show(JobLocation $jobLocation): JsonResponse
    {
        return $this->success($jobLocation, 'Job location retrieved successfully');
    }

    /**
     * PATCH /api/v1/job-locations/{job_location}
     * Update an existing job location.
     */
    public function update(JobLocationRequest $request, JobLocation $jobLocation): JsonResponse
    {
        $jobLocation->update($request->validated());

        return $this->success($jobLocation->fresh(), 'Job location updated successfully');
    }

    /**
     * DELETE /api/v1/job-locations/{job_location}
     * Delete a job location.
     */
    public function destroy(JobLocation $jobLocation): JsonResponse
    {
        $jobLocation->delete();

        return $this->deleted('Job location deleted successfully');
    }
}
