<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $this->call([
            UserSeeder::class,
            IndustrySeeder::class,
            JobCategorySeeder::class,
            CompanySeeder::class,

            // Location & job details
            JobLocationSeeder::class,
            JobTypeSeeder::class,
            SalaryRangeSeeder::class,
            ExperienceLevelSeeder::class,
            EducationLevelSeeder::class,

            // payment system
            PaymentPlanSeeder::class,
            TransactionSeeder::class,

            JobPostSeeder::class,
            BlogSeeder::class,

            // API System (run last)
            // ApiCredentialsSeeder::class,
            // ApiSyncLogSeeder::class,

        ]);

    }
}
