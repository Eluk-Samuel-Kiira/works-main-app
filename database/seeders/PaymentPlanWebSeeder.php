<?php
// database/seeders/PaymentPlanWebSeeder.php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\PaymentPlanWeb;

class PaymentPlanWebSeeder extends Seeder
{
    private const CURRENCY_COUNTRIES = [
        'UGX' => ['UG'],
        'KES' => ['KE'],
        'TZS' => ['TZ'],
        'RWF' => ['RW'],
        'NGN' => ['NG'],
        'ZAR' => ['ZA', 'LS', 'SZ', 'NA'],
        'USD' => ['US', 'SS', 'ZW', 'LR'],
        'GBP' => ['GB'],
        'EUR' => ['DE', 'FR', 'IT', 'ES', 'NL', 'BE', 'PT', 'AT', 'IE', 'FI', 'GR'],
    ];

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

    public function run(): void
    {
        $this->command->info('Seeding payment plans...');

        $seekerPlans = $this->getSeekerPlans();
        $employerPlans = $this->getEmployerPlans();

        $allPlans = array_merge($seekerPlans, $employerPlans);

        foreach ($allPlans as $plan) {
            PaymentPlanWeb::updateOrCreate(
                ['name' => $plan['name']],
                $plan
            );
        }

        $this->command->info('Seeded ' . count($allPlans) . ' payment plans.');
    }

    private function getSeekerPlans(): array
    {
        return [
            [
                'audience' => 'seeker',
                'name' => 'seeker_basic',
                'display_name' => 'Basic',
                'description' => 'Perfect for job seekers starting out',
                'price_usd' => 5.00,
                'billing_period' => 'monthly',
                'is_popular' => false,
                'badge_text' => null,
                'sort_order' => 1,
                'features' => [
                    '5 CV reviews per month',
                    'Advanced ATS score check',
                    '10 cover letters per month',
                    'Missing keyword analysis',
                    'Email delivery of results',
                    'Email support',
                ],
                'local_prices' => [
                    'UGX' => 18750, 'KES' => 650, 'TZS' => 13000,
                    'RWF' => 6500, 'NGN' => 7500, 'ZAR' => 95,
                    'GBP' => 4, 'EUR' => 5,
                ],
            ],
            [
                'audience' => 'seeker',
                'name' => 'seeker_pro',
                'display_name' => 'Pro',
                'description' => 'For serious job seekers',
                'price_usd' => 12.00,
                'billing_period' => 'monthly',
                'is_popular' => true,
                'badge_text' => '🔥 MOST POPULAR',
                'sort_order' => 2,
                'features' => [
                    'Unlimited CV reviews',
                    'Unlimited CV rewriting & revamping',
                    'Advanced ATS score + keyword gaps',
                    'Unlimited cover letters',
                    'CV vs Job Description match analysis',
                    'AI-Powered Job Recommendations',
                    'Interview preparation guide',
                    'Priority support',
                    'PDF download of rewritten CV',
                ],
                'local_prices' => [
                    'UGX' => 45000, 'KES' => 1560, 'TZS' => 31200,
                    'RWF' => 15600, 'NGN' => 18000, 'ZAR' => 228,
                    'GBP' => 10, 'EUR' => 12,
                ],
            ],
            [
                'audience' => 'seeker',
                'name' => 'seeker_elite',
                'display_name' => 'Elite',
                'description' => 'For job seekers with purpose',
                'price_usd' => 49.00,
                'billing_period' => 'yearly',
                'is_popular' => false,
                'badge_text' => '💎 BEST VALUE',
                'sort_order' => 3,
                'features' => [
                    'Everything in Pro — unlimited',
                    'Aptitude test preparation',
                    'Interview scripts & mock Q&A',
                    'LinkedIn profile optimisation tips',
                    'Salary negotiation guide',
                    'Dedicated career advisor (email)',
                    '30-day money-back guarantee',
                ],
                'local_prices' => [
                    'UGX' => 184000, 'KES' => 6370, 'TZS' => 127400,
                    'RWF' => 63700, 'NGN' => 73500, 'ZAR' => 931,
                    'GBP' => 39, 'EUR' => 45,
                ],
            ],
        ];
    }

