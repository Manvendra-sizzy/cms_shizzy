<?php

namespace App\Modules\Systems\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class InfrastructureResource extends Model
{
    protected $fillable = [
        'resource_type',
        'name',
        'vendor',
        'description',
        'access_url',
        'status',
    ];

    public function systems(): BelongsToMany
    {
        return $this->belongsToMany(
            System::class,
            'system_infrastructure_resources',
            'infrastructure_resource_id',
            'system_id'
        )->withTimestamps();
    }
}
