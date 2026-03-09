<?php

namespace App\Models\Payments;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;
use Carbon\Carbon;
use App\Models\Job\{ Company, JobLocation, JobType   };
use App\Models\Auth\{ User };

class Transaction extends Model
{
    use HasFactory;

    protected $fillable = [
        'uuid', 'user_id', 'company_id', 'plan_id', 'reference', 'gateway_reference',
        'transaction_type', 'amount', 'gateway_fee', 'net_amount', 'currency', 'status',
        'payment_gateway', 'payment_method', 'payment_channel', 'gateway_request',
        'gateway_response', 'gateway_webhook', 'gateway_status', 'gateway_message',
        'customer_email', 'customer_phone', 'customer_name', 'billing_address',
        'shipping_address', 'ip_address', 'user_agent', 'device_fingerprint',
        'is_flagged', 'flag_reason', 'retry_count', 'last_retry_at', 'processed_at',
        'confirmed_at', 'refunded_at', 'disputed_at', 'metadata', 'custom_fields',
        'checkout_session_id', 'subscription_id'
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'gateway_fee' => 'decimal:2',
        'net_amount' => 'decimal:2',
        'gateway_request' => 'array',
        'gateway_response' => 'array',
        'gateway_webhook' => 'array',
        'billing_address' => 'array',
        'shipping_address' => 'array',
        'metadata' => 'array',
        'custom_fields' => 'array',
        'is_flagged' => 'boolean',
        'last_retry_at' => 'datetime',
        'processed_at' => 'datetime',
        'confirmed_at' => 'datetime',
        'refunded_at' => 'datetime',
        'disputed_at' => 'datetime',
    ];

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($transaction) {
            if (empty($transaction->uuid)) {
                $transaction->uuid = Str::uuid();
            }
            if (empty($transaction->reference)) {
                $transaction->reference = $transaction->generateReference();
            }
            if (empty($transaction->currency)) {
                $transaction->currency = 'UGX';
            }
            if (empty($transaction->net_amount) && $transaction->amount) {
                $transaction->net_amount = $transaction->amount - $transaction->gateway_fee;
            }
        });

        static::updating(function ($transaction) {
            if ($transaction->isDirty(['amount', 'gateway_fee'])) {
                $transaction->net_amount = $transaction->amount - $transaction->gateway_fee;
            }
        });
    }

    // Relationships
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function company()
    {
        return $this->belongsTo(Company::class);
    }

    public function plan()
    {
        return $this->belongsTo(PaymentPlan::class, 'plan_id');
    }

    public function refunds()
    {
        return $this->hasMany(TransactionRefund::class);
    }

    // Scopes
    public function scopeSuccessful($query)
    {
        return $query->where('status', 'successful');
    }

    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    public function scopeProcessing($query)
    {
        return $query->where('status', 'processing');
    }

    public function scopeFailed($query)
    {
        return $query->where('status', 'failed');
    }

    public function scopeRefunded($query)
    {
        return $query->where('status', 'refunded');
    }

    public function scopeDisputed($query)
    {
        return $query->where('status', 'disputed');
    }

    public function scopeByGateway($query, $gateway)
    {
        return $query->where('payment_gateway', $gateway);
    }

    public function scopeByUser($query, $userId)
    {
        return $query->where('user_id', $userId);
    }

    public function scopeByCompany($query, $companyId)
    {
        return $query->where('company_id', $companyId);
    }

    public function scopeRecent($query, $days = 30)
    {
        return $query->where('created_at', '>=', now()->subDays($days));
    }

    public function scopeFlagged($query)
    {
        return $query->where('is_flagged', true);
    }

    public function scopeToday($query)
    {
        return $query->whereDate('created_at', today());
    }

    public function scopeThisMonth($query)
    {
        return $query->whereMonth('created_at', now()->month)
                    ->whereYear('created_at', now()->year);
    }

    // Accessors
    public function getFormattedAmountAttribute()
    {
        return $this->currency . ' ' . number_format($this->amount);
    }

    public function getFormattedNetAmountAttribute()
    {
        return $this->currency . ' ' . number_format($this->net_amount);
    }

    public function getFormattedGatewayFeeAttribute()
    {
        return $this->currency . ' ' . number_format($this->gateway_fee);
    }

    public function getIsSuccessfulAttribute()
    {
        return $this->status === 'successful';
    }

    public function getIsPendingAttribute()
    {
        return $this->status === 'pending';
    }

    public function getIsProcessingAttribute()
    {
        return $this->status === 'processing';
    }

    public function getIsFailedAttribute()
    {
        return $this->status === 'failed';
    }

    public function getIsRefundedAttribute()
    {
        return $this->status === 'refunded';
    }

    public function getIsDisputedAttribute()
    {
        return $this->status === 'disputed';
    }

    public function getGatewayNameAttribute()
    {
        return ucfirst($this->payment_gateway);
    }

    public function getPaymentMethodDisplayAttribute()
    {
        if (!$this->payment_method) return 'N/A';
        
        $methods = [
            'card' => 'Credit/Debit Card',
            'mobile_money' => 'Mobile Money',
            'bank_transfer' => 'Bank Transfer',
            'bank' => 'Bank Transfer',
            'wallet' => 'Digital Wallet',
            'ussd' => 'USSD',
            'qr' => 'QR Code',
            'paypal' => 'PayPal',
            'apple_pay' => 'Apple Pay',
            'google_pay' => 'Google Pay'
        ];

        return $methods[$this->payment_method] ?? ucfirst(str_replace('_', ' ', $this->payment_method));
    }

    public function getPaymentChannelDisplayAttribute()
    {
        if (!$this->payment_channel) return 'N/A';
        
        $channels = [
            'visa' => 'Visa',
            'mastercard' => 'MasterCard',
            'verve' => 'Verve',
            'mtn' => 'MTN Mobile Money',
            'airtel' => 'Airtel Money',
            'mpesa' => 'M-Pesa',
            'tigo' => 'Tigo Pesa',
            'orange' => 'Orange Money'
        ];

        return $channels[$this->payment_channel] ?? ucfirst($this->payment_channel);
    }

    public function getDurationInSecondsAttribute()
    {
        if (!$this->processed_at) return null;
        return $this->created_at->diffInSeconds($this->processed_at);
    }

    // Methods
    public function generateReference()
    {
        return 'TXN-' . strtoupper(Str::random(3)) . '-' . time() . '-' . Str::random(6);
    }

    public function markAsProcessing($gatewayData = null)
    {
        $this->update([
            'status' => 'processing',
            'gateway_request' => $gatewayData,
            'processed_at' => now()
        ]);
    }

    public function markAsSuccessful($gatewayResponse = null)
    {
        $this->update([
            'status' => 'successful',
            'gateway_response' => $gatewayResponse,
            'gateway_status' => 'success',
            'gateway_message' => 'Payment completed successfully',
            'confirmed_at' => now()
        ]);

        $this->processPostPayment();
    }

    public function markAsFailed($gatewayResponse = null, $message = null)
    {
        $this->update([
            'status' => 'failed',
            'gateway_response' => $gatewayResponse,
            'gateway_status' => 'failed',
            'gateway_message' => $message ?? 'Payment failed',
            'retry_count' => $this->retry_count + 1,
            'last_retry_at' => now()
        ]);
    }

    public function markAsCancelled()
    {
        $this->update([
            'status' => 'cancelled',
            'gateway_status' => 'cancelled',
            'gateway_message' => 'Payment cancelled by user'
        ]);
    }

    public function markAsRefunded($refundData = null)
    {
        $this->update([
            'status' => 'refunded',
            'refunded_at' => now(),
            'metadata' => array_merge($this->metadata ?? [], ['refund_data' => $refundData])
        ]);
    }

    public function markAsDisputed($disputeData = null)
    {
        $this->update([
            'status' => 'disputed',
            'disputed_at' => now(),
            'is_flagged' => true,
            'flag_reason' => 'Payment dispute initiated',
            'metadata' => array_merge($this->metadata ?? [], ['dispute_data' => $disputeData])
        ]);
    }

    public function flagTransaction($reason)
    {
        $this->update([
            'is_flagged' => true,
            'flag_reason' => $reason
        ]);
    }

    public function unflagTransaction()
    {
        $this->update([
            'is_flagged' => false,
            'flag_reason' => null
        ]);
    }

    public function updateGatewayReference($reference)
    {
        $this->update(['gateway_reference' => $reference]);
    }

    public function updateCustomerDetails($email = null, $phone = null, $name = null)
    {
        $updateData = [];
        if ($email) $updateData['customer_email'] = $email;
        if ($phone) $updateData['customer_phone'] = $phone;
        if ($name) $updateData['customer_name'] = $name;

        $this->update($updateData);
    }

    public function updateGatewayFee($fee)
    {
        $this->update([
            'gateway_fee' => $fee,
            'net_amount' => $this->amount - $fee
        ]);
    }

    private function processPostPayment()
    {
        // Implement post-payment logic based on plan type
        switch ($this->plan->type) {
            case 'job_post':
                // Activate job posts
                event(new \App\Events\JobPostPaymentCompleted($this));
                break;
            case 'featured_job':
                // Feature the job
                event(new \App\Events\FeaturedJobPaymentCompleted($this));
                break;
            case 'company_verification':
                // Verify company
                $this->company->update(['is_verified' => true]);
                event(new \App\Events\CompanyVerificationPaymentCompleted($this));
                break;
            case 'premium_profile':
                // Upgrade company profile
                event(new \App\Events\PremiumProfilePaymentCompleted($this));
                break;
        }

        // Log the successful transaction
        \Log::info("Payment completed successfully", [
            'transaction_id' => $this->id,
            'reference' => $this->reference,
            'amount' => $this->formatted_amount,
            'user_id' => $this->user_id,
            'company_id' => $this->company_id
        ]);
    }

    public function canRetry()
    {
        return $this->status === 'failed' && 
               $this->retry_count < 3 && 
               (!$this->last_retry_at || $this->last_retry_at->diffInHours(now()) >= 1);
    }

    public function getRetryDelay()
    {
        // Exponential backoff for retries
        return min(3600, pow(2, $this->retry_count) * 900); // Max 1 hour delay
    }

    public function toGatewayArray()
    {
        return [
            'amount' => $this->amount,
            'currency' => $this->currency,
            'reference' => $this->reference,
            'customer_email' => $this->customer_email,
            'customer_phone' => $this->customer_phone,
            'customer_name' => $this->customer_name,
            'metadata' => array_merge($this->metadata ?? [], [
                'transaction_id' => $this->id,
                'user_id' => $this->user_id,
                'company_id' => $this->company_id,
                'plan_id' => $this->plan_id,
                'plan_type' => $this->plan->type
            ])
        ];
    }

    public function getAnalyticsData()
    {
        return [
            'amount' => $this->amount,
            'gateway_fee' => $this->gateway_fee,
            'net_amount' => $this->net_amount,
            'currency' => $this->currency,
            'payment_gateway' => $this->payment_gateway,
            'payment_method' => $this->payment_method,
            'payment_channel' => $this->payment_channel,
            'duration_seconds' => $this->duration_in_seconds,
            'retry_count' => $this->retry_count,
            'is_flagged' => $this->is_flagged,
            'created_at' => $this->created_at,
            'processed_at' => $this->processed_at
        ];
    }
}
