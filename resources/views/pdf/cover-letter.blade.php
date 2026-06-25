<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Cover Letter</title>
    <style>
        @page {
            margin: 90px 40px 70px 40px;
        }

        body {
            font-family: 'DejaVu Sans', Arial, sans-serif;
            font-size: 12px;
            line-height: 1.8;
            color: #1a202c;
        }

        .letter-content {
            font-family: Georgia, serif;
            font-size: 13px;
            line-height: 1.8;
            white-space: pre-wrap;
        }

        .pdf-footer {
            position: fixed;
            bottom: -50px;
            left: 0;
            right: 0;
            height: 50px;
            text-align: center;
            border-top: 1px solid #e5e7eb;
            padding-top: 10px;
        }

        .pdf-footer .brand {
            font-size: 13px;
            font-weight: 700;
            letter-spacing: -0.3px;
        }

        .pdf-footer .brand .stardena {
            color: #1e293b;
        }

        .pdf-footer .brand .works {
            color: #7c3aed;
        }

        .pdf-footer .tagline {
            font-size: 9px;
            color: #94a3b8;
            margin-top: 2px;
        }
    </style>
</head>
<body>
    <div class="pdf-footer">
        <div class="brand">
            <span class="stardena">Stardena</span> <span class="works">Works</span>
        </div>
        <div class="tagline">Generated via stardenaworks.com</div>
    </div>

    <div class="letter-content">
        {!! nl2br(e($letterContent)) !!}
    </div>
</body>
</html>