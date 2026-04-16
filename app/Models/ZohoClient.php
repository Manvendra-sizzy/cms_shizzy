<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ZohoClient extends Model
{
    use HasFactory;

    protected $fillable = [
        'zoho_contact_id',
        'contact_name',
        'company_name',
        'first_name',
        'last_name',
        'email',
        'phone',
        'mobile',
        'contact_type',
        'status',
        'gst_no',
        'gst_treatment',
        'place_of_contact',
        'outstanding_receivable_amount',
        'raw_payload',
        'last_synced_at',
    ];

    protected $casts = [
        'outstanding_receivable_amount' => 'decimal:2',
        'raw_payload' => 'array',
        'last_synced_at' => 'datetime',
    ];
}
