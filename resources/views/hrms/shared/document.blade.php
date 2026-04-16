<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $document->typeLabel() }}</title>
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap');
        @page { margin: 108px 46px 58px 46px; }
        * { box-sizing: border-box; }
        body {
            font-family: 'Poppins', sans-serif;
            line-height: 1.62;
            color: #0f172a;
            font-size: 12px;
        }
        .header {
            position: fixed;
            top: -92px;
            left: 0;
            right: 0;
            height: 90px;
        }
        .header-row { display: table; width: 100%; }
        .header-cell {
            display: table-cell;
            vertical-align: middle;
        }
        .logo-wrap { width: 130px; }
        .logo {
            width: 106px;
            height: auto;
        }
        .company {
            text-align: right;
            font-size: 10px;
            line-height: 1.42;
            color: #334155;
        }
        .company strong {
            display: block;
            font-size: 11.2px;
            letter-spacing: .15px;
            color: #0f172a;
            margin-bottom: 3px;
        }
        .header-divider {
            margin-top: 10px;
            border-top: 1px solid #d7dde8;
        }

        .doc-title {
            text-align: center;
            margin: 0 0 16px;
            padding-top: 24px;
        }
        .subject {
            margin: 0;
            font-size: 26px;
            line-height: 1.25;
            font-weight: 700;
            color: #0f172a;
            margin-top: 40px;
        }
        .subline { margin-top: 4px; color: #64748b; font-size: 10.5px; }

        .issue-date {
            margin: 8px 0 18px;
            font-size: 11.4px;
            color: #1f2937;
        }
        .recipient {
            margin: 0 0 16px;
            line-height: 1.52;
            font-size: 11.6px;
            color: #0f172a;
        }

        .body {
            color: #0f172a;
            font-size: 12px;
            line-height: 1.78;
        }
        .body p {
            margin: 0 0 11px;
        }
        .body ul, .body ol { margin: 0 0 11px 20px; }
        .body h2, .body h3, .body h4 {
            margin: 0 0 8px;
            line-height: 1.35;
            color: #0f172a;
        }
        .closing {
            margin-top: 20px;
        }
        .signature-img {
            margin-top: 20px;
            font-style: italic;
            color: #374151;
            font-size: 22px;
            font-family: "Brush Script MT", "Segoe Script", cursive;
        }
        .signature-role {
            margin-top: 6px;
            font-size: 11.3px;
            color: #374151;
            font-weight: 600;
        }

        .footer {
            position: fixed;
            bottom: -44px;
            left: 0;
            right: 0;
            height: 40px;
            color: #6b7280;
            font-size: 9.5px;
        }
        .footer-inner {
            border-top: 1px solid #e5e7eb;
            padding-top: 6px;
            text-align: center;
            line-height: 1.35;
        }
        .doc-ref {
            margin-left: 10px;
            color: #9ca3af;
        }
        .meta-mini {
            margin-top: 14px;
            font-size: 10.3px;
            color: #6b7280;
            border-top: 1px dashed #d1d5db;
            padding-top: 7px;
        }
        .kv {
            display: inline-block;
            margin-right: 16px;
        }
        .kv strong {
            color: #111827;
            font-weight: 600;
        }
    </style>
</head>
<body>
@php
    $employee = $document->employeeProfile;
    $user = $employee?->user;
    $issuedOn = optional($document->issued_at)->format('Y-m-d') ?? now()->format('Y-m-d');
    $joiningDate = optional($employee?->joining_date ?? $employee?->join_date)->format('Y-m-d') ?? '—';
    $internshipEnd = optional($employee?->internship_end_date)->format('Y-m-d');
    $probationEnd = optional($employee?->probation_end_date)->format('Y-m-d');
    $employeeName = $user?->name ?? 'Employee';
    $designation = $employee?->orgDesignation?->name ?? 'Team Member';
    $effectiveDate = $issuedOn;
    $bodyHtml = trim((string) ($document->body ?? ''));
@endphp

<div class="header">
    <div class="header-row">
        <div class="header-cell logo-wrap">
            <img class="logo" src="https://shizzy.in/images/shizzy-logo-red.png" alt="Shizzy">
        </div>
        <div class="header-cell company">
            <strong>EXIN INTERNET SERVICES PRIVATE LIMITED</strong>
            Spaze Edge, Sector 47, Gurugram, Haryana, 122018<br>
            www.shizzy.in · office@shizzy.in
        </div>
    </div>
    <div class="header-divider"></div>
 </div>

<div class="footer">
    <div class="footer-inner">
        {{ $document->typeLabel() }} · Issued by HRMS
        @if(!empty($document->document_hash))
            <span class="doc-ref">Ref: {{ $document->document_hash }}</span>
        @endif
    </div>
</div>

<div class="doc-title">
    <h1 class="subject">{{ $document->typeLabel() }}</h1>
</div>

<div class="issue-date">{{ \Carbon\Carbon::parse($issuedOn)->format('F j, Y') }}</div>

<div class="recipient">
    <strong>{{ $employeeName }}</strong><br>
    {{ $employee?->employee_id ?? '—' }}<br>
    {{ $employee?->official_email ?? $user?->email ?? '—' }}
</div>

<div class="body">
    <p><strong>Dear {{ $employeeName }},</strong></p>
    @if($bodyHtml !== '')
        {!! $bodyHtml !!}
    @elseif($document->type === \App\Modules\HRMS\Documents\Models\HRDocument::TYPE_EMPLOYMENT_LETTER)
        <p>We are pleased to confirm your employment with EXIN INTERNET SERVICES PRIVATE LIMITED effective from {{ $joiningDate }} as {{ $designation }}.</p>
        <p>You are expected to perform your duties diligently and comply with all organizational policies, procedures, and professional standards of the company.</p>
        <p>Your employment shall remain subject to satisfactory performance, adherence to company guidelines, and successful completion of any applicable probation period.</p>
        <p>We welcome you to the organization and look forward to your valuable contribution toward our continued success.</p>
    @elseif($document->type === \App\Modules\HRMS\Documents\Models\HRDocument::TYPE_PERMANENT_EMPLOYMENT_LETTER)
        <p>We are pleased to inform you that upon successful completion of your probation period, your employment with EXIN INTERNET SERVICES PRIVATE LIMITED has been confirmed as permanent effective from {{ $effectiveDate }}.</p>
        <p>Your performance, professionalism, and contribution during the probation period have been appreciated by the management.</p>
        <p>We look forward to your continued dedication, commitment, and long-term association with the organization.</p>
        <p>Congratulations on this achievement, and we wish you continued success in your journey with us.</p>
    @elseif($document->type === \App\Modules\HRMS\Documents\Models\HRDocument::TYPE_APPRECIATION_LETTER)
        <p>We would like to express our sincere appreciation for your valuable contribution, dedication, and commitment toward your responsibilities at EXIN INTERNET SERVICES PRIVATE LIMITED.</p>
        <p>Your professionalism, hard work, and positive attitude have been recognized and highly appreciated by the management.</p>
        <p>Thank you for your continued efforts and contribution to the organization’s success. We encourage you to maintain the same level of excellence in the future.</p>
        <p>Keep up the great work.</p>
    @elseif($document->type === \App\Modules\HRMS\Documents\Models\HRDocument::TYPE_INTERNSHIP_APPOINTMENT_LETTER)
        <p>We are pleased to offer you an internship opportunity with EXIN INTERNET SERVICES PRIVATE LIMITED, effective from {{ $joiningDate }}.</p>
        <p>The internship tenure is {{ $employee?->internship_period_months ?? '—' }} month(s), with an expected completion date of {{ $internshipEnd ?? '—' }}.</p>
        <p>During this internship, you are expected to maintain professional conduct, follow company policies, and complete responsibilities assigned by your reporting manager.</p>
    @elseif($document->type === \App\Modules\HRMS\Documents\Models\HRDocument::TYPE_RELIEVING_LETTER)
        <p>To whom it may concern,</p>
        <p>This is to certify that {{ $employeeName }} (Employee ID: {{ $employee?->employee_id ?? '—' }}) has been relieved from duties at EXIN INTERNET SERVICES PRIVATE LIMITED.</p>
        <p>The employee served in the role of {{ $designation }} and has been relieved as per company records and process completion.</p>
        <p>We thank the employee for their services and wish them success in future endeavors.</p>
    @elseif($document->type === \App\Modules\HRMS\Documents\Models\HRDocument::TYPE_SHOW_CAUSE_NOTICE)
        <p>This is to formally notify you regarding an observed concern related to your conduct and/or assigned responsibilities.</p>
        <p>You are required to submit a written explanation within 48 hours from receipt of this notice, clearly detailing the circumstances and your response.</p>
        <p>Failure to respond within the stipulated timeline may result in further disciplinary action as per company policy.</p>
    @elseif($document->type === \App\Modules\HRMS\Documents\Models\HRDocument::TYPE_WARNING_LETTER)
        <p>This letter serves as a formal warning regarding non-compliance with expected professional standards and organizational policies.</p>
        <p>You are advised to take immediate corrective action and ensure strict adherence to company guidelines moving forward.</p>
        <p>Please treat this communication seriously. Any recurrence may lead to further disciplinary measures, including impact on employment status.</p>
    @else
        <p>Please find this official communication issued by EXIN INTERNET SERVICES PRIVATE LIMITED.</p>
    @endif

    <div class="closing">
        <p>Yours sincerely,</p>
        @php
            $signaturePath = public_path('storage/document_signature.png');
            $signatureDataUri = null;
            if (is_file($signaturePath)) {
                try {
                    $signatureMime = mime_content_type($signaturePath) ?: 'image/png';
                    $signatureBase64 = base64_encode((string) file_get_contents($signaturePath));
                    $signatureDataUri = 'data:' . $signatureMime . ';base64,' . $signatureBase64;
                } catch (\Throwable) {
                    $signatureDataUri = null;
                }
            }
        @endphp
        @if($signatureDataUri)
            <div class="signature-img">
                <img src="{{ $signatureDataUri }}" alt="Authorized signature" style="height:48px;width:auto;">
            </div>
        @else
            <div class="signature-img">Signature</div>
        @endif
    </div>
</div>
</body>
</html>

