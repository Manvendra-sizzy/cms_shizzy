<?php

namespace App\Services\Zoho;

use RuntimeException;
use Illuminate\Support\Facades\Http;

class ZohoBooksService
{
    public function __construct(private readonly ZohoTokenService $tokenService)
    {
    }

    /**
     * @return array<string, mixed>
     */
    public function fetchContactsPage(int $page = 1, int $perPage = 200): array
    {
        $accessToken = $this->tokenService->getAccessToken();
        $apiDomain = (string) config('services.zoho.api_domain');
        $organizationId = (string) config('services.zoho.organization_id');

        if ($apiDomain === '' || $organizationId === '') {
            throw new RuntimeException('Zoho API domain or organization id is missing.');
        }

        $response = Http::withToken($accessToken)
            ->timeout(30)
            ->acceptJson()
            ->get(rtrim($apiDomain, '/') . '/books/v3/contacts', [
                'organization_id' => $organizationId,
                'page' => $page,
                'per_page' => $perPage,
            ]);

        if (! $response->successful()) {
            throw new RuntimeException('Failed to fetch Zoho contacts: ' . $response->body());
        }

        $json = $response->json();

        if (! is_array($json)) {
            throw new RuntimeException('Invalid Zoho contacts response format.');
        }

        return $json;
    }

    /**
     * @return array<string, mixed>
     */
    public function fetchInvoicesPage(int $page = 1, int $perPage = 200): array
    {
        $accessToken = $this->tokenService->getAccessToken();
        $apiDomain = (string) config('services.zoho.api_domain');
        $organizationId = (string) config('services.zoho.organization_id');

        if ($apiDomain === '' || $organizationId === '') {
            throw new RuntimeException('Zoho API domain or organization id is missing.');
        }

        $response = Http::withToken($accessToken)
            ->timeout(30)
            ->acceptJson()
            ->get(rtrim($apiDomain, '/') . '/books/v3/invoices', [
                'organization_id' => $organizationId,
                'page' => $page,
                'per_page' => $perPage,
            ]);

        if (! $response->successful()) {
            throw new RuntimeException('Failed to fetch Zoho invoices: ' . $response->body());
        }

        $json = $response->json();

        if (! is_array($json)) {
            throw new RuntimeException('Invalid Zoho invoices response format.');
        }

        return $json;
    }

    public function extractInvoiceProjectId(array $invoice): ?string
    {
        $apiName = (string) config('services.zoho.invoice_project_customfield_api_name', 'cf_project_id');
        $apiName = trim($apiName);

        // 1) top-level: cf_project_id
        $topLevel = $this->toNullableString($invoice[$apiName] ?? null);
        if ($topLevel !== null) {
            return $topLevel;
        }

        // 2) custom_field_hash.cf_project_id
        $customFieldHash = $invoice['custom_field_hash'] ?? null;
        if (is_array($customFieldHash)) {
            $hashValue = $this->toNullableString($customFieldHash[$apiName] ?? null);
            if ($hashValue !== null) {
                return $hashValue;
            }
        }

        // 3) custom_fields[] entry by api_name or label
        $customFields = $invoice['custom_fields'] ?? null;
        if (is_array($customFields)) {
            foreach ($customFields as $field) {
                if (! is_array($field)) {
                    continue;
                }

                $fieldApiName = trim((string) ($field['api_name'] ?? ''));
                $fieldLabel = trim((string) ($field['label'] ?? ''));
                $matches = $fieldApiName === $apiName || strcasecmp($fieldLabel, 'Project ID') === 0;

                if (! $matches) {
                    continue;
                }

                $value = $this->toNullableString($field['value'] ?? null);
                if ($value !== null) {
                    return $value;
                }
            }
        }

        return null;
    }

    /**
     * @return array<int, array{customfield_id:string,value:string}>
     */
    public function buildProjectCustomFieldPayload(string $projectId): array
    {
        $customFieldId = trim((string) config('services.zoho.invoice_project_customfield_id'));
        $value = trim($projectId);

        if ($customFieldId === '' || $value === '') {
            return [];
        }

        return [[
            'customfield_id' => $customFieldId,
            'value' => $value,
        ]];
    }

    public function downloadInvoicePdf(string $zohoInvoiceId): string
    {
        $invoiceId = trim($zohoInvoiceId);
        $accessToken = $this->tokenService->getAccessToken();
        $apiDomain = (string) config('services.zoho.api_domain');
        $organizationId = (string) config('services.zoho.organization_id');

        if ($invoiceId === '') {
            throw new RuntimeException('Invoice id is required for Zoho invoice download.');
        }

        if ($apiDomain === '' || $organizationId === '') {
            throw new RuntimeException('Zoho API domain or organization id is missing.');
        }

        $baseUrl = rtrim($apiDomain, '/') . '/books/v3/invoices/' . $invoiceId;

        $response = Http::withToken($accessToken)
            ->timeout(45)
            ->accept('application/pdf')
            ->get($baseUrl, [
                'organization_id' => $organizationId,
                'accept' => 'pdf',
            ]);

        if (! $response->successful() || stripos((string) $response->header('Content-Type', ''), 'pdf') === false) {
            $response = Http::withToken($accessToken)
                ->timeout(45)
                ->accept('application/pdf')
                ->get($baseUrl, [
                    'organization_id' => $organizationId,
                ]);
        }

        if (! $response->successful()) {
            throw new RuntimeException('Failed to download Zoho invoice PDF: ' . $response->body());
        }

        $contentType = strtolower((string) $response->header('Content-Type', ''));
        if (strpos($contentType, 'pdf') === false) {
            throw new RuntimeException('Zoho invoice download did not return a PDF response.');
        }

        return $response->body();
    }

    private function toNullableString(mixed $value): ?string
    {
        if ($value === null) {
            return null;
        }

        $string = trim((string) $value);

        return $string === '' ? null : $string;
    }
}
