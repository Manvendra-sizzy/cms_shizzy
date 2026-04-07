@extends('hrms.layout')

@section('title', 'Two-Factor Verification')

@section('content')
    <div class="card auth-card">
        <h1>Two-Factor Verification</h1>
        <p class="muted">Enter the 6-digit code from your authenticator app to complete sign-in.</p>

        @if(session('status'))
            <p class="muted" style="color:#146c43;">{{ session('status') }}</p>
        @endif

        <form method="post" action="{{ route('twofactor.challenge.verify') }}" class="form-wrap">
            @csrf
            <div class="field">
                <label for="code">Verification code</label>
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
                />
            </div>
            @error('code')
                <p class="muted" style="color:#b42318;">{{ $message }}</p>
            @enderror
            <div class="action-row">
                <button class="btn" type="submit">Verify</button>
                <a class="pill" href="{{ route('login') }}">Back to login</a>
            </div>
        </form>
    </div>
@endsection
