<?php
// app/Models/PaymentPlanWeb.php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Casts\Attribute;

class PaymentPlanWeb extends Model
{
    protected $table = 'payment_plan_webs';

    protected $fillable = [
        'audience',
        'name',
        'display_name',
        'description',
        'price_usd',
        'billing_period',
        'features',
        'local_prices',
        'is_active',
        'is_popular',
        'badge_text',
        'sort_order',
    ];

    protected $casts = [
        'features' => 'array',
        'local_prices' => 'array',
        'price_usd' => 'decimal:2',
        'is_active' => 'boolean',
        'is_popular' => 'boolean',
        'sort_order' => 'integer',
    ];

    // Accessor for formatted price in given currency
    public function getLocalPrice(string $currency = 'USD'): float
    {
        $localPrices = $this->local_prices ?? [];
        $rate = $this->getExchangeRate($currency);
        
        if (isset($localPrices[$currency])) {
            return (float) $localPrices[$currency];
        }
        
        return round($this->price_usd * $rate, 0);
    }

    private function getExchangeRate(string $currency): float
    {
        $rates = [
            'UGX' => 3750, 'KES' => 130, 'TZS' => 2600,
            'RWF' => 1300, 'NGN' => 1500, 'ZAR' => 19,
            'GBP' => 0.8, 'EUR' => 0.92, 'USD' => 1,
        ];
        return $rates[$currency] ?? 1;
    }

    // Scope for seekers
    public function scopeForSeeker($query)
    {
        return $query->where('audience', 'seeker');
    }

    // Scope for employers
    public function scopeForEmployer($query)
    {
        return $query->where('audience', 'employer');
    }

    // Scope for active plans
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }
}