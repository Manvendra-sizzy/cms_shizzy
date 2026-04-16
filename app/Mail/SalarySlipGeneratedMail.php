<?php

namespace App\Mail;

use App\Modules\HRMS\Payroll\Models\SalarySlip;
use App\Modules\HRMS\Payroll\Models\PayrollRun;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class SalarySlipGeneratedMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(public SalarySlip $slip, public PayrollRun $run)
    {
    }

    public function build(): self
    {
        $name = $this->slip->employeeProfile?->user?->name ?? 'Employee';
        return $this
            ->subject("Salary credited: {$this->slip->slip_number}")
            ->view('emails.cms.salary_slip_generated', [
                'slip' => $this->slip,
                'run' => $this->run,
                'name' => $name,
            ]);
    }
}

