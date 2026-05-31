<?php
// MAIN APP: app/Http/Controllers/Api/Payments/FeaturedJobPaymentController.php

namespace App\Http\Controllers\Api\Payments;

use App\Http\Concerns\ApiResponse;
use App\Http\Controllers\Controller;
use App\Models\Notification;
use App\Models\Payments\Transaction;
use App\Services\PesapalService;
use Illuminate\Http\{Request, JsonResponse};
use Illuminate\Support\Facades\{DB, Log, Mail};

class FeaturedJobPaymentController extends Controller
{
    use ApiResponse;

    public function __construct(private PesapalService $pesapal) {}

    // ─────────────────────────────────────────────────────────────────────
    // POST /api/v1/featured-jobs/initiate
    // No user auth — accepts X-App-Key service token from web proxy
    // ─────────────────────────────────────────────────────────────────────
    public function initiate(Request $request): JsonResponse
    {
        Log::info('[FeaturedJob] Initiate called', [
            'ip'    => $request->ip(),
            'email' => $request->input('email'),
            'plan'  => $request->input('plan'),
        ]);

        $validated = $request->validate([
            'plan'                 => 'required|string|in:featured_week,featured_21days,featured_40days',
            'period'               => 'required|string',
            'amount_usd'           => 'required|numeric|min:1',
            'currency'             => 'required|string|size:3',
            'amount_local'         => 'required|numeric|min:1',
            'first_name'           => 'required|string|max:100',
            'last_name'            => 'required|string|max:100',
            'email'                => 'required|email|max:255',
            'phone'                => 'nullable|string|max:20',
            'country_code'         => 'nullable|string|max:3',
            'company_name'         => 'nullable|string|max:255',
            'job_details_text'     => 'nullable|string|max:50000',
            'job_file_name'        => 'nullable|string|max:255',
            'job_file_base64'      => 'nullable|string',
            'job_file_mime'        => 'nullable|string|max:100',
            'package_display_name' => 'nullable|string|max:255',
            'package_features'     => 'nullable|string', // JSON string
        ]);

        // Must have job content
        if (empty($validated['job_details_text']) && empty($validated['job_file_base64'])) {
            return $this->error('Job details are required — paste text or upload a file.', 422);
        }

        // Decode features JSON string
        $features = [];
        if (!empty($validated['package_features'])) {
            $features = json_decode($validated['package_features'], true) ?? [];
        }

        $packageName = $validated['package_display_name'] ?? $validated['plan'];
        $days        = match($validated['plan']) {
            'featured_week'   => 7,
            'featured_21days' => 21,
            'featured_40days' => 40,
            default           => 7,
        };

        return DB::transaction(function () use ($validated, $features, $packageName, $days, $request) {

            // ── 1. Create Transaction (user_id nullable — anonymous) ───────
            $transaction = Transaction::create([
                'user_id'             => 39,  // anonymous — no login required
                'transaction_type'    => 'featured_job',
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
                    'plan'                 => $validated['plan'],
                    'package_display_name' => $packageName,
                    'package_features'     => $features,
                    'featured_days'        => $days,
                    'period'               => $validated['period'],
                    'amount_usd'           => $validated['amount_usd'],
                    'currency'             => $validated['currency'],
                    'country_code'         => $validated['country_code'] ?? 'UG',
                    'company_name'         => $validated['company_name'] ?? null,
                    'job_details_text'     => $validated['job_details_text'] ?? null,
                    'job_file_name'        => $validated['job_file_name'] ?? null,
                    'job_file_mime'        => $validated['job_file_mime'] ?? null,
                    'has_file'             => !empty($validated['job_file_base64']),
                    'submitted_at'         => now()->toIso8601String(),
                    'ip_address'           => $request->ip(),
                    'user_agent'           => $request->userAgent(),
                ],
            ]);

            Log::info('[FeaturedJob] Transaction created', [
                'reference' => $transaction->reference,
                'plan'      => $validated['plan'],
                'email'     => $validated['email'],
            ]);

            // ── 2. Build Pesapal payload — same pattern as PaymentController ─
            $callbackUrl = rtrim(config('pesapal.featured_callback_url', config('pesapal.callback_url')), '/')
                . '?reference=' . urlencode($transaction->reference);

            $payload = [
                'id'               => $transaction->reference,
                'currency'         => $validated['currency'],
                'amount'           => (float) $validated['amount_local'],
                'description'      => "Stardena Works — Featured Job ({$days} days) — {$packageName}",
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
                // ── 3. Submit to Pesapal — identical to PaymentController ──
                $pesapalResponse = $this->pesapal->submitOrder($payload);

                if (empty($pesapalResponse['redirect_url'])) {
                    throw new \Exception('No redirect URL received from Pesapal');
                }

                $transaction->update([
                    'gateway_reference' => $pesapalResponse['order_tracking_id'],
                    'gateway_request'   => $payload,
                    'gateway_response'  => $pesapalResponse,
                    'status'            => 'processing',
                ]);

                Log::info('[FeaturedJob] Pesapal order submitted', [
                    'reference'   => $transaction->reference,
                    'tracking_id' => $pesapalResponse['order_tracking_id'],
                ]);

                // ── 4. Save Notification ───────────────────────────────────
                $notification = $this->saveNotification(
                    $validated,
                    $features,
                    $packageName,
                    $days,
                    $transaction->reference,
                    $pesapalResponse['order_tracking_id'],
                    'pending'
                );

                // ── 5. Send admin email immediately (fire-and-forget) ──────
                $this->sendAdminEmail(
                    $validated,
                    $features,
                    $packageName,
                    $days,
                    $transaction->reference
                );

                return response()->json([
                    'success'            => true,
                    'redirect_url'       => $pesapalResponse['redirect_url'],
                    'order_tracking_id'  => $pesapalResponse['order_tracking_id'],
                    'merchant_reference' => $transaction->reference,
                    'notification_id'    => $notification->id,
                ]);

            } catch (\Exception $e) {
                Log::error('[FeaturedJob] Pesapal submission failed', [
                    'error'     => $e->getMessage(),
                    'reference' => $transaction->reference,
                ]);

                $transaction->update([
                    'status'        => 'failed',
                    'error_message' => $e->getMessage(),
                ]);

                return $this->error('Payment initialization failed: ' . $e->getMessage(), 500);
            }
        });
    }

    // ─────────────────────────────────────────────────────────────────────
    // GET /api/v1/featured-jobs/callback
    // Called by web proxy after Pesapal redirects the customer back
    // ─────────────────────────────────────────────────────────────────────
    public function callback(Request $request): JsonResponse
    {
        $orderTrackingId   = $request->get('OrderTrackingId');
        $merchantReference = $request->get('OrderMerchantReference');

        Log::info('[FeaturedJob] Callback received', compact('orderTrackingId', 'merchantReference'));

        if (!$orderTrackingId || !$merchantReference) {
            return $this->error('Missing callback parameters', 400);
        }

        $transaction = Transaction::where('reference', $merchantReference)
            ->where('transaction_type', 'featured_job')
            ->first();

        if (!$transaction) {
            Log::warning('[FeaturedJob] Callback: transaction not found', [
                'reference' => $merchantReference,
            ]);
            return $this->error('Transaction not found', 404);
        }

        $statusData = $this->pesapal->getTransactionStatus($orderTrackingId);
        $this->applyStatus($transaction, $statusData);
        $transaction->refresh();

        // ── Update notification to reflect payment result ──────────────
        $this->updateNotificationStatus(
            $merchantReference,
            $transaction->status,
            $statusData['confirmation_code'] ?? null
        );

        // ── Send payer confirmation on success ─────────────────────────
        if ($transaction->status === 'successful') {
            $this->sendPayerConfirmation($transaction);
        }

        Log::info('[FeaturedJob] Callback processed', [
            'reference' => $merchantReference,
            'status'    => $transaction->status,
        ]);

        return response()->json([
            'success'            => true,
            'status'             => $transaction->status,
            'payment_status'     => $statusData['payment_status_description'] ?? 'Unknown',
            'amount'             => $transaction->formatted_amount,
            'plan'               => $transaction->subscription_plan,
            'confirmation_code'  => $statusData['confirmation_code'] ?? null,
            'merchant_reference' => $merchantReference,
            'metadata'           => $transaction->metadata,
        ]);
    }

    // ─────────────────────────────────────────────────────────────────────
    // POST|GET /api/v1/featured-jobs/ipn  — no auth, Pesapal calls this
    // ─────────────────────────────────────────────────────────────────────
    public function ipn(Request $request): JsonResponse
    {
        $orderTrackingId   = $request->get('OrderTrackingId');
        $merchantReference = $request->get('OrderMerchantReference');
        $notificationType  = $request->get('OrderNotificationType');

        Log::info('[FeaturedJob] IPN received', compact(
            'orderTrackingId', 'merchantReference', 'notificationType'
        ));

        $ack = [
            'orderNotificationType'  => $notificationType,
            'orderTrackingId'        => $orderTrackingId,
            'orderMerchantReference' => $merchantReference,
        ];

        $transaction = Transaction::where('reference', $merchantReference)
            ->where('transaction_type', 'featured_job')
            ->first();

        if (!$transaction) {
            Log::warning('[FeaturedJob] IPN: transaction not found', ['reference' => $merchantReference]);
            return response()->json([...$ack, 'status' => 500]);
        }

        $transaction->update(['gateway_webhook' => $request->all()]);

        try {
            $statusData = $this->pesapal->getTransactionStatus($orderTrackingId);
            $previousStatus = $transaction->status;

            $this->applyStatus($transaction, $statusData);
            $transaction->refresh();

            // Only act on transitions — avoid duplicate emails
            if ($previousStatus !== $transaction->status) {
                $this->updateNotificationStatus(
                    $merchantReference,
                    $transaction->status,
                    $statusData['confirmation_code'] ?? null
                );

                if ($transaction->status === 'successful') {
                    $this->sendPayerConfirmation($transaction);
                }
            }

        } catch (\Exception $e) {
            Log::error('[FeaturedJob] IPN status query failed', ['error' => $e->getMessage()]);
        }

        return response()->json([...$ack, 'status' => 200]);
    }

    // ─────────────────────────────────────────────────────────────────────
    // GET /api/v1/featured-jobs/status/{reference}
    // No user check — reference is the only key needed (anonymous-safe)
    // ─────────────────────────────────────────────────────────────────────
    public function status(Request $request, string $reference): JsonResponse
    {

        $transaction = Transaction::where('reference', $reference)
            ->where('transaction_type', 'featured_job')
            ->first();

        if (!$transaction) {
            return $this->error('Transaction not found', 404);
        }

        // Re-query Pesapal if still in-flight — same as PaymentController
        if (in_array($transaction->status, ['pending', 'processing'])
            && $transaction->gateway_reference) {
            try {
                $statusData = $this->pesapal->getTransactionStatus($transaction->gateway_reference);
                $previousStatus = $transaction->status;

                $this->applyStatus($transaction, $statusData);
                $transaction->refresh();

                if ($previousStatus !== $transaction->status) {
                    $this->updateNotificationStatus(
                        $reference,
                        $transaction->status,
                        $statusData['confirmation_code'] ?? null
                    );

                    if ($transaction->status === 'successful') {
                        $this->sendPayerConfirmation($transaction);
                    }
                }

            } catch (\Exception $e) {
                Log::warning('[FeaturedJob] Status re-query failed', ['error' => $e->getMessage()]);
            }
        }

        return response()->json([
            'success'      => true,
            'status'       => $transaction->status,
            'amount'       => $transaction->formatted_amount,
            'reference'    => $transaction->reference,
            'plan'         => $transaction->subscription_plan,
            'confirmed_at' => $transaction->confirmed_at,
        ]);
    }

    // ─────────────────────────────────────────────────────────────────────
    // Private: map Pesapal status_code — identical to PaymentController
    // ─────────────────────────────────────────────────────────────────────
    private function applyStatus(Transaction $transaction, array $data): void
    {
        $code = (int)($data['status_code'] ?? -1);

        Log::info('[FeaturedJob] Applying status', [
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

    // ─────────────────────────────────────────────────────────────────────
    // Private: create Notification record
    // ─────────────────────────────────────────────────────────────────────
    private function saveNotification(
        array  $validated,
        array  $features,
        string $packageName,
        int    $days,
        string $reference,
        string $trackingId,
        string $paymentStatus
    ): Notification {
        return Notification::create([
            'type'     => 'featured_job_request',
            'title'    => "Featured Job — {$packageName} ({$days} days)",
            'message'  => sprintf(
                "%s %s <%s>%s — %s — %s %s (USD %.2f) — Ref: %s",
                $validated['first_name'],
                $validated['last_name'],
                $validated['email'],
                !empty($validated['company_name']) ? ' | ' . $validated['company_name'] : '',
                $packageName,
                $validated['currency'],
                number_format((float) $validated['amount_local'], 2),
                (float) $validated['amount_usd'],
                $reference
            ),
            'data' => [
                // Contact
                'first_name'   => $validated['first_name'],
                'last_name'    => $validated['last_name'],
                'email'        => $validated['email'],
                'phone'        => $validated['phone'] ?? null,
                'company_name' => $validated['company_name'] ?? null,

                // Package
                'plan'                 => $validated['plan'],
                'package_display_name' => $packageName,
                'package_features'     => $features,
                'featured_days'        => $days,
                'amount_usd'           => $validated['amount_usd'],
                'amount_local'         => $validated['amount_local'],
                'currency'             => $validated['currency'],
                'country_code'         => $validated['country_code'] ?? 'UG',

                // Payment tracking
                'merchant_reference' => $reference,
                'order_tracking_id'  => $trackingId,
                'payment_status'     => $paymentStatus,

                // Job content
                'job_details_text' => $validated['job_details_text'] ?? null,
                'job_file_name'    => $validated['job_file_name'] ?? null,
                'job_file_mime'    => $validated['job_file_mime'] ?? null,
                'has_file'         => !empty($validated['job_file_base64'] ?? null),

                // Meta
                'submitted_at' => now()->toIso8601String(),
            ],
            'status'   => 'unread',
            'priority' => 'high',
            'user_id'  => null, // anonymous
        ]);
    }

    // ─────────────────────────────────────────────────────────────────────
    // Private: update notification when payment status changes
    // ─────────────────────────────────────────────────────────────────────
    private function updateNotificationStatus(
        string  $reference,
        string  $paymentStatus,
        ?string $confirmationCode
    ): void {
        $notification = Notification::where('type', 'featured_job_request')
            ->whereJsonContains('data->merchant_reference', $reference)
            ->first();

        if (!$notification) return;

        $data = $notification->data ?? [];
        $data['payment_status']       = $paymentStatus;
        $data['payment_confirmed_at'] = now()->toIso8601String();

        if ($confirmationCode) {
            $data['confirmation_code'] = $confirmationCode;
        }

        $prefix = match($paymentStatus) {
            'successful' => '✅ PAID — ',
            'failed'     => '❌ FAILED — ',
            'refunded'   => '↩️ REFUNDED — ',
            default      => '',
        };

        $notification->update([
            'data'     => $data,
            'priority' => $paymentStatus === 'successful' ? 'urgent' : $notification->priority,
            'title'    => $prefix . preg_replace('/^[✅❌↩️]\s\w+\s—\s/', '', $notification->title),
        ]);
    }

    // ─────────────────────────────────────────────────────────────────────
    // Private: admin email on initiation (job details + file if present)
    // ─────────────────────────────────────────────────────────────────────
    private function sendAdminEmail(
        array  $validated,
        array  $features,
        string $packageName,
        int    $days,
        string $reference
    ): void {
        try {
            $hasFile      = !empty($validated['job_file_base64']);
            $fileContents = $hasFile ? base64_decode($validated['job_file_base64']) : null;
            $fileName     = $validated['job_file_name'] ?? 'job_posting';
            $jobText      = $validated['job_details_text'] ?? null;

            $featuresHtml = implode('', array_map(
                fn($f) => "<li style='margin-bottom:4px;'>✅ " . htmlspecialchars($f) . "</li>",
                $features
            ));

            $jobSection = $hasFile
                ? "<p><strong>📎 Job File:</strong> {$fileName} <em>(see attachment)</em></p>"
                : "<div style='background:#f9fafb;border:1px solid #e5e7eb;border-radius:6px;padding:16px;margin-top:8px;'>"
                  . "<pre style='margin:0;font-size:12px;white-space:pre-wrap;color:#374151;'>"
                  . htmlspecialchars($jobText ?? 'No job details provided.')
                  . "</pre></div>";

            $amountFormatted = $validated['currency'] . ' '
                . number_format((float) $validated['amount_local'], 2)
                . ' (USD ' . number_format((float) $validated['amount_usd'], 2) . ')';

            Mail::send([], [], function ($mail) use (
                $validated, $featuresHtml, $jobSection,
                $packageName, $days, $reference,
                $amountFormatted, $hasFile, $fileContents, $fileName
            ) {
                $mail->to('jobpost@stardenaworks.com')
                        ->cc('samuelkiiraeluk@gmail.com')
                        ->subject("⭐ Featured Job Request — {$packageName} — {$reference}");

                $mail->html("
                <html><body style='font-family:Arial,sans-serif;max-width:640px;margin:0 auto;color:#1f2937;'>
                    <div style='background:#f59e0b;padding:24px;border-radius:8px 8px 0 0;text-align:center;'>
                        <h2 style='color:#fff;margin:0;font-size:22px;'>⭐ New Featured Job Request</h2>
                        <p style='color:#fff;opacity:.9;margin:6px 0 0;font-size:14px;'>
                            Payment initiated — awaiting Pesapal confirmation
                        </p>
                    </div>
                    <div style='background:#fff;padding:28px;border:1px solid #e5e7eb;border-top:none;border-radius:0 0 8px 8px;'>

                        <h3 style='color:#1e3a8a;border-bottom:2px solid #f59e0b;padding-bottom:8px;font-size:16px;'>
                            📦 Package Details
                        </h3>
                        <table style='width:100%;border-collapse:collapse;margin-bottom:8px;font-size:14px;'>
                            <tr><td style='padding:6px 0;color:#6b7280;width:35%;'>Package</td>
                                <td style='padding:6px 0;font-weight:bold;'>{$packageName} ({$days} days)</td></tr>
                            <tr><td style='padding:6px 0;color:#6b7280;'>Amount</td>
                                <td style='padding:6px 0;font-weight:bold;'>{$amountFormatted}</td></tr>
                            <tr><td style='padding:6px 0;color:#6b7280;'>Reference</td>
                                <td style='padding:6px 0;font-family:monospace;font-size:13px;'>{$reference}</td></tr>
                        </table>
                        <ul style='font-size:13px;padding-left:20px;margin:8px 0 0;'>{$featuresHtml}</ul>

                        <h3 style='color:#1e3a8a;border-bottom:2px solid #f59e0b;padding-bottom:8px;font-size:16px;margin-top:24px;'>
                            👤 Contact Details
                        </h3>
                        <table style='width:100%;border-collapse:collapse;font-size:14px;'>
                            <tr><td style='padding:6px 0;color:#6b7280;width:35%;'>Name</td>
                                <td style='padding:6px 0;'>{$validated['first_name']} {$validated['last_name']}</td></tr>
                            <tr><td style='padding:6px 0;color:#6b7280;'>Email</td>
                                <td style='padding:6px 0;'>
                                    <a href='mailto:{$validated['email']}' style='color:#2563eb;'>
                                        {$validated['email']}
                                    </a>
                                </td></tr>
                            <tr><td style='padding:6px 0;color:#6b7280;'>Phone</td>
                                <td style='padding:6px 0;'>" . ($validated['phone'] ?? '—') . "</td></tr>
                            <tr><td style='padding:6px 0;color:#6b7280;'>Company</td>
                                <td style='padding:6px 0;'>" . ($validated['company_name'] ?? '—') . "</td></tr>
                        </table>

                        <h3 style='color:#1e3a8a;border-bottom:2px solid #f59e0b;padding-bottom:8px;font-size:16px;margin-top:24px;'>
                            📋 Job Posting
                        </h3>
                        {$jobSection}

                        <div style='background:#fffbeb;border:1px solid #f59e0b;border-radius:6px;
                                    padding:14px;margin-top:24px;font-size:13px;'>
                            <strong>⚠️ Action Required:</strong> Once Pesapal confirms payment,
                            feature this job on the platform. Payment confirmation is automatic via IPN —
                            check the admin panel or wait for the confirmed email.
                        </div>

                    </div>
                </body></html>");

                if ($hasFile && $fileContents) {
                    $mail->attachData($fileContents, $fileName);
                }
            });

            Log::info('[FeaturedJob] Admin email sent', ['reference' => $reference]);

        } catch (\Exception $e) {
            // Non-fatal — payment still proceeds
            Log::error('[FeaturedJob] Admin email failed', [
                'error'     => $e->getMessage(),
                'reference' => $reference,
            ]);
        }
    }

    // ─────────────────────────────────────────────────────────────────────
    // Private: confirmation email to payer after successful payment
    // ─────────────────────────────────────────────────────────────────────
    private function sendPayerConfirmation(Transaction $transaction): void
    {
        // Guard: only send once
        $meta = $transaction->metadata ?? [];
        if (!empty($meta['payer_email_sent'])) return;

        try {
            $email       = $transaction->customer_email;
            $name        = $transaction->customer_name;
            $reference   = $transaction->reference;
            $packageName = $meta['package_display_name'] ?? $transaction->subscription_plan;
            $days        = $meta['featured_days'] ?? '—';
            $currency    = $meta['currency'] ?? $transaction->currency;
            $amount      = number_format((float) $transaction->amount, 2);

            Mail::send([], [], function ($mail) use (
                $email, $name, $reference, $packageName, $days, $currency, $amount
            ) {
                $firstName = explode(' ', trim($name))[0];

                $mail->to($email)
                     ->subject("✅ Payment Confirmed — Your Job Will Be Featured | Stardena Works");

                $mail->html("
                <html><body style='font-family:Arial,sans-serif;max-width:600px;margin:0 auto;color:#1f2937;'>
                    <div style='background:#1e3a8a;padding:24px;border-radius:8px 8px 0 0;text-align:center;'>
                        <h2 style='color:#fff;margin:0;font-size:22px;'>✅ Payment Confirmed</h2>
                        <p style='color:#93c5fd;margin:6px 0 0;font-size:14px;'>
                            Your job will be featured within 2 hours
                        </p>
                    </div>
                    <div style='background:#fff;padding:28px;border:1px solid #e5e7eb;
                                border-top:none;border-radius:0 0 8px 8px;'>

                        <p style='font-size:15px;'>Hi <strong>{$firstName}</strong>,</p>
                        <p style='font-size:14px;color:#374151;'>
                            Thank you for your payment! We've received your featured job request
                            and it will be <strong>live within 2 hours</strong>.
                        </p>

                        <div style='background:#f0fdf4;border:1px solid #86efac;border-radius:8px;
                                    padding:20px;margin:20px 0;'>
                            <table style='width:100%;border-collapse:collapse;font-size:14px;'>
                                <tr><td style='padding:5px 0;color:#6b7280;width:40%;'>Package</td>
                                    <td style='padding:5px 0;font-weight:bold;'>
                                        {$packageName} ({$days} days)
                                    </td></tr>
                                <tr><td style='padding:5px 0;color:#6b7280;'>Amount Paid</td>
                                    <td style='padding:5px 0;font-weight:bold;'>{$currency} {$amount}</td></tr>
                                <tr><td style='padding:5px 0;color:#6b7280;'>Reference</td>
                                    <td style='padding:5px 0;font-family:monospace;font-size:12px;'>
                                        {$reference}
                                    </td></tr>
                            </table>
                        </div>

                        <p style='font-size:14px;color:#374151;'>
                            Our team will review your job posting and activate the feature boost.
                            You'll receive another email once it's live on the platform.
                        </p>

                        <p style='font-size:14px;color:#374151;'>
                            Questions? Contact us at
                            <a href='mailto:support@stardenaworks.com' style='color:#2563eb;'>
                                support@stardenaworks.com
                            </a>
                        </p>

                        <p style='margin-top:24px;font-size:14px;'>
                            Best regards,<br>
                            <strong>The Stardena Works Team</strong>
                        </p>

                    </div>
                </body></html>");
            });

            // Mark as sent in metadata so IPN + callback don't double-send
            $meta['payer_email_sent']   = true;
            $meta['payer_email_sent_at'] = now()->toIso8601String();
            $transaction->update(['metadata' => $meta]);

            Log::info('[FeaturedJob] Payer confirmation sent', [
                'email'     => $email,
                'reference' => $reference,
            ]);

        } catch (\Exception $e) {
            Log::error('[FeaturedJob] Payer email failed', [
                'error'     => $e->getMessage(),
                'reference' => $transaction->reference,
            ]);
        }
    }
}