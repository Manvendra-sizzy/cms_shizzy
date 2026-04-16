<?php

namespace App\Models;

use App\Modules\HRMS\Employees\Models\EmployeeProfile;
use App\Modules\HRMS\Onboarding\Models\EmployeeOnboarding;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;

class EmploymentAgreementContent extends Model
{
    protected $table = 'hrms_employment_agreement_contents';

    protected $fillable = [
        'body_html',
    ];

    /**
     * Replace {{placeholders}} in stored HTML with values for this onboarding (employee data is escaped).
     *
     * @return string HTML safe for Dompdf
     */
    public static function mergePlaceholders(string $html, EmployeeOnboarding $onboarding, ?EmployeeProfile $profile): string
    {
        $onboarding->loadMissing(['designation', 'department', 'team']);

        $payload = $onboarding->employee_payload ?? [];
        $hr = $payload['hr_agreement'] ?? [];
        $name = (string) ($payload['full_name'] ?? $onboarding->full_name);
        $address = (string) ($payload['address'] ?? $profile?->address ?? '');
        $agreementDate = isset($hr['agreement_made_date']) && $hr['agreement_made_date']
            ? Carbon::parse($hr['agreement_made_date'])->format('F j, Y')
            : now()->format('F j, Y');
        $joiningRaw = $onboarding->joining_date ?? $profile?->joining_date;
        $joining = $joiningRaw ? Carbon::parse($joiningRaw)->format('F j, Y') : '—';
        $fmtMoney = static fn ($v) => isset($v) && $v !== '' ? number_format((float) $v, 2) : '—';
        $personalEmail = (string) ($payload['personal_email'] ?? $onboarding->email ?? '');

        $esc = static fn (string $s): string => htmlspecialchars($s, ENT_QUOTES | ENT_HTML5, 'UTF-8');

        $designation = (string) ($onboarding->designation?->name ?? '');
        $department = (string) ($onboarding->department?->name ?? '');
        $team = (string) ($onboarding->team?->name ?? '');
        $basic = $fmtMoney($hr['basic_salary'] ?? null);
        $salary25 = $fmtMoney($hr['salary_25_percent'] ?? null);
        $other = $fmtMoney($hr['other_allowance'] ?? null);
        $gross = $fmtMoney($hr['gross_salary'] ?? null);
        /** HRA amount (same value as salary_25_percent key; payroll-aligned 50% of basic). */
        $hra = $salary25;

        $companySignatoryName = (string) (config('services.zoho_sign.company_signatory_name') ?? '');
        if ($companySignatoryName === '') {
            $companySignatoryName = '—';
        }

        // Multiple token styles (snake_case, kebab-case, labels) map to the same values.
        $map = [
            // Recipient (signer / employee on this agreement)
            '{{recipient_name}}' => $esc($name),
            '{{recipient-name}}' => $esc($name),
            '{{Recipient Name}}' => $esc($name),
            '{{recipient_email}}' => $esc($personalEmail),
            '{{recipient-email}}' => $esc($personalEmail),
            '{{Recipient Email}}' => $esc($personalEmail),
            // Employee (legacy + kebab)
            '{{employee_name}}' => $esc($name),
            '{{employee-name}}' => $esc($name),
            '{{employee_address}}' => $esc($address),
            '{{employee-address}}' => $esc($address),
            '{{employee-designation}}' => $esc($designation),
            '{{employee-department}}' => $esc($department),
            '{{employee-joining-date}}' => $esc($joining),
            '{{employee-basic-salary}}' => $esc($basic),
            '{{employee-25%-of-salary}}' => $esc($salary25),
            '{{employee-50%-of-salary}}' => $esc($salary25),
            '{{employee-hra}}' => $esc($hra),
            '{{hra}}' => $esc($hra),
            '{{employee-other-allowance}}' => $esc($other),
            '{{employee-gross-salary}}' => $esc($gross),
            // Legacy / short names
            '{{designation}}' => $esc($designation),
            '{{department}}' => $esc($department),
            '{{team}}' => $esc($team),
            '{{joining_date}}' => $esc($joining),
            '{{agreement_date}}' => $esc($agreementDate),
            '{{personal_email}}' => $esc($personalEmail),
            '{{basic_salary}}' => $esc($basic),
            '{{salary_25_percent}}' => $esc($salary25),
            '{{salary_hra}}' => $esc($hra),
            '{{other_allowance}}' => $esc($other),
            '{{gross_salary}}' => $esc($gross),
            '{{issue-date}}' => $esc($agreementDate),
            '{{company-signature}}' => $esc($companySignatoryName),
        ];

        return str_replace(array_keys($map), array_values($map), $html);
    }

    public static function resolveTemplateHtml(): ?string
    {
        $existing = self::query()->value('body_html');
        if (is_string($existing) && trim($existing) !== '') {
            return $existing;
        }

        $path = base_path('employee_agreement_content.txt');
        if (! is_file($path)) {
            return null;
        }

        $raw = file_get_contents($path);
        if ($raw === false || trim($raw) === '') {
            return null;
        }

        $html = '<div class="agreement-doc">'.nl2br(e($raw)).'</div>';
        self::query()->create(['body_html' => $html]);

        return $html;
    }
}
