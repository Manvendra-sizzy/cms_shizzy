@extends('hrms.layout')

@section('content')
    <style>
        .rs-field-hidden { display: none !important; }
    </style>
    <div class="card form-card">
        <div class="row" style="justify-content:space-between;align-items:flex-start;">
            <h1>Edit revenue stream</h1>
            <a class="pill" href="{{ route('projects.finances.show', $project) }}">Back to finances</a>
        </div>

        <p class="muted" style="margin-top:6px;">
            Project: <strong>{{ $project->name }}</strong> ({{ $project->project_code }})
        </p>
        <p class="muted" style="margin-top:4px;">
            Status is managed from finances: use <strong>Change status</strong> to close a stream (date + remarks). Closed streams stay visible as <strong>Closed</strong>.
        </p>

        @if ($errors->any())
            <div class="card" style="margin-top:12px;padding:12px 14px;border-color:#fecaca;background:#fef2f2;color:#991b1b;">
                <strong>Please fix the following:</strong>
                <ul style="margin:8px 0 0;padding-left:1.25rem;">
                    @foreach ($errors->all() as $message)
                        <li>{{ $message }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        @php
            $t0 = old('type', $stream->type === 'annual' ? 'lifetime' : $stream->type);
        @endphp

        <form method="post" action="{{ route('projects.revenue.streams.update', [$project, $stream]) }}" class="form-wrap" style="margin-top:14px;" id="editRevenueStreamForm" novalidate>
            @csrf
            @method('PUT')

            <div class="form-grid cols-2">
                <div class="field">
                    <label>Stream name (optional)</label>
                    <input name="name" value="{{ old('name', $stream->name) }}" placeholder="Leave blank to use the type label only in lists">
                </div>
                <div class="field">
                    <label>Type</label>
                    <select name="type" id="rsTypeEdit" required>
                        @foreach(['retainer'=>'Retainer','usage'=>'Usage','reimbursement'=>'Reimbursement','fixed'=>'Fixed','installment'=>'Installment','lifetime'=>'Lifetime'] as $k=>$v)
                            <option value="{{ $k }}" @selected($t0 === $k)>{{ $v }}</option>
                        @endforeach
                    </select>
                </div>

                <div class="field rs-depends-edit rs-recurring-edit rs-installment-edit" id="rsCycleWrapEdit">
                    <label id="rsCycleLabelEdit">Billing cycle</label>
                    <select name="billing_cycle" id="rsBillingCycleEdit"></select>
                </div>

                <div class="field rs-depends-edit rs-recurring-edit rs-installment-edit rs-lifetime-opt-edit">
                    <label id="rsNextBillLabelEdit">Next billing date</label>
                    <input name="next_billing_date" id="rsNextBillingEdit" type="date" value="{{ old('next_billing_date', optional($stream->next_billing_date)->format('Y-m-d')) }}">
                </div>

                <div class="field rs-depends-edit rs-all-edit">
                    <label>Expected project value (₹)</label>
                    <input name="expected_total_value" id="rsExpectedTotalValueEdit" type="number" step="0.01" min="0" value="{{ old('expected_total_value', $stream->expected_total_value) }}" required>
                </div>

                <div class="field rs-depends-edit rs-all-edit">
                    <label>Start date</label>
                    <input name="start_date" type="date" value="{{ old('start_date', optional($stream->start_date)->format('Y-m-d')) }}" required>
                </div>

                <div class="field rs-depends-edit rs-fixed-edit">
                    <label>Delivery / end date</label>
                    <input name="end_date" id="rsEndDateEdit" type="date" value="{{ old('end_date', optional($stream->end_date)->format('Y-m-d')) }}">
                </div>

                <div class="field rs-depends-edit rs-installment-edit">
                    <label>Installment value (₹)</label>
                    <input name="installment_value" type="number" step="0.01" value="{{ old('installment_value', $t0 === 'installment' ? $stream->rate_per_unit : '') }}" id="rsInstallmentValueEdit">
                </div>

                <div class="field rs-depends-edit rs-installment-edit">
                    <label>Number of installments</label>
                    <input name="installment_count" type="number" step="1" value="{{ old('installment_count', $t0 === 'installment' && $stream->quantity !== null ? (int) $stream->quantity : '') }}" id="rsInstallmentCountEdit">
                </div>

                <div class="field" style="grid-column:1 / -1;">
                    <label>Notes</label>
                    <textarea name="notes" required>{{ old('notes', $stream->notes) }}</textarea>
                </div>
            </div>

            <button class="btn" type="submit">Save changes</button>
        </form>
    </div>

    @push('scripts')
        <script>
            document.addEventListener('DOMContentLoaded', function () {
                const typeSel = document.getElementById('rsTypeEdit');
                const cycleWrap = document.getElementById('rsCycleWrapEdit');
                const cycleSel = document.getElementById('rsBillingCycleEdit');
                const cycleLbl = document.getElementById('rsCycleLabelEdit');
                const nextWrap = document.getElementById('rsNextBillingEdit')?.closest('.field');
                const nextInp = document.getElementById('rsNextBillingEdit');
                const nextLbl = document.getElementById('rsNextBillLabelEdit');
                const endInp = document.getElementById('rsEndDateEdit');
                const endWrap = endInp?.closest('.field');
                const instVal = document.getElementById('rsInstallmentValueEdit');
                const instCnt = document.getElementById('rsInstallmentCountEdit');
                const instValWrap = instVal?.closest('.field');
                const instCntWrap = instCnt?.closest('.field');
                const expectedValInp = document.getElementById('rsExpectedTotalValueEdit');
                const initialCycle = @json(old('billing_cycle', $stream->billing_cycle));

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
                    let matched = false;
                    list.forEach(function (o) {
                        const opt = document.createElement('option');
                        opt.value = o.v;
                        opt.textContent = o.t;
                        if (selected && selected === o.v) {
                            opt.selected = true;
                            matched = true;
                        }
                        cycleSel.appendChild(opt);
                    });
                    if (!matched && list[0]) cycleSel.value = list[0].v;
                }

                function setHidden(el, on) {
                    if (!el) return;
                    el.classList.toggle('rs-field-hidden', on);
                }

                function syncEditStreamForm() {
                    if (!typeSel || !cycleSel) return;
                    const t = typeSel.value;
                    const rec = ['retainer', 'usage', 'reimbursement'].indexOf(t) !== -1;
                    const inst = t === 'installment';
                    const fix = t === 'fixed';
                    const life = t === 'lifetime';

                    setHidden(cycleWrap, !rec && !inst);
                    if (rec || inst) {
                        cycleLbl.textContent = inst ? 'Installment cycle' : 'Billing cycle';
                        const sel = cycleSel.value || initialCycle;
                        if (inst) {
                            const allowed = installmentCycles.map(function (x) { return x.v; });
                            fillCycles(installmentCycles, allowed.indexOf(sel) !== -1 ? sel : (allowed.indexOf(initialCycle) !== -1 ? initialCycle : null));
                        } else {
                            const allowed = recurringCycles.map(function (x) { return x.v; });
                            fillCycles(recurringCycles, allowed.indexOf(sel) !== -1 ? sel : (allowed.indexOf(initialCycle) !== -1 ? initialCycle : null));
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
                    typeSel.addEventListener('change', syncEditStreamForm);
                    syncEditStreamForm();
                }

            });
        </script>
    @endpush
@endsection
