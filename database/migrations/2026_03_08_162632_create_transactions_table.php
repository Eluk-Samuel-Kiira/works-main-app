<?php
// database/migrations/2024_01_01_000011_create_transactions_table.php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('transactions', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            
            // Relationships
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->foreignId('company_id')->constrained()->onDelete('cascade');
            $table->foreignId('plan_id')->constrained('payment_plans')->onDelete('cascade');
            
            // Transaction Details
            $table->string('reference')->unique(); // Our internal reference
            $table->string('gateway_reference')->nullable()->unique(); // Gateway's transaction ID
            $table->string('transaction_type');
            $table->decimal('amount', 12, 2);
            $table->decimal('gateway_fee', 8, 2)->default(0);
            $table->decimal('net_amount', 12, 2)->default(0);
            $table->string('currency', 3)->default('UGX');
            
            // Payment Status & Gateway
            $table->enum('status', ['pending', 'processing', 'successful', 'failed', 'cancelled', 'refunded', 'disputed'])->default('pending');
            $table->enum('payment_gateway', ['flutterwave', 'stripe', 'paypal'])->default('flutterwave');
            $table->string('payment_method')->nullable(); // card, mobile_money, bank_transfer, etc.
            $table->string('payment_channel')->nullable(); // visa, mastercard, mtn, airtel, etc.
            
            // Gateway Responses
            $table->json('gateway_request')->nullable(); // What we sent to gateway
            $table->json('gateway_response')->nullable(); // Raw response from gateway
            $table->json('gateway_webhook')->nullable(); // Webhook data from gateway
            $table->string('gateway_status')->nullable(); // Gateway's status code
            $table->text('gateway_message')->nullable(); // Gateway's status message
            
            // Customer & Payment Details
            $table->string('customer_email')->nullable();
            $table->string('customer_phone')->nullable();
            $table->string('customer_name')->nullable();
            $table->json('billing_address')->nullable();
            $table->json('shipping_address')->nullable();
            
            // Security & Verification
            $table->string('ip_address', 45)->nullable();
            $table->string('user_agent')->nullable();
            $table->string('device_fingerprint')->nullable();
            $table->boolean('is_flagged')->default(false);
            $table->text('flag_reason')->nullable();
            
            // Retry & Timeline
            $table->integer('retry_count')->default(0);
            $table->timestamp('last_retry_at')->nullable();
            $table->timestamp('processed_at')->nullable();
            $table->timestamp('confirmed_at')->nullable();
            $table->timestamp('refunded_at')->nullable();
            $table->timestamp('disputed_at')->nullable();
            
            // Metadata & Analytics
            $table->json('metadata')->nullable();
            $table->json('custom_fields')->nullable();
            $table->string('checkout_session_id')->nullable();
            $table->string('subscription_id')->nullable(); // For recurring payments
            
            $table->timestamps();

            // Comprehensive Indexes
            $table->index('uuid');
            $table->index('reference');
            $table->index('gateway_reference');
            $table->index(['user_id', 'status']);
            $table->index(['company_id', 'status']);
            $table->index(['plan_id', 'status']);
            $table->index('status');
            $table->index('payment_gateway');
            $table->index('payment_method');
            $table->index('customer_email');
            $table->index('created_at');
            $table->index('processed_at');
            $table->index(['status', 'created_at']);
            $table->index(['payment_gateway', 'status']);
        });
    }

    public function down()
    {
        Schema::dropIfExists('transactions');
    }
};