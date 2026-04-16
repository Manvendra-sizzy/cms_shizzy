<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CmsActivityLog extends Model
{
    protected $table = 'cms_activity_logs';

    protected $fillable = [
        'user_id',
        'user_email',
        'action_key',
        'route_name',
        'method',
        'url',
        'ip_address',
        'user_agent',
        'status_code',
        'context',
    ];

    protected $casts = [
        'context' => 'array',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}

