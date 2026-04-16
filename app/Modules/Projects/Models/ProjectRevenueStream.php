<?php

namespace App\Modules\Projects\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ProjectRevenueStream extends Model
{
    protected $table = 'project_revenue_streams';

    protected $fillable = [
        'project_id',
        'name',
        'type',
        'billing_cycle',
        'expected_total_value',
        'rate_per_unit',
        'quantity',
        'calculated_amount',
        'start_date',
        'next_billing_date',
        'end_date',
        'active',
        'closed_at',
        'closed_remark',
        'notes',
    ];

    protected $casts = [
        'expected_total_value' => 'decimal:2',
        'rate_per_unit' => 'decimal:2',
        'quantity' => 'decimal:2',
        'calculated_amount' => 'decimal:2',
        'start_date' => 'date',
        'next_billing_date' => 'date',
        'end_date' => 'date',
        'active' => 'bool',
        'closed_at' => 'date',
    ];

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    public function invoices(): HasMany
    {
        return $this->hasMany(ProjectRevenueInvoice::class, 'project_revenue_stream_id');
    }

    public function payments(): HasMany
    {
        return $this->hasMany(ProjectRevenuePayment::class, 'project_revenue_stream_id');
    }

    public function reimbursements(): HasMany
    {
        return $this->hasMany(ProjectReimbursement::class, 'project_revenue_stream_id');
    }
}

