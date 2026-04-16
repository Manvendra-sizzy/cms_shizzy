<?php

namespace App\Http\Controllers\HRMS;

use App\Http\Controllers\Controller;
use App\Models\OrganizationDepartment;
use App\Models\OrganizationDesignation;
use App\Models\OrganizationTeam;
use App\Modules\HRMS\Employees\Models\EmployeeProfile;
use App\Modules\HRMS\Onboarding\Models\EmployeeOnboarding;
use App\Mail\OnboardingRejectedMail;
use App\Services\HRMS\EmployeeLifecycleService;
use App\Services\HRMS\GrossSalaryBreakdown;
use App\Services\HRMS\InbuiltContractSigningService;
use App\Services\HRMS\OnboardingService;
use App\Services\Zoho\ZohoSignService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use RuntimeException;

class HROnboardingsController extends Controller
{
    public function index(Request $request)
    {
        $q = EmployeeOnboarding::query()->with(['designation', 'department', 'team']);

        if ($request->filled('status')) {
            $q->where('status', $request->string('status'));
        }
        if ($request->filled('search')) {
            $needle = trim((string) $request->input('search'));
            $q->where(function ($query) use ($needle) {
                $query->where('full_name', 'like', '%' . $needle . '%')
                    ->orWhere('email', 'like', '%' . $needle . '%');
            });
        }

        return view('hrms.hr.onboardings.index', [
            'items' => $q->latest('id')->paginate(20)->withQueryString(),
            'statuses' => EmployeeOnboarding::statuses(),
            'counts' => EmployeeOnboarding::query()->selectRaw('status, COUNT(*) as c')->groupBy('status')->pluck('c', 'status'),
        ]);
    }

    public function create()
    {
        return view('hrms.hr.onboardings.create', [
            'departments' => OrganizationDepartment::query()->where('active', true)->orderBy('name')->get(),
            'teams' => OrganizationTeam::query()->where('active', true)->orderBy('name')->get(),
            'designations' => OrganizationDesignation::query()->where('active', true)->orderBy('name')->get(),
            'employeeTypes' => EmployeeLifecycleService::employeeTypeLabels(),
        ]);
    }

    public function store(Request $request, OnboardingService $service)
    {
        $data = $request->validate([
            'full_name' => ['required', 'string', 'max:255'],
            'personal_email' => ['required', 'email', 'max:255'],
            'phone' => ['nullable', 'string', 'max:32'],
            'employee_type' => ['required', 'in:' . implode(',', array_keys(EmployeeLifecycleService::employeeTypeLabels()))],
            'designation_id' => ['required', 'exists:organization_designations,id'],
            'department_id' => ['required', 'exists:organization_departments,id'],
            'team_id' => ['nullable', 'exists:organization_teams,id'],
            'joining_date' => ['required', 'date'],
            /** Same split as payroll slips: basic 50%, HRA 50% of basic, remainder other allowance. */
            'gross_salary' => ['required', 'numeric', 'min:0.01'],
            /** Optional prefill for agreement / Zoho (candidate can still submit their address). */
            'address' => ['nullable', 'string', 'max:2000'],
            /** Stored in employee_payload.hr_agreement (Zoho merge field DATE). */
            'agreement_made_date' => ['nullable', 'date'],
            'send_now' => ['nullable', 'boolean'],
        ]);

        $onboarding = $service->createPreOnboarding($data);
        if ((bool) $request->boolean('send_now')) {
            $service->issueOrResendLink($onboarding);
        }

        return redirect()->route('admin.hrms.onboardings.show', $onboarding)->with('status', 'Onboarding created successfully.');
    }

    public function show(EmployeeOnboarding $onboarding)
    {
        $onboarding->load(['designation', 'department', 'team', 'documents', 'finalEmployeeProfile.user', 'contractEvidenceLogs']);

        return view('hrms.hr.onboardings.show', ['onboarding' => $onboarding]);
    }

    public function resendLink(EmployeeOnboarding $onboarding, OnboardingService $service)
    {
        if (in_array($onboarding->status, [
            EmployeeOnboarding::STATUS_APPROVED,
            EmployeeOnboarding::STATUS_AGREEMENT_SENT,
            EmployeeOnboarding::STATUS_AGREEMENT_SIGNED,
            EmployeeOnboarding::STATUS_COMPLETED,
        ], true)) {
            return back()->withErrors(['status' => 'Cannot resend onboarding link after approval or agreement flow has started.']);
        }

        $service->issueOrResendLink($onboarding);

        return back()->with('status', 'Onboarding link sent.');
    }

