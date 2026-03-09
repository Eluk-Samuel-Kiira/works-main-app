<?php

namespace App\Mail\Auth;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class MagicLoginLink extends Mailable
{
    use Queueable, SerializesModels;

    public $token;
    public $isWelcomeEmail;

    public function __construct($token, $isWelcomeEmail = false)
    {
        $this->token = $token;
        $this->isWelcomeEmail = $isWelcomeEmail;
    }

    public function build()
    {
        $subject = $this->isWelcomeEmail 
            ? __('Welcome to Stardena Works - Your Magic Login Link')
            : __('Your Stardena Works Magic Login Link');

        return $this->subject($subject)
                    ->markdown('emails.auth.magic-login-link')
                    ->with([
                        'token' => $this->token,
                        'isWelcomeEmail' => $this->isWelcomeEmail,
                        'loginUrl' => route('auth.authenticate', $this->token),
                        'expiresAt' => now()->addHours(24)->format('F j, Y \a\t g:i A'),
                    ]);
    }
}