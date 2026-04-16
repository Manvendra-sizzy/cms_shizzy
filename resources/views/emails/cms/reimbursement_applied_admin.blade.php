<!doctype html>
<html lang="en">
<body style="font-family: Poppins, Arial, sans-serif; color:#111827;">
<div style="max-width:640px;margin:0 auto;">
    <div style="margin-bottom:14px;">
        <strong style="color:#FF4D3D;">Shizzy</strong> CMS
    </div>

    <p style="margin:0 0 10px 0;">
        An employee has submitted a reimbursement request.
    </p>

    <div style="background:#F9FAFB;border:1px solid #E5E7EB;border-radius:12px;padding:14px;">
        <p style="margin:0 0 6px 0;"><strong>Employee:</strong> {{ $reimbursementRequest->employeeProfile?->user?->name ?? '—' }}</p>
        <p style="margin:0 0 6px 0;"><strong>Employee ID:</strong> {{ $reimbursementRequest->employeeProfile?->employee_id ?? '—' }}</p>
        <p style="margin:0 0 6px 0;"><strong>Title:</strong> {{ $reimbursementRequest->title }}</p>
        @if(!empty($reimbursementRequest->category))
            <p style="margin:0 0 6px 0;"><strong>Category:</strong> {{ $reimbursementRequest->category }}</p>
        @endif
        <p style="margin:0 0 6px 0;"><strong>Expense date:</strong> {{ $reimbursementRequest->expense_date?->format('Y-m-d') }}</p>
        <p style="margin:0 0 6px 0;"><strong>Amount:</strong> {{ number_format((float) $reimbursementRequest->amount, 2) }}</p>
        @if(!empty($reimbursementRequest->description))
            <p style="margin:0;"><strong>Description:</strong> {{ $reimbursementRequest->description }}</p>
        @endif
    </div>

    <p style="margin:14px 0 0 0; color:#6B7280;font-size:12px;">
        This email is an automated notification from the CMS.
    </p>
</div>
</body>
</html>
