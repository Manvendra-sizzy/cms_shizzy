@extends('hrms.layout')

@section('content')
    <div class="card">
        <div class="row" style="justify-content:space-between;align-items:flex-start;">
            <h1>Reimbursements</h1>
            <div class="row" style="gap:8px;">
                <a class="pill" href="{{ route('employee.reimbursements.create') }}">Apply</a>
                <a class="pill" href="{{ route('employee.dashboard') }}">Back</a>
            </div>
        </div>

        <p class="muted" style="margin-top:8px;">Track your reimbursement requests and their approval status.</p>

        @if($requests->isEmpty())
            <p class="muted" style="margin-top:12px;">No requests yet. Use <strong>Apply</strong> to submit one.</p>
        @else
            <div class="table-wrap" style="margin-top:12px;">
                <table>
                    <thead>
                    <tr>
                        <th>Submitted</th>
                        <th>Title</th>
                        <th>Expense date</th>
                        <th>Amount</th>
                        <th>Paid</th>
                        <th>Remaining</th>
                        <th>Status</th>
                        <th></th>
                        <th>Salary slip</th>
                        <th>Receipt</th>
                    </tr>
                    </thead>
                    <tbody>
                    @foreach($requests as $r)
                        <tr>
                            <td class="muted">{{ $r->created_at?->format('Y-m-d H:i') }}</td>
                            <td><strong>{{ $r->title }}</strong>@if($r->category)<div class="muted" style="font-size:12px;">{{ $r->category }}</div>@endif</td>
                            <td class="muted">{{ $r->expense_date?->format('Y-m-d') }}</td>
                            <td>{{ number_format((float) $r->amount, 2) }}</td>
                            <td class="muted">{{ number_format((float) ($r->paid_amount ?? 0), 2) }}</td>
                            <td class="muted">{{ number_format(max(0, (float) $r->amount - (float) ($r->paid_amount ?? 0)), 2) }}</td>
                            <td>
                                @if($r->status === 'pending')
                                    <span style="color:#b45309;font-weight:600;">Pending</span>
                                @elseif($r->status === 'approved')
                                    <span style="color:#15803d;font-weight:600;">Approved</span>
                                @elseif($r->status === 'partially_paid')
                                    <span style="color:#2563eb;font-weight:600;">Partially paid</span>
                                @elseif($r->status === 'paid')
                                    <span style="color:#065f46;font-weight:600;">Paid</span>
                                @else
                                    <span style="color:#b91c1c;font-weight:600;">Rejected</span>
                                @endif
                                @if($r->decided_at)
                                    <div class="muted" style="font-size:12px;margin-top:4px;">{{ $r->decided_at->format('Y-m-d H:i') }}</div>
                                @endif
                                @if($r->status === 'rejected' && !empty($r->admin_note))
                                    <div class="muted" style="font-size:12px;margin-top:4px;">{{ $r->admin_note }}</div>
                                @endif
                            </td>
                            <td>
                                <a class="pill" href="{{ route('employee.reimbursements.show', $r) }}">View</a>
                            </td>
                            <td class="muted">
                                @if($r->salary_slip_id && $r->salarySlip)
                                    <span title="Included in this slip’s earnings">Included</span>
                                    <div style="margin-top:4px;">
                                        <a class="pill" href="{{ route('employee.salary_slips.download', $r->salarySlip) }}">{{ $r->salarySlip->slip_number }}</a>
                                    </div>
                                @else
                                    —
                                @endif
                            </td>
                            <td>
                                @if(!empty($r->receipt_path))
                                    <a class="pill" href="{{ route('files.public', ['path' => ltrim($r->receipt_path, '/')]) }}" target="_blank" rel="noopener">View</a>
                                @else
                                    <span class="muted">—</span>
                                @endif
                            </td>
                        </tr>
                    @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </div>
@endsection
