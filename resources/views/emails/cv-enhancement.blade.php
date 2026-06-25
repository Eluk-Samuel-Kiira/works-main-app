{{-- resources/views/emails/cv-enhancement.blade.php --}}

@php
    // Helper function to convert markdown to HTML
    function markdownToHtml($text)
    {
        if (!$text) return '';
        
        // Convert **bold** to <strong>
        $text = preg_replace('/\*\*(.*?)\*\*/', '<strong>$1</strong>', $text);
        
        // Convert *italic* to <em>
        $text = preg_replace('/\*(.*?)\*/', '<em>$1</em>', $text);
        
        // Convert bullet points with • to list items
        $text = preg_replace('/• (.*?)(\n|$)/', '<li>$1</li>', $text);
        
        // Convert numbered lists
        $text = preg_replace('/\d+\.\s+(.*?)(\n|$)/', '<li>$1</li>', $text);
        
        // Wrap consecutive list items in <ul>
        if (strpos($text, '<li>') !== false) {
            $text = '<ul style="margin: 0; padding-left: 20px;">' . $text . '</ul>';
        }
        
        // Convert newlines to <br> for plain text sections
        $text = nl2br($text);
        
        return $text;
    }
@endphp

<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>{{ $subject ?? 'Your CV Enhancement Results' }}</title>
    <style>
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif;
            line-height: 1.5;
            color: #1a202c;
            background-color: #f7fafc;
            margin: 0;
            padding: 0;
        }
        .container { max-width: 650px; margin: 0 auto; padding: 20px; }
        .card { background: white; border-radius: 8px; box-shadow: 0 1px 3px rgba(0,0,0,0.1); overflow: hidden; }
        .header { background: #1e3a8a; color: white; padding: 24px 20px; text-align: center; }
        .header h1 { margin: 0; font-size: 22px; font-weight: 600; }
        .header p { margin: 6px 0 0; opacity: 0.85; font-size: 13px; }
        .content { padding: 24px; }
        .greeting { font-size: 14px; margin-bottom: 20px; color: #2d3748; }
        .score-card { background: #f0f4f8; border-radius: 8px; padding: 16px; text-align: center; margin-bottom: 20px; }
        .score-number { font-size: 36px; font-weight: 700; color: #1e3a8a; }
        .score-label { color: #4a5568; font-size: 13px; margin-top: 4px; }
        .section { margin-bottom: 20px; }
        .section-title { font-weight: 600; font-size: 16px; color: #1e3a8a; margin-bottom: 12px; border-bottom: 1px solid #e2e8f0; padding-bottom: 6px; }
        .cv-content {
            background: #f8fafc;
            border: 1px solid #e2e8f0;
            border-radius: 8px;
            padding: 20px;
            font-family: 'Courier New', Courier, monospace;
            font-size: 12px;
            line-height: 1.5;
            white-space: pre-wrap;
            word-wrap: break-word;
            max-height: 600px;
            overflow-y: auto;
        }
        .cv-content strong { color: #1e3a8a; font-weight: 700; }
        .cv-content ul { margin: 8px 0; padding-left: 20px; }
        .cv-content li { margin: 4px 0; }
        .attachment-box { background: #f0fdf4; border-radius: 8px; padding: 12px; margin: 20px 0; text-align: center; font-size: 13px; }
        .button { display: inline-block; background: #1e3a8a; color: white; text-decoration: none; padding: 10px 20px; border-radius: 30px; font-weight: 500; font-size: 13px; margin-top: 12px; }
        .footer { background: #f8fafc; padding: 16px; text-align: center; font-size: 11px; color: #64748b; border-top: 1px solid #e2e8f0; }
        hr { border: none; border-top: 1px solid #e2e8f0; margin: 16px 0; }
        .pro-tip { margin-top: 20px; padding: 12px; background: #fffbeb; border-radius: 8px; text-align: center; font-size: 12px; color: #92400e; }
        @media (max-width: 600px) { .content { padding: 16px; } .score-number { font-size: 28px; } }
    </style>
</head>
<body>
    <div class="container">
        <div class="card">
            <div class="header">
                <h1>Stardena Works</h1>
                <p>{{ $subject ?? 'Your CV Enhancement Results' }}</p>
            </div>
            
            <div class="content">
                <div class="greeting">
                    <p>Dear <strong>{{ $user['first_name'] ?? 'Valued User' }}</strong>,</p>
                    <p>Thank you for using Stardena Works. 
                        @if($type === 'review')
                            We have completed a comprehensive review of your CV.
                        @elseif($type === 'rewrite')
                            Your professionally rewritten CV is ready.
                        @else
                            Your custom cover letter has been generated.
                        @endif
                    </p>
                </div>
                
                @if($type === 'review')
                    <div class="score-card">
                        <div class="score-number">{{ $atsScore ?? 0 }}%</div>
                        <div class="score-label">ATS Compatibility Score</div>
                        @if(($atsScore ?? 0) >= 75)
                            <div class="score-message" style="color:#16a34a;">✓ Good — Your CV will pass most ATS systems</div>
                        @elseif(($atsScore ?? 0) >= 50)
                            <div class="score-message" style="color:#d97706;">⚠️ Fair — Some improvements needed</div>
                        @else
                            <div class="score-message" style="color:#dc2626;">❌ Poor — Significant improvements needed</div>
                        @endif
                    </div>
                    
                    @if(!empty($strengths))
                    <div class="section">
                        <div class="section-title">✓ Strengths</div>
                        @foreach($strengths as $strength)
                            <div style="background:#f0fdf4; padding:10px 12px; margin-bottom:6px; border-radius:6px; border-left:3px solid #22c55e;">{!! markdownToHtml($strength) !!}</div>
                        @endforeach
                    </div>
                    @endif
                    
                @elseif($type === 'rewrite')
                    <div class="score-card">
                        <div style="font-weight:600; margin-bottom:4px;">✨ Your Professional CV is Ready</div>
                        <div style="font-size:13px; color:#4a5568;">Rewritten to professional East African standards</div>
                    </div>
                    
                    <div class="section">
                        <div class="section-title">Your Rewritten CV</div>
                        <div class="cv-content">{!! markdownToHtml($content ?? 'No content available.') !!}</div>
                    </div>
                    
                @elseif($type === 'cover_letter')
                    <div class="score-card">
                        <div class="score-number">{{ $matchScore ?? 0 }}%</div>
                        <div class="score-label">CV to Job Match</div>
                        @if(($matchScore ?? 0) >= 70)
                            <div class="score-message" style="color:#16a34a;">✓ Strong match for this role</div>
                        @elseif(($matchScore ?? 0) >= 50)
                            <div class="score-message" style="color:#d97706;">⚠️ Moderate match</div>
                        @else
                            <div class="score-message" style="color:#dc2626;">❌ Weak match</div>
                        @endif
                    </div>
                    
                    <div class="section">
                        <div class="section-title">Your Cover Letter</div>
                        <div class="cv-content" style="font-family: Georgia, serif; font-size: 13px;">{!! nl2br(e($content ?? 'No cover letter generated.')) !!}</div>
                    </div>
                @endif
                
                <div class="attachment-box">
                    📎 A PDF version is attached to this email.
                    <br>
                    <span style="font-size: 11px; color: #64748b;">
                        @if($type === 'rewrite')
                            (Your professionally rewritten CV in PDF format)
                        @elseif($type === 'cover_letter')
                            (Your custom cover letter in PDF format)
                        @else
                            (Your CV review report in PDF format)
                        @endif
                    </span>
                </div>
                
                <hr>
                
                <div style="text-align: center;">
                    <a href="{{ env('WEB_APP_URL', 'https://stardenaworks.com') }}/dashboard" class="button">Go to Dashboard</a>
                </div>
                
                <div class="pro-tip">
                    💡 <strong>Pro Tip:</strong> 
                    @if($type === 'review')
                        Use this feedback to improve your CV before applying.
                    @elseif($type === 'rewrite')
                        Use your new CV when applying to increase interview chances.
                    @else
                        Personalize this letter for each application.
                    @endif
                </div>
            </div>
            
            <div class="footer">
                <p>&copy; {{ date('Y') }} Stardena Works. All rights reserved.</p>
                <p>Uganda · Kenya · Tanzania · Rwanda</p>
            </div>
        </div>
    </div>
</body>
</html>