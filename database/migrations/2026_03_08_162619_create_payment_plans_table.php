<?php
// database/migrations/2024_01_01_000010_create_payment_plans_table.php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('payment_plans', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->enum('type', ['job_post', 'featured_job', 'company_verification', 'premium_profile']);
            $table->string('country_code')->default('UG');
            $table->decimal('amount', 10, 2);
            $table->string('currency')->default('UGX');
            $table->integer('duration_days')->nullable();
            $table->json('features')->nullable();
            $table->text('description')->nullable();
            $table->boolean('is_active')->default(true);
            $table->boolean('is_popular')->default(false);
            $table->integer('sort_order')->default(0);
            $table->string('stripe_price_id')->nullable();
            $table->string('flutterwave_plan_id')->nullable();
            $table->timestamps();

            $table->index(['type', 'is_active']);
            $table->index(['country_code', 'is_active']);
            $table->index('sort_order');
            $table->index('is_popular');
        });
    }

    public function down()
    {
        Schema::dropIfExists('payment_plans');
    }
};