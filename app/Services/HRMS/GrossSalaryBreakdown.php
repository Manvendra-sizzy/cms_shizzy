<?php

namespace App\Services\HRMS;

/**
 * Fixed split aligned with payroll slip generation (see HRPayrollController slip generation):
 * Basic = 50% of gross, HRA = 50% of basic, other allowance = remainder to match gross.
 *
 * The legacy key `salary_25_percent` on onboarding / agreement merge stores the HRA amount
 * (same numeric role as payroll line "HRA").
 */
final class GrossSalaryBreakdown
{
    /**
     * @return array{
     *     gross_salary: float,
     *     basic_salary: float,
     *     salary_25_percent: float,
     *     other_allowance: float
     * }
     */
    public static function fromGross(float $gross): array
    {
        $gross = round(max(0, $gross), 2);
        $basic = round($gross * 0.50, 2);
        $hra = round($basic * 0.50, 2);
        $other = round(max(0, $gross - $basic - $hra), 2);

        return [
            'gross_salary' => $gross,
            'basic_salary' => $basic,
            'salary_25_percent' => $hra,
            'other_allowance' => $other,
        ];
    }
}
