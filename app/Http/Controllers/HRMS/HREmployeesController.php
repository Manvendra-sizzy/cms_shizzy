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
use Illuminate\Support\Facades\DB;
use Illuminate\Http\Request;
use App\Modules\HRMS\Leaves\Models\LeavePolicy;
use App\Modules\HRMS\Payroll\Models\SalarySlip;
use App\Services\HRMS\AttendanceLeaveSummaryService;
use Carbon\Carbon;
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
        ]);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'codename' => ['required', 'string', 'max:120', 'regex:/^[A-Za-z]+$/', 'unique:users,codename'],
            'personal_email' => ['required', 'email', 'max:255'],
            'personal_mobile' => ['required', 'string', 'max:32'],
            'official_email' => ['required', 'email', 'max:255', 'unique:users,email', 'unique:employee_profiles,official_email'],
            'password' => ['nullable', 'string', 'min:6'],
            'department_id' => ['required', 'exists:organization_departments,id'],
            'team_id' => ['nullable', 'exists:organization_teams,id'], // backward-compatible
            'team_ids' => ['nullable', 'array'],
            'team_ids.*' => ['integer', 'exists:organization_teams,id'],
            'designation_id' => ['required', 'exists:organization_designations,id'],
            'joining_date' => ['required', 'date'],
            'profile_image' => ['nullable', 'file', 'mimes:jpg,jpeg,png,webp', 'max:5120'],
            'pan_card' => ['required', 'file', 'mimes:pdf,jpg,jpeg,png', 'max:5120'],
            'id_card' => ['required', 'file', 'mimes:pdf,jpg,jpeg,png', 'max:5120'],
            'signed_contract' => ['nullable', 'file', 'mimes:pdf,jpg,jpeg,png', 'max:10240'],
            'bank_account_number' => ['required', 'string', 'max:64'],
            'bank_ifsc_code' => ['required', 'string', 'max:32'],
            'bank_name' => ['required', 'string', 'max:255'],
            'salary' => ['required', 'numeric', 'min:0'],
        ]);

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
            'codename' => trim((string) $data['codename']),
            'password' => Hash::make($password),
            'role' => User::ROLE_EMPLOYEE,
        ]);

        $nextNumber = (int) (DB::table('employee_profiles')
            ->selectRaw("COALESCE(MAX(CAST(SUBSTRING(employee_id, 4) AS UNSIGNED)), 0) as max_num")
            ->value('max_num')) + 1;
        $employeeId = 'EXE' . str_pad((string) $nextNumber, 3, '0', STR_PAD_LEFT);

        $panPath = $request->file('pan_card')->store('hrms/employee-documents', 'public');
        $idCardPath = $request->file('id_card')->store('hrms/employee-documents', 'public');
        $dpPath = $request->file('profile_image') ? $request->file('profile_image')->store('hrms/employee-dp', 'public') : null;
        $contractPath = $request->file('signed_contract') ? $request->file('signed_contract')->store('hrms/employee-documents', 'public') : null;

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

        $yearStart = now()->startOfYear()->toDateString();
        $yearEnd = now()->endOfYear()->toDateString();

        $policies = LeavePolicy::query()->where('active', true)->orderBy('name')->get();
        $summarySvc = app(AttendanceLeaveSummaryService::class);
        $usedByPolicyId = $summarySvc->approvedDaysUsedByPolicy($employeeProfile->id, $yearStart, $yearEnd);

        $balances = $policies->map(function (LeavePolicy $policy) use ($usedByPolicyId) {
            $used = (float) ($usedByPolicyId[$policy->id] ?? 0);
            if (! $policy->is_paid) {
                return [
                    'policy' => $policy,
                    'allowance' => null,
                    'used' => $used,
                    'remaining' => null,
                ];
            }
            $allowance = (float) $policy->annual_allowance;
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
}
