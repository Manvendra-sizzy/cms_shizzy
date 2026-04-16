<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Sign Employment Contract</title>
</head>
<body style="margin:0;padding:0;background:#f8fafc;font-family:Arial,sans-serif;color:#0f172a;">
<table role="presentation" width="100%" cellspacing="0" cellpadding="0" style="background:#f8fafc;padding:24px 10px;">
    <tr>
        <td align="center">
            <table role="presentation" width="620" cellspacing="0" cellpadding="0" style="max-width:620px;width:100%;background:#ffffff;border:1px solid #e2e8f0;border-radius:12px;overflow:hidden;">
                <tr>
                    <td style="padding:20px 24px;border-bottom:1px solid #eef2f7;">
                        <img src="https://shizzy.in/images/shizzy-logo-red.png" alt="Shizzy" style="height:38px;display:block;">
                    </td>
                </tr>
                <tr>
                    <td style="padding:24px;">
                        <h2 style="margin:0 0 12px;font-size:20px;line-height:1.3;">Sign your employment contract</h2>
                        <p style="margin:0 0 10px;color:#475569;">Dear {{ $onboarding->full_name }},</p>
                        <p style="margin:0 0 14px;color:#475569;line-height:1.6;">
                            Your onboarding is approved. Please review and sign your employment agreement using the secure link below.
                            You must complete all required steps: consent, drawn signature, and selfie verification.
                        </p>
                        <p style="margin:0 0 18px;color:#334155;line-height:1.6;">
                            This link is one-time and expires automatically for security.
                        </p>
                        <p style="margin:0 0 22px;">
                            <a href="{{ $contractUrl }}" style="display:inline-block;padding:12px 18px;background:#ff4d3d;color:#ffffff;text-decoration:none;border-radius:8px;font-weight:600;">Open contract and sign</a>
                        </p>
                        <p style="margin:0;color:#64748b;font-size:13px;line-height:1.6;">
                            If you cannot access the link, contact HR at office@shizzy.in.
                        </p>
                    </td>
                </tr>
            </table>
        </td>
    </tr>
</table>
</body>
</html>
