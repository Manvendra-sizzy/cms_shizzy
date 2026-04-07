<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ZohoInvoice extends Model
{
    use HasFactory;

    protected $fillable = [
        'zoho_invoice_id',
        'zoho_customer_id',
        'project_id',
        'invoice_number',
        'status',
        'total',
        'balance',
        'invoice_date',
        'due_date',
        'raw_payload',
        'last_synced_at',
    ];

    protected $casts = [
        'total' => 'decimal:2',
        'balance' => 'decimal:2',
        'invoice_date' => 'date',
        'due_date' => 'date',
        'raw_payload' => 'array',
        'last_synced_at' => 'datetime',
    ];
}
