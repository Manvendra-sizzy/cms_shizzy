<?php

namespace App\Modules\HRMS\Documents\Models;

use App\Models\User;
use App\Modules\HRMS\Employees\Models\EmployeeProfile;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class HRDocument extends Model
{
    public const TYPE_INTERNSHIP_APPOINTMENT_LETTER = 'internship_appointment_letter';
    public const TYPE_EMPLOYMENT_LETTER = 'employment_letter';
    public const TYPE_PERMANENT_EMPLOYMENT_LETTER = 'permanent_employment_letter';
    public const TYPE_RELIEVING_LETTER = 'relieving_letter';
    public const TYPE_APPRECIATION_LETTER = 'appreciation_letter';
    public const TYPE_SHOW_CAUSE_NOTICE = 'show_cause_notice';
    public const TYPE_WARNING_LETTER = 'warning_letter';

    protected $table = 'hr_documents';

    protected $fillable = [
        'employee_profile_id',
        'issued_by_user_id',
        'type',
        'title',
        'body',
        'file_path',
        'document_hash',
        'meta',
        'issued_at',
    ];

    protected $casts = [
        'meta' => 'array',
        'issued_at' => 'datetime',
    ];

    public function employeeProfile(): BelongsTo
    {
        return $this->belongsTo(EmployeeProfile::class);
    }

    public function issuedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'issued_by_user_id');
    }

    /**
     * @return array<string, string>
     */
    public static function typeOptions(): array
    {
        return [
            self::TYPE_INTERNSHIP_APPOINTMENT_LETTER => 'Internship Appointment Letter',
            self::TYPE_EMPLOYMENT_LETTER => 'Employment Letter',
            self::TYPE_PERMANENT_EMPLOYMENT_LETTER => 'Permanent Employment Letter',
            self::TYPE_RELIEVING_LETTER => 'Relieving Letter',
            self::TYPE_APPRECIATION_LETTER => 'Appreciation Letter',
            self::TYPE_SHOW_CAUSE_NOTICE => 'Show-Cause Notice',
            self::TYPE_WARNING_LETTER => 'Warning Letter',
        ];
    }

    public function typeLabel(): string
    {
        return self::typeOptions()[$this->type ?? ''] ?? str_replace('_', ' ', ucwords((string) $this->type, '_'));
    }
}

