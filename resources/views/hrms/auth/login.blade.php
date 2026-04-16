@extends('hrms.layout')

@section('content')
    <div style="display:flex;align-items:center;justify-content:center;min-height:calc(100vh - 160px);">
        <div class="card form-card" style="max-width:520px;width:100%;padding:22px;">
            <div style="margin-bottom:14px;text-align:center;">
                <img
                    src="https://shizzy.in/images/shizzy-logo-red.png"
                    alt="Shizzy logo"
                    style="height:50px;width:auto;margin-bottom:8px;"
                />
                <div style="font-weight:900;letter-spacing:.6px;font-size:22px;line-height:1.1;color:var(--text);font-family:var(--font-accent);">
                    CMS
                </div>
                <div class="muted" style="margin-top:6px;">Sign in using your company credentials.</div>
            </div>

            <form method="post" action="{{ route('login.submit') }}" class="form-wrap">
                @csrf
                <div class="field">
                    <label>Email</label>
                    <input name="email" type="email" value="{{ old('email') }}" required autocomplete="username">
                </div>
                <div class="field">
                    <label>Password</label>
                    <input name="password" type="password" required autocomplete="current-password">
                </div>
                <div class="field" style="margin-top:-4px;">
                    <label style="display:flex;align-items:center;gap:8px;cursor:pointer;">
                        <input type="checkbox" name="remember_device" value="1" {{ old('remember_device') ? 'checked' : '' }} style="width:auto;">
                        Keep me logged in on this device for 14 days
                    </label>
                </div>
                <button class="btn" type="submit" style="width:100%;">Sign in</button>
            </form>
        </div>
    </div>
@endsection

