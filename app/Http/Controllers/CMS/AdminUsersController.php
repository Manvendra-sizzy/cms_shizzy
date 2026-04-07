<?php

namespace App\Http\Controllers\CMS;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreEmployeeRoleAssignmentRequest;
use App\Models\CmsRole;
use App\Models\CmsUserRole;
use App\Models\CmsModule;
use App\Models\User;
use App\Modules\HRMS\Employees\Models\EmployeeProfile;
use App\Modules\Systems\Models\System as SystemModel;

class AdminUsersController extends Controller
{
    private const ROLE_MODULE_MAP = [
        'project_manager' => ['projects'],
        'finance_manager' => ['projects', 'assets'],
        'hr_manager' => ['hrms'],
        'developer' => ['systems'],
    ];

    public function index()
    {
        $users = User::query()
            ->with([
                'appRoleAssignments.role',
                'appRoleAssignments.systems:id,system_name',
            ])
            ->orderByDesc('id')
            ->paginate(20);

        return view('cms.admin.users.index', ['users' => $users]);
    }

    public function create()
    {
        $employees = EmployeeProfile::query()
            ->with('user:id,name,email')
            ->whereNotNull('user_id')
            ->orderBy('employee_id')
            ->get();
        $roles = CmsRole::query()->where('active', true)->orderBy('name')->get();
        $systems = SystemModel::query()->orderBy('system_name')->get(['id', 'system_name']);

        return view('cms.admin.users.create', [
            'employees' => $employees,
            'roles' => $roles,
            'systems' => $systems,
        ]);
    }

    public function store(StoreEmployeeRoleAssignmentRequest $request)
    {
        $data = $request->validated();
        $employee = EmployeeProfile::query()
            ->with('user')
            ->whereKey((int) $data['employee_profile_id'])
            ->firstOrFail();
        if (! $employee->user) {
            return back()->withErrors(['employee_profile_id' => 'Selected employee has no user account.'])->withInput();
        }

        $role = CmsRole::query()->where('key', $data['role_key'])->where('active', true)->firstOrFail();
        $allSystems = (bool) ($data['all_systems'] ?? false);
        $systemIds = array_values(array_unique(array_map('intval', $data['system_ids'] ?? [])));

        $assignment = CmsUserRole::query()->updateOrCreate([
            'user_id' => $employee->user_id,
            'cms_role_id' => $role->id,
        ], [
            'all_projects' => $role->key === 'developer' ? $allSystems : false,
            'active' => (bool) ($data['active'] ?? true),
        ]);
        $assignment->projects()->sync([]);
        $assignment->systems()->sync($role->key === 'developer' && ! $allSystems ? $systemIds : []);

        $moduleKeys = self::ROLE_MODULE_MAP[$role->key] ?? [];
        if ($moduleKeys !== []) {
            $moduleIds = CmsModule::query()
                ->whereIn('key', $moduleKeys)
                ->where('active', true)
                ->pluck('id')
                ->all();
            foreach ($moduleIds as $moduleId) {
                $exists = $employee->user->modules()->where('cms_modules.id', $moduleId)->exists();
                if (! $exists) {
                    $employee->user->modules()->attach($moduleId);
                }
            }
        }

        return redirect()
            ->route('admin.users.index')
            ->with('status', 'Role assigned successfully.');
    }
}
