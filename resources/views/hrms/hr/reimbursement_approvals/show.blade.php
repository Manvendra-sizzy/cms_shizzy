@extends('hrms.layout')

@section('content')
    <div class="card form-card">
        <div class="row" style="justify-content:space-between;align-items:flex-start;">
            <h1>Reimbursement #{{ $requestItem->id }}</h1>
            <a class="pill" href="{{ route('admin.hrms.reimbursement_approvals.index') }}">Back</a>
        </div>

        <div class="grid cols-3" style="margin-top:12px;">
            <div class="card">
                <h2>Employee</h2>
                <div><strong>{{ $requestItem->employeeProfile?->employee_id }}</strong> — {{ $requestItem->employeeProfile?->user?->name }}</div>
            </div>
            <div class="card">
                <h2>Status</h2>
                <div><strong>{{ ucwords(str_replace('_', ' ', $requestItem->status)) }}</strong></div>
                <div class="muted" style="margin-top:4px;">Decision: {{ optional($requestItem->decided_at)->format('Y-m-d H:i') ?? '—' }}</div>
            </div>
            <div class="card">
                <h2>Amount</h2>
                <div>Total: <strong>{{ number_format((float) $requestItem->amount, 2) }}</strong></div>
                <div>Paid: <strong>{{ number_format((float) ($requestItem->paid_amount ?? 0), 2) }}</strong></div>
                <div>Remaining: <strong>{{ number_format(max(0, (float) $requestItem->amount - (float) ($requestItem->paid_amount ?? 0)), 2) }}</strong></div>
            </div>
        </div>

        <div class="card" style="margin-top:12px;">
            <h2>Request details</h2>
            <table>
                <tbody>
                <tr><td>Title</td><td><strong>{{ $requestItem->title }}</strong></td></tr>
                <tr><td>Category</td><td>{{ $requestItem->category ?: '—' }}</td></tr>
                <tr><td>Expense date</td><td>{{ optional($requestItem->expense_date)->format('Y-m-d') }}</td></tr>
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
                </tbody>
            </table>
        </div>

        @if($requestItem->status === 'pending')
            <div class="row" style="gap:10px;margin-top:12px;">
                <form method="post" action="{{ route('admin.hrms.reimbursement_approvals.approve', $requestItem) }}">
                    @csrf
                    <button class="btn" type="submit">Approve</button>
                </form>
                <form method="post" action="{{ route('admin.hrms.reimbursement_approvals.reject', $requestItem) }}" class="row" style="gap:8px;">
                    @csrf
                    <input type="text" name="admin_note" maxlength="2000" placeholder="Reason if rejecting">
                    <button class="btn danger" type="submit">Reject</button>
                </form>
            </div>
        @elseif(in_array($requestItem->status, ['approved', 'partially_paid'], true))
            <div class="card" style="margin-top:12px;">
                <h2>Record payment</h2>
                <form method="post" action="{{ route('admin.hrms.reimbursement_approvals.pay', $requestItem) }}" class="row" style="gap:10px;align-items:flex-end;">
                    @csrf
                    <div class="field" style="margin:0;">
                        <label>Payment type</label>
                        <select name="payment_mode" required>
                            <option value="full">Full</option>
                            <option value="partial">Partial</option>
                        </select>
                    </div>
                    <div class="field" style="margin:0;">
                        <label>Partial amount (only if partial)</label>
                        <input type="number" name="pay_amount" min="0.01" step="0.01">
                    </div>
                    <div class="field" style="margin:0;">
                        <label>Payment note (optional)</label>
                        <input type="text" name="admin_note" maxlength="2000">
                    </div>
                    <button class="btn" type="submit">Record payment</button>
                </form>
            </div>
        @endif
    </div>
@endsection

