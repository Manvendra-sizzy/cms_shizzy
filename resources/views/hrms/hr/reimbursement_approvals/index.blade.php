@extends('hrms.layout')

@section('content')
    <div class="card">
        <h1>Reimbursement approvals & payments</h1>
        <p class="muted">Review reimbursement claims, open full details, and record partial/full payments.</p>

        @if($requests->isEmpty())
            <p class="muted" style="margin-top:12px;">No reimbursement items pending approval or payment.</p>
        @else
            <div class="table-wrap" style="margin-top:12px;">
                <table>
                    <thead>
                    <tr>
                        <th>Employee</th>
                        <th>Title</th>
                        <th>Expense date</th>
                        <th>Amount</th>
                        <th>Paid</th>
                        <th>Remaining</th>
                        <th>Status</th>
                        <th>Details</th>
                        <th>Receipt</th>
                        <th></th>
                    </tr>
                    </thead>
                    <tbody>
                    @foreach($requests as $r)
                        <tr>
                            <td><strong>{{ $r->employeeProfile->employee_id }}</strong> — {{ $r->employeeProfile->user->name }}</td>
                            <td>{{ $r->title }}@if($r->category)<div class="muted" style="font-size:12px;">{{ $r->category }}</div>@endif</td>
                            <td class="muted">{{ $r->expense_date->format('Y-m-d') }}</td>
                            <td><strong>{{ number_format((float) $r->amount, 2) }}</strong></td>
                            <td class="muted">{{ number_format((float) ($r->paid_amount ?? 0), 2) }}</td>
                            <td class="muted">{{ number_format(max(0, (float) $r->amount - (float) ($r->paid_amount ?? 0)), 2) }}</td>
                            <td class="muted">{{ ucwords(str_replace('_', ' ', $r->status)) }}</td>
                            <td class="muted">{{ \Illuminate\Support\Str::limit($r->description ?? '—', 80) }}</td>
                            <td>
                                @if(!empty($r->receipt_path))
                                    <a class="pill" href="{{ route('files.public', ['path' => ltrim($r->receipt_path, '/')]) }}" target="_blank" rel="noopener">Open</a>
                                @else
                                    <span class="muted">—</span>
                                @endif
                            </td>
                            <td>
                                <a class="pill" href="{{ route('admin.hrms.reimbursement_approvals.show', $r) }}" style="width:100%;text-align:center;">View</a>
                            </td>
                        </tr>
                    @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </div>
@endsection
