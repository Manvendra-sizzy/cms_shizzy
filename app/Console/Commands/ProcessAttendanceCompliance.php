<?php

namespace App\Console\Commands;

use App\Services\HRMS\AttendanceComplianceService;
use Carbon\Carbon;
use Illuminate\Console\Command;

class ProcessAttendanceCompliance extends Command
{
    protected $signature = 'hrms:attendance:compliance {--date= : Reference date in Y-m-d (default: today IST)}';

    protected $description = 'Apply attendance warnings and consecutive-day lock rules.';

    public function handle(AttendanceComplianceService $service): int
    {
        $dateOpt = $this->option('date');
        $referenceDate = $dateOpt ? Carbon::parse((string) $dateOpt, 'Asia/Kolkata')->startOfDay() : now('Asia/Kolkata')->startOfDay();

        $result = $service->processForDate($referenceDate);

        $this->info('Attendance compliance processed.');
        $this->line('Work date checked: ' . $result['work_date']);
        $this->line('Warnings sent: ' . $result['warnings_sent']);
        $this->line('New locks: ' . $result['new_locks']);

        return self::SUCCESS;
    }
}

