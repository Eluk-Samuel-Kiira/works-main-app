<?php
// MAIN APP — config/pesapal.php
// ═══════════════════════════════════════════════════════════════════════════

return [
    'sandbox' => env('PESAPAL_SANDBOX', true),
    
    // Sandbox Keys (Ugandan Merchant)
    'sandbox_consumer_key' => env('PESAPAL_SANDBOX_CONSUMER_KEY', 'TDpigBOOhs+zAl8cwH2Fl82jJGyD8xev'),
    'sandbox_consumer_secret' => env('PESAPAL_SANDBOX_CONSUMER_SECRET', '1KpqkfsMaihIcOlhnBo/gBZ5smw='),
    
    // Production Keys
    'live_consumer_key' => env('PESAPAL_LIVE_CONSUMER_KEY', 's8nE6+WzqNy0lTGqrfjXnpzh7bGrIpEb'),
    'live_consumer_secret' => env('PESAPAL_LIVE_CONSUMER_SECRET', 'SI7FRlsJRxk4KPKwXv8Qjk1Lhwc='),
    
    // Dynamic consumer key/secret based on environment
    'consumer_key' => env('PESAPAL_SANDBOX', true) 
        ? env('PESAPAL_SANDBOX_CONSUMER_KEY', 'TDpigBOOhs+zAl8cwH2Fl82jJGyD8xev')
        : env('PESAPAL_LIVE_CONSUMER_KEY', 's8nE6+WzqNy0lTGqrfjXnpzh7bGrIpEb'),
    
    'consumer_secret' => env('PESAPAL_SANDBOX', true)
        ? env('PESAPAL_SANDBOX_CONSUMER_SECRET', '1KpqkfsMaihIcOlhnBo/gBZ5smw=')
        : env('PESAPAL_LIVE_CONSUMER_SECRET', 'SI7FRlsJRxk4KPKwXv8Qjk1Lhwc='),
    
    'ipn_id' => env('PESAPAL_IPN_ID'),
    
    // Callback URLs based on environment
    'callback_url' => env('PESAPAL_CALLBACK_URL', env('PESAPAL_SANDBOX', true)
        ? 'http://127.0.0.1:8001/payment/callback'
        : 'https://stardenaworks.com/payment/callback'),
    
    'cancellation_url' => env('PESAPAL_CANCELLATION_URL', env('PESAPAL_SANDBOX', true)
        ? 'http://127.0.0.1:8001/payment/cancelled'
        : 'https://stardenaworks.com/payment/cancelled'),
    
    // IPN URL (where Pesapal sends webhooks) - always points to MAIN APP
    'ipn_url' => env('PESAPAL_IPN_URL', env('PESAPAL_SANDBOX', true)
        ? 'http://127.0.0.1:8000/api/v1/payments/ipn'
        : 'https://ma1n.stardenaworks.com/api/v1/payments/ipn'),
];