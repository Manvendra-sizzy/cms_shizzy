@extends('hrms.layout')

@section('content')
    <style>
        .pf-shell { display:grid; gap:14px; }
        .pf-hero {
            background: linear-gradient(135deg, #0f172a, #1d4ed8 55%, #2563eb);
            border-radius: 16px;
            color: #fff;
            padding: 18px;
            border: 1px solid rgba(255,255,255,.18);
            box-shadow: 0 12px 28px rgba(15,23,42,.22);
        }
        .pf-hero h1 { color:#fff; margin-bottom:6px; }
        .pf-hero .muted { color: rgba(255,255,255,.86); }
        .pf-kpi-grid { display:grid; grid-template-columns:repeat(3,minmax(0,1fr)); gap:14px; }
        .pf-kpi-card { border:1px solid var(--border); border-radius:14px; background:#fff; padding:14px; }
        .pf-kpi-label { color:var(--muted); font-size:12px; }
        .pf-kpi-value { font-size:24px; font-weight:700; margin-top:6px; color:#0f172a; }
        .stream-list { display:grid; gap:14px; margin-top:14px; }
        .stream-item {
            border:1px solid rgba(15,23,42,.08);
            border-radius:16px;
            padding:0;
            background: linear-gradient(145deg, #fff 0%, #f8fafc 100%);
            box-shadow: 0 4px 18px rgba(15,23,42,.06);
            overflow:hidden;
        }
        .stream-item-hd {
            display:flex; justify-content:space-between; align-items:flex-start; gap:12px;
            padding:14px 16px;
            border-bottom:1px solid rgba(15,23,42,.06);
            background: linear-gradient(90deg, rgba(37,99,235,.06), transparent);
        }
        .stream-item-title { font-weight:700; font-size:16px; color:#0f172a; margin:0; }
        .stream-type-pill {
            display:inline-flex; align-items:center;
            padding:4px 10px; border-radius:999px; font-size:11px; font-weight:700;
            letter-spacing:.02em; text-transform:uppercase;
            background:#1e3a8a; color:#fff;
        }
        .stream-actions { display:flex; flex-wrap:wrap; gap:8px; justify-content:flex-end; align-items:center; }
        .stream-item-bd { padding:14px 16px 16px; }
        .stream-grid {
            display:grid;
            grid-template-columns: repeat(auto-fill, minmax(160px, 1fr));
            gap:10px 14px;
        }
        .stream-k { color:var(--muted); font-size:11px; text-transform:uppercase; letter-spacing:.04em; }
        .stream-v { font-weight:600; color:#0f172a; font-size:14px; margin-top:2px; }
        .stream-notes-preview {
            margin-top:12px; padding-top:12px; border-top:1px dashed rgba(15,23,42,.1);
            font-size:13px; color:#334155; line-height:1.45;
        }
        .stream-chip {
            display:inline-flex;
            align-items:center;
            gap:6px;
            padding:5px 12px;
            border-radius:999px;
            font-size:11px;
            font-weight:700;
            letter-spacing:.08em;
            text-transform:uppercase;
            box-shadow:0 1px 3px rgba(15,23,42,.12);
        }
        .stream-chip-open {
            border:1px solid rgba(255,255,255,.25);
            background:linear-gradient(165deg, #34d399 0%, #059669 55%, #047857 100%);
            color:#fff;
            text-shadow:0 1px 0 rgba(0,0,0,.12);
        }
        .stream-chip-open::before {
            content:'';
            width:6px;
            height:6px;
            border-radius:50%;
            background:#ecfdf5;
            box-shadow:0 0 0 1px rgba(255,255,255,.6);
            flex-shrink:0;
        }
        .stream-chip-closed {
            border:1px solid rgba(255,255,255,.2);
            background:linear-gradient(165deg, #94a3b8 0%, #64748b 50%, #475569 100%);
            color:#fff;
            text-shadow:0 1px 0 rgba(0,0,0,.15);
        }
        .rs-field-hidden { display:none !important; }
        .rs-modal-overlay {
            position: fixed; inset: 0; background: rgba(15,23,42,.45);
            display: none; align-items: center; justify-content: center; z-index: 1000; padding: 16px;
        }
        .rs-modal {
            width: 100%; max-width: 520px; background: #fff; border: 1px solid var(--border);
            border-radius: 16px; box-shadow: 0 22px 48px rgba(2,6,23,.28); padding: 16px;
        }
        .rs-modal-title { margin: 0; font-size: 18px; font-weight: 700; color: #0f172a; }
        @media (max-width:980px) {
            .pf-kpi-grid { grid-template-columns:1fr; }
        }
    </style>
    <div class="pf-shell">
    <div class="pf-hero">
        <div class="row" style="justify-content:space-between;align-items:flex-start;">
            <div>
                <h1>Projects Finances: {{ $project->name }} <span class="muted">({{ $project->project_code }})</span></h1>
                <p class="muted" style="margin:0;">
                    Client: <strong>{{ $project->zohoClient?->contact_name ?: ($project->zohoClient?->company_name ?: $project->client?->name ?? '—') }}</strong>
                    · Category: <strong>{{ $project->category }}</strong>
                    · Billing: <strong>{{ $project->billing_type }}</strong>
                </p>
                <p class="muted" style="margin-top:8px;">
                    Status: <strong>{{ $project->status }}</strong>
                </p>
            </div>
            <div class="row" style="gap:10px;flex-wrap:wrap;justify-content:flex-end;">
                <a class="pill" href="{{ route('projects.show', $project) }}" style="background:#fff;border-color:#fff;color:#1e3a8a;">Back to project</a>
                <a class="pill" href="{{ route('projects.edit', $project) }}" style="background:#fff;border-color:#fff;color:#1e3a8a;">Edit</a>
            </div>
        </div>
        <p class="muted" style="margin:8px 0 0;">
            Keep billing operations in one place: revenue setup, invoices, receipts, and reimbursements.
        </p>
    </div>

    <div class="pf-kpi-grid">
        <div class="pf-kpi-card">
            <div class="pf-kpi-label">Revenue streams</div>
            <div class="pf-kpi-value">{{ $streams->count() }}</div>
        </div>
        <div class="pf-kpi-card">
            <div class="pf-kpi-label">Zoho invoices</div>
            <div class="pf-kpi-value">{{ ($zohoInvoices ?? collect())->count() }}</div>
        </div>
        <div class="pf-kpi-card">
            <div class="pf-kpi-label">Mapped Project ID</div>
            <div class="pf-kpi-value" style="font-size:20px;">{{ $project->project_code }}</div>
        </div>
    </div>

    <div class="card">
        <h2>Revenue</h2>
        <p class="muted" style="margin-top:6px;">
            Add and manage revenue streams. This section tracks actual revenue streams only (reimbursements stay in the Reimbursements section).
        </p>

        <div class="grid" style="grid-template-columns: 1.1fr .9fr; gap:14px; margin-top:12px;">
            <div class="card">
                <h2>Add revenue stream</h2>
                <p class="muted" style="margin-top:6px;">New streams start as <strong>open</strong>. Use <strong>Change status</strong> on a stream to close it with a date and remarks.</p>

                <form method="post" action="{{ route('projects.revenue.streams.store', $project) }}" class="form-wrap" id="addRevenueStreamForm" novalidate>
                    @csrf

                    <div class="form-grid cols-2">
                        <div class="field">
                            <label>Stream name (optional)</label>
                            <input name="name" value="{{ old('name') }}" placeholder="e.g. Monthly retainer">
                        </div>

                        <div class="field">
                            <label>Type</label>
                            <select name="type" id="rsType" required>
                                @foreach(['retainer'=>'Retainer','usage'=>'Usage','reimbursement'=>'Reimbursement','fixed'=>'Fixed','installment'=>'Installment','lifetime'=>'Lifetime'] as $k=>$v)
                                    <option value="{{ $k }}" @selected(old('type','retainer')===$k)>{{ $v }}</option>
                                @endforeach
                            </select>
                        </div>

                        <div class="field rs-depends rs-recurring rs-installment" id="rsCycleWrap">
                            <label id="rsCycleLabel">Billing cycle</label>
                            <select name="billing_cycle" id="rsBillingCycle"></select>
                        </div>

                        <div class="field rs-depends rs-recurring rs-installment rs-lifetime-opt">
                            <label id="rsNextBillLabel">Next billing date</label>
                            <input name="next_billing_date" id="rsNextBilling" type="date" value="{{ old('next_billing_date') }}">
                        </div>

                        <div class="field rs-depends rs-all">
                            <label>Expected project value (₹)</label>
                            <input name="expected_total_value" id="rsExpectedTotalValue" type="number" step="0.01" min="0" value="{{ old('expected_total_value', 0) }}" required>
                        </div>

                        <div class="field rs-depends rs-all">
                            <label>Start date</label>
                            <input name="start_date" type="date" value="{{ old('start_date') }}" required>
                        </div>

                        <div class="field rs-depends rs-fixed">
                            <label>Delivery / end date</label>
                            <input name="end_date" id="rsEndDate" type="date" value="{{ old('end_date') }}">
                        </div>

                        <div class="field rs-depends rs-installment">
                            <label>Installment value (₹)</label>
                            <input name="installment_value" type="number" step="0.01" value="{{ old('installment_value') }}" id="rsInstallmentValue">
                        </div>

                        <div class="field rs-depends rs-installment">
                            <label>Number of installments</label>
                            <input name="installment_count" type="number" step="1" value="{{ old('installment_count') }}" id="rsInstallmentCount">
                        </div>

                        <div class="field" style="grid-column:1 / -1;">
                            <label>Notes</label>
                            <textarea name="notes" required>{{ old('notes') }}</textarea>
                        </div>
                    </div>

                    <button class="btn" type="submit">Add stream</button>
                </form>
            </div>

            <div class="card">
                <h2>Revenue streams</h2>
                @if($streams->isEmpty())
                    <p class="muted" style="margin-top:10px;">No revenue streams yet.</p>
                @else
                    @php
                        $cycleLabels = [
                            'one_time' => 'One-time',
                            'monthly' => 'Monthly',
                            'quarterly' => 'Quarterly',
                            'annual' => 'Annual',
                            'lifetime' => 'Lifetime',
                            'custom' => 'Custom',
                        ];
                        $typeLabels = [
                            'retainer' => 'Retainer',
                            'usage' => 'Usage',
                            'reimbursement' => 'Reimbursement',
                            'fixed' => 'Fixed',
                            'installment' => 'Installment',
                            'lifetime' => 'Lifetime',
                            'annual' => 'Lifetime',
                        ];
                    @endphp
                    <div class="stream-list">
                        @foreach($streams as $s)
                            <div class="stream-item">
                                <div class="stream-item-hd">
                                    <div>
                                        <p class="stream-item-title">{{ $s->name ?: ($typeLabels[$s->type] ?? ucfirst($s->type)).' stream' }}</p>
                                        <span class="stream-type-pill">{{ $typeLabels[$s->type] ?? ucfirst($s->type) }}</span>
                                    </div>
                                    <div class="stream-actions">
                                        <span class="stream-chip {{ $s->active ? 'stream-chip-open' : 'stream-chip-closed' }}">{{ $s->active ? 'Open' : 'Closed' }}</span>
                                        <a class="pill" href="{{ route('projects.revenue.streams.edit', [$project, $s]) }}">Edit</a>
                                        @if($s->active)
                                            <button type="button" class="pill premium-cta rs-open-close" style="cursor:pointer;border-style:solid;"
                                                data-action="{{ route('projects.revenue.streams.close', [$project, $s]) }}">Change status</button>
                                        @endif
                                    </div>
                                </div>
                                <div class="stream-item-bd">
                                    <div class="stream-grid">
                                        @if(in_array($s->type, ['retainer','usage','reimbursement','installment'], true))
                                            <div>
                                                <div class="stream-k">{{ $s->type === 'installment' ? 'Installment cycle' : 'Billing cycle' }}</div>
                                                <div class="stream-v">{{ $cycleLabels[$s->billing_cycle] ?? ($s->billing_cycle ?: '—') }}</div>
                                            </div>
                                        @endif
                                        @if($s->type === 'lifetime')
                                            <div>
                                                <div class="stream-k">Recognition</div>
                                                <div class="stream-v">Lifetime</div>
                                            </div>
                                        @endif
                                        <div>
                                            <div class="stream-k">Expected value</div>
                                            <div class="stream-v">₹ {{ number_format((float)($s->expected_total_value ?? 0), 2) }}</div>
                                        </div>
                                        <div>
                                            <div class="stream-k">Start</div>
                                            <div class="stream-v">{{ optional($s->start_date)->format('Y-m-d') ?? '—' }}</div>
                                        </div>
                                        @if($s->type === 'fixed')
                                            <div>
                                                <div class="stream-k">Delivery / end</div>
                                                <div class="stream-v">{{ optional($s->end_date)->format('Y-m-d') ?? optional($s->next_billing_date)->format('Y-m-d') ?? '—' }}</div>
                                            </div>
                                        @else
                                            <div>
                                                <div class="stream-k">Next billing</div>
                                                <div class="stream-v">{{ optional($s->next_billing_date)->format('Y-m-d') ?? '—' }}</div>
                                            </div>
                                        @endif
                                        @if($s->type === 'installment' && ($s->rate_per_unit || $s->quantity))
                                            <div>
                                                <div class="stream-k">Installment</div>
                                                <div class="stream-v">₹ {{ number_format((float)($s->rate_per_unit ?? 0), 2) }} × {{ (int) ($s->quantity ?? 0) }}</div>
                                            </div>
                                        @endif
                                        @if(!$s->active && $s->closed_at)
                                            <div style="grid-column:1 / -1;">
                                                <div class="stream-k">Closed on</div>
                                                <div class="stream-v">{{ $s->closed_at->format('Y-m-d') }}</div>
                                            </div>
                                        @endif
                                    </div>
                                    @if($s->notes)
                                        <div class="stream-notes-preview">{{ \Illuminate\Support\Str::limit($s->notes, 220) }}</div>
                                    @endif
                                    @if(!$s->active && $s->closed_remark)
                                        <div class="stream-notes-preview" style="border-top-style:solid;">
                                            <span class="stream-k">Close remarks</span>
                                            <div style="margin-top:4px;">{{ $s->closed_remark }}</div>
                                        </div>
                                    @endif
                                </div>
                            </div>
                        @endforeach
                    </div>
                @endif
            </div>
        </div>
    </div>

    <div id="rsCloseOverlay" class="rs-modal-overlay">
        <div class="rs-modal">
            <div class="row" style="justify-content:space-between;align-items:center;">
                <h3 class="rs-modal-title">Close revenue stream</h3>
                <button class="pill" type="button" id="rsCloseModalDismiss">Cancel</button>
            </div>
            <p class="muted" style="margin:8px 0 0;font-size:13px;">Closing marks this stream as finished. To track future revenue, add a new stream.</p>
            <form method="post" id="rsCloseForm" style="margin-top:12px;" @if(session('pending_close_stream_id')) action="{{ route('projects.revenue.streams.close', [$project, session('pending_close_stream_id')]) }}" @endif>
                @csrf
                @error('stream_close')
                    <p class="muted" style="color:#b91c1c;margin:0 0 8px;font-size:13px;">{{ $message }}</p>
                @enderror
                @error('effective_date')
                    <p class="muted" style="color:#b91c1c;margin:0 0 8px;font-size:13px;">{{ $message }}</p>
                @enderror
                @error('remark')
                    <p class="muted" style="color:#b91c1c;margin:0 0 8px;font-size:13px;">{{ $message }}</p>
                @enderror
                <div class="field">
                    <label>Effective date</label>
                    <input type="date" name="effective_date" value="{{ old('effective_date', now()->toDateString()) }}" required>
                </div>
                <div class="field">
                    <label>Remarks</label>
                    <textarea name="remark" required placeholder="Why this stream is being closed…">{{ old('remark') }}</textarea>
                </div>
                <button class="btn" type="submit">Mark as closed</button>
            </form>
        </div>
    </div>

    <div class="card" style="margin-top:14px;">
        <h2>Zoho Invoices</h2>
        <p class="muted" style="margin-top:6px;">Invoices synced from Zoho and mapped by Project ID = <strong>{{ $project->project_code }}</strong>.</p>

        @if(($zohoInvoices ?? collect())->isEmpty())
            <p class="muted" style="margin-top:10px;">No Zoho invoices mapped to this project yet.</p>
        @else
            <div class="table-wrap" style="margin-top:10px;">
                <table>
                    <thead>
                    <tr>
                        <th>Invoice Number</th>
                        <th>Zoho Invoice ID</th>
                        <th>Customer ID</th>
                        <th>Project ID</th>
                        <th>Status</th>
                        <th>Total</th>
                        <th>Balance</th>
                        <th>Invoice Date</th>
                        <th>Due Date</th>
                        <th>PDF</th>
                    </tr>
                    </thead>
                    <tbody>
                    @foreach($zohoInvoices as $invoice)
                        <tr>
                            <td>{{ $invoice->invoice_number ?: '—' }}</td>
                            <td>{{ $invoice->zoho_invoice_id }}</td>
                            <td>{{ $invoice->zoho_customer_id ?: '—' }}</td>
                            <td>{{ $invoice->project_id ?: '—' }}</td>
                            <td>{{ $invoice->status ?: '—' }}</td>
                            <td>{{ number_format((float) $invoice->total, 2) }}</td>
                            <td>{{ number_format((float) $invoice->balance, 2) }}</td>
                            <td>{{ $invoice->invoice_date?->format('Y-m-d') ?: '—' }}</td>
                            <td>{{ $invoice->due_date?->format('Y-m-d') ?: '—' }}</td>
                            <td>
                                <a class="pill" href="{{ route('projects.finances.zoho_invoices.open', [$project, $invoice]) }}" target="_blank" rel="noopener">Open PDF</a>
                            </td>
                        </tr>
                    @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </div>
    </div>

    @push('scripts')
        <script>
            document.addEventListener('DOMContentLoaded', function () {
                const typeSel = document.getElementById('rsType');
                const cycleWrap = document.getElementById('rsCycleWrap');
                const cycleSel = document.getElementById('rsBillingCycle');
                const cycleLbl = document.getElementById('rsCycleLabel');
                const nextWrap = document.getElementById('rsNextBilling')?.closest('.field');
                const nextInp = document.getElementById('rsNextBilling');
                const nextLbl = document.getElementById('rsNextBillLabel');
                const endInp = document.getElementById('rsEndDate');
                const endWrap = endInp?.closest('.field');
                const instVal = document.getElementById('rsInstallmentValue');
                const instCnt = document.getElementById('rsInstallmentCount');
                const instValWrap = instVal?.closest('.field');
                const instCntWrap = instCnt?.closest('.field');
                const expectedValInp = document.getElementById('rsExpectedTotalValue');

                const recurringCycles = [
                    { v: 'monthly', t: 'Monthly' },
                    { v: 'quarterly', t: 'Quarterly' },
                    { v: 'annual', t: 'Annual' },
                    { v: 'one_time', t: 'One-time' },
                    { v: 'custom', t: 'Custom' },
                ];
                const installmentCycles = [
                    { v: 'monthly', t: 'Monthly' },
                    { v: 'quarterly', t: 'Quarterly' },
                    { v: 'lifetime', t: 'Lifetime' },
                    { v: 'one_time', t: 'One-time' },
                    { v: 'custom', t: 'Custom' },
                ];

                function fillCycles(list, selected) {
                    cycleSel.innerHTML = '';
                    list.forEach(function (o) {
                        const opt = document.createElement('option');
                        opt.value = o.v;
                        opt.textContent = o.t;
                        if (selected && selected === o.v) opt.selected = true;
                        cycleSel.appendChild(opt);
                    });
                    if (!selected && list[0]) cycleSel.value = list[0].v;
                }

                function setHidden(el, on) {
                    if (!el) return;
                    el.classList.toggle('rs-field-hidden', on);
                }

                function syncAddStreamForm() {
                    if (!typeSel || !cycleSel) return;
                    const t = typeSel.value;
                    const rec = ['retainer', 'usage', 'reimbursement'].indexOf(t) !== -1;
                    const inst = t === 'installment';
                    const fix = t === 'fixed';
                    const life = t === 'lifetime';

                    setHidden(cycleWrap, !rec && !inst);
                    if (rec || inst) {
                        cycleLbl.textContent = inst ? 'Installment cycle' : 'Billing cycle';
                        const cur = cycleSel.value;
                        if (inst) {
                            const allowed = installmentCycles.map(function (x) { return x.v; });
                            fillCycles(installmentCycles, allowed.indexOf(cur) !== -1 ? cur : null);
                        } else {
                            const allowed = recurringCycles.map(function (x) { return x.v; });
                            fillCycles(recurringCycles, allowed.indexOf(cur) !== -1 ? cur : null);
                        }
                    }

                    setHidden(nextWrap, !rec && !inst && !life);
                    if (nextInp && nextLbl) {
                        if (life) {
                            nextLbl.textContent = 'Next billing or review date (optional)';
                            nextInp.removeAttribute('required');
                        } else if (rec || inst) {
                            nextLbl.textContent = 'Next billing date';
                            nextInp.setAttribute('required', 'required');
                        } else {
                            nextInp.removeAttribute('required');
                        }
                    }

                    setHidden(endWrap, !fix);
                    if (endInp) {
                        if (fix) endInp.setAttribute('required', 'required');
                        else endInp.removeAttribute('required');
                    }

                    setHidden(instValWrap, !inst);
                    setHidden(instCntWrap, !inst);
                    if (instVal) {
                        if (inst) {
                            instVal.setAttribute('required', 'required');
                            instVal.setAttribute('min', '0');
                        } else {
                            instVal.removeAttribute('required');
                            instVal.removeAttribute('min');
                        }
                    }
                    if (instCnt) {
                        if (inst) {
                            instCnt.setAttribute('required', 'required');
                            instCnt.setAttribute('min', '1');
                        } else {
                            instCnt.removeAttribute('required');
                            instCnt.removeAttribute('min');
                        }
                    }

                    if (expectedValInp) {
                        if (t === 'reimbursement') {
                            expectedValInp.removeAttribute('required');
                        } else {
                            expectedValInp.setAttribute('required', 'required');
                        }
                    }
                }

                if (typeSel) {
                    typeSel.addEventListener('change', syncAddStreamForm);
                    syncAddStreamForm();
                }

                const overlay = document.getElementById('rsCloseOverlay');
                const closeForm = document.getElementById('rsCloseForm');
                const dismiss = document.getElementById('rsCloseModalDismiss');
                if (overlay && closeForm) {
                    closeForm.addEventListener('submit', function (e) {
                        const a = closeForm.getAttribute('action');
                        if (!a) {
                            e.preventDefault();
                        }
                    });
                    document.querySelectorAll('.rs-open-close').forEach(function (btn) {
                        btn.addEventListener('click', function () {
                            closeForm.setAttribute('action', btn.getAttribute('data-action'));
                            overlay.style.display = 'flex';
                        });
                    });
                    if (dismiss) dismiss.addEventListener('click', function () { overlay.style.display = 'none'; });
                    overlay.addEventListener('click', function (e) {
                        if (e.target === overlay) overlay.style.display = 'none';
                    });
                    @if(session('pending_close_stream_id') && ($errors->has('effective_date') || $errors->has('remark') || $errors->has('stream_close')))
                    overlay.style.display = 'flex';
                    @endif
                }
            });
        </script>
    @endpush
@endsection

