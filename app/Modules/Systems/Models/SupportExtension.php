<?php

namespace App\Modules\Systems\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SupportExtension extends Model
{
    protected $fillable = [
        'system_id',
        'previous_end_date',
        'new_end_date',
        'extended_by_user_id',
        'reason',
        'extended_at',
    ];

    protected $casts = [
        'previous_end_date' => 'date',
        'new_end_date' => 'date',
        'extended_at' => 'datetime',
    ];

    public function system(): BelongsTo
    {
        return $this->belongsTo(System::class, 'system_id');
    }

    public function extendedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'extended_by_user_id');
    }
}
