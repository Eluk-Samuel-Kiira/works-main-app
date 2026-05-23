<?php
// ═══════════════════════════════════════════════════════════════════════════
// MAIN APP — config/pesapal.php
// ═══════════════════════════════════════════════════════════════════════════
return [
    'sandbox'         => env('PESAPAL_SANDBOX', true),
    'consumer_key'    => env('PESAPAL_CONSUMER_KEY'),
    'consumer_secret' => env('PESAPAL_CONSUMER_SECRET'),
    'ipn_id'          => env('PESAPAL_IPN_ID'),           // register once, store here
    // These are Web app URLs — Pesapal redirects the customer browser here
    'callback_url'    => env('PESAPAL_CALLBACK_URL',    'http://127.0.0.1:8001/payment/callback'),
    'cancellation_url'=> env('PESAPAL_CANCELLATION_URL','http://127.0.0.1:8001/payment/cancelled'),
];
