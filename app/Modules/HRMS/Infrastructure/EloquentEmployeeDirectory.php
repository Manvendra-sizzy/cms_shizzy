<?php

namespace App\Modules\HRMS\Infrastructure;

use App\Modules\HRMS\Employees\Models\EmployeeProfile;
use App\Modules\Projects\Contracts\EmployeeDirectoryContract;
use Illuminate\Support\Collection;

class EloquentEmployeeDirectory implements EmployeeDirectoryContract
{
    public function getActiveEmployees(): Collection
    {
        return EmployeeProfile::query()
            ->with('user')
            ->where('status', 'active')
            ->orderBy('employee_id')
            ->get();
    }
}
