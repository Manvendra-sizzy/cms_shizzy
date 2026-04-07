<?php

namespace App\Modules\Systems\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class SupportScope extends Model
{
    protected $fillable = [
        'scope_name',
        'description',
        'included_services',
        'excluded_services',
        'sla_response_time',
        'active',
    ];

    protected $casts = [
        'active' => 'bool',
    ];

    public function systems(): HasMany
    {
        return $this->hasMany(System::class, 'support_scope_id');
    }
}
