<?php

namespace App\Modules\Projects\Models;

use App\Models\ZohoClient;
use App\Models\User;
use App\Modules\HRMS\Employees\Models\EmployeeProfile;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use App\Modules\Projects\Models\ProjectRevenueStream;
use App\Modules\Projects\Models\ProjectReimbursement;

class Project extends Model
{
    protected $fillable = [
        'project_client_id',
        'zoho_client_id',
        'is_internal',
        'project_code',
        'name',
        'category',
        'project_type',
        'billing_type',
        'description',
        'project_manager_employee_profile_id',
        'account_manager_employee_profile_id',
        'project_folder',
        'status',
        'created_by_user_id',
    ];

    public function client(): BelongsTo
    {
        return $this->belongsTo(ProjectClient::class, 'project_client_id');
    }

    public function zohoClient(): BelongsTo
    {
        return $this->belongsTo(ZohoClient::class, 'zoho_client_id');
    }

    protected $casts = [
        'is_internal' => 'bool',
    ];

    public function projectManager(): BelongsTo
    {
        return $this->belongsTo(EmployeeProfile::class, 'project_manager_employee_profile_id');
    }

    public function accountManager(): BelongsTo
    {
        return $this->belongsTo(EmployeeProfile::class, 'account_manager_employee_profile_id');
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by_user_id');
    }

    public function statusLogs(): HasMany
    {
        return $this->hasMany(ProjectStatusLog::class);
    }

    public function teamMembers(): HasMany
    {
        return $this->hasMany(ProjectTeamMember::class);
    }

    public function revenueStreams(): HasMany
    {
        return $this->hasMany(ProjectRevenueStream::class);
    }

    public function reimbursements(): HasMany
    {
        return $this->hasMany(ProjectReimbursement::class);
    }
}

