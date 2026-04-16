<?php

namespace App\Http\Controllers\HRMS;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Modules\HRMS\Employees\Models\EmployeeProfile;
use App\Modules\HRMS\Payroll\Models\PayrollRun;
use App\Modules\HRMS\Payroll\Models\SalarySlip;
use App\Services\HRMS\AttendanceLeaveSummaryService;
use App\Services\HRMS\SalarySlipGenerationService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class HRPayrollController extends Controller
{
    public function index()
    {
        $runs = PayrollRun::query()->orderByDesc('id')->paginate(20);

        return view('hrms.hr.payroll.index', ['runs' => $runs]);
    }

    public function create()
    {
        return view('hrms.hr.payroll.create');
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'period_start' => ['required', 'date'],
            'period_end' => ['required', 'date', 'after_or_equal:period_start'],
            'notes' => ['nullable', 'string'],
        ]);

        $run = PayrollRun::query()->create([
            'period_start' => $data['period_start'],
            'period_end' => $data['period_end'],
            'status' => 'draft',
            'notes' => $data['notes'] ?? null,
        ]);

        return redirect()->route('admin.hrms.payroll.show', $run);
    }

    public function show(PayrollRun $payrollRun, AttendanceLeaveSummaryService $summaryService)
    {
        $payrollRun->load('salarySlips.employeeProfile.user');
        $employees = EmployeeProfile::query()->with('user')->orderBy('employee_id')->get();

        $pStart = Carbon::parse($payrollRun->period_start)->startOfDay();
        $pEnd = Carbon::parse($payrollRun->period_end)->startOfDay();

        $previews = [];
        $reimbursementPending = [];
        foreach ($employees as $emp) {
            $sum = $summaryService->summarizePeriod($emp, $pStart, $pEnd);
            $base = (float) $emp->current_salary;
            $lopDed = $summaryService->lopDeductionAmount($base, $sum['lop_days']);
            $effective = max(0, round($base - $lopDed, 2));
            $previews[$emp->id] = array_merge($sum, [
                'base_salary' => $base,
                'lop_deduction' => $lopDed,
                'effective_gross_basis' => $effective,
            ]);

            $claims = app(SalarySlipGenerationService::class)->unpaidApprovedReimbursementClaims($emp);
            $reimbursementPending[$emp->id] = round((float) $claims->sum(function ($c) {
                return max(0, (float) $c->amount - (float) ($c->paid_amount ?? 0));
            }), 2);
        }

        return view('hrms.hr.payroll.show', [
            'run' => $payrollRun,
            'employees' => $employees,
            'previews' => $previews,
            'reimbursementPending' => $reimbursementPending,
        ]);
    }

    public function generateSlips(Request $request, PayrollRun $payrollRun, AttendanceLeaveSummaryService $summaryService, SalarySlipGenerationService $slipGeneration)
    {
        $data = $request->validate([
            'slips' => ['required', 'array'],
            'slips.*.employee_profile_id' => ['required', 'exists:employee_profiles,id'],
            'slips.*.extra_earnings' => ['nullable', 'array'],
            'slips.*.extra_earnings.*.label' => ['nullable', 'string', 'max:255'],
            'slips.*.extra_earnings.*.amount' => ['nullable', 'numeric', 'min:0'],
            'slips.*.extra_deductions' => ['nullable', 'numeric', 'min:0'],
            'slips.*.include' => ['nullable'],
            'currency' => ['nullable', 'string', 'max:8'],
        ]);

        /** @var User $user */
        $user = Auth::user();

        $currency = 'INR';

        $generated = 0;
        foreach ($data['slips'] as $slipInput) {
            if (empty($slipInput['include'])) {
                continue;
            }

            $emp = EmployeeProfile::query()->find((int) $slipInput['employee_profile_id']);
            if (! $emp) {
                continue;
            }

            // Lock slips: once generated, they are not editable/overwritable.
            $existingSlip = SalarySlip::query()
                ->where('payroll_run_id', $payrollRun->id)
                ->where('employee_profile_id', $emp->id)
                ->first();
            if ($existingSlip) {
                continue;
            }

            $generated++;

            $slipGeneration->createSlipFromInput($payrollRun, $emp, $slipInput, $summaryService, $currency, true);
        }

        if ($generated === 0) {
            return back()->withErrors(['payroll' => 'No new slips generated. Selected employees may already have locked slips for this payroll run.']);
        }

        $payrollRun->update([
            'status' => 'processed',
            'processed_by_user_id' => $user->id,
            'processed_at' => now(),
        ]);

        return redirect()->route('admin.hrms.payroll.show', $payrollRun)->with('status', 'Salary slips generated from attendance & leave.');
    }

    public function downloadSlip(SalarySlip $salarySlip)
    {
        $run = $salarySlip->payrollRun()->first();
        if (! $run) {
            abort(404);
        }

        $salarySlip->load(['employeeProfile.user', 'employeeProfile.orgDepartment', 'employeeProfile.orgDesignation']);

        $html = view('hrms.shared.salary_slip', ['slip' => $salarySlip, 'run' => $run])->render();

        $dompdf = new \Dompdf\Dompdf([
            'defaultFont' => 'DejaVu Sans',
            'isRemoteEnabled' => true,
        ]);
        $dompdf->loadHtml($html, 'UTF-8');
        $dompdf->setPaper('A4', 'portrait');
        $dompdf->render();

        return response($dompdf->output(), 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'attachment; filename="'.$salarySlip->slip_number.'.pdf"',
        ]);
    }

    // Salary structure is intentionally static (Basic/HRA/Other Allowance); see SalarySlipGenerationService.
}
