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
        Schema::table('job_locations', function (Blueprint $table) {
            // Add new columns for enhanced location data
            $table->string('city')->nullable()->after('district');
            $table->string('region')->default('East Africa')->after('country');
            $table->string('country_code', 2)->nullable()->after('country');
            $table->decimal('latitude', 10, 8)->nullable()->after('meta_description');
            $table->decimal('longitude', 11, 8)->nullable()->after('latitude');
            $table->string('timezone')->nullable()->after('longitude');
            $table->boolean('is_capital')->default(false)->after('is_active');
            $table->string('featured_image')->nullable()->after('meta_description');
            
            // Add indexes for better query performance
            $table->index('country_code');
            $table->index('region');
            $table->index('city');
            $table->index(['country_code', 'is_active']);
            $table->index(['latitude', 'longitude']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('job_locations', function (Blueprint $table) {
            $table->dropColumn([
                'city',
                'region',
                'country_code',
                'latitude',
                'longitude',
                'timezone',
                'is_capital',
                'featured_image'
            ]);
        });
    }
};