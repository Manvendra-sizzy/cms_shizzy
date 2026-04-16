<?php

namespace App\Http\Controllers\HRMS;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Modules\HRMS\Employees\Models\EmployeeProfile;
use App\Modules\HRMS\Leaves\Models\LeavePolicy;
use App\Modules\HRMS\Leaves\Models\LeaveRequest;
use App\Services\HRMS\AttendanceLeaveSummaryService;
use App\Services\HRMS\CalendarService;
use App\Services\TelegramBotService;
use App\Models\AttendanceDay;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Mail;
use App\Mail\LeaveAppliedToAdminMail;

class EmployeeLeavesController extends Controller
{
    public function index(Request $request)
    {
        /** @var User $user */
        $user = Auth::user();

        $profile = EmployeeProfile::query()->where('user_id', $user->id)->firstOrFail();

        $requests = LeaveRequest::query()
            ->with('policy')
            ->where('employee_profile_id', $profile->id)
            ->orderByDesc('id')
            ->get();

        $yearStart = now()->startOfYear()->toDateString();
        $yearEnd = now()->endOfYear()->toDateString();

        $policies = LeavePolicy::query()->where('active', true)->orderBy('name')->get();

        $usedByPolicyId = app(AttendanceLeaveSummaryService::class)
            ->approvedDaysUsedByPolicy($profile->id, $yearStart, $yearEnd);

        $balances = $policies->map(function (LeavePolicy $policy) use ($usedByPolicyId, $profile, $yearStart, $yearEnd) {
            $used = (float) ($usedByPolicyId[$policy->id] ?? 0);
            if (! $policy->is_paid) {
                return [
                    'policy' => $policy,
                    'allowance' => null,
                    'used' => $used,
                    'remaining' => null,
                ];
            }
            // Calculate proportionate allowance based on joining date
            $joiningDate = $profile->joining_date ?? $profile->join_date;
            $allowance = app(AttendanceLeaveSummaryService::class)->calculateProportionateAllowance(
                (float) $policy->annual_allowance,
                $joiningDate ? $joiningDate->format('Y-m-d') : null,
                $yearStart,
                $yearEnd
            );
            $remaining = max(0, $allowance - $used);

            return [
                'policy' => $policy,
                'allowance' => $allowance,
                'used' => $used,
                'remaining' => $remaining,
            ];
        });

        $month = (int) ($request->input('month') ?? now()->month);
        $year = (int) ($request->input('year') ?? now()->year);

        $monthStart = Carbon::create($year, $month, 1)->startOfDay();
        $monthEnd = $monthStart->copy()->endOfMonth()->startOfDay();

        $calendar = app(CalendarService::class);

        // Calendar grid includes partial weeks so it always shows full rows.
        $calendarStart = $monthStart->copy()->startOfWeek(Carbon::MONDAY);
        $calendarEnd = $monthEnd->copy()->endOfWeek(Carbon::SUNDAY);

        $weeks = [];
        $cursor = $calendarStart->copy();
        while ($cursor->lte($calendarEnd)) {
            $week = [];
            for ($i = 0; $i < 7; $i++) {
                $week[] = $cursor->copy();
                $cursor->addDay();
            }
            $weeks[] = $week;
        }

        // Map APPROVED leave slots by date for calendar overlay.
        //
        // Compliance rule:
        // If a leave request spans non-working days (Sunday, 2nd/4th Saturday, designated holidays),
        // those days MUST NOT be counted/displayed as leave. Only working days can be mapped.
        $leavesByDate = [];
        $summarySvc = app(AttendanceLeaveSummaryService::class);
        $policyById = LeavePolicy::query()->get()->keyBy('id');

        foreach ($requests as $r) {
            if ($r->status !== 'approved') {
                continue;
            }

            $mapped = $summarySvc->mapRequestToWorkingDays($r); // ymd => ['leave_policy_id', 'fraction']
            foreach ($mapped as $ymd => $row) {
                $pid = (int) ($row['leave_policy_id'] ?? 0);
                $fraction = (float) ($row['fraction'] ?? 1.0);
                $isHalf = abs($fraction - 0.5) < 0.001;

                $pol = $policyById->get($pid);

                $leavesByDate[$ymd][] = [
                    'policy_code' => $pol?->code ?? 'LEAVE',
                    'policy_name' => $pol?->name ?? 'Leave',
                    'status' => 'approved',
                    'days' => $fraction,
                    'is_half' => $isHalf,
                ];
            }
        }

        // Attendance exists only for working days where the employee has punched at least once.
        // For calendar coloring we also need to treat missing attendance (no punch) as Absent.
        $attendanceByDate = AttendanceDay::query()
            ->where('employee_profile_id', $profile->id)
            ->whereBetween('work_date', [$calendarStart->toDateString(), $calendarEnd->toDateString()])
            ->get()
            ->keyBy(fn ($a) => $a->work_date->format('Y-m-d'));

        return view('hrms.employee.leaves.index', [
            'requests' => $requests,
            'balances' => $balances,
            'month' => $month,
            'year' => $year,
            'monthStart' => $monthStart,
            'weeks' => $weeks,
            'leavesByDate' => $leavesByDate,
            'calendar' => $calendar,
            'attendanceByDate' => $attendanceByDate,
            'todayStr' => now()->toDateString(),
        ]);
    }

