<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Payslip — {{ $slip->slip_number }}</title>
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap');
        @page { margin: 24px 28px 22px 28px; }
        * { box-sizing: border-box; }
        body {
            font-family: 'Poppins', 'DejaVu Sans', sans-serif;
            font-size: 10pt;
            line-height: 1.32;
            color: #111;
            margin: 0;
        }
        .header-row {
            display: table;
            width: 100%;
            margin-bottom: 8px;
            padding-bottom: 8px;
            border-bottom: 1px solid #333;
        }
        .header-row .cell {
            display: table-cell;
            vertical-align: middle;
        }
        .header-row .logo-wrap {
            width: 200px;
            padding-right: 16px;
        }
        .logo {
            width: 190px;
            height: auto;
            max-height: none;
            object-fit: contain;
            display: block;
        }
        .company-right {
            text-align: right;
            font-size: 9pt;
            line-height: 1.45;
            color: #222;
        }
        .company-right strong { font-size: 10.5pt; display: block; margin-bottom: 4px; }
        .doc-title {
            text-align: center;
            font-size: 16pt;
            font-weight: 700;
            letter-spacing: 0.5px;
            margin: 0 0 8px 0;
        }

        .meta-grid {
            width: 100%;
            margin-bottom: 8px;
            border-collapse: collapse;
        }
        .meta-grid td {
            width: 50%;
            vertical-align: top;
            padding: 2px 6px 2px 0;
            font-size: 9pt;
        }
        .meta-label { color: #333; display: inline-block; min-width: 118px; }
        .meta-val { font-weight: 600; }
        .meta-slip { vertical-align: top !important; }
        .meta-slip-inner { margin: 0; padding: 0; }
        .meta-slip-block { margin-bottom: 6px; }
        .meta-slip-block:last-child { margin-bottom: 0; }
        .meta-slip-block .meta-label { display: block; min-width: 0; margin-bottom: 1px; font-size: 8.5pt; }
        .meta-slip-block .meta-val { display: block; font-weight: 600; font-size: 9pt; line-height: 1.25; word-break: normal; overflow-wrap: normal; }

        .main-table {
            width: 100%;
            table-layout: fixed;
            border-collapse: collapse;
            border: 1px solid #222;
            margin-bottom: 8px;
        }
        .main-table th {
            background: #e8e8e8;
            border: 1px solid #222;
            padding: 5px 8px;
            font-size: 9pt;
            font-weight: 700;
            text-align: left;
        }
        .main-table th.amt { text-align: right; }
        .main-table td {
            border: 1px solid #222;
            padding: 4px 8px;
            vertical-align: top;
        }
        .main-table td.amt {
            text-align: right;
            font-variant-numeric: tabular-nums;
            white-space: nowrap;
        }
        .main-table .subtotal td {
            font-weight: 700;
            background: #f5f5f5;
        }
        .main-table .subtotal td.amt {
            white-space: nowrap;
        }

        .net-block {
            text-align: center;
            margin: 8px 0 6px 0;
            padding: 8px 12px;
            border: 1px solid #222;
            background: #fafafa;
            page-break-inside: avoid;
        }
        .net-label { font-size: 9.5pt; font-weight: 700; margin-bottom: 4px; }
        .net-fig { font-size: 14pt; font-weight: 700; letter-spacing: 0.3px; }
        .net-words {
            margin-top: 5px;
            font-size: 9pt;
            font-style: italic;
            color: #222;
            line-height: 1.28;
        }

        .footer-note {
            text-align: center;
            font-size: 8pt;
            color: #555;
            margin-top: 6px;
            padding-top: 5px;
            border-top: 1px solid #ccc;
            line-height: 1.25;
            page-break-inside: avoid;
        }
        .hash { font-size: 7.5pt; color: #666; margin-top: 3px; letter-spacing: 0.2px; line-height: 1.2; }
    </style>
</head>
<body>
    @php
        $profile = $slip->employeeProfile;
        $rupee = '₹';
        $fmt = static fn ($n) => $rupee.' '.number_format((float) $n, 2);
        $joining = $profile->joining_date ?? $profile->join_date;
        $joinStr = $joining ? $joining->format('Y-m-d') : '—';
        $deptName = $profile->orgDepartment?->name ?? ($profile->department ?: '—');
        $desigName = $profile->orgDesignation?->name ?? ($profile->designation ?: '—');
        $periodLabel = $run->period_start->format('F Y');
        if (! $run->period_start->isSameMonth($run->period_end)) {
            $periodLabel = $run->period_start->format('d M Y').' – '.$run->period_end->format('d M Y');
        }
        $workedDays = $slip->working_days;
        if (($workedDays === null || (int) $workedDays === 0) && $profile) {
            $joinRaw = $profile->joining_date ?? $profile->join_date;
            $pStart = $run->period_start->copy()->startOfDay();
            $pEnd = $run->period_end->copy()->startOfDay();
            $effStart = $joinRaw
                ? $pStart->copy()->max(\Carbon\Carbon::parse($joinRaw)->startOfDay())
                : $pStart->copy();
            if ($effStart->lte($pEnd)) {
                $recalc = app(\App\Services\HRMS\CalendarService::class)->countWorkingDaysBetween($effStart, $pEnd);
                if ($recalc > 0) {
                    $workedDays = $recalc;
                }
            }
        }
        if ($workedDays === null) {
            $workedDays = '—';
        }
        $earningRows = $slip->earning_lines ?? [];
        $deductionRows = $slip->deduction_lines ?? [];
        $maxDetail = max(count($earningRows), count($deductionRows), 1);
        $netAmt = (float) ($slip->net ?? 0);
        $amountInWords = '—';
        try {
            if (class_exists(\NumberFormatter::class)) {
                $rupees = (int) floor($netAmt);
                $paise = (int) round(($netAmt - $rupees) * 100);
                $nf = new \NumberFormatter('en', \NumberFormatter::SPELLOUT);
                $parts = [];
                if ($rupees > 0) {
                    $parts[] = ucfirst($nf->format($rupees)).' Rupee'.($rupees === 1 ? '' : 's');
                }
                if ($paise > 0) {
                    $parts[] = ucfirst($nf->format($paise)).' Paise';
                }
                $amountInWords = $parts === [] ? 'Zero Rupees' : implode(' and ', $parts);
                $amountInWords .= ' Only';
            }
        } catch (\Throwable $e) {
            $amountInWords = '—';
        }
    @endphp

    <div class="header-row">
        <div class="cell logo-wrap">
            <img class="logo" src="https://shizzy.in/images/shizzy-logo-red.png" alt="Shizzy">
        </div>
        <div class="cell company-right">
            <strong>EXIN INTERNET SERVICES PRIVATE LIMITED</strong>
            Spaze Edge, Sector 47, Gurugram, Haryana — 122018<br>
            www.shizzy.in · office@shizzy.in
        </div>
    </div>

    <div class="doc-title">Payslip</div>

    <table class="meta-grid">
        <tr>
            <td>
                <span class="meta-label">Date of joining</span>
                <span class="meta-val">{{ $joinStr }}</span>
            </td>
            <td>
                <span class="meta-label">Employee name</span>
                <span class="meta-val">{{ $profile->user?->name ?? '—' }}</span>
            </td>
        </tr>
        <tr>
            <td>
                <span class="meta-label">Pay period</span>
                <span class="meta-val">{{ $periodLabel }}</span>
            </td>
            <td>
                <span class="meta-label">Designation</span>
                <span class="meta-val">{{ $desigName }}</span>
            </td>
        </tr>
        <tr>
            <td>
                <span class="meta-label">Working days</span>
                <span class="meta-val">{{ $workedDays }}</span>
            </td>
            <td>
                <span class="meta-label">Department</span>
                <span class="meta-val">{{ $deptName }}</span>
            </td>
        </tr>
        <tr>
            <td>
                <span class="meta-label">Slip number</span>
                <span class="meta-val">{{ $slip->slip_number }}</span>
            </td>
            <td>
                <span class="meta-label">Issued on</span>
                <span class="meta-val">{{ optional($slip->issued_at)->format('Y-m-d') ?? '—' }}</span>
            </td>
        </tr>
        <tr>
            <td>
                <span class="meta-label">Employee ID</span>
                <span class="meta-val">{{ $profile->employee_id }}</span>
            </td>
        </tr>
    </table>

    <table class="main-table">
        <thead>
        <tr>
            <th style="width:31%">Earnings</th>
            <th class="amt" style="width:19%">Amount</th>
            <th style="width:31%">Deductions</th>
            <th class="amt" style="width:19%">Amount</th>
        </tr>
        </thead>
        <tbody>
        @for($i = 0; $i < $maxDetail; $i++)
            <tr>
                <td>
                    @if(isset($earningRows[$i]))
                        {{ $earningRows[$i]['name'] ?? '—' }}
                    @else
                        &nbsp;
                    @endif
                </td>
                <td class="amt">
                    @isset($earningRows[$i])
                        {{ $fmt($earningRows[$i]['amount'] ?? 0) }}
                    @else
                        —
                    @endisset
                </td>
                <td>
                    @isset($deductionRows[$i])
                        {{ $deductionRows[$i]['name'] ?? '—' }}
                    @else
                        &nbsp;
                    @endisset
                </td>
                <td class="amt">
                    @isset($deductionRows[$i])
                        {{ $fmt($deductionRows[$i]['amount'] ?? 0) }}
                    @else
                        —
                    @endisset
                </td>
            </tr>
        @endfor
        <tr class="subtotal">
            <td>Total earnings</td>
            <td class="amt">{{ $fmt($slip->gross ?? 0) }}</td>
            <td>Total deductions</td>
            <td class="amt">{{ $fmt($slip->deductions ?? 0) }}</td>
        </tr>
        </tbody>
    </table>

    <div class="net-block">
        <div class="net-label">Net pay</div>
        <div class="net-fig">{{ $fmt($slip->net ?? 0) }}</div>
        <div class="net-words">{{ $amountInWords }}</div>
    </div>

    <div class="footer-note">
        This is a system-generated payslip.
        @if(!empty($slip->document_hash))
            <div class="hash">DOC HASH: {{ $slip->document_hash }}</div>
        @endif
    </div>
</body>
</html>
