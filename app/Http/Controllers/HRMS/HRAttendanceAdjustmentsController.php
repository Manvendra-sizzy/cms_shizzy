<?php

namespace App\Http\Controllers\HRMS;

use App\Http\Controllers\Controller;
use App\Models\AttendanceDay;
use App\Models\User;
use App\Modules\HRMS\Employees\Models\EmployeeProfile;
use App\Modules\HRMS\Leaves\Models\LeavePolicy;
use App\Modules\HRMS\Leaves\Models\LeaveRequest;
use App\Services\HRMS\CalendarService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class HRAttendanceAdjustmentsController extends Controller
{
    public function index(Request $request, CalendarService $calendar)
    {
        $employees = EmployeeProfile::query()
            ->with('user')
            ->orderBy('employee_id')
            ->get();

        $leavePolicies = LeavePolicy::query()
            ->where('active', true)
            ->orderBy('name')
            ->get();

        $selectedEmployeeId = (int) ($request->query('employee_profile_id') ?? 0);
        $selectedMonth = (string) ($request->query('month') ?? now()->format('Y-m'));
        try {
            $monthStart = Carbon::createFromFormat('Y-m', $selectedMonth)->startOfMonth()->startOfDay();
        } catch (\Throwable) {
            $selectedMonth = now()->format('Y-m');
            $monthStart = Carbon::createFromFormat('Y-m', $selectedMonth)->startOfMonth()->startOfDay();
        }
        $monthEnd = $monthStart->copy()->endOfMonth()->startOfDay();

        $calendarStart = $monthStart->copy()->startOfWeek(Carbon::MONDAY);
        $calendarEnd = $monthEnd->copy()->endOfWeek(Carbon::SUNDAY);
        $weeks = [];
        $cursor = $calendarStart->copy();
        while ($cursor->lte($calendarEnd)) {
            $week = [];
            for ($i = 0; $i < 7; $i++) {
                $week[] = [
                    'ymd' => $cursor->format('Y-m-d'),
                    'day' => (int) $cursor->day,
                    'in_month' => $cursor->month === $monthStart->month,
                    'is_working' => $calendar->isWorkingDay($cursor),
                ];
                $cursor->addDay();
            }
            $weeks[] = $week;
        }

        return view('hrms.hr.attendance_adjustments.index', [
            'employees' => $employees,
            'leavePolicies' => $leavePolicies,
            'selectedEmployeeId' => $selectedEmployeeId > 0 ? $selectedEmployeeId : null,
            'selectedDate' => $request->query('work_date'),
            'selectedMonth' => $selectedMonth,
            'weeks' => $weeks,
        ]);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'employee_profile_id' => ['required', 'exists:employee_profiles,id'],
            'work_date' => ['required', 'date'],
            'status' => ['required', 'string', 'max:64'],
        ]);

        $date = Carbon::parse($data['work_date'])->startOfDay();

        /** @var User $user */
        $user = Auth::user();
        $error = $this->applyAdjustment((int) $data['employee_profile_id'], $date, (string) $data['status'], $user);
        if ($error !== null) {
            return back()->withErrors(['status' => $error]);
        }

        return redirect()
            ->route('admin.hrms.attendance_adjustments.index', [
                'employee_profile_id' => $data['employee_profile_id'],
                'work_date' => $date->toDateString(),
            ])
            ->with('status', 'Attendance adjusted.');
    }

    public function bulkStore(Request $request, CalendarService $calendar)
    {
        $data = $request->validate([
            'employee_profile_id' => ['required', 'exists:employee_profiles,id'],
            'status' => ['required', 'string', 'max:64'],
            'work_dates' => ['required', 'array', 'min:1'],
            'work_dates.*' => ['required', 'date'],
            'month' => ['nullable', 'date_format:Y-m'],
        ]);

        /** @var User $user */
        $user = Auth::user();
        $employeeId = (int) $data['employee_profile_id'];
        $status = (string) $data['status'];

        $applied = 0;
        $skippedOffDays = 0;
        foreach ($data['work_dates'] as $rawDate) {
            $date = Carbon::parse((string) $rawDate)->startOfDay();
            if (! $calendar->isWorkingDay($date)) {
                $skippedOffDays++;
                continue;
            }

            $error = $this->applyAdjustment($employeeId, $date, $status, $user);
            if ($error !== null) {
                return back()->withErrors(['status' => $error])->withInput();
            }
            $applied++;
        }

        if ($applied === 0) {
            return back()->withErrors(['work_dates' => 'No working days selected. Off days cannot be marked.'])->withInput();
        }

        $message = "Bulk adjustment applied for {$applied} day(s).";
        if ($skippedOffDays > 0) {
            $message .= " {$skippedOffDays} off day(s) were skipped.";
        }

        return redirect()
            ->route('admin.hrms.attendance_adjustments.index', [
                'employee_profile_id' => $employeeId,
                'month' => $data['month'] ?? now()->format('Y-m'),
            ])
            ->with('status', $message);
    }

    private function applyAdjustment(int $employeeProfileId, Carbon $date, string $status, User $user): ?string
    {
        if ($status === 'present') {
            LeaveRequest::query()
                ->where('employee_profile_id', $employeeProfileId)
                ->where('status', 'approved')
                ->whereDate('start_date', $date->toDateString())
                ->whereDate('end_date', $date->toDateString())
                ->delete();

            AttendanceDay::query()->updateOrCreate(
                [
                    'employee_profile_id' => $employeeProfileId,
                    'work_date' => $date->toDateString(),
                ],
                [
                    'work_fraction' => 1.0,
                    'punch_in_at' => $date->copy()->setTime(9, 0, 0),
                    'punch_out_at' => $date->copy()->setTime(18, 0, 0),
                ]
            );

            return null;
        }

        if ($status === 'half_day') {
            LeaveRequest::query()
                ->where('employee_profile_id', $employeeProfileId)
                ->where('status', 'approved')
                ->whereDate('start_date', $date->toDateString())
                ->whereDate('end_date', $date->toDateString())
                ->delete();

            AttendanceDay::query()->updateOrCreate(
                [
                    'employee_profile_id' => $employeeProfileId,
                    'work_date' => $date->toDateString(),
                ],
                [
                    'work_fraction' => 0.5,
                    'punch_in_at' => $date->copy()->setTime(9, 0, 0),
                    'punch_out_at' => $date->copy()->setTime(13, 0, 0),
                ]
            );

            return null;
        }

        if ($status === 'absent') {
            LeaveRequest::query()
                ->where('employee_profile_id', $employeeProfileId)
                ->where('status', 'approved')
                ->whereDate('start_date', $date->toDateString())
                ->whereDate('end_date', $date->toDateString())
                ->delete();

            AttendanceDay::query()
                ->where('employee_profile_id', $employeeProfileId)
                ->whereDate('work_date', $date->toDateString())
                ->delete();

            return null;
        }

        if (str_starts_with($status, 'leave:')) {
            $policyId = (int) substr($status, strlen('leave:'));
            $policy = LeavePolicy::query()->where('active', true)->find($policyId);
            if (! $policy) {
                return 'Invalid leave policy selected.';
            }

            AttendanceDay::query()
                ->where('employee_profile_id', $employeeProfileId)
                ->whereDate('work_date', $date->toDateString())
                ->delete();

            LeaveRequest::query()->updateOrCreate(
                [
                    'employee_profile_id' => $employeeProfileId,
                    'start_date' => $date->toDateString(),
                    'end_date' => $date->toDateString(),
                ],
                [
                    'leave_policy_id' => $policy->id,
                    'days' => 1,
                    'reason' => 'Admin attendance adjustment',
                    'status' => 'approved',
                    'decision_by_user_id' => $user->id,
                    'decided_at' => now(),
                    'approval_allocations' => null,
                ]
            );

            return null;
        }

        if (str_starts_with($status, 'leave_half:')) {
            $policyId = (int) substr($status, strlen('leave_half:'));
            $policy = LeavePolicy::query()->where('active', true)->find($policyId);
            if (! $policy) {
                return 'Invalid leave policy selected.';
            }

            LeaveRequest::query()->updateOrCreate(
                [
                    'employee_profile_id' => $employeeProfileId,
                    'start_date' => $date->toDateString(),
                    'end_date' => $date->toDateString(),
                ],
                [
                    'leave_policy_id' => $policy->id,
                    'days' => 0.5,
                    'reason' => 'Admin attendance adjustment (half day)',
                    'status' => 'approved',
                    'decision_by_user_id' => $user->id,
                    'decided_at' => now(),
                    'approval_allocations' => null,
                ]
            );

            return null;
        }

        return 'Invalid status selected.';
    }
}

