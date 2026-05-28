<?php
// MAIN APP: database/seeders/PaymentPlanWebSeeder.php

namespace Database\Seeders;

use App\Models\PaymentPlanWeb;
use Illuminate\Database\Seeder;

class PaymentPlanWebSeeder extends Seeder
{
    public function run(): void
    {
        $plans = [
            [
                'name' => 'basic',
                'display_name' => 'Basic',
                'description' => 'Perfect for job seekers starting out',
                'price_usd' => 5,
                'billing_period' => 'monthly',
                'features' => [
                    '5 CV reviews per month',
                    'Advanced ATS score check',
                    '10 cover letters/month',
                    'Email support',
                ],
                'local_prices' => [
                    'UGX' => 18856,
                    'KES' => 650,
                    'TZS' => 13000,
                    'RWF' => 6500,
                    'NGN' => 7500,
                    'ZAR' => 95,
                ],
                'sort_order' => 1,
                'is_popular' => false,
                'badge_text' => null,
            ],
            [
                'name' => 'pro',
                'display_name' => 'Pro',
                'description' => 'For serious job seekers',
                'price_usd' => 12,
                'billing_period' => 'monthly',
                'features' => [
                    'Unlimited CV reviews',
                    'Unlimited CV rewriting + revamping',
                    'Advanced ATS score + keywords',
                    'Unlimited cover letters',
                    'Priority support',
                    'AI-Powered Job Recommendations',
                    'Interview preparation guide & tutorials',
                ],
                'local_prices' => [
                    'UGX' => 45255,
                    'KES' => 1560,
                    'TZS' => 31200,
                    'RWF' => 15600,
                    'NGN' => 18000,
                    'ZAR' => 228,
                ],
                'sort_order' => 2,
                'is_popular' => true,
                'badge_text' => '🔥 MOST POPULAR',
            ],
            [
                'name' => 'elite',
                'display_name' => 'Elite',
                'description' => 'For job seekers with purpose',
                'price_usd' => 49,
                'billing_period' => 'yearly',
                'features' => [
                    'Unlimited CV reviews',
                    'Unlimited CV rewriting + revamping',
                    'Advanced ATS score + keywords',
                    'Unlimited cover letters',
                    'Priority support',
                    'Interview preparation guide & tutorials',
                    'AI-Powered Job Recommendations',
                    'Aptitude test preparation',
                ],
                'local_prices' => [
                    'UGX' => 184791,
                    'KES' => 6370,
                    'TZS' => 127400,
                    'RWF' => 63700,
                    'NGN' => 73500,
                    'ZAR' => 931,
                ],
                'sort_order' => 3,
                'is_popular' => false,
                'badge_text' => null,
            ],
        ];

        foreach ($plans as $plan) {
            PaymentPlanWeb::updateOrCreate(['name' => $plan['name']], $plan);
        }
    }
}