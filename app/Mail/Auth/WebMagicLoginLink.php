<?php

namespace App\Mail\Auth;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

/**
 * Magic link email for job seekers / web-app users.
 *
 * The link points to works-web (WEB_APP_URL), NOT works-main.
 * Works-web verifies the token via the internal API and creates
 * its own session — the admin app is never touched.
 */
class WebMagicLoginLink extends Mailable
{
    use Queueable, SerializesModels;

    public string $url;
    public bool   $isNew;

    public function __construct(string $token, bool $isNew = false)
    {
        // Link goes to works-web, not works-main
        $webBase   = rtrim(config('api.web_app.url'), '/');
        $this->url = $webBase . '/auth/magic/' . $token;
        $this->isNew = $isNew;
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: $this->isNew
                ? 'Welcome to Stardena Works — sign in to get started'
                : 'Your Stardena Works magic link',
        );
    }

    public function content(): Content
    {
        return new Content(
            // reuse your existing magic link view — it only needs $url
            view: 'emails.auth.magic-login-link-api',
            with: [
                'url'   => $this->url,
                'isNew' => $this->isNew,
            ],
        );
    }
}