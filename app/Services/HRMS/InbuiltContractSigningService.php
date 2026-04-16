<?php

namespace App\Services\HRMS;

use App\Mail\OnboardingContractSignatureMail;
use App\Models\EmploymentAgreementContent;
use App\Modules\HRMS\Employees\Models\EmployeeProfile;
use App\Modules\HRMS\Onboarding\Models\EmployeeOnboarding;
use App\Modules\HRMS\Onboarding\Models\OnboardingContractEvidenceLog;
use Dompdf\Dompdf;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use RuntimeException;

class InbuiltContractSigningService
{
    public function issueContractLink(EmployeeOnboarding $onboarding, bool $sendMail = true): string
    {
        if (! $onboarding->canSendAgreement()) {
            throw new RuntimeException('Contract can be sent only after onboarding approval/finalization.');
        }

        $token = Str::random(64);
        $ttlHours = (int) config('services.onboarding.contract_link_ttl_hours', 120);

        $onboarding->update([
            'contract_status' => EmployeeOnboarding::CONTRACT_STATUS_PENDING,
            'contract_token_hash' => hash('sha256', $token),
            'contract_token_expires_at' => now()->addHours(max(1, $ttlHours)),
            'contract_sent_at' => now(),
            'status' => EmployeeOnboarding::STATUS_AGREEMENT_SENT,
        ]);

        $this->appendEvidence($onboarding, 'contract_link_issued', [
            'contract_status' => EmployeeOnboarding::CONTRACT_STATUS_PENDING,
            'expires_at' => optional($onboarding->fresh()->contract_token_expires_at)?->toIso8601String(),
            'onboarding_status' => EmployeeOnboarding::STATUS_AGREEMENT_SENT,
        ], null);

        if ($sendMail) {
            Mail::to($onboarding->email)->send(new OnboardingContractSignatureMail($onboarding->fresh(), $token));
        }

        return $token;
    }

    public function resolveByContractToken(string $token): ?EmployeeOnboarding
    {
        return EmployeeOnboarding::query()
            ->where('contract_token_hash', hash('sha256', $token))
            ->first();
    }

    public function markOpened(EmployeeOnboarding $onboarding, Request $request): void
    {
        if ($onboarding->contract_opened_at !== null) {
            return;
        }

        $onboarding->update([
            'contract_status' => EmployeeOnboarding::CONTRACT_STATUS_OPENED,
            'contract_opened_at' => now(),
        ]);

        $this->appendEvidence($onboarding, 'contract_opened', [
            'contract_status' => EmployeeOnboarding::CONTRACT_STATUS_OPENED,
            'request_url' => $request->fullUrl(),
        ], $request);
    }

