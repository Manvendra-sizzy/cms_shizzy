<?php

namespace App\Services\HRMS;

use App\Mail\OnboardingInvitationMail;
use App\Models\CmsActivityLog;
use App\Models\EmployeeEmergencyContact;
use App\Models\User;
use App\Modules\HRMS\Employees\Models\EmployeeProfile;
use App\Modules\HRMS\Onboarding\Models\EmployeeOnboarding;
use App\Modules\HRMS\Onboarding\Models\OnboardingDocument;
use App\Services\HRMS\GrossSalaryBreakdown;
use App\Services\Zoho\ZohoSignService;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use RuntimeException;

class OnboardingService
{
    public function __construct(
        private readonly EmployeeLifecycleService $lifecycle,
        private readonly ZohoSignService $zohoSignService,
    ) {
    }

    public function createPreOnboarding(array $data): EmployeeOnboarding
    {
        $gross = (float) ($data['gross_salary'] ?? 0);
        $breakdown = GrossSalaryBreakdown::fromGross($gross);

        $hrAgreement = array_merge($breakdown, [
            'agreement_made_date' => $data['agreement_made_date'] ?? null,
        ]);

        $employeePayload = ['hr_agreement' => $hrAgreement];
        $addr = trim((string) ($data['address'] ?? ''));
        if ($addr !== '') {
            $employeePayload['address'] = $addr;
        }

        return EmployeeOnboarding::query()->create([
            'full_name' => trim((string) $data['full_name']),
            'email' => mb_strtolower(trim((string) ($data['personal_email'] ?? $data['email'] ?? ''))),
            'phone' => $data['phone'] ?? null,
            'employee_type' => $data['employee_type'],
            'designation_id' => $data['designation_id'] ?? null,
            'department_id' => $data['department_id'] ?? null,
            'team_id' => $data['team_id'] ?? null,
            'joining_date' => $data['joining_date'] ?? null,
            'status' => EmployeeOnboarding::STATUS_DRAFT,
            'hr_notes' => $data['hr_notes'] ?? null,
            /** HR-only fields for agreement merge; preserved when employee submits (no new DB columns). */
            'employee_payload' => $employeePayload,
        ]);
    }

    public function issueOrResendLink(EmployeeOnboarding $onboarding): string
    {
        $token = Str::random(64);
        $ttlHours = (int) config('services.onboarding.link_ttl_hours', 72);
        $expires = now()->addHours(max(1, $ttlHours));
        $hash = hash('sha256', $token);

        $updates = [
            'token_hash' => $hash,
            'token_expires_at' => $expires,
            'link_sent_at' => now(),
            'status' => EmployeeOnboarding::STATUS_LINK_SENT,
        ];

        // Same secure token as onboarding: employee can review and sign the employment agreement on the onboarding page.
        if ($onboarding->contract_signed_at === null) {
            $updates['contract_token_hash'] = $hash;
            $updates['contract_token_expires_at'] = $expires;
            $updates['contract_status'] = EmployeeOnboarding::CONTRACT_STATUS_PENDING;
            $updates['contract_sent_at'] = now();
        }

        $onboarding->update($updates);

        Mail::to($onboarding->email)->send(new OnboardingInvitationMail($onboarding->fresh(), $token));

        return $token;
    }

    public function resolveByPublicToken(string $token): ?EmployeeOnboarding
    {
        $hash = hash('sha256', $token);

        return EmployeeOnboarding::query()->where('token_hash', $hash)->first();
    }

    /**
     * Merge employee-submitted fields with HR-locked values so the payload cannot be overridden from the browser.
     *
     * @param  array<string, mixed>  $employeeFields
     * @return array<string, mixed>
     */
    public function applyHrLockedFieldsToPayload(EmployeeOnboarding $onboarding, array $employeeFields): array
    {
        $onboarding->loadMissing(['designation', 'department', 'team']);

        $locked = [
            'full_name' => $onboarding->full_name,
            'personal_email' => $onboarding->email,
            'phone' => $onboarding->phone,
            'employee_type' => $onboarding->employee_type,
            'designation_id' => $onboarding->designation_id,
            'department_id' => $onboarding->department_id,
            'team_id' => $onboarding->team_id,
            'joining_date' => $onboarding->joining_date?->format('Y-m-d'),
            'designation_name' => $onboarding->designation?->name,
            'department_name' => $onboarding->department?->name,
            'team_name' => $onboarding->team?->name,
            'hr_agreement' => $onboarding->employee_payload['hr_agreement'] ?? [],
        ];

        return array_merge($employeeFields, $locked);
    }

