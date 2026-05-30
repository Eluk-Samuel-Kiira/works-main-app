<?php
// app/Console/Commands/RefreshPaymentPlans.php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schema;

class RefreshPaymentPlans extends Command
{
    protected $signature = 'payment-plans:refresh {--fresh : Drop and recreate table before seeding}';
    protected $description = 'Refresh payment plans from seeder';

    public function handle()
    {
        if ($this->option('fresh')) {
            $this->info('Rolling back payment_plan_webs table...');
            
            // Method 1: Use migration rollback for specific table
            try {
                Artisan::call('migrate:rollback', [
                    '--path' => 'database/migrations/2026_05_28_152539_create_payment_plan_webs_table.php',
                    '--force' => true,
                ]);
                $this->info('Rolled back payment_plan_webs table');
            } catch (\Exception $e) {
                // If rollback fails, drop table directly
                $this->warn('Migration rollback failed, dropping table directly...');
                Schema::dropIfExists('payment_plan_webs');
                $this->info('Dropped payment_plan_webs table');
            }
            
            // Run the migration again
            $this->info('Running migration...');
            Artisan::call('migrate', [
                '--path' => 'database/migrations/2026_05_28_152539_create_payment_plan_webs_table.php',
                '--force' => true,
            ]);
            $this->info('Migration completed');
        }

        // Clear existing data and seed fresh
        $this->info('Clearing existing payment plans...');
        \DB::table('payment_plan_webs')->truncate();
        
        $this->info('Seeding payment plans...');
        Artisan::call('db:seed', [
            '--class' => 'PaymentPlanWebSeeder',
            '--force' => true,
        ]);

        $this->info(Artisan::output());
        $this->info('✅ Payment plans refreshed successfully!');
    }
}