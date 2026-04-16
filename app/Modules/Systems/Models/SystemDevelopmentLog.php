<?php

namespace App\Modules\Systems\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SystemDevelopmentLog extends Model
{
    protected $fillable = [
        'system_id',
        'title',
        'description',
        'change_type',
        'version',
        'changed_by_user_id',
        'change_date',
        'deployment_status',
    ];

    protected $casts = [
        'change_date' => 'date',
    ];

    public function system(): BelongsTo
    {
        return $this->belongsTo(System::class, 'system_id');
    }

    public function changedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'changed_by_user_id');
    }
}
