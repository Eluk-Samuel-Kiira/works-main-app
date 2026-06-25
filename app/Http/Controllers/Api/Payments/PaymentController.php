<?php
// MAIN APP: app/Http/Controllers/Api/Payments/PaymentController.php
// Fixed version — uses subscription_plan/period instead of plan_id FK

namespace App\Http\Controllers\Api\Payments;

use App\Http\Concerns\ApiResponse;
use App\Http\Controllers\Controller;
use App\Models\Payments\Transaction;
use App\Services\PesapalService;
use Illuminate\Http\{Request, JsonResponse};
use Illuminate\Support\Facades\{Log, DB};

class PaymentController extends Controller
{
    use ApiResponse;

    public function __construct(private PesapalService $pesapal) {}

    // ─────────────────────────────────────────────────────────────────────
    // POST /api/v1/payments/initiate
    // ─────────────────────────────────────────────────────────────────────

    public function initiate(Request $request): JsonResponse
    {
        Log::info('[Payment] Initiate called', ['user_id' => $request->user()->id]);

        $validated = $request->validate([
            'plan'         => 'required|string|in:seeker_basic,seeker_pro,seeker_elite',
            'period'       => 'required|string|in:monthly,yearly',
            'amount_usd'   => 'required|numeric|min:1',
            'currency'     => 'required|string|size:3',
            'amount_local' => 'required|numeric|min:1',
            'first_name'   => 'required|string|max:100',
            'last_name'    => 'required|string|max:100',
            'email'        => 'required|email',
            'phone'        => 'nullable|string|max:20',
            'country_code' => 'nullable|string|max:3',
        ]);

        $user = $request->user();

        return DB::transaction(function () use ($validated, $user) {
            $transaction = Transaction::create([
                'user_id'             => $user->id,
                'transaction_type'    => 'subscription',
                'subscription_plan'   => $validated['plan'],
                'subscription_period' => $validated['period'],
                'amount'              => $validated['amount_local'],
                'currency'            => $validated['currency'],
                'status'              => 'pending',
                'payment_gateway'     => 'pesapal',
                'customer_email'      => $validated['email'],
                'customer_phone'      => $validated['phone'] ?? null,
                'customer_name'       => trim($validated['first_name'] . ' ' . $validated['last_name']),
                'metadata'            => [
                    'plan'       => $validated['plan'],
                    'period'     => $validated['period'],
                    'amount_usd' => $validated['amount_usd'],
                ],
            ]);

            Log::info('[Payment] Transaction created', [
                'reference' => $transaction->reference,
                'user_id'   => $user->id,
            ]);

            // Build callback URL
            $callbackUrl = rtrim(config('pesapal.callback_url'), '/')
                . '?reference=' . urlencode($transaction->reference);

            $payload = [
                'id'               => $transaction->reference,
                'currency'         => $validated['currency'],
                'amount'           => (float) $validated['amount_local'],
                'description'      => 'Stardena Works ' . ucfirst($validated['plan']) . ' Plan',
                'callback_url'     => $callbackUrl,
                'cancellation_url' => config('pesapal.cancellation_url'),
                'billing_address'  => [
                    'email_address' => $validated['email'],
                    'phone_number'  => $validated['phone'] ?? '',
                    'country_code'  => $validated['country_code'] ?? 'UG',
                    'first_name'    => $validated['first_name'],
                    'last_name'     => $validated['last_name'],
                ],
            ];

            try {
                $pesapalResponse = $this->pesapal->submitOrder($payload);
                
                // Validate response before updating
                if (empty($pesapalResponse['redirect_url'])) {
                    throw new \Exception('No redirect URL received from Pesapal');
                }

                $transaction->update([
                    'gateway_reference' => $pesapalResponse['order_tracking_id'],
                    'gateway_request'   => $payload,
                    'gateway_response'  => $pesapalResponse,
                    'status'            => 'processing',
                ]);

                return response()->json([
                    'success'            => true,
                    'redirect_url'       => $pesapalResponse['redirect_url'],
                    'order_tracking_id'  => $pesapalResponse['order_tracking_id'],
                    'merchant_reference' => $transaction->reference,
                ]);

            } catch (\Exception $e) {
                Log::error('[Payment] Pesapal order submission failed', [
                    'error' => $e->getMessage(),
                    'reference' => $transaction->reference
                ]);
                
                $transaction->update([
                    'status' => 'failed',
                    'error_message' => $e->getMessage()
                ]);
                
                return $this->error('Payment initialization failed: ' . $e->getMessage(), 500);
            }
        });
    }

