<?php

namespace App\Services\HRMS;

use App\Models\SalaryComponent;

class PayrollBreakdownService
{
    public const TYPE_REMAINING = 'remaining';

    /**
     * @return array{earning_lines: array<int, array{name: string, code: string, amount: float}>, deduction_lines: array<int, array{name: string, code: string, amount: float}>, total_earnings: float, total_deductions: float}
     */
    public function buildLines(float $grossBasis): array
    {
        $components = SalaryComponent::query()
            ->where('active', true)
            ->orderBy('sequence')
            ->orderBy('id')
            ->get();

        $amountsByCode = [];
        $earningLines = [];
        $deductionLines = [];

        $remainingComponent = null;
        foreach ($components as $c) {
            if ($c->type === self::TYPE_REMAINING) {
                $remainingComponent = $c;
                continue;
            }
            $amount = 0.0;
            switch ($c->type) {
                case 'percent_of_gross':
                    $amount = round($grossBasis * ((float) $c->value / 100.0), 2);
                    $amountsByCode[$c->code] = ($amountsByCode[$c->code] ?? 0) + $amount;
                    $earningLines[] = [
                        'name' => $c->name,
                        'code' => $c->code,
                        'amount' => $amount,
                    ];
                    break;
                case 'percent_of_component':
                    $base = $amountsByCode[$c->base_component_code] ?? 0;
                    $amount = round($base * ((float) $c->value / 100.0), 2);
                    $amountsByCode[$c->code] = ($amountsByCode[$c->code] ?? 0) + $amount;
                    $earningLines[] = [
                        'name' => $c->name,
                        'code' => $c->code,
                        'amount' => $amount,
                    ];
                    break;
                case 'fixed':
                    $amount = round((float) $c->value, 2);
                    $amountsByCode[$c->code] = ($amountsByCode[$c->code] ?? 0) + $amount;
                    $earningLines[] = [
                        'name' => $c->name,
                        'code' => $c->code,
                        'amount' => $amount,
                    ];
                    break;
                case 'deduction_percent_of_gross':
                    $amount = round($grossBasis * ((float) $c->value / 100.0), 2);
                    $deductionLines[] = [
                        'name' => $c->name,
                        'code' => $c->code,
                        'amount' => $amount,
                    ];
                    break;
            }
        }

        $totalEarnings = round(array_sum(array_column($earningLines, 'amount')), 2);
        if ($remainingComponent) {
            $remainingAmount = round($grossBasis - $totalEarnings, 2);
            if ($remainingAmount < 0) {
                $remainingAmount = 0.0;
            }
            $amountsByCode[$remainingComponent->code] = ($amountsByCode[$remainingComponent->code] ?? 0) + $remainingAmount;
            $earningLines[] = [
                'name' => $remainingComponent->name,
                'code' => $remainingComponent->code,
                'amount' => $remainingAmount,
            ];
            $totalEarnings = round($totalEarnings + $remainingAmount, 2);
        }
        $totalDeductions = round(array_sum(array_column($deductionLines, 'amount')), 2);

        return [
            'earning_lines' => $earningLines,
            'deduction_lines' => $deductionLines,
            'total_earnings' => $totalEarnings,
            'total_deductions' => $totalDeductions,
        ];
    }
}
