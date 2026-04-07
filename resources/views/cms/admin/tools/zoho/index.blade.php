@extends('hrms.layout')

@section('content')
    @php
        $authUrl = rtrim($state['accounts_server'] ?? 'https://accounts.zoho.in', '/') . '/oauth/v2/auth?' . http_build_query([
            'scope' => $state['scope'] ?? 'ZohoBooks.fullaccess.all',
            'client_id' => $state['client_id'] ?? '',
            'response_type' => 'code',
            'access_type' => 'offline',
            'redirect_uri' => $state['redirect_uri'] ?? '',
        ]);
    @endphp

    <div class="card form-card">
        <h1>Zoho Integration Setup</h1>
        <p class="muted" style="margin-top:6px;">Generate authorization code and refresh token for Zoho Books integration.</p>

        <form method="post" action="{{ route('admin.tools.zoho.token') }}" class="form-wrap" style="margin-top:14px;">
            @csrf
            <div class="form-grid cols-2">
                <div class="field">
                    <label>Client ID</label>
                    <input name="client_id" type="text" value="{{ $state['client_id'] ?? '' }}" required />
                </div>

                <div class="field">
                    <label>Client Secret</label>
                    <input name="client_secret" type="password" value="{{ $state['client_secret'] ?? '' }}" required />
                </div>

                <div class="field">
                    <label>Redirect URI</label>
                    <input name="redirect_uri" type="text" value="{{ $state['redirect_uri'] ?? '' }}" required />
                </div>

                <div class="field">
                    <label>Accounts Server</label>
                    <input name="accounts_server" type="text" value="{{ $state['accounts_server'] ?? '' }}" required />
                </div>

                <div class="field">
                    <label>Scope</label>
                    <input name="scope" type="text" value="{{ $state['scope'] ?? '' }}" />
                </div>

                <div class="field" style="display:flex;align-items:flex-end;">
                    <a class="btn" href="{{ $authUrl }}" style="text-decoration:none;">1. Get Authorization Code</a>
                </div>
            </div>

            <div class="field">
                <label>Authorization Code</label>
                <input
                    name="auth_code"
                    type="text"
                    value="{{ $state['auth_code'] ?? '' }}"
                    placeholder="Auto-filled after redirect or paste manually"
                    required
                />
            </div>

            <button class="btn" type="submit" style="background:var(--ok);margin-top:4px;">2. Generate Refresh Token</button>
        </form>
    </div>

    <div class="card" style="margin-top:14px;">
        <h2>Access Token</h2>
        <div class="field">
            <textarea readonly>{{ $state['access_token'] ?? '' }}</textarea>
        </div>

        <h2 style="margin-top:10px;">Refresh Token</h2>
        <div class="field">
            <textarea readonly>{{ $state['refresh_token'] ?? '' }}</textarea>
        </div>

        <h2 style="margin-top:10px;">Full Response</h2>
        <div class="field">
            <textarea readonly>{{ json_encode($state['response'] ?? [], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) }}</textarea>
        </div>
    </div>
@endsection
