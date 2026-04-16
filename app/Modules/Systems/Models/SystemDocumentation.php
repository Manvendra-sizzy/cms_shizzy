<?php

namespace App\Modules\Systems\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SystemDocumentation extends Model
{
    protected $fillable = [
        'system_id',
        'overview',
        'architecture',
        'infrastructure_mapping',
        'deployment_process',
        'recovery_instructions',
        'external_integrations',
        'notes',
    ];

    public function system(): BelongsTo
    {
        return $this->belongsTo(System::class, 'system_id');
    }
}
