<?php

namespace App\Http\Controllers\CMS;

use App\Http\Controllers\Controller;
use App\Models\CmsModule;
use App\Models\OrganizationDepartment;
use App\Models\User;
use Illuminate\Support\Facades\Route;

class AdminDashboardController extends Controller
{
    public function index()
    {
        $departments = OrganizationDepartment::query()
            ->where('active', true)
            ->with([
                'teams' => static function ($q) {
                    $q->where('active', true)->orderBy('name');
                },
            ])
            ->orderBy('name')
            ->get();

        return view('cms.admin.dashboard', [
            'usersCount' => User::query()->count(),
            'adminsCount' => User::query()->where('role', User::ROLE_ADMIN)->count(),
            'employeesCount' => User::query()->where('role', User::ROLE_EMPLOYEE)->count(),
            'modulesCount' => CmsModule::query()->count(),
            'departments' => $departments,
            'projectsUrl' => Route::has('projects.index') ? route('projects.index') : null,
            'assetsUrl' => Route::has('assets.index') ? route('assets.index') : null,
            'systemsUrl' => Route::has('systems.index') ? route('systems.index') : null,
        ]);
    }
}
