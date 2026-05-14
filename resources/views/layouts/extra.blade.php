


{{-- Analytics Sub-menu (admin/moderator) --}}
                @if(auth()->user()?->isAdmin() || auth()->user()?->isModerator())
                <li class="nav-small-cap">
                    <iconify-icon icon="solar:box-minimalistic-linear" class="nav-small-cap-icon fs-4"></iconify-icon>
                    <span class="hide-menu">{{__('Analytical')}}</span>
                </li>
                <li class="sidebar-item">
                    <a class="sidebar-link has-arrow" href="javascript:void(0)" aria-expanded="false">
                        <iconify-icon icon="solar:home-angle-linear" class="aside-icon"></iconify-icon>
                        <span class="hide-menu">{{__('Analytics')}}</span>
                    </a>
                    <ul aria-expanded="false" class="collapse first-level">
                        <li class="sidebar-item">
                            <a href="{{ route('analytics.jobs') }}" class="sidebar-link sublink">
                                <div class="round-16 d-flex align-items-center justify-content-center">
                                <iconify-icon icon="solar:stop-circle-line-duotone"></iconify-icon>
                                </div>
                                <span class="hide-menu">{{__('Jobs')}}</span>
                            </a>
                        </li>
                        <li class="sidebar-item">
                            <a href="{{ route('analytics.users') }}" class="sidebar-link sublink">
                                <div class="round-16 d-flex align-items-center justify-content-center">
                                <iconify-icon icon="solar:stop-circle-line-duotone"></iconify-icon>
                                </div>
                                <span class="hide-menu">{{__('Users')}}</span>
                            </a>
                        </li>
                        <li class="sidebar-item">
                            <a href="{{ route('analytics.companies') }}" class="sidebar-link sublink">
                                <div class="round-16 d-flex align-items-center justify-content-center">
                                <iconify-icon icon="solar:stop-circle-line-duotone"></iconify-icon>
                                </div>
                                <span class="hide-menu">{{__('Companies')}}</span>
                            </a>
                        </li>
                        <li class="sidebar-item">
                            <a href="{{ route('analytics.seo') }}" class="sidebar-link sublink">
                                <div class="round-16 d-flex align-items-center justify-content-center">
                                <iconify-icon icon="solar:stop-circle-line-duotone"></iconify-icon>
                                </div>
                                <span class="hide-menu">{{__('SEO')}}</span>
                            </a>
                        </li>
                        <li class="sidebar-item">
                            <a href="{{ route('job-category.index') }}" class="sidebar-link sublink">
                                <div class="round-16 d-flex align-items-center justify-content-center">
                                <iconify-icon icon="solar:stop-circle-line-duotone"></iconify-icon>
                                </div>
                                <span class="hide-menu">{{__('Job Categories')}}</span>
                            </a>
                        </li>
                        <li class="sidebar-item">
                            <a href="{{ route('analytics.notifications') }}" class="sidebar-link sublink">
                                <div class="round-16 d-flex align-items-center justify-content-center">
                                <iconify-icon icon="solar:stop-circle-line-duotone"></iconify-icon>
                                </div>
                                <span class="hide-menu">{{__('Notifications')}}</span>
                            </a>
                        </li>
                        @if(auth()->user()?->isAdmin())
                        <li class="sidebar-item">
                            <a href="{{ route('analytics.revenue') }}" class="sidebar-link sublink">
                                <div class="round-16 d-flex align-items-center justify-content-center">
                                <iconify-icon icon="solar:stop-circle-line-duotone"></iconify-icon>
                                </div>
                                <span class="hide-menu">{{__('Revenue')}}</span>
                            </a>
                        </li>
                        <li class="sidebar-item">
                            <a href="{{ route('analytics.api') }}" class="sidebar-link sublink">
                                <div class="round-16 d-flex align-items-center justify-content-center">
                                <iconify-icon icon="solar:stop-circle-line-duotone"></iconify-icon>
                                </div>
                                <span class="hide-menu">{{__('API Usage')}}</span>
                            </a>
                        </li>
                        @endif
                    </ul>
                </li>
                @endif

                {{-- Employer analytics --}}
                @if(auth()->user()?->isEmployer())
                <li class="sidebar-item">
                    <a class="sidebar-link" href="{{ route('analytics.employer.dashboard') }}">
                        <iconify-icon icon="solar:chart-2-linear" class="aside-icon"></iconify-icon>
                        <span class="hide-menu">{{__('My Analytics')}}</span>
                    </a>
                </li>
                @endif

                
