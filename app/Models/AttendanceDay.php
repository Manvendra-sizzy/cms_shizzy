<?php

namespace App\Models;

use App\Modules\HRMS\Employees\Models\EmployeeProfile;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AttendanceDay extends Model
{
    protected $fillable = [
        'employee_profile_id',
        'work_date',
        'work_fraction',
        'punch_in_at',
        'punch_out_at',
    ];

    protected $casts = [
        'work_date' => 'date',
        'work_fraction' => 'decimal:2',
        'punch_in_at' => 'datetime',
        'punch_out_at' => 'datetime',
    ];

    public function employeeProfile(): BelongsTo
    {
        return $this->belongsTo(EmployeeProfile::class);
    }
}
