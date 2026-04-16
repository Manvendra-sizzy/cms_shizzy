<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Onboarding submitted — Shizzy</title>
    <link href="https://fonts.googleapis.com/css2?family=Lexend:wght@600;700&family=Poppins:wght@400;500&display=swap" rel="stylesheet">
    <style>
        body { margin: 0; min-height: 100vh; font-family: 'Poppins', sans-serif; background: #f4f7fb; color: #0f172a; display: flex; align-items: center; justify-content: center; padding: 24px; }
        .box { max-width: 480px; background: #fff; border: 1px solid #e2e8f0; border-radius: 16px; padding: 32px; text-align: center; box-shadow: 0 4px 24px rgba(15,23,42,.06); }
        img { height: 44px; margin-bottom: 20px; }
        h1 { font-family: 'Lexend', sans-serif; font-size: 1.35rem; margin: 0 0 12px; }
        p { margin: 0; color: #64748b; line-height: 1.6; font-size: 0.95rem; }
        .check { width: 56px; height: 56px; margin: 0 auto 20px; background: #ecfdf5; border-radius: 50%; display: flex; align-items: center; justify-content: center; color: #059669; font-size: 28px; }
    </style>
</head>
<body>
<div class="box">
    <img src="https://shizzy.in/images/shizzy-logo-red.png" alt="Shizzy">
    <div class="check" aria-hidden="true">✓</div>
    <h1>Thank you, {{ $onboarding->full_name }}</h1>
    <p>Your onboarding has been submitted successfully. After HR confirms your details, you will receive your employment agreement by email for electronic signature. We’ll reach out if anything else is needed.</p>
</div>
</body>
</html>
