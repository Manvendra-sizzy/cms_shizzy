<?php

namespace App\Modules\Projects\Models;

use App\Models\User;
use App\Modules\HRMS\Employees\Models\EmployeeProfile;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProjectTeamMember extends Model
{
    protected $fillable = [
        'project_id',
        'employee_profile_id',
        'role_title',
        'added_by_user_id',
    ];

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    public function employeeProfile(): BelongsTo
    {
        return $this->belongsTo(EmployeeProfile::class);
    }

    public function addedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'added_by_user_id');
    }
}

