<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class OrganizationDesignation extends Model
{
    protected $fillable = ['name', 'code', 'active'];

    protected $casts = ['active' => 'bool'];
}

