<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Employee Onboarding — Shizzy</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Lexend:wght@500;600;700&family=Poppins:wght@400;500;600&display=swap" rel="stylesheet">
    <style>
        :root {
            --bg: #f4f7fb;
            --surface: #ffffff;
            --text: #0f172a;
            --muted: #64748b;
            --border: #e2e8f0;
            --accent: #ff4d3d;
            --accent-hover: #e63e30;
            --ring: rgba(255, 77, 61, 0.15);
            --hr-bg: linear-gradient(135deg, #fff8f7 0%, #f8fafc 50%, #f0f7ff 100%);
        }
        *, *::before, *::after { box-sizing: border-box; }
        html { scroll-behavior: smooth; }
        body {
            margin: 0;
            min-height: 100vh;
            font-family: 'Poppins', system-ui, sans-serif;
            font-size: 15px;
            line-height: 1.5;
            color: var(--text);
            background:
                radial-gradient(ellipse 80% 50% at 50% -20%, rgba(255, 77, 61, 0.08), transparent),
                var(--bg);
        }
        .page { max-width: 880px; margin: 0 auto; padding: 28px 18px 48px; }
        .header {
            background: var(--surface);
            border: 1px solid var(--border);
            border-radius: 16px;
            padding: 24px 28px;
            margin-bottom: 20px;
            box-shadow: 0 4px 24px rgba(15, 23, 42, 0.06);
        }
        .header-inner { display: flex; flex-wrap: wrap; align-items: center; gap: 20px 28px; }
        .brand { display: flex; align-items: center; gap: 16px; }
        .brand img { height: 48px; width: auto; object-fit: contain; }
        .brand-text h1 {
            margin: 0;
            font-family: 'Lexend', sans-serif;
            font-weight: 700;
            font-size: 1.35rem;
            letter-spacing: -0.02em;
            color: var(--text);
        }
        .brand-text p { margin: 4px 0 0; font-size: 0.875rem; color: var(--muted); }
        .badge {
            display: inline-block;
            margin-top: 10px;
            padding: 4px 10px;
            border-radius: 999px;
            font-size: 0.75rem;
            font-weight: 600;
            letter-spacing: 0.02em;
            color: var(--accent);
            background: rgba(255, 77, 61, 0.08);
            border: 1px solid rgba(255, 77, 61, 0.2);
        }
        .hr-summary {
            background: var(--hr-bg);
            border: 1px solid #e8eef5;
            border-radius: 14px;
            padding: 20px 22px 22px;
            margin-bottom: 20px;
            box-shadow: 0 2px 14px rgba(15, 23, 42, 0.05);
        }
        .hr-summary h2 {
            margin: 0 0 4px;
            font-family: 'Lexend', sans-serif;
            font-size: 0.9375rem;
            font-weight: 700;
            color: #1e293b;
        }
        .hr-summary .sub {
            margin: 0 0 16px;
            font-size: 0.8125rem;
            color: var(--muted);
        }
        .hr-grid {
            display: grid;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: 12px 24px;
        }
        @media (max-width: 640px) { .hr-grid { grid-template-columns: 1fr; } }
        .hr-item dt {
            font-size: 0.6875rem;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.06em;
            color: #94a3b8;
            margin: 0 0 4px;
        }
        .hr-item dd { margin: 0; font-size: 0.9375rem; font-weight: 500; color: #0f172a; word-break: break-word; }
        .card {
            background: var(--surface);
            border: 1px solid var(--border);
            border-radius: 14px;
            padding: 22px 24px;
            margin-bottom: 18px;
            box-shadow: 0 2px 12px rgba(15, 23, 42, 0.04);
        }
        .card h2 {
            margin: 0 0 16px;
            font-family: 'Lexend', sans-serif;
            font-size: 1rem;
            font-weight: 600;
            color: #334155;
            padding-bottom: 10px;
            border-bottom: 1px solid #f1f5f9;
        }
        .grid {
            display: grid;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: 16px 20px;
        }
        @media (max-width: 640px) { .grid { grid-template-columns: 1fr; } }
        .field.full { grid-column: 1 / -1; }
        .field label {
            display: block;
            font-size: 0.8125rem;
            font-weight: 500;
            color: #475569;
            margin-bottom: 6px;
        }
        .field input[type="text"],
        .field input:not([type]),
        .field input[type="email"],
        .field input[type="date"],
        .field textarea {
            width: 100%;
            padding: 10px 12px;
            border: 1px solid var(--border);
            border-radius: 10px;
            font: inherit;
            color: var(--text);
            background: #fafbfc;
            transition: border-color 0.15s, box-shadow 0.15s;
        }
        .field input:focus,
        .field textarea:focus {
            outline: none;
            border-color: var(--accent);
            box-shadow: 0 0 0 3px var(--ring);
            background: #fff;
        }
        .field input[type="file"] {
            width: 100%;
            padding: 10px;
            border: 1px dashed #cbd5e1;
            border-radius: 10px;
            font-size: 0.8125rem;
            background: #f8fafc;
        }
        .field textarea { min-height: 96px; resize: vertical; }
        .err { color: #b91c1c; font-size: 0.75rem; margin-top: 4px; display: block; }
        .alert { padding: 12px 16px; border-radius: 10px; margin-bottom: 18px; font-size: 0.9375rem; }
        .alert.ok { background: #ecfdf5; border: 1px solid #a7f3d0; color: #065f46; }
        .alert.err { background: #fef2f2; border: 1px solid #fecaca; color: #991b1b; }
        .alert.err ul { margin: 8px 0 0 18px; padding: 0; }
        .actions { display: flex; flex-wrap: wrap; gap: 12px; margin-top: 8px; padding-top: 8px; }
        .btn {
            font-family: inherit;
            font-weight: 600;
            font-size: 0.9375rem;
            padding: 12px 22px;
            border-radius: 10px;
            border: none;
            cursor: pointer;
            transition: background 0.15s, transform 0.1s;
        }
        .btn-primary { background: var(--accent); color: #fff; }
        .btn-primary:hover { background: var(--accent-hover); }
        .btn-secondary {
            background: #fff;
            color: #334155;
            border: 1px solid var(--border);
            box-shadow: 0 1px 2px rgba(15, 23, 42, 0.05);
        }
        .btn-secondary:hover { background: #f8fafc; border-color: #cbd5e1; }
        .btn:active { transform: translateY(1px); }
        .footer { margin-top: 28px; text-align: center; font-size: 0.75rem; color: var(--muted); }
        .footer strong { color: #94a3b8; font-weight: 600; }
        .hint { margin: -8px 0 16px; font-size: 0.8125rem; color: var(--muted); line-height: 1.45; }
        .contract-scroll {
            border: 1px solid var(--border);
            border-radius: 12px;
            padding: 14px;
            background: #f8fafc;
            max-height: 360px;
            overflow: auto;
            line-height: 1.6;
            font-size: 0.875rem;
        }
        .contract-scroll p { margin: 0 0 0.75em; }
        canvas.sig-canvas {
            width: 100%;
            max-width: 620px;
            height: 220px;
            border: 2px dashed #cbd5e1;
            border-radius: 12px;
            background: #fff;
            touch-action: none;
        }
        @media (max-width: 640px) { canvas.sig-canvas { height: 180px; } }
        .contract-field label.row { display: flex; align-items: flex-start; gap: 10px; font-weight: 500; }
        .contract-field input[type="checkbox"] { margin-top: 4px; }
    </style>
</head>
<body>
@php
    $payload = $onboarding->employee_payload ?? [];
    $typeLabels = \App\Services\HRMS\EmployeeLifecycleService::employeeTypeLabels();
@endphp
<div class="page">
    <header class="header">
        <div class="header-inner">
            <div class="brand">
                <img src="https://shizzy.in/images/shizzy-logo-red.png" alt="Shizzy">
                <div class="brand-text">
                    <h1>Employee onboarding</h1>
                    <p>Exin Internet Services Private Limited · Human Resources</p>
                </div>
            </div>
        </div>
        <span class="badge">Secure link · {{ $onboarding->full_name }}</span>
    </header>

    <section class="hr-summary" aria-labelledby="hr-summary-title">
        <h2 id="hr-summary-title">Details from HR</h2>
        <p class="sub">These were set by HR and cannot be changed here. If anything is wrong, contact HR before submitting.</p>
        <dl class="hr-grid">
            <div class="hr-item"><dt>Full name</dt><dd>{{ $onboarding->full_name }}</dd></div>
            <div class="hr-item"><dt>Personal email</dt><dd>{{ $onboarding->email }}</dd></div>
            <div class="hr-item"><dt>Phone</dt><dd>{{ $onboarding->phone ?: '—' }}</dd></div>
            <div class="hr-item"><dt>Employee type</dt><dd>{{ $typeLabels[$onboarding->employee_type] ?? $onboarding->employee_type }}</dd></div>
            <div class="hr-item"><dt>Department</dt><dd>{{ $onboarding->department?->name ?? '—' }}</dd></div>
            <div class="hr-item"><dt>Designation</dt><dd>{{ $onboarding->designation?->name ?? '—' }}</dd></div>
            <div class="hr-item"><dt>Team</dt><dd>{{ $onboarding->team?->name ?? '—' }}</dd></div>
            <div class="hr-item"><dt>Joining date</dt><dd>{{ optional($onboarding->joining_date)->format('d M Y') ?? '—' }}</dd></div>
        </dl>
    </section>

    @if(session('status'))
        <div class="alert ok">{{ session('status') }}</div>
    @endif
    @if($errors->any())
        <div class="alert err">
            <strong>Please fix the following:</strong>
            <ul>
                @foreach($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <form method="post" action="{{ route('onboarding.submit', ['token' => $token]) }}" enctype="multipart/form-data">
        @csrf

        <section class="card">
            <h2>Your details</h2>
            <div class="grid">
                <div class="field">
                    <label for="date_of_birth">Date of birth *</label>
                    <input id="date_of_birth" type="date" name="date_of_birth" value="{{ old('date_of_birth', $payload['date_of_birth'] ?? '') }}" required>
                    @error('date_of_birth')<span class="err">{{ $message }}</span>@enderror
                </div>
            </div>
        </section>

        <section class="card">
            <h2>Address</h2>
            <div class="grid">
                <div class="field full">
                    <label for="address">Address *</label>
                    <textarea id="address" name="address" rows="3" required>{{ old('address', $payload['address'] ?? '') }}</textarea>
                    @error('address')<span class="err">{{ $message }}</span>@enderror
                </div>
            </div>
        </section>

        <section class="card">
            <h2>Emergency contacts</h2>
            <p class="hint">Provide two contacts we can reach if we cannot reach you.</p>
            <div class="grid">
                <div class="field">
                    <label for="emergency_contact_1_name">Contact 1 — name *</label>
                    <input id="emergency_contact_1_name" type="text" name="emergency_contact_1_name" value="{{ old('emergency_contact_1_name', $payload['emergency_contact_1_name'] ?? $payload['emergency_contact_name'] ?? '') }}" required autocomplete="name">
                    @error('emergency_contact_1_name')<span class="err">{{ $message }}</span>@enderror
                </div>
                <div class="field">
                    <label for="emergency_contact_1_phone">Contact 1 — phone *</label>
                    <input id="emergency_contact_1_phone" type="text" name="emergency_contact_1_phone" value="{{ old('emergency_contact_1_phone', $payload['emergency_contact_1_phone'] ?? $payload['emergency_contact_phone'] ?? '') }}" required autocomplete="tel">
                    @error('emergency_contact_1_phone')<span class="err">{{ $message }}</span>@enderror
                </div>
                <div class="field">
                    <label for="emergency_contact_2_name">Contact 2 — name *</label>
                    <input id="emergency_contact_2_name" type="text" name="emergency_contact_2_name" value="{{ old('emergency_contact_2_name', $payload['emergency_contact_2_name'] ?? '') }}" required autocomplete="name">
                    @error('emergency_contact_2_name')<span class="err">{{ $message }}</span>@enderror
                </div>
                <div class="field">
                    <label for="emergency_contact_2_phone">Contact 2 — phone *</label>
                    <input id="emergency_contact_2_phone" type="text" name="emergency_contact_2_phone" value="{{ old('emergency_contact_2_phone', $payload['emergency_contact_2_phone'] ?? '') }}" required autocomplete="tel">
                    @error('emergency_contact_2_phone')<span class="err">{{ $message }}</span>@enderror
                </div>
            </div>
        </section>

        <section class="card">
            <h2>Bank details</h2>
            <div class="grid">
                <div class="field">
                    <label for="bank_name">Bank name *</label>
                    <input id="bank_name" type="text" name="bank_name" value="{{ old('bank_name', $payload['bank_name'] ?? '') }}" required autocomplete="organization">
                    @error('bank_name')<span class="err">{{ $message }}</span>@enderror
                </div>
                <div class="field">
                    <label for="bank_account_number">Account number *</label>
                    <input id="bank_account_number" type="text" name="bank_account_number" value="{{ old('bank_account_number', $payload['bank_account_number'] ?? '') }}" required inputmode="numeric" autocomplete="off">
                    @error('bank_account_number')<span class="err">{{ $message }}</span>@enderror
                </div>
                <div class="field">
                    <label for="bank_ifsc_code">IFSC code *</label>
                    <input id="bank_ifsc_code" type="text" name="bank_ifsc_code" value="{{ old('bank_ifsc_code', $payload['bank_ifsc_code'] ?? '') }}" required autocomplete="off">
                    @error('bank_ifsc_code')<span class="err">{{ $message }}</span>@enderror
                </div>
            </div>
        </section>

        <section class="card">
            <h2>Identity & KYC</h2>
            <p class="hint">PDF or image, max 5 MB per file. Profile photo is required before you submit. You can save progress and upload files in stages.</p>
            <div class="grid">
                <div class="field">
                    <label for="profile_photo">Profile photo *</label>
                    <input type="file" name="profile_photo" id="profile_photo" accept=".jpg,.jpeg,.png,.webp">
                    @error('profile_photo')<span class="err">{{ $message }}</span>@enderror
                </div>
                <div class="field">
                    <label for="pan_card">PAN card *</label>
                    <input type="file" name="pan_card" id="pan_card" accept=".pdf,.jpg,.jpeg,.png">
                    @error('pan_card')<span class="err">{{ $message }}</span>@enderror
                </div>
                <div class="field">
                    <label for="id_card">ID proof *</label>
                    <input type="file" name="id_card" id="id_card" accept=".pdf,.jpg,.jpeg,.png">
                    @error('id_card')<span class="err">{{ $message }}</span>@enderror
                </div>
            </div>
        </section>

        @if($onboarding->employee_type !== \App\Services\HRMS\EmployeeLifecycleService::TYPE_INTERN)
        <section class="card">
            <h2>Previous employment <span style="font-weight:400;color:var(--muted);font-size:0.875rem;">(optional)</span></h2>
            <p class="hint">If applicable, upload your experience letter, relieving letter, and up to three recent salary slips.</p>
            <div class="grid">
                <div class="field">
                    <label for="experience_letter">Experience letter</label>
                    <input type="file" name="experience_letter" id="experience_letter" accept=".pdf,.jpg,.jpeg,.png">
                    @error('experience_letter')<span class="err">{{ $message }}</span>@enderror
                </div>
                <div class="field">
                    <label for="relieving_letter">Relieving letter</label>
                    <input type="file" name="relieving_letter" id="relieving_letter" accept=".pdf,.jpg,.jpeg,.png">
                    @error('relieving_letter')<span class="err">{{ $message }}</span>@enderror
                </div>
                <div class="field">
                    <label for="salary_slip_1">Salary slip — month 1</label>
                    <input type="file" name="salary_slip_1" id="salary_slip_1" accept=".pdf,.jpg,.jpeg,.png">
                    @error('salary_slip_1')<span class="err">{{ $message }}</span>@enderror
                </div>
                <div class="field">
                    <label for="salary_slip_2">Salary slip — month 2</label>
                    <input type="file" name="salary_slip_2" id="salary_slip_2" accept=".pdf,.jpg,.jpeg,.png">
                    @error('salary_slip_2')<span class="err">{{ $message }}</span>@enderror
                </div>
                <div class="field">
                    <label for="salary_slip_3">Salary slip — month 3</label>
                    <input type="file" name="salary_slip_3" id="salary_slip_3" accept=".pdf,.jpg,.jpeg,.png">
                    @error('salary_slip_3')<span class="err">{{ $message }}</span>@enderror
                </div>
            </div>
        </section>
        @endif

        <div class="card" style="margin-bottom: 0;">
            <div class="actions">
                <button type="submit" class="btn btn-secondary" name="action" value="save" id="btn-save" formnovalidate>Save progress</button>
                <button type="submit" class="btn btn-primary" name="action" value="submit" id="btn-submit">Submit onboarding</button>
            </div>
        </div>
    </form>

    @if($onboarding->contract_signed_at)
        <section class="card" aria-labelledby="contract-signed-title">
            <h2 id="contract-signed-title">Employment agreement</h2>
            <p class="hint" style="margin:0;">Signed on {{ optional($onboarding->contract_signed_at)->format('d M Y, H:i') }} (Asia/Kolkata). Your signed PDF is stored securely for HR and compliance.</p>
        </section>
    @elseif(!empty($canSignInbuiltContract ?? false) && !empty($agreementHtml))
        <section class="card" id="employment-agreement" aria-labelledby="contract-title">
            <h2 id="contract-title">Employment agreement</h2>
            <p class="hint">This is the same document configured under HRMS → Employment agreement. Fill your address above before signing if the contract includes it. Signing is recorded with your IP, device fingerprint, signature, and selfie.</p>
            @error('contract')
                <div class="alert err" style="margin-bottom:12px;">{{ $message }}</div>
            @enderror
        </section>

        <form method="post" action="{{ route('onboarding.sign-contract', ['token' => $token]) }}" enctype="multipart/form-data" id="onboarding-contract-form">
            @csrf
            <section class="card">
                <h2>Contract preview</h2>
                <div class="contract-scroll">
                    {!! $agreementHtml !!}
                </div>
            </section>

            <section class="card">
                <h2>Acceptance</h2>
                <p class="hint" style="margin-top:0;">By signing, you confirm you have read and understood the employment contract.</p>
                <div class="field contract-field">
                    <label class="row">
                        <input type="checkbox" name="agree_contract" value="1" required @checked(old('agree_contract'))>
                        <span>I agree to the employment contract terms and electronically sign this document.</span>
                    </label>
                </div>
                <div class="field">
                    <label for="signer_name_contract">Your full name (as signer)</label>
                    <input id="signer_name_contract" type="text" name="signer_name" required value="{{ old('signer_name', $onboarding->full_name) }}">
                </div>
            </section>

            <section class="card">
                <h2>Signature</h2>
                <p class="hint" style="margin-top:0;">Draw your signature in the box.</p>
                <canvas id="sig-pad-onboarding" class="sig-canvas" width="620" height="220"></canvas>
                <input type="hidden" id="signature_data_onboarding" name="signature_data" value="">
                <div class="actions" style="margin-top:12px;">
                    <button type="button" class="btn btn-secondary" id="clear-sign-onboarding">Clear signature</button>
                </div>
            </section>

            <section class="card">
                <h2>Selfie verification</h2>
                <p class="hint" style="margin-top:0;">Upload a clear selfie at signing time.</p>
                <div class="field">
                    <label for="selfie_onboarding">Selfie image</label>
                    <input id="selfie_onboarding" type="file" name="selfie" accept=".jpg,.jpeg,.png,.webp" required>
                </div>
                <input type="hidden" name="device_fingerprint" id="device_fingerprint_onboarding" value="">
            </section>

            <section class="card" style="margin-bottom: 0;">
                <button type="submit" class="btn btn-primary" id="btn-sign-contract">Sign employment agreement</button>
            </section>
        </form>
    @endif

    <footer class="footer">
        <strong>EXIN INTERNET SERVICES PRIVATE LIMITED</strong><br>
        Need help? Contact HR at <a href="mailto:office@shizzy.in" style="color: var(--accent);">office@shizzy.in</a>
    </footer>
</div>
<script>
    (function () {
        const saveBtn = document.getElementById('btn-save');
        const submitBtn = document.getElementById('btn-submit');
        const requiredFileIds = ['profile_photo', 'pan_card', 'id_card'];
        if (!saveBtn || !submitBtn) return;

        saveBtn.addEventListener('click', function () {
            requiredFileIds.forEach(function (id) {
                const el = document.getElementById(id);
                if (el) el.required = false;
            });
        });
        submitBtn.addEventListener('click', function () {
            requiredFileIds.forEach(function (id) {
                const el = document.getElementById(id);
                if (el) el.required = true;
            });
        });
    })();

    @if(!empty($canSignInbuiltContract ?? false) && !empty($agreementHtml))
    (function () {
        const canvas = document.getElementById('sig-pad-onboarding');
        const hidden = document.getElementById('signature_data_onboarding');
        const clearBtn = document.getElementById('clear-sign-onboarding');
        const form = document.getElementById('onboarding-contract-form');
        if (!canvas || !hidden || !form) return;
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
            const scaleX = canvas.width / rect.width;
            const scaleY = canvas.height / rect.height;
            return { x: (x - rect.left) * scaleX, y: (y - rect.top) * scaleY };
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

        if (clearBtn) {
            clearBtn.addEventListener('click', function () {
                ctx.clearRect(0, 0, canvas.width, canvas.height);
                hasInk = false;
                hidden.value = '';
            });
        }

        const fpEl = document.getElementById('device_fingerprint_onboarding');
        if (fpEl) {
            fpEl.value = [
                navigator.userAgent || '',
                navigator.language || '',
                String(screen.width || ''),
                String(screen.height || ''),
                String((new Date()).getTimezoneOffset()),
                navigator.platform || ''
            ].join('|');
        }

        form.addEventListener('submit', function (evt) {
            if (!hasInk) {
                evt.preventDefault();
                alert('Please draw your signature before signing the agreement.');
                return;
            }
            hidden.value = canvas.toDataURL('image/png');
        });
    })();
    @endif
</script>
</body>
</html>
