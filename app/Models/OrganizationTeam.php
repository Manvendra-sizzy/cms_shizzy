<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class OrganizationTeam extends Model
{
    protected $fillable = ['department_id', 'name', 'code', 'active'];

    protected $casts = ['active' => 'bool'];

    public function department(): BelongsTo
    {
        return $this->belongsTo(OrganizationDepartment::class, 'department_id');
    }

    public function designations(): HasMany
    {
        return $this->hasMany(OrganizationDesignation::class);
    }
}

