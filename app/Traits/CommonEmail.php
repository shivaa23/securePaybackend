<?php

namespace App\Traits;

use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Log;

class CommonEmail
{

public static function sendPlainEmail($toEmail, $toName, $subject, $messageBody)
{
    try {
        Mail::send('emails.plain_email', [
            'subject' => $subject,
            'messageBody' => $messageBody
        ], function ($message) use ($toEmail, $toName, $subject) {
            $message->to($toEmail, $toName)
                    ->subject($subject)
                    ->from(env('MAIL_FROM_ADDRESS', 'noreply@example.com'), env('MAIL_FROM_NAME', 'Your App Name'));
        });

        return true;
    } catch (\Exception $e) {
        Log::error("Failed to send email to $toEmail: " . $e->getMessage());
        return false;
    }
}

}