<?php
// MAIN APP: app/Http/Controllers/Api/Payments/PaymentPlanWebController.php

namespace App\Http\Controllers\Api\Payments;

use App\Http\Concerns\ApiResponse;
use App\Http\Controllers\Controller;
use App\Models\PaymentPlanWeb;
use Illuminate\Http\Request;
use App\Helpers\SubscriptionHelper;

class PaymentPlanWebController extends Controller
{
    use ApiResponse;

    public function getPlans(Request $request)
    {
        $plans = PaymentPlanWeb::where('is_active', true)
            ->orderBy('sort_order')
            ->get();

        $currency = $request->get('currency', 'USD');
        
        $formattedPlans = $plans->map(function($plan) use ($currency) {
            return [
                'id' => $plan->id,
                'name' => $plan->name,
                'display_name' => $plan->display_name,
                'description' => $plan->description,
                'price_usd' => $plan->price_usd,
                'local_price' => $plan->getLocalPrice($currency),
                'billing_period' => $plan->billing_period,
                'features' => $plan->features ?? [],
                'is_popular' => $plan->is_popular,
                'badge_text' => $plan->badge_text,
                'savings_text' => $plan->billing_period === 'yearly' ? 'Save up to 40%' : null,
            ];
        });

        return $this->success([
            'plans' => $formattedPlans,
            'supported_currencies' => $this->getSupportedCurrencies(),
        ]);
    }

    private function getSupportedCurrencies(): array
    {
        return [
            ['code' => 'USD', 'symbol' => '$', 'name' => 'US Dollar', 'flag' => '🇺🇸'],
            ['code' => 'UGX', 'symbol' => 'USh', 'name' => 'Uganda Shilling', 'flag' => '🇺🇬', 'country_code' => 'UG'],
            ['code' => 'KES', 'symbol' => 'KSh', 'name' => 'Kenya Shilling', 'flag' => '🇰🇪', 'country_code' => 'KE'],
            ['code' => 'TZS', 'symbol' => 'TSh', 'name' => 'Tanzania Shilling', 'flag' => '🇹🇿', 'country_code' => 'TZ'],
            ['code' => 'RWF', 'symbol' => 'FRw', 'name' => 'Rwanda Franc', 'flag' => '🇷🇼', 'country_code' => 'RW'],
            ['code' => 'NGN', 'symbol' => '₦', 'name' => 'Nigerian Naira', 'flag' => '🇳🇬', 'country_code' => 'NG'],
            ['code' => 'ZAR', 'symbol' => 'R', 'name' => 'South African Rand', 'flag' => '🇿🇦', 'country_code' => 'ZA'],
        ];
    }

    public function status(Request $request)
    {
        $user = $request->user();
        $subscription = SubscriptionHelper::getActiveSubscription($user->id);

        return $this->success([
            'has_active_subscription' => $subscription['has_active'],
            'plan' => $subscription['plan'],
            'period' => $subscription['period'],
            'expiry_date' => $subscription['expiry_date'],
            'amount' => $subscription['amount'],
            'currency' => $subscription['currency'],
        ]);
    }
}