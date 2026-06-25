<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>CV</title>
    <style>
        @page {
            margin: 90px 40px 70px 40px;
        }

        body {
            font-family: 'DejaVu Sans', Arial, sans-serif;
            font-size: 12px;
            line-height: 1.5;
            color: #1a202c;
        }

        strong {
            color: #1e3a8a;
            font-weight: 700;
        }

        ul {
            margin: 8px 0;
            padding-left: 20px;
        }

        li {
            margin: 4px 0;
        }

        a {
            color: #2563eb;
            text-decoration: none;
        }

        /* dompdf renders header/footer via these fixed-position blocks
           repeated on every page when placed outside normal flow */
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
    </div>

    {!! $bodyHtml !!}
</body>
</html>