    public function saveEmployeeProgress(EmployeeOnboarding $onboarding, array $payload, array $files = []): void
    {
        $documentPaths = $this->storeEmployeeFiles($onboarding, $files);
        $mergedPayload = array_merge($onboarding->employee_payload ?? [], $payload, ['documents' => $documentPaths]);

        $onboarding->update([
            'status' => EmployeeOnboarding::STATUS_IN_PROGRESS,
            'employee_payload' => $mergedPayload,
        ]);
    }

    public function submitByEmployee(EmployeeOnboarding $onboarding, array $payload, array $files = []): void
    {
        $documentPaths = $this->storeEmployeeFiles($onboarding, $files);
        $mergedPayload = array_merge($onboarding->employee_payload ?? [], $payload, ['documents' => $documentPaths]);
        $onboarding->markSubmitted($mergedPayload);
    }

    /**
     * @return array{onboarding: EmployeeOnboarding, profile: EmployeeProfile, zoho_error: string|null}
     */
    public function approveAndFinalize(EmployeeOnboarding $onboarding, int $approvedByUserId): array
    {
        if (! $onboarding->canApprove() && $onboarding->status !== EmployeeOnboarding::STATUS_APPROVED) {
            throw new RuntimeException('This onboarding is not ready for approval.');
        }

        $onboarding->loadMissing(['designation', 'department', 'team']);

        $onboarding = DB::transaction(function () use ($onboarding, $approvedByUserId) {
            $onboarding->refresh();
            if ($onboarding->status === EmployeeOnboarding::STATUS_SUBMITTED) {
                $onboarding->markApproved($approvedByUserId);
            }

            if ($onboarding->final_employee_profile_id) {
                return $onboarding->fresh();
            }

            $payload = $onboarding->employee_payload ?? [];
            $email = (string) ($payload['personal_email'] ?? $onboarding->email);
            $password = Str::random(12);

            $user = User::query()->create([
                'name' => (string) ($payload['full_name'] ?? $onboarding->full_name),
                'email' => $email,
                'codename' => $this->sanitizeCodename((string) ($payload['codename'] ?? '')),
                'password' => Hash::make($password),
                'role' => User::ROLE_EMPLOYEE,
            ]);

            $employeeType = (string) $onboarding->employee_type;
            $employeePrefix = $employeeType === EmployeeLifecycleService::TYPE_INTERN ? 'EXI' : 'EXE';
            $employeeId = $this->lifecycle->allocateNextEmployeeId($employeePrefix);

            $joiningDate = $onboarding->joining_date?->toDateString() ?? now()->toDateString();
            $internshipPeriod = $employeeType === EmployeeLifecycleService::TYPE_INTERN ? 3 : null;
            $probationPeriod = $employeeType === EmployeeLifecycleService::TYPE_PERMANENT_EMPLOYEE ? 3 : null;

            $ec1Name = $payload['emergency_contact_1_name'] ?? $payload['emergency_contact_name'] ?? null;
            $ec1Phone = $payload['emergency_contact_1_phone'] ?? $payload['emergency_contact_phone'] ?? null;
            $ec2Name = $payload['emergency_contact_2_name'] ?? null;
            $ec2Phone = $payload['emergency_contact_2_phone'] ?? null;

            $profile = EmployeeProfile::query()->create([
                'user_id' => $user->id,
                'employee_id' => $employeeId,
                'department_id' => $onboarding->department_id,
                'team_id' => $onboarding->team_id,
                'designation_id' => $onboarding->designation_id,
                'personal_email' => (string) ($payload['personal_email'] ?? $onboarding->email),
                'personal_mobile' => (string) ($payload['phone'] ?? $onboarding->phone),
                'official_email' => $email,
                'joining_date' => $joiningDate,
                'pan_card_path' => $this->documentPath($onboarding, 'pan_card'),
                'id_card_path' => $this->documentPath($onboarding, 'id_card'),
                'profile_image_path' => $this->documentPath($onboarding, 'profile_photo'),
                'bank_account_number' => (string) ($payload['bank_account_number'] ?? ''),
                'bank_ifsc_code' => (string) ($payload['bank_ifsc_code'] ?? ''),
                'bank_name' => (string) ($payload['bank_name'] ?? ''),
                'status' => 'active',
                'employee_type' => $employeeType,
                'employee_badge' => $employeeType === EmployeeLifecycleService::TYPE_INTERN
                    ? EmployeeLifecycleService::BADGE_INTERNSHIP_I
                    : EmployeeLifecycleService::BADGE_PROBATION_E,
                'internship_period_months' => $internshipPeriod,
                'internship_start_date' => $employeeType === EmployeeLifecycleService::TYPE_INTERN ? $joiningDate : null,
                'internship_end_date' => $employeeType === EmployeeLifecycleService::TYPE_INTERN
                    ? $this->lifecycle->computeInternshipEndDate($joiningDate, (int) $internshipPeriod)
                    : null,
                'probation_period_months' => $probationPeriod,
                'probation_start_date' => $employeeType === EmployeeLifecycleService::TYPE_PERMANENT_EMPLOYEE ? $joiningDate : null,
                'probation_end_date' => $employeeType === EmployeeLifecycleService::TYPE_PERMANENT_EMPLOYEE
                    ? $this->lifecycle->computeProbationEndDate($joiningDate, (int) $probationPeriod)
                    : null,
                'date_of_birth' => $payload['date_of_birth'] ?? null,
                'address' => $payload['address'] ?? null,
                'emergency_contact_name' => $ec1Name,
                'emergency_contact_phone' => $ec1Phone,
            ]);

            foreach ([
                1 => ['name' => $ec1Name, 'phone' => $ec1Phone],
                2 => ['name' => $ec2Name, 'phone' => $ec2Phone],
            ] as $slot => $c) {
                $has = trim((string) ($c['name'] ?? '')) !== '' || trim((string) ($c['phone'] ?? '')) !== '';
                if (! $has) {
                    EmployeeEmergencyContact::query()
                        ->where('employee_profile_id', $profile->id)
                        ->where('slot', $slot)
                        ->delete();

                    continue;
                }

                EmployeeEmergencyContact::query()->updateOrCreate(
                    ['employee_profile_id' => $profile->id, 'slot' => $slot],
                    [
                        'name' => $c['name'] !== null && trim((string) $c['name']) !== '' ? trim((string) $c['name']) : null,
                        'phone' => $c['phone'] !== null && trim((string) $c['phone']) !== '' ? trim((string) $c['phone']) : null,
                        'relation' => null,
                    ]
                );
            }

            $onboarding->update([
                'final_user_id' => $user->id,
                'final_employee_profile_id' => $profile->id,
            ]);

            CmsActivityLog::query()->create([
                'user_id' => $approvedByUserId,
                'user_email' => User::query()->whereKey($approvedByUserId)->value('email'),
                'action_key' => 'hrms.onboarding.finalized',
                'method' => 'POST',
                'route_name' => 'admin.hrms.onboardings.approve',
                'url' => url('/admin/hrms/onboardings/' . $onboarding->id . '/approve'),
                'ip_address' => request()?->ip(),
                'user_agent' => request()?->userAgent(),
                'status_code' => 200,
                'context' => ['onboarding_id' => $onboarding->id, 'employee_profile_id' => $profile->id],
            ]);

            return $onboarding->fresh();
        });

        $profile = EmployeeProfile::query()->whereKey($onboarding->final_employee_profile_id)->firstOrFail();

        $zohoError = null;
        try {
            $onboarding->load(['designation', 'department', 'team']);
            $zoho = $this->zohoSignService->sendEmploymentAgreement($onboarding, $profile);
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
        } catch (\Throwable $e) {
            report($e);
            $zohoError = $e->getMessage();
        }

        return [
            'onboarding' => $onboarding->fresh(),
            'profile' => $profile,
            'zoho_error' => $zohoError,
        ];
    }

