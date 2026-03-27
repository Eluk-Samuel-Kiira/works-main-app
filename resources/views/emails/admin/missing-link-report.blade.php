{{-- resources/views/emails/admin/missing-link-report.blade.php --}}
<!DOCTYPE html>
<html>
<head>
    <title>Missing Application Link Report</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            line-height: 1.6;
            color: #333;
        }
        .container {
            max-width: 600px;
            margin: 0 auto;
            padding: 20px;
        }
        .header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            padding: 20px;
            text-align: center;
            border-radius: 8px 8px 0 0;
            color: white;
        }
        .content {
            padding: 30px;
            background: #fff;
            border: 1px solid #e0e0e0;
            border-top: none;
            border-radius: 0 0 8px 8px;
        }
        .details {
            background: #f8f9fa;
            padding: 20px;
            border-radius: 8px;
            margin: 20px 0;
        }
        .detail-item {
            margin-bottom: 12px;
            padding-bottom: 8px;
            border-bottom: 1px solid #e0e0e0;
        }
        .detail-label {
            font-weight: bold;
            color: #495057;
            display: inline-block;
            width: 120px;
        }
        .detail-value {
            color: #212529;
        }
        .button {
            display: inline-block;
            padding: 12px 24px;
            background: #007bff;
            color: #fff;
            text-decoration: none;
            border-radius: 5px;
            margin-top: 20px;
            margin-right: 10px;
        }
        .button:hover {
            background: #0056b3;
        }
        .button-danger {
            background: #dc3545;
        }
        .button-danger:hover {
            background: #c82333;
        }
        .footer {
            text-align: center;
            padding: 20px;
            font-size: 12px;
            color: #6c757d;
            border-top: 1px solid #dee2e6;
            margin-top: 20px;
        }
        .badge {
            display: inline-block;
            padding: 3px 8px;
            background: #ffc107;
            color: #212529;
            border-radius: 4px;
            font-size: 12px;
            font-weight: bold;
            margin-left: 8px;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h2>⚠️ Missing Application Link Report</h2>
            <p>Action Required - Immediate Attention Needed</p>
        </div>
        
        <div class="content">
            <p><strong>Dear Admin,</strong></p>
            <p>A user has reported that a job posting is missing an application link. Please review and update the job posting as soon as possible.</p>
            
            <div class="details">
                <div class="detail-item">
                    <span class="detail-label">Job Title:</span>
                    <span class="detail-value"><strong>{{ $jobTitle }}</strong></span>
                    @if(!$job)
                        <span class="badge">Job Not Found in DB</span>
                    @endif
                </div>
                <div class="detail-item">
                    <span class="detail-label">Company:</span>
                    <span class="detail-value">{{ $companyName ?? 'Not specified' }}</span>
                </div>
                <div class="detail-item">
                    <span class="detail-label">Job ID:</span>
                    <span class="detail-value">{{ $jobId }}</span>
                </div>
                <div class="detail-item">
                    <span class="detail-label">Job URL:</span>
                    <span class="detail-value">
                        <a href="{{ $url }}" target="_blank">{{ $url }}</a>
                    </span>
                </div>
                <div class="detail-item">
                    <span class="detail-label">Reported By:</span>
                    <span class="detail-value">{{ $reportedBy }}</span>
                </div>
                <div class="detail-item">
                    <span class="detail-label">Reported At:</span>
                    <span class="detail-value">{{ $reportedAt }}</span>
                </div>
                <div class="detail-item">
                    <span class="detail-label">User Agent:</span>
                    <span class="detail-value"><small>{{ $userAgent }}</small></span>
                </div>
            </div>
            
            <p><strong>Action Required:</strong></p>
            <ul>
                <li>✅ Review the job posting to verify if application details are missing</li>
                <li>📞 Contact the poster to request the correct application link</li>
                <li>✏️ Update the job posting with the correct application method</li>
                <li>🔔 Mark this notification as resolved in the admin panel</li>
            </ul>
            
            <div style="text-align: center; margin-top: 30px;">
                <a href="{{ $url }}" class="button button-danger" target="_blank">🔍 View Job Posting</a>
            </div>
        </div>
        
        <div class="footer">
            <p>This is an automated notification from Stardena Works.</p>
            <p>Please do not reply to this email. To manage notifications, visit your admin dashboard.</p>
            <p><small>Report Reference: #{{ $jobId }}_{{ date('YmdHis') }}</small></p>
        </div>
    </div>
</body>
</html>