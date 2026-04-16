<?php

namespace App\Models;

use App\Modules\HRMS\Employees\Models\EmployeeProfile;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EmployeeAttendanceReminder extends Model
{
    public const TYPE_MISSED_PUNCH_OUT = 'missed_punch_out';

    protected $fillable = [
        'employee_profile_id',
        'work_date',
        'type',
        'sent_at',
    ];

    protected $casts = [
        'work_date' => 'date',
        'sent_at' => 'datetime',
    ];

    public function employeeProfile(): BelongsTo
    {
        return $this->belongsTo(EmployeeProfile::class);
    }
}