    // ─────────────────────────────────────────────────────────────────────
    // GET /api/v1/payments/callback
    // ─────────────────────────────────────────────────────────────────────
    public function callback(Request $request): JsonResponse
    {
        $orderTrackingId   = $request->get('OrderTrackingId');
        $merchantReference = $request->get('OrderMerchantReference');

        Log::info('[Payment] Callback received', compact('orderTrackingId', 'merchantReference'));

        if (!$orderTrackingId || !$merchantReference) {
            return $this->error('Missing callback parameters', 400);
        }

        $transaction = Transaction::where('reference', $merchantReference)->first();

        if (!$transaction) {
            Log::warning('[Payment] Callback: transaction not found', ['reference' => $merchantReference]);
            return $this->error('Transaction not found', 404);
        }

        $statusData = $this->pesapal->getTransactionStatus($orderTrackingId);
        $this->applyStatus($transaction, $statusData);

        $transaction->refresh();

        return response()->json([
            'success'            => true,
            'status'             => $transaction->status,
            'payment_status'     => $statusData['payment_status_description'] ?? 'Unknown',
            'amount'             => $transaction->formatted_amount,
            'plan'               => $transaction->subscription_plan,
            'period'             => $transaction->subscription_period,
            'confirmation_code'  => $statusData['confirmation_code'] ?? null,
            'merchant_reference' => $merchantReference,
        ]);
    }

    // ─────────────────────────────────────────────────────────────────────
    // POST|GET /api/v1/payments/ipn  — no auth, Pesapal calls this
    // ─────────────────────────────────────────────────────────────────────
    public function ipn(Request $request): JsonResponse
    {
        $orderTrackingId   = $request->get('OrderTrackingId');
        $merchantReference = $request->get('OrderMerchantReference');
        $notificationType  = $request->get('OrderNotificationType');

        Log::info('[Payment] IPN received', compact('orderTrackingId', 'merchantReference', 'notificationType'));

        $ack = [
            'orderNotificationType'  => $notificationType,
            'orderTrackingId'        => $orderTrackingId,
            'orderMerchantReference' => $merchantReference,
        ];

        $transaction = Transaction::where('reference', $merchantReference)->first();

        if (!$transaction) {
            Log::warning('[Payment] IPN: transaction not found', ['reference' => $merchantReference]);
            return response()->json([...$ack, 'status' => 500]);
        }

        $transaction->update(['gateway_webhook' => $request->all()]);

        try {
            $statusData = $this->pesapal->getTransactionStatus($orderTrackingId);
            $this->applyStatus($transaction, $statusData);
        } catch (\Exception $e) {
            Log::error('[Payment] IPN status query failed', ['error' => $e->getMessage()]);
        }

        return response()->json([...$ack, 'status' => 200]);
    }



    // ─────────────────────────────────────────────────────────────────────
    // Map Pesapal status_code to our status
    // 0=INVALID, 1=COMPLETED, 2=FAILED, 3=REVERSED
    // ─────────────────────────────────────────────────────────────────────
    private function applyStatus(Transaction $transaction, array $data): void
    {
        $code = (int)($data['status_code'] ?? -1);

        Log::info('[Payment] Applying status', [
            'reference'   => $transaction->reference,
            'status_code' => $code,
            'description' => $data['payment_status_description'] ?? '',
        ]);

        match ($code) {
            1 => $transaction->markAsSuccessful($data),
            2 => $transaction->markAsFailed($data, $data['description'] ?? 'Payment failed'),
            3 => $transaction->markAsRefunded($data),
            default => null,
        };
    }




