<?php

namespace App\Models;

use App\Models\User;
use App\Modules\HRMS\Employees\Models\EmployeeProfile;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EmployeeUploadedDocument extends Model
{
    protected $fillable = [
        'employee_profile_id',
        'title',
        'file_path',
        'uploaded_by_user_id',
        'uploaded_at',
    ];

    protected $casts = [
        'uploaded_at' => 'datetime',
    ];

    public function employeeProfile(): BelongsTo
    {
        return $this->belongsTo(EmployeeProfile::class);
    }

    public function uploadedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'uploaded_by_user_id');
    }
}

