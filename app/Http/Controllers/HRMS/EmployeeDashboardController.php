<?php

namespace App\Http\Controllers\HRMS;

use App\Http\Controllers\Controller;
use App\Models\AttendanceDay;
use App\Models\EmployeeAttendanceReminder;
use App\Models\User;
use App\Modules\HRMS\Employees\Models\EmployeeProfile;
use App\Modules\HRMS\Leaves\Models\LeaveRequest;
use App\Modules\HRMS\Reimbursements\Models\ReimbursementRequest;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;

class EmployeeDashboardController extends Controller
{
    private const MIN_PREMISE_MINUTES = 540; // 9 hours

    public function index()
    {
        /** @var User $user */
        $user = Auth::user();

        $profile = EmployeeProfile::query()
            ->with(['user', 'orgDepartment', 'orgDesignation', 'reportingManager.user'])
            ->where('user_id', $user->id)
            ->first();

        $latestInsufficientWarning = null;
        $latestAbsentWarning = null;
        $noticeBoard = [
            'prev_day_insufficient_hours' => null,
            'today_warning' => null,
        ];
        if ($profile) {
            $latestInsufficientWarning = EmployeeAttendanceReminder::query()
                ->where('employee_profile_id', $profile->id)
                ->whereIn('type', ['insufficient_warning_1', 'insufficient_warning_2'])
                ->orderByDesc('sent_at')
                ->first();
            $latestAbsentWarning = EmployeeAttendanceReminder::query()
                ->where('employee_profile_id', $profile->id)
                ->whereIn('type', ['absent_warning_1', 'absent_warning_2'])
                ->orderByDesc('sent_at')
                ->first();

            $previousDay = now('Asia/Kolkata')->copy()->subDay()->startOfDay();
            $previousRow = AttendanceDay::query()
                ->where('employee_profile_id', $profile->id)
                ->whereDate('work_date', $previousDay->toDateString())
                ->first();

            if ($previousRow && $previousRow->punch_in_at && $previousRow->punch_out_at) {
                $workedMinutes = (int) $previousRow->punch_in_at->diffInMinutes($previousRow->punch_out_at);
                if ($workedMinutes < self::MIN_PREMISE_MINUTES) {
                    $noticeBoard['prev_day_insufficient_hours'] = [
                        'date' => $previousDay->toDateString(),
                        'worked_minutes' => $workedMinutes,
                    ];
                }
            }

            $monthStart = now('Asia/Kolkata')->copy()->startOfMonth()->toDateString();
            $monthEnd = now('Asia/Kolkata')->copy()->endOfMonth()->toDateString();

            $violationDays = AttendanceDay::query()
                ->where('employee_profile_id', $profile->id)
                ->whereBetween('work_date', [$monthStart, $monthEnd])
                ->whereNotNull('punch_in_at')
                ->where(function ($q) {
                    $q->whereNull('punch_out_at')
                        ->orWhereRaw('TIMESTAMPDIFF(MINUTE, punch_in_at, punch_out_at) < ?', [self::MIN_PREMISE_MINUTES]);
                })
                ->count();

            if ($violationDays >= 2 && ! $profile->attendance_locked_at) {
                $noticeBoard['today_warning'] = [
                    'violation_days' => $violationDays,
                    'message' => 'Warning: Your ID may be disabled if attendance compliance is not improved.',
                ];
            }
        }

        return view('hrms.employee.dashboard', [
            'profile' => $profile,
            'latestInsufficientWarning' => $latestInsufficientWarning,
            'latestAbsentWarning' => $latestAbsentWarning,
            'noticeBoard' => $noticeBoard,
            'pendingLeavesCount' => $profile
                ? LeaveRequest::query()->where('employee_profile_id', $profile->id)->where('status', 'pending')->count()
                : 0,
            'pendingReimbursementsCount' => $profile
                ? ReimbursementRequest::query()->where('employee_profile_id', $profile->id)->where('status', 'pending')->count()
                : 0,
        ]);
    }
}
