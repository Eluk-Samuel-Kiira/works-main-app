{{-- resources/views/emails/contact-admin.blade.php - MAIN APP --}}
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=yes">
    <title>New Contact Form Submission - Stardena Works</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif;
            background: #f3f4f6;
            padding: 16px;
            line-height: 1.5;
        }
        
        .container {
            max-width: 100%;
            width: 100%;
            background: #ffffff;
            border-radius: 16px;
            overflow: hidden;
        }
        
        /* Header */
        .header {
            background: #1e3a8a;
            padding: 24px 20px;
            text-align: center;
        }
        
        .logo {
            font-size: 24px;
            font-weight: 700;
            color: white;
            margin-bottom: 12px;
        }
        
        .logo span {
            color: #fbbf24;
        }
        
        .header h1 {
            color: white;
            font-size: 20px;
            font-weight: 600;
            margin-bottom: 6px;
        }
        
        .header p {
            color: #bfdbfe;
            font-size: 13px;
        }
        
        .badge {
            display: inline-block;
            background: rgba(251, 191, 36, 0.2);
            padding: 4px 12px;
            border-radius: 20px;
            font-size: 11px;
            font-weight: 500;
            color: #fbbf24;
            margin-top: 12px;
        }
        
        /* Content */
        .content {
            padding: 20px;
        }
        
        /* Message Card */
        .card {
            background: #f9fafb;
            border-radius: 12px;
            padding: 16px;
            margin-bottom: 20px;
            border: 1px solid #e5e7eb;
        }
        
        .card-title {
            font-size: 14px;
            font-weight: 600;
            color: #374151;
            margin-bottom: 12px;
            padding-bottom: 8px;
            border-bottom: 1px solid #e5e7eb;
        }
        
        .row {
            margin-bottom: 12px;
        }
        
        .label {
            font-size: 12px;
            color: #6b7280;
            margin-bottom: 4px;
        }
        
        .value {
            font-size: 14px;
            color: #111827;
            font-weight: 500;
            word-break: break-word;
        }
        
        .value a {
            color: #2563eb;
            text-decoration: none;
        }
        
        .message-box {
            background: #ffffff;
            border-left: 3px solid #2563eb;
            padding: 14px;
            border-radius: 8px;
            margin-top: 8px;
        }
        
        .message-box p {
            font-size: 13px;
            color: #374151;
            line-height: 1.5;
            white-space: pre-wrap;
        }
        
        /* Tips Box */
        .tips {
            background: #fffbeb;
            border-left: 3px solid #f59e0b;
            padding: 14px;
            border-radius: 8px;
            margin-top: 16px;
        }
        
        .tips p {
            font-size: 12px;
            color: #78350f;
        }
        
        /* Buttons */
        .buttons {
            margin-top: 24px;
        }
        
        .btn {
            display: block;
            width: 100%;
            text-align: center;
            padding: 12px;
            border-radius: 40px;
            text-decoration: none;
            font-weight: 600;
            font-size: 14px;
            margin-bottom: 10px;
        }
        
        .btn-primary {
            background: #2563eb;
            color: white;
        }
        
        .btn-outline {
            background: transparent;
            color: #2563eb;
            border: 1px solid #2563eb;
        }
        
        /* Footer */
        .footer {
            background: #f9fafb;
            padding: 16px 20px;
            text-align: center;
            border-top: 1px solid #e5e7eb;
        }
        
        .footer p {
            font-size: 11px;
            color: #6b7280;
            margin-bottom: 6px;
        }
        
        hr {
            margin: 16px 0;
            border: none;
            border-top: 1px solid #e5e7eb;
        }
        
        @media (max-width: 480px) {
            body {
                padding: 12px;
            }
            
            .header {
                padding: 20px 16px;
            }
            
            .content {
                padding: 16px;
            }
            
            .logo {
                font-size: 20px;
            }
            
            .header h1 {
                font-size: 18px;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        {{-- Header --}}
        <div class="header">
            <div class="logo">
                Stardena <span>Works</span>
            </div>
            <h1>📬 New Contact Message</h1>
            <p>Action required - Respond within 24 hours</p>
            <div class="badge">⚡ Priority: Medium</div>
        </div>
        
        {{-- Content --}}
        <div class="content">
            {{-- Contact Info Card --}}
            <div class="card">
                <div class="card-title">👤 Contact Information</div>
                
                <div class="row">
                    <div class="label">Full Name</div>
                    <div class="value">{{ $data['name'] ?? 'N/A' }}</div>
                </div>
                
                <div class="row">
                    <div class="label">Email Address</div>
                    <div class="value">
                        <a href="mailto:{{ $data['email'] ?? 'N/A' }}">
                            {{ $data['email'] ?? 'N/A' }}
                        </a>
                    </div>
                </div>
                
                <div class="row">
                    <div class="label">Subject</div>
                    <div class="value">
                        <span style="background: #dbeafe; padding: 2px 10px; border-radius: 16px; font-size: 12px;">
                            {{ $data['subject'] ?? 'General Inquiry' }}
                        </span>
                    </div>
                </div>
                
                <div class="row">
                    <div class="label">Submitted At</div>
                    <div class="value">{{ now()->format('M j, Y g:i A') }}</div>
                </div>
            </div>
            
            {{-- Message Card --}}
            <div class="card">
                <div class="card-title">💬 Message</div>
                <div class="message-box">
                    <p>{{ nl2br(e($data['message'] ?? 'No message provided')) }}</p>
                </div>
            </div>
            
            {{-- Action Buttons --}}
            <div class="buttons">
                <a href="mailto:{{ $data['email'] ?? '' }}?subject=Re: {{ urlencode($data['subject'] ?? 'Your Inquiry') }}" class="btn btn-primary">
                    ✉️ Reply to {{ $data['name'] ?? 'Sender' }}
                </a>
                <a href="{{ config('app.url') }}/admin/notifications" class="btn btn-outline">
                    📋 View in Dashboard
                </a>
            </div>
            
            {{-- Tips --}}
            <div class="tips">
                <p>💡 <strong>Pro Tip:</strong> Responding within 24 hours increases customer satisfaction by 85%.</p>
            </div>
        </div>
        
        {{-- Footer --}}
        <div class="footer">
            <p><strong>Stardena Works</strong> — Connecting talent with opportunity</p>
            <p>© {{ date('Y') }} Stardena Works. All rights reserved.</p>
        </div>
    </div>
</body>
</html>