    public function approve(EmployeeOnboarding $onboarding, OnboardingService $service, InbuiltContractSigningService $inbuiltContractService)
    {
        $result = $service->approveAndFinalize($onboarding, (int) Auth::id());

        $message = 'Onboarding approved and employee profile finalized.';
        $inbuiltError = null;
        try {
            $inbuiltContractService->issueContractLink($result['onboarding']->fresh());
        } catch (\Throwable $e) {
            $inbuiltError = $e->getMessage();
        }

        if (! empty($result['zoho_error'])) {
            $warning = 'Employment agreement could not be sent via Zoho Sign: ' . $result['zoho_error'];
            if ($inbuiltError !== null) {
                $warning .= ' | Inbuilt contract link send failed: '.$inbuiltError;
            }

            return back()->with('status', $message)->with('warning', $warning);
        }

        if ($inbuiltError !== null) {
            return back()->with('status', $message)
                ->with('warning', 'Inbuilt contract link send failed: '.$inbuiltError);
        }

        return back()->with('status', $message . ' Contract signing link has been sent to employee email.');
    }

    public function reject(Request $request, EmployeeOnboarding $onboarding)
    {
        $data = $request->validate([
            'hr_notes' => ['required', 'string', 'max:2000'],
        ]);

        $reason = $data['hr_notes'];

        $onboarding->update([
            'status' => EmployeeOnboarding::STATUS_REJECTED,
            'rejected_at' => now(),
            'hr_notes' => $reason,
        ]);

        try {
            Mail::to($onboarding->email)->send(new OnboardingRejectedMail($onboarding->fresh(), $reason));
        } catch (\Throwable $e) {
            Log::warning('CMS email notification failed for onboarding rejected', [
                'onboarding_id' => $onboarding->id,
                'error' => $e->getMessage(),
            ]);
        }

        return back()->with('status', 'Onboarding rejected. The candidate was notified by email.');
    }

    public function saveAgreementDetails(Request $request, EmployeeOnboarding $onboarding)
    {
        if (! $onboarding->hrCanEditAgreementPrefill()) {
            return back()->withErrors(['status' => 'Agreement details cannot be edited for this record.']);
        }

        $data = $request->validate([
            'agreement_made_date' => ['nullable', 'date'],
            'gross_salary' => ['nullable', 'numeric', 'min:0.01'],
        ]);

        $payload = $onboarding->employee_payload ?? [];
        $hr = $payload['hr_agreement'] ?? [];
        $hasStoredGross = isset($hr['gross_salary']) && is_numeric($hr['gross_salary']) && (float) $hr['gross_salary'] > 0;
        $grossInRequest = isset($data['gross_salary']) && $data['gross_salary'] !== null && $data['gross_salary'] !== '';

        if ($grossInRequest) {
            $breakdown = GrossSalaryBreakdown::fromGross((float) $data['gross_salary']);
            $payload['hr_agreement'] = array_merge($hr, $breakdown, [
                'agreement_made_date' => $data['agreement_made_date'] ?? ($hr['agreement_made_date'] ?? null),
            ]);
        } elseif ($hasStoredGross) {
            $payload['hr_agreement'] = array_merge($hr, [
                'agreement_made_date' => $data['agreement_made_date'] ?? ($hr['agreement_made_date'] ?? null),
            ]);
        } else {
            return back()->withErrors(['gross_salary' => 'Gross salary is required. Enter it here or set it when creating the onboarding.']);
        }

        $onboarding->update(['employee_payload' => $payload]);

        return back()->with('status', 'Employment agreement details saved. You can send the agreement for signature when ready.');
    }

    public function sendAgreement(EmployeeOnboarding $onboarding, ZohoSignService $zohoSignService)
    {
        if (! $onboarding->canSendAgreement()) {
            return back()->withErrors(['status' => 'Agreement can only be sent after the employee record is created (approval).']);
        }

        try {
            $onboarding->loadMissing('finalEmployeeProfile');
            $zoho = $zohoSignService->sendEmploymentAgreement($onboarding, $onboarding->finalEmployeeProfile);
        } catch (RuntimeException $e) {
            return back()->withErrors(['zoho' => $e->getMessage()]);
        }

        $submit = $zoho['submit_response'];
        $requestId = (string) data_get($submit, 'requests.request_id', '');
        $status = (string) data_get($submit, 'requests.request_status', 'sent');
        $status = $status !== '' ? $status : 'sent';

        $onboarding->update([
            'status' => EmployeeOnboarding::STATUS_AGREEMENT_SENT,
            'zoho_sign_request_id' => $requestId !== '' ? $requestId : null,
            'zoho_sign_status' => $status,
            'zoho_sign_sent_at' => now(),
            'zoho_sign_agreement_pdf_path' => $zoho['agreement_pdf_path'],
            'zoho_sign_meta' => [
                'create_response' => $zoho['create_response'],
                'submit_response' => $submit,
            ],
        ]);

        Log::info('hrms.onboarding.send_agreement', [
            'onboarding_id' => $onboarding->id,
            'zoho_request_id' => $requestId,
        ]);

        return back()->with('status', 'Agreement sent via Zoho Sign.');
    }

    public function sendInbuiltContract(EmployeeOnboarding $onboarding, InbuiltContractSigningService $service)
    {
        try {
            $service->issueContractLink($onboarding->fresh());
        } catch (RuntimeException $e) {
            return back()->withErrors(['contract' => $e->getMessage()]);
        }

        return back()->with('status', 'Inbuilt contract signing link sent to employee.');
    }

