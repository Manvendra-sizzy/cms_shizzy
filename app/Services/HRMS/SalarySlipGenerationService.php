<?php

namespace App\Services\HRMS;

use App\Mail\SalarySlipGeneratedMail;
use App\Modules\HRMS\Employees\Models\EmployeeProfile;
use App\Modules\HRMS\Payroll\Models\PayrollRun;
use App\Modules\HRMS\Payroll\Models\SalarySlip;
use App\Modules\HRMS\Reimbursements\Models\ReimbursementRequest;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class SalarySlipGenerationService
{
    /**
     * Reconstruct generateSlips POST shape from a stored slip (extra earnings / deductions only).
     * Core Basic/HRA/Other are always recomputed from current salary + attendance.
     *
     * Extra earnings are mapped back onto the four payroll slots (Overtime / Bonus / Reimbursements / Extra)
     * so reimbursement linking (index 2) still works when labels match the defaults.
     *
     * @return array{employee_profile_id: int, include: int, extra_earnings: array<int, array{label: string, amount: float}>, extra_deductions: float}
     */
    public static function slipInputFromExisting(SalarySlip $slip): array
    {
        $slotLabels = ['Overtime', 'Bonus', 'Reimbursements', 'Extra'];
        $slots = [];
        foreach ($slotLabels as $lab) {
            $slots[] = ['label' => $lab, 'amount' => 0.0];
        }

        foreach ($slip->earning_lines ?? [] as $line) {
            if (($line['code'] ?? '') !== 'EXTRA_EARN') {
                continue;
            }
            $name = trim((string) ($line['name'] ?? ''));
            $amt = round((float) ($line['amount'] ?? 0), 2);
            $placed = false;
            foreach ($slotLabels as $si => $lab) {
                if ($name !== '' && strcasecmp($name, $lab) === 0) {
                    $slots[$si] = ['label' => $lab, 'amount' => $amt];
                    $placed = true;
                    break;
                }
            }
            if (! $placed) {
                foreach (range(0, 3) as $si) {
                    if ((float) ($slots[$si]['amount'] ?? 0) == 0.0) {
                        $slots[$si] = [
                            'label' => $name !== '' ? $name : $slotLabels[$si],
                            'amount' => $amt,
                        ];
                        break;
                    }
                }
            }
        }

        $extraDed = 0.0;
        foreach ($slip->deduction_lines ?? [] as $d) {
            if (($d['code'] ?? '') === 'OTHER') {
                $extraDed += (float) ($d['amount'] ?? 0);
            }
        }

        return [
            'employee_profile_id' => (int) $slip->employee_profile_id,
            'include' => 1,
            'extra_earnings' => $slots,
            'extra_deductions' => round($extraDed, 2),
        ];
    }

    /**
     * All approved reimbursement claims not yet paid on a salary slip.
     *
     * @return \Illuminate\Database\Eloquent\Collection<int, ReimbursementRequest>
     */
    public function unpaidApprovedReimbursementClaims(EmployeeProfile $emp)
    {
        return ReimbursementRequest::query()
            ->where('employee_profile_id', $emp->id)
            ->whereIn('status', ['approved', 'partially_paid'])
            ->whereNull('salary_slip_id')
            ->orderBy('decided_at')
            ->orderBy('id')
            ->get();
    }

    public function reimbursementExtraRowAmount(array $slipInput): float
    {
        $extra = $slipInput['extra_earnings'] ?? [];
        $row = is_array($extra) ? ($extra[2] ?? []) : [];

        return round((float) ($row['amount'] ?? 0), 2);
    }

    /**
     * Prefill uses extra_earnings row index 2 ("Reimbursements"). When the posted amount equals
     * the sum of unpaid approved claims, link those rows to the new slip.
     */
    public function attachReimbursementClaimsToSlipIfAmountMatches(
        EmployeeProfile $emp,
        array $slipInput,
        SalarySlip $slip
    ): void {
        $claims = $this->unpaidApprovedReimbursementClaims($emp);
        if ($claims->isEmpty()) {
            return;
        }

        $claimsSum = round((float) $claims->sum(function ($c) {
            return max(0, (float) $c->amount - (float) ($c->paid_amount ?? 0));
        }), 2);
        $posted = $this->reimbursementExtraRowAmount($slipInput);
        if ($claimsSum <= 0 || abs($posted - $claimsSum) > 0.02) {
            return;
        }

        ReimbursementRequest::query()
            ->whereIn('id', $claims->pluck('id'))
            ->update([
                'salary_slip_id' => $slip->id,
                'paid_amount' => DB::raw('amount'),
                'last_paid_at' => now(),
                'status' => 'paid',
            ]);
    }

    /**
     * Create one salary slip (same rules as admin payroll generate).
     */
    public function createSlipFromInput(
        PayrollRun $payrollRun,
        EmployeeProfile $emp,
        array $slipInput,
        AttendanceLeaveSummaryService $summaryService,
        string $currency = 'INR',
        bool $sendMail = true
    ): SalarySlip {
        $pStart = Carbon::parse($payrollRun->period_start)->startOfDay();
        $pEnd = Carbon::parse($payrollRun->period_end)->startOfDay();

        $sum = $summaryService->summarizePeriod($emp, $pStart, $pEnd);
        $base = (float) $emp->current_salary;
        $lopDed = $summaryService->lopDeductionAmount($base, $sum['lop_days']);
        $effectiveGross = max(0, round($base - $lopDed, 2));

        $extraDed = (float) ($slipInput['extra_deductions'] ?? 0);

        // Fixed salary structure:
        // Basic = 50% of gross basis
        // HRA = 50% of Basic
        // Other Allowance = remaining (balance to match gross basis)
        $basic = round($effectiveGross * 0.50, 2);
        $hra = round($basic * 0.50, 2);
        $otherAllowance = round(max(0, $effectiveGross - $basic - $hra), 2);

        $earningLines = [
            ['name' => 'Basic Salary', 'code' => 'BASIC', 'amount' => $basic],
            ['name' => 'HRA', 'code' => 'HRA', 'amount' => $hra],
            ['name' => 'Other Allowance', 'code' => 'OTHER_ALLOW', 'amount' => $otherAllowance],
        ];

        $extraEarnings = $slipInput['extra_earnings'] ?? [];
        $extraEarningLines = [];
        if (is_array($extraEarnings)) {
            foreach ($extraEarnings as $row) {
                if (! is_array($row)) {
                    continue;
                }
                $label = trim((string) ($row['label'] ?? ''));
                $amount = (float) ($row['amount'] ?? 0);
                if ($label === '' || $amount <= 0) {
                    continue;
                }

                $amount = round($amount, 2);
                $extraEarningLines[] = [
                    'name' => $label,
                    'code' => 'EXTRA_EARN',
                    'amount' => $amount,
                ];
            }
        }

        if ($extraEarningLines !== []) {
            $earningLines = array_merge($earningLines, $extraEarningLines);
        }

        $deductionLines = [];
        if ($extraDed > 0) {
            $deductionLines[] = ['name' => 'Other deductions', 'code' => 'OTHER', 'amount' => round($extraDed, 2)];
        }

        $totalDeductions = round(array_sum(array_column($deductionLines, 'amount')), 2);
        $grossDisplay = round(array_sum(array_column($earningLines, 'amount')), 2);
        $net = max(0, round($grossDisplay - $totalDeductions, 2));

        $slip = new SalarySlip();
        $slip->payroll_run_id = $payrollRun->id;
        $slip->employee_profile_id = $emp->id;
        $slip->slip_number = 'SLIP-'.Str::upper(Str::random(10));
        $slip->document_hash = strtoupper(hash('sha256', $slip->slip_number.'|'.$payrollRun->id.'|'.$emp->id.'|'.Str::random(16)));
        $slip->fill([
            'currency' => $currency,
            'base_salary' => $base,
            'working_days' => $sum['working_days'],
            'paid_leave_days' => $sum['paid_leave_days'],
            'lop_days' => $sum['lop_days'],
            'lop_deduction' => $lopDed,
            'gross' => $grossDisplay,
            'deductions' => $totalDeductions,
            'net' => $net,
            'earning_lines' => $earningLines,
            'deduction_lines' => $deductionLines,
            'issued_at' => now(),
        ]);
        $slip->save();

        $this->attachReimbursementClaimsToSlipIfAmountMatches($emp, $slipInput, $slip);

        $slip->load(['employeeProfile.user', 'employeeProfile.orgDepartment', 'employeeProfile.orgDesignation']);

        $html = view('hrms.shared.salary_slip', ['slip' => $slip, 'run' => $payrollRun])->render();
        $path = "hrms/salary-slips/slip-{$slip->id}.html";
        Storage::disk('local')->put($path, $html);
        $slip->update(['file_path' => $path]);

        if ($sendMail) {
            try {
                $emp->load('user');
                $email = $emp->preferredNotificationEmail();
                if (is_string($email) && $email !== '') {
                    Mail::to($email)->send(new SalarySlipGeneratedMail($slip, $payrollRun));
                }
            } catch (\Throwable $e) {
                Log::warning('CMS email notification failed for salary credited', [
                    'employee_profile_id' => $emp->id,
                    'slip_id' => $slip->id,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        return $slip->fresh();
    }
}