    // public use case
    /**
     * Public endpoint to get transaction status
     * No authentication required - works for both featured jobs and subscriptions
     */
    public function publicStatus(Request $request, string $reference): JsonResponse
    {
        $transaction = Transaction::where('reference', $reference)->first();

        if (!$transaction) {
            return response()->json([
                'success' => false,
                'message' => 'Transaction not found',
                'status' => 'not_found'
            ], 404);
        }

        // Get metadata
        $metadata = $transaction->metadata ?? [];
        
        // Determine package display name
        $packageName = $this->getPackageDisplayName($transaction);
        
        // Get job summary if featured job
        $jobSummary = null;
        $companyName = null;
        if ($transaction->transaction_type === 'featured_job') {
            $jobSummary = $metadata['job_details_text'] ?? null;
            $companyName = $metadata['company_name'] ?? null;
        }

        return response()->json([
            'success' => true,
            'data' => [
                'reference' => $transaction->reference,
                'status' => $transaction->status,
                'transaction_type' => $transaction->transaction_type,
                'subscription_plan' => $transaction->subscription_plan,
                'subscription_period' => $transaction->subscription_period,
                'amount' => $transaction->amount,
                'currency' => $transaction->currency,
                'formatted_amount' => $transaction->formatted_amount,
                'confirmation_code' => $transaction->confirmation_code,
                'customer_email' => $transaction->customer_email,
                'customer_name' => $transaction->customer_name,
                'customer_phone' => $transaction->customer_phone,
                'package_display_name' => $packageName,
                'job_summary' => $jobSummary,
                'company_name' => $companyName,
                'metadata' => $metadata,
                'created_at' => $transaction->created_at,
                'updated_at' => $transaction->updated_at,
            ]
        ]);
    }

    private function getPackageDisplayName(Transaction $transaction): string
    {
        $metadata = $transaction->metadata ?? [];
        
        // Check if package_display_name exists in metadata
        if (!empty($metadata['package_display_name'])) {
            return $metadata['package_display_name'];
        }
        
        // For featured jobs
        if ($transaction->transaction_type === 'featured_job') {
            $plan = $transaction->subscription_plan;
            switch ($plan) {
                case 'featured_week':
                    return '⭐ FEATURED - 7 DAYS';
                case 'featured_21days':
                    return '🔥 FEATURED - 21 DAYS';
                case 'featured_40days':
                    return '🚀 FEATURED - 40 DAYS';
                default:
                    return '⭐ FEATURED JOB POSTING';
            }
        }
        
        // For subscriptions
        $plan = $transaction->subscription_plan;
        switch ($plan) {
            case 'seeker_basic':
                return '📋 BASIC PLAN';
            case 'seeker_pro':
                return '⭐ PRO PLAN';
            case 'seeker_elite':
                return '💎 ELITE PLAN';
            default:
                return '📋 SUBSCRIPTION PLAN';
        }
    }


