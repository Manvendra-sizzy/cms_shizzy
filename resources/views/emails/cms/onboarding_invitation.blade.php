<!doctype html>
<html lang="en">
<body style="font-family: Poppins, Arial, sans-serif; color:#111827;">
<div style="max-width:640px;margin:0 auto;">
    <p>Hello {{ $onboarding->full_name }},</p>
    <p>Your onboarding has been initiated at Shizzy. Please complete your details using the secure link below. On the same page you can review and electronically sign your employment agreement (the document configured in HRMS).</p>
    <p style="margin:18px 0;">
        <a href="{{ $onboardingUrl }}" style="background:#FF4D3D;color:#fff;padding:10px 16px;border-radius:8px;text-decoration:none;">Complete Onboarding</a>
    </p>
    <p style="margin:0 0 6px 0;font-size:13px;color:#6B7280;">This link is time-limited and single-purpose.</p>
    <p style="margin:0;font-size:13px;color:#6B7280;">If the button does not work, open this URL: {{ $onboardingUrl }}</p>
</div>
</body>
</html>

