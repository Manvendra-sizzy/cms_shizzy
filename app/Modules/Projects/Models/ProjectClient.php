<?php

namespace App\Modules\Projects\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ProjectClient extends Model
{
    protected $table = 'project_clients';

    protected $fillable = [
        'name',
        'email',
        'phone',
        'address',
        'active',
    ];

    protected $casts = [
        'active' => 'bool',
    ];

    public function projects(): HasMany
    {
        return $this->hasMany(Project::class, 'project_client_id');
    }
}

