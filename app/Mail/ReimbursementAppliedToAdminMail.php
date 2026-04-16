<?php

namespace App\Mail;

use App\Modules\HRMS\Reimbursements\Models\ReimbursementRequest;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class ReimbursementAppliedToAdminMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(public ReimbursementRequest $reimbursementRequest)
    {
    }

    public function build(): self
    {
        $title = $this->reimbursementRequest->title;

        return $this
            ->subject("Reimbursement request: {$title}")
            ->view('emails.cms.reimbursement_applied_admin', [
                'reimbursementRequest' => $this->reimbursementRequest,
            ]);
    }
}
