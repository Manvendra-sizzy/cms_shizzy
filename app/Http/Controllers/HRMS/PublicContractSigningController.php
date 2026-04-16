<?php

namespace App\Http\Controllers\HRMS;

use App\Http\Controllers\Controller;
use App\Models\EmploymentAgreementContent;
use App\Services\HRMS\InbuiltContractSigningService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use RuntimeException;

class PublicContractSigningController extends Controller
{
    public function show(string $token, InbuiltContractSigningService $service)
    {
        $onboarding = $service->resolveByContractToken($token);
        if (! $onboarding || ! $onboarding->canSignContract()) {
            return response()->view('hrms.onboarding.contract_invalid', [], 403);
        }

        $onboarding->loadMissing(['designation', 'department', 'team']);
        $service->markOpened($onboarding, request());
        $template = EmploymentAgreementContent::resolveTemplateHtml();
        $agreementHtml = is_string($template) && trim($template) !== ''
            ? EmploymentAgreementContent::mergePlaceholders($template, $onboarding, $onboarding->finalEmployeeProfile)
            : '<p>Contract template is not configured.</p>';

        return view('hrms.onboarding.contract_sign', [
            'onboarding' => $onboarding->fresh(),
            'token' => $token,
            'agreementHtml' => $agreementHtml,
        ]);
    }

    public function submit(string $token, Request $request, InbuiltContractSigningService $service)
    {
        $onboarding = $service->resolveByContractToken($token);
        if (! $onboarding || ! $onboarding->canSignContract()) {
            return response()->view('hrms.onboarding.contract_invalid', [], 403);
        }

        $data = $request->validate([
            'agree_contract' => ['accepted'],
            'signer_name' => ['required', 'string', 'max:255'],
            'signature_data' => ['required', 'string'],
            'selfie' => ['required', 'file', 'mimes:jpg,jpeg,png,webp', 'max:6144'],
            'device_fingerprint' => ['nullable', 'string', 'max:1000'],
        ]);

        try {
            $result = $service->signContract($onboarding, [
                'signer_name' => $data['signer_name'],
                'consent_statement' => 'I have read and agree to the employment contract.',
                'signature_data' => $data['signature_data'],
                'selfie_file' => $request->file('selfie'),
                'device_fingerprint' => $data['device_fingerprint'] ?? '',
            ], $request);
        } catch (RuntimeException $e) {
            return back()->withErrors(['contract' => $e->getMessage()])->withInput();
        }

        return view('hrms.onboarding.contract_success', [
            'onboarding' => $onboarding->fresh(),
            'documentHash' => $result['document_hash'],
            'evidenceHash' => $result['evidence_chain_hash'],
            'downloadUrl' => Storage::disk('public')->url($result['signed_pdf_path']),
        ]);
    }
}
