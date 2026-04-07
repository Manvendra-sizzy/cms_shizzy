<?php

namespace App\Modules\Projects\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProjectRevenuePayment extends Model
{
    protected $table = 'project_revenue_payments';

    protected $fillable = [
        'project_revenue_stream_id',
        'project_revenue_invoice_id',
        'payment_date',
        'amount',
        'method',
        'reference',
        'notes',
    ];

    protected $casts = [
        'payment_date' => 'date',
        'amount' => 'decimal:2',
    ];

    public function stream(): BelongsTo
    {
        return $this->belongsTo(ProjectRevenueStream::class, 'project_revenue_stream_id');
    }

    public function invoice(): BelongsTo
    {
        return $this->belongsTo(ProjectRevenueInvoice::class, 'project_revenue_invoice_id');
    }
}

