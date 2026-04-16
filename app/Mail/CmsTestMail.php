<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class CmsTestMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(public string $message = 'Test email from CMS')
    {
    }

    public function build(): self
    {
        return $this
            ->subject('CMS: Email test')
            ->view('emails.cms.test', [
                'cmsMessage' => $this->message,
            ]);
    }
}

