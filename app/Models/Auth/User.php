<?php

namespace App\Models\Auth;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Str;
use Spatie\Permission\Traits\HasRoles;
use Spatie\Permission\Models\Role; // Import Spatie's Role model
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    use HasFactory, Notifiable, SoftDeletes, HasRoles, HasApiTokens;
    protected $guard_name = 'web';

    protected $fillable = [
        'uuid',
        'email',
        'first_name',
        'last_name',
        'phone',
        'role_id',
        'email_verified_at',
        'magic_link_token',
        'magic_link_sent_at',
        'magic_link_expires_at',
        'country_code',
        'is_active',
        'last_login_at',
    ];

    protected $hidden = [
        'magic_link_token',
    ];

    protected $casts = [
        'email_verified_at' => 'datetime',
        'magic_link_sent_at' => 'datetime',
        'magic_link_expires_at' => 'datetime',
        'last_login_at' => 'datetime',
        'is_active' => 'boolean',
    ];

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($model) {
            if (empty($model->uuid)) {
                $model->uuid = Str::uuid();
            }
        });
        
        // Sync Spatie roles with role_id when saving
        static::saved(function ($model) {
            if ($model->role_id) {
                $role = Role::find($model->role_id);
                if ($role && !$model->hasRole($role->name)) {
                    // Remove all existing roles and assign the primary one
                    $model->syncRoles([$role->name]);
                }
            }
        });
    }

    // Add relationship to Spatie's Role model
    public function role()
    {
        return $this->belongsTo(Role::class, 'role_id');
    }

    public function getFullNameAttribute()
    {
        return "{$this->first_name} {$this->last_name}";
    }

    // Role-based helper methods using role_id for quick checks
    public function isSuperAdmin()
    {
        return $this->role_id && $this->role && $this->role->name === 'super_admin';
    }

    public function isAdmin()
    {
        return $this->role_id && $this->role && in_array($this->role->name, ['super_admin', 'admin']);
    }

    public function isEmployer()
    {
        return $this->role_id && $this->role && $this->role->name === 'employer';
    }

    public function isJobSeeker()
    {
        return $this->role_id && $this->role && $this->role->name === 'job_seeker';
    }

    public function isModerator()
    {
        return $this->role_id && $this->role && $this->role->name === 'moderator';
    }

    public function isSupport()
    {
        return $this->role_id && $this->role && $this->role->name === 'support';
    }

    // Also keep Spatie-based checks for completeness
    public function hasSpatieRole($role)
    {
        return $this->hasRole($role);
    }

    // User type compatibility methods
    public function isEmployee()
    {
        return $this->isJobSeeker();
    }

    public function isInternee()
    {
        return $this->isJobSeeker();
    }

    public function isVolunteer()
    {
        return $this->isJobSeeker();
    }

    // Magic link methods
    public function isMagicLinkValid()
    {
        return $this->magic_link_token && 
               $this->magic_link_expires_at && 
               $this->magic_link_expires_at->isFuture();
    }

    // Assign primary role and sync with Spatie
    public function assignPrimaryRole($roleId)
    {
        $role = Role::find($roleId);
        
        if ($role) {
            $this->role_id = $roleId;
            $this->save();
            
            // Sync Spatie roles
            $this->syncRoles([$role->name]);
        }
        
        return $this;
    }

    // Scope methods using role_id (faster)
    public function scopeSuperAdmins($query)
    {
        return $query->whereHas('role', function ($q) {
            $q->where('name', 'super_admin');
        });
    }

    public function scopeAdmins($query)
    {
        return $query->whereHas('role', function ($q) {
            $q->whereIn('name', ['super_admin', 'admin']);
        });
    }

    public function scopeEmployers($query)
    {
        return $query->whereHas('role', function ($q) {
            $q->where('name', 'employer');
        });
    }

    public function scopeJobSeekers($query)
    {
        return $query->whereHas('role', function ($q) {
            $q->where('name', 'job_seeker');
        });
    }

    public function scopeModerators($query)
    {
        return $query->whereHas('role', function ($q) {
            $q->where('name', 'moderator');
        });
    }

    public function scopeSupport($query)
    {
        return $query->whereHas('role', function ($q) {
            $q->where('name', 'support');
        });
    }
}