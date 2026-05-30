<?php

namespace App\Http\Controllers\Api\Payments;

use App\Http\Concerns\ApiResponse;
use App\Http\Controllers\Controller;
use App\Models\PaymentPlanWeb;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use App\Helpers\SubscriptionHelper;

class PaymentPlanWebController extends Controller
{
    use ApiResponse;
    
    private const CURRENCIES = [
        'UGX' => ['symbol' => 'UGX', 'name' => 'Uganda Shilling',    'flag' => '🇺🇬', 'country_code' => 'UG'],
        'KES' => ['symbol' => 'KSh', 'name' => 'Kenyan Shilling',     'flag' => '🇰🇪', 'country_code' => 'KE'],
        'TZS' => ['symbol' => 'TSh', 'name' => 'Tanzanian Shilling',  'flag' => '🇹🇿', 'country_code' => 'TZ'],
        'RWF' => ['symbol' => 'FRw', 'name' => 'Rwandan Franc',       'flag' => '🇷🇼', 'country_code' => 'RW'],
        'NGN' => ['symbol' => '₦',   'name' => 'Nigerian Naira',      'flag' => '🇳🇬', 'country_code' => 'NG'],
        'ZAR' => ['symbol' => 'R',   'name' => 'South African Rand',  'flag' => '🇿🇦', 'country_code' => 'ZA'],
        'USD' => ['symbol' => '$',   'name' => 'US Dollar',           'flag' => '🇺🇸', 'country_code' => 'US'],
        'GBP' => ['symbol' => '£',   'name' => 'British Pound',       'flag' => '🇬🇧', 'country_code' => 'GB'],
        'EUR' => ['symbol' => '€',   'name' => 'Euro',                'flag' => '🇪🇺', 'country_code' => 'EU'],
    ];

    private const COUNTRY_CURRENCY = [
        'UG' => 'UGX', 'KE' => 'KES', 'TZ' => 'TZS', 'RW' => 'RWF',
        'NG' => 'NGN', 'ZA' => 'ZAR', 'LS' => 'ZAR', 'SZ' => 'ZAR',
        'US' => 'USD', 'GB' => 'GBP',
        'DE' => 'EUR', 'FR' => 'EUR', 'IT' => 'EUR', 'ES' => 'EUR',
        'NL' => 'EUR', 'BE' => 'EUR', 'PT' => 'EUR', 'AT' => 'EUR',
        'IE' => 'EUR', 'FI' => 'EUR',
    ];

    public function getPlans(Request $request): \Illuminate\Http\JsonResponse
    {
        $audience = $request->get('audience', 'seeker');
        $currency = strtoupper($request->get('currency', 'USD'));
        $ip = $request->ip();

        // Auto-detect currency if not specified
        if ($currency === 'USD' && !$request->has('currency')) {
            $currency = $this->detectCurrencyFromIp($ip);
        }

        if (!isset(self::CURRENCIES[$currency])) {
            $currency = 'USD';
        }

        $currencyMeta = self::CURRENCIES[$currency];

        // Load plans from database
        $plans = PaymentPlanWeb::active()
            ->where('audience', $audience)
            ->orderBy('sort_order')
            ->get();

        $formattedPlans = $plans->map(function ($plan) use ($currency, $currencyMeta) {
            return [
                'name' => $plan->name,
                'display_name' => $plan->display_name,
                'description' => $plan->description,
                'price_usd' => (float) $plan->price_usd,
                'billing_period' => $plan->billing_period,
                'local_price' => $plan->getLocalPrice($currency),
                'currency' => $currency,
                'currency_symbol' => $currencyMeta['symbol'],
                'features' => $plan->features,
                'is_popular' => (bool) $plan->is_popular,
                'badge_text' => $plan->badge_text,
                'sort_order' => (int) $plan->sort_order,
            ];
        });

        $supportedCurrencies = collect(self::CURRENCIES)->map(function ($meta, $code) {
            return array_merge(['code' => $code], $meta);
        })->values();

        return response()->json([
            'success' => true,
            'data' => [
                'plans' => $formattedPlans,
                'supported_currencies' => $supportedCurrencies,
                'detected_currency' => array_merge(['code' => $currency], $currencyMeta),
                'audience' => $audience,
            ],
        ]);
    }

    private function detectCurrencyFromIp(string $ip): string
    {
        if (in_array($ip, ['127.0.0.1', '::1', 'localhost'])) {
            return 'UGX';
        }

        $cacheKey = 'geo_currency_' . md5($ip);

        return Cache::remember($cacheKey, 3600, function () use ($ip) {
            // Try ipapi.co first
            try {
                $response = Http::timeout(3)->get("https://ipapi.co/{$ip}/json/");
                if ($response->successful()) {
                    $countryCode = $response->json('country_code');
                    if ($countryCode && isset(self::COUNTRY_CURRENCY[$countryCode])) {
                        return self::COUNTRY_CURRENCY[$countryCode];
                    }
                }
            } catch (\Exception $e) {
                Log::debug('Geo detection failed (ipapi.co): ' . $e->getMessage());
            }

            // Fallback to ip-api.com
            try {
                $response = Http::timeout(3)->get("http://ip-api.com/json/{$ip}?fields=countryCode");
                if ($response->successful()) {
                    $countryCode = $response->json('countryCode');
                    if ($countryCode && isset(self::COUNTRY_CURRENCY[$countryCode])) {
                        return self::COUNTRY_CURRENCY[$countryCode];
                    }
                }
            } catch (\Exception $e) {
                Log::debug('Geo detection failed (ip-api.com): ' . $e->getMessage());
            }

            return 'USD';
        });
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