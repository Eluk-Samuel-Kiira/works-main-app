<?php

namespace App\Http\Controllers\Api\Jobs;

use App\Http\Concerns\ApiResponse;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\Jobs\SalaryRangeRequest;
use App\Models\Job\SalaryRange;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class SalaryRangeController extends Controller
{
    use ApiResponse;

    /**
     * GET /api/v1/salary-ranges
     * List all salary ranges with optional filters and pagination.
     *
     * Query params:
     *   search    (string) - filter by name
     *   is_active (bool)   - filter by active status
     *   currency  (string) - filter by currency (e.g. UGX)
     *   per_page  (int)    - records per page (default 15)
     */
    public function index(Request $request): JsonResponse
    {
        $query = SalaryRange::query();

        if ($request->filled('search')) {
            $query->where('name', 'like', "%{$request->search}%");
        }

        if ($request->has('is_active')) {
            $query->where('is_active', filter_var($request->is_active, FILTER_VALIDATE_BOOLEAN));
        }

        if ($request->filled('currency')) {
            $query->where('currency', $request->currency);
        }

        $ranges = $query->orderBy('sort_order')
                        ->orderBy('min_salary')
                        ->paginate($request->integer('per_page', 15));

        return $this->paginated($ranges, 'Salary ranges retrieved successfully');
    }

    /**
     * POST /api/v1/salary-ranges
     * Create a new salary range.
     */
    public function store(SalaryRangeRequest $request): JsonResponse
    {
        $range = SalaryRange::create($request->validated());

        return $this->created($range, 'Salary range created successfully');
    }

    /**
     * GET /api/v1/salary-ranges/{salary_range}
     * Show a single salary range by ID.
     */
    public function show(SalaryRange $salaryRange): JsonResponse
    {
        return $this->success($salaryRange, 'Salary range retrieved successfully');
    }

    /**
     * PATCH /api/v1/salary-ranges/{salary_range}
     * Update an existing salary range.
     */
    public function update(SalaryRangeRequest $request, SalaryRange $salaryRange): JsonResponse
    {
        $salaryRange->update($request->validated());

        return $this->success($salaryRange->fresh(), 'Salary range updated successfully');
    }

    /**
     * DELETE /api/v1/salary-ranges/{salary_range}
     * Delete a salary range.
     */
    public function destroy(SalaryRange $salaryRange): JsonResponse
    {
        $salaryRange->delete();

        return $this->deleted('Salary range deleted successfully');
    }
}
