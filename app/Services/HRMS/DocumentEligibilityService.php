<?php

namespace App\Services\HRMS;

use App\Modules\HRMS\Documents\Models\HRDocument;
use App\Modules\HRMS\Employees\Models\EmployeeProfile;

class DocumentEligibilityService
{
    /**
     * @return array{allowed:bool,reason:string|null}
     */
    public function canIssue(EmployeeProfile $employee, string $documentType): array
    {
        $type = trim($documentType);

        return match ($type) {
            HRDocument::TYPE_INTERNSHIP_APPOINTMENT_LETTER => $this->canIssueInternshipAppointment($employee),
            HRDocument::TYPE_EMPLOYMENT_LETTER => $this->canIssueEmploymentLetter($employee),
            HRDocument::TYPE_PERMANENT_EMPLOYMENT_LETTER => $this->canIssuePermanentEmploymentLetter($employee),
            HRDocument::TYPE_RELIEVING_LETTER => $this->canIssueRelievingLetter($employee),
            default => ['allowed' => true, 'reason' => null],
        };
    }

    /**
     * @return array{allowed:bool,reason:string|null}
     */
    private function canIssueInternshipAppointment(EmployeeProfile $employee): array
    {
        if (! $employee->isIntern()) {
            return ['allowed' => false, 'reason' => 'Internship Appointment Letter can only be issued to intern employees.'];
        }

        return ['allowed' => true, 'reason' => null];
    }

    /**
     * @return array{allowed:bool,reason:string|null}
     */
    private function canIssueEmploymentLetter(EmployeeProfile $employee): array
    {
        if (($employee->status ?? 'active') !== 'active') {
            return ['allowed' => false, 'reason' => 'Employment Letter can only be issued to active employees.'];
        }

        return ['allowed' => true, 'reason' => null];
    }

    /**
     * @return array{allowed:bool,reason:string|null}
     */
    private function canIssuePermanentEmploymentLetter(EmployeeProfile $employee): array
    {
        if (! $employee->isPermanentEmployee()) {
            return ['allowed' => false, 'reason' => 'Permanent Employment Letter can only be issued to permanent employees.'];
        }

        if (! $employee->hasCompletedProbation()) {
            return ['allowed' => false, 'reason' => 'Permanent Employment Letter can be issued only after probation completion.'];
        }

        return ['allowed' => true, 'reason' => null];
    }

    /**
     * @return array{allowed:bool,reason:string|null}
     */
    private function canIssueRelievingLetter(EmployeeProfile $employee): array
    {
        $isExitStatus = ($employee->status ?? null) === 'former';
        $hasExitSignal = $employee->separation_effective_at !== null
            || $employee->inactive_at !== null
            || ! empty($employee->separation_type);

        if (! $isExitStatus && ! $hasExitSignal) {
            return ['allowed' => false, 'reason' => 'Relieving Letter can only be issued when the employee exit process is recorded.'];
        }

        return ['allowed' => true, 'reason' => null];
    }
}
