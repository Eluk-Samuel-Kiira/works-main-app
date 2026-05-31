<?php
// app/Mail/ContactNotification.php - MAIN APP

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class ContactNotification extends Mailable
{
    use Queueable, SerializesModels;

    public $data;
    public $type;

    public function __construct($data, $type = 'admin')
    {
        $this->data = $data;
        $this->type = $type;
    }

    public function build()
    {
        if ($this->type === 'admin') {
            return $this->subject('📧 New Contact Form Submission - ' . $this->data['name'])
                        ->markdown('emails.contact-admin')
                        ->with($this->data);
        }
        
        return $this->subject('Thank you for contacting ' . config('app.name'))
                    ->markdown('emails.contact-user')
                    ->with($this->data);
    }
}