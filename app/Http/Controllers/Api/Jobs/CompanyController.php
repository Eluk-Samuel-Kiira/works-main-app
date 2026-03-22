<?php

namespace App\Http\Controllers\Api\Jobs;

use App\Http\Concerns\ApiResponse;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\Jobs\CompanyRequest;
use App\Models\Job\Company;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CompanyController extends Controller
{
    use ApiResponse;

    /**
     * GET /api/v1/companies
     * List all companies with optional filters and pagination.
     *
     * Query params:
     *   search      (string)  - filter by name
     *   is_active   (bool)    - filter by active status
     *   is_verified (bool)    - filter by verified status
     *   industry_id (int)     - filter by industry
     *   per_page    (int)     - records per page (default 15)
     */
    public function index(Request $request): JsonResponse
    {
        $query = Company::with('industry');

        if ($request->filled('search')) {
            $query->where('name', 'like', "%{$request->search}%");
        }

        if ($request->has('is_active')) {
            $query->where('is_active', filter_var($request->is_active, FILTER_VALIDATE_BOOLEAN));
        }

        if ($request->has('is_verified')) {
            $query->where('is_verified', filter_var($request->is_verified, FILTER_VALIDATE_BOOLEAN));
        }

        if ($request->filled('industry_id')) {
            $query->where('industry_id', $request->industry_id);
        }

        $companies = $query->orderBy('name')
                           ->paginate($request->integer('per_page', 15));

        return $this->paginated($companies, 'Companies retrieved successfully');
    }

    /**
     * POST /api/v1/companies
     * Create a new company.
     */
    public function store(CompanyRequest $request): JsonResponse
    {
        $company = Company::create($request->validated());

        return $this->created($company->load('industry'), 'Company created successfully');
    }

    /**
     * GET /api/v1/companies/{company}
     * Show a single company by slug.
     */
    public function show(Company $company): JsonResponse
    {
        return $this->success($company->load('industry'), 'Company retrieved successfully');
    }

    /**
     * PATCH /api/v1/companies/{company}
     * Update an existing company.
     */
    public function update(CompanyRequest $request, Company $company): JsonResponse
    {
        $company->update($request->validated());

        return $this->success($company->fresh()->load('industry'), 'Company updated successfully');
    }

    /**
     * DELETE /api/v1/companies/{company}
     * Delete a company.
     */
    public function destroy(Company $company): JsonResponse
    {
        $company->delete();

        return $this->deleted('Company deleted successfully');
    }
}
