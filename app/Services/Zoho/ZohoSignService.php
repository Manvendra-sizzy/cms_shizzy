<?php

namespace App\Services\Zoho;

use App\Modules\HRMS\Employees\Models\EmployeeProfile;
use App\Modules\HRMS\Onboarding\Models\EmployeeOnboarding;
use App\Services\HRMS\EmploymentAgreementPdfService;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use RuntimeException;

/**
 * Zoho Sign: direct PDF upload (POST /requests) + submit — no templates.
 */
class ZohoSignService
{
    public function __construct(
        private readonly ZohoSignTokenService $tokenService,
        private readonly EmploymentAgreementPdfService $employmentAgreementPdfService,
    ) {
    }

    /**
     * Generate agreement PDF, create draft request, submit with signature field.
     *
     * @return array{submit_response: array<string, mixed>, create_response: array<string, mixed>, agreement_pdf_disk: string, agreement_pdf_path: string}
     */
    public function sendEmploymentAgreement(EmployeeOnboarding $onboarding, ?EmployeeProfile $profile = null): array
    {
        $this->validateAgreementReadyForZoho($onboarding, $profile);

        $onboarding->loadMissing(['designation', 'department', 'team']);

        $pdf = $this->employmentAgreementPdfService->generateAndStore($onboarding, $profile);
        $absolutePath = $this->employmentAgreementPdfService->absolutePath($pdf['disk'], $pdf['path']);

        $createResponse = $this->createDocumentRequest($absolutePath, $onboarding, $profile);
        $this->throwUnlessZohoSuccess($createResponse);

        $requestId = (string) data_get($createResponse, 'requests.request_id', '');
        if ($requestId === '') {
            throw new RuntimeException('Zoho Sign create request did not return request_id.');
        }

        $submitResponse = $this->submitDocumentRequest($requestId, $createResponse, $pdf['page_count']);
        $this->throwUnlessZohoSuccess($submitResponse);

        $this->zohoLog('info', 'zoho_sign.agreement_submitted', [
            'onboarding_id' => $onboarding->id,
            'request_id' => $requestId,
            'agreement_pdf_path' => $pdf['path'],
        ]);

        return [
            'submit_response' => $submitResponse,
            'create_response' => $createResponse,
            'agreement_pdf_disk' => $pdf['disk'],
            'agreement_pdf_path' => $pdf['path'],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function createDocumentRequest(string $absolutePdfPath, EmployeeOnboarding $onboarding, ?EmployeeProfile $profile = null): array
    {
        $baseUrl = rtrim((string) config('services.zoho_sign.base_url'), '/');
        $payload = $onboarding->employee_payload ?? [];
        $recipientName = (string) ($payload['full_name'] ?? $onboarding->full_name);
        $recipientEmail = (string) ($payload['personal_email'] ?? $onboarding->email);

        $data = [
            'requests' => [
                'request_name' => 'Employment Agreement — '.$recipientName,
                'description' => 'Employment agreement for HRMS onboarding #'.$onboarding->id,
                'is_sequential' => true,
                'expiration_days' => (int) config('services.zoho_sign.request_expiration_days', 30),
                'email_reminders' => filter_var(config('services.zoho_sign.email_reminders', true), FILTER_VALIDATE_BOOL),
                'reminder_period' => (int) config('services.zoho_sign.reminder_period_days', 3),
                'notes' => (string) config('services.zoho_sign.default_request_notes', 'Please review and sign your employment agreement.'),
                'actions' => [[
                    'action_type' => 'SIGN',
                    'recipient_name' => $recipientName,
                    'recipient_email' => $recipientEmail,
                    'signing_order' => 0,
                    'verify_recipient' => filter_var(config('services.zoho_sign.verify_recipient', false), FILTER_VALIDATE_BOOL),
                    'verification_type' => 'EMAIL',
                    'private_notes' => '',
                ]],
            ],
        ];

        $this->appendCompanySignerActionIfEnabled($data['requests']);

        $url = $baseUrl.'/requests';

        $this->logZohoHttpCall('POST', $url, ['step' => 'create_request_multipart']);

        $response = $this->httpZoho()
            ->timeout(120)
            ->attach('file', file_get_contents($absolutePdfPath), 'employment-agreement.pdf', [
                'Content-Type' => 'application/pdf',
            ])
            ->post($url, [
                'data' => json_encode($data, JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR),
            ]);

        if (! $response->successful()) {
            throw new RuntimeException('Zoho Sign create request failed: '.$response->body());
        }
        $json = $response->json();
        if (! is_array($json)) {
            $this->zohoLog('warning', 'zoho_sign.create_invalid_response', ['body' => $response->body()]);

            throw new RuntimeException('Zoho Sign create request failed (invalid response).');
        }

        return $json;
    }

    /**
     * @param  array<string, mixed>  $createResponse
     * @return array<string, mixed>
     */
    public function submitDocumentRequest(string $requestId, array $createResponse, int $pageCount): array
    {
        $baseUrl = rtrim((string) config('services.zoho_sign.base_url'), '/');
        $actionsOut = data_get($createResponse, 'requests.actions');
        if (! is_array($actionsOut) || $actionsOut === []) {
            throw new RuntimeException('Zoho Sign create response missing actions.');
        }

        $documentId = (string) data_get($createResponse, 'requests.document_ids.0.document_id', '');
        if ($documentId === '') {
            throw new RuntimeException('Zoho Sign create response missing document_id.');
        }

        $sig = (array) config('services.zoho_sign.signature_field', []);
        $pageOverride = config('services.zoho_sign.signature_page_no');
        $pageNo = $pageOverride !== null && $pageOverride !== ''
            ? (int) $pageOverride
            : max(0, $pageCount - 1);
        $yPrimary = (int) ($sig['y_coord'] ?? 620);
        $ySecondary = (int) config('services.zoho_sign.signature_field_secondary_y', 520);

        $submitActions = [];
        $signerIndex = 0;
        foreach ($actionsOut as $action) {
            if (! is_array($action)) {
                continue;
            }
            $actionId = (string) ($action['action_id'] ?? '');
            $actionType = (string) ($action['action_type'] ?? '');
            if ($actionId === '' || $actionType !== 'SIGN') {
                continue;
            }

            $yCoord = $signerIndex === 0 ? $yPrimary : $ySecondary;
            $signerIndex++;

            $submitActions[] = [
                'action_id' => $actionId,
                'action_type' => 'SIGN',
                'fields' => [
                    'image_fields' => [[
                        'field_type_name' => 'Signature',
                        'field_name' => 'Signature',
                        'field_label' => 'Signature',
                        'field_category' => 'image',
                        'document_id' => $documentId,
                        'action_id' => $actionId,
                        'page_no' => $pageNo,
                        'x_coord' => (int) ($sig['x_coord'] ?? 72),
                        'y_coord' => $yCoord,
                        'abs_width' => (int) ($sig['abs_width'] ?? 160),
                        'abs_height' => (int) ($sig['abs_height'] ?? 28),
                        'is_mandatory' => true,
                        'description_tooltip' => '',
                    ]],
                ],
            ];
        }

        if ($submitActions === []) {
            throw new RuntimeException('No SIGN actions found to submit on Zoho Sign request.');
        }

        $payload = ['requests' => ['actions' => $submitActions]];
        $url = $baseUrl.'/requests/'.rawurlencode($requestId).'/submit';

        $this->logZohoHttpCall('POST', $url, ['step' => 'submit_request', 'request_id' => $requestId]);

        $response = $this->httpZoho()
            ->acceptJson()
            ->asForm()
            ->timeout(90)
            ->post($url, [
                'data' => json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR),
            ]);

        if (! $response->successful()) {
            throw new RuntimeException('Zoho Sign submit failed: '.$response->body());
        }
        $json = $response->json();
        if (! is_array($json)) {
            $this->zohoLog('warning', 'zoho_sign.submit_invalid_response', ['body' => $response->body()]);

            throw new RuntimeException('Zoho Sign submit failed (invalid response).');
        }

        return $json;
    }

    /**
     * @return array<string, mixed>
     */
    public function fetchRequest(string $requestId): array
    {
        $baseUrl = rtrim((string) config('services.zoho_sign.base_url'), '/');
        $url = $baseUrl.'/requests/'.rawurlencode($requestId);

        $this->logZohoHttpCall('GET', $url, ['step' => 'get_request', 'request_id' => $requestId]);

        $response = $this->httpZoho()->acceptJson()->timeout(30)->get($url);
        if (! $response->successful()) {
            throw new RuntimeException('Zoho Sign get request failed: '.$response->body());
        }
        $json = $response->json();
        if (! is_array($json)) {
            throw new RuntimeException('Zoho Sign get request failed: '.$response->body());
        }

        return $json;
    }

    public function downloadSignedPdfBinary(string $requestId): string
    {
        $baseUrl = rtrim((string) config('services.zoho_sign.base_url'), '/');
        $url = $baseUrl.'/requests/'.rawurlencode($requestId).'/pdf';

        $this->logZohoHttpCall('GET', $url, ['step' => 'download_pdf', 'request_id' => $requestId]);

        $response = $this->httpZoho()->timeout(120)->get($url);
        if (! $response->successful()) {
            throw new RuntimeException('Zoho Sign PDF download failed: '.$response->body());
        }

        $body = $response->body();
        if ($body === '' || str_starts_with($body, '{')) {
            throw new RuntimeException('Zoho Sign PDF download returned non-PDF content.');
        }

        return $body;
    }

    /**
     * @throws RuntimeException
     */
    public function validateAgreementReadyForZoho(EmployeeOnboarding $onboarding, ?EmployeeProfile $profile = null): void
    {
        $payload = $onboarding->employee_payload ?? [];
        $hr = $payload['hr_agreement'] ?? [];

        $errors = [];

        $address = trim((string) ($payload['address'] ?? $profile?->address ?? ''));
        if ($address === '') {
            $errors[] = 'Employee address is required on the agreement (candidate form or profile).';
        }

        foreach (['basic_salary', 'other_allowance', 'gross_salary'] as $key) {
            if (! isset($hr[$key]) || $hr[$key] === '' || ! is_numeric($hr[$key])) {
                $errors[] = 'HR must save agreement salary fields ('.str_replace('_', ' ', $key).') before sending.';
            }
        }

        $joining = $onboarding->joining_date ?? $profile?->joining_date;
        if ($joining === null) {
            $errors[] = 'Joining date is missing on the onboarding record.';
        }

        $email = trim((string) ($payload['personal_email'] ?? $onboarding->email ?? ''));
        if ($email === '' || ! filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $errors[] = 'A valid recipient email is required for Zoho Sign.';
        }

        if ($errors !== []) {
            throw new RuntimeException(implode(' ', $errors));
        }
    }

    /**
     * @param  array<string, mixed>  $json
     */
    public function throwUnlessZohoSuccess(array $json): void
    {
        if ($this->isZohoSignApiSuccess($json)) {
            return;
        }

        $this->throwZohoSignApiError($json, json_encode($json));
    }

    /**
     * @param  array<string, mixed>  $json
     */
    private function isZohoSignApiSuccess(array $json): bool
    {
        $code = (int) ($json['code'] ?? -1);

        return $code === 0
            && (($json['status'] ?? '') === 'success' || isset($json['requests']));
    }

    /**
     * @param  array<string, mixed>  $json
     */
    private function throwZohoSignApiError(array $json, string $rawBody): never
    {
        $code = (int) ($json['code'] ?? 0);
        $message = trim((string) ($json['message'] ?? ''));

        if ($code === 12000) {
            throw new RuntimeException(
                'Zoho Sign: your current plan may not allow this API operation (Zoho error 12000). '
                . 'Confirm API access with Zoho or use a supported plan.'
            );
        }

        $detail = $message !== '' ? $message : $rawBody;
        throw new RuntimeException('Zoho Sign send failed: '.$detail);
    }

    /**
     * Log Zoho Sign diagnostics. Prefer storage/logs/laravel.log; if the file is not writable
     * (permissions on hosting), fall back to PHP error_log so logging never aborts the HTTP flow.
     *
     * @param  'info'|'warning'  $level
     * @param  array<string, mixed>  $context
     */
    private function zohoLog(string $level, string $message, array $context = []): void
    {
        try {
            $log = Log::channel('single');
            if ($level === 'warning') {
                $log->warning($message, $context);
            } else {
                $log->info($message, $context);
            }
        } catch (\Throwable $e) {
            $payload = $message;
            if ($context !== []) {
                $encoded = json_encode($context, JSON_UNESCAPED_UNICODE);
            if ($encoded === false) {
                $encoded = '{}';
            }
                $payload .= ' '.$encoded;
            }
            error_log($payload.' [laravel log failed: '.$e->getMessage().']');
        }
    }

    /**
     * @param  array<string, mixed>  $context
     */
    private function logZohoHttpCall(string $method, string $url, array $context = []): void
    {
        $this->zohoLog('info', 'zoho_sign.http_call', array_merge([
            'method' => $method,
            'url' => $url,
        ], $context));
    }

    private function httpZoho(): PendingRequest
    {
        return Http::withHeaders([
            'Authorization' => 'Zoho-oauthtoken '.$this->tokenService->getAccessToken(),
        ]);
    }

    /**
     * @param  array<string, mixed>  $requestsBlock
     */
    private function appendCompanySignerActionIfEnabled(array &$requestsBlock): void
    {
        if (! filter_var(config('services.zoho_sign.company_signatory_enabled', false), FILTER_VALIDATE_BOOL)) {
            return;
        }

        $name = (string) config('services.zoho_sign.company_signatory_name', '');
        $email = (string) config('services.zoho_sign.company_signatory_email', '');
        if ($name === '' || $email === '' || ! filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return;
        }

        $requestsBlock['actions'][] = [
            'action_type' => 'SIGN',
            'recipient_name' => $name,
            'recipient_email' => $email,
            'signing_order' => 1,
            'verify_recipient' => false,
            'verification_type' => 'EMAIL',
            'private_notes' => '',
        ];
    }
}
