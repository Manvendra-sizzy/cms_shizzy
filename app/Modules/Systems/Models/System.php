<?php

namespace App\Modules\Systems\Models;

use App\Modules\Projects\Models\Project;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class System extends Model
{
    protected $table = 'systems';

    protected $fillable = [
        'project_id',
        'support_scope_id',
        'system_name',
        'system_type',
        'description',
        'live_url',
        'admin_url',
        'repository_link',
        'tech_stack',
        'status',
        'support_start_date',
        'support_end_date',
        'support_status',
    ];

    protected $casts = [
        'support_start_date' => 'date',
        'support_end_date' => 'date',
    ];

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class, 'project_id');
    }

    public function supportScope(): BelongsTo
    {
        return $this->belongsTo(SupportScope::class, 'support_scope_id');
    }

    public function infrastructureResources(): BelongsToMany
    {
        return $this->belongsToMany(
            InfrastructureResource::class,
            'system_infrastructure_resources',
            'system_id',
            'infrastructure_resource_id'
        )->withTimestamps();
    }

    public function supportExtensions(): HasMany
    {
        return $this->hasMany(SupportExtension::class, 'system_id');
    }

    public function documentation(): HasOne
    {
        return $this->hasOne(SystemDocumentation::class, 'system_id');
    }

    public function developmentLogs(): HasMany
    {
        return $this->hasMany(SystemDevelopmentLog::class, 'system_id');
    }
}
