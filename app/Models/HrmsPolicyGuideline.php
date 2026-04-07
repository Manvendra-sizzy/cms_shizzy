<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class HrmsPolicyGuideline extends Model
{
    protected $table = 'hrms_policies_guidelines';

    protected $fillable = [
        'title',
        'content',
        'active',
    ];

    protected $casts = [
        'active' => 'bool',
    ];
}

