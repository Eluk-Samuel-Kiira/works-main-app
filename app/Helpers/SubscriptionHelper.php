<?php
// MAIN APP: app/Helpers/SubscriptionHelper.php

namespace App\Helpers;

use App\Models\Payments\Transaction;
use Carbon\Carbon;

class SubscriptionHelper
{
    /**
     * Get user's active subscription
     * Returns array with plan, period, expiry, and status
     */
    public static function getActiveSubscription($userId): array
    {
        // Find the most recent successful subscription transaction
        $transaction = Transaction::where('user_id', $userId)
            ->where('transaction_type', 'subscription')
            ->where('status', 'successful')
            ->orderBy('confirmed_at', 'desc')
            ->first();

        if (!$transaction) {
            return [
                'has_active' => false,
                'plan' => null,
                'period' => null,
                'expiry_date' => null,
                'amount' => null,
                'currency' => null,
            ];
        }

        // Calculate expiry date based on subscription period
        $confirmedAt = Carbon::parse($transaction->confirmed_at);
        $expiryDate = $transaction->subscription_period === 'monthly' 
            ? $confirmedAt->addMonth() 
            : $confirmedAt->addYear();

        // Check if subscription is still active (not expired)
        $isExpired = now()->greaterThan($expiryDate);

        return [
            'has_active' => !$isExpired,
            'plan' => $transaction->subscription_plan,
            'period' => $transaction->subscription_period,
            'expiry_date' => $expiryDate->format('M d, Y'),
            'amount' => $transaction->amount,
            'currency' => $transaction->currency,
            'status' => $transaction->status,
            'transaction_id' => $transaction->id,
        ];
    }

    /**
     * Check if user has any successful transaction (ever)
     */
    public static function hasEverSubscribed($userId): bool
    {
        return Transaction::where('user_id', $userId)
            ->where('transaction_type', 'subscription')
            ->where('status', 'successful')
            ->exists();
    }

    /**
     * Get usage limits based on subscription plan
     */
    public static function getPlanLimits($plan): array
    {
        return match($plan) {
            'basic' => ['cv_reviews' => 5, 'cv_rewrites' => 5, 'cover_letters' => 10],
            'pro' => ['cv_reviews' => 999, 'cv_rewrites' => 999, 'cover_letters' => 999],
            'elite' => ['cv_reviews' => 999, 'cv_rewrites' => 999, 'cover_letters' => 999],
            default => ['cv_reviews' => 5, 'cv_rewrites' => 5, 'cover_letters' => 5],
        };
    }
}