<?php

namespace App\Models;

use App\Modules\HRMS\Employees\Models\EmployeeProfile;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EmployeeEmergencyContact extends Model
{
    protected $fillable = [
        'employee_profile_id',
        'slot',
        'name',
        'phone',
        'relation',
    ];

    protected $casts = [
        'slot' => 'int',
    ];

    public function employeeProfile(): BelongsTo
    {
        return $this->belongsTo(EmployeeProfile::class);
    }
}

