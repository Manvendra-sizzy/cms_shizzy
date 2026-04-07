<?php

namespace App\Modules\Projects\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProjectReimbursement extends Model
{
    protected $table = 'project_reimbursements';

    protected $fillable = [
        'project_id',
        'project_revenue_stream_id',
        'spent_date',
        'description',
        'spend_amount',
        'markup_type',
        'markup_value',
        'final_billable_amount',
        'status',
        'notes',
    ];

    protected $casts = [
        'spent_date' => 'date',
        'spend_amount' => 'decimal:2',
        'markup_value' => 'decimal:2',
        'final_billable_amount' => 'decimal:2',
    ];

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    public function stream(): BelongsTo
    {
        return $this->belongsTo(ProjectRevenueStream::class, 'project_revenue_stream_id');
    }
}

