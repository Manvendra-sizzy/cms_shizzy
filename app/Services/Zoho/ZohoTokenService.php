<?php

namespace App\Services\Zoho;

use RuntimeException;
use Illuminate\Support\Facades\Http;

class ZohoTokenService
{
    public function getAccessToken(): string
    {
        $accountsUrl = (string) config('services.zoho.accounts_url');
        $clientId = (string) config('services.zoho.client_id');
        $clientSecret = (string) config('services.zoho.client_secret');
        $refreshToken = (string) config('services.zoho.refresh_token');

        $missing = [];

        if ($accountsUrl === '') {
            $missing[] = 'ZOHO_ACCOUNTS_URL (or ZOHO_ACCOUNTS_SERVER)';
        }
        if ($clientId === '') {
            $missing[] = 'ZOHO_CLIENT_ID';
        }
        if ($clientSecret === '') {
            $missing[] = 'ZOHO_CLIENT_SECRET';
        }
        if ($refreshToken === '') {
            $missing[] = 'ZOHO_REFRESH_TOKEN';
        }

        if ($missing !== []) {
            throw new RuntimeException('Zoho credentials are missing: ' . implode(', ', $missing));
        }

        $response = Http::asForm()
            ->timeout(20)
            ->post(rtrim($accountsUrl, '/') . '/oauth/v2/token', [
                'grant_type' => 'refresh_token',
                'client_id' => $clientId,
                'client_secret' => $clientSecret,
                'refresh_token' => $refreshToken,
            ]);

        if (! $response->successful()) {
            throw new RuntimeException('Failed to fetch Zoho access token: ' . $response->body());
        }

        $accessToken = (string) $response->json('access_token', '');

        if ($accessToken === '') {
            throw new RuntimeException('Zoho token response did not contain access_token.');
        }

        return $accessToken;
    }
}
