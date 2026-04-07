<?php

namespace App\Mail;

use App\Modules\HRMS\Leaves\Models\LeaveRequest;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class LeaveAppliedToAdminMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(public LeaveRequest $leaveRequest)
    {
    }

    public function build(): self
    {
        $policyName = $this->leaveRequest->policy?->name ?? 'Leave';
        return $this
            ->subject("Leave applied: {$policyName}")
            ->view('emails.cms.leave_applied_admin', [
                'leaveRequest' => $this->leaveRequest,
            ]);
    }
}

