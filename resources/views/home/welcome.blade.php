@extends('layouts.auth')

@section('title', 'Magic Login - Stardena Works')

@section('auth-content')
    <div class="position-relative text-center my-4">
        <p class="mb-0 fs-4 px-3 d-inline-block bg-white text-dark z-index-5 position-relative">
            {{ __('Sign In With Magic Link') }}
        </p>
        <span class="border-top w-100 position-absolute top-50 start-50 translate-middle"></span>
    </div>
    
    <!-- In your form -->
    <form method="POST" action="{{ route('auth.send-login-link') }}">
        @csrf
        <div class="mb-3">
            <label for="email" class="form-label">{{ __('Email') }}</label>
            <input type="email" name="email" class="form-control @error('email') is-invalid @enderror" 
                id="email" value="{{ old('email') }}" required>
            @error('email')
                <span class="invalid-feedback" role="alert">
                    <strong>{{ $message }}</strong>
                </span>
            @enderror
        </div>
        
        <button type="submit" class="btn btn-primary w-100 py-8 mb-4">
            {{ __('Request Magic Link') }}
        </button>
    </form>
@endsection

@push('scripts')

@endpush