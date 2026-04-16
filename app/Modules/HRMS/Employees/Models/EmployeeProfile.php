<?php

namespace App\Modules\HRMS\Employees\Models;

use App\Models\User;
use App\Models\OrganizationDepartment;
use App\Models\OrganizationTeam;
use App\Models\OrganizationDesignation;
use App\Modules\HRMS\Documents\Models\HRDocument;
use App\Modules\HRMS\Leaves\Models\LeaveRequest;
use App\Modules\HRMS\Payroll\Models\SalarySlip;
use App\Services\HRMS\EmployeeLifecycleService;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class EmployeeProfile extends Model
{
    public const TYPE_INTERN = EmployeeLifecycleService::TYPE_INTERN;
    public const TYPE_PERMANENT_EMPLOYEE = EmployeeLifecycleService::TYPE_PERMANENT_EMPLOYEE;

    public const BADGE_INTERNSHIP_I = EmployeeLifecycleService::BADGE_INTERNSHIP_I;
    public const BADGE_PROBATION_E = EmployeeLifecycleService::BADGE_PROBATION_E;
    public const BADGE_PERMANENT_EMPLOYEE_PE = EmployeeLifecycleService::BADGE_PERMANENT_EMPLOYEE_PE;

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
        'employee_type',
        'employee_badge',
        'internship_period_months',
        'internship_start_date',
        'internship_end_date',
        'probation_period_months',
        'probation_start_date',
        'probation_end_date',
        'converted_to_permanent_at',
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
        'internship_start_date' => 'date',
        'internship_end_date' => 'date',
        'probation_start_date' => 'date',
        'probation_end_date' => 'date',
        'converted_to_permanent_at' => 'datetime',
        'inactive_at' => 'date',
        'attendance_locked_at' => 'datetime',
        'attendance_unlock_at' => 'datetime',
        'last_missed_punch_out_notice_at' => 'datetime',
        'separation_effective_at' => 'date',
        'date_of_birth' => 'date',
        'internship_period_months' => 'integer',
        'probation_period_months' => 'integer',
        'is_remote' => 'bool',
    ];

    public function isIntern(): bool
    {
        return $this->employee_type === self::TYPE_INTERN;
    }

    public function isPermanentEmployee(): bool
    {
        return $this->employee_type === self::TYPE_PERMANENT_EMPLOYEE;
    }

    public function isOnProbation(): bool
    {
        if (! $this->isPermanentEmployee()) {
            return false;
        }

        if (! $this->probation_end_date) {
            return false;
        }

        return Carbon::today()->lt($this->probation_end_date->copy()->startOfDay());
    }

    public function hasCompletedProbation(): bool
    {
        if (! $this->isPermanentEmployee()) {
            return false;
        }

        if (! $this->probation_end_date) {
            return true;
        }

        return Carbon::today()->gte($this->probation_end_date->copy()->startOfDay());
    }

    public function hasCompletedInternship(): bool
    {
        if (! $this->isIntern()) {
            return false;
        }

        if (! $this->internship_end_date) {
            return false;
        }

        return Carbon::today()->gte($this->internship_end_date->copy()->startOfDay());
    }

    public function canBeConvertedToPermanent(): bool
    {
        return $this->isIntern()
            && ($this->status ?? 'active') === 'active'
            && $this->hasCompletedInternship();
    }

    public function badgeLabel(): string
    {
        $stored = (string) ($this->employee_badge ?? '');
        if ($stored !== '') {
            return EmployeeLifecycleService::badgeLabels()[$stored] ?? '—';
        }

        if ($this->isIntern()) {
            return EmployeeLifecycleService::badgeLabels()[self::BADGE_INTERNSHIP_I];
        }

        return $this->hasCompletedProbation()
            ? EmployeeLifecycleService::badgeLabels()[self::BADGE_PERMANENT_EMPLOYEE_PE]
            : EmployeeLifecycleService::badgeLabels()[self::BADGE_PROBATION_E];
    }

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

    /**
     * Personal email for employee-facing notifications (documents, slips, etc.).
     * Falls back to the login/official user email when personal is missing or invalid.
     */
    public function preferredNotificationEmail(): ?string
    {
        $p = trim((string) ($this->personal_email ?? ''));
        if ($p !== '' && filter_var($p, FILTER_VALIDATE_EMAIL)) {
            return $p;
        }

        $this->loadMissing('user');

        return $this->user?->email;
    }
}

