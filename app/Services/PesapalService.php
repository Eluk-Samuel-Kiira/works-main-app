<?php
// MAIN APP: app/Services/PesapalService.php

namespace App\Services;

use Illuminate\Support\Facades\{Http, Cache, Log};

class PesapalService
{
    private string $baseUrl;
    private string $consumerKey;
    private string $consumerSecret;
    private string $ipnId;
    private bool $isSandbox;

    public function __construct()
    {
        $this->isSandbox = config('pesapal.sandbox', true);

        $this->baseUrl = $this->isSandbox
            ? 'https://cybqa.pesapal.com/pesapalv3'
            : 'https://pay.pesapal.com/v3';

        $this->consumerKey = config('pesapal.consumer_key');
        $this->consumerSecret = config('pesapal.consumer_secret');
        $this->ipnId = config('pesapal.ipn_id');

        Log::info('[Pesapal] Service initialized', [
            'sandbox' => $this->isSandbox,
            'base_url' => $this->baseUrl,
            'has_key' => !empty($this->consumerKey),
            'has_secret' => !empty($this->consumerSecret),
            'has_ipn_id' => !empty($this->ipnId),
        ]);
    }

    // ─────────────────────────────────────────────────────────────────────
    // Step 1 — Get Bearer Token (cached for 4 mins, expires in 5)
    // ─────────────────────────────────────────────────────────────────────
    public function getToken(): string
    {
        if (empty($this->consumerKey) || empty($this->consumerSecret)) {
            throw new \Exception('Pesapal consumer key or secret is missing. Please check configuration.');
        }

        return Cache::remember('pesapal_token', 240, function () {
            Log::info('[Pesapal] Requesting new token', [
                'sandbox' => $this->isSandbox,
                'key_prefix' => substr($this->consumerKey, 0, 10) . '...'
            ]);

            $response = Http::timeout(15)
                ->acceptJson()
                ->post("{$this->baseUrl}/api/Auth/RequestToken", [
                    'consumer_key' => $this->consumerKey,
                    'consumer_secret' => $this->consumerSecret,
                ]);

            Log::info('[Pesapal] Token response', [
                'status' => $response->status(),
                'body' => $response->body(),
            ]);

            if (!$response->successful()) {
                $error = $response->json();
                $message = $error['error']['message'] ?? $error['error']['code'] ?? 'Unknown error';
                throw new \Exception("Pesapal token failed ({$this->baseUrl}): {$message}");
            }

            $token = $response->json('token');
            if (empty($token)) {
                throw new \Exception('No token returned from Pesapal');
            }

            Log::info('[Pesapal] Token obtained successfully');
            return $token;
        });
    }

    // ─────────────────────────────────────────────────────────────────────
    // Step 2 — Submit Order Request → returns redirect_url
    // ─────────────────────────────────────────────────────────────────────
    public function submitOrder(array $payload): array
    {
        if (empty($this->ipnId)) {
            throw new \Exception('IPN ID not configured. Please register IPN first.');
        }

        $token = $this->getToken();

        $payload['notification_id'] = $this->ipnId;

        Log::info('[Pesapal] Submitting order', [
            'id' => $payload['id'],
            'amount' => $payload['amount'],
            'currency' => $payload['currency'],
        ]);

        $response = Http::timeout(30)
            ->acceptJson()
            ->withToken($token)
            ->post("{$this->baseUrl}/api/Transactions/SubmitOrderRequest", $payload);

        if (!$response->successful()) {
            Log::error('[Pesapal] SubmitOrder failed', [
                'payload' => $payload,
                'response' => $response->body(),
            ]);
            throw new \Exception('Pesapal order submission failed: ' . $response->body());
        }

        $data = $response->json();

        Log::info('[Pesapal] Order submitted', [
            'merchant_reference' => $data['merchant_reference'] ?? null,
            'order_tracking_id' => $data['order_tracking_id'] ?? null,
            'redirect_url' => $data['redirect_url'] ?? null,
        ]);

        return $data;
    }

    // ─────────────────────────────────────────────────────────────────────
    // Step 3 — Get Transaction Status
    // ─────────────────────────────────────────────────────────────────────
    public function getTransactionStatus(string $orderTrackingId): array
    {
        $token = $this->getToken();

        $response = Http::timeout(15)
            ->acceptJson()
            ->withToken($token)
            ->get("{$this->baseUrl}/api/Transactions/GetTransactionStatus", [
                'orderTrackingId' => $orderTrackingId,
            ]);

        if (!$response->successful()) {
            Log::error('[Pesapal] GetTransactionStatus failed', [
                'orderTrackingId' => $orderTrackingId,
                'response' => $response->body(),
            ]);
            throw new \Exception('Failed to get transaction status: ' . $response->body());
        }

        return $response->json();
    }

    // ─────────────────────────────────────────────────────────────────────
    // Register IPN URL
    // ─────────────────────────────────────────────────────────────────────
    public function registerIpn(string $ipnUrl, string $method = 'POST'): array
    {
        $token = $this->getToken();

        Log::info('[Pesapal] Registering IPN', ['url' => $ipnUrl, 'method' => $method]);

        $response = Http::timeout(15)
            ->acceptJson()
            ->withToken($token)
            ->post("{$this->baseUrl}/api/URLSetup/RegisterIPN", [
                'url' => $ipnUrl,
                'ipn_notification_type' => $method,
            ]);

        if (!$response->successful()) {
            throw new \Exception('IPN registration failed: ' . $response->body());
        }

        $data = $response->json();

        Log::info('[Pesapal] IPN registered', [
            'ipn_id' => $data['ipn_id'] ?? null,
            'url' => $data['url'] ?? null,
        ]);

        return $data;
    }

    public function getIpnId(): string
    {
        if (empty($this->ipnId)) {
            throw new \Exception('IPN ID not configured. Please run: php artisan pesapal:register-ipn');
        }
        return $this->ipnId;
    }

    public function isSandbox(): bool
    {
        return $this->isSandbox;
    }
}