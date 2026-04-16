@extends('hrms.layout')

@section('content')
    <div class="card form-card">
        <div class="row" style="justify-content:space-between;">
            <h1>Onboarding #{{ $onboarding->id }}</h1>
            <a class="pill" href="{{ route('admin.hrms.onboardings.index') }}">Back</a>
        </div>

        <div class="grid cols-3" style="margin-top:12px;">
            <div class="card"><strong>{{ $onboarding->full_name }}</strong><div class="muted">{{ $onboarding->email }}</div></div>
            <div class="card"><strong>Status</strong><div>{{ ucwords(str_replace('_',' ', $onboarding->status)) }}</div></div>
            <div class="card"><strong>Zoho Sign</strong>
                <div>{{ $onboarding->zoho_sign_status ?: '—' }}</div>
                @if($onboarding->zoho_sign_request_id)
                    <div class="muted" style="margin-top:6px;font-size:11px;">Request ID: {{ $onboarding->zoho_sign_request_id }}</div>
                @endif
            </div>
        </div>

        <div class="card" style="margin-top:12px;">
            <h2>Inbuilt contract signing</h2>
            <table><tbody>
                <tr><td>Contract status</td><td>{{ $onboarding->contract_status ? ucwords(str_replace('_', ' ', $onboarding->contract_status)) : '—' }}</td></tr>
                <tr><td>Link sent at</td><td>{{ optional($onboarding->contract_sent_at)->format('Y-m-d H:i') ?? '—' }}</td></tr>
                <tr><td>Opened at</td><td>{{ optional($onboarding->contract_opened_at)->format('Y-m-d H:i') ?? '—' }}</td></tr>
                <tr><td>Agreed at</td><td>{{ optional($onboarding->contract_agreed_at)->format('Y-m-d H:i') ?? '—' }}</td></tr>
                <tr><td>Signed at</td><td>{{ optional($onboarding->contract_signed_at)->format('Y-m-d H:i') ?? '—' }}</td></tr>
                <tr><td>Signed PDF</td><td>{{ $onboarding->contract_signed_pdf_path ? 'Stored (public)' : '—' }}</td></tr>
                <tr><td>Document hash</td><td style="word-break:break-all;">{{ $onboarding->contract_document_hash ?: '—' }}</td></tr>
            </tbody></table>
        </div>

        <div class="card" style="margin-top:12px;">
            <h2>HR-set details (locked for candidate)</h2>
            @php($payload = $onboarding->employee_payload ?? [])
            <table><tbody>
                <tr><td>Full name</td><td>{{ $onboarding->full_name }}</td></tr>
                <tr><td>Personal email</td><td>{{ $onboarding->email }}</td></tr>
                <tr><td>Phone</td><td>{{ $onboarding->phone ?: '—' }}</td></tr>
                <tr><td>Employee type</td><td>{{ \App\Services\HRMS\EmployeeLifecycleService::employeeTypeLabels()[$onboarding->employee_type] ?? $onboarding->employee_type }}</td></tr>
                <tr><td>Department</td><td>{{ $onboarding->department?->name ?? '—' }}</td></tr>
                <tr><td>Designation</td><td>{{ $onboarding->designation?->name ?? '—' }}</td></tr>
                <tr><td>Team</td><td>{{ $onboarding->team?->name ?? '—' }}</td></tr>
                <tr><td>Joining date</td><td>{{ optional($onboarding->joining_date)->format('Y-m-d') ?? '—' }}</td></tr>
                <tr><td>Address (prefill)</td><td>{{ $payload['address'] ?? '—' }}</td></tr>
                <tr><td>Agreement date (contract)</td><td>{{ ($payload['hr_agreement']['agreement_made_date'] ?? null) ? \Illuminate\Support\Carbon::parse($payload['hr_agreement']['agreement_made_date'])->format('Y-m-d') : '— (optional)' }}</td></tr>
                @php($hrA = $payload['hr_agreement'] ?? [])
                @php($hasGrossFromHr = isset($hrA['gross_salary']) && is_numeric($hrA['gross_salary']) && (float) $hrA['gross_salary'] > 0)
                <tr><td>Gross salary (monthly)</td><td>{{ isset($hrA['gross_salary']) ? number_format((float) $hrA['gross_salary'], 2) : '—' }}</td></tr>
                <tr><td>Basic (50% of gross)</td><td>{{ isset($hrA['basic_salary']) ? number_format((float) $hrA['basic_salary'], 2) : '—' }}</td></tr>
                <tr><td>HRA (50% of basic)</td><td>{{ isset($hrA['salary_25_percent']) ? number_format((float) $hrA['salary_25_percent'], 2) : '—' }}</td></tr>
                <tr><td>Other allowance (balance)</td><td>{{ isset($hrA['other_allowance']) ? number_format((float) $hrA['other_allowance'], 2) : '—' }}</td></tr>
            </tbody></table>
        </div>

        @if($onboarding->hrCanEditAgreementPrefill())
            <div class="card" style="margin-top:12px;">
                <h2>Employment agreement (PDF + Zoho Sign)</h2>
                <p class="muted" style="margin:0 0 14px;line-height:1.5;">Required before <strong>Approve &amp; finalize</strong> or <strong>Send agreement</strong>. Values are merged into the generated employment agreement PDF; the candidate signs with their personal email via Zoho Sign.</p>
                <form method="post" action="{{ route('admin.hrms.onboardings.agreement_details', $onboarding) }}" id="onboarding-agreement-form">
                    @csrf
                    <div class="form-grid cols-2" style="gap:12px;">
                        <div class="field">
                            <label for="agreement_made_date">Agreement made date (optional)</label>
                            <input id="agreement_made_date" name="agreement_made_date" type="date" value="{{ old('agreement_made_date', isset($payload['hr_agreement']['agreement_made_date']) ? \Illuminate\Support\Carbon::parse($payload['hr_agreement']['agreement_made_date'])->format('Y-m-d') : '') }}">
                        </div>
                        @if(! $hasGrossFromHr)
                            <div class="field" style="grid-column:1 / -1;">
                                <label for="gross_salary_edit">Gross salary (monthly) *</label>
                                <input id="gross_salary_edit" name="gross_salary" type="text" inputmode="decimal" required value="{{ old('gross_salary', $hrA['gross_salary'] ?? '') }}">
                            </div>
                        @endif
                    </div>
                    <div class="card" style="margin-top:10px;padding:12px 14px;background:rgba(248,250,252,0.95);">
                        <strong style="font-size:13px;">Calculated from gross (payroll-aligned)</strong>
                        @if($hasGrossFromHr)
                            <div class="muted" style="margin-top:8px;font-size:13px;line-height:1.65;">
                                Gross (monthly): <strong>{{ number_format((float) $hrA['gross_salary'], 2) }}</strong><br>
                                Basic (50%): <strong>{{ isset($hrA['basic_salary']) ? number_format((float) $hrA['basic_salary'], 2) : '—' }}</strong><br>
                                HRA (50% of basic): <strong>{{ isset($hrA['salary_25_percent']) ? number_format((float) $hrA['salary_25_percent'], 2) : '—' }}</strong><br>
                                Other allowance (balance): <strong>{{ isset($hrA['other_allowance']) ? number_format((float) $hrA['other_allowance'], 2) : '—' }}</strong>
                            </div>
                        @else
                            <div id="salary-breakdown-preview-edit" class="muted" style="margin-top:8px;font-size:13px;line-height:1.5;"></div>
                        @endif
                    </div>
                    <button class="btn" type="submit" style="margin-top:8px;">Save agreement details</button>
                </form>
            </div>
        @endif

        <div class="card" style="margin-top:12px;">
            <h2>Candidate-submitted details</h2>
            <table><tbody>
                <tr><td>DOB</td><td>{{ $payload['date_of_birth'] ?? '—' }}</td></tr>
                <tr><td>Address</td><td>{{ $payload['address'] ?? '—' }}</td></tr>
                <tr><td>Emergency contact 1</td><td>{{ ($payload['emergency_contact_1_name'] ?? $payload['emergency_contact_name'] ?? '—') }} · {{ ($payload['emergency_contact_1_phone'] ?? $payload['emergency_contact_phone'] ?? '—') }}</td></tr>
                <tr><td>Emergency contact 2</td><td>{{ ($payload['emergency_contact_2_name'] ?? '—') }} · {{ ($payload['emergency_contact_2_phone'] ?? '—') }}</td></tr>
                <tr><td>Bank</td><td>{{ ($payload['bank_name'] ?? '—') }} / {{ ($payload['bank_account_number'] ?? '—') }}</td></tr>
            </tbody></table>
        </div>

        <div class="card" style="margin-top:12px;">
            <h2>Documents</h2>
            @forelse($onboarding->documents as $doc)
                <div class="row" style="justify-content:space-between;margin:6px 0;">
                    <span>{{ $doc->title }}</span>
                    <a class="pill" href="{{ route('files.public', ['path' => ltrim($doc->file_path, '/')]) }}" target="_blank">Open</a>
                </div>
            @empty
                <p class="muted">No documents uploaded yet.</p>
            @endforelse
        </div>

        <div class="row" style="gap:10px;margin-top:12px;flex-wrap:wrap;">
            <form method="post" action="{{ route('admin.hrms.onboardings.resend_link', $onboarding) }}">@csrf <button class="pill" type="submit">Send/Resend Link</button></form>
            @if($onboarding->status === \App\Modules\HRMS\Onboarding\Models\EmployeeOnboarding::STATUS_SUBMITTED)
                <form method="post" action="{{ route('admin.hrms.onboardings.approve', $onboarding) }}">@csrf <button class="btn" type="submit">Approve & Finalize</button></form>
            @endif
            @if($onboarding->canSendAgreement())
                <form method="post" action="{{ route('admin.hrms.onboardings.send_inbuilt_contract', $onboarding) }}">@csrf <button class="btn" type="submit">Send Inbuilt Contract Link</button></form>
            @endif
            @if($onboarding->canSendAgreement())
                <form method="post" action="{{ route('admin.hrms.onboardings.send_agreement', $onboarding) }}">@csrf <button class="btn" type="submit">Send Agreement (Zoho Sign)</button></form>
            @endif
            @if($onboarding->contract_signed_pdf_path)
                <a class="pill" href="{{ route('admin.hrms.onboardings.inbuilt_signed_contract', $onboarding) }}">Download inbuilt signed contract</a>
            @endif
            @if($onboarding->zoho_sign_request_id)
                <form method="post" action="{{ route('admin.hrms.onboardings.sync_zoho', $onboarding) }}">@csrf <button class="pill" type="submit">Sync Zoho status</button></form>
                <a class="pill" href="{{ route('admin.hrms.onboardings.signed_agreement', $onboarding) }}">Download signed PDF</a>
            @endif
        </div>

        @if($onboarding->contractEvidenceLogs->isNotEmpty())
            <div class="card" style="margin-top:12px;">
                <h2>Contract evidence log (hash chain)</h2>
                <div class="table-wrap">
                    <table>
                        <thead>
                        <tr>
                            <th>ID</th>
                            <th>Event</th>
                            <th>Created</th>
                            <th>IP</th>
                            <th>Event Hash</th>
                            <th>Previous Hash</th>
                        </tr>
                        </thead>
                        <tbody>
                        @foreach($onboarding->contractEvidenceLogs->sortByDesc('id') as $e)
                            <tr>
                                <td>{{ $e->id }}</td>
                                <td>{{ $e->event_type }}</td>
                                <td>{{ optional($e->created_at)->format('Y-m-d H:i:s') }}</td>
                                <td>{{ $e->ip_address ?: '—' }}</td>
                                <td style="word-break:break-all;">{{ $e->event_hash }}</td>
                                <td style="word-break:break-all;">{{ $e->previous_hash ?: '—' }}</td>
                            </tr>
                        @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        @endif

        @if($onboarding->zoho_sign_request_id)
            <div class="card" style="margin-top:12px;">
                <h2>Zoho Sign details</h2>
                <table><tbody>
                    <tr><td>Request ID</td><td>{{ $onboarding->zoho_sign_request_id }}</td></tr>
                    <tr><td>Status</td><td>{{ $onboarding->zoho_sign_status ?: '—' }}</td></tr>
                    <tr><td>Sent at</td><td>{{ optional($onboarding->zoho_sign_sent_at)->format('Y-m-d H:i') ?? '—' }}</td></tr>
                    <tr><td>Completed at</td><td>{{ optional($onboarding->zoho_sign_completed_at)->format('Y-m-d H:i') ?? '—' }}</td></tr>
                    <tr><td>Generated agreement PDF</td><td>{{ $onboarding->zoho_sign_agreement_pdf_path ? 'Stored (local)' : '—' }}</td></tr>
                    <tr><td>Signed PDF</td><td>{{ $onboarding->zoho_sign_signed_pdf_path ? 'Stored (public)' : '—' }}</td></tr>
                </tbody></table>
            </div>
        @endif

        @if($onboarding->status === \App\Modules\HRMS\Onboarding\Models\EmployeeOnboarding::STATUS_SUBMITTED)
            <div class="card" style="margin-top:16px;border-color:#fecaca;background:linear-gradient(165deg,#ffffff 0%,#fff5f5 100%);box-shadow:0 4px 18px rgba(220,38,38,0.07);">
                <h2 style="color:#991b1b;border-bottom-color:#fecaca;">Reject onboarding</h2>
                <p class="muted" style="margin:0 0 16px;line-height:1.5;">If this submission cannot be approved, record a clear reason below. The onboarding will be marked as rejected and the candidate will be notified by email at their personal address.</p>
                <form method="post" action="{{ route('admin.hrms.onboardings.reject', $onboarding) }}">
                    @csrf
                    <div class="field" style="margin-bottom:0;">
                        <label for="hr_notes_reject">Rejection reason</label>
                        <textarea id="hr_notes_reject" name="hr_notes" rows="5" placeholder="Explain why this onboarding is being rejected…" required maxlength="2000">{{ old('hr_notes') }}</textarea>
                    </div>
                    <div style="margin-top:14px;">
                        <button class="btn danger" type="submit">Reject onboarding</button>
                    </div>
                </form>
            </div>
        @endif

        @if($onboarding->finalEmployeeProfile)
            <div class="card" style="margin-top:12px;">
                Final employee created:
                <a class="pill" href="{{ route('admin.hrms.employees.show', $onboarding->finalEmployeeProfile) }}">{{ $onboarding->finalEmployeeProfile->employee_id }}</a>
            </div>
        @endif
    </div>
    <script>
        (function () {
            const input = document.getElementById('gross_salary_edit');
            const out = document.getElementById('salary-breakdown-preview-edit');
            if (!input || !out) return;
            function fmt(n) {
                return (Math.round(n * 100) / 100).toLocaleString(undefined, { minimumFractionDigits: 2, maximumFractionDigits: 2 });
            }
            function render() {
                const g = parseFloat(String(input.value).replace(/,/g, '')) || 0;
                if (g <= 0) {
                    out.textContent = 'Enter gross salary to see basic, HRA, and other allowance.';
                    return;
                }
                const basic = Math.round(g * 0.5 * 100) / 100;
                const hra = Math.round(basic * 0.5 * 100) / 100;
                const other = Math.round(Math.max(0, g - basic - hra) * 100) / 100;
                out.innerHTML = 'Basic (50%): <strong>' + fmt(basic) + '</strong><br>' +
                    'HRA (50% of basic): <strong>' + fmt(hra) + '</strong><br>' +
                    'Other allowance (balance): <strong>' + fmt(other) + '</strong>';
            }
            input.addEventListener('input', render);
            input.addEventListener('change', render);
            render();
        })();
    </script>
@endsection

