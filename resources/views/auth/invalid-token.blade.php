@extends('layouts.auth')

@section('title', __('Invalid Token'))

@section('auth-content')
<div class="text-center">
    <!-- Error Icon -->
    <div class="mb-4">
        <span class="bg-danger bg-opacity-10 p-3 rounded-circle d-inline-flex">
            <i class="ti ti-alert-circle text-danger fs-6"></i>
        </span>
    </div>
    
    <!-- Error Title -->
    <h4 class="fw-bold mb-2">{{__('Invalid or Expired Token')}}</h4>
    
    <!-- Error Message -->
    <p class="text-muted fs-3 mb-4">
        {{__('The authentication link you used is invalid or has expired.')}}
    </p>
    
    <!-- Request New Link Button -->
    <div class="d-grid mb-3">
        <a href="{{ route('login') }}" class="btn btn-primary py-8">
            <i class="ti ti-mail fs-5 me-2"></i>
            {{ __('Request New Magic Link') }}
        </a>
    </div>
    
    <!-- Alternative Option -->
    <div class="text-center">
        <p class="text-muted fs-2 mb-0">
            {{ __('Need help?') }} 
            <a href="mailto:admin@stardena.com" class="text-primary text-decoration-none">
                {{ __('Contact Support') }}
            </a>
        </p>
    </div>
</div>
@endsection