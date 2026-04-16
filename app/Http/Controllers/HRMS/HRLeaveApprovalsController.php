<?php

namespace App\Http\Controllers\HRMS;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Modules\HRMS\Leaves\Models\LeavePolicy;
use App\Modules\HRMS\Leaves\Models\LeaveRequest;
use App\Services\HRMS\CalendarService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;

class HRLeaveApprovalsController extends Controller
{
    public function index(CalendarService $calendar)
    {
        $pending = LeaveRequest::query()
            ->with(['employeeProfile.user', 'policy'])
            ->where('status', 'pending')
            ->orderBy('start_date')
            ->get();

        foreach ($pending as $r) {
            $expected = $calendar->countWorkingDaysBetween(
                Carbon::parse($r->start_date)->startOfDay(),
                Carbon::parse($r->end_date)->startOfDay()
            );
            if ((float) $r->days === 0.5 && Carbon::parse($r->start_date)->isSameDay(Carbon::parse($r->end_date))) {
                $expected = 0.5;
            }
            $r->setAttribute('working_days_expected', $expected);
        }

        $policies = LeavePolicy::query()->where('active', true)->orderBy('name')->get();

        return view('hrms.hr.leave_approvals.index', [
            'requests' => $pending,
            'policies' => $policies,
        ]);
    }

    public function approve(Request $request, LeaveRequest $leaveRequest, CalendarService $calendar)
    {
        /** @var User $user */
        $user = Auth::user();

        if ($leaveRequest->status !== 'pending') {
            return back()->with('status', 'Leave request already processed.');
        }

        $expected = $calendar->countWorkingDaysBetween(
            Carbon::parse($leaveRequest->start_date)->startOfDay(),
            Carbon::parse($leaveRequest->end_date)->startOfDay()
        );
        if ((float) $leaveRequest->days === 0.5 && Carbon::parse($leaveRequest->start_date)->isSameDay(Carbon::parse($leaveRequest->end_date))) {
            $expected = 0.5;
        }

        $data = $request->validate([
            'alloc' => ['required', 'array'],
            'alloc.*' => ['nullable', 'numeric', 'min:0'],
        ]);

        $allocations = [];
        foreach ($data['alloc'] as $policyId => $days) {
            $d = round((float) $days, 2);
            if ($d > 0) {
                $allocations[] = [
                    'leave_policy_id' => (int) $policyId,
                    'days' => $d,
                ];
            }
        }

        $sum = array_sum(array_column($allocations, 'days'));
        if (abs($sum - (float) $expected) > 0.001) {
            return back()->withErrors([
                'alloc' => "Allocated working days ({$sum}) must equal calendar working days in range ({$expected}). Adjust the split.",
            ]);
        }

        if ($allocations === []) {
            return back()->withErrors(['alloc' => 'Allocate at least one day to a leave type.']);
        }

        $leaveRequest->update([
            'status' => 'approved',
            'decision_by_user_id' => $user->id,
            'decided_at' => now(),
            'approval_allocations' => $allocations,
        ]);

        return back()->with('status', 'Leave approved with your allocation.');
    }

    public function reject(LeaveRequest $leaveRequest)
    {
        /** @var User $user */
        $user = Auth::user();

        if ($leaveRequest->status !== 'pending') {
            return back()->with('status', 'Leave request already processed.');
        }

        $leaveRequest->update([
            'status' => 'rejected',
            'decision_by_user_id' => $user->id,
            'decided_at' => now(),
        ]);

        return back()->with('status', 'Leave rejected.');
    }
}
