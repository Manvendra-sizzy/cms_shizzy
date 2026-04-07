<?php

namespace App\Http\Controllers\HRMS;

use App\Http\Controllers\Controller;
use App\Mail\ReimbursementApprovedToEmployeeMail;
use App\Models\User;
use App\Modules\HRMS\Reimbursements\Models\ReimbursementRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class HRReimbursementApprovalsController extends Controller
{
    public function index()
    {
        $pending = ReimbursementRequest::query()
            ->with(['employeeProfile.user'])
            ->whereIn('status', ['pending', 'approved', 'partially_paid'])
            ->orderByDesc('id')
            ->get();

        return view('hrms.hr.reimbursement_approvals.index', [
            'requests' => $pending,
        ]);
    }

    public function approve(ReimbursementRequest $reimbursementRequest)
    {
        /** @var User $user */
        $user = Auth::user();

        if ($reimbursementRequest->status !== 'pending') {
            return back()->with('status', 'This request was already processed.');
        }

        $reimbursementRequest->update([
            'status' => 'approved',
            'decision_by_user_id' => $user->id,
            'decided_at' => now(),
            'admin_note' => null,
            'paid_amount' => 0,
            'last_paid_at' => null,
        ]);

        $reimbursementRequest->load(['employeeProfile.user']);

        $employeeEmail = $reimbursementRequest->employeeProfile?->user?->email;
        if (is_string($employeeEmail) && $employeeEmail !== '') {
            try {
                Mail::to($employeeEmail)->send(new ReimbursementApprovedToEmployeeMail($reimbursementRequest));
            } catch (\Throwable $e) {
                Log::warning('CMS email notification failed for reimbursement approved', [
                    'error' => $e->getMessage(),
                ]);
            }
        }

        return back()->with('status', 'Reimbursement approved; employee notified by email.');
    }

    public function show(ReimbursementRequest $reimbursementRequest)
    {
        $reimbursementRequest->load(['employeeProfile.user', 'decidedBy', 'salarySlip']);

        return view('hrms.hr.reimbursement_approvals.show', [
            'requestItem' => $reimbursementRequest,
        ]);
    }

    public function payPartial(Request $request, ReimbursementRequest $reimbursementRequest)
    {
        if (! in_array($reimbursementRequest->status, ['approved', 'partially_paid'], true)) {
            return back()->withErrors(['pay_amount' => 'Only approved reimbursements can be paid.']);
        }

        $data = $request->validate([
            'payment_mode' => ['required', 'in:full,partial'],
            'pay_amount' => ['nullable', 'numeric', 'min:0.01'],
            'admin_note' => ['nullable', 'string', 'max:2000'],
        ]);

        $total = (float) $reimbursementRequest->amount;
        $alreadyPaid = (float) ($reimbursementRequest->paid_amount ?? 0);
        $remaining = round(max(0, $total - $alreadyPaid), 2);
        $pay = $data['payment_mode'] === 'full'
            ? $remaining
            : round((float) ($data['pay_amount'] ?? 0), 2);

        if ($data['payment_mode'] === 'partial' && $pay <= 0) {
            return back()->withErrors(['pay_amount' => 'Enter an amount for partial payment.']);
        }

        if ($pay > $remaining + 0.001) {
            return back()->withErrors(['pay_amount' => 'Pay amount cannot exceed remaining reimbursement amount.']);
        }

        $newPaid = round($alreadyPaid + $pay, 2);
        $newStatus = $newPaid >= $total - 0.001 ? 'paid' : 'partially_paid';

        $reimbursementRequest->update([
            'paid_amount' => $newPaid,
            'last_paid_at' => now(),
            'status' => $newStatus,
            'admin_note' => $data['admin_note'] ?? $reimbursementRequest->admin_note,
        ]);

        return back()->with('status', $newStatus === 'paid' ? 'Reimbursement fully paid.' : 'Partial reimbursement payment recorded.');
    }

    public function reject(Request $request, ReimbursementRequest $reimbursementRequest)
    {
        /** @var User $user */
        $user = Auth::user();

        if ($reimbursementRequest->status !== 'pending') {
            return back()->with('status', 'This request was already processed.');
        }

        $data = $request->validate([
            'admin_note' => ['nullable', 'string', 'max:2000'],
        ]);

        $reimbursementRequest->update([
            'status' => 'rejected',
            'decision_by_user_id' => $user->id,
            'decided_at' => now(),
            'admin_note' => $data['admin_note'] ?? null,
        ]);

        return back()->with('status', 'Reimbursement rejected.');
    }
}