    /**
     * @param array<string, UploadedFile|null> $files
     * @return array<string, string>
     */
    private function storeEmployeeFiles(EmployeeOnboarding $onboarding, array $files): array
    {
        $stored = [];
        foreach ($files as $key => $file) {
            if (! $file instanceof UploadedFile) {
                continue;
            }
            $path = $file->store('hrms/onboarding/' . $onboarding->id, 'public');
            $stored[$key] = $path;

            $titleMap = [
                'pan_card' => 'PAN card',
                'id_card' => 'ID proof',
                'profile_photo' => 'Profile photo',
                'experience_letter' => 'Previous company experience letter',
                'relieving_letter' => 'Relieving letter',
                'salary_slip_1' => 'Salary slip (month 1)',
                'salary_slip_2' => 'Salary slip (month 2)',
                'salary_slip_3' => 'Salary slip (month 3)',
            ];
            $title = $titleMap[$key] ?? str_replace('_', ' ', Str::title((string) $key));

            OnboardingDocument::query()->create([
                'employee_onboarding_id' => $onboarding->id,
                'doc_key' => (string) $key,
                'title' => $title,
                'file_path' => $path,
                'uploaded_at' => now(),
            ]);
        }

        return $stored;
    }

    private function documentPath(EmployeeOnboarding $onboarding, string $key): ?string
    {
        return $onboarding->documents()->where('doc_key', $key)->latest('id')->value('file_path');
    }

    private function sanitizeCodename(string $codename): ?string
    {
        $value = preg_replace('/[^A-Za-z]/', '', $codename) ?: '';

        return $value === '' ? null : $value;
    }
}

