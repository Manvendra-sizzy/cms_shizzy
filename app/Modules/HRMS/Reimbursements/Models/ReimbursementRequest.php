<?php

namespace App\Modules\HRMS\Reimbursements\Models;

use App\Models\User;
use App\Modules\HRMS\Employees\Models\EmployeeProfile;
use App\Modules\HRMS\Payroll\Models\SalarySlip;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ReimbursementRequest extends Model
{
    protected $table = 'employee_reimbursement_requests';

    protected $fillable = [
        'employee_profile_id',
        'title',
        'category',
        'expense_date',
        'amount',
        'paid_amount',
        'last_paid_at',
        'description',
        'receipt_path',
        'status',
        'decision_by_user_id',
        'decided_at',
        'admin_note',
        'salary_slip_id',
    ];

    protected $casts = [
        'expense_date' => 'date',
        'amount' => 'decimal:2',
        'paid_amount' => 'decimal:2',
        'last_paid_at' => 'datetime',
        'decided_at' => 'datetime',
    ];

    public function employeeProfile(): BelongsTo
    {
        return $this->belongsTo(EmployeeProfile::class);
    }

    public function decidedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'decision_by_user_id');
    }

    public function salarySlip(): BelongsTo
    {
        return $this->belongsTo(SalarySlip::class);
    }
}
