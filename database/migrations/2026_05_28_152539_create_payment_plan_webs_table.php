<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        
        Schema::create('payment_plan_webs', function (Blueprint $table) {
            $table->id();
            $table->enum('audience', ['seeker', 'employer', 'other1', 'other2', 'other3'])->default('seeker')->index();
            $table->string('name')->unique();
            $table->string('display_name');
            $table->text('description')->nullable();
            $table->decimal('price_usd', 10, 2);
            $table->enum('billing_period', ['weekly','quarterly','monthly', 'yearly', 'one_time'])->default('monthly');
            $table->json('features');
            $table->json('local_prices');
            $table->boolean('is_active')->default(true);
            $table->boolean('is_popular')->default(false);
            $table->string('badge_text')->nullable();
            $table->integer('sort_order')->default(0);
            $table->timestamps();

            $table->index(['audience', 'is_active', 'sort_order']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payment_plan_webs');
    }
};