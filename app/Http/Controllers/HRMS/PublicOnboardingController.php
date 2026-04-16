<?php

namespace App\Http\Controllers\HRMS;

use App\Http\Controllers\Controller;
use App\Models\EmploymentAgreementContent;
use App\Modules\HRMS\Onboarding\Models\EmployeeOnboarding;
use App\Services\HRMS\EmployeeLifecycleService;
use App\Services\HRMS\InbuiltContractSigningService;
use App\Services\HRMS\OnboardingService;
use Illuminate\Http\Request;
use RuntimeException;

class PublicOnboardingController extends Controller
{
    public function show(string $token, OnboardingService $service, InbuiltContractSigningService $contractService)
    {
        $onboarding = $service->resolveByPublicToken($token);
        if (! $onboarding) {
            return response()->view('hrms.onboarding.invalid_link', [], 404);
        }

        if ($onboarding->isExpired()) {
            $onboarding->update(['status' => EmployeeOnboarding::STATUS_EXPIRED]);

            return response()->view('hrms.onboarding.expired_link', ['onboarding' => $onboarding], 410);
        }

        if (! $onboarding->canEditByEmployee() && $onboarding->status !== EmployeeOnboarding::STATUS_SUBMITTED) {
            return response()->view('hrms.onboarding.invalid_link', [], 403);
        }

        $onboarding->load(['designation', 'department', 'team']);

        $agreementHtml = null;
        $canSignInbuiltContract = false;
        if ($onboarding->canSignContractEmbedded()) {
            $template = EmploymentAgreementContent::resolveTemplateHtml();
            if (is_string($template) && trim($template) !== '') {
                $agreementHtml = EmploymentAgreementContent::mergePlaceholders($template, $onboarding, $onboarding->finalEmployeeProfile);
                $contractService->markOpened($onboarding, request());
                $onboarding->refresh();
                $canSignInbuiltContract = true;
            }
        }

        return view('hrms.onboarding.form', [
            'onboarding' => $onboarding,
            'token' => $token,
            'agreementHtml' => $agreementHtml,
            'canSignInbuiltContract' => $canSignInbuiltContract,
        ]);
    }

    public function submitContract(string $token, Request $request, OnboardingService $service, InbuiltContractSigningService $contractService)
    {
        $onboarding = $service->resolveByPublicToken($token);
        if (! $onboarding || $onboarding->isExpired()) {
            return response()->view('hrms.onboarding.invalid_link', [], 403);
        }

        if (! $onboarding->canSignContractEmbedded()) {
            return redirect()->route('onboarding.show', ['token' => $token])
                ->withErrors(['contract' => 'This contract link is invalid, expired, or already signed.']);
        }

        $data = $request->validate([
            'agree_contract' => ['accepted'],
            'signer_name' => ['required', 'string', 'max:255'],
            'signature_data' => ['required', 'string'],
            'selfie' => ['required', 'file', 'mimes:jpg,jpeg,png,webp', 'max:6144'],
            'device_fingerprint' => ['nullable', 'string', 'max:1000'],
        ]);

        try {
            $contractService->signContract($onboarding, [
                'signer_name' => $data['signer_name'],
                'consent_statement' => 'I have read and agree to the employment contract.',
                'signature_data' => $data['signature_data'],
                'selfie_file' => $request->file('selfie'),
                'device_fingerprint' => $data['device_fingerprint'] ?? '',
            ], $request);
        } catch (RuntimeException $e) {
            return redirect()->route('onboarding.show', ['token' => $token])
                ->withErrors(['contract' => $e->getMessage()])
                ->withInput();
        }

        return redirect()->route('onboarding.show', ['token' => $token])
            ->with('status', 'Your employment agreement has been signed successfully.');
    }