    /**
     * @param  array<string, mixed>  $payload
     * @return array{signed_pdf_path:string,document_hash:string,signature_hash:string,selfie_hash:string,evidence_chain_hash:string}
     */
    public function signContract(EmployeeOnboarding $onboarding, array $payload, Request $request): array
    {
        if (! $onboarding->canSignContract()) {
            throw new RuntimeException('This contract link is invalid, expired, or already signed.');
        }

        $signatureRaw = $this->decodeBase64Png((string) ($payload['signature_data'] ?? ''));
        if ($signatureRaw === '') {
            throw new RuntimeException('Signature drawing is required.');
        }

        $selfie = $payload['selfie_file'] ?? null;
        if (! $selfie instanceof \Illuminate\Http\UploadedFile) {
            throw new RuntimeException('Selfie capture is required.');
        }

        $onboarding->loadMissing(['designation', 'department', 'team', 'finalEmployeeProfile']);

        $template = EmploymentAgreementContent::resolveTemplateHtml();
        if (! is_string($template) || trim($template) === '') {
            throw new RuntimeException('Contract template is missing in HRMS -> Employment agreement.');
        }

        $profile = $onboarding->finalEmployeeProfile instanceof EmployeeProfile ? $onboarding->finalEmployeeProfile : null;
        $agreementHtml = EmploymentAgreementContent::mergePlaceholders($template, $onboarding, $profile);
        $documentHash = hash('sha256', $agreementHtml);

        $basePath = 'hrms/onboarding/'.$onboarding->id.'/contract';
        $signaturePath = $basePath.'/signature-'.now()->format('YmdHis').'.png';
        Storage::disk('local')->put($signaturePath, $signatureRaw);

        $selfiePath = $selfie->store($basePath, 'local');
        $selfieRaw = Storage::disk('local')->get($selfiePath);
        $signatureHash = hash('sha256', $signatureRaw);
        $selfieHash = hash('sha256', $selfieRaw);

        $signedPdfPath = $this->generateSignedPdf(
            $onboarding,
            $agreementHtml,
            $signatureRaw,
            $selfieRaw,
            [
                'signer_name' => (string) ($payload['signer_name'] ?? ''),
                'consent_statement' => (string) ($payload['consent_statement'] ?? ''),
                'device_fingerprint' => (string) ($payload['device_fingerprint'] ?? ''),
                'ip' => (string) $request->ip(),
                'user_agent' => (string) $request->userAgent(),
                'signed_at' => now()->toDateTimeString(),
                'document_hash' => $documentHash,
                'signature_hash' => $signatureHash,
                'selfie_hash' => $selfieHash,
            ]
        );

        return DB::transaction(function () use (
            $onboarding,
            $payload,
            $request,
            $documentHash,
            $signatureHash,
            $selfieHash,
            $signaturePath,
            $selfiePath,
            $signedPdfPath
        ): array {
            $nextMainStatus = $onboarding->status;
            if (in_array($onboarding->status, [
                EmployeeOnboarding::STATUS_APPROVED,
                EmployeeOnboarding::STATUS_COMPLETED,
                EmployeeOnboarding::STATUS_AGREEMENT_SENT,
            ], true)) {
                $nextMainStatus = EmployeeOnboarding::STATUS_AGREEMENT_SIGNED;
            }

            $onboarding->update([
                'contract_status' => EmployeeOnboarding::CONTRACT_STATUS_SIGNED,
                'contract_agreed_at' => now(),
                'contract_signed_at' => now(),
                'contract_signature_path' => $signaturePath,
                'contract_selfie_path' => $selfiePath,
                'contract_signed_pdf_path' => $signedPdfPath,
                'contract_document_hash' => $documentHash,
                'status' => $nextMainStatus,
                'contract_sign_meta' => [
                    'signer_name' => (string) ($payload['signer_name'] ?? ''),
                    'signer_email' => $onboarding->email,
                    'device_fingerprint' => (string) ($payload['device_fingerprint'] ?? ''),
                    'ip_address' => (string) $request->ip(),
                    'user_agent' => (string) $request->userAgent(),
                    'accept_language' => (string) $request->header('Accept-Language', ''),
                    'referer' => (string) $request->header('referer', ''),
                    'signature_hash' => $signatureHash,
                    'selfie_hash' => $selfieHash,
                    'document_hash' => $documentHash,
                ],
            ]);

            if ($onboarding->final_employee_profile_id) {
                EmployeeProfile::query()
                    ->whereKey($onboarding->final_employee_profile_id)
                    ->update(['signed_contract_path' => $signedPdfPath]);
            }

            $log = $this->appendEvidence($onboarding, 'contract_signed', [
                'signer_name' => (string) ($payload['signer_name'] ?? ''),
                'signer_email' => $onboarding->email,
                'agreement_checked' => true,
                'device_fingerprint' => (string) ($payload['device_fingerprint'] ?? ''),
                'document_hash' => $documentHash,
                'signature_hash' => $signatureHash,
                'selfie_hash' => $selfieHash,
                'signature_path' => $signaturePath,
                'selfie_path' => $selfiePath,
                'signed_pdf_path' => $signedPdfPath,
            ], $request);

            return [
                'signed_pdf_path' => $signedPdfPath,
                'document_hash' => $documentHash,
                'signature_hash' => $signatureHash,
                'selfie_hash' => $selfieHash,
                'evidence_chain_hash' => $log->event_hash,
            ];
        });
    }

    public function appendEvidence(EmployeeOnboarding $onboarding, string $eventType, array $payload, ?Request $request): OnboardingContractEvidenceLog
    {
        $previous = OnboardingContractEvidenceLog::query()
            ->where('employee_onboarding_id', $onboarding->id)
            ->latest('id')
            ->first();
        $previousHash = $previous?->event_hash;

        $canonical = json_encode($payload, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
        if ($canonical === false) {
            $canonical = '{}';
        }

        $eventHash = hash('sha256', implode('|', [
            'onboarding_contract_evidence_v1',
            (string) $onboarding->id,
            $eventType,
            (string) $previousHash,
            $canonical,
        ]));

        return OnboardingContractEvidenceLog::query()->create([
            'employee_onboarding_id' => $onboarding->id,
            'event_type' => $eventType,
            'event_hash' => $eventHash,
            'previous_hash' => $previousHash,
            'ip_address' => $request?->ip(),
            'user_agent' => $request?->userAgent(),
            'payload' => $payload,
        ]);
    }

    private function decodeBase64Png(string $value): string
    {
        if (! preg_match('/^data:image\/png;base64,([A-Za-z0-9+\/=]+)$/', $value, $m)) {
            return '';
        }

        $raw = base64_decode($m[1], true);
        if ($raw === false) {
            return '';
        }

        if (strlen($raw) > 2_500_000) {
            return '';
        }

        return $raw;
    }

    /**
     * @param  array<string, string>  $evidence
     */
    private function generateSignedPdf(EmployeeOnboarding $onboarding, string $agreementHtml, string $signatureRaw, string $selfieRaw, array $evidence): string
    {
        $html = view('hrms.onboarding.contract_signed_pdf', [
            'onboarding' => $onboarding,
            'agreementBodyHtml' => $agreementHtml,
            'signatureDataUri' => 'data:image/png;base64,'.base64_encode($signatureRaw),
            'selfieDataUri' => 'data:image/jpeg;base64,'.base64_encode($selfieRaw),
            'evidence' => $evidence,
        ])->render();

        $dompdf = new Dompdf([
            'defaultFont' => 'DejaVu Sans',
            'isRemoteEnabled' => true,
        ]);
        $dompdf->loadHtml($html, 'UTF-8');
        $dompdf->setPaper('A4', 'portrait');
        $dompdf->render();

        $bytes = $dompdf->output();
        $relative = 'hrms/onboarding/'.$onboarding->id.'/contract/signed-contract-'.now()->format('YmdHis').'.pdf';
        Storage::disk('public')->put($relative, $bytes);

        return $relative;
    }
}
