<?php
// MAIN APP: app/Models/Payments/Transaction.php — full fixed version

namespace App\Models\Payments;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\{Str, Facades\Log};
use App\Models\Job\Company;
use App\Models\Auth\User;

class Transaction extends Model
{
    use HasFactory;

    protected $fillable = [
        'uuid', 'user_id', 'company_id', 'plan_id', 'reference', 'gateway_reference',
        'transaction_type', 'subscription_plan', 'subscription_period',
        'amount', 'gateway_fee', 'net_amount', 'currency', 'status',
        'payment_gateway', 'payment_method', 'payment_channel',
        'gateway_request', 'gateway_response', 'gateway_webhook',
        'gateway_status', 'gateway_message',
        'customer_email', 'customer_phone', 'customer_name',
        'billing_address', 'shipping_address',
        'ip_address', 'user_agent', 'device_fingerprint',
        'is_flagged', 'flag_reason', 'retry_count', 'last_retry_at',
        'processed_at', 'confirmed_at', 'refunded_at', 'disputed_at',
        'metadata', 'custom_fields', 'checkout_session_id', 'subscription_id',
    ];

    protected $casts = [
        'amount'          => 'decimal:2',
        'gateway_fee'     => 'decimal:2',
        'net_amount'      => 'decimal:2',
        'gateway_request' => 'array',
        'gateway_response'=> 'array',
        'gateway_webhook' => 'array',
        'billing_address' => 'array',
        'shipping_address'=> 'array',
        'metadata'        => 'array',
        'custom_fields'   => 'array',
        'is_flagged'      => 'boolean',
        'last_retry_at'   => 'datetime',
        'processed_at'    => 'datetime',
        'confirmed_at'    => 'datetime',
        'refunded_at'     => 'datetime',
        'disputed_at'     => 'datetime',
    ];

    protected static function boot(): void
    {
        parent::boot();

        static::creating(function (self $t) {
            $t->uuid      = $t->uuid      ?? (string) Str::uuid();
            $t->reference = $t->reference ?? $t->generateReference();
            $t->currency  = $t->currency  ?? 'UGX';

            // net_amount — safe even when gateway_fee is null
            if (empty($t->net_amount) && !empty($t->amount)) {
                $t->net_amount = $t->amount - ($t->gateway_fee ?? 0);
            }

            // company_id and plan_id are now nullable — no forced defaults needed
        });

        static::updating(function (self $t) {
            if ($t->isDirty(['amount', 'gateway_fee'])) {
                $t->net_amount = $t->amount - ($t->gateway_fee ?? 0);
            }
        });
    }

