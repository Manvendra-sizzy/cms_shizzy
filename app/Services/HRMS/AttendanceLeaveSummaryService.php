<?php

namespace App\Services\HRMS;

use App\Models\AttendanceDay;
use App\Modules\HRMS\Employees\Models\EmployeeProfile;
use App\Modules\HRMS\Leaves\Models\LeavePolicy;
use App\Modules\HRMS\Leaves\Models\LeaveRequest;
use Carbon\Carbon;
use Carbon\CarbonInterface;

class AttendanceLeaveSummaryService
{
    public function __construct(
        protected CalendarService $calendar
    ) {
    }

    /**
     * Map each working day in the leave request to a leave_policy_id and fraction (after approval distribution).
     *
     * @return array<string, array{leave_policy_id:int, fraction:float}> ymd => {leave_policy_id, fraction}
     */
    public function mapRequestToWorkingDays(LeaveRequest $req): array
    {
        $start = Carbon::parse($req->start_date)->startOfDay();
        $end = Carbon::parse($req->end_date)->startOfDay();
        $workingList = $this->calendar->workingDaysBetween($start, $end);

        $alloc = $req->approval_allocations;
        if (is_string($alloc)) {
            $alloc = json_decode($alloc, true) ?: [];
        }
        $out = [];

        // Special case: single-day half leave.
        if ($start->equalTo($end) && (float) $req->days === 0.5) {
            $ymd = $start->format('Y-m-d');
            $out[$ymd] = ['leave_policy_id' => (int) $req->leave_policy_id, 'fraction' => 0.5];
            return $out;
        }

        if (! empty($alloc) && is_array($alloc)) {
            $i = 0;
            foreach ($alloc as $row) {
                $pid = (int) ($row['leave_policy_id'] ?? 0);
                $days = (float) ($row['days'] ?? 0);

                // Full days
                $full = (int) floor($days + 1e-9);
                for ($k = 0; $k < $full && $i < count($workingList); $k++, $i++) {
                    $out[$workingList[$i]->format('Y-m-d')] = ['leave_policy_id' => $pid, 'fraction' => 1.0];
                }

                // Half day
                $remain = round($days - $full, 2);
                if ($remain >= 0.5 - 1e-9 && $i < count($workingList)) {
                    $out[$workingList[$i]->format('Y-m-d')] = ['leave_policy_id' => $pid, 'fraction' => 0.5];
                    $i++;
                }
            }

            return $out;
        }

        foreach ($workingList as $d) {
            $out[$d->format('Y-m-d')] = ['leave_policy_id' => (int) $req->leave_policy_id, 'fraction' => 1.0];
        }

        return $out;
    }

    /**
     * @return array<string, array{leave_policy_id:int, fraction:float}>
     */
    public function buildLeaveDayPolicyMap(int $employeeProfileId, CarbonInterface $rangeStart, CarbonInterface $rangeEnd): array
    {
        $requests = LeaveRequest::query()
            ->where('employee_profile_id', $employeeProfileId)
            ->where('status', 'approved')
            ->whereDate('start_date', '<=', $rangeEnd->toDateString())
            ->whereDate('end_date', '>=', $rangeStart->toDateString())
            ->orderBy('id')
            ->get();

        $map = [];
        foreach ($requests as $req) {
            foreach ($this->mapRequestToWorkingDays($req) as $ymd => $row) {
                if ($this->calendar->isWorkingDay(Carbon::parse($ymd))) {
                    $map[$ymd] = $row;
                }
            }
        }

        return $map;
    }

