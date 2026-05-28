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
        $isSandbox = $pesapal->isSandbox();
        $ipnUrl = config('pesapal.ipn_url');
        
        $this->info('Registering IPN URL with Pesapal...');
        $this->info('Environment: ' . ($isSandbox ? 'SANDBOX' : 'PRODUCTION'));
        $this->info('IPN URL: ' . $ipnUrl);
        
        // Warning for production with localhost URL
        if (!$isSandbox && (str_contains($ipnUrl, '127.0.0.1') || str_contains($ipnUrl, 'localhost'))) {
            $this->error('⚠️  WARNING: You are in PRODUCTION mode but using a localhost IPN URL!');
            $this->error('Pesapal cannot send IPN notifications to localhost.');
            $this->error('Please update PESAPAL_IPN_URL in your .env to a publicly accessible URL.');
            
            if (!$this->confirm('Do you want to continue anyway?', false)) {
                return 1;
            }
        }
        
        try {
            $result = $pesapal->registerIpn($ipnUrl, 'POST');
            
            $this->newLine();
            $this->info('✅ IPN registered successfully!');
            $this->info('IPN ID: ' . ($result['ipn_id'] ?? 'N/A'));
            $this->info('IPN URL: ' . ($result['url'] ?? 'N/A'));
            $this->info('Environment: ' . ($isSandbox ? 'SANDBOX' : 'PRODUCTION'));
            
            $this->newLine();
            $this->info('Add this to your .env file:');
            $this->line("PESAPAL_IPN_ID=" . ($result['ipn_id'] ?? ''));
            
        } catch (\Exception $e) {
            $this->error('Failed to register IPN: ' . $e->getMessage());
            return 1;
        }
        
        return 0;
    }
}