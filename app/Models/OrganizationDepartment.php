<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class OrganizationDepartment extends Model
{
    protected $fillable = ['name', 'code', 'active'];

    protected $casts = ['active' => 'bool'];

    public function teams(): HasMany
    {
        return $this->hasMany(OrganizationTeam::class, 'department_id');
    }

    public function designations(): HasMany
    {
        return $this->hasMany(OrganizationDesignation::class);
    }
}

