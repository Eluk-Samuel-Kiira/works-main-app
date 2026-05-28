<?php
// MAIN APP — config/pesapal.php
// ═══════════════════════════════════════════════════════════════════════════

return [
    'sandbox' => env('PESAPAL_SANDBOX', true),
    
    // Sandbox Keys - MUST come from .env
    'sandbox_consumer_key' => env('PESAPAL_SANDBOX_CONSUMER_KEY'),
    'sandbox_consumer_secret' => env('PESAPAL_SANDBOX_CONSUMER_SECRET'),
    
    // Production Keys - MUST come from .env
    'live_consumer_key' => env('PESAPAL_LIVE_CONSUMER_KEY'),
    'live_consumer_secret' => env('PESAPAL_LIVE_CONSUMER_SECRET'),
    
    // Dynamic consumer key/secret based on environment
    'consumer_key' => env('PESAPAL_SANDBOX', true) 
        ? env('PESAPAL_SANDBOX_CONSUMER_KEY')
        : env('PESAPAL_LIVE_CONSUMER_KEY'),
    
    'consumer_secret' => env('PESAPAL_SANDBOX', true)
        ? env('PESAPAL_SANDBOX_CONSUMER_SECRET')
        : env('PESAPAL_LIVE_CONSUMER_SECRET'),
    
    'ipn_id' => env('PESAPAL_IPN_ID'),
    
    // Callback URLs - MUST come from .env
    'callback_url' => env('PESAPAL_CALLBACK_URL'),
    'cancellation_url' => env('PESAPAL_CANCELLATION_URL'),
    
    // IPN URL (where Pesapal sends webhooks) - from .env
    'ipn_url' => env('PESAPAL_IPN_URL'),
];