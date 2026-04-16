<!doctype html>
<html lang="en">
<body style="font-family: Poppins, Arial, sans-serif; color:#111827;">
<div style="max-width:640px;margin:0 auto;">
    <div style="margin-bottom:14px;">
        <strong style="color:#FF4D3D;">Shizzy</strong> CMS
    </div>

    <p style="margin:0 0 10px 0;">Your salary has been credited for the selected payroll period.</p>

    <div style="background:#F9FAFB;border:1px solid #E5E7EB;border-radius:12px;padding:14px;">
        <p style="margin:0 0 6px 0;"><strong>Employee:</strong> {{ $name }}</p>
        <p style="margin:0 0 6px 0;"><strong>Slip:</strong> {{ $slip->slip_number }}</p>
        <p style="margin:0 0 6px 0;">
            <strong>Period:</strong>
            {{ optional($run->period_start)->format('Y-m-d') }} → {{ optional($run->period_end)->format('Y-m-d') }}
        </p>
        <p style="margin:0 0 6px 0;"><strong>Net Pay:</strong> {{ $slip->net }}</p>
        <p style="margin:0;"><strong>Currency:</strong> {{ $slip->currency ?? 'INR' }}</p>
    </div>

    <p style="margin:14px 0 0 0; color:#6B7280;font-size:12px;">
        This email is an automated notification from the CMS.
    </p>
</div>
</body>
</html>

