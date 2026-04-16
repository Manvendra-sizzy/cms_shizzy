<?php

namespace App\Modules\HRMS\Onboarding\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class OnboardingContractEvidenceLog extends Model
{
    protected $fillable = [
        'employee_onboarding_id',
        'event_type',
        'event_hash',
        'previous_hash',
        'ip_address',
        'user_agent',
        'payload',
    ];

    protected $casts = [
        'payload' => 'array',
    ];

    public function onboarding(): BelongsTo
    {
        return $this->belongsTo(EmployeeOnboarding::class, 'employee_onboarding_id');
    }
}
