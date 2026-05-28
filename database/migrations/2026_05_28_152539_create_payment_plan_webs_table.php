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
        Schema::create('payment_plan_webs', function (Blueprint $table) {
            $table->id();
            $table->string('name'); // basic, pro, elite
            $table->string('display_name'); // Basic, Pro, Elite
            $table->string('description')->nullable();
            $table->decimal('price_usd', 10, 2);
            $table->enum('billing_period', ['monthly', 'yearly']);
            $table->json('features')->nullable(); // JSON array of features
            $table->json('local_prices')->nullable(); // JSON of local currency prices
            $table->integer('sort_order')->default(0);
            $table->boolean('is_active')->default(true);
            $table->boolean('is_popular')->default(false);
            $table->string('badge_text')->nullable(); // "Save 40%", "Most Popular"
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('payment_plan_webs');
    }
};
