<?php
// MAIN APP: app/Services/PesapalService.php
// Handles all Pesapal API communication

namespace App\Services;

use Illuminate\Support\Facades\{Http, Cache, Log};

class PesapalService
{
    private string $baseUrl;
    private string $consumerKey;
    private string $consumerSecret;
    private string $ipnId;

    public function __construct()
    {
        $sandbox = config('pesapal.sandbox', true);

        $this->baseUrl       = $sandbox
            ? 'https://cybqa.pesapal.com/pesapalv3'
            : 'https://pay.pesapal.com/v3';

        $this->consumerKey    = config('pesapal.consumer_key');
        $this->consumerSecret = config('pesapal.consumer_secret');
        $this->ipnId          = config('pesapal.ipn_id');
    }

    // ─────────────────────────────────────────────────────────────────────
    // Step 1 — Get Bearer Token (cached for 4 mins, expires in 5)
    // ─────────────────────────────────────────────────────────────────────
    public function getToken(): string
    {
        return Cache::remember('pesapal_token', 240, function () {
            $res = Http::timeout(15)
                ->acceptJson()
                ->post("{$this->baseUrl}/api/Auth/RequestToken", [
                    'consumer_key'    => $this->consumerKey,
                    'consumer_secret' => $this->consumerSecret,
                ]);

            if (!$res->successful() || empty($res->json('token'))) {
                Log::error('[Pesapal] Token request failed', ['body' => $res->body()]);
                throw new \Exception('Failed to obtain Pesapal token: ' . $res->body());
            }

            Log::info('[Pesapal] Token obtained successfully');
            return $res->json('token');
        });
    }

    // ─────────────────────────────────────────────────────────────────────
    // Step 2 — Submit Order Request → returns redirect_url
    // ─────────────────────────────────────────────────────────────────────
    public function submitOrder(array $payload): array
    {
        $token = $this->getToken();

        $res = Http::timeout(30)
            ->acceptJson()
            ->withToken($token)
            ->post("{$this->baseUrl}/api/Transactions/SubmitOrderRequest", $payload);

        if (!$res->successful()) {
            Log::error('[Pesapal] SubmitOrder failed', [
                'payload'  => $payload,
                'response' => $res->body(),
            ]);
            throw new \Exception('Pesapal order submission failed: ' . $res->body());
        }

        $data = $res->json();

        Log::info('[Pesapal] Order submitted', [
            'merchant_reference'  => $data['merchant_reference'] ?? null,
            'order_tracking_id'   => $data['order_tracking_id']  ?? null,
        ]);

        return $data;
    }

    // ─────────────────────────────────────────────────────────────────────
    // Step 3 — Get Transaction Status (called from callback + IPN)
    // ─────────────────────────────────────────────────────────────────────
    public function getTransactionStatus(string $orderTrackingId): array
    {
        $token = $this->getToken();

        $res = Http::timeout(15)
            ->acceptJson()
            ->withToken($token)
            ->get("{$this->baseUrl}/api/Transactions/GetTransactionStatus", [
                'orderTrackingId' => $orderTrackingId,
            ]);

        if (!$res->successful()) {
            Log::error('[Pesapal] GetTransactionStatus failed', [
                'orderTrackingId' => $orderTrackingId,
                'response'        => $res->body(),
            ]);
            throw new \Exception('Failed to get transaction status: ' . $res->body());
        }

        return $res->json();
    }

    // ─────────────────────────────────────────────────────────────────────
    // Register IPN URL (run once via artisan or on first boot)
    // ─────────────────────────────────────────────────────────────────────
    public function registerIpn(string $ipnUrl, string $method = 'GET'): array
    {
        $token = $this->getToken();

        $res = Http::timeout(15)
            ->acceptJson()
            ->withToken($token)
            ->post("{$this->baseUrl}/api/URLSetup/RegisterIPN", [
                'url'          => $ipnUrl,
                'ipn_notification_type' => $method,
            ]);

        if (!$res->successful()) {
            throw new \Exception('IPN registration failed: ' . $res->body());
        }

        Log::info('[Pesapal] IPN registered', ['ipn_url' => $ipnUrl, 'response' => $res->json()]);
        return $res->json();
    }

    public function getIpnId(): string
    {
        return $this->ipnId;
    }
}