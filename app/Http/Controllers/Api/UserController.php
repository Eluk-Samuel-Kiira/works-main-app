<?php
namespace App\Http\Controllers\Api;

use App\Http\Concerns\ApiResponse;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\UserRequest;
use App\Models\Auth\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Spatie\Permission\Models\Role;
use Illuminate\Support\Facades\DB;

class UserController extends Controller
{
    use ApiResponse;

    /**
     * GET /api/v1/users
     * List all users with optional filters and pagination.
     */
    public function index(Request $request): JsonResponse
    {
        $query = User::query()->with('roles');

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

        if ($request->filled('role')) {
            $query->whereHas('roles', function($q) use ($request) {
                $q->where('name', $request->role);
            });
        }

        $users = $query->orderBy('created_at', 'desc')
                    ->paginate($request->integer('per_page', 15));

        // Transform users to include role names
        $users->getCollection()->transform(function ($user) {
            $user->role_names = $user->roles->pluck('name');
            $user->primary_role = $user->roles->first()?->name;
            return $user;
        });

        // Get role statistics - make sure this runs AFTER transformations
        $roleStats = $this->getRoleStats();
        
        // Add total to meta
        $meta = [
            'current_page' => $users->currentPage(),
            'last_page' => $users->lastPage(),
            'per_page' => $users->perPage(),
            'total' => $users->total(),
            'from' => $users->firstItem(),
            'to' => $users->lastItem(),
            'role_stats' => $roleStats  // Make sure this is included
        ];

        return response()->json([
            'success' => true,
            'message' => 'Users retrieved successfully',
            'data' => $users->items(),
            'meta' => $meta,
            'links' => [
                'first' => $users->url(1),
                'last' => $users->url($users->lastPage()),
                'prev' => $users->previousPageUrl(),
                'next' => $users->nextPageUrl(),
            ]
        ]);
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

        if ($request->filled('role')) {
            $user->assignRole($request->role);
        }

        $user->load('roles');
        $user->role_names = $user->roles->pluck('name');
        $user->primary_role = $user->roles->first()?->name;

        return $this->created($user, 'User created successfully');
    }

    /**
     * GET /api/v1/users/{user}
     * Show a single user by ID.
     */
    public function show(User $user): JsonResponse
    {
        $user->load('roles');
        $user->role_names = $user->roles->pluck('name');
        $user->primary_role = $user->roles->first()?->name;
        $user->available_roles = $this->getAvailableRoles();
        
        return $this->success($user, 'User retrieved successfully');
    }

    /**
     * PATCH /api/v1/users/{user}
     * Update an existing user.
     */
    public function update(UserRequest $request, User $user): JsonResponse
    {
        $user->update($request->validated());

        if ($request->filled('role')) {
            $user->syncRoles([$request->role]);
        }

        $user->load('roles');
        $user->role_names = $user->roles->pluck('name');
        $user->primary_role = $user->roles->first()?->name;

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

    /**
     * Get available roles (excluding super_admin)
     */
    private function getAvailableRoles(): array
    {
        $roles = Role::where('name', '!=', 'super_admin')
                     ->orderBy('name')
                     ->get();
        
        return $roles->map(fn($role) => [
            'id' => $role->id,
            'name' => $role->name,
            'guard_name' => $role->guard_name
        ])->toArray();
    }


    /**
     * Get user statistics grouped by roles
     */
    private function getRoleStats(): array
    {
        $stats = [];
        
        // Get all roles (including all, not just non-super_admin for stats)
        $roles = Role::all();
        
        foreach ($roles as $role) {
            // Direct count using the relationship
            $count = DB::table('model_has_roles')
                ->where('role_id', $role->id)
                ->count();
            
            $stats[$role->name] = $count;
        }
        
        // Count users without any role
        $stats['no_role'] = User::whereDoesntHave('roles')->count();
        
        // Log for debugging - check your laravel.log file
        \Log::info('Role stats calculated (direct DB count):', $stats);
        
        return $stats;
    }

    /**
     * Get all roles for dropdown (excluding super_admin)
     */
    public function getRoles(): JsonResponse
    {
        $roles = Role::where('name', '!=', 'super_admin')
                     ->orderBy('name')
                     ->get()
                     ->map(fn($role) => [
                         'id' => $role->id,
                         'name' => $role->name,
                         'guard_name' => $role->guard_name
                     ]);
        
        return response()->json(['data' => $roles]);
    }

    public function list(): JsonResponse
    {
        $users = User::select('id', 'first_name', 'last_name', 'email')
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