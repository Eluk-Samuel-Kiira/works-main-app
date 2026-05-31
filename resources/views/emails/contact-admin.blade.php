{{-- resources/views/emails/contact-admin.blade.php - MAIN APP --}}
<!DOCTYPE html>
<html lang="en" xmlns="http://www.w3.org/1999/xhtml">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <meta http-equiv="X-UA-Compatible" content="IE=edge" />
  <title>New Contact Message - Stardena Works</title>
  <style>
    body, table, td, a { -webkit-text-size-adjust: 100%; -ms-text-size-adjust: 100%; }
    table, td { mso-table-lspace: 0pt; mso-table-rspace: 0pt; }
    img { -ms-interpolation-mode: bicubic; border: 0; outline: none; text-decoration: none; }
    body { margin: 0 !important; padding: 0 !important; background-color: #f4f4f7; }
    a[x-apple-data-detectors] { color: inherit !important; text-decoration: none !important; }
    @media only screen and (max-width: 600px) {
      .email-wrapper { width: 100% !important; }
      .email-content { padding: 24px 20px !important; }
      .reply-btn { display: block !important; width: 100% !important; }
    }
  </style>
</head>
<body style="margin:0;padding:0;background-color:#f4f4f7;">

  {{-- ── Preheader (hidden preview text) ── --}}
  <div style="display:none;font-size:1px;color:#f4f4f7;line-height:1px;
              max-height:0;max-width:0;opacity:0;overflow:hidden;">
    New contact message from {{ $data['name'] ?? 'someone' }} — respond within 24 hours.
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
                style="background-color:#4F36E8;padding:24px 32px;">
              <span style="font-family:Georgia,'Times New Roman',serif;
                           font-size:20px;font-weight:bold;color:#ffffff;">
                Stardena <span style="color:rgba(255,255,255,0.75);">Works</span>
              </span>
            </td>
          </tr>

          {{-- ── Body ── --}}
          <tr>
            <td class="email-content" style="padding:32px 40px;">

              {{-- Greeting --}}
              <p style="font-family:-apple-system,BlinkMacSystemFont,'Segoe UI',Helvetica,Arial,sans-serif;
                         font-size:22px;font-weight:700;color:#1a1a2e;margin:0 0 8px;
                         line-height:1.3;letter-spacing:-0.3px;">
                📬 New contact message
              </p>

              {{-- Subheading --}}
              <p style="font-family:-apple-system,BlinkMacSystemFont,'Segoe UI',Helvetica,Arial,sans-serif;
                         font-size:15px;color:#6b7280;margin:0 0 24px;line-height:1.6;">
                {{ $data['name'] ?? 'Someone' }} has sent a message via the contact form.
                Reply within 24 hours.
              </p>

              {{-- ── Contact Info Card ── --}}
              <table role="presentation" border="0" cellpadding="0" cellspacing="0"
                     width="100%" style="margin-bottom:20px;">
                <tr>
                  <td style="background-color:#f9fafb;border-radius:12px;
                              padding:16px 20px;border:1px solid #e5e7eb;">
                    <p style="font-family:-apple-system,BlinkMacSystemFont,'Segoe UI',
                               Helvetica,Arial,sans-serif;font-size:13px;
                               font-weight:600;color:#374151;margin:0 0 12px;">
                      📋 Contact Details
                    </p>
                    <table width="100%" cellpadding="0" cellspacing="0">
                      <tr>
                        <td width="100" style="font-size:12px;color:#6b7280;padding:4px 0;">Name</td>
                        <td style="font-size:13px;color:#111827;padding:4px 0;">
                          {{ $data['name'] ?? 'N/A' }}
                        </td>
                      </tr>
                      <tr>
                        <td style="font-size:12px;color:#6b7280;padding:4px 0;">Email</td>
                        <td style="font-size:13px;color:#111827;padding:4px 0;">
                          <a href="mailto:{{ $data['email'] ?? 'N/A' }}"
                             style="color:#4F36E8;text-decoration:none;">
                            {{ $data['email'] ?? 'N/A' }}
                          </a>
                        </td>
                      </tr>
                      <tr>
                        <td style="font-size:12px;color:#6b7280;padding:4px 0;">Subject</td>
                        <td style="font-size:13px;color:#111827;padding:4px 0;">
                          <span style="background:#eef2ff;padding:2px 10px;border-radius:14px;font-size:12px;">
                            {{ $data['subject'] ?? 'General Inquiry' }}
                          </span>
                        </td>
                      </tr>
                      <tr>
                        <td style="font-size:12px;color:#6b7280;padding:4px 0;">Submitted</td>
                        <td style="font-size:13px;color:#111827;padding:4px 0;">
                          {{ now()->format('M j, Y g:i A') }}
                        </td>
                      </tr>
                    </table>
                  </td>
                </tr>
              </table>

              {{-- ── Message Card ── --}}
              <table role="presentation" border="0" cellpadding="0" cellspacing="0"
                     width="100%" style="margin-bottom:24px;">
                <tr>
                  <td style="background-color:#f9fafb;border-radius:12px;
                              padding:16px 20px;border:1px solid #e5e7eb;">
                    <p style="font-family:-apple-system,BlinkMacSystemFont,'Segoe UI',
                               Helvetica,Arial,sans-serif;font-size:13px;
                               font-weight:600;color:#374151;margin:0 0 12px;">
                      💬 Message
                    </p>
                    <div style="background-color:#ffffff;border-left:3px solid #4F36E8;
                                padding:14px;border-radius:8px;">
                      <p style="font-family:-apple-system,BlinkMacSystemFont,'Segoe UI',
                                 Helvetica,Arial,sans-serif;
                                 font-size:13px;color:#374151;margin:0;
                                 line-height:1.6;white-space:pre-wrap;">
                        {{ nl2br(e($data['message'] ?? 'No message provided')) }}
                      </p>
                    </div>
                  </td>
                </tr>
              </table>

              {{-- ── Reply Button ── --}}
              <table role="presentation" border="0" cellpadding="0" cellspacing="0"
                     width="100%" style="margin-bottom:20px;">
                <tr>
                  <td align="center">
                    <table role="presentation" border="0" cellpadding="0" cellspacing="0">
                      <tr>
                        <td style="border-radius:12px;background-color:#4F36E8;">
                          <a href="mailto:{{ $data['email'] ?? '' }}?subject=Re: {{ urlencode($data['subject'] ?? 'Your Inquiry') }}"
                             class="reply-btn"
                             style="display:inline-block;font-family:-apple-system,BlinkMacSystemFont,
                                    'Segoe UI',Helvetica,Arial,sans-serif;
                                    font-size:14px;font-weight:600;color:#ffffff;
                                    text-decoration:none;padding:12px 28px;
                                    border-radius:12px;background-color:#4F36E8;">
                            ✉️ Reply to {{ $data['name'] ?? 'Sender' }}
                          </a>
                        </td>
                      </tr>
                    </table>
                  </td>
                </tr>
              </table>

              {{-- ── Tips note ── --}}
              <table role="presentation" border="0" cellpadding="0" cellspacing="0"
                     width="100%" style="margin-bottom:24px;">
                <tr>
                  <td style="background-color:#fffbeb;border-radius:10px;
                              padding:12px 16px;border:1px solid #fde68a;">
                    <p style="font-family:-apple-system,BlinkMacSystemFont,'Segoe UI',
                               Helvetica,Arial,sans-serif;
                               font-size:12px;color:#92400e;margin:0;line-height:1.5;">
                      💡 <strong>Pro tip:</strong> Responding within 24 hours
                      increases customer satisfaction by 85%.
                    </p>
                  </td>
                </tr>
              </table>

              {{-- ── Fallback email link ── --}}
              <p style="font-family:-apple-system,BlinkMacSystemFont,'Segoe UI',Helvetica,Arial,sans-serif;
                         font-size:12px;color:#9ca3af;margin:0 0 6px;line-height:1.5;">
                Or copy this email address:
              </p>
              <p style="font-family:'Courier New',Courier,monospace;
                         font-size:12px;color:#4F36E8;margin:0;
                         word-break:break-all;line-height:1.6;">
                {{ $data['email'] ?? 'N/A' }}
              </p>

            </td>
          </tr>

          {{-- ── Divider ── --}}
          <tr>
            <td style="padding:0 40px;">
              <table role="presentation" border="0" cellpadding="0" cellspacing="0" width="100%">
                <tr>
                  <td style="border-top:1px solid #e5e7eb;padding:0;font-size:0;">&nbsp;</td>
                </tr>
              </table>
            </td>
          </tr>

          {{-- ── Footer ── --}}
          <tr>
            <td style="padding:24px 40px 32px;">
              <p style="font-family:-apple-system,BlinkMacSystemFont,'Segoe UI',Helvetica,Arial,sans-serif;
                         font-size:12px;color:#9ca3af;margin:0 0 6px;line-height:1.6;">
                This is an automated notification from Stardena Works.
                You're receiving this because someone submitted a contact form.
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