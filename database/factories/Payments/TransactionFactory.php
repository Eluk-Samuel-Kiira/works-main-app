<?php
// database/factories/TransactionFactory.php

namespace Database\Factories\Payments;

use App\Models\Job\{ Company  };
use App\Models\Auth\{ User };
use App\Models\Payments\{ PaymentPlan, Transaction };
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

class TransactionFactory extends Factory
{

    protected $model = Transaction::class;

    public function definition()
    {
        $gateway = $this->faker->randomElement(['flutterwave', 'stripe', 'paypal']);
        $status = $this->faker->randomElement(['pending', 'processing', 'successful', 'failed']);
        
        return [
            'uuid' => Str::uuid(),
            'user_id' => User::inRandomOrder()->first()->id ?? User::factory()->create()->id,
            'company_id' => Company::inRandomOrder()->first()->id ?? Company::factory()->create()->id,
            'plan_id' => PaymentPlan::inRandomOrder()->first()->id ?? PaymentPlan::factory()->create()->id,
            'reference' => 'TXN-' . strtoupper(Str::random(3)) . '-' . time() . '-' . Str::random(6),
            'gateway_reference' => $this->generateGatewayReference($gateway),
            'transaction_type' => $this->faker->randomElement(['job_post', 'featured_job', 'company_verification', 'premium_profile']),
            'amount' => $this->faker->numberBetween(10000, 500000),
            'gateway_fee' => $this->faker->numberBetween(100, 5000),
            'currency' => 'UGX',
            'status' => $status,
            'payment_gateway' => $gateway,
            'payment_method' => $this->generatePaymentMethod($gateway),
            'payment_channel' => $this->generatePaymentChannel($gateway),
            'gateway_request' => $this->generateGatewayRequest($gateway),
            'gateway_response' => $this->generateGatewayResponse($gateway, $status),
            'gateway_webhook' => $status === 'successful' ? $this->generateWebhookData($gateway) : null,
            'gateway_status' => $this->getGatewayStatus($status),
            'gateway_message' => $this->getGatewayMessage($status),
            'customer_email' => $this->faker->companyEmail(),
            'customer_phone' => '+256 7' . $this->faker->numerify('## ### ###'),
            'customer_name' => $this->faker->name(),
            'billing_address' => $this->generateAddress(),
            'ip_address' => $this->faker->ipv4,
            'user_agent' => $this->faker->userAgent,
            'device_fingerprint' => Str::random(32),
            'metadata' => $this->generateMetadata(),
            'custom_fields' => $this->generateCustomFields(),
            'processed_at' => $status !== 'pending' ? $this->faker->dateTimeBetween('-30 days', 'now') : null,
            'confirmed_at' => $status === 'successful' ? $this->faker->dateTimeBetween('-30 days', 'now') : null,
        ];
    }

    // State Methods
    public function successful()
    {
        return $this->state(function (array $attributes) {
            return [
                'status' => 'successful',
                'gateway_status' => 'success',
                'gateway_message' => 'Payment completed successfully',
                'processed_at' => $this->faker->dateTimeBetween('-30 days', 'now'),
                'confirmed_at' => $this->faker->dateTimeBetween('-30 days', 'now'),
            ];
        });
    }

    public function pending()
    {
        return $this->state(function (array $attributes) {
            return [
                'status' => 'pending',
                'gateway_status' => 'pending',
                'gateway_message' => 'Payment is being processed',
                'processed_at' => null,
                'confirmed_at' => null,
            ];
        });
    }

    public function processing()
    {
        return $this->state(function (array $attributes) {
            return [
                'status' => 'processing',
                'gateway_status' => 'processing',
                'gateway_message' => 'Payment is being verified',
                'processed_at' => now(),
                'confirmed_at' => null,
            ];
        });
    }

    public function failed()
    {
        return $this->state(function (array $attributes) {
            return [
                'status' => 'failed',
                'gateway_status' => 'failed',
                'gateway_message' => $this->faker->randomElement(['Insufficient funds', 'Card declined', 'Network error']),
                'processed_at' => $this->faker->dateTimeBetween('-30 days', 'now'),
                'confirmed_at' => null,
                'retry_count' => $this->faker->numberBetween(1, 2),
                'last_retry_at' => $this->faker->dateTimeBetween('-1 day', 'now'),
            ];
        });
    }

    public function refunded()
    {
        return $this->state(function (array $attributes) {
            return [
                'status' => 'refunded',
                'gateway_status' => 'refunded',
                'gateway_message' => 'Payment refunded to customer',
                'refunded_at' => $this->faker->dateTimeBetween('-7 days', 'now'),
            ];
        });
    }

