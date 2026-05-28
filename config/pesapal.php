<?php
// MAIN APP — config/pesapal.php
// ═══════════════════════════════════════════════════════════════════════════

$isSandbox = env('PESAPAL_SANDBOX', true);

return [
    'sandbox' => $isSandbox,
    
    // Sandbox Keys
    'sandbox_consumer_key' => env('PESAPAL_SANDBOX_CONSUMER_KEY'),
    'sandbox_consumer_secret' => env('PESAPAL_SANDBOX_CONSUMER_SECRET'),
    
    // Production Keys
    'live_consumer_key' => env('PESAPAL_LIVE_CONSUMER_KEY'),
    'live_consumer_secret' => env('PESAPAL_LIVE_CONSUMER_SECRET'),
    
    // Dynamic consumer key/secret based on environment
    'consumer_key' => $isSandbox 
        ? env('PESAPAL_SANDBOX_CONSUMER_KEY')
        : env('PESAPAL_LIVE_CONSUMER_KEY'),
    
    'consumer_secret' => $isSandbox
        ? env('PESAPAL_SANDBOX_CONSUMER_SECRET')
        : env('PESAPAL_LIVE_CONSUMER_SECRET'),
    
    // Dynamic IPN ID based on environment
    'ipn_id' => $isSandbox
        ? env('PESAPAL_SANDBOX_IPN_ID')
        : env('PESAPAL_LIVE_IPN_ID'),
    
    // Dynamic URLs based on environment
    'callback_url' => $isSandbox
        ? env('PESAPAL_SANDBOX_CALLBACK_URL')
        : env('PESAPAL_LIVE_CALLBACK_URL'),
    
    'cancellation_url' => $isSandbox
        ? env('PESAPAL_SANDBOX_CANCELLATION_URL')
        : env('PESAPAL_LIVE_CANCELLATION_URL'),
    
    'ipn_url' => $isSandbox
        ? env('PESAPAL_SANDBOX_IPN_URL')
        : env('PESAPAL_LIVE_IPN_URL'),
];