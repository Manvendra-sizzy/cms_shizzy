@extends('hrms.layout')

@section('title', 'Set up Two-Factor Authentication')

@section('content')
    <div class="card form-card" style="max-width:760px;">
        <h1>Set up Two-Factor Authentication</h1>
        <p class="muted">Scan the QR in Google Authenticator (or any TOTP app), then enter the 6-digit code to continue login.</p>

        <div class="grid cols-3" style="grid-template-columns:220px 1fr;align-items:start;">
            <div>
                <img src="{{ $qrUrl }}" alt="2FA QR code" style="width:220px;height:220px;border:1px solid #dbe5f2;border-radius:12px;background:#fff;">
            </div>
            <div>
                <p class="muted" style="margin-bottom:10px;"><strong>App Name:</strong> Shizzy CMS</p>
                <p class="muted" style="margin-bottom:10px;"><strong>Manual key:</strong> <code>{{ $manualSecret }}</code></p>
                <form method="post" action="{{ route('twofactor.setup.complete') }}" class="form-wrap">
                    @csrf
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
                            autofocus
                        >
                    </div>
                    @error('code')
                        <p class="muted" style="color:#b42318;">{{ $message }}</p>
                    @enderror
                    <button type="submit" class="btn">Verify and Continue</button>
                </form>
                <p class="muted" style="margin-top:10px;">
                    <a href="{{ route('login') }}">Back to login</a>
                </p>
            </div>
        </div>
    </div>
@endsection

