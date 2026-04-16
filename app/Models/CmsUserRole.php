<?php

namespace App\Models;

use App\Modules\Projects\Models\Project;
use App\Modules\Systems\Models\System as SystemModel;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class CmsUserRole extends Model
{
    protected $table = 'cms_user_roles';

    protected $fillable = [
        'user_id',
        'cms_role_id',
        'all_projects',
        'active',
    ];

    protected $casts = [
        'all_projects' => 'bool',
        'active' => 'bool',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function role(): BelongsTo
    {
        return $this->belongsTo(CmsRole::class, 'cms_role_id');
    }

    public function projects(): BelongsToMany
    {
        return $this->belongsToMany(
            Project::class,
            'cms_user_role_projects',
            'cms_user_role_id',
            'project_id'
        )->withTimestamps();
    }

    public function systems(): BelongsToMany
    {
        return $this->belongsToMany(
            SystemModel::class,
            'cms_user_role_systems',
            'cms_user_role_id',
            'system_id'
        )->withTimestamps();
    }
}
