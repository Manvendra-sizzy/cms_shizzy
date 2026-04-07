<?php

namespace App\Http\Controllers\HRMS;

use App\Http\Controllers\Controller;
use App\Models\AttendanceDay;
use App\Modules\HRMS\Employees\Models\EmployeeProfile;
use App\Modules\HRMS\Leaves\Models\LeaveRequest;
use App\Modules\HRMS\Reimbursements\Models\ReimbursementRequest;
use App\Modules\HRMS\Payroll\Models\PayrollRun;

class HRDashboardController extends Controller
{
    public function index()
    {
        $today = now()->toDateString();

        return view('hrms.hr.dashboard', [
            'employeesCount' => EmployeeProfile::query()->count(),
            'presentEmployeesToday' => AttendanceDay::query()
                ->whereDate('work_date', $today)
                ->whereNotNull('punch_in_at')
                ->distinct('employee_profile_id')
                ->count('employee_profile_id'),
            'pendingLeavesCount' => LeaveRequest::query()->where('status', 'pending')->count(),
            'pendingReimbursementsCount' => ReimbursementRequest::query()->where('status', 'pending')->count(),
            'payrollRunsCount' => PayrollRun::query()->count(),
        ]);
    }
}
