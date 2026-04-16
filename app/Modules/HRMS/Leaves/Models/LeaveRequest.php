<?php

namespace App\Modules\HRMS\Leaves\Models;

use App\Models\User;
use App\Modules\HRMS\Employees\Models\EmployeeProfile;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LeaveRequest extends Model
{
    protected $fillable = [
        'employee_profile_id',
        'leave_policy_id',
        'start_date',
        'end_date',
        'days',
        'reason',
        'status',
        'decision_by_user_id',
        'decided_at',
        'approval_allocations',
    ];

    protected $casts = [
        'start_date' => 'date',
        'end_date' => 'date',
        'days' => 'decimal:2',
        'decided_at' => 'datetime',
        'approval_allocations' => 'array',
    ];

    public function employeeProfile(): BelongsTo
    {
        return $this->belongsTo(EmployeeProfile::class);
    }

    public function policy(): BelongsTo
    {
        return $this->belongsTo(LeavePolicy::class, 'leave_policy_id');
    }

    public function decidedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'decision_by_user_id');
    }
}

