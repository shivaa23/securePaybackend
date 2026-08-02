<?php

namespace App\Mail;

use Illuminate\Mail\Mailable;

class SendEmail extends Mailable
{
    public $details;

    public function __construct($details)
    {
        $this->details = $details;
    }

    public function build()
    {
        return $this->from(config('mail.from.address'))
                    ->subject($this->details['subject'])
                    ->view('emails.sendEmail')
                    ->with('details', $this->details);
    }
}
