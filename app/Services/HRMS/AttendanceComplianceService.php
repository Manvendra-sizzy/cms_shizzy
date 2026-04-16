<?php

namespace App\Services\HRMS;

use App\Models\AttendanceDay;
use App\Models\EmployeeAttendanceReminder;
use App\Modules\HRMS\Leaves\Models\LeaveRequest;
use App\Modules\HRMS\Employees\Models\EmployeeProfile;
use Carbon\Carbon;

class AttendanceComplianceService
{
    private const MIN_PREMISE_MINUTES = 540; // 9 hours (8h work + 1h lunch/misc)
    private const MAX_STREAK_LOOKBACK_DAYS = 45;
    private const TYPE_INSUFFICIENT_WARNING_1 = 'insufficient_warning_1';
    private const TYPE_INSUFFICIENT_WARNING_2 = 'insufficient_warning_2';
    private const TYPE_ABSENT_WARNING_1 = 'absent_warning_1';
    private const TYPE_ABSENT_WARNING_2 = 'absent_warning_2';

    public function __construct(
        private readonly CalendarService $calendarService
    ) {
    }

    public function processForDate(?Carbon $referenceDate = null): array
    {
        $referenceDate = ($referenceDate ?: now('Asia/Kolkata'))->copy()->startOfDay();
        $previousDay = $referenceDate->copy()->subDay()->startOfDay();

        $warningsSent = 0;
        $newLocks = 0;

        $employees = EmployeeProfile::query()
            ->whereNotIn('status', ['inactive', 'former'])
            ->get();

        foreach ($employees as $employee) {
            if ($employee->attendance_locked_at) {
                continue;
            }

            $absentStreak = $this->consecutiveStreak($employee->id, $previousDay, 'absent');
            $insufficientStreak = $this->consecutiveStreak($employee->id, $previousDay, 'insufficient');

            if ($absentStreak >= 3) {
                $employee->update([
                    'attendance_locked_at' => now(),
                    'attendance_lock_reason' => 'Auto-locked: absent for 3 consecutive working days without approved leave.',
                ]);
                $newLocks++;
                continue;
            }

            if ($insufficientStreak >= 3) {
                $employee->update([
                    'attendance_locked_at' => now(),
                    'attendance_lock_reason' => 'Auto-locked: insufficient working hours for 3 consecutive working days.',
                ]);
                $newLocks++;
                continue;
            }

            if ($insufficientStreak === 1) {
                $warningsSent += $this->createReminderOnce($employee->id, $previousDay, self::TYPE_INSUFFICIENT_WARNING_1) ? 1 : 0;
            } elseif ($insufficientStreak === 2) {
                $warningsSent += $this->createReminderOnce($employee->id, $previousDay, self::TYPE_INSUFFICIENT_WARNING_2) ? 1 : 0;
            }

            if ($absentStreak === 1) {
                $warningsSent += $this->createReminderOnce($employee->id, $previousDay, self::TYPE_ABSENT_WARNING_1) ? 1 : 0;
            } elseif ($absentStreak === 2) {
                $warningsSent += $this->createReminderOnce($employee->id, $previousDay, self::TYPE_ABSENT_WARNING_2) ? 1 : 0;
            }
        }

        return [
            'warnings_sent' => $warningsSent,
            'new_locks' => $newLocks,
            'work_date' => $previousDay->toDateString(),
        ];
    }

    private function consecutiveStreak(int $employeeProfileId, Carbon $endDate, string $targetStatus): int
    {
        $streak = 0;
        $cursor = $endDate->copy();
        $looked = 0;

        while ($looked < self::MAX_STREAK_LOOKBACK_DAYS) {
            if (! $this->calendarService->isWorkingDay($cursor)) {
                $cursor->subDay();
                $looked++;
                continue;
            }

            $status = $this->dayStatus($employeeProfileId, $cursor);
            if ($status !== $targetStatus) {
                break;
            }

            $streak++;
            $cursor->subDay();
            $looked++;
        }

        return $streak;
    }

    private function dayStatus(int $employeeProfileId, Carbon $date): string
    {
        if ($this->hasApprovedLeave($employeeProfileId, $date)) {
            return 'leave';
        }

        $row = AttendanceDay::query()
            ->where('employee_profile_id', $employeeProfileId)
            ->whereDate('work_date', $date->toDateString())
            ->first();

        if (! $row || ! $row->punch_in_at) {
            return 'absent';
        }

        if (! $row->punch_out_at) {
            return 'insufficient';
        }

        $minutes = (int) $row->punch_in_at->diffInMinutes($row->punch_out_at);
        return $minutes >= self::MIN_PREMISE_MINUTES ? 'present' : 'insufficient';
    }

    private function hasApprovedLeave(int $employeeProfileId, Carbon $date): bool
    {
        return LeaveRequest::query()
            ->where('employee_profile_id', $employeeProfileId)
            ->where('status', 'approved')
            ->whereDate('start_date', '<=', $date->toDateString())
            ->whereDate('end_date', '>=', $date->toDateString())
            ->exists();
    }

    private function createReminderOnce(int $employeeProfileId, Carbon $workDate, string $type): bool
    {
        $exists = EmployeeAttendanceReminder::query()
            ->where('employee_profile_id', $employeeProfileId)
            ->whereDate('work_date', $workDate->toDateString())
            ->where('type', $type)
            ->exists();

        if ($exists) {
            return false;
        }

        EmployeeAttendanceReminder::query()->create([
            'employee_profile_id' => $employeeProfileId,
            'work_date' => $workDate->toDateString(),
            'type' => $type,
            'sent_at' => now(),
        ]);

        return true;
    }
}

