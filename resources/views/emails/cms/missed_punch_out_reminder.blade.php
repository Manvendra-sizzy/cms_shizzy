<!doctype html>
<html lang="en">
<body style="font-family:Arial,sans-serif;color:#111;line-height:1.5;">
    <p style="margin:0 0 10px 0;">Hi {{ $name }},</p>
    <p style="margin:0 0 10px 0;">
        You punched in on <strong>{{ $workDate->format('Y-m-d') }}</strong> but did not punch out.
    </p>
    <p style="margin:0 0 10px 0;">
        Please ensure your attendance is completed every working day. Repeated missed punch-outs may result in account lock as per HRMS policy.
    </p>
    <p style="margin:18px 0 0 0;">Regards,<br>Shizzy office</p>
</body>
</html>

