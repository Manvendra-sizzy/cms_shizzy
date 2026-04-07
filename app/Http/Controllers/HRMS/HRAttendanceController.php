<?php

namespace App\Http\Controllers\HRMS;

use App\Http\Controllers\Controller;
use App\Models\AttendanceDay;
use App\Modules\HRMS\Employees\Models\EmployeeProfile;
use App\Modules\HRMS\Leaves\Models\LeaveRequest;
use App\Services\HRMS\AttendanceLeaveSummaryService;
use App\Services\HRMS\CalendarService;
use Carbon\Carbon;
use Illuminate\Http\Request;

class HRAttendanceController extends Controller
{
    private const MIN_PREMISE_MINUTES = 540; // 9 hours

    public function index(
        Request $request,
        CalendarService $calendarService,
        AttendanceLeaveSummaryService $summaryService
    )
    {
        $date = $request->filled('date')
            ? Carbon::parse((string) $request->input('date'))->toDateString()
            : now()->toDateString();

        $employeeQuery = EmployeeProfile::query()->with('user')->orderBy('employee_id');
        $employeeFilter = trim((string) $request->input('employee', ''));

        if ($employeeFilter !== '') {
            $employeeQuery->where(function ($q) use ($employeeFilter) {
                $q->where('employee_id', 'like', "%{$employeeFilter}%")
                    ->orWhereHas('user', function ($uq) use ($employeeFilter) {
                        $uq->where('name', 'like', "%{$employeeFilter}%");
                    });
            });
        }

        $employees = $employeeQuery->get();

        $attendanceRows = AttendanceDay::query()
            ->whereDate('work_date', $date)
            ->whereIn('employee_profile_id', $employees->pluck('id'))
            ->get()
            ->keyBy('employee_profile_id');
        $approvedLeaveRequests = LeaveRequest::query()
            ->where('status', 'approved')
            ->whereDate('start_date', '<=', $date)
            ->whereDate('end_date', '>=', $date)
            ->whereIn('employee_profile_id', $employees->pluck('id'))
            ->get();
        $leaveFractionByEmployee = [];
        foreach ($approvedLeaveRequests as $req) {
            $map = $summaryService->mapRequestToWorkingDays($req);
            $fraction = (float) ($map[$date]['fraction'] ?? 0.0);
            if ($fraction <= 0) {
                continue;
            }
            $employeeId = (int) $req->employee_profile_id;
            $leaveFractionByEmployee[$employeeId] = max((float) ($leaveFractionByEmployee[$employeeId] ?? 0.0), $fraction);
        }
        $isOfficialLeave = ! $calendarService->isWorkingDay(Carbon::parse($date));

        $rows = $employees->map(function (EmployeeProfile $employee) use ($attendanceRows, $leaveFractionByEmployee, $isOfficialLeave) {
            $attendance = $attendanceRows->get($employee->id);
            $status = 'absent';
            $workedMinutes = null;
            $leaveFraction = (float) ($leaveFractionByEmployee[$employee->id] ?? 0.0);
            if ($isOfficialLeave) {
                $status = 'official_leave';
            } elseif ($leaveFraction >= 1.0) {
                $status = 'employee_leave';
            } elseif ($leaveFraction > 0.0) {
                $status = 'half_day';
            } elseif ($attendance && $attendance->punch_in_at && $attendance->punch_out_at) {
                $workedMinutes = (int) $attendance->punch_in_at->diffInMinutes($attendance->punch_out_at);
                $status = $workedMinutes >= self::MIN_PREMISE_MINUTES ? 'present' : 'insufficient_hours';
            } elseif ($attendance && $attendance->punch_in_at && ! $attendance->punch_out_at) {
                $status = 'insufficient_hours';
            }

            return [
                'employee' => $employee,
                'attendance' => $attendance,
                'status' => $status,
                'worked_minutes' => $workedMinutes,
            ];
        });

        return view('hrms.hr.attendance.index', [
            'date' => $date,
            'employeeFilter' => $employeeFilter,
            'rows' => $rows,
            'presentCount' => $rows->where('status', 'present')->count(),
            'insufficientHoursCount' => $rows->where('status', 'insufficient_hours')->count(),
            'halfDayCount' => $rows->where('status', 'half_day')->count(),
            'employeeLeaveCount' => $rows->where('status', 'employee_leave')->count(),
            'absentCount' => $rows->where('status', 'absent')->count(),
        ]);
    }
}

