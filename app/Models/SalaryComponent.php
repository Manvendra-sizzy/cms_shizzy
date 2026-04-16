<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SalaryComponent extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'code',
        'type',
        'value',
        'base_component_code',
        'sequence',
        'active',
    ];

    protected $casts = [
        'value' => 'decimal:4',
        'active' => 'bool',
    ];
}