    public function disputed()
    {
        return $this->state(function (array $attributes) {
            return [
                'status' => 'disputed',
                'gateway_status' => 'disputed',
                'gateway_message' => 'Payment under dispute',
                'is_flagged' => true,
                'flag_reason' => 'Customer initiated chargeback',
                'disputed_at' => $this->faker->dateTimeBetween('-3 days', 'now'),
            ];
        });
    }

    public function flutterwave()
    {
        return $this->state(function (array $attributes) {
            return [
                'payment_gateway' => 'flutterwave',
                'payment_method' => $this->faker->randomElement(['card', 'mobile_money', 'bank_transfer']),
                'payment_channel' => $this->faker->randomElement(['visa', 'mastercard', 'mtn', 'airtel']),
            ];
        });
    }

    public function stripe()
    {
        return $this->state(function (array $attributes) {
            return [
                'payment_gateway' => 'stripe',
                'payment_method' => 'card',
                'payment_channel' => $this->faker->randomElement(['visa', 'mastercard']),
            ];
        });
    }

    public function paypal()
    {
        return $this->state(function (array $attributes) {
            return [
                'payment_gateway' => 'paypal',
                'payment_method' => 'paypal',
                'payment_channel' => 'paypal',
            ];
        });
    }

    public function mobileMoney()
    {
        return $this->state(function (array $attributes) {
            return [
                'payment_gateway' => 'flutterwave',
                'payment_method' => 'mobile_money',
                'payment_channel' => $this->faker->randomElement(['mtn', 'airtel']),
            ];
        });
    }

    public function flagged()
    {
        return $this->state(function (array $attributes) {
            return [
                'is_flagged' => true,
                'flag_reason' => $this->faker->randomElement([
                    'Suspicious transaction pattern',
                    'High risk customer',
                    'Multiple failed attempts',
                    'Amount above threshold'
                ]),
            ];
        });
    }

    // Helper Methods
    private function generateGatewayReference($gateway)
    {
        $prefixes = [
            'flutterwave' => 'FLW',
            'stripe' => 'STR',
            'paypal' => 'PPL'
        ];

        return $prefixes[$gateway] . '_' . Str::random(12);
    }

    private function generatePaymentMethod($gateway)
    {
        $methods = [
            'flutterwave' => ['card', 'mobile_money', 'bank_transfer', 'ussd'],
            'stripe' => ['card', 'bank_transfer'],
            'paypal' => ['paypal', 'card']
        ];

        return $this->faker->randomElement($methods[$gateway]);
    }

    private function generatePaymentChannel($gateway)
    {
        $channels = [
            'flutterwave' => ['visa', 'mastercard', 'verve', 'mtn', 'airtel', 'mpesa'],
            'stripe' => ['visa', 'mastercard', 'american_express'],
            'paypal' => ['paypal']
        ];

        return $this->faker->randomElement($channels[$gateway]);
    }

    private function generateGatewayRequest($gateway)
    {
        $requests = [
            'flutterwave' => [
                'tx_ref' => $this->faker->bothify('FLW-########'),
                'amount' => $this->faker->numberBetween(10000, 500000),
                'currency' => 'UGX',
                'payment_options' => 'card,mobilemoney,ussd',
                'redirect_url' => $this->faker->url,
                'customer' => [
                    'email' => $this->faker->email,
                    'phonenumber' => $this->faker->phoneNumber,
                    'name' => $this->faker->name
                ],
                'customizations' => [
                    'title' => 'Job Post Payment',
                    'description' => 'Payment for job posting services',
                    'logo' => $this->faker->imageUrl
                ]
            ],
            'stripe' => [
                'amount' => $this->faker->numberBetween(10000, 500000),
                'currency' => 'ugx',
                'payment_method_types' => ['card'],
                'customer' => 'cus_' . Str::random(14),
                'description' => 'Job Post Payment'
            ],
            'paypal' => [
                'intent' => 'sale',
                'payer' => [
                    'payment_method' => 'paypal'
                ],
                'transactions' => [
                    [
                        'amount' => [
                            'total' => $this->faker->numberBetween(10000, 500000) / 100,
                            'currency' => 'USD'
                        ],
                        'description' => 'Job Post Payment'
                    ]
                ],
                'redirect_urls' => [
                    'return_url' => $this->faker->url,
                    'cancel_url' => $this->faker->url
                ]
            ]
        ];

        return $requests[$gateway];
    }

    private function generateGatewayResponse($gateway, $status)
    {
        $responses = [
            'successful' => [
                'status' => 'successful',
                'message' => 'Transaction completed successfully',
                'transaction_id' => $this->generateGatewayReference($gateway),
                'authorization_code' => 'AUTH_' . Str::random(8),
            ],
            'pending' => [
                'status' => 'pending',
                'message' => 'Transaction is being processed',
                'transaction_id' => $this->generateGatewayReference($gateway),
            ],
            'failed' => [
                'status' => 'failed',
                'message' => 'Transaction failed',
                'error_code' => $this->faker->randomElement(['insufficient_funds', 'card_declined', 'network_error']),
            ]
        ];

        return $responses[$status] ?? [];
    }

