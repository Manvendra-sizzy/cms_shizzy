<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class CmsRole extends Model
{
    protected $table = 'cms_roles';

    protected $fillable = [
        'key',
        'name',
        'active',
    ];

    protected $casts = [
        'active' => 'bool',
    ];

    public function userRoles(): HasMany
    {
        return $this->hasMany(CmsUserRole::class, 'cms_role_id');
    }
}
