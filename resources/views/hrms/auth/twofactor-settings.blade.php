@extends('hrms.layout')

@section('title', '2FA Security')

@section('content')
    <div class="card form-card" style="max-width:760px;">
        <h1>Authenticator 2FA</h1>
        <p class="muted">Use Google Authenticator, Microsoft Authenticator, or any TOTP app.</p>

        @if($userHasTotp)
            <div class="flash">2FA is currently enabled. To change your device, scan the new QR and verify below.</div>
        @endif

        <div class="grid cols-3" style="grid-template-columns:220px 1fr;align-items:start;margin-top:12px;">
            <div>
                <img src="{{ $qrUrl }}" alt="2FA QR code" style="width:220px;height:220px;border:1px solid #dbe5f2;border-radius:12px;background:#fff;">
            </div>
            <div>
                <p class="muted" style="margin-bottom:10px;"><strong>App Name:</strong> Shizzy CMS</p>
                <p><strong>Step 1:</strong> Scan this QR code in your authenticator app.</p>
                <p><strong>Step 2:</strong> Enter the 6-digit code to save or update your 2FA device.</p>
                <p class="muted" style="margin-bottom:10px;"><strong>Manual key:</strong> <code>{{ $manualSecret }}</code></p>
                <form method="post" action="{{ route('security.twofactor.enable') }}" class="form-wrap">
                    @csrf
                    @if($userHasTotp)
                        <div class="field">
                            <label for="password">Current password (required to change device)</label>
                            <input id="password" name="password" type="password" required autocomplete="current-password">
                        </div>
                    @endif
                    <div class="field">
                        <label for="code">Authenticator code</label>
                        <input
                            id="code"
                            name="code"
                            type="text"
                            inputmode="numeric"
                            maxlength="6"
                            pattern="[0-9]{6}"
                            value="{{ old('code') }}"
                            required
                        >
                    </div>
                    @error('code')
                        <p class="muted" style="color:#b42318;">{{ $message }}</p>
                    @enderror
                    @error('password')
                        <p class="muted" style="color:#b42318;">{{ $message }}</p>
                    @enderror
                    <button type="submit" class="btn">{{ $userHasTotp ? 'Update 2FA device' : 'Enable 2FA' }}</button>
                </form>

                @if($userHasTotp)
                    <form method="post" action="{{ route('security.twofactor.disable') }}" class="form-wrap" style="margin-top:14px;">
                        @csrf
                        <div class="field">
                            <label for="disable_password">Confirm password to disable</label>
                            <input id="disable_password" name="password" type="password" required autocomplete="current-password">
                        </div>
                        <button type="submit" class="btn danger">Disable 2FA</button>
                    </form>
                @endif
            </div>
        </div>
    </div>
@endsection

