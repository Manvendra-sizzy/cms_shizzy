<?php

namespace App\Modules\Projects\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProjectStatusLog extends Model
{
    protected $fillable = [
        'project_id',
        'from_status',
        'to_status',
        'effective_date',
        'remark',
        'changed_by_user_id',
    ];

    protected $casts = [
        'effective_date' => 'date',
    ];

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    public function changedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'changed_by_user_id');
    }
}