    private function generateWebhookData($gateway)
    {
        $webhooks = [
            'flutterwave' => [
                'event' => 'charge.completed',
                'data' => [
                    'id' => $this->generateGatewayReference($gateway),
                    'tx_ref' => $this->faker->bothify('FLW-########'),
                    'flw_ref' => 'FLW-M03-' . Str::random(10),
                    'device_fingerprint' => Str::random(32),
                    'amount' => $this->faker->numberBetween(10000, 500000),
                    'currency' => 'UGX',
                    'charged_amount' => $this->faker->numberBetween(10000, 500000),
                    'app_fee' => $this->faker->numberBetween(100, 5000),
                    'merchant_fee' => 0,
                    'processor_response' => 'Approved',
                    'auth_model' => 'PIN',
                    'ip' => $this->faker->ipv4,
                    'narration' => 'Job Post Payment',
                    'status' => 'successful',
                    'payment_type' => 'card',
                    'created_at' => now()->toISOString(),
                    'account_id' => $this->faker->numberBetween(1000, 9999),
                    'customer' => [
                        'id' => $this->faker->numberBetween(1000, 9999),
                        'name' => $this->faker->name,
                        'phone_number' => $this->faker->phoneNumber,
                        'email' => $this->faker->email,
                        'created_at' => now()->toISOString()
                    ]
                ]
            ],
            'stripe' => [
                'id' => 'evt_' . Str::random(14),
                'object' => 'event',
                'api_version' => '2023-10-16',
                'created' => time(),
                'data' => [
                    'object' => [
                        'id' => 'pi_' . Str::random(14),
                        'object' => 'payment_intent',
                        'amount' => $this->faker->numberBetween(10000, 500000),
                        'amount_received' => $this->faker->numberBetween(10000, 500000),
                        'currency' => 'ugx',
                        'status' => 'succeeded',
                        'customer' => 'cus_' . Str::random(14),
                        'payment_method' => 'pm_' . Str::random(14),
                        'created' => time()
                    ]
                ],
                'type' => 'payment_intent.succeeded'
            ]
        ];

        return $webhooks[$gateway] ?? [];
    }

    private function getGatewayStatus($status)
    {
        $mapping = [
            'pending' => 'pending',
            'processing' => 'processing',
            'successful' => 'success',
            'failed' => 'failed',
            'cancelled' => 'cancelled',
            'refunded' => 'refunded',
            'disputed' => 'disputed'
        ];

        return $mapping[$status];
    }

    private function getGatewayMessage($status)
    {
        $messages = [
            'pending' => 'Payment is being processed',
            'processing' => 'Payment is being verified',
            'successful' => 'Payment completed successfully',
            'failed' => 'Payment failed',
            'cancelled' => 'Payment cancelled by user',
            'refunded' => 'Payment refunded to customer',
            'disputed' => 'Payment under dispute'
        ];

        return $messages[$status];
    }

    private function generateAddress()
    {
        return [
            'street' => $this->faker->streetAddress,
            'city' => $this->faker->city,
            'state' => $this->faker->state,
            'postal_code' => $this->faker->postcode,
            'country' => 'Uganda'
        ];
    }

    private function generateMetadata()
    {
        return [
            'ip_address' => $this->faker->ipv4,
            'user_agent' => $this->faker->userAgent,
            'device_type' => $this->faker->randomElement(['desktop', 'mobile', 'tablet']),
            'browser' => $this->faker->randomElement(['chrome', 'firefox', 'safari', 'edge']),
            'os' => $this->faker->randomElement(['Windows', 'macOS', 'Linux', 'iOS', 'Android']),
            'screen_resolution' => $this->faker->randomElement(['1920x1080', '1366x768', '1536x864']),
            'timezone' => $this->faker->timezone,
        ];
    }

    private function generateCustomFields()
    {
        return [
            'job_id' => $this->faker->optional()->numberBetween(1, 1000),
            'campaign_id' => $this->faker->optional()->bothify('CAM-#####'),
            'affiliate_id' => $this->faker->optional()->bothify('AFF-#####'),
            'utm_source' => $this->faker->optional()->randomElement(['google', 'facebook', 'direct']),
            'utm_medium' => $this->faker->optional()->randomElement(['cpc', 'organic', 'email']),
            'utm_campaign' => $this->faker->optional()->bothify('job-post-#####'),
        ];
    }
}