    private function getEmployerPlans(): array
    {
        return [
            [
                'audience' => 'employer',
                'name' => 'employer_starter',
                'display_name' => 'Starter',
                'description' => 'Post jobs and attract quality candidates',
                'price_usd' => 15.00,
                'billing_period' => 'monthly',
                'is_popular' => false,
                'badge_text' => null,
                'sort_order' => 1,
                'features' => [
                    '3 active job postings',
                    'Standard listing placement',
                    'Applicant management dashboard',
                    'Email notifications for applications',
                    'Basic candidate profiles',
                    '30 days per listing',
                ],
                'local_prices' => [
                    'UGX' => 56250, 'KES' => 1950, 'TZS' => 39000,
                    'RWF' => 19500, 'NGN' => 22500, 'ZAR' => 285,
                    'GBP' => 12, 'EUR' => 14,
                ],
            ],
            [
                'audience' => 'employer',
                'name' => 'employer_pro',
                'display_name' => 'Pro',
                'description' => 'For growing companies hiring regularly',
                'price_usd' => 35.00,
                'billing_period' => 'monthly',
                'is_popular' => true,
                'badge_text' => '🔥 MOST POPULAR',
                'sort_order' => 2,
                'features' => [
                    '10 active job postings',
                    'Featured listing — top of search results',
                    'AI CV matching — ranked candidates',
                    'Access to full candidate CV profiles',
                    'Advanced applicant filtering',
                    'Applicant shortlisting tools',
                    '45 days per listing',
                    'Priority support',
                ],
                'local_prices' => [
                    'UGX' => 131250, 'KES' => 4550, 'TZS' => 91000,
                    'RWF' => 45500, 'NGN' => 52500, 'ZAR' => 665,
                    'GBP' => 28, 'EUR' => 33,
                ],
            ],
            [
                'audience' => 'employer',
                'name' => 'employer_elite',
                'display_name' => 'Elite',
                'description' => 'For enterprises and high-volume hiring',
                'price_usd' => 99.00,
                'billing_period' => 'monthly',
                'is_popular' => false,
                'badge_text' => '💎 ENTERPRISE',
                'sort_order' => 3,
                'features' => [
                    'Unlimited active job postings',
                    'Featured + Urgent listing badges',
                    'AI CV matching with ranked shortlist',
                    'Full candidate CV download access',
                    'Bulk applicant management',
                    'Company profile verification badge',
                    'Dedicated account manager',
                    '60 days per listing',
                    'Analytics dashboard',
                ],
                'local_prices' => [
                    'UGX' => 371250, 'KES' => 12870, 'TZS' => 257400,
                    'RWF' => 128700, 'NGN' => 148500, 'ZAR' => 1881,
                    'GBP' => 79, 'EUR' => 92,
                ],
            ],
            // Featured listing add-ons (one-time)
            [
                'audience' => 'employer',
                'name' => 'featured_week',
                'display_name' => 'Featured — 7 Days',
                'description' => 'Boost any single job listing to featured for 7 days',
                'price_usd' => 13.00,
                'billing_period' => 'one_time',
                'is_popular' => false,
                'badge_text' => null,
                'sort_order' => 4,
                'features' => [
                    'Featured badge on listing',
                    'Top placement in search results',
                    'Highlighted in job alerts',
                    '7 days of featured exposure',
                ],
                'local_prices' => [
                    'UGX' => 50000, 'KES' => 1690, 'TZS' => 33800,
                    'RWF' => 16900, 'NGN' => 19500, 'ZAR' => 247,
                    'GBP' => 10, 'EUR' => 12,
                ],
            ],
            [
                'audience' => 'employer',
                'name' => 'featured_21days',
                'display_name' => 'Featured — 21 Days',
                'description' => 'Maximum exposure for your job listing for 3 weeks',
                'price_usd' => 32.00,
                'billing_period' => 'one_time',
                'is_popular' => false,
                'badge_text' => '⭐ BEST FOR URGENT ROLES',
                'sort_order' => 5,
                'features' => [
                    'Featured + Urgent badge on listing',
                    'Top placement in search results',
                    'Priority in job alerts & emails',
                    'Social media promotion',
                    '21 days of featured exposure',
                ],
                'local_prices' => [
                    'UGX' => 130000, 'KES' => 4160, 'TZS' => 83200,
                    'RWF' => 41600, 'NGN' => 48000, 'ZAR' => 608,
                    'GBP' => 26, 'EUR' => 30,
                ],
            ],
            [
                'audience' => 'employer',
                'name' => 'featured_40days',
                'display_name' => 'Featured — 40 Days',
                'description' => 'Long-term visibility for hard-to-fill positions',
                'price_usd' => 55.00,
                'billing_period' => 'one_time',
                'is_popular' => false,
                'badge_text' => '🚀 MAXIMUM REACH',
                'sort_order' => 6,
                'features' => [
                    'Featured + Urgent badge on listing',
                    'Top placement in search results',
                    'Priority in job alerts & weekly digest',
                    'Social media promotion (3 posts)',
                    'AI candidate matching report',
                    '40 days of featured exposure',
                ],
                'local_prices' => [
                    'UGX' => 250000, 'KES' => 7150, 'TZS' => 143000,
                    'RWF' => 71500, 'NGN' => 82500, 'ZAR' => 1045,
                    'GBP' => 44, 'EUR' => 52,
                ],
            ],
        ];
    }

    public static function getCurrencyMeta(): array
    {
        return self::CURRENCIES;
    }

    public static function getCurrencyForCountry(string $countryCode): string
    {
        foreach (self::CURRENCY_COUNTRIES as $currency => $countries) {
            if (in_array($countryCode, $countries)) {
                return $currency;
            }
        }
        return 'USD';
    }
}