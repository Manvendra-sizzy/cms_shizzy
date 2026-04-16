<?php

namespace App\Modules\HRMS\Leaves\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class LeavePolicy extends Model
{
    protected $fillable = [
        'name',
        'code',
        'annual_allowance',
        'carry_forward',
        'max_carry_forward',
        'requires_approval',
        'active',
        'is_paid',
    ];

    protected $casts = [
        'carry_forward' => 'bool',
        'requires_approval' => 'bool',
        'active' => 'bool',
        'is_paid' => 'bool',
    ];

    public function leaveRequests(): HasMany
    {
        return $this->hasMany(LeaveRequest::class);
    }
}