    public function submit(string $token, Request $request, OnboardingService $service)
    {
        $onboarding = $service->resolveByPublicToken($token);
        if (! $onboarding || $onboarding->isExpired() || ! $onboarding->canEditByEmployee()) {
            return response()->view('hrms.onboarding.invalid_link', [], 403);
        }

        $rules = [
            'date_of_birth' => ['required_unless:action,save', 'nullable', 'date'],
            'address' => ['required_unless:action,save', 'nullable', 'string', 'max:3000'],
            'emergency_contact_1_name' => ['required_unless:action,save', 'nullable', 'string', 'max:255'],
            'emergency_contact_1_phone' => ['required_unless:action,save', 'nullable', 'string', 'max:32'],
            'emergency_contact_2_name' => ['required_unless:action,save', 'nullable', 'string', 'max:255'],
            'emergency_contact_2_phone' => ['required_unless:action,save', 'nullable', 'string', 'max:32'],
            'bank_account_number' => ['required_unless:action,save', 'nullable', 'string', 'max:64'],
            'bank_ifsc_code' => ['required_unless:action,save', 'nullable', 'string', 'max:32'],
            'bank_name' => ['required_unless:action,save', 'nullable', 'string', 'max:255'],
            'profile_photo' => ['nullable', 'file', 'mimes:jpg,jpeg,png,webp', 'max:5120', 'required_if:action,submit'],
            'pan_card' => ['nullable', 'file', 'mimes:pdf,jpg,jpeg,png', 'max:5120', 'required_if:action,submit'],
            'id_card' => ['nullable', 'file', 'mimes:pdf,jpg,jpeg,png', 'max:5120', 'required_if:action,submit'],
            'action' => ['required', 'in:save,submit'],
        ];
        if ($onboarding->employee_type !== EmployeeLifecycleService::TYPE_INTERN) {
            $rules['experience_letter'] = ['nullable', 'file', 'mimes:pdf,jpg,jpeg,png', 'max:5120'];
            $rules['relieving_letter'] = ['nullable', 'file', 'mimes:pdf,jpg,jpeg,png', 'max:5120'];
            $rules['salary_slip_1'] = ['nullable', 'file', 'mimes:pdf,jpg,jpeg,png', 'max:5120'];
            $rules['salary_slip_2'] = ['nullable', 'file', 'mimes:pdf,jpg,jpeg,png', 'max:5120'];
            $rules['salary_slip_3'] = ['nullable', 'file', 'mimes:pdf,jpg,jpeg,png', 'max:5120'];
        }

        $data = $request->validate($rules);

        $action = $data['action'];
        unset($data['action']);

        // Drop empty strings so partial "save" does not overwrite stored values with blanks.
        $data = array_filter($data, static fn ($v) => $v !== null && $v !== '');

        $merged = $service->applyHrLockedFieldsToPayload($onboarding, $data);

        $files = [
            'profile_photo' => $request->file('profile_photo'),
            'pan_card' => $request->file('pan_card'),
            'id_card' => $request->file('id_card'),
            'experience_letter' => $request->file('experience_letter'),
            'relieving_letter' => $request->file('relieving_letter'),
            'salary_slip_1' => $request->file('salary_slip_1'),
            'salary_slip_2' => $request->file('salary_slip_2'),
            'salary_slip_3' => $request->file('salary_slip_3'),
        ];
        if ($onboarding->employee_type === EmployeeLifecycleService::TYPE_INTERN) {
            foreach (['experience_letter', 'relieving_letter', 'salary_slip_1', 'salary_slip_2', 'salary_slip_3'] as $k) {
                unset($files[$k]);
            }
        }

        if ($action === 'save') {
            $service->saveEmployeeProgress($onboarding, $merged, $files);

            return back()->with('status', 'Progress saved.');
        }

        $docKeys = ['profile_photo', 'pan_card', 'id_card'];
        foreach ($docKeys as $key) {
            if (! $request->file($key) && ! $onboarding->documents()->where('doc_key', $key)->exists()) {
                $labels = [
                    'profile_photo' => 'Profile photo',
                    'pan_card' => 'PAN card',
                    'id_card' => 'ID proof',
                ];

                return back()->withErrors([$key => ($labels[$key] ?? $key) . ' is required before final submission.'])->withInput();
            }
        }

        $service->submitByEmployee($onboarding, $merged, $files);

        return view('hrms.onboarding.success', ['onboarding' => $onboarding->fresh()]);
    }
}

