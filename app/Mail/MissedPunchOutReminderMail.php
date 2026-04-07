<?php

namespace App\Mail;

use App\Modules\HRMS\Employees\Models\EmployeeProfile;
use Carbon\Carbon;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class MissedPunchOutReminderMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(public EmployeeProfile $employeeProfile, public Carbon $workDate)
    {
    }

    public function build(): self
    {
        $name = $this->employeeProfile->user?->name ?? 'Employee';

        return $this
            ->subject('Reminder: Missing punch-out entry')
            ->view('emails.cms.missed_punch_out_reminder', [
                'name' => $name,
                'workDate' => $this->workDate,
            ]);
    }
}

