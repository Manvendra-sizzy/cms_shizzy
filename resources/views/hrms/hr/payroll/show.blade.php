@extends('hrms.layout')

@section('content')
    <div class="card">
        <h1>Payroll run #{{ $run->id }}</h1>
        <p class="muted">
            Period: <strong>{{ $run->period_start->format('Y-m-d') }}</strong> → <strong>{{ $run->period_end->format('Y-m-d') }}</strong>
            · Status: <strong>{{ $run->status }}</strong>
        </p>

        <h2>Existing salary slips</h2>
        @if($run->salarySlips->isEmpty())
            <p class="muted">No slips yet.</p>
        @else
            <table>
                <thead>
                <tr>
                    <th>Employee</th>
                    <th>Slip #</th>
                    <th>LOP</th>
                    <th>Gross</th>
                    <th>Deductions</th>
                    <th>Net</th>
                    <th></th>
                </tr>
                </thead>
                <tbody>
                @foreach($run->salarySlips as $slip)
                    <tr>
                        <td><strong>{{ $slip->employeeProfile->employee_id }}</strong> — {{ $slip->employeeProfile->user->name }}</td>
                        <td class="muted">{{ $slip->slip_number }}</td>
                        <td class="muted">{{ $slip->lop_days ?? '—' }} d</td>
                        <td>{{ $slip->currency }} {{ $slip->gross }}</td>
                        <td>{{ $slip->currency }} {{ $slip->deductions }}</td>
                        <td><strong>{{ $slip->currency }} {{ $slip->net }}</strong></td>
                        <td><a class="pill" href="{{ route('admin.hrms.payroll.slips.download', $slip) }}">Download</a></td>
                    </tr>
                @endforeach
                </tbody>
            </table>
        @endif
    </div>

    <div class="card" style="margin-top:14px;">
        <h1>Generate slips (attendance + leave + LOP)</h1>
        <p class="muted">
            Working days exclude Sundays, 2nd &amp; 4th Saturdays, and holidays from the <a href="{{ route('admin.hrms.calendar.index') }}">Calendar</a>.
            LOP = no punch-in on a working day without paid leave, plus unpaid leave days. Deduction: (monthly salary ÷ 30) × LOP days.
            Salary structure applies to gross after LOP deduction. Optional extra earnings are added on top.
            <strong>Reimbursements</strong> (third row) is prefilled with the <strong>total of all approved</strong> reimbursement claims that are not yet on a salary slip (multiple claims per employee are summed). Submitted claims still awaiting HR approval do not appear until approved. If you keep that amount equal to the pending total, those claims are linked to the slip when you generate it.
        </p>

        @error('payroll')
            <p style="color:#c00;">{{ $message }}</p>
        @enderror

        <form method="post" action="{{ route('admin.hrms.payroll.slips.generate', $run) }}">
            @csrf
            <div class="field" style="max-width: 180px;">
                <label>Currency</label>
                <input name="currency" value="{{ old('currency', 'INR') }}">
            </div>

            <table>
                <thead>
                <tr>
                    <th>Include</th>
                    <th>Employee</th>
                    <th>Working</th>
                    <th>Present</th>
                    <th>Paid leave</th>
                    <th>LOP</th>
                    <th>LOP ₹</th>
                    <th>Basis (after LOP)</th>
                    <th>Reimb. pending</th>
                    <th>Extra earnings</th>
                    <th>Extra ded. ₹</th>
                </tr>
                </thead>
                <tbody>
                @foreach($employees as $i => $emp)
                    @php
                        $p = $previews[$emp->id] ?? [];
                        $reimbursementMap = $reimbursementPending ?? [];
                        $rp = $reimbursementMap[$emp->id] ?? 0;
                        $extraLabels = ['Overtime', 'Bonus', 'Reimbursements', 'Extra'];
                    @endphp
                    <tr>
                        <td>
                            <input type="hidden" name="slips[{{ $i }}][employee_profile_id]" value="{{ $emp->id }}">
                            <input type="checkbox" name="slips[{{ $i }}][include]" value="1" checked style="width:auto;">
                        </td>
                        <td><strong>{{ $emp->employee_id }}</strong> — {{ $emp->user->name }}</td>
                        <td class="muted">{{ $p['working_days'] ?? '—' }}</td>
                        <td class="muted">{{ $p['present_days'] ?? '—' }}</td>
                        <td class="muted">{{ $p['paid_leave_days'] ?? '—' }}</td>
                        <td class="muted">{{ $p['lop_days'] ?? '—' }}</td>
                        <td class="muted">{{ number_format($p['lop_deduction'] ?? 0, 2) }}</td>
                        <td><strong>{{ number_format($p['effective_gross_basis'] ?? 0, 2) }}</strong></td>
                        <td class="muted" title="Sum of approved reimbursement claims not yet paid on a salary slip">
                            @if($rp > 0)
                                <strong>{{ number_format($rp, 2) }}</strong>
                            @else
                                —
                            @endif
                        </td>
                        <td>
                            <div style="display:grid; grid-template-columns: 1fr 110px; gap:8px; align-items:center;">
                                @for($j = 0; $j < 4; $j++)
                                    <div class="field" style="margin:0;">
                                        <input
                                            name="slips[{{ $i }}][extra_earnings][{{ $j }}][label]"
                                            type="text"
                                            value="{{ old('slips.'.$i.'.extra_earnings.'.$j.'.label', $extraLabels[$j] ?? '') }}"
                                            placeholder="{{ $extraLabels[$j] ?? 'Extra' }}"
                                            style="width:100%;"
                                        >
                                    </div>
                                    <div class="field" style="margin:0;">
                                        <input
                                            name="slips[{{ $i }}][extra_earnings][{{ $j }}][amount]"
                                            type="number"
                                            step="0.01"
                                            min="0"
                                            value="{{ old('slips.'.$i.'.extra_earnings.'.$j.'.amount', $j === 2 ? $rp : 0) }}"
                                            style="width:100%;"
                                        >
                                    </div>
                                @endfor
                            </div>
                        </td>
                        <td>
                            <input name="slips[{{ $i }}][extra_deductions]" type="number" step="0.01" min="0" value="{{ old("slips.$i.extra_deductions", 0) }}" style="width:100px;">
                        </td>
                    </tr>
                @endforeach
                </tbody>
            </table>

            <div style="margin-top:12px;">
                <button class="btn" type="submit">Generate selected slips</button>
            </div>
        </form>
    </div>
@endsection