    public function downloadInbuiltSignedContract(EmployeeOnboarding $onboarding)
    {
        $path = (string) ($onboarding->contract_signed_pdf_path ?? '');
        if ($path === '' || ! Storage::disk('public')->exists($path)) {
            return back()->withErrors(['contract' => 'Signed inbuilt contract PDF is not available yet.']);
        }

        return $this->downloadPublicFile($path, 'signed-employment-contract.pdf');
    }

    public function syncZohoStatus(EmployeeOnboarding $onboarding, ZohoSignService $zohoSignService)
    {
        $requestId = $onboarding->zoho_sign_request_id;
        if ($requestId === null || $requestId === '') {
            return back()->withErrors(['zoho' => 'No Zoho Sign request is stored for this onboarding.']);
        }

        try {
            $json = $zohoSignService->fetchRequest($requestId);
            $zohoSignService->throwUnlessZohoSuccess($json);
        } catch (RuntimeException $e) {
            return back()->withErrors(['zoho' => $e->getMessage()]);
        }

        $status = strtolower((string) data_get($json, 'requests.request_status', ''));
        $updates = [
            'zoho_sign_status' => $status !== '' ? $status : $onboarding->zoho_sign_status,
            'zoho_sign_meta' => array_merge($onboarding->zoho_sign_meta ?? [], ['last_sync_response' => $json]),
        ];
        if ($status === 'completed') {
            $updates['zoho_sign_completed_at'] = $onboarding->zoho_sign_completed_at ?? now();
            $updates['zoho_sign_signed_at'] = $onboarding->zoho_sign_signed_at ?? now();
            $updates['status'] = EmployeeOnboarding::STATUS_AGREEMENT_SIGNED;
        }
        $onboarding->update($updates);

        Log::info('hrms.onboarding.zoho_sync', [
            'onboarding_id' => $onboarding->id,
            'zoho_status' => $status,
        ]);

        return back()->with('status', 'Zoho Sign status updated.');
    }

    public function downloadSignedAgreement(EmployeeOnboarding $onboarding, ZohoSignService $zohoSignService)
    {
        $requestId = $onboarding->zoho_sign_request_id;
        if ($requestId === null || $requestId === '') {
            return back()->withErrors(['zoho' => 'No Zoho Sign request is stored for this onboarding.']);
        }

        if ($onboarding->zoho_sign_signed_pdf_path && Storage::disk('public')->exists($onboarding->zoho_sign_signed_pdf_path)) {
            return $this->downloadPublicFile($onboarding->zoho_sign_signed_pdf_path, 'signed-employment-agreement.pdf');
        }

        try {
            $json = $zohoSignService->fetchRequest($requestId);
            $zohoSignService->throwUnlessZohoSuccess($json);
        } catch (RuntimeException $e) {
            return back()->withErrors(['zoho' => $e->getMessage()]);
        }

        $st = strtolower((string) data_get($json, 'requests.request_status', ''));
        if ($st !== 'completed') {
            return back()->withErrors(['zoho' => 'The agreement is not completed in Zoho Sign yet (current status: '.($st !== '' ? $st : 'unknown').'). Try syncing status first.']);
        }

        try {
            $binary = $zohoSignService->downloadSignedPdfBinary($requestId);
        } catch (RuntimeException $e) {
            return back()->withErrors(['zoho' => $e->getMessage()]);
        }

        $relative = 'hrms/onboarding/'.$onboarding->id.'/signed-agreement-'.now()->format('YmdHis').'.pdf';
        Storage::disk('public')->put($relative, $binary);

        $onboarding->update([
            'status' => EmployeeOnboarding::STATUS_AGREEMENT_SIGNED,
            'zoho_sign_signed_pdf_path' => $relative,
            'zoho_sign_signed_at' => $onboarding->zoho_sign_signed_at ?? now(),
            'zoho_sign_completed_at' => $onboarding->zoho_sign_completed_at ?? now(),
            'zoho_sign_status' => 'completed',
        ]);

        if ($onboarding->final_employee_profile_id) {
            EmployeeProfile::query()->whereKey($onboarding->final_employee_profile_id)->update([
                'signed_contract_path' => $relative,
            ]);
        }

        Log::info('hrms.onboarding.zoho_signed_pdf_stored', [
            'onboarding_id' => $onboarding->id,
            'path' => $relative,
        ]);

        return $this->downloadPublicFile($relative, 'signed-employment-agreement.pdf');
    }

    /**
     * Serve a public-disk file without Laravel's streamed download (uses fpassthru), which breaks on hosts that disable stream functions.
     */
    private function downloadPublicFile(string $relativePath, string $downloadName): \Illuminate\Http\Response
    {
        $disk = Storage::disk('public');
        $contents = $disk->get($relativePath);
        $mime = $disk->mimeType($relativePath) ?: 'application/octet-stream';

        return response($contents, 200, [
            'Content-Type' => $mime,
            'Content-Disposition' => 'attachment; filename="'.$downloadName.'"',
        ]);
    }
}