<aside class="left-sidebar with-vertical">
    <div><!-- ---------------------------------- -->
        <!-- Start Vertical Layout Sidebar -->
        <!-- ---------------------------------- -->
        <!-- Sidebar scroll-->
        <nav class="sidebar-nav scroll-sidebar" data-simplebar>

            <ul id="sidebarnav">

                <li class="nav-small-cap">
                    <iconify-icon icon="solar:menu-dots-bold" class="nav-small-cap-icon fs-4"></iconify-icon>
                    <span class="hide-menu">{{__('Dashboards')}}</span>
                </li>
                <li class="sidebar-item">
                    <a class="sidebar-link" href="{{ route('dashboard') }}" >
                        <iconify-icon icon="solar:screencast-2-linear" class="aside-icon"></iconify-icon>
                        <span class="hide-menu">{{__('General')}}</span>
                    </a>
                </li>
                <li class="sidebar-item">
                    <a class="sidebar-link {{ request()->routeIs('analytics.*') ? 'active' : '' }}" href="{{ route('analytics.dashboard') }}">
                        <iconify-icon icon="solar:chart-2-linear" class="aside-icon"></iconify-icon>
                        <span class="hide-menu">{{__('Analytics')}}</span>
                    </a>
                </li>

                {{-- Analytics Sub-menu (admin/moderator) --}}
                @if(auth()->user()?->isAdmin() || auth()->user()?->isModerator())
                <li class="sidebar-item">
                    <a class="sidebar-link has-arrow {{ request()->routeIs('analytics.*') ? '' : '' }}" href="javascript:void(0)" aria-expanded="{{ request()->routeIs('analytics.*') ? 'true' : 'false' }}">
                        <iconify-icon icon="solar:graph-up-linear" class="aside-icon"></iconify-icon>
                        <span class="hide-menu">{{__('Analytics')}}</span>
                    </a>
                    <ul aria-expanded="{{ request()->routeIs('analytics.*') ? 'true' : 'false' }}" class="collapse first-level {{ request()->routeIs('analytics.*') ? 'show' : '' }}">
                        <li class="sidebar-item">
                            <a href="{{ route('analytics.dashboard') }}" class="sidebar-link sublink {{ request()->routeIs('analytics.dashboard') ? 'active' : '' }}">
                                <div class="round-16 d-flex align-items-center justify-content-center">
                                    <iconify-icon icon="solar:stop-circle-line-duotone"></iconify-icon>
                                </div>
                                <span class="hide-menu">{{__('Overview')}}</span>
                            </a>
                        </li>
                        <li class="sidebar-item">
                            <a href="{{ route('analytics.jobs') }}" class="sidebar-link sublink {{ request()->routeIs('analytics.jobs') ? 'active' : '' }}">
                                <div class="round-16 d-flex align-items-center justify-content-center">
                                    <iconify-icon icon="solar:stop-circle-line-duotone"></iconify-icon>
                                </div>
                                <span class="hide-menu">{{__('Jobs')}}</span>
                            </a>
                        </li>
                        <li class="sidebar-item">
                            <a href="{{ route('analytics.users') }}" class="sidebar-link sublink {{ request()->routeIs('analytics.users') ? 'active' : '' }}">
                                <div class="round-16 d-flex align-items-center justify-content-center">
                                    <iconify-icon icon="solar:stop-circle-line-duotone"></iconify-icon>
                                </div>
                                <span class="hide-menu">{{__('Users')}}</span>
                            </a>
                        </li>
                        <li class="sidebar-item">
                            <a href="{{ route('analytics.companies') }}" class="sidebar-link sublink {{ request()->routeIs('analytics.companies') ? 'active' : '' }}">
                                <div class="round-16 d-flex align-items-center justify-content-center">
                                    <iconify-icon icon="solar:stop-circle-line-duotone"></iconify-icon>
                                </div>
                                <span class="hide-menu">{{__('Companies')}}</span>
                            </a>
                        </li>
                        <li class="sidebar-item">
                            <a href="{{ route('analytics.seo') }}" class="sidebar-link sublink {{ request()->routeIs('analytics.seo') ? 'active' : '' }}">
                                <div class="round-16 d-flex align-items-center justify-content-center">
                                    <iconify-icon icon="solar:stop-circle-line-duotone"></iconify-icon>
                                </div>
                                <span class="hide-menu">{{__('SEO')}}</span>
                            </a>
                        </li>
                        <li class="sidebar-item">
                            <a href="{{ route('analytics.notifications') }}" class="sidebar-link sublink {{ request()->routeIs('analytics.notifications') ? 'active' : '' }}">
                                <div class="round-16 d-flex align-items-center justify-content-center">
                                    <iconify-icon icon="solar:stop-circle-line-duotone"></iconify-icon>
                                </div>
                                <span class="hide-menu">{{__('Notifications')}}</span>
                            </a>
                        </li>
                        @if(auth()->user()?->isAdmin())
                        <li class="sidebar-item">
                            <a href="{{ route('analytics.revenue') }}" class="sidebar-link sublink {{ request()->routeIs('analytics.revenue') ? 'active' : '' }}">
                                <div class="round-16 d-flex align-items-center justify-content-center">
                                    <iconify-icon icon="solar:stop-circle-line-duotone"></iconify-icon>
                                </div>
                                <span class="hide-menu">{{__('Revenue')}}</span>
                            </a>
                        </li>
                        <li class="sidebar-item">
                            <a href="{{ route('analytics.api') }}" class="sidebar-link sublink {{ request()->routeIs('analytics.api') ? 'active' : '' }}">
                                <div class="round-16 d-flex align-items-center justify-content-center">
                                    <iconify-icon icon="solar:stop-circle-line-duotone"></iconify-icon>
                                </div>
                                <span class="hide-menu">{{__('API Usage')}}</span>
                            </a>
                        </li>
                        @endif
                    </ul>
                </li>
                @endif

                {{-- Employer analytics --}}
                @if(auth()->user()?->isEmployer())
                <li class="sidebar-item">
                    <a class="sidebar-link {{ request()->routeIs('analytics.employer.*') ? 'active' : '' }}" href="{{ route('analytics.employer.dashboard') }}">
                        <iconify-icon icon="solar:chart-2-linear" class="aside-icon"></iconify-icon>
                        <span class="hide-menu">{{__('My Analytics')}}</span>
                    </a>
                </li>
                @endif



                <li class="nav-small-cap">
                    <iconify-icon icon="solar:menu-dots-bold" class="nav-small-cap-icon fs-4"></iconify-icon>
                    <span class="hide-menu">{{__('Job Posting')}}</span>
                </li>
                <li class="sidebar-item">
                    <a class="sidebar-link has-arrow" href="javascript:void(0)" aria-expanded="false">
                        <iconify-icon icon="solar:home-angle-linear" class="aside-icon"></iconify-icon>
                        <span class="hide-menu">{{__('Job Posts')}}</span>
                    </a>
                    <ul aria-expanded="false" class="collapse first-level">
                        <li class="sidebar-item">
                            <a href="{{ route('job-post.index') }}" class="sidebar-link sublink">
                                <div class="round-16 d-flex align-items-center justify-content-center">
                                <iconify-icon icon="solar:stop-circle-line-duotone"></iconify-icon>
                                </div>
                                <span class="hide-menu">{{__('Jobs')}}</span>
                            </a>
                        </li>
                        <li class="sidebar-item">
                            <a href="{{ route('ai-posting') }}" class="sidebar-link sublink">
                                <div class="round-16 d-flex align-items-center justify-content-center">
                                <iconify-icon icon="solar:stop-circle-line-duotone"></iconify-icon>
                                </div>
                                <span class="hide-menu">{{__('AI-Job Posting')}}</span>
                            </a>
                        </li>
                        <li class="sidebar-item">
                            <a href="{{ route('company.index') }}" class="sidebar-link sublink">
                                <div class="round-16 d-flex align-items-center justify-content-center">
                                <iconify-icon icon="solar:stop-circle-line-duotone"></iconify-icon>
                                </div>
                                <span class="hide-menu">{{__('Companies')}}</span>
                            </a>
                        </li>
                        <li class="sidebar-item">
                            <a href="{{ route('industry.index') }}" class="sidebar-link sublink">
                                <div class="round-16 d-flex align-items-center justify-content-center">
                                <iconify-icon icon="solar:stop-circle-line-duotone"></iconify-icon>
                                </div>
                                <span class="hide-menu">{{__('Industries')}}</span>
                            </a>
                        </li>
                        <li class="sidebar-item">
                            <a href="{{ route('job-category.index') }}" class="sidebar-link sublink">
                                <div class="round-16 d-flex align-items-center justify-content-center">
                                <iconify-icon icon="solar:stop-circle-line-duotone"></iconify-icon>
                                </div>
                                <span class="hide-menu">{{__('Job Categories')}}</span>
                            </a>
                        </li>
                        <li class="sidebar-item">
                            <a href="{{ route('job-type.index') }}" class="sidebar-link sublink">
                                <div class="round-16 d-flex align-items-center justify-content-center">
                                <iconify-icon icon="solar:stop-circle-line-duotone"></iconify-icon>
                                </div>
                                <span class="hide-menu">{{__('Job Types')}}</span>
                            </a>
                        </li>
                        <li class="sidebar-item">
                            <a href="{{ route('job-location.index') }}" class="sidebar-link sublink">
                                <div class="round-16 d-flex align-items-center justify-content-center">
                                <iconify-icon icon="solar:stop-circle-line-duotone"></iconify-icon>
                                </div>
                                <span class="hide-menu">{{__('Locations')}}</span>
                            </a>
                        </li>
                    </ul>
                </li>
                
                <li class="nav-small-cap">
                    <iconify-icon icon="solar:menu-dots-bold" class="nav-small-cap-icon fs-4"></iconify-icon>
                    <span class="hide-menu">{{__('Blog Posting')}}</span>
                </li>
                <li class="sidebar-item">
                    <a class="sidebar-link has-arrow" href="javascript:void(0)" aria-expanded="false">
                        <iconify-icon icon="solar:document-text-linear" class="aside-icon"></iconify-icon>
                        <span class="hide-menu">{{__('Blog Posts')}}</span>
                    </a>
                    <ul aria-expanded="false" class="collapse first-level">
                        <li class="sidebar-item">
                            <a href="{{ route('blogs.index') }}" class="sidebar-link sublink">
                                <div class="round-16 d-flex align-items-center justify-content-center">
                                <iconify-icon icon="solar:document-text-linear"></iconify-icon>
                                </div>
                                <span class="hide-menu">{{__('Blogs')}}</span>
                            </a>
                        </li>
                        <li class="sidebar-item">
                            <a href="{{ route('blogs.create') }}" class="sidebar-link sublink">
                                <div class="round-16 d-flex align-items-center justify-content-center">
                                <iconify-icon icon="solar:pen-new-square-linear"></iconify-icon>
                                </div>
                                <span class="hide-menu">{{__('New Blog')}}</span>
                            </a>
                        </li>
                    </ul>
                </li>
                


                <li class="nav-small-cap">
                    <iconify-icon icon="solar:menu-dots-bold" class="nav-small-cap-icon fs-4"></iconify-icon>
                    <span class="hide-menu">{{__('Lookups')}}</span>
                </li>
                <li class="sidebar-item">
                    <a class="sidebar-link has-arrow" href="javascript:void(0)" aria-expanded="false">
                        <iconify-icon icon="solar:settings-linear" class="aside-icon"></iconify-icon>
                        <span class="hide-menu">{{__('Job Settings')}}</span>
                    </a>
                    <ul aria-expanded="false" class="collapse first-level">
                        <li class="sidebar-item">
                            <a href="{{ route('experience-level.index') }}" class="sidebar-link sublink">
                                <div class="round-16 d-flex align-items-center justify-content-center">
                                <iconify-icon icon="solar:stop-circle-line-duotone"></iconify-icon>
                                </div>
                                <span class="hide-menu">{{__('Experience Levels')}}</span>
                            </a>
                        </li>
                        <li class="sidebar-item">
                            <a href="{{ route('education-level.index') }}" class="sidebar-link sublink">
                                <div class="round-16 d-flex align-items-center justify-content-center">
                                <iconify-icon icon="solar:stop-circle-line-duotone"></iconify-icon>
                                </div>
                                <span class="hide-menu">{{__('Education Levels')}}</span>
                            </a>
                        </li>
                        <li class="sidebar-item">
                            <a href="{{ route('salary-range.index') }}" class="sidebar-link sublink">
                                <div class="round-16 d-flex align-items-center justify-content-center">
                                <iconify-icon icon="solar:stop-circle-line-duotone"></iconify-icon>
                                </div>
                                <span class="hide-menu">{{__('Salary Ranges')}}</span>
                            </a>
                        </li>
                    </ul>
                </li>
                
                <li class="sidebar-item">
                    <a class="sidebar-link" href="{{ route('social-media') }}">
                        <iconify-icon icon="solar:share-linear" class="aside-icon"></iconify-icon>
                        <span class="hide-menu">{{__('SM Platforms')}}</span>
                    </a>
                </li>

                <li class="sidebar-item">
                    <a class="sidebar-link" href="{{ route('whatsapp-docs') }}">
                        <i class="ti ti-brand-whatsapp"></i>
                        <span class="hide-menu">{{__('WhatsApp Docs')}}</span>
                    </a>
                </li>

                <li class="nav-small-cap">
                    <iconify-icon icon="solar:menu-dots-bold" class="nav-small-cap-icon fs-4"></iconify-icon>
                    <span class="hide-menu">{{__('User Management')}}</span>
                </li>
                <li class="sidebar-item">
                    <a class="sidebar-link" href="{{ route('user.index') }}">
                        <iconify-icon icon="solar:users-group-rounded-linear" class="aside-icon"></iconify-icon>
                        <span class="hide-menu">{{__('Users')}}</span>
                    </a>
                </li>

                <li class="nav-small-cap">
                    <iconify-icon icon="solar:menu-dots-bold" class="nav-small-cap-icon fs-4"></iconify-icon>
                    <span class="hide-menu">{{__('Config Settings')}}</span>
                </li>
                <li class="sidebar-item">
                    <a class="sidebar-link has-arrow" href="javascript:void(0)" aria-expanded="false">
                        <iconify-icon icon="solar:home-angle-linear" class="aside-icon"></iconify-icon>
                        <span class="hide-menu">{{__('App Settings')}}</span>
                    </a>
                    <ul aria-expanded="false" class="collapse first-level">
                        <li class="sidebar-item">
                            <a href="{{ route('artisan.index') }}" class="sidebar-link sublink">
                                <div class="round-16 d-flex align-items-center justify-content-center">
                                <iconify-icon icon="solar:stop-circle-line-duotone"></iconify-icon>
                                </div>
                                <span class="hide-menu">{{__('Artisan Commands')}}</span>
                            </a>
                        </li>
                    </ul>
                </li>
            </ul>
        </nav>

        <!-- End Sidebar scroll-->
    </div>
</aside>