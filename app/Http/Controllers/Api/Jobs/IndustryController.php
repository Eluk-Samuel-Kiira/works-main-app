<?php

namespace App\Http\Controllers\Api\Jobs;

use App\Http\Concerns\ApiResponse;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\Jobs\IndustryRequest;
use App\Models\Job\Industry;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class IndustryController extends Controller
{
    use ApiResponse;

    /**
     * GET /api/v1/industries
     * List all industries with optional filters and pagination.
     *
     * Query params:
     *   search    (string) - filter by name
     *   is_active (bool)   - filter by active status
     *   per_page  (int)    - records per page (default 15)
     */
    public function index(Request $request): JsonResponse
    {
        $query = Industry::query();

        if ($request->filled('search')) {
            $query->where('name', 'like', "%{$request->search}%");
        }

        if ($request->has('is_active')) {
            $query->where('is_active', filter_var($request->is_active, FILTER_VALIDATE_BOOLEAN));
        }

        $industries = $query->orderBy('sort_order')
                            ->orderBy('name')
                            ->paginate($request->integer('per_page', 15));

        return $this->paginated($industries, 'Industries retrieved successfully');
    }

    /**
     * POST /api/v1/industries
     * Create a new industry.
     */
    public function store(IndustryRequest $request): JsonResponse
    {
        $industry = Industry::create($request->validated());

        return $this->created($industry, 'Industry created successfully');
    }

    /**
     * GET /api/v1/industries/{industry}
     * Show a single industry by ID.
     */
    public function show(Industry $industry): JsonResponse
    {
        return $this->success($industry, 'Industry retrieved successfully');
    }

    /**
     * PATCH /api/v1/industries/{industry}
     * Update an existing industry.
     */
    public function update(IndustryRequest $request, Industry $industry): JsonResponse
    {
        $industry->update($request->validated());

        return $this->success($industry->fresh(), 'Industry updated successfully');
    }

    /**
     * DELETE /api/v1/industries/{industry}
     * Delete an industry.
     */
    public function destroy(Industry $industry): JsonResponse
    {
        $industry->delete();

        return $this->deleted('Industry deleted successfully');
    }
}
