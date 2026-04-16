<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>Employment Agreement</title>
    <style>
        @page { margin: 48px 40px 52px 40px; }
        * { box-sizing: border-box; }
        body {
            font-family: DejaVu Sans, sans-serif;
            font-size: 11px;
            line-height: 1.55;
            color: #111827;
        }
        h1 { font-size: 18px; margin: 0 0 12px; text-align: center; }
        .muted { color: #6b7280; font-size: 10px; }
        .kv { margin: 6px 0; }
        .kv strong { display: inline-block; min-width: 140px; color: #374151; }
        table.compact { width: 100%; border-collapse: collapse; margin: 12px 0; font-size: 10.5px; }
        table.compact th, table.compact td { border: 1px solid #d1d5db; padding: 6px 8px; text-align: left; vertical-align: top; }
        table.compact th { background: #f9fafb; font-weight: 600; }
        .section { margin-top: 14px; }
        p { margin: 0 0 8px; }
        .agreement-doc { font-size: 10.5px; line-height: 1.5; }
        .agreement-doc h1, .agreement-doc h2, .agreement-doc h3 { font-size: 11px; margin: 10px 0 6px; }
        .agreement-doc table { width: 100%; border-collapse: collapse; margin: 8px 0; }
        .agreement-doc table td, .agreement-doc table th { border: 1px solid #d1d5db; padding: 4px 6px; vertical-align: top; }
        .sig-page { page-break-before: always; padding-top: 24px; }
        .sig-note { margin-top: 40px; font-size: 10px; color: #6b7280; }
    </style>
</head>
<body>
@php
    $hr = ($onboarding->employee_payload ?? [])['hr_agreement'] ?? [];
    $fmtMoney = static fn ($v) => isset($v) && $v !== '' ? number_format((float) $v, 2) : '—';
@endphp

<div class="agreement-doc">
    {!! $agreementBodyHtml !!}
</div>

<div class="section">
    <p><strong>Salary summary (HR entry)</strong> Figures in INR unless stated otherwise.</p>
    <table class="compact">
        <tr><th>Component</th><th>Amount</th></tr>
        <tr><td>Basic salary</td><td>{{ $fmtMoney($hr['basic_salary'] ?? null) }}</td></tr>
        <tr><td>HRA (50% of basic)</td><td>{{ $fmtMoney($hr['salary_25_percent'] ?? null) }}</td></tr>
        <tr><td>Other allowance</td><td>{{ $fmtMoney($hr['other_allowance'] ?? null) }}</td></tr>
        <tr><td>Gross salary</td><td>{{ $fmtMoney($hr['gross_salary'] ?? null) }}</td></tr>
    </table>
</div>

<div class="sig-page">
    <p><strong>Signatures</strong></p>
    <p>The Employee acknowledges that they have read and understood this Agreement.</p>
    <p class="sig-note">Employee e-signature will be collected via Zoho Sign on the following page area.</p>
</div>
</body>
</html>