    /**
     * @return array{
     *   working_days:int,
     *   present_days:float,
     *   paid_leave_days:float,
     *   unpaid_leave_days:float,
     *   lop_days:float,
     *   leave_breakdown:array<string,float>
     * }
     */
    public function summarizePeriod(EmployeeProfile $employee, CarbonInterface $periodStart, CarbonInterface $periodEnd): array
    {
        $joiningRaw = $employee->joining_date ?? $employee->join_date;
        $joining = $joiningRaw
            ? Carbon::parse($joiningRaw)->startOfDay()
            : $periodStart->copy()->startOfDay();

        $start = $periodStart->copy()->startOfDay()->max($joining);
        $end = $periodEnd->copy()->startOfDay();
        if ($start->gt($end)) {
            return $this->emptySummary();
        }

        $working = $this->calendar->workingDaysBetween($start, $end);
        $workingYmd = array_map(static fn (Carbon $c) => $c->format('Y-m-d'), $working);

        $policies = LeavePolicy::query()->get()->keyBy('id');
        $leaveMap = $this->buildLeaveDayPolicyMap($employee->id, $start, $end);

        $attendance = AttendanceDay::query()
            ->where('employee_profile_id', $employee->id)
            ->whereBetween('work_date', [$start->toDateString(), $end->toDateString()])
            ->get()
            ->keyBy(fn ($a) => $a->work_date->format('Y-m-d'));

        $paidByCode = [];
        $unpaidLeaveDays = 0;
        $present = 0.0;
        $lop = 0.0;
        $paidLeaveDays = 0.0;

        foreach ($workingYmd as $ymd) {
            $remaining = 1.0;

            if (isset($leaveMap[$ymd])) {
                $pid = (int) ($leaveMap[$ymd]['leave_policy_id'] ?? 0);
                $fraction = (float) ($leaveMap[$ymd]['fraction'] ?? 1.0);
                $fraction = max(0.0, min(1.0, $fraction));
                $pol = $policies->get($pid);
                if ($pol && $pol->is_paid) {
                    $paidLeaveDays += $fraction;
                    $code = $pol->code;
                    $paidByCode[$code] = ($paidByCode[$code] ?? 0) + $fraction;
                } else {
                    $unpaidLeaveDays += $fraction;
                    $lop += $fraction;
                }
                $remaining = max(0.0, $remaining - $fraction);
            }

            $a = $attendance[$ymd] ?? null;
            if ($a && $a->punch_in_at && $remaining > 0) {
                $attFraction = (float) ($a->work_fraction ?? 1.0);
                $attFraction = max(0.0, min(1.0, $attFraction));
                $use = min($attFraction, $remaining);
                $present += $use;
                $remaining = max(0.0, $remaining - $use);
            }

            if ($remaining > 0) {
                $lop += $remaining;
            }
        }

        return [
            'working_days' => count($workingYmd),
            'present_days' => $present,
            'paid_leave_days' => $paidLeaveDays,
            'unpaid_leave_days' => $unpaidLeaveDays,
            'lop_days' => $lop,
            'leave_breakdown' => $paidByCode,
        ];
    }

    public function lopDeductionAmount(float $monthlySalary, float $lopDays): float
    {
        if ($lopDays <= 0) {
            return 0.0;
        }
        $perDay = $monthlySalary / 30.0;

        return round($perDay * $lopDays, 2);
    }

    /**
     * Used days per policy for balance (year window).
     *
     * @return array<int, float> policy_id => days
     */
    public function approvedDaysUsedByPolicy(int $employeeProfileId, string $yearStart, string $yearEnd): array
    {
        $requests = LeaveRequest::query()
            ->where('employee_profile_id', $employeeProfileId)
            ->where('status', 'approved')
            ->whereDate('start_date', '<=', $yearEnd)
            ->whereDate('end_date', '>=', $yearStart)
            ->get();

        $byPolicy = [];
        foreach ($requests as $req) {
            $map = $this->mapRequestToWorkingDays($req);
            foreach ($map as $ymd => $row) {
                if ($ymd < $yearStart || $ymd > $yearEnd) {
                    continue;
                }
                if (! $this->calendar->isWorkingDay(Carbon::parse($ymd))) {
                    continue;
                }
                $policyId = (int) ($row['leave_policy_id'] ?? 0);
                $fraction = (float) ($row['fraction'] ?? 1.0);
                if ($policyId <= 0) {
                    continue;
                }
                $byPolicy[$policyId] = ($byPolicy[$policyId] ?? 0) + $fraction;
            }
        }

        return $byPolicy;
    }

    protected function emptySummary(): array
    {
        return [
            'working_days' => 0,
            'present_days' => 0,
            'paid_leave_days' => 0,
            'unpaid_leave_days' => 0,
            'lop_days' => 0,
            'leave_breakdown' => [],
        ];
    }
}
