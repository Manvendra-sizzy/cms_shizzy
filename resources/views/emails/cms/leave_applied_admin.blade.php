<!doctype html>
<html lang="en">
<body style="font-family: Poppins, Arial, sans-serif; color:#111827;">
<div style="max-width:640px;margin:0 auto;">
    <div style="margin-bottom:14px;">
        <strong style="color:#FF4D3D;">Shizzy</strong> CMS
    </div>

    <p style="margin:0 0 10px 0;">
        A leave request has been applied.
    </p>

    <div style="background:#F9FAFB;border:1px solid #E5E7EB;border-radius:12px;padding:14px;">
        <p style="margin:0 0 6px 0;"><strong>Employee:</strong> {{ $leaveRequest->employeeProfile?->user?->name ?? '—' }}</p>
        <p style="margin:0 0 6px 0;"><strong>Policy:</strong> {{ $leaveRequest->policy?->name ?? '—' }}</p>
        <p style="margin:0 0 6px 0;"><strong>Dates:</strong> {{ $leaveRequest->start_date?->format('Y-m-d') }} → {{ $leaveRequest->end_date?->format('Y-m-d') }}</p>
        <p style="margin:0 0 6px 0;"><strong>Days:</strong> {{ $leaveRequest->days ?? '—' }}</p>
        @if(!empty($leaveRequest->reason))
            <p style="margin:0;"><strong>Reason:</strong> {{ $leaveRequest->reason }}</p>
        @endif
    </div>

    <p style="margin:14px 0 0 0; color:#6B7280;font-size:12px;">
        This email is an automated notification from the CMS.
    </p>
</div>
</body>
</html>

