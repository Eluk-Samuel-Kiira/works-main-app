<?php
// database/seeders/TransactionSeeder.php

namespace Database\Seeders;

use App\Models\Job\{ Company  };
use App\Models\Auth\{ User };
use App\Models\Payments\{ PaymentPlan, Transaction };
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;

class TransactionSeeder extends Seeder
{
    public function run()
    {
        // Clear existing transactions
        Transaction::query()->delete();

        $users = User::limit(20)->get();
        $companies = Company::limit(15)->get();
        $plans = PaymentPlan::active()->get();

        if ($users->isEmpty() || $companies->isEmpty() || $plans->isEmpty()) {
            $this->command->warn('Need users, companies, and payment plans to seed transactions.');
            $this->command->warn('Please run UserSeeder, CompanySeeder, and PaymentPlanSeeder first.');
            return;
        }

        // Create successful transactions (70%)
        $this->createSuccessfulTransactions($users, $companies, $plans);

        // Create pending transactions (10%)
        $this->createPendingTransactions($users, $companies, $plans);

        // Create processing transactions (5%)
        $this->createProcessingTransactions($users, $companies, $plans);

        // Create failed transactions (10%)
        $this->createFailedTransactions($users, $companies, $plans);

        // Create refunded transactions (3%)
        $this->createRefundedTransactions($users, $companies, $plans);

        // Create disputed transactions (2%)
        $this->createDisputedTransactions($users, $companies, $plans);

        $this->command->info('Transactions seeded successfully!');
        $this->command->info('Total transactions: ' . Transaction::count());
        $this->command->info('Successful: ' . Transaction::where('status', 'successful')->count());
        $this->command->info('Pending: ' . Transaction::where('status', 'pending')->count());
        $this->command->info('Processing: ' . Transaction::where('status', 'processing')->count());
        $this->command->info('Failed: ' . Transaction::where('status', 'failed')->count());
        $this->command->info('Refunded: ' . Transaction::where('status', 'refunded')->count());
        $this->command->info('Disputed: ' . Transaction::where('status', 'disputed')->count());
        $this->command->info('Flutterwave: ' . Transaction::where('payment_gateway', 'flutterwave')->count());
        $this->command->info('Stripe: ' . Transaction::where('payment_gateway', 'stripe')->count());
        $this->command->info('PayPal: ' . Transaction::where('payment_gateway', 'paypal')->count());
    }

    private function createSuccessfulTransactions($users, $companies, $plans)
    {
        $count = 70; // 70% of total

        for ($i = 0; $i < $count; $i++) {
            $user = $users->random();
            $company = $companies->random();
            $plan = $plans->random();

            Transaction::factory()
                ->for($user)
                ->for($company)
                ->for($plan, 'plan') // Fixed: specify relationship name
                ->successful()
                ->create([
                    'transaction_type' => $plan->type,
                    'amount' => $plan->amount,
                    'created_at' => Carbon::now()->subDays(rand(1, 90)),
                    'processed_at' => Carbon::now()->subDays(rand(1, 90))->addMinutes(rand(1, 30)),
                    'confirmed_at' => Carbon::now()->subDays(rand(1, 90))->addMinutes(rand(5, 60)),
                ]);
        }
    }

    private function createPendingTransactions($users, $companies, $plans)
    {
        $count = 10; // 10% of total

        for ($i = 0; $i < $count; $i++) {
            $user = $users->random();
            $company = $companies->random();
            $plan = $plans->random();

            Transaction::factory()
                ->for($user)
                ->for($company)
                ->for($plan, 'plan') // Fixed: specify relationship name
                ->pending()
                ->create([
                    'transaction_type' => $plan->type,
                    'amount' => $plan->amount,
                    'created_at' => Carbon::now()->subHours(rand(1, 24)),
                ]);
        }
    }

    private function createProcessingTransactions($users, $companies, $plans)
    {
        $count = 5; // 5% of total

        for ($i = 0; $i < $count; $i++) {
            $user = $users->random();
            $company = $companies->random();
            $plan = $plans->random();

            Transaction::factory()
                ->for($user)
                ->for($company)
                ->for($plan, 'plan') // Fixed: specify relationship name
                ->processing()
                ->create([
                    'transaction_type' => $plan->type,
                    'amount' => $plan->amount,
                    'created_at' => Carbon::now()->subMinutes(rand(5, 60)),
                    'processed_at' => Carbon::now()->subMinutes(rand(1, 5)),
                ]);
        }
    }

    private function createFailedTransactions($users, $companies, $plans)
    {
        $count = 10; // 10% of total

        for ($i = 0; $i < $count; $i++) {
            $user = $users->random();
            $company = $companies->random();
            $plan = $plans->random();

            Transaction::factory()
                ->for($user)
                ->for($company)
                ->for($plan, 'plan') // Fixed: specify relationship name
                ->failed()
                ->create([
                    'transaction_type' => $plan->type,
                    'amount' => $plan->amount,
                    'created_at' => Carbon::now()->subDays(rand(1, 30)),
                    'processed_at' => Carbon::now()->subDays(rand(1, 30))->addMinutes(rand(1, 10)),
                ]);
        }
    }

    private function createRefundedTransactions($users, $companies, $plans)
    {
        $count = 3; // 3% of total

        for ($i = 0; $i < $count; $i++) {
            $user = $users->random();
            $company = $companies->random();
            $plan = $plans->random();

            // Create successful transaction first
            $transaction = Transaction::factory()
                ->for($user)
                ->for($company)
                ->for($plan, 'plan') // Fixed: specify relationship name
                ->successful()
                ->create([
                    'transaction_type' => $plan->type,
                    'amount' => $plan->amount,
                    'created_at' => Carbon::now()->subDays(rand(10, 60)),
                    'processed_at' => Carbon::now()->subDays(rand(10, 60))->addMinutes(rand(1, 30)),
                    'confirmed_at' => Carbon::now()->subDays(rand(10, 60))->addMinutes(rand(5, 60)),
                ]);

            // Then mark as refunded
            $transaction->markAsRefunded([
                'refund_reason' => 'Customer request',
                'refund_amount' => $transaction->amount,
                'refunded_by' => 'admin',
                'refund_date' => Carbon::now()->subDays(rand(1, 9))->toDateTimeString()
            ]);
        }
    }

    private function createDisputedTransactions($users, $companies, $plans)
    {
        $count = 2; // 2% of total

        for ($i = 0; $i < $count; $i++) {
            $user = $users->random();
            $company = $companies->random();
            $plan = $plans->random();

            // Create successful transaction first
            $transaction = Transaction::factory()
                ->for($user)
                ->for($company)
                ->for($plan, 'plan') // Fixed: specify relationship name
                ->successful()
                ->create([
                    'transaction_type' => $plan->type,
                    'amount' => $plan->amount,
                    'created_at' => Carbon::now()->subDays(rand(5, 30)),
                    'processed_at' => Carbon::now()->subDays(rand(5, 30))->addMinutes(rand(1, 30)),
                    'confirmed_at' => Carbon::now()->subDays(rand(5, 30))->addMinutes(rand(5, 60)),
                ]);

            // Then mark as disputed
            $transaction->markAsDisputed([
                'dispute_reason' => 'Unauthorized transaction',
                'dispute_date' => Carbon::now()->subDays(rand(1, 4))->toDateTimeString(),
                'case_id' => 'CASE-' . strtoupper(\Illuminate\Support\Str::random(8)),
                'status' => 'under_review'
            ]);
        }
    }
}