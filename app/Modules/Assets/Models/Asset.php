<?php

namespace App\Modules\Assets\Models;

use App\Modules\HRMS\Employees\Models\EmployeeProfile;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Asset extends Model
{
    protected $fillable = [
        'asset_category_id',
        'name',
        'condition',
        'asset_code',
        'serial_number',
        'description',
        'purchase_date',
        'purchase_value',
        'status',
    ];

    protected $casts = [
        'purchase_date' => 'date',
        'purchase_value' => 'decimal:2',
    ];

    public function category(): BelongsTo
    {
        return $this->belongsTo(AssetCategory::class, 'asset_category_id');
    }

    public function assignments(): HasMany
    {
        return $this->hasMany(AssetAssignment::class);
    }

    public function currentAssignment(): ?AssetAssignment
    {
        return $this->assignments()
            ->whereNull('returned_at')
            ->latest('assigned_at')
            ->first();
    }

    public function currentHolder(): ?EmployeeProfile
    {
        $assignment = $this->currentAssignment();

        return $assignment?->employeeProfile;
    }
}

