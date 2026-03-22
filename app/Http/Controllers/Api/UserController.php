<?php

namespace App\Http\Controllers\Api;

use App\Http\Concerns\ApiResponse;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\UserRequest;
use App\Models\Auth\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class UserController extends Controller
{
    use ApiResponse;

    /**
     * GET /api/v1/users
     * List all users with optional filters and pagination.
     *
     * Query params:
     *   search      (string)  - filter by first_name, last_name, or email
     *   is_active   (bool)    - filter by active status
     *   per_page    (int)     - records per page (default 15)
     */
    public function index(Request $request): JsonResponse
    {
        $query = User::query();

        if ($request->filled('search')) {
            $term = $request->search;
            $query->where(function ($q) use ($term) {
                $q->where('first_name', 'like', "%{$term}%")
                  ->orWhere('last_name',  'like', "%{$term}%")
                  ->orWhere('email',       'like', "%{$term}%");
            });
        }

        if ($request->has('is_active')) {
            $query->where('is_active', filter_var($request->is_active, FILTER_VALIDATE_BOOLEAN));
        }

        $users = $query->orderBy('created_at', 'desc')
                       ->paginate($request->integer('per_page', 15));

        return $this->paginated($users, 'Users retrieved successfully');
    }

    /**
     * POST /api/v1/users
     * Create a new user.
     */
    public function store(UserRequest $request): JsonResponse
    {
        $user = User::create(array_merge($request->validated(), [
            'is_active' => $request->boolean('is_active', true),
        ]));

        return $this->created($user, 'User created successfully');
    }

    /**
     * GET /api/v1/users/{user}
     * Show a single user by ID.
     */
    public function show(User $user): JsonResponse
    {
        return $this->success($user, 'User retrieved successfully');
    }

    /**
     * PATCH /api/v1/users/{user}
     * Update an existing user.
     */
    public function update(UserRequest $request, User $user): JsonResponse
    {
        $user->update($request->validated());

        return $this->success($user->fresh(), 'User updated successfully');
    }

    /**
     * DELETE /api/v1/users/{user}
     * Soft-delete a user.
     */
    public function destroy(User $user): JsonResponse
    {
        $user->delete();

        return $this->deleted('User deleted successfully');
    }

    public function list(): JsonResponse
    {
        $users = \App\Models\Auth\User::select('id', 'first_name', 'last_name', 'email')
            ->where('is_active', true)
            ->whereDoesntHave('roles', fn($q) => $q->where('name', 'job_seeker'))
            ->orderBy('first_name')
            ->get()
            ->map(fn($u) => [
                'id'    => $u->id,
                'name'  => trim($u->first_name . ' ' . $u->last_name),
                'email' => $u->email,
            ]);

        return response()->json(['data' => $users]);
    }
}
