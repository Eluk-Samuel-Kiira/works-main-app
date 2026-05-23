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
            'plan'         => 'required|string|in:basic,pro,elite',
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

            // ── Create transaction — no company_id or plan_id needed ──────
            $transaction = Transaction::create([
                'user_id'             => $user->id,
                'transaction_type'    => 'subscription',
                'subscription_plan'   => $validated['plan'],    // basic/pro/elite
                'subscription_period' => $validated['period'],  // monthly/yearly
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
                'plan'      => $validated['plan'],
                'amount'    => $validated['amount_local'],
                'currency'  => $validated['currency'],
            ]);

            // ── Build Pesapal payload ─────────────────────────────────────
            $callbackUrl = rtrim(config('pesapal.callback_url'), '/')
                . '?reference=' . urlencode($transaction->reference);

            $payload = [
                'id'               => $transaction->reference,
                'currency'         => $validated['currency'],
                'amount'           => (float) $validated['amount_local'],
                'description'      => 'Stardena Works ' . ucfirst($validated['plan'])
                                      . ' (' . ucfirst($validated['period']) . ')',
                'callback_url'     => $callbackUrl,
                'cancellation_url' => config('pesapal.cancellation_url'),
                'notification_id'  => $this->pesapal->getIpnId(),
                'billing_address'  => [
                    'email_address' => $validated['email'],
                    'phone_number'  => $validated['phone'] ?? '',
                    'country_code'  => $validated['country_code'] ?? 'UG',
                    'first_name'    => $validated['first_name'],
                    'last_name'     => $validated['last_name'],
                ],
            ];

            // ── Submit to Pesapal ─────────────────────────────────────────
            $pesapalResponse = $this->pesapal->submitOrder($payload);

            // ── Update transaction with Pesapal's tracking ID ─────────────
            $transaction->update([
                'gateway_reference' => $pesapalResponse['order_tracking_id'] ?? null,
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
    // GET /api/v1/payments/status/{reference}
    // ─────────────────────────────────────────────────────────────────────
    public function status(Request $request, string $reference): JsonResponse
    {
        $transaction = Transaction::where('reference', $reference)
            ->where('user_id', $request->user()->id)
            ->first();

        if (!$transaction) {
            return $this->error('Transaction not found', 404);
        }

        // Re-query Pesapal if still in-flight
        if (in_array($transaction->status, ['pending', 'processing'])
            && $transaction->gateway_reference) {
            try {
                $statusData = $this->pesapal->getTransactionStatus($transaction->gateway_reference);
                $this->applyStatus($transaction, $statusData);
                $transaction->refresh();
            } catch (\Exception $e) {
                Log::warning('[Payment] Status re-query failed', ['error' => $e->getMessage()]);
            }
        }

        return response()->json([
            'success'      => true,
            'status'       => $transaction->status,
            'amount'       => $transaction->formatted_amount,
            'reference'    => $transaction->reference,
            'plan'         => $transaction->subscription_plan,
            'period'       => $transaction->subscription_period,
            'confirmed_at' => $transaction->confirmed_at,
        ]);
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
}