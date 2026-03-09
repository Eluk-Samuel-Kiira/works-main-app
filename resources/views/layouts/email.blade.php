<!DOCTYPE html>
<html lang="en" dir="ltr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <title>{{ $title ?? 'Stardena Works' }}</title>
    <style>
        /* Email-safe styles - keeping minimal since email clients strip most CSS */
        .email-wrapper {
            background-color: #f6f9fc;
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif;
            padding: 40px 20px;
        }
        .email-container {
            max-width: 600px;
            margin: 0 auto;
            background-color: #ffffff;
            border-radius: 16px;
            overflow: hidden;
            box-shadow: 0 10px 40px rgba(0, 0, 0, 0.08);
        }
        .email-header {
            padding: 32px 40px 24px;
            text-align: center;
            background: linear-gradient(135deg, #5a4bda 0%, #6c5ce7 100%);
        }
        .email-body {
            padding: 40px;
            background-color: #ffffff;
        }
        .email-footer {
            padding: 24px 40px;
            text-align: center;
            background-color: #f8fafc;
            border-top: 1px solid #e9ecef;
        }
        .btn {
            display: inline-block;
            padding: 14px 32px;
            background: linear-gradient(135deg, #5a4bda 0%, #6c5ce7 100%);
            color: #ffffff !important;
            text-decoration: none;
            border-radius: 12px;
            font-weight: 600;
            font-size: 16px;
            margin: 16px 0;
            box-shadow: 0 4px 12px rgba(90, 75, 218, 0.3);
        }
        .btn:hover {
            background: linear-gradient(135deg, #4a3bc0 0%, #5a4bda 100%);
        }
        .text-primary {
            color: #5a4bda !important;
        }
        .text-muted {
            color: #6c757d !important;
        }
        .divider {
            height: 1px;
            background-color: #e9ecef;
            margin: 24px 0;
        }
        .social-links {
            margin: 20px 0 10px;
        }
        .social-links a {
            display: inline-block;
            margin: 0 8px;
            color: #5a4bda;
            text-decoration: none;
        }
        @media only screen and (max-width: 600px) {
            .email-header, .email-body, .email-footer {
                padding: 24px 20px;
            }
        }
    </style>
</head>
<body style="margin: 0; padding: 0; background-color: #f6f9fc;">
    <div class="email-wrapper" style="background-color: #f6f9fc; font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif; padding: 40px 20px;">
        <div class="email-container" style="max-width: 600px; margin: 0 auto; background-color: #ffffff; border-radius: 16px; overflow: hidden; box-shadow: 0 10px 40px rgba(0, 0, 0, 0.08);">
            
            <!-- Header with Logo -->
            <div class="email-header" style="padding: 32px 40px 24px; text-align: center; background: linear-gradient(135deg, #5a4bda 0%, #6c5ce7 100%);">
                <h1 style="color: #ffffff; margin: 0 0 8px 0; font-size: 36px; font-weight: 800; letter-spacing: 1px; text-shadow: 0 2px 4px rgba(0,0,0,0.2); text-align: center;">
                    Stardena Works
                </h1>
                <h4 style="color: #ffffff; margin: 0; font-size: 18px; font-weight: 400; letter-spacing: 0.5px; opacity: 0.9;">
                    @yield('title')
                </h4>
            </div>
            
            <!-- Main Content -->
            <div class="email-body" style="padding: 40px; background-color: #ffffff;">
                @yield('content')
            </div>
            
            <!-- Footer -->
            <div class="email-footer" style="padding: 24px 40px; text-align: center; background-color: #f8fafc; border-top: 1px solid #e9ecef;">
                <div class="social-links" style="margin: 20px 0 10px;">
                    <a href="#" style="display: inline-block; margin: 0 8px; color: #5a4bda; text-decoration: none;">Facebook</a>
                    <a href="#" style="display: inline-block; margin: 0 8px; color: #5a4bda; text-decoration: none;">Twitter</a>
                    <a href="#" style="display: inline-block; margin: 0 8px; color: #5a4bda; text-decoration: none;">LinkedIn</a>
                    <a href="#" style="display: inline-block; margin: 0 8px; color: #5a4bda; text-decoration: none;">GitHub</a>
                </div>
                <p style="margin: 0; color: #6c757d; font-size: 14px;">
                    © {{ date('Y') }} Stardena Works. All rights reserved.
                </p>
                <p style="margin: 8px 0 0; color: #6c757d; font-size: 12px;">
                    <a href="#" style="color: #5a4bda; text-decoration: none;">Privacy Policy</a> • 
                    <a href="#" style="color: #5a4bda; text-decoration: none;">Terms of Service</a> • 
                    <a href="#" style="color: #5a4bda; text-decoration: none;">Unsubscribe</a>
                </p>
            </div>
        </div>
    </div>
</body>
</html>