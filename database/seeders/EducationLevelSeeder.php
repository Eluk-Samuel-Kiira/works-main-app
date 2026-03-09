<?php
// database/seeders/EducationLevelSeeder.php

namespace Database\Seeders;

use App\Models\Job\EducationLevel;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class EducationLevelSeeder extends Seeder
{
    public function run()
    {
        $educationLevels = [
            // ===== NO FORMAL EDUCATION =====
            [
                'name' => 'No Formal Education',
                'slug' => 'no-formal-education-jobs-uganda',
                'meta_title' => 'No Formal Education Jobs in Uganda - Skills-based Opportunities',
                'meta_description' => 'Browse jobs in Uganda requiring no formal education. Find opportunities based on skills, experience, and on-the-job training.',
                'sort_order' => 1
            ],
            [
                'name' => 'Informal Education Only',
                'slug' => 'informal-education-jobs-uganda',
                'meta_title' => 'Informal Education Jobs in Uganda',
                'meta_description' => 'Jobs for candidates with informal education, self-taught skills, and practical experience.',
                'sort_order' => 2
            ],
            
            // ===== PRIMARY EDUCATION =====
            [
                'name' => 'Some Primary School',
                'slug' => 'some-primary-school-jobs-uganda',
                'meta_title' => 'Some Primary School Jobs in Uganda',
                'meta_description' => 'Jobs suitable for candidates with partial primary education.',
                'sort_order' => 3
            ],
            [
                'name' => 'Completed Primary School',
                'slug' => 'completed-primary-school-jobs-uganda',
                'meta_title' => 'Completed Primary School Jobs in Uganda - PLE Certificate',
                'meta_description' => 'Find jobs in Uganda requiring completion of primary education with PLE certificate.',
                'sort_order' => 4
            ],
            
            // ===== SECONDARY EDUCATION =====
            [
                'name' => 'Some Secondary School',
                'slug' => 'some-secondary-school-jobs-uganda',
                'meta_title' => 'Some Secondary School Jobs in Uganda',
                'meta_description' => 'Jobs suitable for candidates with partial secondary education.',
                'sort_order' => 5
            ],
            [
                'name' => 'O-Level / UCE',
                'slug' => 'o-level-uce-jobs-uganda',
                'meta_title' => 'O-Level (UCE) Jobs in Uganda - Secondary School Opportunities',
                'meta_description' => 'Find jobs in Uganda requiring O-Level/UCE certification. Entry-level and skilled positions for secondary school graduates.',
                'sort_order' => 6
            ],
            [
                'name' => 'A-Level / UACE',
                'slug' => 'a-level-uace-jobs-uganda',
                'meta_title' => 'A-Level (UACE) Jobs in Uganda - Advanced Secondary Opportunities',
                'meta_description' => 'Browse jobs in Uganda requiring A-Level/UACE certification. Advanced entry positions for secondary school leavers.',
                'sort_order' => 7
            ],
            
            // ===== VOCATIONAL & TECHNICAL =====
            [
                'name' => 'Vocational Certificate',
                'slug' => 'vocational-certificate-jobs-uganda',
                'meta_title' => 'Vocational Certificate Jobs in Uganda - Technical Skills',
                'meta_description' => 'Find jobs requiring vocational certificates in trades, crafts, and technical skills.',
                'sort_order' => 8
            ],
            [
                'name' => 'Technical Certificate',
                'slug' => 'technical-certificate-jobs-uganda',
                'meta_title' => 'Technical Certificate Jobs in Uganda - Skilled Trades',
                'meta_description' => 'Browse technical certificate positions in engineering, mechanics, construction and more.',
                'sort_order' => 9
            ],
            [
                'name' => 'Certificate',
                'slug' => 'certificate-jobs-uganda',
                'meta_title' => 'Certificate Jobs in Uganda - Vocational Opportunities',
                'meta_description' => 'Browse jobs in Uganda requiring certificate qualifications. Find vocational and technical positions across various industries.',
                'sort_order' => 10
            ],
            [
                'name' => 'Diploma',
                'slug' => 'diploma-jobs-uganda',
                'meta_title' => 'Diploma Jobs in Uganda - Technical Career Opportunities',
                'meta_description' => 'Find jobs in Uganda requiring diploma qualifications. Discover technical and specialized career opportunities for diploma holders.',
                'sort_order' => 11
            ],
            [
                'name' => 'Higher Diploma',
                'slug' => 'higher-diploma-jobs-uganda',
                'meta_title' => 'Higher Diploma Jobs in Uganda - Advanced Technical Roles',
                'meta_description' => 'Advanced technical and supervisory positions requiring higher diploma qualifications.',
                'sort_order' => 12
            ],
            [
                'name' => 'Postgraduate Diploma',
                'slug' => 'postgraduate-diploma-jobs-uganda',
                'meta_title' => 'Postgraduate Diploma Jobs in Uganda - Specialized Professional Roles',
                'meta_description' => 'Professional roles requiring postgraduate diploma qualifications in specialized fields.',
                'sort_order' => 13
            ],
            
            // ===== UNDERGRADUATE DEGREES =====
            [
                'name' => 'Some University (No Degree)',
                'slug' => 'some-university-jobs-uganda',
                'meta_title' => 'Some University (No Degree) Jobs in Uganda',
                'meta_description' => 'Jobs suitable for candidates with some university education but no completed degree.',
                'sort_order' => 14
            ],
            [
                'name' => 'Bachelor\'s Degree',
                'slug' => 'bachelors-degree-jobs-uganda',
                'meta_title' => 'Bachelor Degree Jobs in Uganda - Graduate Opportunities',
                'meta_description' => 'Browse jobs in Uganda requiring bachelor\'s degrees. Find graduate opportunities and professional positions for degree holders.',
                'sort_order' => 15
            ],
            [
                'name' => 'Bachelor\'s Degree with Honours',
                'slug' => 'bachelors-honours-jobs-uganda',
                'meta_title' => 'Bachelor\'s with Honours Jobs in Uganda',
                'meta_description' => 'Specialized roles requiring honours-level undergraduate qualifications.',
                'sort_order' => 16
            ],
            
            // ===== POSTGRADUATE DEGREES =====
            [
                'name' => 'Postgraduate Certificate',
                'slug' => 'postgraduate-certificate-jobs-uganda',
                'meta_title' => 'Postgraduate Certificate Jobs in Uganda',
                'meta_description' => 'Professional development and specialized roles requiring postgraduate certificates.',
                'sort_order' => 17
            ],
            [
                'name' => 'Master\'s Degree',
                'slug' => 'masters-degree-jobs-uganda',
                'meta_title' => 'Master Degree Jobs in Uganda - Postgraduate Opportunities',
                'meta_description' => 'Discover jobs in Uganda requiring master\'s degrees. Find postgraduate opportunities and advanced professional positions.',
                'sort_order' => 18
            ],
            [
                'name' => 'MPhil (Master of Philosophy)',
                'slug' => 'mphil-jobs-uganda',
                'meta_title' => 'MPhil Jobs in Uganda - Research-focused Master\'s Roles',
                'meta_description' => 'Research and academic positions requiring Master of Philosophy qualifications.',
                'sort_order' => 19
            ],
            [
                'name' => 'PhD / Doctorate',
                'slug' => 'phd-jobs-uganda',
                'meta_title' => 'PhD Jobs in Uganda - Doctoral Level Opportunities',
                'meta_description' => 'Find jobs in Uganda requiring PhD qualifications. Browse research, academic, and high-level professional opportunities.',
                'sort_order' => 20
            ],
            [
                'name' => 'Doctor of Business Administration (DBA)',
                'slug' => 'dba-jobs-uganda',
                'meta_title' => 'DBA Jobs in Uganda - Executive Doctorate Roles',
                'meta_description' => 'Executive and senior leadership roles requiring DBA qualifications.',
                'sort_order' => 21
            ],
            
            // ===== PROFESSIONAL CERTIFICATIONS =====
            [
                'name' => 'Professional Certification',
                'slug' => 'professional-certification-jobs-uganda',
                'meta_title' => 'Professional Certification Jobs in Uganda - Industry Certifications',
                'meta_description' => 'Jobs requiring professional certifications like CPA, ACCA, CIM, etc.',
                'sort_order' => 22
            ],
            [
                'name' => 'CPA / ACCA / Accounting Certification',
                'slug' => 'accounting-certification-jobs-uganda',
                'meta_title' => 'CPA & ACCA Jobs in Uganda - Accounting Professional Roles',
                'meta_description' => 'Accounting and finance positions requiring professional certification.',
                'sort_order' => 23
            ],
            [
                'name' => 'Engineering Certification (IEK, ERB)',
                'slug' => 'engineering-certification-jobs-uganda',
                'meta_title' => 'Certified Engineering Jobs in Uganda - Professional Engineers',
                'meta_description' => 'Engineering roles requiring professional registration and certification.',
                'sort_order' => 24
            ],
            [
                'name' => 'Medical/Health Professional License',
                'slug' => 'medical-license-jobs-uganda',
                'meta_title' => 'Medical License Jobs in Uganda - Healthcare Professionals',
                'meta_description' => 'Healthcare positions requiring professional medical licenses and registration.',
                'sort_order' => 25
            ],
            [
                'name' => 'Legal Professional (LLB, Dip Law)',
                'slug' => 'legal-professional-jobs-uganda',
                'meta_title' => 'Legal Professional Jobs in Uganda - Lawyers & Legal Officers',
                'meta_description' => 'Legal positions requiring law degrees and professional qualifications.',
                'sort_order' => 26
            ],
            [
                'name' => 'Teaching Certification (Grade III, Grade V)',
                'slug' => 'teaching-certification-jobs-uganda',
                'meta_title' => 'Teaching Certification Jobs in Uganda - Qualified Teachers',
                'meta_description' => 'Teaching positions requiring professional teaching certificates and qualifications.',
                'sort_order' => 27
            ],
            [
                'name' => 'IT Certification (Cisco, Microsoft, CompTIA)',
                'slug' => 'it-certification-jobs-uganda',
                'meta_title' => 'IT Certification Jobs in Uganda - Tech Professionals',
                'meta_description' => 'Technology roles requiring industry certifications in networking, security, development.',
                'sort_order' => 28
            ],
            [
                'name' => 'Project Management (PMP, PRINCE2)',
                'slug' => 'project-management-certification-jobs-uganda',
                'meta_title' => 'Project Management Jobs in Uganda - Certified Project Managers',
                'meta_description' => 'Project management positions requiring PMP, PRINCE2 or equivalent certifications.',
                'sort_order' => 29
            ],
            [
                'name' => 'HR Certification (HRMAU, CIPD)',
                'slug' => 'hr-certification-jobs-uganda',
                'meta_title' => 'HR Certification Jobs in Uganda - Human Resource Professionals',
                'meta_description' => 'Human resource positions requiring professional HR certifications.',
                'sort_order' => 30
            ],
            [
                'name' => 'Marketing Certification (CIM, Digital Marketing)',
                'slug' => 'marketing-certification-jobs-uganda',
                'meta_title' => 'Marketing Certification Jobs in Uganda - Marketing Professionals',
                'meta_description' => 'Marketing roles requiring professional certifications in marketing and digital marketing.',
                'sort_order' => 31
            ],
            
            // ===== ONGOING EDUCATION =====
            [
                'name' => 'Currently Enrolled in High School',
                'slug' => 'currently-enrolled-high-school-jobs-uganda',
                'meta_title' => 'Part-time Jobs for High School Students in Uganda',
                'meta_description' => 'Part-time and casual jobs suitable for current high school students.',
                'sort_order' => 32
            ],
            [
                'name' => 'Currently Enrolled in University',
                'slug' => 'currently-enrolled-university-jobs-uganda',
                'meta_title' => 'Part-time & Internship Jobs for University Students in Uganda',
                'meta_description' => 'Part-time roles, internships, and attachments for current university students.',
                'sort_order' => 33
            ],
            [
                'name' => 'Currently in Vocational Training',
                'slug' => 'currently-enrolled-vocational-jobs-uganda',
                'meta_title' => 'Apprenticeship & Training Jobs in Uganda',
                'meta_description' => 'Apprenticeships and trainee positions for those in vocational training.',
                'sort_order' => 34
            ],
            
            // ===== SPECIALIZED EDUCATION =====
            [
                'name' => 'Executive Education',
                'slug' => 'executive-education-jobs-uganda',
                'meta_title' => 'Executive Education Jobs in Uganda - Senior Leadership Roles',
                'meta_description' => 'Senior leadership roles requiring executive education and continuous professional development.',
                'sort_order' => 35
            ],
            [
                'name' => 'Fellowship',
                'slug' => 'fellowship-jobs-uganda',
                'meta_title' => 'Fellowship Programs in Uganda',
                'meta_description' => 'Fellowship opportunities for researchers, academics, and professionals.',
                'sort_order' => 36
            ],
            [
                'name' => 'Post-Doctoral Fellowship',
                'slug' => 'postdoctoral-fellowship-jobs-uganda',
                'meta_title' => 'Post-Doctoral Fellowships in Uganda',
                'meta_description' => 'Advanced research opportunities for recent PhD graduates.',
                'sort_order' => 37
            ],
            
            // ===== SHORT COURSES & WORKSHOPS =====
            [
                'name' => 'Short Course Certificate',
                'slug' => 'short-course-certificate-jobs-uganda',
                'meta_title' => 'Short Course Certificate Jobs in Uganda',
                'meta_description' => 'Jobs requiring specialized short course training and certificates.',
                'sort_order' => 38
            ],
            [
                'name' => 'Workshop/Training Attendance',
                'slug' => 'workshop-training-jobs-uganda',
                'meta_title' => 'Workshop & Training Jobs in Uganda',
                'meta_description' => 'Positions where workshop attendance and continuous learning are valued.',
                'sort_order' => 39
            ],
        ];

        foreach ($educationLevels as $level) {
            EducationLevel::firstOrCreate(
                ['name' => $level['name']],
                $level
            );
        }
        
        $this->command->info(count($educationLevels) . ' education levels seeded successfully!');
    }
}