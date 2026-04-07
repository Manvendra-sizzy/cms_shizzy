<?php

namespace App\Modules\Projects\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ProjectRevenueInvoice extends Model
{
    protected $table = 'project_revenue_invoices';

    protected $fillable = [
        'project_revenue_stream_id',
        'invoice_number',
        'invoice_date',
        'due_date',
        'invoice_file_path',
        'amount',
        'status',
        'notes',
    ];

    protected $casts = [
        'invoice_date' => 'date',
        'due_date' => 'date',
        'amount' => 'decimal:2',
    ];

    public function stream(): BelongsTo
    {
        return $this->belongsTo(ProjectRevenueStream::class, 'project_revenue_stream_id');
    }

    public function payments(): HasMany
    {
        return $this->hasMany(ProjectRevenuePayment::class, 'project_revenue_invoice_id');
    }
}

