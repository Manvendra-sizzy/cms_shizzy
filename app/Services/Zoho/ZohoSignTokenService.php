<?php

namespace App\Services\Zoho;

use Illuminate\Support\Facades\Http;
use RuntimeException;

/**
 * OAuth access token for Zoho Sign API (separate OAuth app from Books / other Zoho APIs).
 * Prefer ZOHO_SIGN_* credentials; falls back to main ZOHO_* when Sign-specific values are unset.
 */
class ZohoSignTokenService
{
    public function getAccessToken(): string
    {
        $accountsUrl = (string) (config('services.zoho_sign.accounts_url') ?: config('services.zoho.accounts_url'));
        $clientId = (string) (config('services.zoho_sign.client_id') ?: config('services.zoho.client_id'));
        $clientSecret = (string) (config('services.zoho_sign.client_secret') ?: config('services.zoho.client_secret'));
        $refreshToken = (string) (config('services.zoho_sign.refresh_token') ?: config('services.zoho.refresh_token'));

        $missing = [];

        if ($accountsUrl === '') {
            $missing[] = 'ZOHO_ACCOUNTS_BASE_URL or ZOHO_ACCOUNTS_URL';
        }
        if ($clientId === '') {
            $missing[] = 'ZOHO_SIGN_CLIENT_ID (or ZOHO_CLIENT_ID)';
        }
        if ($clientSecret === '') {
            $missing[] = 'ZOHO_SIGN_CLIENT_SECRET (or ZOHO_CLIENT_SECRET)';
        }
        if ($refreshToken === '') {
            $missing[] = 'ZOHO_SIGN_REFRESH_TOKEN (or ZOHO_REFRESH_TOKEN)';
        }

        if ($missing !== []) {
            throw new RuntimeException('Zoho Sign OAuth credentials are missing: ' . implode(', ', $missing));
        }

        $tokenUrl = (string) (config('services.zoho_sign.oauth_token_url') ?: '');
        if ($tokenUrl === '') {
            $tokenUrl = rtrim($accountsUrl, '/') . '/oauth/v2/token';
        }

        $response = Http::asForm()
            ->timeout(20)
            ->post($tokenUrl, [
                'grant_type' => 'refresh_token',
                'client_id' => $clientId,
                'client_secret' => $clientSecret,
                'refresh_token' => $refreshToken,
            ]);

        if (! $response->successful()) {
            throw new RuntimeException('Failed to fetch Zoho Sign access token: ' . $response->body());
        }

        $accessToken = (string) $response->json('access_token', '');

        if ($accessToken === '') {
            throw new RuntimeException('Zoho Sign token response did not contain access_token.');
        }

        return $accessToken;
    }
}