    // ─────────────────────────────────────────────────────────────────────
    // Relationships
    // ─────────────────────────────────────────────────────────────────────
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
        // nullable FK — always use optional chaining when accessing plan->type
        return $this->belongsTo(PaymentPlan::class, 'plan_id');
    }

    public function refunds()
    {
        return $this->hasMany(TransactionRefund::class);
    }

    // ─────────────────────────────────────────────────────────────────────
    // Scopes
    // ─────────────────────────────────────────────────────────────────────
    public function scopePending($q)    { return $q->where('status', 'pending'); }
    public function scopeProcessing($q) { return $q->where('status', 'processing'); }
    public function scopeFailed($q)     { return $q->where('status', 'failed'); }
    public function scopeRefunded($q)   { return $q->where('status', 'refunded'); }
    public function scopeDisputed($q)   { return $q->where('status', 'disputed'); }
    public function scopeFlagged($q)    { return $q->where('is_flagged', true); }
    public function scopeToday($q)      { return $q->whereDate('created_at', today()); }

    public function scopeByGateway($q, $g)   { return $q->where('payment_gateway', $g); }
    public function scopeByUser($q, $id)      { return $q->where('user_id', $id); }
    public function scopeByCompany($q, $id)   { return $q->where('company_id', $id); }
    public function scopeRecent($q, $days=30) { return $q->where('created_at', '>=', now()->subDays($days)); }

    public function scopeThisMonth($q)
    {
        return $q->whereMonth('created_at', now()->month)
                 ->whereYear('created_at',  now()->year);
    }

    // ─────────────────────────────────────────────────────────────────────
    // Accessors
    // ─────────────────────────────────────────────────────────────────────
    public function getFormattedAmountAttribute(): string
    {
        return $this->currency . ' ' . number_format($this->amount);
    }

    public function getFormattedNetAmountAttribute(): string
    {
        return $this->currency . ' ' . number_format($this->net_amount);
    }

    public function getFormattedGatewayFeeAttribute(): string
    {
        return $this->currency . ' ' . number_format($this->gateway_fee ?? 0);
    }

    public function getIsSuccessfulAttribute(): bool { return $this->status === 'successful'; }
    public function getIsPendingAttribute(): bool    { return $this->status === 'pending'; }
    public function getIsProcessingAttribute(): bool { return $this->status === 'processing'; }
    public function getIsFailedAttribute(): bool     { return $this->status === 'failed'; }
    public function getIsRefundedAttribute(): bool   { return $this->status === 'refunded'; }
    public function getIsDisputedAttribute(): bool   { return $this->status === 'disputed'; }

    public function getGatewayNameAttribute(): string
    {
        return ucfirst($this->payment_gateway ?? 'Unknown');
    }

    public function getDurationInSecondsAttribute(): ?int
    {
        return $this->processed_at
            ? $this->created_at->diffInSeconds($this->processed_at)
            : null;
    }

    // ─────────────────────────────────────────────────────────────────────
    // State transition methods
    // ─────────────────────────────────────────────────────────────────────
    public function markAsProcessing(?array $gatewayData = null): void
    {
        $this->update([
            'status'          => 'processing',
            'gateway_request' => $gatewayData,
            'processed_at'    => now(),
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

        // Trigger subscription activation event
        event(new \App\Events\SubscriptionActivated($this));
    }

    public function scopeSuccessful($query)
    {
        return $query->where('status', 'successful');
    }

    public function scopeActiveSubscription($query, $userId)
    {
        return $query->where('user_id', $userId)
            ->where('transaction_type', 'subscription')
            ->where('status', 'successful')
            ->where(function($q) {
                $q->whereNull('expires_at')
                ->orWhere('expires_at', '>', now());
            });
    }

    public function markAsFailed(?array $gatewayResponse = null, ?string $message = null): void
    {
        $this->update([
            'status'           => 'failed',
            'gateway_response' => $gatewayResponse,
            'gateway_status'   => 'failed',
            'gateway_message'  => $message ?? 'Payment failed',
            'retry_count'      => ($this->retry_count ?? 0) + 1,
            'last_retry_at'    => now(),
        ]);
    }

    public function markAsCancelled(): void
    {
        $this->update([
            'status'          => 'cancelled',
            'gateway_status'  => 'cancelled',
            'gateway_message' => 'Payment cancelled by user',
        ]);
    }

    public function markAsRefunded(?array $refundData = null): void
    {
        $this->update([
            'status'      => 'refunded',
            'refunded_at' => now(),
            'metadata'    => array_merge($this->metadata ?? [], ['refund_data' => $refundData]),
        ]);
    }

    public function markAsDisputed(?array $disputeData = null): void
    {
        $this->update([
            'status'       => 'disputed',
            'disputed_at'  => now(),
            'is_flagged'   => true,
            'flag_reason'  => 'Payment dispute initiated',
            'metadata'     => array_merge($this->metadata ?? [], ['dispute_data' => $disputeData]),
        ]);
    }

    public function flagTransaction(string $reason): void
    {
        $this->update(['is_flagged' => true, 'flag_reason' => $reason]);
    }

    public function unflagTransaction(): void
    {
        $this->update(['is_flagged' => false, 'flag_reason' => null]);
    }

    public function updateGatewayReference(string $ref): void
    {
        $this->update(['gateway_reference' => $ref]);
    }

    public function updateGatewayFee(float $fee): void
    {
        $this->update(['gateway_fee' => $fee, 'net_amount' => $this->amount - $fee]);
    }

    // ─────────────────────────────────────────────────────────────────────
    // Post-payment actions — safe, never crashes even if plan is null
    // ─────────────────────────────────────────────────────────────────────
    private function processPostPayment(): void
    {
        Log::info('[Transaction] Payment confirmed', [
            'id'        => $this->id,
            'reference' => $this->reference,
            'amount'    => $this->formatted_amount,
            'user_id'   => $this->user_id,
            'type'      => $this->transaction_type,
            'plan'      => $this->subscription_plan,
        ]);

        // Subscription payments (CV enhancement plans)
        if ($this->transaction_type === 'subscription') {
            $this->activateSubscription();
            return;
        }

        // Job/company plan payments — need the plan FK
        if (!$this->plan_id || !$this->plan) {
            Log::warning('[Transaction] No plan linked for non-subscription payment', [
                'id'   => $this->id,
                'type' => $this->transaction_type,
            ]);
            return;
        }

        match ($this->plan->type) {
            'job_post'             => event(new \App\Events\JobPostPaymentCompleted($this)),
            'featured_job'         => event(new \App\Events\FeaturedJobPaymentCompleted($this)),
            'company_verification' => $this->verifyCompany(),
            'premium_profile'      => event(new \App\Events\PremiumProfilePaymentCompleted($this)),
            default                => Log::warning('[Transaction] Unknown plan type: ' . $this->plan->type),
        };
    }

    private function activateSubscription(): void
    {
        // TODO: Create/update a UserSubscription record
        // For now, log it — wire up subscription model when ready
        Log::info('[Transaction] Subscription activated', [
            'user_id' => $this->user_id,
            'plan'    => $this->subscription_plan,
            'period'  => $this->subscription_period,
        ]);

        // Example: UserSubscription::createOrUpdate($this);
        // event(new \App\Events\SubscriptionActivated($this));
    }

    private function verifyCompany(): void
    {
        if ($this->company) {
            $this->company->update(['is_verified' => true]);
            event(new \App\Events\CompanyVerificationPaymentCompleted($this));
        }
    }

    // ─────────────────────────────────────────────────────────────────────
    // Helpers
    // ─────────────────────────────────────────────────────────────────────
    public function generateReference(): string
    {
        // Pesapal requirement: only alphanumeric, dashes, underscores, dots, colons
        // Max 50 chars — "TXN-ABC-1716000000-XYZABC" = 26 chars ✓
        return 'TXN-' . strtoupper(Str::random(3)) . '-' . time() . '-' . strtoupper(Str::random(6));
    }

    public function canRetry(): bool
    {
        return $this->status === 'failed'
            && ($this->retry_count ?? 0) < 3
            && (!$this->last_retry_at || $this->last_retry_at->diffInHours(now()) >= 1);
    }

    public function getRetryDelay(): int
    {
        return min(3600, (int) pow(2, $this->retry_count ?? 0) * 900);
    }
}