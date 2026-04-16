<?php

namespace App\Mail;

use App\Modules\HRMS\Onboarding\Models\EmployeeOnboarding;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class OnboardingInvitationMail extends Mailable
{
    use Queueable;
    use SerializesModels;

    public function __construct(
        public EmployeeOnboarding $onboarding,
        public string $token
    ) {
    }

    public function build(): self
    {
        return $this->subject('Complete Your Onboarding - Shizzy')
            ->view('emails.cms.onboarding_invitation')
            ->with([
                'onboarding' => $this->onboarding,
                'onboardingUrl' => route('onboarding.show', ['token' => $this->token]),
            ]);
    }
}

