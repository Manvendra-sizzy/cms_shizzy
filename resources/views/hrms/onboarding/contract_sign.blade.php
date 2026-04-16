<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Sign Employment Contract - Shizzy</title>
    <link href="https://fonts.googleapis.com/css2?family=Lexend:wght@600;700&family=Poppins:wght@400;500;600&display=swap" rel="stylesheet">
    <style>
        body { margin: 0; background: #f4f7fb; color: #0f172a; font-family: 'Poppins', sans-serif; }
        .page { max-width: 980px; margin: 0 auto; padding: 24px 16px 40px; }
        .card { background: #fff; border: 1px solid #e2e8f0; border-radius: 14px; padding: 20px; margin-bottom: 14px; }
        h1, h2 { font-family: 'Lexend', sans-serif; margin: 0 0 10px; }
        h1 { font-size: 1.3rem; }
        h2 { font-size: 1rem; color: #334155; }
        .muted { color: #64748b; font-size: .92rem; }
        .box { border: 1px solid #cbd5e1; border-radius: 12px; padding: 14px; background: #f8fafc; max-height: 280px; overflow: auto; line-height: 1.6; }
        .field { margin-top: 12px; }
        label { display: block; font-weight: 500; margin-bottom: 6px; }
        input[type=text], input[type=file] { width: 100%; border: 1px solid #cbd5e1; border-radius: 10px; padding: 10px 12px; font: inherit; background: #fff; }
        .row { display: flex; gap: 12px; align-items: center; flex-wrap: wrap; }
        .btn { border: 0; border-radius: 10px; background: #ff4d3d; color: #fff; padding: 11px 18px; font-weight: 600; cursor: pointer; }
        .err { color: #b91c1c; font-size: .85rem; margin-top: 6px; }
        canvas { width: 100%; max-width: 620px; height: 220px; border: 2px dashed #cbd5e1; border-radius: 12px; background: #fff; touch-action: none; }
        @media (max-width: 640px) { canvas { height: 180px; } }
    </style>
</head>
<body>
<div class="page">
    <div class="card">
        <h1>Employment contract signing</h1>
        <p class="muted">Candidate: <strong>{{ $onboarding->full_name }}</strong> · Email: {{ $onboarding->email }}</p>
        <p class="muted">This workflow records legal evidence including IP address, browser/device signature, timestamps, and cryptographic hashes.</p>
    </div>

    @if($errors->any())
        <div class="card" style="border-color:#fecaca;background:#fff5f5;">
            @foreach($errors->all() as $error)
                <div class="err">{{ $error }}</div>
            @endforeach
        </div>
    @endif

    <form method="post" action="{{ route('onboarding.contract.submit', ['token' => $token]) }}" enctype="multipart/form-data" id="contract-form">
        @csrf
        <div class="card">
            <h2>Contract preview</h2>
            <div class="box" style="max-height:360px;">
                {!! $agreementHtml !!}
            </div>
        </div>

        <div class="card">
            <h2>Contract acceptance</h2>
            <div class="box">
                By proceeding, you acknowledge that you have read and understood your employment contract and are signing it electronically with full intent and consent.
                Your signature evidence, selfie, and signing metadata will be securely stored for legal and compliance purposes.
            </div>
            <div class="field">
                <label class="row">
                    <input type="checkbox" name="agree_contract" value="1" required @checked(old('agree_contract'))>
                    <span>I agree to the employment contract terms and electronically sign this document.</span>
                </label>
            </div>
            <div class="field">
                <label for="signer_name">Your full name (as signer)</label>
                <input id="signer_name" type="text" name="signer_name" required value="{{ old('signer_name', $onboarding->full_name) }}">
            </div>
        </div>

        <div class="card">
            <h2>Draw your signature</h2>
            <p class="muted">Sign clearly inside the box below.</p>
            <canvas id="sig-pad" width="620" height="220"></canvas>
            <input type="hidden" id="signature_data" name="signature_data" required>
            <div class="row" style="margin-top:10px;">
                <button type="button" id="clear-sign" class="btn" style="background:#475569;">Clear signature</button>
            </div>
        </div>

        <div class="card">
            <h2>Selfie verification</h2>
            <p class="muted">Upload a clear selfie at signing time for signer identity evidence.</p>
            <div class="field">
                <label for="selfie">Selfie image</label>
                <input id="selfie" type="file" name="selfie" accept=".jpg,.jpeg,.png,.webp" required>
            </div>
            <input type="hidden" name="device_fingerprint" id="device_fingerprint">
        </div>

        <div class="card">
            <button type="submit" class="btn">Sign Contract and Submit</button>
        </div>
    </form>
</div>
<script>
    (function () {
        const canvas = document.getElementById('sig-pad');
        const hidden = document.getElementById('signature_data');
        const clearBtn = document.getElementById('clear-sign');
        const form = document.getElementById('contract-form');
        const ctx = canvas.getContext('2d');

        let drawing = false;
        let hasInk = false;
        ctx.lineWidth = 2;
        ctx.lineCap = 'round';
        ctx.strokeStyle = '#0f172a';

        function pos(evt) {
            const rect = canvas.getBoundingClientRect();
            const touch = evt.touches && evt.touches[0] ? evt.touches[0] : null;
            const x = touch ? touch.clientX : evt.clientX;
            const y = touch ? touch.clientY : evt.clientY;
            return { x: x - rect.left, y: y - rect.top };
        }

        function start(evt) {
            drawing = true;
            const p = pos(evt);
            ctx.beginPath();
            ctx.moveTo(p.x, p.y);
            evt.preventDefault();
        }
        function move(evt) {
            if (!drawing) return;
            const p = pos(evt);
            ctx.lineTo(p.x, p.y);
            ctx.stroke();
            hasInk = true;
            evt.preventDefault();
        }
        function end() { drawing = false; }

        canvas.addEventListener('mousedown', start);
        canvas.addEventListener('mousemove', move);
        canvas.addEventListener('mouseup', end);
        canvas.addEventListener('mouseleave', end);
        canvas.addEventListener('touchstart', start, { passive: false });
        canvas.addEventListener('touchmove', move, { passive: false });
        canvas.addEventListener('touchend', end);

        clearBtn.addEventListener('click', function () {
            ctx.clearRect(0, 0, canvas.width, canvas.height);
            hasInk = false;
            hidden.value = '';
        });

        const fp = [
            navigator.userAgent || '',
            navigator.language || '',
            String(screen.width || ''),
            String(screen.height || ''),
            String((new Date()).getTimezoneOffset()),
            navigator.platform || ''
        ].join('|');
        document.getElementById('device_fingerprint').value = fp;

        form.addEventListener('submit', function (evt) {
            if (!hasInk) {
                evt.preventDefault();
                alert('Please draw your signature before submitting.');
                return;
            }
            hidden.value = canvas.toDataURL('image/png');
        });
    })();
</script>
</body>
</html>
