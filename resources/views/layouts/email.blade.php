<!DOCTYPE html>
<html lang="en" dir="ltr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=yes">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <title>{{ $title ?? 'Stardena Works' }}</title>
    <style>
        /* Email-safe styles - optimized for mobile */
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            margin: 0;
            padding: 0;
            background-color: #f6f9fc;
            -webkit-text-size-adjust: 100%;
            -ms-text-size-adjust: 100%;
        }
        
        .email-wrapper {
            background-color: #f6f9fc;
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif;
            padding: 20px 12px;
        }
        
        .email-container {
            max-width: 600px;
            width: 100%;
            margin: 0 auto;
            background-color: #ffffff;
            border-radius: 20px;
            overflow: hidden;
            box-shadow: 0 8px 24px rgba(0, 0, 0, 0.08);
        }
        
        .email-header {
            padding: 40px 24px 32px;
            text-align: center;
            background: linear-gradient(135deg, #5a4bda 0%, #6c5ce7 100%);
        }
        
        .email-header h1 {
            color: #ffffff;
            margin: 0 0 12px 0;
            font-size: 32px;
            font-weight: 800;
            letter-spacing: 1px;
            text-shadow: 0 2px 4px rgba(0,0,0,0.2);
            text-align: center;
            line-height: 1.2;
            word-break: break-word;
        }
        
        .email-header h4 {
            color: #ffffff;
            margin: 0;
            font-size: 16px;
            font-weight: 400;
            letter-spacing: 0.5px;
            opacity: 0.95;
            line-height: 1.4;
            word-break: break-word;
        }
        
        .email-body {
            padding: 32px 24px;
            background-color: #ffffff;
        }
        
        /* Mobile-friendly content styles */
        .email-body p {
            font-size: 16px;
            line-height: 1.5;
            color: #2c3e50;
            margin-bottom: 16px;
            word-break: break-word;
        }
        
        .email-body h1, .email-body h2, .email-body h3 {
            color: #1a202c;
            margin-bottom: 16px;
            line-height: 1.3;
            word-break: break-word;
        }
        
        .email-body h2 {
            font-size: 24px;
        }
        
        .email-body h3 {
            font-size: 20px;
        }
        
        .btn {
            display: inline-block;
            padding: 14px 28px;
            background: linear-gradient(135deg, #5a4bda 0%, #6c5ce7 100%);
            color: #ffffff !important;
            text-decoration: none;
            border-radius: 12px;
            font-weight: 600;
            font-size: 16px;
            margin: 16px 0;
            text-align: center;
            width: auto;
            min-width: 200px;
            max-width: 100%;
            box-shadow: 0 4px 12px rgba(90, 75, 218, 0.25);
            transition: all 0.3s ease;
        }
        
        /* Make buttons full width on very small screens */
        @media only screen and (max-width: 480px) {
            .btn {
                display: block;
                width: 100%;
                padding: 14px 20px;
                box-sizing: border-box;
            }
        }
        
        .text-primary {
            color: #5a4bda !important;
        }
        
        .text-muted {
            color: #6c757d !important;
            font-size: 14px;
            line-height: 1.5;
        }
        
        .divider {
            height: 1px;
            background-color: #e9ecef;
            margin: 24px 0;
        }
        
        /* Mobile-friendly lists */
        .email-body ul, .email-body ol {
            margin: 16px 0;
            padding-left: 24px;
        }
        
        .email-body li {
            margin-bottom: 8px;
            line-height: 1.5;
            font-size: 16px;
        }
        
        /* Card-like elements for better mobile display */
        .info-card {
            background-color: #f8fafc;
            border-radius: 12px;
            padding: 20px;
            margin: 20px 0;
            border: 1px solid #e9ecef;
        }
        
        /* Social links - mobile optimized */
        .social-links {
            margin: 20px 0 16px;
            text-align: center;
        }
        
        .social-links a {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            margin: 4px 8px;
            padding: 8px 12px;
            color: #5a4bda;
            text-decoration: none;
            font-size: 14px;
            font-weight: 500;
            border-radius: 20px;
            background-color: #ffffff;
            transition: background-color 0.3s ease;
        }
        
        /* Mobile social links */
        @media only screen and (max-width: 480px) {
            .social-links a {
                display: inline-block;
                margin: 4px 6px;
                padding: 8px 10px;
                font-size: 13px;
            }
        }
        
        .email-footer {
            padding: 32px 24px;
            text-align: center;
            background-color: #f8fafc;
            border-top: 1px solid #e9ecef;
        }
        
        .email-footer p {
            margin: 0 0 12px 0;
            color: #6c757d;
            font-size: 13px;
            line-height: 1.5;
        }
        
        .footer-links {
            margin: 12px 0 0;
        }
        
        .footer-links a {
            display: inline-block;
            margin: 4px 8px;
            color: #5a4bda;
            text-decoration: none;
            font-size: 12px;
            font-weight: 500;
        }
        
        /* Responsive tables for email clients */
        .responsive-table {
            width: 100%;
            border-collapse: collapse;
        }
        
        .responsive-table td {
            padding: 12px;
            display: block;
            width: 100%;
            box-sizing: border-box;
        }
        
        @media only screen and (max-width: 600px) {
            .email-wrapper {
                padding: 16px 10px;
            }
            
            .email-header {
                padding: 32px 20px 28px;
            }
            
            .email-header h1 {
                font-size: 28px;
            }
            
            .email-header h4 {
                font-size: 15px;
            }
            
            .email-body {
                padding: 28px 20px;
            }
            
            .email-footer {
                padding: 28px 20px;
            }
            
            .email-body p, 
            .email-body li {
                font-size: 15px;
            }
            
            .info-card {
                padding: 16px;
                margin: 16px 0;
            }
        }
        
        /* Extra small devices */
        @media only screen and (max-width: 380px) {
            .email-header h1 {
                font-size: 24px;
            }
            
            .email-body {
                padding: 24px 16px;
            }
            
            .btn {
                font-size: 15px;
                padding: 12px 16px;
            }
        }
        
        /* Touch-friendly tap targets */
        a, button, .btn {
            min-height: 44px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
        }
        
        /* Improve readability */
        .email-body {
            font-size: 16px;
            line-height: 1.5;
        }
        
        /* Images responsive */
        img {
            max-width: 100%;
            height: auto;
            display: block;
        }
    </style>
</head>
<body style="margin: 0; padding: 0; background-color: #f6f9fc; -webkit-text-size-adjust: 100%;">
    <div class="email-wrapper" style="background-color: #f6f9fc; font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif; padding: 20px 12px;">
        <div class="email-container" style="max-width: 600px; width: 100%; margin: 0 auto; background-color: #ffffff; border-radius: 20px; overflow: hidden; box-shadow: 0 8px 24px rgba(0, 0, 0, 0.08);">
            
            <!-- Header with Logo -->
            <div class="email-header" style="padding: 40px 24px 32px; text-align: center; background: linear-gradient(135deg, #5a4bda 0%, #6c5ce7 100%);">
                <h1 style="color: #ffffff; margin: 0 0 12px 0; font-size: 32px; font-weight: 800; letter-spacing: 1px; text-shadow: 0 2px 4px rgba(0,0,0,0.2); text-align: center; line-height: 1.2;">
                    Stardena Works
                </h1>
                <h4 style="color: #ffffff; margin: 0; font-size: 16px; font-weight: 400; letter-spacing: 0.5px; opacity: 0.95; line-height: 1.4;">
                    @yield('title')
                </h4>
            </div>
            
            <!-- Main Content -->
            <div class="email-body" style="padding: 32px 24px; background-color: #ffffff;">
                @yield('content')
            </div>
            
            <!-- Footer -->
            <div class="email-footer" style="padding: 32px 24px; text-align: center; background-color: #f8fafc; border-top: 1px solid #e9ecef;">
                <div class="social-links" style="margin: 20px 0 16px; text-align: center;">
                    <a href="#" style="display: inline-flex; align-items: center; justify-content: center; margin: 4px 8px; padding: 8px 12px; color: #5a4bda; text-decoration: none; font-size: 14px; font-weight: 500; border-radius: 20px; background-color: #ffffff; min-height: 44px;">Facebook</a>
                    <a href="#" style="display: inline-flex; align-items: center; justify-content: center; margin: 4px 8px; padding: 8px 12px; color: #5a4bda; text-decoration: none; font-size: 14px; font-weight: 500; border-radius: 20px; background-color: #ffffff; min-height: 44px;">Twitter</a>
                    <a href="#" style="display: inline-flex; align-items: center; justify-content: center; margin: 4px 8px; padding: 8px 12px; color: #5a4bda; text-decoration: none; font-size: 14px; font-weight: 500; border-radius: 20px; background-color: #ffffff; min-height: 44px;">LinkedIn</a>
                    <a href="#" style="display: inline-flex; align-items: center; justify-content: center; margin: 4px 8px; padding: 8px 12px; color: #5a4bda; text-decoration: none; font-size: 14px; font-weight: 500; border-radius: 20px; background-color: #ffffff; min-height: 44px;">GitHub</a>
                </div>
                <p style="margin: 0 0 12px 0; color: #6c757d; font-size: 13px; line-height: 1.5;">
                    © {{ date('Y') }} Stardena Works. All rights reserved.
                </p>
                <div class="footer-links" style="margin: 12px 0 0;">
                    <a href="#" style="display: inline-block; margin: 4px 8px; color: #5a4bda; text-decoration: none; font-size: 12px; font-weight: 500; min-height: 44px; line-height: 44px;">Privacy Policy</a> • 
                    <a href="#" style="display: inline-block; margin: 4px 8px; color: #5a4bda; text-decoration: none; font-size: 12px; font-weight: 500; min-height: 44px; line-height: 44px;">Terms of Service</a> • 
                    <a href="#" style="display: inline-block; margin: 4px 8px; color: #5a4bda; text-decoration: none; font-size: 12px; font-weight: 500; min-height: 44px; line-height: 44px;">Unsubscribe</a>
                </div>
            </div>
        </div>
    </div>
</body>
</html>