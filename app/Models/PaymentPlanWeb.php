<?php
// app/Models/PaymentPlanWeb.php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PaymentPlanWeb extends Model
{
    protected $fillable = [
        'audience', 'name', 'display_name', 'description',
        'price_usd', 'billing_period', 'is_popular', 'badge_text',
        'sort_order', 'features', 'local_prices', 'is_trial', 'trial_days'
    ];

    protected $casts = [
        'features' => 'array',
        'local_prices' => 'array',
        'is_popular' => 'boolean',
        'is_trial' => 'boolean',
        'trial_days' => 'integer',
        'price_usd' => 'float',
        'sort_order' => 'integer',
    ];

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function getLocalPrice(string $currency): float
    {
        $prices = $this->local_prices ?? [];
        return (float) ($prices[$currency] ?? $this->price_usd);
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

}