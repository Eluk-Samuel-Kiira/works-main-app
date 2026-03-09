<?php
// database/seeders/JobTypeSeeder.php

namespace Database\Seeders;

use App\Models\Job\JobType;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class JobTypeSeeder extends Seeder
{
    public function run()
    {
        $jobTypes = [
            [
                'name' => 'Full-time Jobs',
                'slug' => 'full-time-jobs-uganda',
                'description' => 'Find permanent full-time employment opportunities in Uganda with regular working hours and benefits',
                'meta_title' => 'Full-time Jobs in Uganda - Permanent Employment Opportunities',
                'meta_description' => 'Browse full-time jobs in Uganda. Find permanent employment with regular hours, benefits, and career growth opportunities across all industries.',
                'icon' => 'fa-briefcase',
                'is_active' => true,
                'sort_order' => 1
            ],
            [
                'name' => 'Part-time Jobs',
                'slug' => 'part-time-jobs-uganda',
                'description' => 'Discover part-time employment opportunities in Uganda with flexible working hours',
                'meta_title' => 'Part-time Jobs in Uganda - Flexible Work Opportunities',
                'meta_description' => 'Find part-time jobs in Uganda. Browse flexible work opportunities with reduced hours suitable for students, parents, and supplementary income.',
                'icon' => 'fa-clock',
                'is_active' => true,
                'sort_order' => 2
            ],
            [
                'name' => 'Contract Jobs',
                'slug' => 'contract-jobs-uganda',
                'description' => 'Find contract-based employment opportunities and fixed-term positions in Uganda',
                'meta_title' => 'Contract Jobs in Uganda - Fixed-term Employment',
                'meta_description' => 'Browse contract jobs in Uganda. Discover fixed-term employment opportunities, project-based work, and temporary positions across various sectors.',
                'icon' => 'fa-file-contract',
                'is_active' => true,
                'sort_order' => 3
            ],
            [
                'name' => 'Internship Opportunities',
                'slug' => 'internships-uganda',
                'description' => 'Discover internship programs and training opportunities for students and graduates in Uganda',
                'meta_title' => 'Internships in Uganda - Student & Graduate Opportunities',
                'meta_description' => 'Find internship opportunities in Uganda. Browse training programs, graduate internships, and work experience placements for students and fresh graduates.',
                'icon' => 'fa-user-graduate',
                'is_active' => true,
                'sort_order' => 4
            ],
            [
                'name' => 'Remote Jobs',
                'slug' => 'remote-jobs-uganda',
                'description' => 'Find work-from-home and remote employment opportunities available in Uganda',
                'meta_title' => 'Remote Jobs in Uganda - Work From Home Opportunities',
                'meta_description' => 'Browse remote jobs in Uganda. Discover work-from-home opportunities, online jobs, and virtual positions available for professionals across Uganda.',
                'icon' => 'fa-laptop-house',
                'is_active' => true,
                'sort_order' => 5
            ],
            [
                'name' => 'Freelance Work',
                'slug' => 'freelance-jobs-uganda',
                'description' => 'Discover freelance projects and independent contractor opportunities in Uganda',
                'meta_title' => 'Freelance Jobs in Uganda - Project-based Work',
                'meta_description' => 'Find freelance jobs in Uganda. Browse project-based work, independent contractor opportunities, and gig economy jobs across various skills.',
                'icon' => 'fa-user-tie',
                'is_active' => true,
                'sort_order' => 6
            ],
            [
                'name' => 'Temporary Jobs',
                'slug' => 'temporary-jobs-uganda',
                'description' => 'Find short-term and temporary employment opportunities across Uganda',
                'meta_title' => 'Temporary Jobs in Uganda - Short-term Employment',
                'meta_description' => 'Browse temporary jobs in Uganda. Discover short-term employment opportunities, seasonal work, and temporary positions in various industries.',
                'icon' => 'fa-calendar-alt',
                'is_active' => true,
                'sort_order' => 7
            ],
            [
                'name' => 'Volunteer Opportunities',
                'slug' => 'volunteer-jobs-uganda',
                'description' => 'Discover volunteer positions and community service opportunities in Uganda',
                'meta_title' => 'Volunteer Opportunities in Uganda - NGO & Community Work',
                'meta_description' => 'Find volunteer opportunities in Uganda. Browse NGO positions, community service work, and charitable organization roles across Uganda.',
                'icon' => 'fa-hands-helping',
                'is_active' => true,
                'sort_order' => 8
            ],
        ];

        foreach ($jobTypes as $jobType) {
            JobType::create($jobType);
        }
    }
}