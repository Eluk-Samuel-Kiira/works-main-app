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

  <title>@yield('title', __('Stardena Works'))</title>
</head>

<body>
    <!-- Preloader -->
    <div class="preloader">
        <img src="{{ getFavicon() }}" alt="loader" class="lds-ripple img-fluid" />
    </div>
    
    <div id="main-wrapper" class="auth-customizer-none">
        <div class="position-relative overflow-hidden radial-gradient min-vh-100 w-100 d-flex align-items-center justify-content-center">
            <div class="d-flex align-items-center justify-content-center w-100">
                <div class="row justify-content-center w-100">
                    <div class="col-md-8 col-lg-6 col-xxl-3 auth-card">
                        <div class="card mb-0">
                            <div class="card-body">
                                <a href="/" class="text-nowrap logo-img d-flex align-items-center justify-content-center gap-2 mb-4 w-100">
                                    <b class="logo-icon">
                                        <img src="{{ getWhiteLogo() }}" alt="homepage" class="dark-logo" style="width: 150px; height: auto;" />
                                    </b>
                                </a>
                                
                                <!-- Dynamic Content Section -->
                                @yield('auth-content')
                                
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Toast Container -->
    <div class="toast-container position-fixed top-0 end-0 p-3" style="z-index: 9999; max-width: 400px;">
        @if(session('success'))
            <div class="toast toast-onload align-items-center text-bg-success border-0 w-100" role="alert" aria-live="assertive" aria-atomic="true" data-bs-autohide="true" data-bs-delay="5000">
                <div class="toast-body hstack align-items-start gap-6">
                    <!-- Tabler Icon -->
                    <i class="ti ti-check-circle fs-6"></i>
                    <!-- Fallback emoji (will show if icon fails) -->
                    <span class="d-none">✅</span>
                    <div style="flex: 1; min-width: 0;">
                        <h5 class="text-white fs-3 mb-1">Success</h5>
                        <h6 class="text-white fs-2 mb-0" style="display: -webkit-box; -webkit-line-clamp: 2; -webkit-box-orient: vertical; overflow: hidden;">{{ session('success') }}</h6>
                    </div>
                    <button type="button" class="btn-close btn-close-white fs-2 m-0 ms-auto shadow-none flex-shrink-0" data-bs-dismiss="toast" aria-label="Close"></button>
                </div>
            </div>
        @endif

        @if(session('error'))
            <div class="toast toast-onload align-items-center text-bg-danger border-0 w-100" role="alert" aria-live="assertive" aria-atomic="true" data-bs-autohide="true" data-bs-delay="5000">
                <div class="toast-body hstack align-items-start gap-6">
                    <i class="ti ti-alert-circle fs-6"></i>
                    <span class="d-none">❌</span>
                    <div style="flex: 1; min-width: 0;">
                        <h5 class="text-white fs-3 mb-1">Error</h5>
                        <h6 class="text-white fs-2 mb-0" style="display: -webkit-box; -webkit-line-clamp: 2; -webkit-box-orient: vertical; overflow: hidden;">{{ session('error') }}</h6>
                    </div>
                    <button type="button" class="btn-close btn-close-white fs-2 m-0 ms-auto shadow-none flex-shrink-0" data-bs-dismiss="toast" aria-label="Close"></button>
                </div>
            </div>
        @endif

        @if(session('warning'))
            <div class="toast toast-onload align-items-center text-bg-warning border-0 w-100" role="alert" aria-live="assertive" aria-atomic="true" data-bs-autohide="true" data-bs-delay="5000">
                <div class="toast-body hstack align-items-start gap-6">
                    <i class="ti ti-alert-triangle fs-6"></i>
                    <span class="d-none">⚠️</span>
                    <div style="flex: 1; min-width: 0;">
                        <h5 class="text-white fs-3 mb-1">Warning</h5>
                        <h6 class="text-white fs-2 mb-0" style="display: -webkit-box; -webkit-line-clamp: 2; -webkit-box-orient: vertical; overflow: hidden;">{{ session('warning') }}</h6>
                    </div>
                    <button type="button" class="btn-close btn-close-white fs-2 m-0 ms-auto shadow-none flex-shrink-0" data-bs-dismiss="toast" aria-label="Close"></button>
                </div>
            </div>
        @endif

        @if(session('info'))
            <div class="toast toast-onload align-items-center text-bg-info border-0 w-100" role="alert" aria-live="assertive" aria-atomic="true" data-bs-autohide="true" data-bs-delay="5000">
                <div class="toast-body hstack align-items-start gap-6">
                    <i class="ti ti-info-circle fs-6"></i>
                    <span class="d-none">ℹ️</span>
                    <div style="flex: 1; min-width: 0;">
                        <h5 class="text-white fs-3 mb-1">Information</h5>
                        <h6 class="text-white fs-2 mb-0" style="display: -webkit-box; -webkit-line-clamp: 2; -webkit-box-orient: vertical; overflow: hidden;">{{ session('info') }}</h6>
                    </div>
                    <button type="button" class="btn-close btn-close-white fs-2 m-0 ms-auto shadow-none flex-shrink-0" data-bs-dismiss="toast" aria-label="Close"></button>
                </div>
            </div>
        @endif
    </div>

    <!-- Toast Initialization Script -->
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Initialize Tabler Icons (if they need initialization)
            // Tabler icons usually work with just CSS
            
            // Initialize Feather Icons if you're using them instead
            if (typeof feather !== 'undefined') {
                feather.replace();
            }
            
            // Initialize Bootstrap Toasts
            var toastElList = [].slice.call(document.querySelectorAll('.toast'))
            var toastList = toastElList.map(function(toastEl) {
                return new bootstrap.Toast(toastEl, {
                    autohide: true,
                    delay: 5000
                })
            });
            
            toastList.forEach(toast => toast.show());
        });
    </script>
    
    <!-- Import Js Files -->
    <script src="{{ asset('assets/libs/bootstrap/dist/js/bootstrap.bundle.min.js') }}"></script>
    <script src="{{ asset('assets/libs/simplebar/dist/simplebar.min.js') }}"></script>
    <script src="{{ asset('assets/js/theme/app.init.js') }}"></script>
    <script src="{{ asset('assets/js/theme/theme.js') }}"></script>
    <script src="{{ asset('assets/js/theme/app.min.js') }}"></script>
    <script src="{{ asset('assets/js/theme/feather.min.js') }}"></script>
    
    @yield('scripts')
</body>

</html>