    public function create()
    {
        $policies = LeavePolicy::query()->where('active', true)->orderBy('name')->get();
        return view('hrms.employee.leaves.create', ['policies' => $policies]);
    }

    public function store(Request $request)
    {
        /** @var User $user */
        $user = Auth::user();

        $profile = EmployeeProfile::query()->where('user_id', $user->id)->firstOrFail();

        $data = $request->validate([
            'leave_policy_id' => ['required', 'exists:leave_policies,id'],
            'start_date' => ['required', 'date'],
            'end_date' => ['required', 'date', 'after_or_equal:start_date'],
            'is_half_day' => ['nullable'],
            'reason' => ['nullable', 'string', 'max:2000'],
        ]);

        $start = Carbon::parse($data['start_date'])->startOfDay();
        $end = Carbon::parse($data['end_date'])->startOfDay();
        $calendar = app(CalendarService::class);

        $isHalf = (bool) ($data['is_half_day'] ?? false);
        if ($isHalf) {
            $end = $start->copy();
        }

        $days = $isHalf ? 0.5 : (float) $calendar->countWorkingDaysBetween($start, $end);
        if ($days < 0.5) {
            return back()->withErrors(['start_date' => 'Selected range has no working days.'])->withInput();
        }

        $leaveRequest = LeaveRequest::query()->create([
            'employee_profile_id' => $profile->id,
            'leave_policy_id' => (int) $data['leave_policy_id'],
            'start_date' => $start->toDateString(),
            'end_date' => $end->toDateString(),
            'days' => $days,
            'reason' => $data['reason'] ?? null,
            'status' => 'pending',
        ]);

        // Notify admins that a leave has been applied (audit + transparency).
        $leaveRequest->load(['policy', 'employeeProfile.user']);

        try {
            $admins = User::query()->where('role', User::ROLE_ADMIN)->get();

            foreach ($admins as $admin) {
                $email = $admin->email;
                if (! is_string($email) || $email === '') {
                    continue;
                }

                Mail::to($email)->send(new LeaveAppliedToAdminMail($leaveRequest));
            }
        } catch (\Throwable $e) {
            Log::warning('CMS email notification failed for leave applied', [
                'error' => $e->getMessage(),
            ]);
        }

        try {
            app(TelegramBotService::class)->sendLeaveAppliedNotice($leaveRequest);
        } catch (\Throwable $e) {
            Log::warning('CMS telegram notification failed for leave applied', [
                'error' => $e->getMessage(),
            ]);
        }

        return redirect()->route('employee.leaves.index')->with('status', 'Leave applied.');
    }
}
