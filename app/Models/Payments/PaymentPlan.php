<?php

namespace App\Models\Payments;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\Auth\{ User };
use App\Models\Job\{ Company  };

class PaymentPlan extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'type',
        'country_code',
        'amount',
        'currency',
        'duration_days',
        'features',
        'description',
        'is_active',
        'is_popular',
        'sort_order',
        'stripe_price_id',
        'flutterwave_plan_id'
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'is_active' => 'boolean',
        'is_popular' => 'boolean',
        'features' => 'array'
    ];

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($plan) {
            if (empty($plan->currency)) {
                $plan->currency = 'UGX';
            }
            if (empty($plan->country_code)) {
                $plan->country_code = 'UG';
            }
        });
    }

    // Relationships
    public function transactions()
    {
        return $this->hasMany(Transaction::class);
    }

    // Scopes
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopePopular($query)
    {
        return $query->where('is_popular', true);
    }

    public function scopeByType($query, $type)
    {
        return $query->where('type', $type);
    }

    public function scopeForCountry($query, $countryCode = 'UG')
    {
        return $query->where('country_code', $countryCode);
    }

    public function scopeJobPosts($query)
    {
        return $query->where('type', 'job_post');
    }

    public function scopeFeaturedJobs($query)
    {
        return $query->where('type', 'featured_job');
    }

    public function scopeCompanyVerification($query)
    {
        return $query->where('type', 'company_verification');
    }

    public function scopePremiumProfiles($query)
    {
        return $query->where('type', 'premium_profile');
    }

    // Accessors
    public function getFormattedAmountAttribute()
    {
        return $this->currency . ' ' . number_format($this->amount);
    }

    public function getDailyCostAttribute()
    {
        if (!$this->duration_days) return $this->amount;
        return $this->amount / $this->duration_days;
    }

    public function getFeaturesListAttribute()
    {
        return $this->features ? implode(', ', $this->features) : '';
    }

    // Methods
    public function isRecurring()
    {
        return !is_null($this->duration_days);
    }

    public function getPriceForDisplay()
    {
        if ($this->amount == 0) {
            return 'Free';
        }
        return $this->formatted_amount . ($this->duration_days ? ' / ' . $this->duration_days . ' days' : '');
    }
}
