<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\Job\SalaryRange;
use Illuminate\Support\Str;

class SalaryRangeSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // UGX Salary Ranges (Ugandan Shillings)
        $ugxRanges = [
            // Entry Level / Internship
            [
                'name' => 'Below 500,000 UGX',
                'slug' => Str::slug('Below 500000 UGX'),
                'min_salary' => 0,
                'max_salary' => 499999,
                'currency' => 'UGX',
                'meta_title' => 'Jobs with salary below 500,000 UGX',
                'meta_description' => 'Entry level positions, internships, and part-time jobs with monthly salary below 500,000 Ugandan Shillings.',
                'is_active' => true,
                'sort_order' => 1,
            ],
            [
                'name' => '500,000 - 1,000,000 UGX',
                'slug' => Str::slug('500000 - 1000000 UGX'),
                'min_salary' => 500000,
                'max_salary' => 1000000,
                'currency' => 'UGX',
                'meta_title' => 'Jobs with salary 500,000 - 1,000,000 UGX',
                'meta_description' => 'Entry to mid-level positions with monthly salary between 500,000 and 1,000,000 Ugandan Shillings.',
                'is_active' => true,
                'sort_order' => 2,
            ],
            [
                'name' => '1,000,000 - 1,500,000 UGX',
                'slug' => Str::slug('1000000 - 1500000 UGX'),
                'min_salary' => 1000000,
                'max_salary' => 1500000,
                'currency' => 'UGX',
                'meta_title' => 'Jobs with salary 1,000,000 - 1,500,000 UGX',
                'meta_description' => 'Mid-level positions with monthly salary between 1,000,000 and 1,500,000 Ugandan Shillings.',
                'is_active' => true,
                'sort_order' => 3,
            ],
            [
                'name' => '1,500,000 - 2,000,000 UGX',
                'slug' => Str::slug('1500000 - 2000000 UGX'),
                'min_salary' => 1500000,
                'max_salary' => 2000000,
                'currency' => 'UGX',
                'meta_title' => 'Jobs with salary 1,500,000 - 2,000,000 UGX',
                'meta_description' => 'Mid to senior-level positions with monthly salary between 1,500,000 and 2,000,000 Ugandan Shillings.',
                'is_active' => true,
                'sort_order' => 4,
            ],
            [
                'name' => '2,000,000 - 2,500,000 UGX',
                'slug' => Str::slug('2000000 - 2500000 UGX'),
                'min_salary' => 2000000,
                'max_salary' => 2500000,
                'currency' => 'UGX',
                'meta_title' => 'Jobs with salary 2,000,000 - 2,500,000 UGX',
                'meta_description' => 'Senior-level positions with monthly salary between 2,000,000 and 2,500,000 Ugandan Shillings.',
                'is_active' => true,
                'sort_order' => 5,
            ],
            [
                'name' => '2,500,000 - 3,000,000 UGX',
                'slug' => Str::slug('2500000 - 3000000 UGX'),
                'min_salary' => 2500000,
                'max_salary' => 3000000,
                'currency' => 'UGX',
                'meta_title' => 'Jobs with salary 2,500,000 - 3,000,000 UGX',
                'meta_description' => 'Senior to management-level positions with monthly salary between 2,500,000 and 3,000,000 Ugandan Shillings.',
                'is_active' => true,
                'sort_order' => 6,
            ],
            [
                'name' => '3,000,000 - 4,000,000 UGX',
                'slug' => Str::slug('3000000 - 4000000 UGX'),
                'min_salary' => 3000000,
                'max_salary' => 4000000,
                'currency' => 'UGX',
                'meta_title' => 'Jobs with salary 3,000,000 - 4,000,000 UGX',
                'meta_description' => 'Management positions with monthly salary between 3,000,000 and 4,000,000 Ugandan Shillings.',
                'is_active' => true,
                'sort_order' => 7,
            ],
            [
                'name' => '4,000,000 - 5,000,000 UGX',
                'slug' => Str::slug('4000000 - 5000000 UGX'),
                'min_salary' => 4000000,
                'max_salary' => 5000000,
                'currency' => 'UGX',
                'meta_title' => 'Jobs with salary 4,000,000 - 5,000,000 UGX',
                'meta_description' => 'Senior management positions with monthly salary between 4,000,000 and 5,000,000 Ugandan Shillings.',
                'is_active' => true,
                'sort_order' => 8,
            ],
            [
                'name' => '5,000,000 - 7,000,000 UGX',
                'slug' => Str::slug('5000000 - 7000000 UGX'),
                'min_salary' => 5000000,
                'max_salary' => 7000000,
                'currency' => 'UGX',
                'meta_title' => 'Jobs with salary 5,000,000 - 7,000,000 UGX',
                'meta_description' => 'Executive-level positions with monthly salary between 5,000,000 and 7,000,000 Ugandan Shillings.',
                'is_active' => true,
                'sort_order' => 9,
            ],
            [
                'name' => '7,000,000 - 10,000,000 UGX',
                'slug' => Str::slug('7000000 - 10000000 UGX'),
                'min_salary' => 7000000,
                'max_salary' => 10000000,
                'currency' => 'UGX',
                'meta_title' => 'Jobs with salary 7,000,000 - 10,000,000 UGX',
                'meta_description' => 'Senior executive and director-level positions with monthly salary between 7,000,000 and 10,000,000 Ugandan Shillings.',
                'is_active' => true,
                'sort_order' => 10,
            ],
            [
                'name' => '10,000,000 - 15,000,000 UGX',
                'slug' => Str::slug('10000000 - 15000000 UGX'),
                'min_salary' => 10000000,
                'max_salary' => 15000000,
                'currency' => 'UGX',
                'meta_title' => 'Jobs with salary 10,000,000 - 15,000,000 UGX',
                'meta_description' => 'C-suite and top executive positions with monthly salary between 10,000,000 and 15,000,000 Ugandan Shillings.',
                'is_active' => true,
                'sort_order' => 11,
            ],
            [
                'name' => '15,000,000 - 20,000,000 UGX',
                'slug' => Str::slug('15000000 - 20000000 UGX'),
                'min_salary' => 15000000,
                'max_salary' => 20000000,
                'currency' => 'UGX',
                'meta_title' => 'Jobs with salary 15,000,000 - 20,000,000 UGX',
                'meta_description' => 'Top executive and CEO-level positions with monthly salary between 15,000,000 and 20,000,000 Ugandan Shillings.',
                'is_active' => true,
                'sort_order' => 12,
            ],
            [
                'name' => 'Above 20,000,000 UGX',
                'slug' => Str::slug('Above 20000000 UGX'),
                'min_salary' => 20000000,
                'max_salary' => null,
                'currency' => 'UGX',
                'meta_title' => 'Jobs with salary above 20,000,000 UGX',
                'meta_description' => 'High-level executive positions with monthly salary above 20,000,000 Ugandan Shillings.',
                'is_active' => true,
                'sort_order' => 13,
            ],
        ];

        // KES Salary Ranges (Kenyan Shillings)
        $kesRanges = [
            // Entry Level / Internship
            [
                'name' => 'Below 20,000 KES',
                'slug' => Str::slug('Below 20000 KES'),
                'min_salary' => 0,
                'max_salary' => 19999,
                'currency' => 'KES',
                'meta_title' => 'Jobs with salary below 20,000 KES',
                'meta_description' => 'Entry level positions, internships, and part-time jobs with monthly salary below 20,000 Kenyan Shillings.',
                'is_active' => true,
                'sort_order' => 14,
            ],
            [
                'name' => '20,000 - 40,000 KES',
                'slug' => Str::slug('20000 - 40000 KES'),
                'min_salary' => 20000,
                'max_salary' => 40000,
                'currency' => 'KES',
                'meta_title' => 'Jobs with salary 20,000 - 40,000 KES',
                'meta_description' => 'Entry to mid-level positions with monthly salary between 20,000 and 40,000 Kenyan Shillings.',
                'is_active' => true,
                'sort_order' => 15,
            ],
            [
                'name' => '40,000 - 60,000 KES',
                'slug' => Str::slug('40000 - 60000 KES'),
                'min_salary' => 40000,
                'max_salary' => 60000,
                'currency' => 'KES',
                'meta_title' => 'Jobs with salary 40,000 - 60,000 KES',
                'meta_description' => 'Mid-level positions with monthly salary between 40,000 and 60,000 Kenyan Shillings.',
                'is_active' => true,
                'sort_order' => 16,
            ],
            [
                'name' => '60,000 - 80,000 KES',
                'slug' => Str::slug('60000 - 80000 KES'),
                'min_salary' => 60000,
                'max_salary' => 80000,
                'currency' => 'KES',
                'meta_title' => 'Jobs with salary 60,000 - 80,000 KES',
                'meta_description' => 'Mid to senior-level positions with monthly salary between 60,000 and 80,000 Kenyan Shillings.',
                'is_active' => true,
                'sort_order' => 17,
            ],
            [
                'name' => '80,000 - 100,000 KES',
                'slug' => Str::slug('80000 - 100000 KES'),
                'min_salary' => 80000,
                'max_salary' => 100000,
                'currency' => 'KES',
                'meta_title' => 'Jobs with salary 80,000 - 100,000 KES',
                'meta_description' => 'Senior-level positions with monthly salary between 80,000 and 100,000 Kenyan Shillings.',
                'is_active' => true,
                'sort_order' => 18,
            ],
            [
                'name' => '100,000 - 150,000 KES',
                'slug' => Str::slug('100000 - 150000 KES'),
                'min_salary' => 100000,
                'max_salary' => 150000,
                'currency' => 'KES',
                'meta_title' => 'Jobs with salary 100,000 - 150,000 KES',
                'meta_description' => 'Management-level positions with monthly salary between 100,000 and 150,000 Kenyan Shillings.',
                'is_active' => true,
                'sort_order' => 19,
            ],
            [
                'name' => '150,000 - 200,000 KES',
                'slug' => Str::slug('150000 - 200000 KES'),
                'min_salary' => 150000,
                'max_salary' => 200000,
                'currency' => 'KES',
                'meta_title' => 'Jobs with salary 150,000 - 200,000 KES',
                'meta_description' => 'Senior management positions with monthly salary between 150,000 and 200,000 Kenyan Shillings.',
                'is_active' => true,
                'sort_order' => 20,
            ],
            [
                'name' => '200,000 - 250,000 KES',
                'slug' => Str::slug('200000 - 250000 KES'),
                'min_salary' => 200000,
                'max_salary' => 250000,
                'currency' => 'KES',
                'meta_title' => 'Jobs with salary 200,000 - 250,000 KES',
                'meta_description' => 'Executive-level positions with monthly salary between 200,000 and 250,000 Kenyan Shillings.',
                'is_active' => true,
                'sort_order' => 21,
            ],
            [
                'name' => '250,000 - 300,000 KES',
                'slug' => Str::slug('250000 - 300000 KES'),
                'min_salary' => 250000,
                'max_salary' => 300000,
                'currency' => 'KES',
                'meta_title' => 'Jobs with salary 250,000 - 300,000 KES',
                'meta_description' => 'Senior executive positions with monthly salary between 250,000 and 300,000 Kenyan Shillings.',
                'is_active' => true,
                'sort_order' => 22,
            ],
            [
                'name' => '300,000 - 400,000 KES',
                'slug' => Str::slug('300000 - 400000 KES'),
                'min_salary' => 300000,
                'max_salary' => 400000,
                'currency' => 'KES',
                'meta_title' => 'Jobs with salary 300,000 - 400,000 KES',
                'meta_description' => 'Director-level positions with monthly salary between 300,000 and 400,000 Kenyan Shillings.',
                'is_active' => true,
                'sort_order' => 23,
            ],
            [
                'name' => '400,000 - 500,000 KES',
                'slug' => Str::slug('400000 - 500000 KES'),
                'min_salary' => 400000,
                'max_salary' => 500000,
                'currency' => 'KES',
                'meta_title' => 'Jobs with salary 400,000 - 500,000 KES',
                'meta_description' => 'C-suite and top executive positions with monthly salary between 400,000 and 500,000 Kenyan Shillings.',
                'is_active' => true,
                'sort_order' => 24,
            ],
            [
                'name' => '500,000 - 750,000 KES',
                'slug' => Str::slug('500000 - 750000 KES'),
                'min_salary' => 500000,
                'max_salary' => 750000,
                'currency' => 'KES',
                'meta_title' => 'Jobs with salary 500,000 - 750,000 KES',
                'meta_description' => 'Top executive and CEO-level positions with monthly salary between 500,000 and 750,000 Kenyan Shillings.',
                'is_active' => true,
                'sort_order' => 25,
            ],
            [
                'name' => '750,000 - 1,000,000 KES',
                'slug' => Str::slug('750000 - 1000000 KES'),
                'min_salary' => 750000,
                'max_salary' => 1000000,
                'currency' => 'KES',
                'meta_title' => 'Jobs with salary 750,000 - 1,000,000 KES',
                'meta_description' => 'High-level executive positions with monthly salary between 750,000 and 1,000,000 Kenyan Shillings.',
                'is_active' => true,
                'sort_order' => 26,
            ],
            [
                'name' => 'Above 1,000,000 KES',
                'slug' => Str::slug('Above 1000000 KES'),
                'min_salary' => 1000000,
                'max_salary' => null,
                'currency' => 'KES',
                'meta_title' => 'Jobs with salary above 1,000,000 KES',
                'meta_description' => 'High-level executive positions with monthly salary above 1,000,000 Kenyan Shillings.',
                'is_active' => true,
                'sort_order' => 27,
            ],
        ];

        // Merge both arrays and create records
        $salaryRanges = array_merge($ugxRanges, $kesRanges);

        foreach ($salaryRanges as $range) {
            SalaryRange::create($range);
        }

        $this->command->info('====================================');
        $this->command->info('SALARY RANGE SEEDER COMPLETED SUCCESSFULLY!');
        $this->command->info('====================================');
        $this->command->info('Total salary ranges created: ' . count($salaryRanges));
        $this->command->info('UGX Ranges: ' . count($ugxRanges));
        $this->command->info('KES Ranges: ' . count($kesRanges));
        $this->command->info('====================================');
    }
}