<!doctype html>
<html lang="en">
<body style="font-family: Poppins, Arial, sans-serif; color:#111827;">
<div style="max-width:640px;margin:0 auto;">
    <p>Hello {{ $onboarding->full_name }},</p>
    <p>Your onboarding submission at Shizzy could not be approved at this time.</p>
    <p style="margin:16px 0;padding:12px 14px;background:#F9FAFB;border-radius:8px;border:1px solid #E5E7EB;">
        <strong style="display:block;margin-bottom:6px;color:#374151;">Reason</strong>
        {{ $reason }}
    </p>
    <p style="margin:0;font-size:13px;color:#6B7280;">If you have questions, please contact HR.</p>
</div>
</body>
</html>
