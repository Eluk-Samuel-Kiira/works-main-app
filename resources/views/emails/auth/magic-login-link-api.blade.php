<!DOCTYPE html>
<html lang="en" xmlns="http://www.w3.org/1999/xhtml">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <meta http-equiv="X-UA-Compatible" content="IE=edge" />
  <title>{{ $isNew ? 'Welcome to Stardena Works' : 'Your magic login link' }}</title>
  <!--[if mso]>
  <noscript>
    <xml><o:OfficeDocumentSettings><o:PixelsPerInch>96</o:PixelsPerInch></o:OfficeDocumentSettings></xml>
  </noscript>
  <![endif]-->
  <style>
    body, table, td, a { -webkit-text-size-adjust: 100%; -ms-text-size-adjust: 100%; }
    table, td { mso-table-lspace: 0pt; mso-table-rspace: 0pt; }
    img { -ms-interpolation-mode: bicubic; border: 0; outline: none; text-decoration: none; }
    body { margin: 0 !important; padding: 0 !important; background-color: #f4f4f7; }
    a[x-apple-data-detectors] { color: inherit !important; text-decoration: none !important; }
    @media only screen and (max-width: 600px) {
      .email-wrapper { width: 100% !important; }
      .email-content { padding: 24px 16px !important; }
      .btn-magic { display: block !important; }
    }
  </style>
</head>
<body style="margin:0;padding:0;background-color:#f4f4f7;">

  {{-- ── Preheader (hidden preview text in inbox) ── --}}
  <div style="display:none;font-size:1px;color:#f4f4f7;line-height:1px;
              max-height:0;max-width:0;opacity:0;overflow:hidden;">
    {{ $isNew
        ? 'Your Stardena Works account is ready — click to sign in instantly.'
        : 'Click your magic link to sign in — no password needed.' }}
    &nbsp;&zwnj;&nbsp;&zwnj;&nbsp;&zwnj;&nbsp;&zwnj;&nbsp;&zwnj;&nbsp;&zwnj;
  </div>

  {{-- ── Outer wrapper ── --}}
  <table role="presentation" border="0" cellpadding="0" cellspacing="0"
         width="100%" style="background-color:#f4f4f7;">
    <tr>
      <td align="center" style="padding:40px 16px;">

        {{-- ── Email card ── --}}
        <table role="presentation" class="email-wrapper" border="0" cellpadding="0"
               cellspacing="0" width="560"
               style="background-color:#ffffff;border-radius:16px;
                      border:1px solid #e5e7eb;overflow:hidden;">

          {{-- ── Header band ── --}}
          <tr>
            <td align="center"
                style="background-color:#4F36E8;padding:28px 40px;">
              <table role="presentation" border="0" cellpadding="0" cellspacing="0">
                <tr>
                  <td>
                    {{-- Logo mark --}}
                    <table role="presentation" border="0" cellpadding="0" cellspacing="0">
                      <tr>
                        <td style="background-color:rgba(255,255,255,0.15);
                                   border-radius:10px;padding:8px 10px;
                                   vertical-align:middle;">
                          <span style="font-size:18px;line-height:1;">💼</span>
                        </td>
                        <td style="padding-left:10px;vertical-align:middle;">
                          <span style="font-family:Georgia,'Times New Roman',serif;
                                       font-size:20px;font-weight:bold;color:#ffffff;
                                       letter-spacing:-0.3px;">
                            Stardena
                            <span style="color:rgba(255,255,255,0.75);font-weight:normal;">
                              Works
                            </span>
                          </span>
                        </td>
                      </tr>
                    </table>
                  </td>
                </tr>
              </table>
            </td>
          </tr>

          {{-- ── Body ── --}}
          <tr>
            <td class="email-content" style="padding:40px 48px;">

              {{-- Greeting --}}
              <p style="font-family:-apple-system,BlinkMacSystemFont,'Segoe UI',Helvetica,Arial,sans-serif;
                         font-size:26px;font-weight:700;color:#1a1a2e;margin:0 0 8px;
                         line-height:1.25;letter-spacing:-0.4px;">
                @if($isNew)
                  Welcome aboard! 🎉
                @else
                  Here's your magic link
                @endif
              </p>

              {{-- Subheading --}}
              <p style="font-family:-apple-system,BlinkMacSystemFont,'Segoe UI',Helvetica,Arial,sans-serif;
                         font-size:15px;color:#6b7280;margin:0 0 28px;line-height:1.6;">
                @if($isNew)
                  Your Stardena Works account is ready.
                  Click the button below to sign in — no password needed, now or ever.
                @else
                  Click the button below to sign in to your account instantly.
                  No password required.
                @endif
              </p>

              {{-- ── Magic link button ── --}}
              <table role="presentation" border="0" cellpadding="0" cellspacing="0"
                     width="100%" style="margin-bottom:28px;">
                <tr>
                  <td align="center">
                    <table role="presentation" border="0" cellpadding="0" cellspacing="0">
                      <tr>
                        <td style="border-radius:12px;background-color:#4F36E8;">
                          <a href="{{ $url }}"
                             class="btn-magic"
                             style="display:inline-block;font-family:-apple-system,BlinkMacSystemFont,
                                    'Segoe UI',Helvetica,Arial,sans-serif;
                                    font-size:15px;font-weight:600;color:#ffffff;
                                    text-decoration:none;padding:14px 36px;
                                    border-radius:12px;mso-padding-alt:0;
                                    background-color:#4F36E8;">
                            <!--[if mso]>&nbsp;<![endif]-->
                            {{ $isNew ? 'Activate my account →' : 'Sign in to Stardena Works →' }}
                            <!--[if mso]>&nbsp;<![endif]-->
                          </a>
                        </td>
                      </tr>
                    </table>
                  </td>
                </tr>
              </table>

              {{-- ── Expiry notice ── --}}
              <table role="presentation" border="0" cellpadding="0" cellspacing="0"
                     width="100%" style="margin-bottom:28px;">
                <tr>
                  <td style="background-color:#f9fafb;border-radius:10px;
                              padding:14px 18px;border:1px solid #e5e7eb;">
                    <p style="font-family:-apple-system,BlinkMacSystemFont,'Segoe UI',
                               Helvetica,Arial,sans-serif;
                               font-size:13px;color:#6b7280;margin:0;line-height:1.5;">
                      ⏱&nbsp; This link expires in <strong style="color:#374151;">24 hours</strong>
                      and can only be used once. If you didn't request this,
                      you can safely ignore this email.
                    </p>
                  </td>
                </tr>
              </table>

              {{-- ── Fallback URL ── --}}
              <p style="font-family:-apple-system,BlinkMacSystemFont,'Segoe UI',Helvetica,Arial,sans-serif;
                         font-size:13px;color:#9ca3af;margin:0 0 6px;line-height:1.5;">
                Button not working? Copy and paste this link into your browser:
              </p>
              <p style="font-family:'Courier New',Courier,monospace;
                         font-size:12px;color:#4F36E8;margin:0;
                         word-break:break-all;line-height:1.6;">
                {{ $url }}
              </p>

            </td>
          </tr>

          {{-- ── Divider ── --}}
          <tr>
            <td style="padding:0 48px;">
              <table role="presentation" border="0" cellpadding="0" cellspacing="0" width="100%">
                <tr>
                  <td style="border-top:1px solid #e5e7eb;padding:0;font-size:0;">&nbsp;</td>
                </tr>
              </table>
            </td>
          </tr>

          {{-- ── Footer ── --}}
          <tr>
            <td style="padding:24px 48px 32px;">
              <p style="font-family:-apple-system,BlinkMacSystemFont,'Segoe UI',Helvetica,Arial,sans-serif;
                         font-size:12px;color:#9ca3af;margin:0 0 6px;line-height:1.6;">
                You're receiving this because
                @if($isNew)
                  someone created a Stardena Works account with this email address.
                @else
                  a sign-in was requested for your Stardena Works account.
                @endif
              </p>
              <p style="font-family:-apple-system,BlinkMacSystemFont,'Segoe UI',Helvetica,Arial,sans-serif;
                         font-size:12px;color:#d1d5db;margin:0;line-height:1.6;">
                &copy; {{ date('Y') }} Stardena Works. All rights reserved.
              </p>
            </td>
          </tr>

        </table>
        {{-- /email card --}}

      </td>
    </tr>
  </table>

</body>
</html>