<?php

namespace App\Mail;

use App\Modules\HRMS\Reimbursements\Models\ReimbursementRequest;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class ReimbursementApprovedToEmployeeMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(public ReimbursementRequest $reimbursementRequest)
    {
    }

    public function build(): self
    {
        return $this
            ->subject('Your reimbursement request was approved')
            ->view('emails.cms.reimbursement_approved_employee', [
                'reimbursementRequest' => $this->reimbursementRequest,
            ]);
    }
}
