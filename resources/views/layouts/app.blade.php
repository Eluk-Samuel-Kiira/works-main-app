
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
    <link rel="stylesheet" href="{{ asset('assets/libs/bootstrap-datepicker/dist/css/bootstrap-datepicker.min.css') }}" />

    <title>@yield('title')</title>
    @stack('rich-editor-styles')
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

    </div>
    <div class="dark-transparent sidebartoggler"></div>
    
    <!-- Toast Container -->
    <div class="toast-container position-fixed top-0 end-0 p-3" style="z-index: 9999; max-width: 400px;">
        @if(session('success'))
            <div class="toast align-items-center text-white bg-success border-0 w-100" role="alert" aria-live="assertive" aria-atomic="true" data-bs-autohide="true" data-bs-delay="5000" data-bs-animation="true">
                <div class="d-flex">
                    <div class="toast-body d-flex align-items-center gap-3">
                        <span class="d-flex align-items-center" style="width: 24px; height: 24px;">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" width="24" height="24">
                                <polyline points="20 6 9 17 4 12"/>
                            </svg>
                        </span>
                        <span class="fs-3">{{ session('success') }}</span>
                    </div>
                    <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast" aria-label="Close"></button>
                </div>
            </div>
        @endif

        @if(session('error'))
            <div class="toast align-items-center text-white bg-danger border-0 w-100" role="alert" aria-live="assertive" aria-atomic="true" data-bs-autohide="true" data-bs-delay="5000" data-bs-animation="true">
                <div class="d-flex">
                    <div class="toast-body d-flex align-items-center gap-3">
                        <span class="d-flex align-items-center" style="width: 24px; height: 24px;">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" width="24" height="24">
                                <circle cx="12" cy="12" r="10"/>
                                <line x1="15" y1="9" x2="9" y2="15"/>
                                <line x1="9" y1="9" x2="15" y2="15"/>
                            </svg>
                        </span>
                        <span class="fs-3">{{ session('error') }}</span>
                    </div>
                    <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast" aria-label="Close"></button>
                </div>
            </div>
        @endif

        @if(session('warning'))
            <div class="toast align-items-center text-white bg-warning border-0 w-100" role="alert" aria-live="assertive" aria-atomic="true" data-bs-autohide="true" data-bs-delay="5000" data-bs-animation="true">
                <div class="d-flex">
                    <div class="toast-body d-flex align-items-center gap-3">
                        <span class="d-flex align-items-center" style="width: 24px; height: 24px;">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" width="24" height="24">
                                <path d="M10.29 3.86L1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"/>
                                <line x1="12" y1="9" x2="12" y2="13"/>
                                <line x1="12" y1="17" x2="12.01" y2="17"/>
                            </svg>
                        </span>
                        <span class="fs-3">{{ session('warning') }}</span>
                    </div>
                    <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast" aria-label="Close"></button>
                </div>
            </div>
        @endif

        @if(session('info'))
            <div class="toast align-items-center text-white bg-info border-0 w-100" role="alert" aria-live="assertive" aria-atomic="true" data-bs-autohide="true" data-bs-delay="5000" data-bs-animation="true">
                <div class="d-flex">
                    <div class="toast-body d-flex align-items-center gap-3">
                        <span class="d-flex align-items-center" style="width: 24px; height: 24px;">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" width="24" height="24">
                                <circle cx="12" cy="12" r="10"/>
                                <line x1="12" y1="8" x2="12" y2="12"/>
                                <line x1="12" y1="16" x2="12.01" y2="16"/>
                            </svg>
                        </span>
                        <span class="fs-3">{{ session('info') }}</span>
                    </div>
                    <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast" aria-label="Close"></button>
                </div>
            </div>
        @endif
    </div>

    <!-- Toast Initialization Script -->
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // SVG icons per type
            const ICONS = {
                success: `<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" width="24" height="24"><polyline points="20 6 9 17 4 12"/></svg>`,
                error:   `<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" width="24" height="24"><circle cx="12" cy="12" r="10"/><line x1="15" y1="9" x2="9" y2="15"/><line x1="9" y1="9" x2="15" y2="15"/></svg>`,
                warning: `<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" width="24" height="24"><path d="M10.29 3.86L1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"/><line x1="12" y1="9" x2="12" y2="13"/><line x1="12" y1="17" x2="12.01" y2="17"/></svg>`,
                info:    `<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" width="24" height="24"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>`,
                slide:   `<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" width="24" height="24"><polyline points="8 18 12 22 16 18"/><polyline points="8 6 12 2 16 6"/><line x1="12" y1="2" x2="12" y2="22"/></svg>`
            };
            
            // Initialize Bootstrap Toasts with slide animation
            var toastElList = [].slice.call(document.querySelectorAll('.toast'));
            var toastList = toastElList.map(function(toastEl) {
                return new bootstrap.Toast(toastEl, {
                    autohide: true,
                    delay: 5000,
                    animation: true
                });
            });
            
            // Show all toasts
            toastList.forEach(toast => toast.show());
            
            // Function to show toast of any type
            window.showToast = function(type, message) {
                const bgColors = {
                    'success': 'bg-success',
                    'error': 'bg-danger',
                    'warning': 'bg-warning',
                    'info': 'bg-info',
                    'slide': 'bg-primary'
                };
                
                const icon = ICONS[type] || ICONS.info;
                const bgColor = bgColors[type] || bgColors.info;
                
                // Create toast element
                const toastHtml = `
                    <div class="toast align-items-center text-white ${bgColor} border-0 w-100" role="alert" aria-live="assertive" aria-atomic="true" data-bs-autohide="true" data-bs-delay="5000" data-bs-animation="true">
                        <div class="d-flex">
                            <div class="toast-body d-flex align-items-center gap-3">
                                <span class="d-flex align-items-center" style="width: 24px; height: 24px;">
                                    ${icon}
                                </span>
                                <span class="fs-3">${message}</span>
                            </div>
                            <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast" aria-label="Close"></button>
                        </div>
                    </div>
                `;
                
                // Add to container and show
                const container = document.querySelector('.toast-container');
                const tempDiv = document.createElement('div');
                tempDiv.innerHTML = toastHtml;
                const toastElement = tempDiv.firstElementChild;
                container.appendChild(toastElement);
                
                const toast = new bootstrap.Toast(toastElement, {
                    autohide: true,
                    delay: 5000,
                    animation: true
                });
                toast.show();
                
                // Remove after hidden
                toastElement.addEventListener('hidden.bs.toast', function() {
                    toastElement.remove();
                });
            };
            
            // Single button to demonstrate all toast types one by one
            const slideBtn = document.getElementById('slide-toast');
            if(slideBtn) {
                const toastTypes = [
                    { type: 'success', message: 'Operation completed successfully!', bg: 'bg-success-subtle', text: 'text-success' },
                    { type: 'info', message: 'New information available.', bg: 'bg-info-subtle', text: 'text-info' },
                    { type: 'warning', message: 'Please check your settings.', bg: 'bg-warning-subtle', text: 'text-warning' },
                    { type: 'error', message: 'Something went wrong!', bg: 'bg-danger-subtle', text: 'text-danger' },
                    { type: 'slide', message: 'Slide animation demo!', bg: 'bg-primary-subtle', text: 'text-primary' }
                ];
                
                let toastIndex = 0;
                
                slideBtn.addEventListener('click', function() {
                    // Show one toast at a time in sequence
                    if (toastIndex < toastTypes.length) {
                        const current = toastTypes[toastIndex];
                        showToast(current.type, current.message);
                        
                        // Update button text to show next
                        if (toastIndex < toastTypes.length - 1) {
                            const next = toastTypes[toastIndex + 1];
                            slideBtn.innerHTML = `Next: ${next.type.charAt(0).toUpperCase() + next.type.slice(1)} Toast →`;
                            slideBtn.className = `btn px-4 fs-4 ${next.bg} ${next.text} fw-medium`;
                        } else {
                            slideBtn.innerHTML = '✓ All Toasts Displayed';
                            slideBtn.className = 'btn px-4 fs-4 bg-success-subtle text-success fw-medium';
                            slideBtn.disabled = true;
                        }
                        
                        toastIndex++;
                    }
                });
                
                // Initial button setup
                if (toastTypes.length > 0) {
                    const first = toastTypes[0];
                    slideBtn.innerHTML = `Start: ${first.type.charAt(0).toUpperCase() + first.type.slice(1)} Toast →`;
                    slideBtn.className = `btn px-4 fs-4 ${first.bg} ${first.text} fw-medium`;
                }
            }
        });
    </script>

    @include('layouts.editer')

