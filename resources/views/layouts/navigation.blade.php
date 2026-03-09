
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
                    <a class="sidebar-link" href="" id="get-url">
                        <iconify-icon icon="solar:box-minimalistic-linear" class="aside-icon"></iconify-icon>
                        <span class="hide-menu">{{__('Analytical')}}</span>
                    </a>
                </li>

                
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
                            <a href="../main/frontend-landingpage.html" class="sidebar-link sublink">
                                <div class="round-16 d-flex align-items-center justify-content-center">
                                <iconify-icon icon="solar:stop-circle-line-duotone"></iconify-icon>
                                </div>
                                <span class="hide-menu">{{__('Jobs')}}</span>
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
                            <a href="../main/frontend-blogpage.html" class="sidebar-link sublink">
                                <div class="round-16 d-flex align-items-center justify-content-center">
                                <iconify-icon icon="solar:stop-circle-line-duotone"></iconify-icon>
                                </div>
                                <span class="hide-menu">{{__('AI & Simple Posting')}}</span>
                            </a>
                        </li>
                        <li class="sidebar-item">
                            <a href="../main/frontend-blogdetailpage.html" class="sidebar-link sublink">
                                <div class="round-16 d-flex align-items-center justify-content-center">
                                <iconify-icon icon="solar:stop-circle-line-duotone"></iconify-icon>
                                </div>
                                <span class="hide-menu">{{__('Job Settings')}}</span>
                            </a>
                        </li>
                        <li class="sidebar-item">
                            <a href="../main/frontend-contactpage.html" class="sidebar-link sublink">
                                <div class="round-16 d-flex align-items-center justify-content-center">
                                <iconify-icon icon="solar:stop-circle-line-duotone"></iconify-icon>
                                </div>
                                <span class="hide-menu">{{__('Posting Locations')}}</span>
                            </a>
                        </li>
                        <li class="sidebar-item">
                            <a href="../main/frontend-pricingpage.html" class="sidebar-link sublink">
                                <div class="round-16 d-flex align-items-center justify-content-center">
                                <iconify-icon icon="solar:stop-circle-line-duotone"></iconify-icon>
                                </div>
                                <span class="hide-menu">{{__('Pricing')}}</span>
                            </a>
                        </li>
                    </ul>
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
                            <a href="../main/frontend-landingpage.html" class="sidebar-link sublink">
                                <div class="round-16 d-flex align-items-center justify-content-center">
                                <iconify-icon icon="solar:stop-circle-line-duotone"></iconify-icon>
                                </div>
                                <span class="hide-menu">{{__('Jobs')}}</span>
                            </a>
                        </li>
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