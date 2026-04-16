<!doctype html>
<html lang="en">
<body style="font-family: Poppins, Arial, sans-serif; color:#111827;">
<div style="max-width:640px;margin:0 auto;">
    <div style="margin-bottom:14px;">
        <strong style="color:#FF4D3D;">Shizzy</strong> CMS
    </div>

    <p style="margin:0 0 10px 0;">A document has been issued to your profile in the CMS. A PDF copy is attached to this email.</p>

    <div style="background:#F9FAFB;border:1px solid #E5E7EB;border-radius:12px;padding:14px;">
        <p style="margin:0 0 6px 0;"><strong>Employee:</strong> {{ $document->employeeProfile?->user?->name ?? '—' }}</p>
        <p style="margin:0 0 6px 0;"><strong>Document:</strong> {{ $type }}</p>
        <p style="margin:0 0 6px 0;"><strong>Issued At:</strong> {{ optional($document->issued_at)->format('Y-m-d') ?? '—' }}</p>
        @if(!empty($document->document_hash))
            <p style="margin:0;"><strong>Document Hash:</strong> {{ $document->document_hash }}</p>
        @endif
    </div>

    <p style="margin:14px 0 0 0; color:#6B7280;font-size:12px;">
        This email is an automated notification from the CMS.
    </p>
</div>
</body>
</html>

