<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\Job\{ Company, JobLocation, SalaryRange, EducationLevel, ExperienceLevel, 
    JobType, Industry, JobCategory, JobPost   };
use App\Models\Auth\{ User };

class JobPostSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Check if we have the necessary related data
        $this->checkRelatedData();

        // Create 150 job posts with various states
        $this->command->info('Creating 150 job posts...');

        // Create regular job posts (100)
        JobPost::factory()
            ->count(100)
            ->create();

        // Create featured jobs (15)
        JobPost::factory()
            ->featured()
            ->count(15)
            ->create();

        // Create urgent jobs (10)
        JobPost::factory()
            ->urgent()
            ->count(10)
            ->create();

        // Create expired jobs (10)
        JobPost::factory()
            ->expired()
            ->count(10)
            ->create();

        // Create remote jobs (10)
        JobPost::factory()
            ->remote()
            ->count(10)
            ->create();

        // Create high salary jobs (5)
        JobPost::factory()
            ->highSalary()
            ->count(5)
            ->create();

        // Create jobs with specific employment types
        $this->createJobsByEmploymentType();

        // Create jobs by location type
        $this->createJobsByLocationType();

        // Create jobs by currency (UGX and KES)
        $this->createJobsByCurrency();

        // Output statistics
        $this->outputStatistics();
    }

    /**
     * Check if related data exists
     */
    private function checkRelatedData(): void
    {
        $requiredModels = [
            'companies' => Company::count(),
            'job categories' => JobCategory::count(),
            'industries' => Industry::count(),
            'locations' => JobLocation::count(),
            'job types' => JobType::count(),
            'experience levels' => ExperienceLevel::count(),
            'education levels' => EducationLevel::count(),
            'salary ranges' => SalaryRange::count(),
            // 'employer users' => User::role('employer')->count(),
        ];

        $missing = [];
        foreach ($requiredModels as $name => $count) {
            if ($count === 0) {
                $missing[] = $name;
            }
        }

        // if (!empty($missing)) {
        //     $this->command->warn('Warning: The following related data is missing: ' . implode(', ', $missing));
        //     $this->command->warn('Job posts may be created with factory defaults, but consider running related seeders first.');
            
        //     if (!$this->command->confirm('Continue anyway?', true)) {
        //         $this->command->error('Seeding cancelled.');
        //         exit;
        //     }
        // }
    }

    /**
     * Create jobs by employment type
     */
    private function createJobsByEmploymentType(): void
    {
        $employmentTypes = [
            'full-time' => 30,
            'part-time' => 10,
            'contract' => 10,
            'temporary' => 5,
            'internship' => 5,
            'volunteer' => 5,
        ];

        foreach ($employmentTypes as $type => $count) {
            JobPost::factory()
                ->count($count)
                ->create([
                    'employment_type' => $type,
                ]);
        }
    }

    /**
     * Create jobs by location type
     */
    private function createJobsByLocationType(): void
    {
        $locationTypes = [
            'on-site' => 20,
            'hybrid' => 10,
        ];

        foreach ($locationTypes as $type => $count) {
            JobPost::factory()
                ->count($count)
                ->create([
                    'location_type' => $type,
                ]);
        }
    }

    /**
     * Create jobs by currency
     */
    private function createJobsByCurrency(): void
    {
        // UGX jobs (additional 10)
        JobPost::factory()
            ->count(10)
            ->create([
                'currency' => 'UGX',
            ]);

        // KES jobs (5)
        JobPost::factory()
            ->count(5)
            ->create([
                'currency' => 'KES',
            ]);
    }

    /**
     * Output seeding statistics
     */
    private function outputStatistics(): void
    {
        $totalJobs = JobPost::count();
        
        $this->command->info('====================================');
        $this->command->info('JOB POST SEEDER COMPLETED SUCCESSFULLY!');
        $this->command->info('====================================');
        $this->command->info('Total job posts created: ' . $totalJobs);
        $this->command->info('------------------------------------');
        $this->command->info('Breakdown by status:');
        $this->command->info('- Active: ' . JobPost::where('is_active', true)->count());
        $this->command->info('- Inactive: ' . JobPost::where('is_active', false)->count());
        $this->command->info('- Featured: ' . JobPost::where('is_featured', true)->count());
        $this->command->info('- Urgent: ' . JobPost::where('is_urgent', true)->count());
        $this->command->info('- Verified: ' . JobPost::where('is_verified', true)->count());
        $this->command->info('------------------------------------');
        $this->command->info('Breakdown by employment type:');
        $this->command->info('- Full-time: ' . JobPost::where('employment_type', 'full-time')->count());
        $this->command->info('- Part-time: ' . JobPost::where('employment_type', 'part-time')->count());
        $this->command->info('- Contract: ' . JobPost::where('employment_type', 'contract')->count());
        $this->command->info('- Temporary: ' . JobPost::where('employment_type', 'temporary')->count());
        $this->command->info('- Internship: ' . JobPost::where('employment_type', 'internship')->count());
        $this->command->info('- Volunteer: ' . JobPost::where('employment_type', 'volunteer')->count());
        $this->command->info('------------------------------------');
        $this->command->info('Breakdown by location type:');
        $this->command->info('- On-site: ' . JobPost::where('location_type', 'on-site')->count());
        $this->command->info('- Remote: ' . JobPost::where('location_type', 'remote')->count());
        $this->command->info('- Hybrid: ' . JobPost::where('location_type', 'hybrid')->count());
        $this->command->info('------------------------------------');
        $this->command->info('Breakdown by currency:');
        $this->command->info('- UGX: ' . JobPost::where('currency', 'UGX')->count());
        $this->command->info('- KES: ' . JobPost::where('currency', 'KES')->count());
        $this->command->info('====================================');
    }
}