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
        $this->info('Registering IPN URL with Pesapal...');
        
        $ipnUrl = env('PESAPAL_IPN_URL', 'http://127.0.0.1:8000/api/v1/payments/ipn');
        
        try {
            $result = $pesapal->registerIpn($ipnUrl, 'POST');
            
            $this->info('✅ IPN registered successfully!');
            $this->info('IPN ID: ' . ($result['ipn_id'] ?? 'N/A'));
            $this->info('IPN URL: ' . ($result['ipn_url'] ?? 'N/A'));
            
            $this->newLine();
            $this->info('Add this to your .env file:');
            $this->line("PESAPAL_IPN_ID=" . ($result['ipn_id'] ?? ''));
            
        } catch (\Exception $e) {
            $this->error('Failed to register IPN: ' . $e->getMessage());
        }
    }
}