<!-- First, load jQuery -->
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>

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


<!-- Then load other dependencies -->
<script src="{{ asset('assets/libs/bootstrap-datepicker/dist/js/bootstrap-datepicker.min.js') }}"></script>
<script src="{{ asset('assets/js/extra-libs/moment/moment.min.js') }}"></script>

<!-- Then load your custom scripts -->
<script src="{{ asset('assets/js/dashboards/dashboard1.js') }}"></script>
<script src="{{ asset('assets/js/forms/datepicker-init.js') }}"></script>

<script defer src="https://static.cloudflareinsights.com/beacon.min.js/v8c78df7c7c0f484497ecbca7046644da1771523124516" integrity="sha512-8DS7rgIrAmghBFwoOTujcf6D9rXvH8xm8JQ1Ja01h9QX8EzXldiszufYa4IFfKdLUKTTrnSFXLDkUEOTrZQ8Qg==" data-cf-beacon='{"version":"2024.11.0","token":"ceaa0eef431a46f3b195537c6963f062","r":1,"server_timing":{"name":{"cfCacheStatus":true,"cfEdge":true,"cfExtPri":true,"cfL4":true,"cfOrigin":true,"cfSpeedBrain":true},"location_startswith":null}}' crossorigin="anonymous"></script>
@stack('rich-editor-scripts')
</body>

</html>