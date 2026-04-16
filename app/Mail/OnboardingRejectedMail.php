<?php

namespace App\Mail;

use App\Modules\HRMS\Onboarding\Models\EmployeeOnboarding;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class OnboardingRejectedMail extends Mailable
{
    use Queueable;
    use SerializesModels;

    public function __construct(
        public EmployeeOnboarding $onboarding,
        public string $reason
    ) {
    }

    public function build(): self
    {
        return $this->subject('Onboarding update — Shizzy')
            ->view('emails.cms.onboarding_rejected');
    }
}
