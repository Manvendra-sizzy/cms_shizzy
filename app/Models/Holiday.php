<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Holiday extends Model
{
    protected $fillable = ['observed_on', 'title'];

    protected $casts = [
        'observed_on' => 'date',
    ];
}
