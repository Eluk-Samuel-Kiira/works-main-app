
<!DOCTYPE html>
<html lang="en" dir="ltr" data-bs-theme="light" data-color-theme="Blue_Theme" data-layout="vertical">

<head>
    <!-- Required meta tags -->
    <meta charset="UTF-8" />
    <meta http-equiv="X-UA-Compatible" content="IE=edge" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />

    <!-- Favicon icon-->
    <link rel="shortcut icon" type="image/png" href="{{ getFavicon() }}" />

    <!-- Core Css -->
    <link rel="stylesheet" href="{{ asset('assets/css/styles.css') }}" />

    <title>@yield('title')</title>
</head>

<body>
<!-- <div class="toast toast-onload align-items-center text-bg-secondary border-0" role="alert" aria-live="assertive" aria-atomic="true">
    <div class="toast-body hstack align-items-start gap-6">
    <i class="ti ti-alert-circle fs-6"></i>
    <div>
        <h5 class="text-white fs-3 mb-1">Welcome to Stardena Works</h5>
        <h6 class="text-white fs-2 mb-0">Easy to costomize the Template!!!</h6>
    </div>
    <button type="button" class="btn-close btn-close-white fs-2 m-0 ms-auto shadow-none" data-bs-dismiss="toast" aria-label="Close"></button>
    </div>
</div> -->

    <!-- Preloader -->
    <div class="preloader">
        <img src="{{ getFavicon() }}" alt="loader" class="lds-ripple img-fluid" />
    </div>
    <div id="main-wrapper">

        <!-- Sidebar Start -->
        @include('layouts.navigation')
        <!--  Sidebar End -->

        <div class="page-wrapper">

        <!--  Header Start -->
        @include('layouts.header')
        <!--  Header End -->

        <!-- Dynamic Content Section -->
        @yield('app-content')

        </div>

        <!--  Search Bar -->
        <div class="modal fade" id="exampleModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-scrollable modal-lg">
            <div class="modal-content rounded-1">
            <div class="modal-header border-bottom">
                <input type="search" class="form-control fs-2" placeholder="Search here" id="search" />
                <a href="javascript:void(0)" data-bs-dismiss="modal" class="lh-1">
                <i class="ti ti-x fs-5 ms-3"></i>
                </a>
            </div>
            <div class="modal-body message-body" data-simplebar="">
                <h5 class="mb-0 fs-5 p-1">Quick Page Links</h5>
                <ul class="list mb-0 py-2">
                <li class="p-1 mb-1 px-2 rounded bg-hover-light-black">
                    <a href="javascript:void(0)">
                    <span class="h6 mb-1">Modern</span>
                    <span class="fs-2 text-muted d-block">/dashboards/dashboard1</span>
                    </a>
                </li>
                <li class="p-1 mb-1 px-2 rounded bg-hover-light-black">
                    <a href="javascript:void(0)">
                    <span class="h6 mb-1">Dashboard</span>
                    <span class="fs-2 text-muted d-block">/dashboards/dashboard2</span>
                    </a>
                </li>
                <li class="p-1 mb-1 px-2 rounded bg-hover-light-black">
                    <a href="javascript:void(0)">
                    <span class="h6 mb-1">Contacts</span>
                    <span class="fs-2 text-muted d-block">/apps/contacts</span>
                    </a>
                </li>
                <li class="p-1 mb-1 px-2 rounded bg-hover-light-black">
                    <a href="javascript:void(0)">
                    <span class="h6 mb-1">Posts</span>
                    <span class="fs-2 text-muted d-block">/apps/blog/posts</span>
                    </a>
                </li>
                <li class="p-1 mb-1 px-2 rounded bg-hover-light-black">
                    <a href="javascript:void(0)">
                    <span class="h6 mb-1">Detail</span>
                    <span class="fs-2 text-muted d-block">/apps/blog/detail/streaming-video-way-before-it-was-cool-go-dark-tomorrow</span>
                    </a>
                </li>
                <li class="p-1 mb-1 px-2 rounded bg-hover-light-black">
                    <a href="javascript:void(0)">
                    <span class="h6 mb-1">Shop</span>
                    <span class="fs-2 text-muted d-block">/apps/ecommerce/shop</span>
                    </a>
                </li>
                <li class="p-1 mb-1 px-2 rounded bg-hover-light-black">
                    <a href="javascript:void(0)">
                    <span class="h6 mb-1">Modern</span>
                    <span class="fs-2 text-muted d-block">/dashboards/dashboard1</span>
                    </a>
                </li>
                <li class="p-1 mb-1 px-2 rounded bg-hover-light-black">
                    <a href="javascript:void(0)">
                    <span class="h6 mb-1">Dashboard</span>
                    <span class="fs-2 text-muted d-block">/dashboards/dashboard2</span>
                    </a>
                </li>
                <li class="p-1 mb-1 px-2 rounded bg-hover-light-black">
                    <a href="javascript:void(0)">
                    <span class="h6 mb-1">Contacts</span>
                    <span class="fs-2 text-muted d-block">/apps/contacts</span>
                    </a>
                </li>
                <li class="p-1 mb-1 px-2 rounded bg-hover-light-black">
                    <a href="javascript:void(0)">
                    <span class="h6 mb-1">Posts</span>
                    <span class="fs-2 text-muted d-block">/apps/blog/posts</span>
                    </a>
                </li>
                <li class="p-1 mb-1 px-2 rounded bg-hover-light-black">
                    <a href="javascript:void(0)">
                    <span class="h6 mb-1">Detail</span>
                    <span class="fs-2 text-muted d-block">/apps/blog/detail/streaming-video-way-before-it-was-cool-go-dark-tomorrow</span>
                    </a>
                </li>
                <li class="p-1 mb-1 px-2 rounded bg-hover-light-black">
                    <a href="javascript:void(0)">
                    <span class="h6 mb-1">Shop</span>
                    <span class="fs-2 text-muted d-block">/apps/ecommerce/shop</span>
                    </a>
                </li>
                </ul>
            </div>
            </div>
        </div>
        </div>

    </div>
    <div class="dark-transparent sidebartoggler"></div>

<!-- Import Js Files -->
<script src="{{ asset('assets/js/breadcrumb/breadcrumbChart.js') }}"></script>
<script src="{{ asset('assets/libs/apexcharts/dist/apexcharts.min.js') }}"></script>
<script src="{{ asset('assets/libs/bootstrap/dist/js/bootstrap.bundle.min.js') }}"></script>
<script src="{{ asset('assets/libs/simplebar/dist/simplebar.min.js') }}"></script>
<script src="{{ asset('assets/js/theme/app.init.js') }}"></script>
<script src="{{ asset('assets/js/theme/theme.js') }}"></script>
<script src="{{ asset('assets/js/theme/app.min.js') }}"></script>
<script src="{{ asset('assets/js/theme/sidebarmenu.js') }}"></script>
<script src="{{ asset('assets/js/theme/feather.min.js') }}"></script>

<!-- solar icons -->
<script src="{{ asset('assets/libs/iconify-icon/dist/iconify-icon.min.js') }}"></script>

<!-- highlight.js') }} (code view) -->
<script src="{{ asset('assets/js/highlights/highlight.min.js') }}"></script>
<script>
hljs.initHighlightingOnLoad();


document.querySelectorAll("pre.code-view > code").forEach((codeBlock) => {
    codeBlock.textContent = codeBlock.innerHTML;
});
</script>
<script src="{{ asset('assets/js/dashboards/dashboard1.js') }}"></script>
</body>

</html>