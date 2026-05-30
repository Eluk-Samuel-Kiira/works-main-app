<?php
// app/Console/Commands/RefreshPaymentPlans.php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;

class RefreshPaymentPlans extends Command
{
    protected $signature = 'payment-plans:refresh 
                            {--seed : Seed the payment plans (default behavior)}
                            {--truncate : Truncate table before seeding}
                            {--fresh : Drop and recreate table (use with caution)}';
    
    protected $description = 'Refresh payment plans from seeder';

    public function handle()
    {
        // Fresh option - full table recreation (use with caution on production)
        if ($this->option('fresh')) {
            $this->warn('⚠️  WARNING: This will drop and recreate the entire table!');
            if (!$this->confirm('Are you sure you want to continue?')) {
                $this->info('Operation cancelled.');
                return Command::SUCCESS;
            }
            
            $this->info('Dropping payment_plan_webs table...');
            \Schema::dropIfExists('payment_plan_webs');
            $this->info('✅ Table dropped');
            
            $this->info('Running migration to recreate table...');
            Artisan::call('migrate', [
                '--path' => 'database/migrations/2025_05_30_000001_create_payment_plan_webs_table.php',
                '--force' => true,
            ]);
            $this->info('✅ Table recreated');
        }
        
        // Truncate option - clear data but keep structure
        if ($this->option('truncate')) {
            $this->info('Truncating payment_plan_webs table...');
            DB::table('payment_plan_webs')->truncate();
            $this->info('✅ Table truncated');
        }
        
        // Seed the plans (always run)
        $this->info('Seeding payment plans...');
        Artisan::call('db:seed', [
            '--class' => 'PaymentPlanWebSeeder',
            '--force' => true,
        ]);
        
        $output = Artisan::output();
        if (!empty($output)) {
            $this->line($output);
        }
        
        // Show final count
        $count = DB::table('payment_plan_webs')->count();
        $this->info("✅ Payment plans refreshed successfully! Total plans: {$count}");
        
        return Command::SUCCESS;
    }
}