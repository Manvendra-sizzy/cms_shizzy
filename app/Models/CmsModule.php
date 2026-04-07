<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class CmsModule extends Model
{
    protected $table = 'cms_modules';

    protected $fillable = [
        'key',
        'name',
        'active',
    ];

    protected $casts = [
        'active' => 'bool',
    ];

    public function users(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'cms_user_modules', 'cms_module_id', 'user_id')
            ->withTimestamps();
    }
}

