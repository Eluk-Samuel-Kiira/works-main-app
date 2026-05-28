<?php
// MAIN APP — config/pesapal.php
// ═══════════════════════════════════════════════════════════════════════════

return [
    'sandbox' => env('PESAPAL_SANDBOX', true),
    
    // Sandbox Keys
    'sandbox_consumer_key' => env('PESAPAL_SANDBOX_CONSUMER_KEY'),
    'sandbox_consumer_secret' => env('PESAPAL_SANDBOX_CONSUMER_SECRET'),
    
    // Production Keys
    'live_consumer_key' => env('PESAPAL_LIVE_CONSUMER_KEY'),
    'live_consumer_secret' => env('PESAPAL_LIVE_CONSUMER_SECRET'),
    
    // Dynamic keys based on environment
    'consumer_key' => env('PESAPAL_SANDBOX', true) 
        ? env('PESAPAL_SANDBOX_CONSUMER_KEY')
        : env('PESAPAL_LIVE_CONSUMER_KEY'),
    
    'consumer_secret' => env('PESAPAL_SANDBOX', true)
        ? env('PESAPAL_SANDBOX_CONSUMER_SECRET')
        : env('PESAPAL_LIVE_CONSUMER_SECRET'),
    
    'ipn_id' => env('PESAPAL_IPN_ID'),
    
    // Dynamic URLs based on environment
    'callback_url' => env('PESAPAL_CALLBACK_URL'),
    'cancellation_url' => env('PESAPAL_CANCELLATION_URL'),
    'ipn_url' => env('PESAPAL_IPN_URL'),
];