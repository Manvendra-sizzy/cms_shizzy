<?php

namespace App\Http\Controllers\CMS;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;

class ZohoIntegrationController extends Controller
{
    public function index(Request $request)
    {
        return view('cms.admin.tools.zoho.index', [
            'state' => [
                'client_id' => (string) $request->old('client_id', '1000.E91E9XTLWAR97KXQ5E8WRFWN6QLO1C'),
                'client_secret' => (string) $request->old('client_secret', ''),
                'redirect_uri' => (string) $request->old('redirect_uri', 'https://cms.shizzy.in/zoho/callback'),
                'accounts_server' => (string) $request->old('accounts_server', 'https://accounts.zoho.in'),
                'scope' => (string) $request->old('scope', 'ZohoBooks.fullaccess.all'),
                'auth_code' => (string) $request->old('auth_code', (string) $request->query('code', '')),
                'access_token' => (string) $request->old('access_token', ''),
                'refresh_token' => (string) $request->old('refresh_token', ''),
                'response' => $request->session()->get('zoho_oauth_response', []),
            ],
        ]);
    }

    public function generateRefreshToken(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'client_id' => ['required', 'string'],
            'client_secret' => ['required', 'string'],
            'redirect_uri' => ['required', 'url'],
            'accounts_server' => ['required', 'url'],
            'scope' => ['nullable', 'string'],
            'auth_code' => ['required', 'string'],
        ]);

        $response = Http::asForm()->post(rtrim($data['accounts_server'], '/') . '/oauth/v2/token', [
            'grant_type' => 'authorization_code',
            'client_id' => $data['client_id'],
            'client_secret' => $data['client_secret'],
            'redirect_uri' => $data['redirect_uri'],
            'code' => $data['auth_code'],
        ]);

        $json = $response->json();
        $payload = is_array($json) ? $json : ['raw' => $response->body()];

        if (! $response->successful()) {
            return back()
                ->withInput()
                ->withErrors([
                    'zoho' => 'Token request failed. Check credentials/code and try again.',
                ])
                ->with('zoho_oauth_response', $payload);
        }

        return back()->withInput([
            'client_id' => $data['client_id'],
            'client_secret' => $data['client_secret'],
            'redirect_uri' => $data['redirect_uri'],
            'accounts_server' => $data['accounts_server'],
            'scope' => (string) ($data['scope'] ?? ''),
            'auth_code' => $data['auth_code'],
            'access_token' => (string) ($payload['access_token'] ?? ''),
            'refresh_token' => (string) ($payload['refresh_token'] ?? ''),
        ])->with('zoho_oauth_response', $payload);
    }
}
