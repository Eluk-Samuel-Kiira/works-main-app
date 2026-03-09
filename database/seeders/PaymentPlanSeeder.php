<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\Payments\PaymentPlan;
use Illuminate\Support\Str;

class PaymentPlanSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Clear existing payment plans if needed (optional)
        // PaymentPlan::truncate();

        // UGX Payment Plans for Uganda
        $ugxPlans = [
            // Job Post Plans
            [
                'name' => 'Basic Job Post',
                'type' => 'job_post',
                'country_code' => 'UG',
                'amount' => 35000,
                'currency' => 'UGX',
                'duration_days' => 14,
                'features' => json_encode([
                    '14-day job listing',
                    'Basic search visibility',
                    'Up to 50 applications',
                    'Email notifications'
                ]),
                'description' => 'Perfect for small businesses looking to fill entry-level positions quickly.',
                'is_active' => true,
                'is_popular' => false,
                'sort_order' => 1,
                'stripe_price_id' => 'price_basic_job_' . Str::random(8),
                'flutterwave_plan_id' => 'PLN_basic_' . Str::random(6),
            ],
            [
                'name' => 'Standard Job Post',
                'type' => 'job_post',
                'country_code' => 'UG',
                'amount' => 75000,
                'currency' => 'UGX',
                'duration_days' => 30,
                'features' => json_encode([
                    '30-day job listing',
                    'Enhanced search visibility',
                    'Unlimited applications',
                    'Email and SMS notifications',
                    'Applicant tracking'
                ]),
                'description' => 'Our most popular job posting option for medium-sized businesses.',
                'is_active' => true,
                'is_popular' => true,
                'sort_order' => 2,
                'stripe_price_id' => 'price_std_job_' . Str::random(8),
                'flutterwave_plan_id' => 'PLN_std_' . Str::random(6),
            ],
            [
                'name' => 'Premium Job Post',
                'type' => 'job_post',
                'country_code' => 'UG',
                'amount' => 150000,
                'currency' => 'UGX',
                'duration_days' => 60,
                'features' => json_encode([
                    '60-day job listing',
                    'Premium search placement',
                    'Social media promotion',
                    'Email and SMS notifications',
                    'Advanced applicant analytics',
                    'Priority support'
                ]),
                'description' => 'Maximum exposure for hard-to-fill positions and executive roles.',
                'is_active' => true,
                'is_popular' => false,
                'sort_order' => 3,
                'stripe_price_id' => 'price_prem_job_' . Str::random(8),
                'flutterwave_plan_id' => 'PLN_prem_' . Str::random(6),
            ],

            // Featured Job Plans
            [
                'name' => 'Featured Job - 7 Days',
                'type' => 'featured_job',
                'country_code' => 'UG',
                'amount' => 100000,
                'currency' => 'UGX',
                'duration_days' => 7,
                'features' => json_encode([
                    'Top of search results',
                    'Highlighted with featured badge',
                    'Homepage feature for 24 hours',
                    'Email newsletter inclusion',
                    'Social media mention'
                ]),
                'description' => 'Short-term boost for urgent hiring needs.',
                'is_active' => true,
                'is_popular' => false,
                'sort_order' => 4,
                'stripe_price_id' => 'price_feat_7d_' . Str::random(8),
                'flutterwave_plan_id' => 'PLN_feat7_' . Str::random(6),
            ],
            [
                'name' => 'Featured Job - 14 Days',
                'type' => 'featured_job',
                'country_code' => 'UG',
                'amount' => 180000,
                'currency' => 'UGX',
                'duration_days' => 14,
                'features' => json_encode([
                    'Top of search results',
                    'Highlighted with featured badge',
                    'Homepage rotation',
                    'Email newsletter inclusion',
                    'Multiple social media posts',
                    'Push notifications to job seekers'
                ]),
                'description' => 'Extended visibility for critical positions.',
                'is_active' => true,
                'is_popular' => true,
                'sort_order' => 5,
                'stripe_price_id' => 'price_feat_14d_' . Str::random(8),
                'flutterwave_plan_id' => 'PLN_feat14_' . Str::random(6),
            ],
            [
                'name' => 'Featured Job - 30 Days',
                'type' => 'featured_job',
                'country_code' => 'UG',
                'amount' => 300000,
                'currency' => 'UGX',
                'duration_days' => 30,
                'features' => json_encode([
                    'Top of search results',
                    'Highlighted with featured badge',
                    'Homepage featured slot',
                    'Weekly email newsletter features',
                    'Social media campaign',
                    'Push notifications to relevant job seekers',
                    'Featured in job alerts'
                ]),
                'description' => 'Maximum visibility for executive and high-volume positions.',
                'is_active' => true,
                'is_popular' => false,
                'sort_order' => 6,
                'stripe_price_id' => 'price_feat_30d_' . Str::random(8),
                'flutterwave_plan_id' => 'PLN_feat30_' . Str::random(6),
            ],

            // Company Verification Plans
            [
                'name' => 'Company Verification',
                'type' => 'company_verification',
                'country_code' => 'UG',
                'amount' => 120000,
                'currency' => 'UGX',
                'duration_days' => 365,
                'features' => json_encode([
                    'Verified badge on profile',
                    'Enhanced credibility indicator',
                    'Priority in search results',
                    'Trust score boost',
                    'Verified employer listing'
                ]),
                'description' => 'Build trust with job seekers through official verification.',
                'is_active' => true,
                'is_popular' => true,
                'sort_order' => 7,
                'stripe_price_id' => 'price_verify_' . Str::random(8),
                'flutterwave_plan_id' => 'PLN_verify_' . Str::random(6),
            ],

            // Premium Profile Plans
            [
                'name' => 'Premium Profile - 3 Months',
                'type' => 'premium_profile',
                'country_code' => 'UG',
                'amount' => 200000,
                'currency' => 'UGX',
                'duration_days' => 90,
                'features' => json_encode([
                    'Enhanced company profile',
                    'Up to 5 active job slots',
                    'Basic analytics dashboard',
                    'Applicant management system',
                    'Email support'
                ]),
                'description' => 'Everything you need to establish your brand on our platform.',
                'is_active' => true,
                'is_popular' => false,
                'sort_order' => 8,
                'stripe_price_id' => 'price_prem_3m_' . Str::random(8),
                'flutterwave_plan_id' => 'PLN_prem3m_' . Str::random(6),
            ],
            [
                'name' => 'Premium Profile - 6 Months',
                'type' => 'premium_profile',
                'country_code' => 'UG',
                'amount' => 350000,
                'currency' => 'UGX',
                'duration_days' => 180,
                'features' => json_encode([
                    'Enhanced company profile with gallery',
                    'Up to 15 active job slots',
                    'Advanced analytics dashboard',
                    'Applicant tracking system',
                    'Candidate database access',
                    'Priority email and phone support'
                ]),
                'description' => 'Our most popular premium package for active recruiters.',
                'is_active' => true,
                'is_popular' => true,
                'sort_order' => 9,
                'stripe_price_id' => 'price_prem_6m_' . Str::random(8),
                'flutterwave_plan_id' => 'PLN_prem6m_' . Str::random(6),
            ],
            [
                'name' => 'Premium Profile - 1 Year',
                'type' => 'premium_profile',
                'country_code' => 'UG',
                'amount' => 600000,
                'currency' => 'UGX',
                'duration_days' => 365,
                'features' => json_encode([
                    'Enhanced company profile with branding',
                    'Unlimited active job slots',
                    'Enterprise analytics dashboard',
                    'Advanced applicant tracking system',
                    'Full candidate database access',
                    'API access for integrations',
                    'Dedicated account manager',
                    '24/7 priority support'
                ]),
                'description' => 'Complete solution for enterprise-level recruitment needs.',
                'is_active' => true,
                'is_popular' => false,
                'sort_order' => 10,
                'stripe_price_id' => 'price_prem_1y_' . Str::random(8),
                'flutterwave_plan_id' => 'PLN_prem1y_' . Str::random(6),
            ],
        ];

        // KES Payment Plans for Kenya
        $kesPlans = [
            // Job Post Plans
            [
                'name' => 'Basic Job Post',
                'type' => 'job_post',
                'country_code' => 'KE',
                'amount' => 1500,
                'currency' => 'KES',
                'duration_days' => 14,
                'features' => json_encode([
                    '14-day job listing',
                    'Basic search visibility',
                    'Up to 50 applications',
                    'Email notifications'
                ]),
                'description' => 'Perfect for small businesses looking to fill entry-level positions quickly.',
                'is_active' => true,
                'is_popular' => false,
                'sort_order' => 11,
                'stripe_price_id' => 'price_kes_basic_' . Str::random(8),
                'flutterwave_plan_id' => 'PLN_kes_basic_' . Str::random(6),
            ],
            [
                'name' => 'Standard Job Post',
                'type' => 'job_post',
                'country_code' => 'KE',
                'amount' => 3500,
                'currency' => 'KES',
                'duration_days' => 30,
                'features' => json_encode([
                    '30-day job listing',
                    'Enhanced search visibility',
                    'Unlimited applications',
                    'Email and SMS notifications',
                    'Applicant tracking'
                ]),
                'description' => 'Our most popular job posting option for medium-sized businesses.',
                'is_active' => true,
                'is_popular' => true,
                'sort_order' => 12,
                'stripe_price_id' => 'price_kes_std_' . Str::random(8),
                'flutterwave_plan_id' => 'PLN_kes_std_' . Str::random(6),
            ],
            [
                'name' => 'Premium Job Post',
                'type' => 'job_post',
                'country_code' => 'KE',
                'amount' => 6500,
                'currency' => 'KES',
                'duration_days' => 60,
                'features' => json_encode([
                    '60-day job listing',
                    'Premium search placement',
                    'Social media promotion',
                    'Email and SMS notifications',
                    'Advanced applicant analytics',
                    'Priority support'
                ]),
                'description' => 'Maximum exposure for hard-to-fill positions and executive roles.',
                'is_active' => true,
                'is_popular' => false,
                'sort_order' => 13,
                'stripe_price_id' => 'price_kes_prem_' . Str::random(8),
                'flutterwave_plan_id' => 'PLN_kes_prem_' . Str::random(6),
            ],

            // Featured Job Plans
            [
                'name' => 'Featured Job - 7 Days',
                'type' => 'featured_job',
                'country_code' => 'KE',
                'amount' => 4500,
                'currency' => 'KES',
                'duration_days' => 7,
                'features' => json_encode([
                    'Top of search results',
                    'Highlighted with featured badge',
                    'Homepage feature for 24 hours',
                    'Email newsletter inclusion',
                    'Social media mention'
                ]),
                'description' => 'Short-term boost for urgent hiring needs.',
                'is_active' => true,
                'is_popular' => false,
                'sort_order' => 14,
                'stripe_price_id' => 'price_kes_feat7_' . Str::random(8),
                'flutterwave_plan_id' => 'PLN_kes_feat7_' . Str::random(6),
            ],
            [
                'name' => 'Featured Job - 14 Days',
                'type' => 'featured_job',
                'country_code' => 'KE',
                'amount' => 8000,
                'currency' => 'KES',
                'duration_days' => 14,
                'features' => json_encode([
                    'Top of search results',
                    'Highlighted with featured badge',
                    'Homepage rotation',
                    'Email newsletter inclusion',
                    'Multiple social media posts',
                    'Push notifications to job seekers'
                ]),
                'description' => 'Extended visibility for critical positions.',
                'is_active' => true,
                'is_popular' => true,
                'sort_order' => 15,
                'stripe_price_id' => 'price_kes_feat14_' . Str::random(8),
                'flutterwave_plan_id' => 'PLN_kes_feat14_' . Str::random(6),
            ],
            [
                'name' => 'Featured Job - 30 Days',
                'type' => 'featured_job',
                'country_code' => 'KE',
                'amount' => 15000,
                'currency' => 'KES',
                'duration_days' => 30,
                'features' => json_encode([
                    'Top of search results',
                    'Highlighted with featured badge',
                    'Homepage featured slot',
                    'Weekly email newsletter features',
                    'Social media campaign',
                    'Push notifications to relevant job seekers',
                    'Featured in job alerts'
                ]),
                'description' => 'Maximum visibility for executive and high-volume positions.',
                'is_active' => true,
                'is_popular' => false,
                'sort_order' => 16,
                'stripe_price_id' => 'price_kes_feat30_' . Str::random(8),
                'flutterwave_plan_id' => 'PLN_kes_feat30_' . Str::random(6),
            ],

            // Company Verification Plans
            [
                'name' => 'Company Verification',
                'type' => 'company_verification',
                'country_code' => 'KE',
                'amount' => 5000,
                'currency' => 'KES',
                'duration_days' => 365,
                'features' => json_encode([
                    'Verified badge on profile',
                    'Enhanced credibility indicator',
                    'Priority in search results',
                    'Trust score boost',
                    'Verified employer listing'
                ]),
                'description' => 'Build trust with job seekers through official verification.',
                'is_active' => true,
                'is_popular' => true,
                'sort_order' => 17,
                'stripe_price_id' => 'price_kes_verify_' . Str::random(8),
                'flutterwave_plan_id' => 'PLN_kes_verify_' . Str::random(6),
            ],

            // Premium Profile Plans
            [
                'name' => 'Premium Profile - 3 Months',
                'type' => 'premium_profile',
                'country_code' => 'KE',
                'amount' => 9000,
                'currency' => 'KES',
                'duration_days' => 90,
                'features' => json_encode([
                    'Enhanced company profile',
                    'Up to 5 active job slots',
                    'Basic analytics dashboard',
                    'Applicant management system',
                    'Email support'
                ]),
                'description' => 'Everything you need to establish your brand on our platform.',
                'is_active' => true,
                'is_popular' => false,
                'sort_order' => 18,
                'stripe_price_id' => 'price_kes_prem3m_' . Str::random(8),
                'flutterwave_plan_id' => 'PLN_kes_prem3m_' . Str::random(6),
            ],
            [
                'name' => 'Premium Profile - 6 Months',
                'type' => 'premium_profile',
                'country_code' => 'KE',
                'amount' => 16000,
                'currency' => 'KES',
                'duration_days' => 180,
                'features' => json_encode([
                    'Enhanced company profile with gallery',
                    'Up to 15 active job slots',
                    'Advanced analytics dashboard',
                    'Applicant tracking system',
                    'Candidate database access',
                    'Priority email and phone support'
                ]),
                'description' => 'Our most popular premium package for active recruiters.',
                'is_active' => true,
                'is_popular' => true,
                'sort_order' => 19,
                'stripe_price_id' => 'price_kes_prem6m_' . Str::random(8),
                'flutterwave_plan_id' => 'PLN_kes_prem6m_' . Str::random(6),
            ],
            [
                'name' => 'Premium Profile - 1 Year',
                'type' => 'premium_profile',
                'country_code' => 'KE',
                'amount' => 28000,
                'currency' => 'KES',
                'duration_days' => 365,
                'features' => json_encode([
                    'Enhanced company profile with branding',
                    'Unlimited active job slots',
                    'Enterprise analytics dashboard',
                    'Advanced applicant tracking system',
                    'Full candidate database access',
                    'API access for integrations',
                    'Dedicated account manager',
                    '24/7 priority support'
                ]),
                'description' => 'Complete solution for enterprise-level recruitment needs.',
                'is_active' => true,
                'is_popular' => false,
                'sort_order' => 20,
                'stripe_price_id' => 'price_kes_prem1y_' . Str::random(8),
                'flutterwave_plan_id' => 'PLN_kes_prem1y_' . Str::random(6),
            ],
        ];

        // Merge both arrays and create records
        $paymentPlans = array_merge($ugxPlans, $kesPlans);

        foreach ($paymentPlans as $plan) {
            PaymentPlan::create($plan);
        }

        $this->command->info('====================================');
        $this->command->info('PAYMENT PLAN SEEDER COMPLETED SUCCESSFULLY!');
        $this->command->info('====================================');
        $this->command->info('Total payment plans created: ' . count($paymentPlans));
        $this->command->info('UGX Plans: ' . count($ugxPlans));
        $this->command->info('KES Plans: ' . count($kesPlans));
        $this->command->info('====================================');
        $this->command->info('Breakdown by type:');
        $this->command->info('- Job Post Plans: ' . $this->countByType($paymentPlans, 'job_post'));
        $this->command->info('- Featured Job Plans: ' . $this->countByType($paymentPlans, 'featured_job'));
        $this->command->info('- Company Verification Plans: ' . $this->countByType($paymentPlans, 'company_verification'));
        $this->command->info('- Premium Profile Plans: ' . $this->countByType($paymentPlans, 'premium_profile'));
        $this->command->info('====================================');
    }

    /**
     * Helper method to count plans by type
     */
    private function countByType($plans, $type)
    {
        return count(array_filter($plans, function($plan) use ($type) {
            return $plan['type'] === $type;
        }));
    }
}