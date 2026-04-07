<?php

namespace App\Modules\HRMS\Documents\Models;

use App\Models\User;
use App\Modules\HRMS\Employees\Models\EmployeeProfile;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class HRDocument extends Model
{
    protected $table = 'hr_documents';

    protected $fillable = [
        'employee_profile_id',
        'issued_by_user_id',
        'type',
        'title',
        'body',
        'file_path',
        'document_hash',
        'meta',
        'issued_at',
    ];

    protected $casts = [
        'meta' => 'array',
        'issued_at' => 'datetime',
    ];

    public function employeeProfile(): BelongsTo
    {
        return $this->belongsTo(EmployeeProfile::class);
    }

    public function issuedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'issued_by_user_id');
    }
}