    /**
     * Activate free trial for a user
     * POST /api/v1/payment/activate-trial
     */
    public function activateTrial(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'plan' => 'required|string|in:seeker_trial',
            'period' => 'required|string|in:monthly',
        ]);

        $user = $request->user();

        if (!$user) {
            return $this->error('User not authenticated.', 401);
        }

        // Check if user already had a trial
        $existingTrial = Transaction::where('user_id', $user->id)
            ->where('subscription_plan', 'seeker_trial')
            ->where('status', 'successful')
            ->exists();

        if ($existingTrial) {
            return $this->error('You have already used your free trial.', 400);
        }

        // Check if user already has an active subscription
        $activeSubscription = Transaction::where('user_id', $user->id)
            ->where('status', 'successful')
            ->where('transaction_type', 'subscription')
            ->exists();

        if ($activeSubscription) {
            return $this->error('You already have an active subscription.', 400);
        }

        try {
            $trialEndsAt = now()->addDays(7);

            $transaction = Transaction::create([
                'user_id' => $user->id,
                'transaction_type' => 'subscription',
                'subscription_plan' => $validated['plan'],
                'subscription_period' => 'monthly',
                'amount' => 0,
                'currency' => 'USD',
                'status' => 'successful',
                'payment_gateway' => 'pesapal',  // ✅ Changed from 'trial' to 'pesapal'
                'customer_email' => $user->email,
                'customer_name' => $user->first_name . ' ' . $user->last_name,
                'metadata' => [
                    'plan' => $validated['plan'],
                    'period' => 'monthly',
                    'trial_days' => 7,
                    'trial_ends_at' => $trialEndsAt->toIso8601String(),
                    'is_trial' => true,  // ✅ Add flag to identify trial
                ],
                'confirmed_at' => now(),
            ]);

            return $this->success([
                'message' => 'Free trial activated successfully! You have 7 days to try all features.',
                'trial_ends_at' => $trialEndsAt->toIso8601String(),
                'trial_days_left' => 7,
            ]);

        } catch (\Exception $e) {
            Log::error('Trial activation failed', [
                'user_id' => $user->id,
                'error' => $e->getMessage()
            ]);

            return $this->error('Failed to activate trial: ' . $e->getMessage(), 500);
        }
    }

    /**
     * Check if user has used their trial
     * GET /api/v1/payment/trial-status
     */
    public function trialStatus(Request $request): JsonResponse
    {
        $user = $request->user();

        if (!$user) {
            return $this->error('User not authenticated.', 401);
        }

        $hasUsedTrial = Transaction::where('user_id', $user->id)
            ->where('subscription_plan', 'seeker_trial')
            ->where('status', 'successful')
            ->exists();

        $trial = Transaction::where('user_id', $user->id)
            ->where('subscription_plan', 'seeker_trial')
            ->where('status', 'successful')
            ->first();

        $trialEndsAt = null;
        $trialDaysLeft = 0;
        $isActive = false;

        if ($trial && isset($trial->metadata['trial_ends_at'])) {
            $trialEndsAt = \Carbon\Carbon::parse($trial->metadata['trial_ends_at']);
            $trialDaysLeft = max(0, (int) ceil(now()->diffInDays($trialEndsAt, false)));
            $isActive = $trialDaysLeft > 0;
        }

        return $this->success([
            'has_used_trial' => $hasUsedTrial,
            'has_active_trial' => $isActive,
            'trial_days_left' => $trialDaysLeft,
            'trial_ends_at' => $trialEndsAt ? $trialEndsAt->toIso8601String() : null,
        ]);
    }

    /**
     * GET /api/v1/subscription/status
     * Returns subscription status with usage stats
     */
    public function status(Request $request)
    {
        $user = $request->user();
        
        // Get usage counters
        $counter = CvUsageCounter::firstOrCreate(
            ['user_id' => $user->id],
            [
                'cv_reviews_count' => 0,
                'cv_rewrites_count' => 0,
                'cover_letters_count' => 0,
                'period_start' => now()->startOfMonth(),
            ]
        );
        
        // Check for trial subscription via metadata
        $trial = Transaction::where('user_id', $user->id)
            ->where('subscription_plan', 'seeker_trial')
            ->where('status', 'successful')
            ->first();

        if ($trial) {
            // Check if trial has expired via metadata
            $isExpired = false;
            $trialDaysLeft = 7;
            
            if (isset($trial->metadata['trial_ends_at'])) {
                $trialEndsAt = \Carbon\Carbon::parse($trial->metadata['trial_ends_at']);
                $trialDaysLeft = max(0, (int) ceil(now()->diffInDays($trialEndsAt, false)));
                $isExpired = $trialDaysLeft <= 0;
            }
            
            // If trial is expired, treat as no subscription
            if ($isExpired) {
                return $this->success([
                    'has_active_subscription' => false,
                    'is_trial' => false,
                    'usage' => [
                        'cv_reviews_count' => (int) $counter->cv_reviews_count,
                        'cv_rewrites_count' => (int) $counter->cv_rewrites_count,
                        'cover_letters_count' => (int) $counter->cover_letters_count,
                    ],
                    'limits' => [
                        'cv_reviews' => 0,
                        'cv_rewrites' => 0,
                        'cover_letters' => 0,
                    ],
                ]);
            }
            
            // Trial limits
            $limits = [
                'cv_reviews' => 3,
                'cv_rewrites' => 3,
                'cover_letters' => 6,
            ];
            
            return $this->success([
                'has_active_subscription' => true,
                'plan' => 'seeker_trial',
                'plan_display_name' => 'Free Trial',
                'period' => 'monthly',
                'expiry_date' => $trial->metadata['trial_ends_at'] ?? null,
                'is_trial' => true,
                'trial_days_left' => $trialDaysLeft,
                'amount' => 0,
                'currency' => 'USD',
                'usage' => [
                    'cv_reviews_count' => (int) $counter->cv_reviews_count,
                    'cv_rewrites_count' => (int) $counter->cv_rewrites_count,
                    'cover_letters_count' => (int) $counter->cover_letters_count,
                ],
                'limits' => $limits,
                'remaining' => [
                    'cv_reviews' => max(0, $limits['cv_reviews'] - (int) $counter->cv_reviews_count),
                    'cv_rewrites' => max(0, $limits['cv_rewrites'] - (int) $counter->cv_rewrites_count),
                    'cover_letters' => max(0, $limits['cover_letters'] - (int) $counter->cover_letters_count),
                ],
            ]);
        }

        // Check for regular subscription
        $subscription = Transaction::where('user_id', $user->id)
            ->where('status', 'successful')
            ->where('transaction_type', 'subscription')
            ->where('subscription_plan', '!=', 'seeker_trial')
            ->first();

        if ($subscription) {
            // Set limits based on plan
            $limits = match($subscription->subscription_plan) {
                'seeker_basic' => ['cv_reviews' => 5, 'cv_rewrites' => 5, 'cover_letters' => 10],
                'seeker_pro' => ['cv_reviews' => PHP_INT_MAX, 'cv_rewrites' => PHP_INT_MAX, 'cover_letters' => PHP_INT_MAX],
                'seeker_elite' => ['cv_reviews' => PHP_INT_MAX, 'cv_rewrites' => PHP_INT_MAX, 'cover_letters' => PHP_INT_MAX],
                default => ['cv_reviews' => 0, 'cv_rewrites' => 0, 'cover_letters' => 0],
            };
            
            $planDisplayNames = [
                'seeker_basic' => 'Basic',
                'seeker_pro' => 'Pro',
                'seeker_elite' => 'Elite',
            ];
            
            return $this->success([
                'has_active_subscription' => true,
                'plan' => $subscription->subscription_plan,
                'plan_display_name' => $planDisplayNames[$subscription->subscription_plan] ?? ucfirst(str_replace('seeker_', '', $subscription->subscription_plan)),
                'period' => $subscription->subscription_period ?? 'monthly',
                'expiry_date' => $subscription->confirmed_at ? \Carbon\Carbon::parse($subscription->confirmed_at)->addDays(30)->toIso8601String() : null,
                'is_trial' => false,
                'amount' => $subscription->amount,
                'currency' => $subscription->currency,
                'usage' => [
                    'cv_reviews_count' => (int) $counter->cv_reviews_count,
                    'cv_rewrites_count' => (int) $counter->cv_rewrites_count,
                    'cover_letters_count' => (int) $counter->cover_letters_count,
                ],
                'limits' => $limits,
                'remaining' => [
                    'cv_reviews' => $limits['cv_reviews'] === PHP_INT_MAX ? 'Unlimited' : max(0, $limits['cv_reviews'] - (int) $counter->cv_reviews_count),
                    'cv_rewrites' => $limits['cv_rewrites'] === PHP_INT_MAX ? 'Unlimited' : max(0, $limits['cv_rewrites'] - (int) $counter->cv_rewrites_count),
                    'cover_letters' => $limits['cover_letters'] === PHP_INT_MAX ? 'Unlimited' : max(0, $limits['cover_letters'] - (int) $counter->cover_letters_count),
                ],
            ]);
        }

        // No active subscription
        return $this->success([
            'has_active_subscription' => false,
            'is_trial' => false,
            'usage' => [
                'cv_reviews_count' => (int) $counter->cv_reviews_count,
                'cv_rewrites_count' => (int) $counter->cv_rewrites_count,
                'cover_letters_count' => (int) $counter->cover_letters_count,
            ],
            'limits' => [
                'cv_reviews' => 0,
                'cv_rewrites' => 0,
                'cover_letters' => 0,
            ],
        ]);
    }

}