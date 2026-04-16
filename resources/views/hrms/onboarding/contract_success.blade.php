<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Contract signed successfully</title>
    <style>
        body { margin: 0; min-height: 100vh; background: #f4f7fb; display: grid; place-items: center; font-family: Arial, sans-serif; color: #0f172a; padding: 16px; }
        .box { max-width: 620px; background: #fff; border: 1px solid #e2e8f0; border-radius: 14px; padding: 26px; }
        h1 { margin: 0 0 10px; font-size: 1.3rem; }
        .muted { color: #64748b; line-height: 1.6; }
        .hash { margin-top: 10px; background: #f8fafc; border: 1px solid #cbd5e1; border-radius: 8px; padding: 8px 10px; font-family: monospace; font-size: 12px; word-break: break-all; }
        a.btn { display: inline-block; margin-top: 14px; padding: 10px 14px; border-radius: 8px; text-decoration: none; background: #ff4d3d; color: #fff; font-weight: 600; }
    </style>
</head>
<body>
<div class="box">
    <h1>Contract signed successfully</h1>
    <p class="muted">Thank you, {{ $onboarding->full_name }}. Your contract has been signed and recorded with legal evidence metadata.</p>
    <p class="muted">Document hash (SHA-256):</p>
    <div class="hash">{{ $documentHash }}</div>
    <p class="muted">Evidence chain hash:</p>
    <div class="hash">{{ $evidenceHash }}</div>
    <a class="btn" href="{{ $downloadUrl }}" target="_blank" rel="noopener">Download signed contract</a>
</div>
</body>
</html>
