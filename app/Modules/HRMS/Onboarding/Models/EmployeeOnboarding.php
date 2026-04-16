<?php

namespace App\Modules\HRMS\Onboarding\Models;

use App\Models\OrganizationDepartment;
use App\Models\OrganizationDesignation;
use App\Models\OrganizationTeam;
use App\Models\User;
use App\Modules\HRMS\Employees\Models\EmployeeProfile;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

class EmployeeOnboarding extends Model
{
    public const CONTRACT_STATUS_PENDING = 'pending';
    public const CONTRACT_STATUS_OPENED = 'opened';
    public const CONTRACT_STATUS_AGREED = 'agreed';
    public const CONTRACT_STATUS_SIGNED = 'signed';

    public const STATUS_DRAFT = 'draft';
    public const STATUS_LINK_SENT = 'link_sent';
    public const STATUS_IN_PROGRESS = 'in_progress';
    public const STATUS_SUBMITTED = 'submitted';
    public const STATUS_APPROVED = 'approved';
    public const STATUS_REJECTED = 'rejected';
    public const STATUS_AGREEMENT_SENT = 'agreement_sent';
    public const STATUS_AGREEMENT_SIGNED = 'agreement_signed';
    public const STATUS_COMPLETED = 'completed';
    public const STATUS_EXPIRED = 'expired';

    protected $fillable = [
        'full_name', 'email', 'phone', 'employee_type', 'designation_id', 'department_id', 'team_id',
        'joining_date', 'status', 'token_hash', 'token_expires_at', 'link_sent_at', 'submitted_at',
        'approved_at', 'rejected_at', 'completed_at', 'hr_notes', 'employee_payload',
        'zoho_sign_request_id', 'zoho_sign_status', 'zoho_sign_sent_at', 'zoho_sign_signed_at', 'zoho_sign_completed_at',
        'zoho_sign_meta', 'zoho_sign_agreement_pdf_path', 'zoho_sign_signed_pdf_path',
        'contract_status', 'contract_token_hash', 'contract_token_expires_at', 'contract_sent_at', 'contract_opened_at',
        'contract_agreed_at', 'contract_signed_at', 'contract_signature_path', 'contract_selfie_path',
        'contract_signed_pdf_path', 'contract_document_hash', 'contract_sign_meta',
        'approved_by_user_id', 'final_user_id', 'final_employee_profile_id',
    ];

    protected $casts = [
        'joining_date' => 'date',
        'token_expires_at' => 'datetime',
        'link_sent_at' => 'datetime',
        'submitted_at' => 'datetime',
        'approved_at' => 'datetime',
        'rejected_at' => 'datetime',
        'completed_at' => 'datetime',
        'employee_payload' => 'array',
        'zoho_sign_sent_at' => 'datetime',
        'zoho_sign_signed_at' => 'datetime',
        'zoho_sign_completed_at' => 'datetime',
        'zoho_sign_meta' => 'array',
        'contract_token_expires_at' => 'datetime',
        'contract_sent_at' => 'datetime',
        'contract_opened_at' => 'datetime',
        'contract_agreed_at' => 'datetime',
        'contract_signed_at' => 'datetime',
        'contract_sign_meta' => 'array',
    ];

    public static function statuses(): array
    {
        return [
            self::STATUS_DRAFT,
            self::STATUS_LINK_SENT,
            self::STATUS_IN_PROGRESS,
            self::STATUS_SUBMITTED,
            self::STATUS_APPROVED,
            self::STATUS_REJECTED,
            self::STATUS_AGREEMENT_SENT,
            self::STATUS_AGREEMENT_SIGNED,
            self::STATUS_COMPLETED,
            self::STATUS_EXPIRED,
        ];
    }

    public function designation(): BelongsTo
    {
        return $this->belongsTo(OrganizationDesignation::class, 'designation_id');
    }

    public function department(): BelongsTo
    {
        return $this->belongsTo(OrganizationDepartment::class, 'department_id');
    }

    public function team(): BelongsTo
    {
        return $this->belongsTo(OrganizationTeam::class, 'team_id');
    }

    public function documents(): HasMany
    {
        return $this->hasMany(OnboardingDocument::class, 'employee_onboarding_id');
    }

    public function contractEvidenceLogs(): HasMany
    {
        return $this->hasMany(OnboardingContractEvidenceLog::class, 'employee_onboarding_id');
    }

    public function approvedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by_user_id');
    }

    public function finalUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'final_user_id');
    }

    public function finalEmployeeProfile(): BelongsTo
    {
        return $this->belongsTo(EmployeeProfile::class, 'final_employee_profile_id');
    }

    public function canEditByEmployee(): bool
    {
        return in_array($this->status, [self::STATUS_LINK_SENT, self::STATUS_IN_PROGRESS, self::STATUS_REJECTED], true)
            && ! $this->isExpired();
    }

    public function canApprove(): bool
    {
        return $this->status === self::STATUS_SUBMITTED;
    }

    public function canSendAgreement(): bool
    {
        return in_array($this->status, [
            self::STATUS_APPROVED,
            self::STATUS_COMPLETED,
            self::STATUS_AGREEMENT_SENT,
        ], true);
    }

    public function canSignContract(): bool
    {
        if ($this->contract_signed_at !== null || $this->contract_status === self::CONTRACT_STATUS_SIGNED) {
            return false;
        }

        if ($this->contract_token_hash === null) {
            return false;
        }

        $expires = $this->contract_token_expires_at ?? $this->token_expires_at;
        if (! $expires instanceof Carbon || now()->greaterThan($expires)) {
            return false;
        }

        return in_array($this->status, [
            self::STATUS_LINK_SENT,
            self::STATUS_IN_PROGRESS,
            self::STATUS_SUBMITTED,
            self::STATUS_REJECTED,
            self::STATUS_APPROVED,
            self::STATUS_AGREEMENT_SENT,
            self::STATUS_COMPLETED,
        ], true);
    }

    /**
     * Same secure token as the onboarding link (issueOrResendLink). When HR issues a separate contract link
     * (issueContractLink), hashes differ — signing must use /onboarding-contract/{token} instead.
     */
    public function canSignContractEmbedded(): bool
    {
        if (! $this->canSignContract()) {
            return false;
        }

        return $this->token_hash !== null
            && $this->contract_token_hash !== null
            && hash_equals((string) $this->token_hash, (string) $this->contract_token_hash);
    }

    /** HR may edit Zoho prefill salary fields until the agreement is fully signed. */
    public function hrCanEditAgreementPrefill(): bool
    {
        return ! in_array($this->status, [
            self::STATUS_REJECTED,
            self::STATUS_EXPIRED,
            self::STATUS_AGREEMENT_SIGNED,
        ], true);
    }

    public function isExpired(): bool
    {
        return $this->token_expires_at instanceof Carbon && now()->greaterThan($this->token_expires_at);
    }

    public function markSubmitted(array $payload): void
    {
        $this->update([
            'status' => self::STATUS_SUBMITTED,
            'submitted_at' => now(),
            'employee_payload' => $payload,
        ]);
    }

    public function markApproved(int $userId): void
    {
        $this->update([
            'status' => self::STATUS_APPROVED,
            'approved_at' => now(),
            'approved_by_user_id' => $userId,
        ]);
    }

    public function markCompleted(): void
    {
        $this->update([
            'status' => self::STATUS_COMPLETED,
            'completed_at' => now(),
        ]);
    }
}

