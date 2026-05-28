<?php
// MAIN APP: app/Models/PaymentPlanWeb.php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PaymentPlanWeb extends Model
{
    use HasFactory;

    protected $table = 'payment_plan_webs';

    protected $fillable = [
        'name', 'display_name', 'description', 'price_usd',
        'billing_period', 'features', 'local_prices', 'sort_order',
        'is_active', 'is_popular', 'badge_text'
    ];

    protected $casts = [
        'features' => 'array',
        'local_prices' => 'array',
        'price_usd' => 'decimal:2',
        'is_active' => 'boolean',
        'is_popular' => 'boolean',
    ];

    public function getLocalPrice(string $currencyCode): float
    {
        $localPrices = $this->local_prices ?? [];
        return $localPrices[$currencyCode] ?? $this->price_usd;
    }
}