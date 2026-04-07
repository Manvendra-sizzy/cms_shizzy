<?php

namespace App\Modules\HRMS\Employees\Models;

use App\Models\User;
use App\Models\OrganizationDepartment;
use App\Models\OrganizationTeam;
use App\Models\OrganizationDesignation;
use App\Modules\HRMS\Documents\Models\HRDocument;
use App\Modules\HRMS\Leaves\Models\LeaveRequest;
use App\Modules\HRMS\Payroll\Models\SalarySlip;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class EmployeeProfile extends Model
{
    protected $fillable = [
        'user_id',
        'employee_id',
        'department_id',
        'team_id',
        'designation_id',
        'personal_email',
        'personal_mobile',
        'official_email',
        'joining_date',
        'pan_card_path',
        'id_card_path',
        'profile_image_path',
        'signed_contract_path',
        'bank_account_number',
        'bank_ifsc_code',
        'bank_name',
        'department',
        'designation',
        'join_date',
        'status',
        'inactive_at',
        'inactive_remarks',
        'separation_type',
        'separation_effective_at',
        'separation_remarks',
        'current_salary',
        'attendance_locked_at',
        'attendance_lock_reason',
        'attendance_unlock_note',
        'attendance_unlock_by_user_id',
        'attendance_unlock_at',
        'last_missed_punch_out_notice_at',
        'is_remote',
        'reporting_manager_employee_profile_id',
        'date_of_birth',
        'phone',
        'address',
        'emergency_contact_name',
        'emergency_contact_phone',
    ];

    protected $casts = [
        'join_date' => 'date',
        'joining_date' => 'date',
        'inactive_at' => 'date',
        'attendance_locked_at' => 'datetime',
        'attendance_unlock_at' => 'datetime',
        'last_missed_punch_out_notice_at' => 'datetime',
        'separation_effective_at' => 'date',
        'date_of_birth' => 'date',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function orgDepartment(): BelongsTo
    {
        return $this->belongsTo(OrganizationDepartment::class, 'department_id');
    }

    public function orgTeam(): BelongsTo
    {
        return $this->belongsTo(OrganizationTeam::class, 'team_id');
    }

    public function orgTeams(): BelongsToMany
    {
        return $this->belongsToMany(
            OrganizationTeam::class,
            'employee_profile_team',
            'employee_profile_id',
            'organization_team_id'
        )->withTimestamps();
    }

    public function orgDesignation(): BelongsTo
    {
        return $this->belongsTo(OrganizationDesignation::class, 'designation_id');
    }

    public function reportingManager(): BelongsTo
    {
        return $this->belongsTo(self::class, 'reporting_manager_employee_profile_id');
    }

    public function documents(): HasMany
    {
        return $this->hasMany(HRDocument::class);
    }

    public function leaveRequests(): HasMany
    {
        return $this->hasMany(LeaveRequest::class);
    }

    public function salarySlips(): HasMany
    {
        return $this->hasMany(SalarySlip::class);
    }
}

