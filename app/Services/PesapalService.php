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

        if (empty($this->consumerKey) || empty($this->consumerSecret)) {
            Log::error('[Pesapal] Missing consumer key or secret');
            throw new \Exception('Pesapal configuration error: Missing consumer key or secret.');
        }
    }

    public function getToken(): string
    {
        return Cache::remember('pesapal_token', 240, function () {
            Log::info('[Pesapal] Requesting token', [
                'sandbox' => $this->isSandbox,
                'url' => "{$this->baseUrl}/api/Auth/RequestToken"
            ]);

            $response = Http::timeout(15)
                ->acceptJson()
                ->post("{$this->baseUrl}/api/Auth/RequestToken", [
                    'consumer_key' => $this->consumerKey,
                    'consumer_secret' => $this->consumerSecret,
                ]);

            if (!$response->successful()) {
                $error = $response->json();
                $message = $error['error']['message'] ?? $error['error']['code'] ?? $response->body();
                throw new \Exception("Pesapal token failed: {$message}");
            }

            $data = $response->json();
            $token = $data['token'] ?? null;

            if (empty($token)) {
                throw new \Exception('No token returned from Pesapal');
            }

            Log::info('[Pesapal] Token obtained');
            return $token;
        });
    }

    public function submitOrder(array $payload): array
    {
        if (empty($this->ipnId)) {
            throw new \Exception('IPN ID not configured.');
        }

        $token = $this->getToken();
        $payload['notification_id'] = $this->ipnId;

        Log::info('[Pesapal] Submitting order', [
            'id' => $payload['id'],
            'amount' => $payload['amount'],
            'currency' => $payload['currency'],
            'callback_url' => $payload['callback_url'] ?? 'not set',
        ]);

        $response = Http::timeout(30)
            ->acceptJson()
            ->withToken($token)
            ->post("{$this->baseUrl}/api/Transactions/SubmitOrderRequest", $payload);

        $responseData = $response->json();
        
        Log::info('[Pesapal] SubmitOrder response', [
            'status' => $response->status(),
            'has_error' => isset($responseData['error']),
            'response' => $responseData
        ]);

        if (!$response->successful() || isset($responseData['error'])) {
            $error = $responseData['error']['message'] ?? $responseData['message'] ?? 'Order submission failed';
            throw new \Exception("Pesapal order failed: {$error}");
        }

        // Check for required fields - Pesapal v3 uses different field names
        $merchantReference = $responseData['merchant_reference'] ?? $responseData['MerchantReference'] ?? null;
        $orderTrackingId = $responseData['order_tracking_id'] ?? $responseData['OrderTrackingId'] ?? null;
        $redirectUrl = $responseData['redirect_url'] ?? $responseData['RedirectURL'] ?? null;

        if (empty($redirectUrl)) {
            Log::error('[Pesapal] No redirect_url in response', ['response' => $responseData]);
            throw new \Exception('Pesapal did not return a payment URL. Please check your configuration.');
        }

        return [
            'merchant_reference' => $merchantReference,
            'order_tracking_id' => $orderTrackingId,
            'redirect_url' => $redirectUrl,
        ];
    }

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
            throw new \Exception('Failed to get transaction status: ' . $response->body());
        }

        return $response->json();
    }

    public function registerIpn(string $ipnUrl, string $method = 'POST'): array
    {
        $token = $this->getToken();

        Log::info('[Pesapal] Registering IPN', ['url' => $ipnUrl]);

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
        
        return [
            'ipn_id' => $data['ipn_id'] ?? $data['IPNId'] ?? null,
            'url' => $data['url'] ?? $data['Url'] ?? null,
        ];
    }

    public function getIpnId(): string
    {
        if (empty($this->ipnId)) {
            throw new \Exception('IPN ID not configured.');
        }
        return $this->ipnId;
    }

    public function isSandbox(): bool
    {
        return $this->isSandbox;
    }
}