<?php

namespace Database\Factories\Auth;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;
use Spatie\Permission\Models\Role;

class UserFactory extends Factory
{
    public function definition()
    {
        // Get or create roles if they don't exist
        $this->ensureRolesExist();
        
        return [
            'uuid' => Str::uuid(),
            'email' => $this->faker->unique()->safeEmail(),
            'first_name' => $this->faker->firstName(),
            'last_name' => $this->faker->lastName(),
            'phone' => $this->faker->phoneNumber(),
            'role_id' => Role::where('name', 'job_seeker')->first()->id,
            'email_verified_at' => now(),
            'magic_link_token' => null,
            'magic_link_sent_at' => null,
            'magic_link_expires_at' => null,
            'country_code' => 'UG',
            'is_active' => $this->faker->boolean(90), // 90% chance of being active
            'last_login_at' => $this->faker->optional(0.7)->dateTimeBetween('-30 days', 'now'),
        ];
    }

    /**
     * Ensure all required roles exist
     */
    protected function ensureRolesExist()
    {
        $roles = ['super_admin', 'admin', 'employer', 'job_seeker', 'moderator', 'support'];
        
        foreach ($roles as $roleName) {
            if (!Role::where('name', $roleName)->exists()) {
                Role::create(['name' => $roleName, 'guard_name' => 'web']);
            }
        }
    }

    /**
     * Configure the model factory.
     */
    public function configure()
    {
        return $this->afterCreating(function (User $user) {
            // Sync the user's Spatie roles with their primary role
            if ($user->role_id && $user->primaryRole) {
                $user->syncRoles([$user->primaryRole->name]);
            }
        });
    }

    public function unverified()
    {
        return $this->state(function (array $attributes) {
            return [
                'email_verified_at' => null,
            ];
        });
    }

    public function superAdmin()
    {
        return $this->state(function (array $attributes) {
            $role = Role::firstOrCreate(['name' => 'super_admin', 'guard_name' => 'web']);
            return [
                'role_id' => $role->id,
            ];
        });
    }

    public function admin()
    {
        return $this->state(function (array $attributes) {
            $role = Role::firstOrCreate(['name' => 'admin', 'guard_name' => 'web']);
            return [
                'role_id' => $role->id,
            ];
        });
    }

    public function employer()
    {
        return $this->state(function (array $attributes) {
            $role = Role::firstOrCreate(['name' => 'employer', 'guard_name' => 'web']);
            return [
                'role_id' => $role->id,
            ];
        });
    }

    public function jobSeeker()
    {
        return $this->state(function (array $attributes) {
            $role = Role::firstOrCreate(['name' => 'job_seeker', 'guard_name' => 'web']);
            return [
                'role_id' => $role->id,
            ];
        });
    }

    public function moderator()
    {
        return $this->state(function (array $attributes) {
            $role = Role::firstOrCreate(['name' => 'moderator', 'guard_name' => 'web']);
            return [
                'role_id' => $role->id,
            ];
        });
    }

    public function support()
    {
        return $this->state(function (array $attributes) {
            $role = Role::firstOrCreate(['name' => 'support', 'guard_name' => 'web']);
            return [
                'role_id' => $role->id,
            ];
        });
    }

    // Keep the original user_type methods for backward compatibility
    // but map them to appropriate roles
    public function internee()
    {
        return $this->jobSeeker(); // Map internee to job_seeker role
    }

    public function volunteer()
    {
        return $this->jobSeeker(); // Map volunteer to job_seeker role
    }

    public function employee()
    {
        return $this->jobSeeker(); // Map employee to job_seeker role
    }

    public function inactive()
    {
        return $this->state(function (array $attributes) {
            return [
                'is_active' => false,
            ];
        });
    }

    public function withMagicLink()
    {
        return $this->state(function (array $attributes) {
            return [
                'magic_link_token' => Str::random(60),
                'magic_link_sent_at' => now(),
                'magic_link_expires_at' => now()->addHours(24),
            ];
        });
    }

    /**
     * Create a user with a specific role ID
     */
    public function withRoleId($roleId)
    {
        return $this->state(function (array $attributes) use ($roleId) {
            return [
                'role_id' => $roleId,
            ];
        });
    }

    /**
     * Create a user with a specific role name
     */
    public function withRoleName($roleName)
    {
        $role = Role::firstOrCreate(['name' => $roleName, 'guard_name' => 'web']);
        
        return $this->state(function (array $attributes) use ($role) {
            return [
                'role_id' => $role->id,
            ];
        });
    }
}