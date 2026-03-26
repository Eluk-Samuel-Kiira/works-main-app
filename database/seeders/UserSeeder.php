<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\Auth\User;
use App\Models\Auth\LoginToken;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Illuminate\Support\Str;

class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Clear cached permissions first
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        // Instead of deleting, we'll use firstOrCreate to avoid duplicates
        // This is safer than deleting and recreating

        // Define roles for the job platform
        $roles = [
            'super_admin',
            'admin',
            'employer',           // Companies posting jobs
            'job_seeker',          // People looking for work
            'moderator',           // Reviews job posts
            'support',             // Customer support
        ];

        // Create roles with guard_name explicitly set (use firstOrCreate to avoid duplicates)
        $createdRoles = [];
        foreach ($roles as $role) {
            $createdRoles[$role] = Role::firstOrCreate(
                [
                    'name' => $role,
                    'guard_name' => 'web'
                ]
            );
        }

        // Define permissions by category
        $permissionCategories = [
            'Job Management' => [
                'create job',
                'view job',
                'edit job',
                'delete job',
                'archive job',
                'repost job',
                'feature job',
                'boost job',
                'close job',
                'fill job',
            ],
            
            'Job Categories' => [
                'view job categories',
                'create job categories',
                'edit job categories',
                'delete job categories',
                'manage job categories hierarchy',
                'import job categories',
                'export job categories',
            ],
            
            'Job Types' => [
                'view job types',
                'create job types',
                'edit job types',
                'delete job types',
                'manage job types',
                'import job types',
            ],
            
            'Industries' => [
                'view industries',
                'create industries',
                'edit industries',
                'delete industries',
                'manage industries',
                'import industries',
            ],
            
            'Experience Levels' => [
                'view experience levels',
                'create experience levels',
                'edit experience levels',
                'delete experience levels',
                'manage experience levels',
                'import experience levels',
            ],
            
            'Education Levels' => [
                'view education levels',
                'create education levels',
                'edit education levels',
                'delete education levels',
                'manage education levels',
                'import education levels',
            ],
            
            'Salary Ranges' => [
                'view salary ranges',
                'create salary ranges',
                'edit salary ranges',
                'delete salary ranges',
                'manage salary ranges',
                'import salary ranges',
            ],
            
            'Locations' => [
                'view locations',
                'create locations',
                'edit locations',
                'delete locations',
                'manage locations hierarchy',
                'import locations',
                'export locations',
            ],
            
            'Application Management' => [
                'apply for job',
                'view applications',
                'manage applications',
                'shortlist candidate',
                'reject candidate',
                'interview candidate',
                'hire candidate',
                'bulk process applications',
                'export applications',
                'add application notes',
                'schedule interview',
            ],
            
            'CV/Profile Management' => [
                'create cv',
                'edit cv',
                'upload cv',
                'delete cv',
                'download cv',
                'make cv public',
                'make cv private',
                'add work experience',
                'edit work experience',
                'delete work experience',
                'add education',
                'edit education',
                'delete education',
                'add skills',
                'add certifications',
                'add languages',
                'add portfolio',
                'add references',
            ],
            
            'Company Profile' => [
                'create company profile',
                'edit company profile',
                'view company profile',
                'delete company profile',
                'verify company',
                'add company logo',
                'add company photos',
                'add company videos',
                'manage company social links',
                'view company reviews',
            ],
            
            'Casual/Gig Work' => [
                'post casual job',
                'view casual jobs',
                'apply for casual work',
                'manage shifts',
                'view shift schedule',
                'clock in',
                'clock out',
                'mark attendance',
                'report hours',
                'approve timesheets',
                'view site assignments',
                'manage work sites',
            ],
            
            'Subscriptions & Payments' => [
                'view subscription plans',
                'subscribe to plan',
                'upgrade subscription',
                'downgrade subscription',
                'cancel subscription',
                'renew subscription',
                'view billing history',
                'download invoice',
                'manage payment methods',
                'apply promo code',
                'view payment plans',
                'create payment plans',
                'edit payment plans',
                'delete payment plans',
                'activate payment plans',
                'deactivate payment plans',
                'manage pricing tiers',
            ],
            
            'Company Subscriptions' => [
                'view company subscriptions',
                'create company subscriptions',
                'edit company subscriptions',
                'delete company subscriptions',
                'activate company subscriptions',
                'deactivate company subscriptions',
                'manage subscription plans',
                'renew subscriptions',
            ],
            
            'Job Promotions' => [
                'view job promotions',
                'create job promotions',
                'edit job promotions',
                'delete job promotions',
                'activate job promotions',
                'deactivate job promotions',
                'manage promotion campaigns',
                'view promotion analytics',
            ],
            
            'Messaging' => [
                'send message',
                'view messages',
                'delete message',
                'archive conversation',
                'send bulk message',
                'send email',
                'send SMS',
                'manage chat',
            ],
            
            'Reviews & Ratings' => [
                'rate worker',
                'rate employer',
                'view ratings',
                'edit rating',
                'delete rating',
                'report review',
                'respond to review',
            ],
            
            'Verification' => [
                'verify identity',
                'verify documents',
                'verify qualifications',
                'verify employment history',
                'request documents',
                'approve verification',
                'reject verification',
                'view verification status',
            ],
            
            'Reports & Analytics' => [
                'view job reports',
                'view application reports',
                'view hiring reports',
                'view worker reports',
                'view employer reports',
                'view revenue reports',
                'view financial reports',
                'view analytics',
                'view dashboard',
                'view admin dashboard',
                'view employer dashboard',
                'view job seeker dashboard',
                'view system metrics',
                'export reports',
                'schedule reports',
                'view real-time analytics',
                'export analytics data',
            ],
            
            'Moderation' => [
                'review job posts',
                'approve job post',
                'reject job post',
                'flag content',
                'suspend user',
                'warn user',
                'block user',
                'view flagged content',
                'manage moderation queue',
                'escalate content',
            ],
            
            'Support' => [
                'view support tickets',
                'create support ticket',
                'respond to tickets',
                'close ticket',
                'escalate ticket',
                'view knowledge base',
                'manage FAQs',
                'view user feedback',
            ],
            
            'User Management' => [
                'create user',
                'view user',
                'edit user',
                'delete user',
                'manage roles',
                'assign roles',
                'view user activity',
                'export user data',
                'impersonate user',
            ],
            
            'API Management' => [
                'view api credentials',
                'create api credentials',
                'edit api credentials',
                'delete api credentials',
                'regenerate api keys',
                'manage api access',
                'view api usage',
                'view api sync logs',
                'retry failed syncs',
                'export api credentials',
            ],
            
            'Notifications' => [
                'view notifications',
                'send notifications',
                'manage email templates',
                'manage SMS templates',
                'manage push notifications',
                'send bulk emails',
                'view email analytics',
            ],
            
            'System Settings' => [
                'manage settings',
                'manage countries',
                'manage payment gateways',
                'view system logs',
                'manage webhooks',
                'system maintenance',
                'clear cache',
                'manage backups',
                'data migration',
                'data cleanup',
            ],
            
            'Data Management' => [
                'import data',
                'export data',
                'bulk operations',
                'import job categories',
                'export job categories',
                'import locations',
                'export locations',
            ],
            
            'Financial Management' => [
                'view financial reports',
                'view transaction history',
                'view revenue reports',
                'manage invoices',
                'view tax reports',
                'process refunds',
                'manage payouts',
            ],
        ];

        // Create permissions with firstOrCreate to avoid duplicates
        foreach ($permissionCategories as $category => $permissions) {
            foreach ($permissions as $permission) {
                Permission::firstOrCreate(
                    [
                        'name' => $permission,
                        'guard_name' => 'web'
                    ]
                );
            }
        }

        // Super Admin only permissions
        $superAdminPermissions = [
            'super_admin only',
            'manage roles',
            'system maintenance',
            'view system logs',
            'manage backups',
            'impersonate user',
            'data migration',
        ];

        foreach ($superAdminPermissions as $permission) {
            Permission::firstOrCreate(
                [
                    'name' => $permission,
                    'guard_name' => 'web'
                ]
            );
        }

        // Now sync permissions for roles
        $this->syncRolePermissions();
        
        // Create users
        $this->createUsers();
    }

    /**
     * Sync permissions for all roles
     */
    private function syncRolePermissions(): void
    {
        // Get all permissions with specific guard
        $allPermissions = Permission::where('guard_name', 'web')->get();

        // Get roles with specific guard
        $superAdminRole = Role::where('name', 'super_admin')->where('guard_name', 'web')->first();
        $adminRole = Role::where('name', 'admin')->where('guard_name', 'web')->first();
        $employerRole = Role::where('name', 'employer')->where('guard_name', 'web')->first();
        $jobSeekerRole = Role::where('name', 'job_seeker')->where('guard_name', 'web')->first();
        $moderatorRole = Role::where('name', 'moderator')->where('guard_name', 'web')->first();
        $supportRole = Role::where('name', 'support')->where('guard_name', 'web')->first();

        // Assign ALL permissions to super_admin
        if ($superAdminRole) {
            $superAdminRole->syncPermissions($allPermissions);
        }

        // Assign permissions to admin (almost everything except super admin specific)
        if ($adminRole) {
            $adminPermissions = $allPermissions->filter(function($permission) {
                return !in_array($permission->name, [
                    'super_admin only',
                    'manage roles',
                    'system maintenance',
                    'view system logs',
                    'manage backups',
                    'impersonate user',
                    'data migration',
                ]);
            });
            $adminRole->syncPermissions($adminPermissions);
        }

        // Assign permissions to employer
        if ($employerRole) {
            $employerPermissions = $allPermissions->filter(function($permission) {
                return in_array($permission->name, [
                    // Job Management
                    'create job', 'view job', 'edit job', 'delete job', 'archive job', 'repost job', 'feature job', 'boost job', 'close job',
                    
                    // Applications
                    'view applications', 'manage applications', 'shortlist candidate', 'reject candidate', 'interview candidate', 'hire candidate',
                    'export applications', 'add application notes', 'schedule interview',
                    
                    // Company Profile
                    'create company profile', 'edit company profile', 'view company profile', 'add company logo', 'add company photos',
                    'manage company social links', 'view company reviews',
                    
                    // Casual Work
                    'post casual job', 'view casual jobs', 'manage shifts', 'view shift schedule', 'approve timesheets',
                    
                    // Subscriptions
                    'view subscription plans', 'subscribe to plan', 'upgrade subscription', 'downgrade subscription', 
                    'cancel subscription', 'view billing history', 'download invoice', 'manage payment methods',
                    
                    // Messaging
                    'send message', 'view messages', 'send bulk message',
                    
                    // Reviews
                    'rate worker', 'view ratings', 'respond to review',
                    
                    // Reports
                    'view job reports', 'view application reports', 'view hiring reports', 'export reports',
                    'view employer dashboard',
                    
                    // Job Promotions
                    'view job promotions', 'create job promotions', 'activate job promotions',
                    
                    // Locations
                    'view locations', 'manage work sites',
                ]);
            });
            $employerRole->syncPermissions($employerPermissions);
        }

        // Assign permissions to job seeker
        if ($jobSeekerRole) {
            $jobSeekerPermissions = $allPermissions->filter(function($permission) {
                return in_array($permission->name, [
                    // Jobs
                    'view job', 'apply for job', 'view applications',
                    
                    // CV Management
                    'create cv', 'edit cv', 'upload cv', 'delete cv', 'download cv',
                    'make cv public', 'make cv private', 'add work experience', 'edit work experience',
                    'add education', 'edit education', 'add skills', 'add certifications',
                    'add languages', 'add portfolio', 'add references',
                    
                    // Company
                    'view company profile', 'view company reviews',
                    
                    // Casual Work
                    'view casual jobs', 'apply for casual work', 'view shift schedule', 'clock in', 'clock out', 'report hours',
                    
                    // Messaging
                    'send message', 'view messages',
                    
                    // Reviews
                    'rate employer', 'view ratings',
                    
                    // Verification
                    'verify identity', 'verify documents', 'request documents', 'view verification status',
                    
                    // Dashboard
                    'view job seeker dashboard',
                    
                    // Notifications
                    'view notifications',
                    
                    // Support
                    'create support ticket', 'view support tickets',
                ]);
            });
            $jobSeekerRole->syncPermissions($jobSeekerPermissions);
        }

        // Assign permissions to moderator
        if ($moderatorRole) {
            $moderatorPermissions = $allPermissions->filter(function($permission) {
                return in_array($permission->name, [
                    // Moderation
                    'review job posts', 'approve job post', 'reject job post', 'flag content',
                    'suspend user', 'warn user', 'block user', 'view flagged content',
                    'manage moderation queue', 'escalate content',
                    
                    // Views
                    'view job', 'view applications', 'view company profile', 'view user',
                    'view ratings', 'report review',
                    
                    // Reports
                    'view reports', 'export reports',
                ]);
            });
            $moderatorRole->syncPermissions($moderatorPermissions);
        }

        // Assign permissions to support
        if ($supportRole) {
            $supportPermissions = $allPermissions->filter(function($permission) {
                return in_array($permission->name, [
                    // Support
                    'view support tickets', 'respond to tickets', 'close ticket', 'escalate ticket',
                    'view knowledge base', 'manage FAQs', 'view user feedback',
                    
                    // Views
                    'view user', 'view messages',
                    
                    // Reports
                    'view reports',
                ]);
            });
            $supportRole->syncPermissions($supportPermissions);
        }
    }

    /**
     * Create all users
     */
    private function createUsers(): void
    {
        // Get roles
        $superAdminRole = Role::where('name', 'super_admin')->where('guard_name', 'web')->first();
        $adminRole = Role::where('name', 'admin')->where('guard_name', 'web')->first();
        $employerRole = Role::where('name', 'employer')->where('guard_name', 'web')->first();
        $jobSeekerRole = Role::where('name', 'job_seeker')->where('guard_name', 'web')->first();
        $moderatorRole = Role::where('name', 'moderator')->where('guard_name', 'web')->first();
        $supportRole = Role::where('name', 'support')->where('guard_name', 'web')->first();

        // Create SUPER ADMIN user
        $superAdmin = User::firstOrCreate(
            ['email' => 'super@stardenaworks.com'],
            [
                'uuid' => Str::uuid(),
                'first_name' => 'Super',
                'last_name' => 'Admin',
                'phone' => '+256700000000',
                'role_id' => $superAdminRole?->id,
                'email_verified_at' => now(),
                'country_code' => 'UG',
                'is_active' => true,
                'last_login_at' => now(),
            ]
        );
        if (!$superAdmin->hasRole('super_admin')) {
            $superAdmin->assignRole('super_admin');
        }

        // Create Another SUPER ADMIN user
        $superAdmin = User::firstOrCreate(
            ['email' => 'samuelkiiraeluk@gmail.com'],
            [
                'uuid' => Str::uuid(),
                'first_name' => 'Stardena',
                'last_name' => 'Works',
                'phone' => '+256754428612',
                'role_id' => $superAdminRole?->id,
                'email_verified_at' => now(),
                'country_code' => 'UG',
                'is_active' => true,
                'last_login_at' => now(),
            ]
        );
        if (!$superAdmin->hasRole('super_admin')) {
            $superAdmin->assignRole('super_admin');
        }
        // Create SUPER ADMIN user
        $superAdmin = User::firstOrCreate(
            ['email' => 'super@stardenaworks.com'],
            [
                'uuid' => Str::uuid(),
                'first_name' => 'Super',
                'last_name' => 'Admin',
                'phone' => '+256700000000',
                'role_id' => $superAdminRole?->id,
                'email_verified_at' => now(),
                'country_code' => 'UG',
                'is_active' => true,
                'last_login_at' => now(),
            ]
        );
        if (!$superAdmin->hasRole('super_admin')) {
            $superAdmin->assignRole('super_admin');
        }

        // Create Another SUPER ADMIN user
        $superAdmin = User::firstOrCreate(
            ['email' => 'fredsseginda70@gmail.com'],
            [
                'uuid' => Str::uuid(),
                'first_name' => 'Stardena',
                'last_name' => 'Works',
                'phone' => '+256709105749',
                'role_id' => $superAdminRole?->id,
                'email_verified_at' => now(),
                'country_code' => 'UG',
                'is_active' => true,
                'last_login_at' => now(),
            ]
        );
        if (!$superAdmin->hasRole('super_admin')) {
            $superAdmin->assignRole('super_admin');
        }


        // Create admin user
        $admin = User::firstOrCreate(
            ['email' => 'admin@stardenaworks.com'],
            [
                'uuid' => Str::uuid(),
                'first_name' => 'System',
                'last_name' => 'Admin',
                'phone' => '+256700000001',
                'role_id' => $adminRole?->id,
                'email_verified_at' => now(),
                'country_code' => 'UG',
                'is_active' => true,
                'last_login_at' => now(),
            ]
        );
        if (!$admin->hasRole('admin')) {
            $admin->assignRole('admin');
        }

        // Create employers
        $employers = [
            [
                'email' => 'hr@techsolutions.co.ug',
                'first_name' => 'Sarah',
                'last_name' => 'Johnson',
                'phone' => '+256701000001',
            ],
            [
                'email' => 'jobs@constructionpro.co.ug',
                'first_name' => 'Michael',
                'last_name' => 'Mukasa',
                'phone' => '+256701000002',
            ],
            [
                'email' => 'careers@cityhospital.co.ug',
                'first_name' => 'Grace',
                'last_name' => 'Nakato',
                'phone' => '+256701000003',
            ],
            [
                'email' => 'recruitment@retailgiant.co.ug',
                'first_name' => 'David',
                'last_name' => 'Ssekitoleko',
                'phone' => '+256701000004',
            ],
            [
                'email' => 'work@agrofarms.co.ug',
                'first_name' => 'Esther',
                'last_name' => 'Achieng',
                'phone' => '+256701000005',
            ],
            [
                'email' => 'hr@hotelparadise.co.ug',
                'first_name' => 'Robert',
                'last_name' => 'Kato',
                'phone' => '+256701000006',
            ],
            [
                'email' => 'jobs@securityservices.co.ug',
                'first_name' => 'Peter',
                'last_name' => 'Lule',
                'phone' => '+256701000007',
            ],
        ];

        foreach ($employers as $employerData) {
            $employer = User::firstOrCreate(
                ['email' => $employerData['email']],
                [
                    'uuid' => Str::uuid(),
                    'first_name' => $employerData['first_name'],
                    'last_name' => $employerData['last_name'],
                    'phone' => $employerData['phone'],
                    'role_id' => $employerRole?->id,
                    'email_verified_at' => now(),
                    'country_code' => 'UG',
                    'is_active' => true,
                    'last_login_at' => now(),
                ]
            );
            
            if (!$employer->hasRole('employer')) {
                $employer->assignRole('employer');
            }
            
            // Create or update magic link token
            LoginToken::updateOrCreate(
                ['user_id' => $employer->id],
                [
                    'token' => Str::random(60),
                    'expires_at' => now()->addDays(30),
                ]
            );
        }

        // Create job seekers
        $jobSeekers = [
            [
                'email' => 'peter.wasswa@gmail.com',
                'first_name' => 'Peter',
                'last_name' => 'Wasswa',
                'phone' => '+256702000001',
            ],
            [
                'email' => 'maria.nambi@yahoo.com',
                'first_name' => 'Maria',
                'last_name' => 'Nambi',
                'phone' => '+256702000002',
            ],
            [
                'email' => 'robert.mukasa@hotmail.com',
                'first_name' => 'Robert',
                'last_name' => 'Mukasa',
                'phone' => '+256702000003',
            ],
            [
                'email' => 'sarah.kyomuhendo@gmail.com',
                'first_name' => 'Sarah',
                'last_name' => 'Kyomuhendo',
                'phone' => '+256702000004',
            ],
            [
                'email' => 'james.otim@gmail.com',
                'first_name' => 'James',
                'last_name' => 'Otim',
                'phone' => '+256702000005',
            ],
            [
                'email' => 'helen.namubiru@yahoo.com',
                'first_name' => 'Helen',
                'last_name' => 'Namubiru',
                'phone' => '+256702000006',
            ],
            [
                'email' => 'john.okello@gmail.com',
                'first_name' => 'John',
                'last_name' => 'Okello',
                'phone' => '+256702000007',
            ],
            [
                'email' => 'mary.nakato@gmail.com',
                'first_name' => 'Mary',
                'last_name' => 'Nakato',
                'phone' => '+256702000008',
            ],
            [
                'email' => 'david.kimani@kenya.com',
                'first_name' => 'David',
                'last_name' => 'Kimani',
                'phone' => '+254702000001',
                'country' => 'KE',
            ],
            [
                'email' => 'amina.mohamed@tz.com',
                'first_name' => 'Amina',
                'last_name' => 'Mohamed',
                'phone' => '+255702000001',
                'country' => 'TZ',
            ],
            [
                'email' => 'grace.akello@gmail.com',
                'first_name' => 'Grace',
                'last_name' => 'Akello',
                'phone' => '+256702000009',
            ],
            [
                'email' => 'samuel.okwir@gmail.com',
                'first_name' => 'Samuel',
                'last_name' => 'Okwir',
                'phone' => '+256702000010',
            ],
            [
                'email' => 'patricia.namutebi@gmail.com',
                'first_name' => 'Patricia',
                'last_name' => 'Namutebi',
                'phone' => '+256702000011',
            ],
            [
                'email' => 'george.williams@gmail.com',
                'first_name' => 'George',
                'last_name' => 'Williams',
                'phone' => '+256702000012',
            ],
            [
                'email' => 'jennifer.nakalembe@gmail.com',
                'first_name' => 'Jennifer',
                'last_name' => 'Nakalembe',
                'phone' => '+256702000013',
            ],
        ];

        foreach ($jobSeekers as $seekerData) {
            $seeker = User::firstOrCreate(
                ['email' => $seekerData['email']],
                [
                    'uuid' => Str::uuid(),
                    'first_name' => $seekerData['first_name'],
                    'last_name' => $seekerData['last_name'],
                    'phone' => $seekerData['phone'],
                    'role_id' => $jobSeekerRole?->id,
                    'email_verified_at' => now(),
                    'country_code' => $seekerData['country'] ?? 'UG',
                    'is_active' => true,
                    'last_login_at' => now(),
                ]
            );
            
            if (!$seeker->hasRole('job_seeker')) {
                $seeker->assignRole('job_seeker');
            }
            
            // Create or update magic link token
            LoginToken::updateOrCreate(
                ['user_id' => $seeker->id],
                [
                    'token' => Str::random(60),
                    'expires_at' => now()->addDays(30),
                ]
            );
        }

        // Create moderator
        $moderator = User::firstOrCreate(
            ['email' => 'moderator@stardena.com'],
            [
                'uuid' => Str::uuid(),
                'first_name' => 'Content',
                'last_name' => 'Moderator',
                'phone' => '+256703000001',
                'role_id' => $moderatorRole?->id,
                'email_verified_at' => now(),
                'country_code' => 'UG',
                'is_active' => true,
                'last_login_at' => now(),
            ]
        );
        if (!$moderator->hasRole('moderator')) {
            $moderator->assignRole('moderator');
        }

        // Create support agent
        $support = User::firstOrCreate(
            ['email' => 'support@stardena.com'],
            [
                'uuid' => Str::uuid(),
                'first_name' => 'Customer',
                'last_name' => 'Support',
                'phone' => '+256703000002',
                'role_id' => $supportRole?->id,
                'email_verified_at' => now(),
                'country_code' => 'UG',
                'is_active' => true,
                'last_login_at' => now(),
            ]
        );
        if (!$support->hasRole('support')) {
            $support->assignRole('support');
        }

        // Create pending users
        for ($i = 1; $i <= 5; $i++) {
            $user = User::firstOrCreate(
                ['email' => "pending{$i}@example.com"],
                [
                    'uuid' => Str::uuid(),
                    'first_name' => 'Pending',
                    'last_name' => "User {$i}",
                    'phone' => "+2567040000{$i}",
                    'role_id' => $jobSeekerRole?->id,
                    'email_verified_at' => null,
                    'country_code' => 'UG',
                    'is_active' => true,
                ]
            );
            
            if (!$user->hasRole('job_seeker')) {
                $user->assignRole('job_seeker');
            }
            
            LoginToken::updateOrCreate(
                ['user_id' => $user->id],
                [
                    'token' => Str::random(60),
                    'expires_at' => now()->addDays(7),
                ]
            );
        }

        // Create inactive users
        for ($i = 1; $i <= 3; $i++) {
            $user = User::firstOrCreate(
                ['email' => "inactive{$i}@example.com"],
                [
                    'uuid' => Str::uuid(),
                    'first_name' => 'Inactive',
                    'last_name' => "User {$i}",
                    'phone' => "+2567050000{$i}",
                    'role_id' => $jobSeekerRole?->id,
                    'email_verified_at' => now(),
                    'country_code' => 'UG',
                    'is_active' => false,
                ]
            );
            
            if (!$user->hasRole('job_seeker')) {
                $user->assignRole('job_seeker');
            }
        }

        $this->command->info('====================================');
        $this->command->info('JOB PLATFORM SEEDED SUCCESSFULLY!');
        $this->command->info('====================================');
        $this->command->info('Super Admin: super@stardena.com');
        $this->command->info('Admin: admin@stardena.com');
        $this->command->info('Employers: ' . count($employers) . ' created/updated');
        $this->command->info('Job Seekers: ' . count($jobSeekers) . ' created/updated');
        $this->command->info('Moderator: moderator@stardena.com');
        $this->command->info('Support: support@stardena.com');
        $this->command->info('Pending Users: 5 created/updated');
        $this->command->info('Inactive Users: 3 created/updated');
        $this->command->info('====================================');
    }
}