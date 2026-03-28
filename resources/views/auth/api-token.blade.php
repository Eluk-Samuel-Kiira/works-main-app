{{--
    ══════════════════════════════════════════════════════
    FILE: resources/views/layouts/partials/api-token.blade.php

    Include this inside <head> in your main layout files
    (layouts/home.blade.php, layouts/jobs.blade.php, etc.)

    It injects the Sanctum token into window.ApiConfig so that
    every fetch() / axios call on the page can send it automatically.
    ══════════════════════════════════════════════════════
--}}
<script>
  window.ApiConfig = {
    baseUrl:   '{{ config("api.main_app.api_base") }}',
    token:     '{{ session("api_token", "") }}',
    userId:    {{ Auth::check() ? Auth::id() : 'null' }},
    userName:  '{{ Auth::check() ? Auth::user()->full_name : "" }}',
    userEmail: '{{ Auth::check() ? Auth::user()->email : "" }}',
    csrfToken: '{{ csrf_token() }}',
  };

  // ── Global fetch wrapper ─────────────────────────────────────────────────
  // Replace bare fetch() calls with apiFetch() to automatically
  // attach the Bearer token and CSRF header.
  window.apiFetch = function(url, options = {}) {
    const headers = {
      'Content-Type':     'application/json',
      'Accept':           'application/json',
      'X-Requested-With': 'XMLHttpRequest',
      'X-CSRF-TOKEN':     window.ApiConfig.csrfToken,
      ...(options.headers || {}),
    };

    if (window.ApiConfig.token) {
      headers['Authorization'] = 'Bearer ' + window.ApiConfig.token;
    }

    return fetch(url, { ...options, headers });
  };
</script>