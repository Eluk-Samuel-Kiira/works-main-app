 
# // ═══════════════════════════════════════════════════════════════════════════
# // ONE-TIME: Register your IPN URL with Pesapal (run once in tinker on Main)
# // ═══════════════════════════════════════════════════════════════════════════
# /*
# php artisan tinker
# >>> $svc = app(\App\Services\PesapalService::class);
# >>> $result = $svc->registerIpn('http://your-main-domain.com/api/v1/payments/ipn', 'GET');
# >>> $result
# // Copy the 'ipn_id' from the result and put it in .env as PESAPAL_IPN_ID
# */




// ═══════════════════════════════════════════════════════════════════════════
// MAIN APP — .env  (add these)
// ═══════════════════════════════════════════════════════════════════════════
/*