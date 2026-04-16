<?php

namespace App\Modules\HRMS\Payroll\Models;

use App\Modules\HRMS\Employees\Models\EmployeeProfile;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SalarySlip extends Model
{
    protected $fillable = [
        'payroll_run_id',
        'employee_profile_id',
        'slip_number',
        'currency',
        'gross',
        'deductions',
        'net',
        'file_path',
        'document_hash',
        'issued_at',
        'base_salary',
        'working_days',
        'paid_leave_days',
        'lop_days',
        'lop_deduction',
        'earning_lines',
        'deduction_lines',
    ];

    protected $casts = [
        'gross' => 'decimal:2',
        'deductions' => 'decimal:2',
        'net' => 'decimal:2',
        'issued_at' => 'datetime',
        'base_salary' => 'decimal:2',
        'paid_leave_days' => 'decimal:2',
        'lop_days' => 'decimal:2',
        'lop_deduction' => 'decimal:2',
        'earning_lines' => 'array',
        'deduction_lines' => 'array',
    ];

    public function payrollRun(): BelongsTo
    {
        return $this->belongsTo(PayrollRun::class);
    }

    public function employeeProfile(): BelongsTo
    {
        return $this->belongsTo(EmployeeProfile::class);
    }
}

