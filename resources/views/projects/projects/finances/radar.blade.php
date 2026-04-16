@extends('hrms.layout')

@section('content')
    <style>
        .radar-shell { display: grid; gap: 14px; }
        .radar-hero {
            background: linear-gradient(135deg, #1d4ed8, #2563eb 60%, #3b82f6);
            color: #fff;
            border-radius: 16px;
            padding: 18px;
            border: 1px solid rgba(255,255,255,.18);
            box-shadow: 0 10px 24px rgba(37,99,235,.28);
        }
        .radar-hero .muted { color: rgba(255,255,255,.85); }
        .radar-hero h1 { color: #fff; margin-bottom: 6px; }
        .radar-content { display: grid; gap: 14px; }
        .radar-kpi-grid {
            display: grid;
            gap: 14px;
            grid-template-columns: repeat(3, minmax(0, 1fr));
        }
        .radar-kpi-card {
            border: 1px solid var(--border);
            border-radius: 14px;
            padding: 14px;
            background: #fff;
        }
        .radar-kpi-label { color: var(--muted); font-size: 12px; }
        .radar-kpi-value { font-size: 26px; font-weight: 700; margin-top: 6px; color: #0f172a; }
        .radar-kpi-sub { color: var(--muted); font-size: 12px; margin-top: 8px; }
        .radar-section-title {
            margin: 0 0 10px;
            font-size: 15px;
            font-weight: 700;
            color: #334155;
            letter-spacing: .15px;
        }
        .radar-surface {
            border: 1px solid var(--border);
            border-radius: 16px;
            background: #fff;
            padding: 14px;
        }
        @media (max-width: 980px) {
            .radar-kpi-grid { grid-template-columns: 1fr; }
        }
    </style>
    <div class="radar-shell">
        <div class="radar-hero">
            <div class="row" style="justify-content:space-between;align-items:flex-start;">
                <div>
                    <h1>Project Finance Radar</h1>
                    <p class="muted" style="margin:0;">
                        Finance summary powered by revenue streams and Zoho Books for <strong>{{ $monthLabel }}</strong>
                    </p>
                </div>
                <div class="row" style="justify-content:flex-end;">
                    <a class="pill" href="{{ route('projects.finances.radar', ['month' => $monthContext['prev_month']]) }}" style="background:#fff;border-color:#fff;color:#1e3a8a;">Previous Month</a>
                    @if(!$monthContext['is_current_month_view'])
                        <a class="pill" href="{{ route('projects.finances.radar', ['month' => now()->format('Y-m')]) }}" style="background:#fff;border-color:#fff;color:#1e3a8a;">Current Month</a>
                    @endif
                    <a class="pill" href="{{ route('projects.finances.radar', ['month' => $monthContext['next_month']]) }}" style="background:#fff;border-color:#fff;color:#1e3a8a;">Next Month</a>
                    <a class="pill" href="{{ route('projects.index') }}" style="background:#fff;border-color:#fff;color:#1e3a8a;">Back to projects</a>
                </div>
            </div>
        </div>

        <div class="radar-content">
            <div class="radar-kpi-grid">
                <div class="radar-kpi-card">
                    <div class="radar-kpi-label">Fixed Monthly Retainers</div>
                    <div class="radar-kpi-value">₹ {{ number_format((float)($totals['fixed_monthly_retainers'] ?? 0), 2) }}</div>
                    <div class="radar-kpi-sub">
                        Monthly normalized contribution from active retainer streams.
                    </div>
                </div>
                <div class="radar-kpi-card">
                    <div class="radar-kpi-label">Fixed Monthly Installments</div>
                    <div class="radar-kpi-value">₹ {{ number_format((float)($totals['fixed_monthly_installments'] ?? 0), 2) }}</div>
                    <div class="radar-kpi-sub">
                        Monthly normalized contribution from active installment streams.
                    </div>
                </div>
                <div class="radar-kpi-card">
                    <div class="radar-kpi-label">Fixed Monthly (Lifetime)</div>
                    <div class="radar-kpi-value">₹ {{ number_format((float)($totals['fixed_monthly_lifetimes'] ?? 0), 2) }}</div>
                    <div class="radar-kpi-sub">
                        Lifetime-type revenue streams: expected value spread monthly (÷12).
                    </div>
                </div>
            </div>

            <div class="radar-surface">
                <div class="radar-section-title">Total Fixed Earning (Monthly)</div>
                <p class="muted" style="margin-top:0;">
                    Based on active streams and cycle-normalized expected project value.
                    Quarterly is divided by 3; annual and lifetime cycles use ÷12 for a monthly figure.
                </p>
                <div class="radar-kpi-value">₹ {{ number_format((float)($totals['fixed_monthly_total'] ?? 0), 2) }}</div>
            </div>

            <div class="radar-surface">
                <div class="radar-section-title">Invoice Summary</div>
                <div class="radar-kpi-grid">
                    <div class="radar-kpi-card">
                        <div class="radar-kpi-label">Total Invoices</div>
                        <div class="radar-kpi-value">{{ number_format((int)($totals['zoho_total_invoices'] ?? 0)) }}</div>
                    </div>
                    <div class="radar-kpi-card">
                        <div class="radar-kpi-label">Total Invoice Amount</div>
                        <div class="radar-kpi-value">₹ {{ number_format((float)($totals['zoho_total_invoice_amount'] ?? 0), 2) }}</div>
                    </div>
                    <div class="radar-kpi-card">
                        <div class="radar-kpi-label">Total Collected</div>
                        <div class="radar-kpi-value">₹ {{ number_format((float)($totals['zoho_total_collected'] ?? 0), 2) }}</div>
                    </div>
                    <div class="radar-kpi-card">
                        <div class="radar-kpi-label">Total Outstanding</div>
                        <div class="radar-kpi-value">₹ {{ number_format((float)($totals['zoho_total_balance'] ?? 0), 2) }}</div>
                    </div>
                    <div class="radar-kpi-card">
                        <div class="radar-kpi-label">Overdue Invoices</div>
                        <div class="radar-kpi-value">{{ number_format((int)($totals['zoho_overdue_count'] ?? 0)) }}</div>
                    </div>
                </div>
            </div>

            <div class="radar-surface">
                <div class="radar-section-title">Current Month Invoice Stats</div>
                <div class="radar-kpi-grid">
                    <div class="radar-kpi-card">
                        <div class="radar-kpi-label">Current Month Invoices</div>
                        <div class="radar-kpi-value">{{ number_format((int)($currentMonthInvoiceStats['invoice_count'] ?? 0)) }}</div>
                    </div>
                    <div class="radar-kpi-card">
                        <div class="radar-kpi-label">Current Month Invoice Amount</div>
                        <div class="radar-kpi-value">₹ {{ number_format((float)($currentMonthInvoiceStats['invoice_amount'] ?? 0), 2) }}</div>
                    </div>
                    <div class="radar-kpi-card">
                        <div class="radar-kpi-label">Current Month Collected</div>
                        <div class="radar-kpi-value">₹ {{ number_format((float)($currentMonthInvoiceStats['collected'] ?? 0), 2) }}</div>
                    </div>
                </div>
            </div>

            <div class="radar-surface">
                <div class="radar-section-title">Client-wise Invoice Summary</div>
                @if(($clientSummary ?? collect())->isEmpty())
                    <p class="muted">No invoice data for selected month.</p>
                @else
                    <div class="table-wrap">
                        <table>
                            <thead>
                            <tr>
                                <th>Client</th>
                                <th>Total Invoices</th>
                                <th>Invoice Amount</th>
                                <th>Collected</th>
                                <th>Outstanding</th>
                            </tr>
                            </thead>
                            <tbody>
                            @foreach($clientSummary as $row)
                                <tr>
                                    <td><strong>{{ $row['label'] }}</strong></td>
                                    <td>{{ number_format((int) $row['invoice_count']) }}</td>
                                    <td>₹ {{ number_format((float) $row['invoice_amount'], 2) }}</td>
                                    <td>₹ {{ number_format((float) $row['collected'], 2) }}</td>
                                    <td>₹ {{ number_format((float) $row['balance'], 2) }}</td>
                                </tr>
                            @endforeach
                            </tbody>
                        </table>
                    </div>
                @endif
            </div>

            <div class="radar-surface">
                <div class="radar-section-title">Project Category-wise Invoice Summary</div>
                @if(($categorySummary ?? collect())->isEmpty())
                    <p class="muted">No invoice data for selected month.</p>
                @else
                    <div class="table-wrap">
                        <table>
                            <thead>
                            <tr>
                                <th>Category</th>
                                <th>Total Invoices</th>
                                <th>Invoice Amount</th>
                                <th>Collected</th>
                                <th>Outstanding</th>
                            </tr>
                            </thead>
                            <tbody>
                            @foreach($categorySummary as $row)
                                <tr>
                                    <td><strong>{{ $row['label'] }}</strong></td>
                                    <td>{{ number_format((int) $row['invoice_count']) }}</td>
                                    <td>₹ {{ number_format((float) $row['invoice_amount'], 2) }}</td>
                                    <td>₹ {{ number_format((float) $row['collected'], 2) }}</td>
                                    <td>₹ {{ number_format((float) $row['balance'], 2) }}</td>
                                </tr>
                            @endforeach
                            </tbody>
                        </table>
                    </div>
                @endif
            </div>

            <div class="radar-surface">
                <div class="radar-section-title">Fixed Earning by Project</div>
                <div class="table-wrap">
                    <table>
                        <thead>
                        <tr>
                            <th>Project</th>
                            <th>Retainers (Monthly)</th>
                            <th>Installments (Monthly)</th>
                            <th>Lifetime (Monthly)</th>
                            <th>Total Fixed Monthly</th>
                        </tr>
                        </thead>
                        <tbody>
                        @foreach($rows as $r)
                            @php($p = $r['project'])
                            <tr>
                                <td><strong>{{ $p->project_code }}</strong></td>
                                <td>₹ {{ number_format((float)($r['fixed_monthly_retainers'] ?? 0), 2) }}</td>
                                <td>₹ {{ number_format((float)($r['fixed_monthly_installments'] ?? 0), 2) }}</td>
                                <td>₹ {{ number_format((float)($r['fixed_monthly_lifetimes'] ?? 0), 2) }}</td>
                                <td><strong>₹ {{ number_format((float)($r['fixed_monthly_total'] ?? 0), 2) }}</strong></td>
                            </tr>
                        @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
@endsection

