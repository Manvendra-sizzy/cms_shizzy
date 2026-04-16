<?php

namespace App\Http\Controllers\HRMS;

use App\Http\Controllers\Controller;
use App\Mail\ReimbursementAppliedToAdminMail;
use App\Models\User;
use App\Modules\HRMS\Employees\Models\EmployeeProfile;
use App\Modules\HRMS\Reimbursements\Models\ReimbursementRequest;
use App\Services\TelegramBotService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class EmployeeReimbursementsController extends Controller
{
    public function index()
    {
        /** @var User $user */
        $user = Auth::user();

        $profile = EmployeeProfile::query()->where('user_id', $user->id)->firstOrFail();

        $requests = ReimbursementRequest::query()
            ->with('salarySlip')
            ->where('employee_profile_id', $profile->id)
            ->orderByDesc('id')
            ->get();

        return view('hrms.employee.reimbursements.index', [
            'requests' => $requests,
        ]);
    }

    public function create()
    {
        return view('hrms.employee.reimbursements.create');
    }

    public function show(ReimbursementRequest $reimbursementRequest)
    {
        /** @var User $user */
        $user = Auth::user();
        $profile = EmployeeProfile::query()->where('user_id', $user->id)->firstOrFail();

        if ($reimbursementRequest->employee_profile_id !== $profile->id) {
            abort(403);
        }

        $reimbursementRequest->load(['salarySlip']);

        return view('hrms.employee.reimbursements.show', [
            'requestItem' => $reimbursementRequest,
        ]);
    }

    public function store(Request $request)
    {
        /** @var User $user */
        $user = Auth::user();

        $profile = EmployeeProfile::query()->where('user_id', $user->id)->firstOrFail();

        $data = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'category' => ['nullable', 'string', 'max:120'],
            'expense_date' => ['required', 'date'],
            'amount' => ['required', 'numeric', 'min:0.01'],
            'description' => ['nullable', 'string', 'max:5000'],
            'receipt' => ['nullable', 'file', 'mimes:pdf,jpg,jpeg,png,webp', 'max:5120'],
        ]);

        $receiptPath = null;
        if ($request->hasFile('receipt')) {
            $receiptPath = $request->file('receipt')->store('hrms/reimbursements', 'public');
        }

        $reimbursement = ReimbursementRequest::query()->create([
            'employee_profile_id' => $profile->id,
            'title' => $data['title'],
            'category' => $data['category'] ?? null,
            'expense_date' => $data['expense_date'],
            'amount' => $data['amount'],
            'description' => $data['description'] ?? null,
            'receipt_path' => $receiptPath,
            'status' => 'pending',
        ]);

        $reimbursement->load(['employeeProfile.user']);

        try {
            $admins = User::query()->where('role', User::ROLE_ADMIN)->get();

            foreach ($admins as $admin) {
                $email = $admin->email;
                if (! is_string($email) || $email === '') {
                    continue;
                }

                Mail::to($email)->send(new ReimbursementAppliedToAdminMail($reimbursement));
            }
        } catch (\Throwable $e) {
            Log::warning('CMS email notification failed for reimbursement applied', [
                'error' => $e->getMessage(),
            ]);
        }

        try {
            app(TelegramBotService::class)->sendReimbursementAppliedNotice($reimbursement);
        } catch (\Throwable $e) {
            Log::warning('CMS telegram notification failed for reimbursement applied', [
                'error' => $e->getMessage(),
            ]);
        }

        return redirect()->route('employee.reimbursements.index')->with('status', 'Reimbursement request submitted.');
    }
}
