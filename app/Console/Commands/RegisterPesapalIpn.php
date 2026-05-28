<?php
// MAIN APP: app/Console/Commands/RegisterPesapalIpn.php

namespace App\Console\Commands;

use App\Services\PesapalService;
use Illuminate\Console\Command;

class RegisterPesapalIpn extends Command
{
    protected $signature = 'pesapal:register-ipn';
    protected $description = 'Register IPN URL with Pesapal';

    public function handle(PesapalService $pesapal)
    {
        $isSandbox = config('pesapal.sandbox', true);
        $ipnUrl = config('pesapal.ipn_url');
        $consumerKey = config('pesapal.consumer_key');
        $consumerSecret = config('pesapal.consumer_secret');
        
        $this->info('╔═══════════════════════════════════════════════════════════╗');
        $this->info('║           Pesapal IPN Registration                        ║');
        $this->info('╚═══════════════════════════════════════════════════════════╝');
        $this->newLine();
        
        $this->info('📋 Configuration Check:');
        $this->line('   Environment:     ' . ($isSandbox ? '🔵 SANDBOX' : '🔴 PRODUCTION'));
        $this->line('   IPN URL:         ' . $ipnUrl);
        $this->line('   Consumer Key:    ' . (empty($consumerKey) ? '❌ MISSING' : '✅ ' . substr($consumerKey, 0, 15) . '...'));
        $this->line('   Consumer Secret: ' . (empty($consumerSecret) ? '❌ MISSING' : '✅ ' . substr($consumerSecret, 0, 15) . '...'));
        $this->newLine();
        
        // Validate required values
        if (empty($consumerKey) || empty($consumerSecret)) {
            $this->error('❌ ERROR: Consumer key or secret is missing!');
            $this->error('Please check your .env file:');
            $this->error('  PESAPAL_SANDBOX=' . ($isSandbox ? 'true' : 'false'));
            $this->error('  PESAPAL_LIVE_CONSUMER_KEY=...');
            $this->error('  PESAPAL_LIVE_CONSUMER_SECRET=...');
            return 1;
        }
        
        if (empty($ipnUrl)) {
            $this->error('❌ ERROR: IPN URL is missing!');
            $this->error('Please set PESAPAL_IPN_URL in your .env file');
            return 1;
        }
        
        // Warning for production with localhost URL
        if (!$isSandbox && (str_contains($ipnUrl, '127.0.0.1') || str_contains($ipnUrl, 'localhost'))) {
            $this->error('╔═══════════════════════════════════════════════════════════╗');
            $this->error('║  ⚠️  WARNING: PRODUCTION mode with LOCALHOST IPN URL!     ║');
            $this->error('║                                                           ║');
            $this->error('║  Pesapal CANNOT send IPN notifications to localhost.      ║');
            $this->error('║  You need a publicly accessible URL like:                 ║');
            $this->error('║  https://ma1n.stardenaworks.com/api/v1/payments/ipn       ║');
            $this->error('╚═══════════════════════════════════════════════════════════╝');
            $this->newLine();
            
            if (!$this->confirm('Do you want to continue anyway? (Not recommended)', false)) {
                return 1;
            }
        }
        
        $this->info('🚀 Registering IPN with Pesapal...');
        $this->newLine();
        
        try {
            $result = $pesapal->registerIpn($ipnUrl, 'POST');
            
            $this->newLine();
            $this->info('╔═══════════════════════════════════════════════════════════╗');
            $this->info('║  ✅ IPN REGISTERED SUCCESSFULLY!                          ║');
            $this->info('╚═══════════════════════════════════════════════════════════╝');
            $this->newLine();
            $this->info('📝 Registration Details:');
            $this->line('   IPN ID:      ' . ($result['ipn_id'] ?? 'N/A'));
            $this->line('   IPN URL:     ' . ($result['url'] ?? 'N/A'));
            $this->line('   Environment: ' . ($isSandbox ? 'SANDBOX' : 'PRODUCTION'));
            $this->line('   Method:      POST');
            
            $this->newLine();
            $this->info('📌 Add this to your .env file:');
            $this->line('   PESAPAL_IPN_ID=' . ($result['ipn_id'] ?? ''));
            
            $this->newLine();
            $this->info('💡 Next steps:');
            $this->line('   1. Add the IPN ID to your .env file');
            $this->line('   2. Run: php artisan config:clear');
            $this->line('   3. Test payments in ' . ($isSandbox ? 'SANDBOX' : 'PRODUCTION') . ' environment');
            
        } catch (\Exception $e) {
            $this->error('╔═══════════════════════════════════════════════════════════╗');
            $this->error('║  ❌ IPN REGISTRATION FAILED                                ║');
            $this->error('╚═══════════════════════════════════════════════════════════╝');
            $this->error('Error: ' . $e->getMessage());
            
            $this->newLine();
            $this->info('🔧 Troubleshooting tips:');
            $this->line('   1. Check that your consumer key/secret are correct');
            $this->line('   2. Verify the IPN URL is accessible:');
            $this->line('      curl -I ' . $ipnUrl);
            $this->line('   3. For production, ensure you are using LIVE keys, not sandbox keys');
            $this->line('   4. Check your internet connection');
            
            return 1;
        }
        
        return 0;
    }
}