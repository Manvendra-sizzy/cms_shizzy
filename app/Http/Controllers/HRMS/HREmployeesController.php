<?php

namespace App\Http\Controllers\HRMS;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Modules\HRMS\Employees\Models\EmployeeProfile;
use App\Models\OrganizationDepartment;
use App\Models\OrganizationDesignation;
use App\Models\OrganizationTeam;
use App\Modules\Projects\Models\ProjectTeamMember;
use App\Modules\Assets\Models\AssetAssignment;
use App\Modules\HRMS\Documents\Models\HRDocument;
use App\Models\EmployeeChangeLog;
use App\Models\EmployeeUploadedDocument;
use Illuminate\Http\Request;
use App\Modules\HRMS\Leaves\Models\LeavePolicy;
use App\Modules\HRMS\Payroll\Models\SalarySlip;
use App\Services\HRMS\AttendanceLeaveSummaryService;
use App\Services\HRMS\EmployeeLifecycleService;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class HREmployeesController extends Controller
{
    public function index()
    {
        $employees = EmployeeProfile::query()
            ->with(['user', 'orgDepartment', 'orgDesignation'])
            ->orderBy('id', 'desc')
            ->paginate(20);

        return view('hrms.hr.employees.index', ['employees' => $employees]);
    }

    public function create()
    {
        return view('hrms.hr.employees.create', [
            'departments' => OrganizationDepartment::query()->where('active', true)->orderBy('name')->get(),
            'teams' => OrganizationTeam::query()->where('active', true)->orderBy('name')->get(),
            'designations' => OrganizationDesignation::query()->where('active', true)->orderBy('name')->get(),
            'employeeTypes' => EmployeeLifecycleService::employeeTypeLabels(),
            'internshipPeriods' => EmployeeLifecycleService::internshipPeriods(),
        ]);
    }

    public function store(Request $request)
    {
        $lifecycle = app(EmployeeLifecycleService::class);

        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'personal_email' => ['required', 'email', 'max:255'],
            'personal_mobile' => ['required', 'string', 'max:32'],
            'official_email' => ['required', 'email', 'max:255', 'unique:users,email', 'unique:employee_profiles,official_email'],
            'password' => ['nullable', 'string', 'min:6'],
            'employee_type' => ['required', 'in:' . implode(',', array_keys(EmployeeLifecycleService::employeeTypeLabels()))],
            'internship_period_months' => ['nullable', 'integer', 'in:' . implode(',', EmployeeLifecycleService::internshipPeriods())],
            'probation_period_months' => ['nullable', 'integer', 'min:1', 'max:36'],
            'department_id' => ['required', 'exists:organization_departments,id'],
            'team_id' => ['nullable', 'exists:organization_teams,id'], // backward-compatible
            'team_ids' => ['nullable', 'array'],
            'team_ids.*' => ['integer', 'exists:organization_teams,id'],
            'designation_id' => ['required', 'exists:organization_designations,id'],
            'joining_date' => ['required', 'date'],
            'profile_image' => ['required', 'file', 'mimes:jpg,jpeg,png,webp', 'max:5120'],
            'pan_card' => ['required', 'file', 'mimes:pdf,jpg,jpeg,png', 'max:5120'],
            'id_card' => ['required', 'file', 'mimes:pdf,jpg,jpeg,png', 'max:5120'],
            'signed_contract' => ['nullable', 'file', 'mimes:pdf,jpg,jpeg,png', 'max:10240'],
            'bank_account_number' => ['required', 'string', 'max:64'],
            'bank_ifsc_code' => ['required', 'string', 'max:32'],
            'bank_name' => ['required', 'string', 'max:255'],
            'salary' => ['required', 'numeric', 'min:0'],
        ]);

        if ($data['employee_type'] === EmployeeLifecycleService::TYPE_INTERN && empty($data['internship_period_months'])) {
            return back()->withErrors([
                'internship_period_months' => 'Internship period is required for intern employees.',
            ])->withInput();
        }
        if ($data['employee_type'] === EmployeeLifecycleService::TYPE_PERMANENT_EMPLOYEE && empty($data['probation_period_months'])) {
            return back()->withErrors([
                'probation_period_months' => 'Probation period is required for permanent employees.',
            ])->withInput();
        }

        $teamIds = collect($data['team_ids'] ?? [])
            ->filter(fn ($v) => $v !== null && $v !== '')
            ->map(fn ($v) => (int) $v)
            ->values();
        if (! empty($data['team_id'])) {
            $teamIds->push((int) $data['team_id']);
        }
        $teamIds = $teamIds->unique()->values();

        $password = $data['password'] ?? Str::random(12);

        $user = User::query()->create([
            'name' => $data['name'],
            'email' => $data['official_email'],
            'password' => Hash::make($password),
            'role' => User::ROLE_EMPLOYEE,
        ]);

        $employeeType = (string) $data['employee_type'];
        $employeePrefix = $employeeType === EmployeeLifecycleService::TYPE_INTERN ? 'EXI' : 'EXE';
        $employeeId = $lifecycle->allocateNextEmployeeId($employeePrefix);

        $panPath = $request->file('pan_card')->store('hrms/employee-documents', 'public');
        $idCardPath = $request->file('id_card')->store('hrms/employee-documents', 'public');
        $dpPath = $request->file('profile_image') ? $request->file('profile_image')->store('hrms/employee-dp', 'public') : null;
        $contractPath = $request->file('signed_contract') ? $request->file('signed_contract')->store('hrms/employee-documents', 'public') : null;
        $joiningDate = Carbon::parse($data['joining_date'])->startOfDay()->toDateString();

        $internshipPeriod = $employeeType === EmployeeLifecycleService::TYPE_INTERN
            ? (int) ($data['internship_period_months'] ?? 0)
            : null;
        $internshipStart = $employeeType === EmployeeLifecycleService::TYPE_INTERN ? $joiningDate : null;
        $internshipEnd = ($employeeType === EmployeeLifecycleService::TYPE_INTERN && $internshipPeriod)
            ? $lifecycle->computeInternshipEndDate($joiningDate, $internshipPeriod)
            : null;

        $probationPeriod = $employeeType === EmployeeLifecycleService::TYPE_PERMANENT_EMPLOYEE
            ? (int) ($data['probation_period_months'] ?? 0)
            : null;
        $probationStart = $employeeType === EmployeeLifecycleService::TYPE_PERMANENT_EMPLOYEE ? $joiningDate : null;
        $probationEnd = ($employeeType === EmployeeLifecycleService::TYPE_PERMANENT_EMPLOYEE && $probationPeriod)
            ? $lifecycle->computeProbationEndDate($joiningDate, $probationPeriod)
            : null;

        $initialBadge = $employeeType === EmployeeLifecycleService::TYPE_INTERN
            ? EmployeeLifecycleService::BADGE_INTERNSHIP_I
            : EmployeeLifecycleService::BADGE_PROBATION_E;

        $profile = EmployeeProfile::query()->create([
            'user_id' => $user->id,
            'employee_id' => $employeeId,
            'department_id' => $data['department_id'],
            // Keep legacy single-team column in sync with the first selection.
            'team_id' => $teamIds->first() ?: null,
            'designation_id' => $data['designation_id'],
            'personal_email' => $data['personal_email'],
            'personal_mobile' => $data['personal_mobile'],
            'official_email' => $data['official_email'],
            'joining_date' => $data['joining_date'],
            'pan_card_path' => $panPath,
            'id_card_path' => $idCardPath,
            'profile_image_path' => $dpPath,
            'signed_contract_path' => $contractPath,
            'bank_account_number' => $data['bank_account_number'],
            'bank_ifsc_code' => $data['bank_ifsc_code'],
            'bank_name' => $data['bank_name'],
            'status' => 'active',
            'employee_type' => $employeeType,
            'employee_badge' => $initialBadge,
            'internship_period_months' => $internshipPeriod,
            'internship_start_date' => $internshipStart,
            'internship_end_date' => $internshipEnd,
            'probation_period_months' => $probationPeriod,
            'probation_start_date' => $probationStart,
            'probation_end_date' => $probationEnd,
            'current_salary' => $data['salary'],
        ]);

        if ($teamIds->isNotEmpty()) {
            $profile->orgTeams()->sync($teamIds->all());
        }

        \App\Models\SalaryHistory::query()->create([
            'employee_profile_id' => $profile->id,
            'effective_date' => $data['joining_date'],
            'amount' => $data['salary'],
            'reason' => 'Initial salary',
        ]);

        return redirect()
            ->route('admin.hrms.employees.show', $profile)
            ->with('status', "Employee created. Temporary password: {$password}");
    }

    public function show(EmployeeProfile $employeeProfile)
    {
        $employeeProfile->load(['user', 'orgDepartment', 'orgDesignation', 'orgTeams']);
        app(EmployeeLifecycleService::class)->synchronizeBadge($employeeProfile);
        $employeeProfile->refresh()->load(['user', 'orgDepartment', 'orgDesignation', 'orgTeams']);

        $yearStart = now()->startOfYear()->toDateString();
        $yearEnd = now()->endOfYear()->toDateString();

        $policies = LeavePolicy::query()->where('active', true)->orderBy('name')->get();
        $summarySvc = app(AttendanceLeaveSummaryService::class);
        $usedByPolicyId = $summarySvc->approvedDaysUsedByPolicy($employeeProfile->id, $yearStart, $yearEnd);

        $balances = $policies->map(function (LeavePolicy $policy) use ($usedByPolicyId, $employeeProfile, $yearStart, $yearEnd) {
            $used = (float) ($usedByPolicyId[$policy->id] ?? 0);
            if (! $policy->is_paid) {
                return [
                    'policy' => $policy,
                    'allowance' => null,
                    'used' => $used,
                    'remaining' => null,
                ];
            }
            // Calculate proportionate allowance based on joining date
            $joiningDate = $employeeProfile->joining_date ?? $employeeProfile->join_date;
            $allowance = app(AttendanceLeaveSummaryService::class)->calculateProportionateAllowance(
                (float) $policy->annual_allowance,
                $joiningDate ? $joiningDate->format('Y-m-d') : null,
                $yearStart,
                $yearEnd
            );
            $remaining = max(0, $allowance - $used);

            return [
                'policy' => $policy,
                'allowance' => $allowance,
                'used' => $used,
                'remaining' => $remaining,
            ];
        });

        $summaryYear = (int) request('summary_year', now()->year);
        $summaryMonth = (int) request('summary_month', now()->month);
        $monthlySummary = $summarySvc->summarizePeriod(
            $employeeProfile,
            Carbon::create($summaryYear, $summaryMonth, 1)->startOfDay(),
            Carbon::create($summaryYear, $summaryMonth, 1)->endOfMonth()
        );

        $projects = ProjectTeamMember::query()
            ->with(['project.client'])
            ->where('employee_profile_id', $employeeProfile->id)
            ->get();

        $assets = AssetAssignment::query()
            ->with(['asset.category'])
            ->where('employee_profile_id', $employeeProfile->id)
            ->whereNull('returned_at')
            ->orderBy('assigned_at', 'desc')
            ->get();

        $documents = HRDocument::query()
            ->where('employee_profile_id', $employeeProfile->id)
            ->orderByDesc('id')
            ->get();

        $uploadedDocs = EmployeeUploadedDocument::query()
            ->where('employee_profile_id', $employeeProfile->id)
            ->orderByDesc('uploaded_at')
            ->get();

        $logs = EmployeeChangeLog::query()
            ->with('changedBy')
            ->where('employee_profile_id', $employeeProfile->id)
            ->orderByDesc('changed_at')
            ->paginate(10);

        $conversionEligibility = app(EmployeeLifecycleService::class)->conversionEligibility($employeeProfile);

        return view('hrms.hr.employees.show', [
            'employee' => $employeeProfile,
            'balances' => $balances,
            'monthlySummary' => $monthlySummary,
            'summaryYear' => $summaryYear,
            'summaryMonth' => $summaryMonth,
            'projects' => $projects,
            'assets' => $assets,
            'documents' => $documents,
            'uploadedDocs' => $uploadedDocs,
            'logs' => $logs,
            'conversionEligibility' => $conversionEligibility,
        ]);
    }

    public function resetPassword(EmployeeProfile $employeeProfile)
    {
        $employeeProfile->load('user');

        $password = Str::random(12);
        $employeeProfile->user->update([
            'password' => Hash::make($password),
        ]);

        return redirect()
            ->route('admin.hrms.employees.show', $employeeProfile)
            ->with('status', "Password reset. Temporary password: {$password}");
    }

    public function edit(EmployeeProfile $employeeProfile)
    {
        $employeeProfile->load(['user', 'orgDepartment', 'orgTeam', 'orgDesignation']);

        return view('hrms.hr.employees.edit', [
            'employee' => $employeeProfile,
            'departments' => OrganizationDepartment::query()->where('active', true)->orderBy('name')->get(),
            'teams' => OrganizationTeam::query()->where('active', true)->orderBy('name')->get(),
            'designations' => OrganizationDesignation::query()->where('active', true)->orderBy('name')->get(),
        ]);
    }

    public function update(Request $request, EmployeeProfile $employeeProfile)
    {
        $employeeProfile->load('user');

        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'personal_email' => ['required', 'email', 'max:255'],
            'personal_mobile' => ['required', 'string', 'max:32'],
            'official_email' => ['required', 'email', 'max:255', 'unique:users,email,' . $employeeProfile->user_id, 'unique:employee_profiles,official_email,' . $employeeProfile->id],
            'department_id' => ['required', 'exists:organization_departments,id'],
            'team_id' => ['nullable', 'exists:organization_teams,id'],
            'designation_id' => ['required', 'exists:organization_designations,id'],
            'joining_date' => ['required', 'date'],
        ]);

        $employeeProfile->user->update([
            'name' => $data['name'],
            'email' => $data['official_email'],
        ]);

        $employeeProfile->update([
            'department_id' => $data['department_id'],
            'team_id' => $data['team_id'] ?? null,
            'designation_id' => $data['designation_id'],
            'personal_email' => $data['personal_email'],
            'personal_mobile' => $data['personal_mobile'],
            'official_email' => $data['official_email'],
            'joining_date' => $data['joining_date'],
        ]);

        return redirect()->route('admin.hrms.employees.show', $employeeProfile)->with('status', 'Employee updated.');
    }

    public function showStatus(EmployeeProfile $employeeProfile)
    {
        return view('hrms.hr.employees.status', ['employee' => $employeeProfile]);
    }

    public function updateStatus(Request $request, EmployeeProfile $employeeProfile)
    {
        $currentStatus = $employeeProfile->status ?? 'active';

        $data = $request->validate([
            'status' => ['required', 'string', 'in:active,inactive,former'],
            'inactive_at' => ['nullable', 'date'],
            'inactive_remarks' => ['nullable', 'string', 'max:2000'],
            'separation_type' => ['nullable', 'string', 'in:resigned,terminated,retired'],
            'separation_effective_at' => ['nullable', 'date'],
            'separation_remarks' => ['nullable', 'string', 'max:2000'],
        ]);

        $newStatus = $data['status'];

        if ($currentStatus === 'former' && $newStatus !== 'former') {
            return back()->withErrors(['status' => 'Status cannot be changed once employee is marked as Former Employee.']);
        }

        if ($newStatus === 'inactive') {
            if (empty($data['inactive_at']) || empty($data['inactive_remarks'])) {
                return back()->withErrors(['inactive_at' => 'Inactive date and remarks are required when setting status to Inactive.']);
            }
            $data['separation_type'] = null;
            $data['separation_effective_at'] = null;
            $data['separation_remarks'] = null;
        } elseif ($newStatus === 'former') {
            if (empty($data['separation_type']) || empty($data['separation_effective_at']) || empty($data['separation_remarks'])) {
                return back()->withErrors(['separation_type' => 'Type, effective date, and remarks are required when setting status to Former Employee.']);
            }
        } elseif ($newStatus === 'active') {
            $data['inactive_at'] = null;
            $data['inactive_remarks'] = null;
        }

        $employeeProfile->update([
            'status' => $newStatus,
            'inactive_at' => $data['inactive_at'] ?? $employeeProfile->inactive_at,
            'inactive_remarks' => $data['inactive_remarks'] ?? $employeeProfile->inactive_remarks,
            'separation_type' => $data['separation_type'] ?? $employeeProfile->separation_type,
            'separation_effective_at' => $data['separation_effective_at'] ?? $employeeProfile->separation_effective_at,
            'separation_remarks' => $data['separation_remarks'] ?? $employeeProfile->separation_remarks,
        ]);

        return redirect()->route('admin.hrms.employees.show', $employeeProfile)->with('status', 'Employment status updated.');
    }

    public function salarySlipsIndex(EmployeeProfile $employeeProfile)
    {
        $employeeProfile->load('user');

        $slips = SalarySlip::query()
            ->with('payrollRun')
            ->where('employee_profile_id', $employeeProfile->id)
            ->orderByDesc('issued_at')
            ->paginate(20);

        return view('hrms.hr.employees.salary_slips', [
            'employee' => $employeeProfile,
            'slips' => $slips,
        ]);
    }

    public function lockedAttendanceUsers()
    {
        $employees = EmployeeProfile::query()
            ->with('user')
            ->whereNotNull('attendance_locked_at')
            ->orderByDesc('attendance_locked_at')
            ->paginate(20);

        return view('hrms.hr.employees.attendance_locks', [
            'employees' => $employees,
        ]);
    }

    public function unlockAttendanceLock(Request $request, EmployeeProfile $employeeProfile)
    {
        if (! $employeeProfile->attendance_locked_at) {
            return back()->with('status', 'Employee is already unlocked.');
        }

        $data = $request->validate([
            'unlock_note' => ['nullable', 'string', 'max:2000'],
        ]);

        $employeeProfile->update([
            'attendance_locked_at' => null,
            'attendance_lock_reason' => null,
            'attendance_unlock_note' => $data['unlock_note'] ?? null,
            'attendance_unlock_by_user_id' => auth()->id(),
            'attendance_unlock_at' => now(),
        ]);

        return redirect()
            ->route('admin.hrms.employees.attendance_locks.index')
            ->with('status', 'Employee login unlocked successfully.');
    }

    public function showSalary(EmployeeProfile $employeeProfile)
    {
        $history = \App\Models\SalaryHistory::query()
            ->where('employee_profile_id', $employeeProfile->id)
            ->orderBy('effective_date')
            ->get();

        return view('hrms.hr.employees.salary', [
            'employee' => $employeeProfile,
            'history' => $history,
        ]);
    }

    public function amendSalary(Request $request, EmployeeProfile $employeeProfile)
    {
        $data = $request->validate([
            'effective_date' => ['required', 'date'],
            'amount' => ['required', 'numeric', 'min:0'],
            'reason' => ['nullable', 'string', 'max:2000'],
        ]);

        \App\Models\SalaryHistory::query()->create([
            'employee_profile_id' => $employeeProfile->id,
            'effective_date' => $data['effective_date'],
            'amount' => $data['amount'],
            'reason' => $data['reason'] ?? null,
        ]);

        $employeeProfile->update(['current_salary' => $data['amount']]);

        return redirect()->route('admin.hrms.employees.salary.show', $employeeProfile)->with('status', 'Salary amended.');
    }

    public function convertToPermanent(Request $request, EmployeeProfile $employeeProfile)
    {
        $data = $request->validate([
            'effective_date' => ['required', 'date'],
            'probation_period_months' => ['required', 'integer', 'min:1', 'max:36'],
        ]);

        /** @var User $actor */
        $actor = Auth::user();
        $lifecycle = app(EmployeeLifecycleService::class);
        $eligibility = $lifecycle->conversionEligibility($employeeProfile);
        if (! $eligibility['allowed']) {
            return back()->withErrors(['conversion' => (string) $eligibility['reason']]);
        }

        $oldType = (string) $employeeProfile->employee_type;
        $oldBadge = (string) $employeeProfile->employee_badge;
        $oldProbation = (string) ($employeeProfile->probation_period_months ?? '');
        $oldEmployeeId = (string) $employeeProfile->employee_id;

        $lifecycle->convertInternToPermanent(
            $employeeProfile,
            (int) $data['probation_period_months'],
            (string) $data['effective_date']
        );
        $employeeProfile->refresh();

        EmployeeChangeLog::query()->create([
            'employee_profile_id' => $employeeProfile->id,
            'field' => 'lifecycle_conversion',
            'old_value' => $oldType,
            'new_value' => (string) $employeeProfile->employee_type,
            'meta' => [
                'old_employee_id' => $oldEmployeeId,
                'new_employee_id' => (string) $employeeProfile->employee_id,
                'old_badge' => $oldBadge,
                'new_badge' => (string) $employeeProfile->employee_badge,
                'old_probation_period_months' => $oldProbation,
                'new_probation_period_months' => $employeeProfile->probation_period_months,
                'effective_date' => $data['effective_date'],
                'converted_to_permanent_at' => optional($employeeProfile->converted_to_permanent_at)->toDateTimeString(),
            ],
            'changed_by_user_id' => $actor->id,
            'changed_at' => now(),
        ]);

        return redirect()
            ->route('admin.hrms.employees.show', $employeeProfile)
            ->with(
                'status',
                'Intern converted to probation (Permanent Employee). Employee ID changed from '.$oldEmployeeId.' to '.$employeeProfile->employee_id.'.'
            );
    }
}
