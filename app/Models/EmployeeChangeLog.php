<?php

namespace App\Models;

use App\Models\User;
use App\Modules\HRMS\Employees\Models\EmployeeProfile;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EmployeeChangeLog extends Model
{
    protected $fillable = [
        'employee_profile_id',
        'field',
        'old_value',
        'new_value',
        'meta',
        'changed_by_user_id',
        'changed_at',
    ];

    protected $casts = [
        'meta' => 'array',
        'changed_at' => 'datetime',
    ];

    public function employeeProfile(): BelongsTo
    {
        return $this->belongsTo(EmployeeProfile::class);
    }

    public function changedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'changed_by_user_id');
    }
}

