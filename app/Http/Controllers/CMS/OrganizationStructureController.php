<?php

namespace App\Http\Controllers\CMS;

use App\Http\Controllers\Controller;
use App\Models\OrganizationDepartment;
use App\Models\OrganizationDesignation;
use App\Models\OrganizationTeam;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class OrganizationStructureController extends Controller
{
    public function index()
    {
        return view('cms.organization.dashboard');
    }

    public function departmentsIndex()
    {
        return view('cms.organization.departments.index', [
            'departments' => OrganizationDepartment::query()->orderBy('name')->get(),
        ]);
    }

    public function departmentsEdit(OrganizationDepartment $department)
    {
        return view('cms.organization.departments.edit', [
            'department' => $department,
        ]);
    }

    public function storeDepartment(Request $request)
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255', 'unique:organization_departments,name'],
            'code' => ['required', 'string', 'max:32', 'unique:organization_departments,code'],
        ]);
        OrganizationDepartment::query()->create($data + ['active' => true]);
        return redirect()->route('admin.organization.departments.index')->with('status', 'Department created.');
    }

    public function updateDepartment(Request $request, OrganizationDepartment $department)
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255', Rule::unique('organization_departments', 'name')->ignore($department->id)],
            'code' => ['required', 'string', 'max:32', Rule::unique('organization_departments', 'code')->ignore($department->id)],
            'active' => ['nullable'],
        ]);
        $department->update([
            'name' => $data['name'],
            'code' => $data['code'],
            'active' => (bool)($data['active'] ?? false),
        ]);
        return redirect()->route('admin.organization.departments.index')->with('status', 'Department updated.');
    }

    public function destroyDepartment(OrganizationDepartment $department)
    {
        $department->delete();
        return redirect()->route('admin.organization.departments.index')->with('status', 'Department deleted.');
    }

    public function teamsIndex()
    {
        return view('cms.organization.teams.index', [
            'departments' => OrganizationDepartment::query()->where('active', true)->orderBy('name')->get(),
            'teams' => OrganizationTeam::query()->with('department')->orderBy('name')->get(),
        ]);
    }

    public function teamsEdit(OrganizationTeam $team)
    {
        return view('cms.organization.teams.edit', [
            'departments' => OrganizationDepartment::query()->where('active', true)->orderBy('name')->get(),
            'team' => $team->load('department'),
        ]);
    }

    public function storeTeam(Request $request)
    {
        $data = $request->validate([
            'department_id' => ['required', 'exists:organization_departments,id'],
            'name' => ['required', 'string', 'max:255'],
            'code' => [
                'required',
                'string',
                'max:32',
                Rule::unique('organization_teams', 'code')->where(fn ($q) => $q->where('department_id', $request->input('department_id'))),
            ],
        ]);
        OrganizationTeam::query()->create($data + ['active' => true]);
        return redirect()->route('admin.organization.teams.index')->with('status', 'Team created.');
    }

    public function updateTeam(Request $request, OrganizationTeam $team)
    {
        $data = $request->validate([
            'department_id' => ['required', 'exists:organization_departments,id'],
            'name' => ['required', 'string', 'max:255'],
            'code' => [
                'required',
                'string',
                'max:32',
                Rule::unique('organization_teams', 'code')
                    ->where(fn ($q) => $q->where('department_id', $request->input('department_id')))
                    ->ignore($team->id),
            ],
            'active' => ['nullable'],
        ]);
        $team->update([
            'department_id' => $data['department_id'],
            'name' => $data['name'],
            'code' => $data['code'],
            'active' => (bool)($data['active'] ?? false),
        ]);
        return redirect()->route('admin.organization.teams.index')->with('status', 'Team updated.');
    }

    public function destroyTeam(OrganizationTeam $team)
    {
        $team->delete();
        return redirect()->route('admin.organization.teams.index')->with('status', 'Team deleted.');
    }

    public function designationsIndex()
    {
        return view('cms.organization.designations.index', [
            'designations' => OrganizationDesignation::query()->orderBy('name')->get(),
        ]);
    }

    public function designationsEdit(OrganizationDesignation $designation)
    {
        return view('cms.organization.designations.edit', [
            'designation' => $designation,
        ]);
    }

    public function storeDesignation(Request $request)
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'code' => ['required', 'string', 'max:32', 'unique:organization_designations,code'],
        ]);
        OrganizationDesignation::query()->create($data + ['active' => true]);
        return redirect()->route('admin.organization.designations.index')->with('status', 'Designation created.');
    }

    public function updateDesignation(Request $request, OrganizationDesignation $designation)
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'code' => ['required', 'string', 'max:32', Rule::unique('organization_designations', 'code')->ignore($designation->id)],
            'active' => ['nullable'],
        ]);
        $designation->update([
            'name' => $data['name'],
            'code' => $data['code'],
            'active' => (bool)($data['active'] ?? false),
        ]);
        return redirect()->route('admin.organization.designations.index')->with('status', 'Designation updated.');
    }

    public function destroyDesignation(OrganizationDesignation $designation)
    {
        $designation->delete();
        return redirect()->route('admin.organization.designations.index')->with('status', 'Designation deleted.');
    }
}

