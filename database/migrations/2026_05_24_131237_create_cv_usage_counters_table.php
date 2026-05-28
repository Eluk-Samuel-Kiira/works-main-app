<?php
// database/migrations/2024_01_01_000003_create_cv_usage_counters_table.php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('cv_usage_counters', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->unique()->constrained()->cascadeOnDelete();
            $table->integer('cv_reviews_count')->default(0);
            $table->integer('cv_rewrites_count')->default(0);
            $table->integer('cover_letters_count')->default(0);
            $table->timestamp('period_start')->nullable();  // For monthly resets
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('cv_usage_counters');
    }
};