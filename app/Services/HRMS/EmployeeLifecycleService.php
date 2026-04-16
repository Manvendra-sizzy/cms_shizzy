<?php

namespace App\Services\HRMS;

use App\Modules\HRMS\Employees\Models\EmployeeProfile;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class EmployeeLifecycleService
{
    public const TYPE_INTERN = 'intern';
    public const TYPE_PERMANENT_EMPLOYEE = 'permanent_employee';

    public const BADGE_INTERNSHIP_I = 'internship_i';
    public const BADGE_PROBATION_E = 'probation_e';
    public const BADGE_PERMANENT_EMPLOYEE_PE = 'permanent_employee_pe';

    /**
     * @return array<string, string>
     */
    public static function employeeTypeLabels(): array
    {
        return [
            self::TYPE_INTERN => 'Intern',
            self::TYPE_PERMANENT_EMPLOYEE => 'Permanent Employee',
        ];
    }

    /**
     * @return array<string, string>
     */
    public static function badgeLabels(): array
    {
        return [
            self::BADGE_INTERNSHIP_I => 'Internship I',
            self::BADGE_PROBATION_E => 'Probation E',
            self::BADGE_PERMANENT_EMPLOYEE_PE => 'Permanent Employee PE',
        ];
    }

    /**
     * @return array<int, int>
     */
    public static function internshipPeriods(): array
    {
        return [1, 3, 6, 12];
    }

    public function computeInternshipEndDate(string $startDate, int $months): string
    {
        return Carbon::parse($startDate)->startOfDay()->addMonthsNoOverflow($months)->toDateString();
    }

    public function computeProbationEndDate(string $startDate, int $months): string
    {
        return Carbon::parse($startDate)->startOfDay()->addMonthsNoOverflow($months)->toDateString();
    }

    /**
     * Next sequential ID for EXI### or EXE### (same rules as HR “Add employee” and onboarding approval).
     */
    public function allocateNextEmployeeId(string $prefix): string
    {
        $prefix = strtoupper($prefix);
        if ($prefix !== 'EXI' && $prefix !== 'EXE') {
            throw new \InvalidArgumentException('Employee ID prefix must be EXI or EXE.');
        }

        $nextNumber = (int) (DB::table('employee_profiles')
            ->where('employee_id', 'like', $prefix.'%')
            ->selectRaw('COALESCE(MAX(CAST(SUBSTRING(employee_id, 4) AS UNSIGNED)), 0) as max_num')
            ->value('max_num')) + 1;

        return $prefix.str_pad((string) $nextNumber, 3, '0', STR_PAD_LEFT);
    }

    public function resolveBadge(EmployeeProfile $employee): string
    {
        if ($employee->employee_type === self::TYPE_INTERN) {
            return self::BADGE_INTERNSHIP_I;
        }

        if ($employee->employee_type === self::TYPE_PERMANENT_EMPLOYEE) {
            return $employee->hasCompletedProbation()
                ? self::BADGE_PERMANENT_EMPLOYEE_PE
                : self::BADGE_PROBATION_E;
        }

        return self::BADGE_PERMANENT_EMPLOYEE_PE;
    }

    public function synchronizeBadge(EmployeeProfile $employee): void
    {
        $employee->update([
            'employee_badge' => $this->resolveBadge($employee),
        ]);
    }

    /**
     * @return array{allowed:bool,reason:string|null}
     */
    public function conversionEligibility(EmployeeProfile $employee): array
    {
        if (! $employee->isIntern()) {
            return ['allowed' => false, 'reason' => 'Only intern employees can be converted.'];
        }

        if (($employee->status ?? 'active') !== 'active') {
            return ['allowed' => false, 'reason' => 'Only active interns can be converted.'];
        }

        if (! $employee->hasCompletedInternship()) {
            $end = optional($employee->internship_end_date)->format('Y-m-d');

            return [
                'allowed' => false,
                'reason' => $end
                    ? "Internship is not complete yet. Expected completion: {$end}."
                    : 'Internship completion date is missing.',
            ];
        }

        return ['allowed' => true, 'reason' => null];
    }

    public function convertInternToPermanent(
        EmployeeProfile $employee,
        int $probationPeriodMonths,
        string $effectiveDate
    ): void {
        $eligibility = $this->conversionEligibility($employee);
        if (! $eligibility['allowed']) {
            throw ValidationException::withMessages([
                'conversion' => (string) $eligibility['reason'],
            ]);
        }

        $effective = Carbon::parse($effectiveDate)->startOfDay()->toDateString();
        $probationEnd = $this->computeProbationEndDate($effective, $probationPeriodMonths);

        $newEmployeeId = $this->allocateNextEmployeeId('EXE');

        $employee->update([
            'employee_id' => $newEmployeeId,
            'employee_type' => self::TYPE_PERMANENT_EMPLOYEE,
            'probation_period_months' => $probationPeriodMonths,
            'probation_start_date' => $effective,
            'probation_end_date' => $probationEnd,
            'converted_to_permanent_at' => now(),
            'employee_badge' => self::BADGE_PROBATION_E,
        ]);
    }
}
