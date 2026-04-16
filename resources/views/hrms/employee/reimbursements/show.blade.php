@extends('hrms.layout')

@section('content')
    <div class="card form-card">
        <div class="row" style="justify-content:space-between;align-items:flex-start;">
            <h1>Reimbursement #{{ $requestItem->id }}</h1>
            <a class="pill" href="{{ route('employee.reimbursements.index') }}">Back</a>
        </div>

        <div class="card" style="margin-top:12px;">
            <h2>Summary</h2>
            <table>
                <tbody>
                <tr><td>Title</td><td><strong>{{ $requestItem->title }}</strong></td></tr>
                <tr><td>Category</td><td>{{ $requestItem->category ?: '—' }}</td></tr>
                <tr><td>Expense date</td><td>{{ optional($requestItem->expense_date)->format('Y-m-d') }}</td></tr>
                <tr><td>Total amount</td><td><strong>{{ number_format((float) $requestItem->amount, 2) }}</strong></td></tr>
                <tr><td>Paid amount</td><td><strong>{{ number_format((float) ($requestItem->paid_amount ?? 0), 2) }}</strong></td></tr>
                <tr><td>Remaining amount</td><td><strong>{{ number_format(max(0, (float) $requestItem->amount - (float) ($requestItem->paid_amount ?? 0)), 2) }}</strong></td></tr>
                <tr><td>Status</td><td><strong>{{ ucwords(str_replace('_', ' ', $requestItem->status)) }}</strong></td></tr>
                <tr><td>Description</td><td>{{ $requestItem->description ?: '—' }}</td></tr>
                <tr><td>Admin note</td><td>{{ $requestItem->admin_note ?: '—' }}</td></tr>
                <tr>
                    <td>Receipt</td>
                    <td>
                        @if($requestItem->receipt_path)
                            <a class="pill" href="{{ route('files.public', ['path' => ltrim($requestItem->receipt_path, '/')]) }}" target="_blank" rel="noopener">Open receipt</a>
                        @else
                            —
                        @endif
                    </td>
                </tr>
                @if($requestItem->salary_slip_id && $requestItem->salarySlip)
                    <tr>
                        <td>Salary slip</td>
                        <td><a class="pill" href="{{ route('employee.salary_slips.download', $requestItem->salarySlip) }}">{{ $requestItem->salarySlip->slip_number }}</a></td>
                    </tr>
                @endif
                </tbody>
            </table>
        </div>
    </div>
@endsection

