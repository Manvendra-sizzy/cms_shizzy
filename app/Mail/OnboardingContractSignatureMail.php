<?php

namespace App\Mail;

use App\Modules\HRMS\Onboarding\Models\EmployeeOnboarding;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class OnboardingContractSignatureMail extends Mailable
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
        return $this->subject('Action Required: Sign Your Employment Contract - Shizzy CMS')
            ->view('emails.cms.onboarding_contract_signature')
            ->with([
                'onboarding' => $this->onboarding,
                'contractUrl' => route('onboarding.contract.show', ['token' => $this->token]),
            ]);
    }
}
