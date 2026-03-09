@extends('layouts.email')

@section('title', $isWelcomeEmail ? __('Welcome to Stardena Works') : __('Magic Login Link'))

@section('content')
    <!-- Greeting -->
    <div style="text-align: center; margin-bottom: 30px;">
        <h1 style="color: #1a2639; font-size: 28px; font-weight: 700; margin: 0 0 8px; letter-spacing: -0.5px;">
            @if($isWelcomeEmail)
                {{ __('Welcome to Stardena Works!') }}
            @else
                {{ __('Hello!') }}
            @endif
        </h1>
        
        <p style="color: #6c757d; font-size: 16px; line-height: 1.6; margin: 0;">
            @if($isWelcomeEmail)
                {{ __('Your account has been created. Click the button below to securely access Stardena Works.') }}
            @else
                {{ __('You requested a magic link to securely access your Stardena Works account.') }}
            @endif
        </p>
    </div>
    
    <!-- Magic Link Button -->
    <div style="text-align: center; margin: 35px 0 25px;">
        <a href="{{ $loginUrl }}" class="btn-primary" style="display: inline-block; padding: 16px 36px; background: linear-gradient(145deg, #5a4bda 0%, #6c5ce7 100%); color: #ffffff !important; text-decoration: none; border-radius: 14px; font-weight: 600; font-size: 16px; letter-spacing: 0.3px; box-shadow: 0 8px 20px rgba(90, 75, 218, 0.25);">
            🔑 {{ __('Login to Stardena Works') }}
        </a>
        
        <p style="color: #6c757d; font-size: 14px; margin: 12px 0 0;">
            {{ __('This link will expire in 24 hours') }}
        </p>
    </div>
    
    <!-- Alternative Link Card -->
    <div style="background-color: #f8fafc; border-radius: 12px; padding: 16px; margin: 30px 0; border: 1px dashed #d0d9e8;">
        <p style="color: #6c757d; font-size: 13px; margin: 0 0 8px; font-weight: 500;">🔗 {{ __("Can't click the button? Copy this link:") }}</p>
        <p style="font-family: 'Courier New', monospace; font-size: 13px; color: #5a4bda; word-break: break-all; margin: 0; background-color: #ffffff; padding: 12px; border-radius: 8px; border: 1px solid #eef2f6;">
            {{ $loginUrl }}
        </p>
    </div>
    
    <!-- Quick Login Info (only for welcome email) -->
    @if($isWelcomeEmail)
    <div style="background-color: #e8f0fe; border-radius: 16px; padding: 20px; margin: 30px 0; border: 1px solid #ccdfff;">
        <div style="display: flex; gap: 16px; align-items: flex-start;">
            <div style="flex-shrink: 0; width: 32px; height: 32px; background-color: #5a4bda20; border-radius: 50%; display: flex; align-items: center; justify-content: center; color: #5a4bda;">⚡</div>
            <div>
                <h4 style="color: #1a2639; font-size: 15px; font-weight: 600; margin: 0 0 4px;">{{ __('Quick Access') }}</h4>
                <p style="color: #4a5568; font-size: 14px; line-height: 1.5; margin: 0;">
                    {{ __('No password needed! Just click the magic link above anytime to access your account securely.') }}
                </p>
            </div>
        </div>
    </div>
    @endif
    
    <!-- Security Notice -->
    <div style="display: flex; gap: 16px; align-items: flex-start; background-color: #fff8e7; border-radius: 16px; padding: 20px; margin: 24px 0; border: 1px solid #ffe4b5;">
        <div style="flex-shrink: 0; width: 32px; height: 32px; background-color: #fff3cd; border-radius: 50%; display: flex; align-items: center; justify-content: center; color: #856404;">🔒</div>
        <div>
            <h4 style="color: #856404; font-size: 15px; font-weight: 600; margin: 0 0 4px;">{{ __('Security Information') }}</h4>
            <p style="color: #856404; font-size: 13px; line-height: 1.5; margin: 0;">
                @if($isWelcomeEmail)
                    {{ __("If you didn't create an account with Stardena Works, please ignore this email or") }} 
                @else
                    {{ __("If you didn't request this login link, please ignore this email or") }}
                @endif
                <a href="#" style="color: #5a4bda; text-decoration: underline;">{{ __('contact support') }}</a> {{ __('immediately.') }}
            </p>
        </div>
    </div>
    
    <!-- Expiry Notice -->
    <div style="margin-top: 20px; text-align: center;">
        <span style="background-color: #f0f2f5; border-radius: 30px; padding: 6px 16px; color: #6c757d; font-size: 13px; font-weight: 500;">
            {{ __('Link expires on') }} {{ $expiresAt }}
        </span>
    </div>
@endsection