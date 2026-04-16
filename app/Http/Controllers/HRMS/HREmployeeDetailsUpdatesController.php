<?php

namespace App\Http\Controllers\HRMS;

use App\Http\Controllers\Controller;
use App\Models\EmployeeChangeLog;
use App\Models\EmployeeEmergencyContact;
use App\Models\EmployeeUploadedDocument;
use App\Models\OrganizationDepartment;
use App\Models\OrganizationDesignation;
use App\Models\OrganizationTeam;
use App\Modules\HRMS\Employees\Models\EmployeeProfile;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class HREmployeeDetailsUpdatesController extends Controller
{
    public function index(EmployeeProfile $employeeProfile)
    {
        $employeeProfile->load(['user', 'orgDepartment', 'orgTeam', 'orgTeams', 'orgDesignation', 'reportingManager.user']);

        $logs = EmployeeChangeLog::query()
            ->with('changedBy')
            ->where('employee_profile_id', $employeeProfile->id)
            ->orderByDesc('changed_at')
            ->limit(80)
            ->get();

        return view('hrms.hr.employees.update_details', [
            'employee' => $employeeProfile,
            'departments' => OrganizationDepartment::query()->where('active', true)->orderBy('name')->get(),
            'teams' => OrganizationTeam::query()->where('active', true)->orderBy('name')->get(),
            'designations' => OrganizationDesignation::query()->where('active', true)->orderBy('name')->get(),
            'managers' => EmployeeProfile::query()->with('user')->orderBy('employee_id')->get(),
            'logs' => $logs,
        ]);
    }

    public function update(Request $request, EmployeeProfile $employeeProfile)
    {
        $employeeProfile->load(['user', 'orgDepartment', 'orgTeam', 'orgTeams', 'orgDesignation']);

        $data = $request->validate([
            'field' => ['required', 'string', 'max:64'],
            'value' => ['nullable'],
        ]);

        /** @var \App\Models\User $user */
        $user = Auth::user();

        $field = (string) $data['field'];
        $value = $data['value'];

        $meta = [];
        $old = null;
        $new = null;

        switch ($field) {
            case 'name':
                $old = (string) ($employeeProfile->user->name ?? '');
                $new = (string) $value;
                $request->validate(['value' => ['required', 'string', 'max:255']]);
                $employeeProfile->user->update(['name' => $new]);
                break;
            case 'personal_email':
                $old = (string) ($employeeProfile->personal_email ?? '');
                $new = (string) $value;
                $request->validate(['value' => ['required', 'email', 'max:255']]);
                $employeeProfile->update(['personal_email' => $new]);
                break;
            case 'personal_mobile':
                $old = (string) ($employeeProfile->personal_mobile ?? '');
                $new = (string) $value;
                $request->validate(['value' => ['required', 'string', 'max:32']]);
                $employeeProfile->update(['personal_mobile' => $new]);
                break;
            case 'official_email':
                $old = (string) ($employeeProfile->official_email ?? '');
                $new = (string) $value;
                $request->validate(['value' => ['required', 'email', 'max:255']]);
                $employeeProfile->update(['official_email' => $new]);
                $employeeProfile->user->update(['email' => $new]);
                break;
            case 'department_id':
                $request->validate(['value' => ['required', 'exists:organization_departments,id']]);
                $old = (string) ($employeeProfile->department_id ?? '');
                $new = (string) $value;
                $meta = [
                    'old_label' => $employeeProfile->orgDepartment?->name,
                    'new_label' => OrganizationDepartment::query()->find((int) $value)?->name,
                ];
                $employeeProfile->update(['department_id' => (int) $value]);
                break;
            case 'team_id':
                $request->validate(['value' => ['nullable', 'exists:organization_teams,id']]);
                $old = (string) ($employeeProfile->team_id ?? '');
                $new = (string) ($value ?? '');
                $meta = [
                    'old_label' => $employeeProfile->orgTeam?->name,
                    'new_label' => $value ? OrganizationTeam::query()->find((int) $value)?->name : null,
                ];
                $employeeProfile->update(['team_id' => $value ? (int) $value : null]);
                break;
            case 'team_ids':
                $request->validate([
                    'value' => ['nullable', 'array'],
                    'value.*' => ['integer', 'exists:organization_teams,id'],
                ]);
                $oldIds = ($employeeProfile->orgTeams ?? collect())->pluck('id')->map(fn ($v) => (int) $v)->values();
                $newIds = collect($value ?? [])->map(fn ($v) => (int) $v)->unique()->values();

                $old = $oldIds->implode(',');
                $new = $newIds->implode(',');

                $meta = [
                    'old_label' => OrganizationTeam::query()->whereIn('id', $oldIds->all())->orderBy('name')->pluck('name')->implode(', ') ?: null,
                    'new_label' => OrganizationTeam::query()->whereIn('id', $newIds->all())->orderBy('name')->pluck('name')->implode(', ') ?: null,
                ];

                $employeeProfile->orgTeams()->sync($newIds->all());
                // Keep legacy single-team column aligned for screens that still use it.
                $employeeProfile->update(['team_id' => $newIds->first() ?: null]);
                break;
            case 'designation_id':
                $request->validate(['value' => ['required', 'exists:organization_designations,id']]);
                $old = (string) ($employeeProfile->designation_id ?? '');
                $new = (string) $value;
                $meta = [
                    'old_label' => $employeeProfile->orgDesignation?->name,
                    'new_label' => OrganizationDesignation::query()->find((int) $value)?->name,
                ];
                $employeeProfile->update(['designation_id' => (int) $value]);
                break;
            case 'joining_date':
                $request->validate(['value' => ['required', 'date']]);
                $old = optional($employeeProfile->joining_date)->toDateString();
                $new = (string) $value;
                $employeeProfile->update(['joining_date' => $new]);
                break;
            case 'bank_account_number':
                $request->validate(['value' => ['required', 'string', 'max:64']]);
                $old = (string) ($employeeProfile->bank_account_number ?? '');
                $new = (string) $value;
                $employeeProfile->update(['bank_account_number' => $new]);
                break;
            case 'bank_ifsc_code':
                $request->validate(['value' => ['required', 'string', 'max:32']]);
                $old = (string) ($employeeProfile->bank_ifsc_code ?? '');
                $new = (string) $value;
                $employeeProfile->update(['bank_ifsc_code' => $new]);
                break;
            case 'bank_name':
                $request->validate(['value' => ['required', 'string', 'max:255']]);
                $old = (string) ($employeeProfile->bank_name ?? '');
                $new = (string) $value;
                $employeeProfile->update(['bank_name' => $new]);
                break;
            case 'is_remote':
                $request->validate(['value' => ['required', 'in:0,1']]);
                $old = (string) ((int) ($employeeProfile->is_remote ?? false));
                $new = (string) ((int) ((string) $value === '1'));
                $employeeProfile->update(['is_remote' => ((string) $value === '1')]);
                break;
            case 'reporting_manager_employee_profile_id':
                $request->validate(['value' => ['nullable', 'exists:employee_profiles,id']]);
                $old = (string) ($employeeProfile->reporting_manager_employee_profile_id ?? '');
                $new = (string) ($value ?? '');
                $meta = [
                    'old_label' => $employeeProfile->reportingManager?->user?->name,
                    'new_label' => $value ? EmployeeProfile::query()->with('user')->find((int) $value)?->user?->name : null,
                ];
                $employeeProfile->update(['reporting_manager_employee_profile_id' => $value ? (int) $value : null]);
                break;
            case 'profile_image':
                $request->validate([
                    'file' => ['required', 'file', 'mimes:jpg,jpeg,png,webp', 'max:5120'],
                ]);
                $old = (string) ($employeeProfile->profile_image_path ?? '');
                $path = $request->file('file')->store('hrms/employee-dp', 'public');
                $employeeProfile->update(['profile_image_path' => $path]);
                $new = (string) $path;
                break;
            case 'signed_contract':
                $request->validate([
                    'file' => ['required', 'file', 'mimes:pdf,jpg,jpeg,png', 'max:10240'],
                ]);
                $old = (string) ($employeeProfile->signed_contract_path ?? '');
                $path = $request->file('file')->store('hrms/employee-documents', 'public');
                $employeeProfile->update(['signed_contract_path' => $path]);
                $new = (string) $path;
                break;
            case 'upload_document':
                $request->validate([
                    'doc_title' => ['required', 'string', 'max:255'],
                    'file' => ['required', 'file', 'mimes:pdf,jpg,jpeg,png', 'max:10240'],
                ]);
                $path = $request->file('file')->store('hrms/employee-documents', 'public');
                EmployeeUploadedDocument::query()->create([
                    'employee_profile_id' => $employeeProfile->id,
                    'title' => (string) $request->input('doc_title'),
                    'file_path' => $path,
                    'uploaded_by_user_id' => $user->id,
                    'uploaded_at' => now(),
                ]);
                $old = null;
                $new = (string) $request->input('doc_title');
                $meta = ['file_path' => $path];
                $field = 'uploaded_document';
                break;
            default:
                return back()->withErrors(['field' => 'Unsupported field.']);
        }

        if ((string) $old !== (string) $new) {
            EmployeeChangeLog::query()->create([
                'employee_profile_id' => $employeeProfile->id,
                'field' => $field,
                'old_value' => $old,
                'new_value' => $new,
                'meta' => $meta ?: null,
                'changed_by_user_id' => $user->id,
                'changed_at' => now(),
            ]);
        }

        return redirect()
            ->route('admin.hrms.employees.update_details.index', $employeeProfile)
            ->with('status', 'Employee detail updated.');
    }
}

