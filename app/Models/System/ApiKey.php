<?php

namespace App\Models\System;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Models\Auth\User;
use Illuminate\Support\Facades\Crypt;

class ApiKey extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'api_keys';

    protected $fillable = [
        'name',
        'service',
        'provider',
        'key',
        'secret',
        'endpoint',
        'version',
        'config',
        'permissions',
        'rate_limits',
        'usage_quota',
        'usage_count',
        'last_used_at',
        'expires_at',
        'is_active',
        'is_default',
        'environment',
        'notes',
        'created_by',
    ];

    protected $casts = [
        'config' => 'array',
        'permissions' => 'array',
        'rate_limits' => 'array',
        'usage_quota' => 'array',
        'is_active' => 'boolean',
        'is_default' => 'boolean',
        'expires_at' => 'datetime',
        'last_used_at' => 'datetime',
    ];

    protected $hidden = [
        'key',
        'secret',
    ];

    /**
     * Encrypt the API key when saving
     */
    public function setKeyAttribute($value)
    {
        $this->attributes['key'] = $value ? Crypt::encryptString($value) : null;
    }

    /**
     * Decrypt the API key when accessing
     */
    public function getKeyAttribute($value)
    {
        return $value ? Crypt::decryptString($value) : null;
    }

    /**
     * Encrypt the secret when saving
     */
    public function setSecretAttribute($value)
    {
        $this->attributes['secret'] = $value ? Crypt::encryptString($value) : null;
    }

    /**
     * Decrypt the secret when accessing
     */
    public function getSecretAttribute($value)
    {
        return $value ? Crypt::decryptString($value) : null;
    }

    /**
     * Get the user who created this API key
     */
    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Get the logs for this API key
     */
    public function logs()
    {
        return $this->hasMany(ApiLog::class);
    }

    /**
     * Scope active keys
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true)
            ->where(function ($q) {
                $q->whereNull('expires_at')
                  ->orWhere('expires_at', '>', now());
            });
    }

    /**
     * Scope by service
     */
    public function scopeService($query, $service)
    {
        return $query->where('service', $service);
    }

    /**
     * Scope by environment
     */
    public function scopeEnvironment($query, $environment)
    {
        return $query->where('environment', $environment);
    }

    /**
     * Get the default key for a service
     */
    public static function getDefaultForService($service, $environment = 'production')
    {
        return self::active()
            ->where('service', $service)
            ->where('environment', $environment)
            ->where('is_default', true)
            ->first();
    }

    /**
     * Check if key has permission
     */
    public function hasPermission($permission)
    {
        if (!$this->permissions) {
            return true; // No restrictions
        }
        
        return in_array($permission, $this->permissions) || in_array('*', $this->permissions);
    }

    /**
     * Check if key can be used (rate limits, quota)
     */
    public function canUse(): bool
    {
        if (!$this->is_active) {
            return false;
        }

        if ($this->expires_at && $this->expires_at->isPast()) {
            return false;
        }

        // Check rate limits
        if ($this->rate_limits && isset($this->rate_limits['max_per_day'])) {
            $todayUsage = $this->logs()
                ->whereDate('created_at', today())
                ->count();
            
            if ($todayUsage >= $this->rate_limits['max_per_day']) {
                return false;
            }
        }

        // Check usage quota
        if ($this->usage_quota && isset($this->usage_quota['max_total'])) {
            if ($this->usage_count >= $this->usage_quota['max_total']) {
                return false;
            }
        }

        return true;
    }

    /**
     * Increment usage count
     */
    public function incrementUsage()
    {
        $this->increment('usage_count');
        $this->update(['last_used_at' => now()]);
    }

    /**
     * Get service display name
     */
    public function getServiceDisplayAttribute(): string
    {
        $services = [
            'openai' => 'OpenAI',
            'anthropic' => 'Claude (Anthropic)',
            'stripe' => 'Stripe',
            'google' => 'Google',
            'aws' => 'AWS',
            'azure' => 'Azure',
            'sendgrid' => 'SendGrid',
            'twilio' => 'Twilio',
            'paypal' => 'PayPal',
            'flutterwave' => 'Flutterwave',
            'paystack' => 'Paystack',
            'mapbox' => 'Mapbox',
            'algolia' => 'Algolia',
            'elasticsearch' => 'Elasticsearch',
            'redis' => 'Redis',
            's3' => 'AWS S3',
        ];

        return $services[$this->service] ?? ucfirst($this->service);
    }
}