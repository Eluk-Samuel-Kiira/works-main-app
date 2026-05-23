<?php
// database/migrations/2024_xx_xx_fix_transactions_for_pesapal.php
// Run: php artisan make:migration fix_transactions_for_pesapal
// Then replace the content with this

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\{Schema, DB};

return new class extends Migration
{
    public function up(): void
    {
        // ── 1. Fix payment_plans type enum — add 'subscription' ──────────
        // The existing enum doesn't include 'subscription' which is what
        // CV enhancement plans are. We alter to add it.
        DB::statement("ALTER TABLE payment_plans MODIFY COLUMN type 
            ENUM('job_post','featured_job','company_verification','premium_profile','subscription')
            NOT NULL");

        // ── 2. Fix transactions table ─────────────────────────────────────
        Schema::table('transactions', function (Blueprint $table) {

            // company_id: make nullable — subscriptions have no company
            $table->dropForeign(['company_id']);
            $table->foreignId('company_id')
                  ->nullable()
                  ->change()
                  ->constrained()
                  ->nullOnDelete();

            // plan_id: make nullable — we may create transaction before plan lookup
            $table->dropForeign(['plan_id']);
            $table->foreignId('plan_id')
                  ->nullable()
                  ->change()
                  ->constrained('payment_plans')
                  ->nullOnDelete();
        });

        // ── 3. Fix payment_gateway enum — add 'pesapal' ──────────────────
        DB::statement("ALTER TABLE transactions MODIFY COLUMN payment_gateway 
            ENUM('flutterwave','stripe','paypal','pesapal','manual')
            DEFAULT 'pesapal'");

        // ── 4. Add subscription_plan column (basic/pro/elite) ────────────
        // Stores the human plan name separately from plan_id FK
        Schema::table('transactions', function (Blueprint $table) {
            if (!Schema::hasColumn('transactions', 'subscription_plan')) {
                $table->string('subscription_plan')->nullable()->after('transaction_type');
            }
            if (!Schema::hasColumn('transactions', 'subscription_period')) {
                $table->string('subscription_period')->nullable()->after('subscription_plan');
            }
        });
    }

    public function down(): void
    {
        Schema::table('transactions', function (Blueprint $table) {
            $table->dropColumn(['subscription_plan', 'subscription_period']);
        });

        DB::statement("ALTER TABLE transactions MODIFY COLUMN payment_gateway 
            ENUM('flutterwave','stripe','paypal') DEFAULT 'flutterwave'");

        DB::statement("ALTER TABLE payment_plans MODIFY COLUMN type 
            ENUM('job_post','featured_job','company_verification','premium_profile')
            NOT NULL");
    }
};