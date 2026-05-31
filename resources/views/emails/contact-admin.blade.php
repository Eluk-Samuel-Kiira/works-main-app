{{-- resources/views/emails/contact-admin.blade.php - MAIN APP --}}
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>New Contact Form Submission - Stardena Works</title>
    <style>
        /* Reset styles */
        body, html {
            margin: 0;
            padding: 0;
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif;
            background: linear-gradient(135deg, #f0f7ff 0%, #e8f0fe 100%);
            -webkit-font-smoothing: antialiased;
        }
        
        /* Main container */
        .email-container {
            max-width: 600px;
            margin: 40px auto;
            background: #ffffff;
            border-radius: 20px;
            overflow: hidden;
            box-shadow: 0 20px 35px -10px rgba(0, 0, 0, 0.1);
        }
        
        /* Header with Stardena gradient */
        .email-header {
            background: linear-gradient(135deg, #1e3a8a 0%, #3b82f6 100%);
            padding: 40px 30px;
            text-align: center;
            position: relative;
            overflow: hidden;
        }
        
        .email-header::before {
            content: '';
            position: absolute;
            top: -50%;
            right: -20%;
            width: 200px;
            height: 200px;
            background: rgba(255, 255, 255, 0.1);
            border-radius: 50%;
        }
        
        .email-header::after {
            content: '';
            position: absolute;
            bottom: -30%;
            left: -10%;
            width: 150px;
            height: 150px;
            background: rgba(255, 255, 255, 0.08);
            border-radius: 50%;
        }
        
        .logo {
            font-size: 32px;
            font-weight: 800;
            color: white;
            margin-bottom: 10px;
            position: relative;
            z-index: 1;
        }
        
        .logo span {
            color: #fbbf24;
        }
        
        .email-header h1 {
            color: white;
            margin: 0;
            font-size: 28px;
            font-weight: 700;
            position: relative;
            z-index: 1;
        }
        
        .email-header p {
            color: rgba(255, 255, 255, 0.9);
            margin: 10px 0 0;
            font-size: 14px;
            position: relative;
            z-index: 1;
        }
        
        .priority-badge {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            background: rgba(245, 158, 11, 0.15);
            backdrop-filter: blur(10px);
            padding: 6px 14px;
            border-radius: 30px;
            font-size: 12px;
            font-weight: 600;
            color: #fbbf24;
            margin-top: 15px;
        }
        
        /* Content area */
        .email-content {
            padding: 35px 30px;
        }
        
        /* Welcome message */
        .welcome-message {
            background: linear-gradient(135deg, #eff6ff 0%, #ffffff 100%);
            border-radius: 16px;
            padding: 20px;
            margin-bottom: 30px;
            border: 1px solid rgba(59, 130, 246, 0.1);
        }
        
        .welcome-message h2 {
            font-size: 20px;
            font-weight: 700;
            color: #1e3a8a;
            margin: 0 0 5px;
        }
        
        /* Section styling */
        .info-section {
            margin-bottom: 30px;
            background: #ffffff;
            border: 1px solid #e2e8f0;
            border-radius: 16px;
            overflow: hidden;
        }
        
        .section-header {
            background: #f8fafc;
            padding: 15px 20px;
            border-bottom: 2px solid #e2e8f0;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .section-header .icon {
            width: 32px;
            height: 32px;
            background: linear-gradient(135deg, #3b82f6, #1e3a8a);
            border-radius: 10px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 16px;
        }
        
        .section-header h3 {
            margin: 0;
            font-size: 16px;
            font-weight: 700;
            color: #1e293b;
        }
        
        .info-grid {
            padding: 20px;
        }
        
        .info-row {
            display: flex;
            padding: 12px 0;
            border-bottom: 1px solid #f1f5f9;
        }
        
        .info-row:last-child {
            border-bottom: none;
        }
        
        .info-label {
            width: 110px;
            font-weight: 600;
            color: #475569;
            font-size: 13px;
        }
        
        .info-value {
            flex: 1;
            color: #1e293b;
            font-size: 13px;
            word-break: break-word;
        }
        
        .info-value a {
            color: #3b82f6;
            text-decoration: none;
        }
        
        .info-value a:hover {
            text-decoration: underline;
        }
        
        /* Message box */
        .message-box {
            background: #f8fafc;
            border-left: 4px solid #3b82f6;
            padding: 20px;
            border-radius: 12px;
            margin: 20px;
        }
        
        .message-box p {
            margin: 0;
            font-size: 14px;
            line-height: 1.6;
            color: #334155;
            white-space: pre-wrap;
        }
        
        /* Stats section */
        .stats-grid {
            display: flex;
            gap: 15px;
            margin: 20px;
            flex-wrap: wrap;
        }
        
        .stat-card {
            flex: 1;
            background: linear-gradient(135deg, #f8fafc 0%, #ffffff 100%);
            border-radius: 12px;
            padding: 15px;
            text-align: center;
            border: 1px solid #e2e8f0;
        }
        
        .stat-value {
            font-size: 24px;
            font-weight: 800;
            color: #1e3a8a;
        }
        
        .stat-label {
            font-size: 11px;
            color: #64748b;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            margin-top: 5px;
        }
        
        /* Action buttons */
        .action-buttons {
            margin: 30px 0 20px;
        }
        
        .btn {
            display: inline-block;
            padding: 12px 24px;
            border-radius: 40px;
            text-decoration: none;
            font-weight: 600;
            font-size: 14px;
            transition: all 0.2s ease;
            margin-right: 12px;
            margin-bottom: 12px;
        }
        
        .btn-primary {
            background: linear-gradient(135deg, #3b82f6, #1e3a8a);
            color: white;
            box-shadow: 0 4px 12px rgba(59, 130, 246, 0.3);
        }
        
        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 16px rgba(59, 130, 246, 0.4);
        }
        
        .btn-outline {
            background: transparent;
            color: #3b82f6;
            border: 2px solid #3b82f6;
        }
        
        .btn-outline:hover {
            background: #eff6ff;
        }
        
        /* Footer */
        .email-footer {
            background: #f8fafc;
            padding: 25px 30px;
            text-align: center;
            border-top: 1px solid #e2e8f0;
        }
        
        .email-footer p {
            margin: 0 0 10px;
            font-size: 12px;
            color: #94a3b8;
        }
        
        .social-links {
            margin-top: 15px;
        }
        
        .social-links a {
            color: #94a3b8;
            text-decoration: none;
            margin: 0 8px;
            font-size: 18px;
        }
        
        .social-links a:hover {
            color: #3b82f6;
        }
        
        hr {
            border: none;
            border-top: 1px solid #e2e8f0;
            margin: 20px 0;
        }
        
        @media (max-width: 600px) {
            .email-container {
                margin: 20px;
                border-radius: 16px;
            }
            
            .email-header {
                padding: 30px 20px;
            }
            
            .email-content {
                padding: 25px 20px;
            }
            
            .info-row {
                flex-direction: column;
            }
            
            .info-label {
                width: 100%;
                margin-bottom: 5px;
            }
            
            .stats-grid {
                flex-direction: column;
            }
            
            .btn {
                display: block;
                margin-right: 0;
                text-align: center;
            }
            
            .action-buttons {
                text-align: center;
            }
        }
    </style>
</head>
<body>
    <div class="email-container">
        {{-- Header --}}
        <div class="email-header">
            <div class="logo">
                Stardena <span>Works</span>
            </div>
            <h1>📬 New Contact Message</h1>
            <p>Action required - Please respond within 24 hours</p>
            <div class="priority-badge">
                <span>⚡</span> Priority: Medium
            </div>
        </div>
        
        {{-- Content --}}
        <div class="email-content">
            {{-- Welcome --}}
            <div class="welcome-message">
                <h2>👋 New inquiry received</h2>
                <p style="margin: 5px 0 0; color: #475569; font-size: 13px;">
                    A new contact form submission has been received from a user. Please review the details below and respond promptly.
                </p>
            </div>
            
            {{-- Contact Information --}}
            <div class="info-section">
                <div class="section-header">
                    <div class="icon">👤</div>
                    <h3>Contact Information</h3>
                </div>
                <div class="info-grid">
                    <div class="info-row">
                        <div class="info-label">Full Name</div>
                        <div class="info-value">{{ $data['name'] ?? 'N/A' }}</div>
                    </div>
                    <div class="info-row">
                        <div class="info-label">Email Address</div>
                        <div class="info-value">
                            <a href="mailto:{{ $data['email'] ?? 'N/A' }}">
                                {{ $data['email'] ?? 'N/A' }}
                                <span style="font-size: 12px;">→</span>
                            </a>
                        </div>
                    </div>
                    <div class="info-row">
                        <div class="info-label">Subject</div>
                        <div class="info-value">
                            <span style="background: #eff6ff; padding: 3px 10px; border-radius: 20px; font-size: 12px;">
                                {{ $data['subject'] ?? 'General Inquiry' }}
                            </span>
                        </div>
                    </div>
                    <div class="info-row">
                        <div class="info-label">Submitted At</div>
                        <div class="info-value">{{ now()->format('F j, Y \a\t g:i A') }}</div>
                    </div>
                </div>
            </div>
            
            {{-- Message Content --}}
            <div class="info-section">
                <div class="section-header">
                    <div class="icon">💬</div>
                    <h3>Message Content</h3>
                </div>
                <div class="message-box">
                    <p>{{ nl2br(e($data['message'] ?? 'No message provided')) }}</p>
                </div>
            </div>
        
            
            <hr>
            
            {{-- Tips --}}
            <div style="background: #fffbeb; border-radius: 12px; padding: 15px; border-left: 4px solid #f59e0b;">
                <div style="display: flex; gap: 10px; align-items: flex-start;">
                    <span style="font-size: 20px;">💡</span>
                    <div>
                        <strong style="color: #92400e; font-size: 13px;">Pro Tip</strong>
                        <p style="margin: 5px 0 0; font-size: 12px; color: #78350f;">
                            Responding within 24 hours increases customer satisfaction by 85%. Use the quick reply button above.
                        </p>
                    </div>
                </div>
            </div>
        </div>
        
        {{-- Footer --}}
        <div class="email-footer">
            <p>
                <strong>Stardena Works</strong><br>
                Connecting talent with opportunity
            </p>
            <p style="font-size: 11px;">
                This is an automated notification from Stardena Works.<br>
                © {{ date('Y') }} Stardena Works. All rights reserved.
            </p>
        </div>
    </div>
</body>
</html>