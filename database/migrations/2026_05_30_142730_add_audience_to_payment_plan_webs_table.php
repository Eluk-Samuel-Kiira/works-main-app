<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('payment_plan_webs', function (Blueprint $table) {
            // Check if column exists before adding to avoid errors
            if (!Schema::hasColumn('payment_plan_webs', 'audience')) {
                $table->enum('audience', ['seeker', 'employer'])->default('seeker')->after('id');
            }
            
            if (!Schema::hasColumn('payment_plan_webs', 'display_name')) {
                $table->string('display_name')->after('name');
            }
            
            if (!Schema::hasColumn('payment_plan_webs', 'description')) {
                $table->text('description')->nullable()->after('display_name');
            }
            
            if (!Schema::hasColumn('payment_plan_webs', 'price_usd')) {
                $table->decimal('price_usd', 10, 2)->after('description');
            }
            
            if (!Schema::hasColumn('payment_plan_webs', 'billing_period')) {
                $table->enum('billing_period', ['monthly', 'yearly', 'one_time'])->default('monthly')->after('price_usd');
            }
            
            if (!Schema::hasColumn('payment_plan_webs', 'features')) {
                $table->json('features')->nullable()->after('billing_period');
            }
            
            if (!Schema::hasColumn('payment_plan_webs', 'local_prices')) {
                $table->json('local_prices')->nullable()->after('features');
            }
            
            if (!Schema::hasColumn('payment_plan_webs', 'is_active')) {
                $table->boolean('is_active')->default(true)->after('local_prices');
            }
            
            if (!Schema::hasColumn('payment_plan_webs', 'is_popular')) {
                $table->boolean('is_popular')->default(false)->after('is_active');
            }
            
            if (!Schema::hasColumn('payment_plan_webs', 'badge_text')) {
                $table->string('badge_text')->nullable()->after('is_popular');
            }
            
            if (!Schema::hasColumn('payment_plan_webs', 'sort_order')) {
                $table->integer('sort_order')->default(0)->after('badge_text');
            }
            
            // Add indexes for performance
            $table->index(['audience', 'is_active', 'sort_order'], 'idx_audience_active_sort');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('payment_plan_webs', function (Blueprint $table) {
            // Drop columns if they exist
            $columns = [
                'audience', 'display_name', 'description', 'price_usd', 
                'billing_period', 'features', 'local_prices', 'is_active', 
                'is_popular', 'badge_text', 'sort_order'
            ];
            
            foreach ($columns as $column) {
                if (Schema::hasColumn('payment_plan_webs', $column)) {
                    $table->dropColumn($column);
                }
            }
            
            // Drop the index
            $table->dropIndex('idx_audience_active_sort');
        });
    }
};