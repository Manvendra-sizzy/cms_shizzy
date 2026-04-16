@extends('hrms.layout')

@section('content')
    <div class="card form-card">
        <h1>Employment agreement</h1>
        <p class="muted">Edit the agreement text used when generating the PDF for onboarding and Zoho Sign. Use placeholders below; they are replaced per employee when the PDF is built.</p>

        @if(session('status'))
            <p class="muted" style="color:var(--ok);margin-top:8px;">{{ session('status') }}</p>
        @endif

        @if(!$content)
            <p class="muted" style="margin-top:12px;">No agreement text is stored yet. Run <code>php artisan db:seed --class=EmploymentAgreementContentSeeder</code> to import <code>employee_agreement_content.txt</code>, then refresh this page.</p>
        @endif

        <form method="post" action="{{ route('admin.hrms.employment_agreement.update') }}" class="form-wrap" style="margin-top:14px;">
            @csrf
            @method('PUT')
            <div class="field">
                <label for="body_html">Agreement (HTML)</label>
                <textarea id="body_html" name="body_html" rows="24" class="tinymce-agreement">{{ old('body_html', $content?->body_html ?? '') }}</textarea>
                @error('body_html')
                    <p class="muted" style="color:var(--danger);margin-top:6px;">{{ $message }}</p>
                @enderror
            </div>
            <div class="card" style="margin-top:12px;padding:14px 16px;background:var(--surface-soft);">
                <strong style="font-size:14px;">Template variables</strong>
                <p class="muted" style="margin:8px 0 0;font-size:13px;">Type or paste these exactly (double curly braces). They are filled when the PDF is generated from onboarding data.</p>
                <div class="table-wrap" style="margin-top:10px;font-size:13px;">
                    <table>
                        <thead>
                        <tr>
                            <th>Variable</th>
                            <th>Source</th>
                        </tr>
                        </thead>
                        <tbody>
                        <tr>
                            <td><code>@{{recipient_name}}</code> · <code>@{{recipient-name}}</code> · <code>@{{Recipient Name}}</code></td>
                            <td class="muted">Employee / signer name</td>
                        </tr>
                        <tr>
                            <td><code>@{{recipient_email}}</code> · <code>@{{recipient-email}}</code> · <code>@{{Recipient Email}}</code></td>
                            <td class="muted">Personal email (onboarding email)</td>
                        </tr>
                        <tr>
                            <td><code>@{{employee-name}}</code> <span class="muted">(or</span> <code>@{{employee_name}}</code><span class="muted">)</span></td>
                            <td class="muted">Full name</td>
                        </tr>
                        <tr>
                            <td><code>@{{employee-address}}</code></td>
                            <td class="muted">Address from form / profile</td>
                        </tr>
                        <tr>
                            <td><code>@{{employee-designation}}</code> <span class="muted">(or</span> <code>@{{designation}}</code><span class="muted">)</span></td>
                            <td class="muted">Designation</td>
                        </tr>
                        <tr>
                            <td><code>@{{employee-joining-date}}</code> <span class="muted">(or</span> <code>@{{joining_date}}</code><span class="muted">)</span></td>
                            <td class="muted">Joining date</td>
                        </tr>
                        <tr>
                            <td><code>@{{employee-basic-salary}}</code> <span class="muted">(or</span> <code>@{{basic_salary}}</code><span class="muted">)</span></td>
                            <td class="muted">HR agreement: basic salary</td>
                        </tr>
                        <tr>
                            <td><code>@{{employee-50%-of-salary}}</code> <span class="muted">(or</span> <code>@{{employee-25%-of-salary}}</code>, <code>@{{salary_25_percent}}</code><span class="muted">)</span></td>
                            <td class="muted">HRA (50% of basic; legacy keys still work)</td>
                        </tr>
                        <tr>
                            <td><code>@{{employee-other-allowance}}</code></td>
                            <td class="muted">Other allowance</td>
                        </tr>
                        <tr>
                            <td><code>@{{employee-gross-salary}}</code></td>
                            <td class="muted">Gross salary</td>
                        </tr>
                        <tr>
                            <td><code>@{{department}}</code>, <code>@{{team}}</code>, <code>@{{agreement_date}}</code></td>
                            <td class="muted">Department, team, agreement date (HR)</td>
                        </tr>
                        </tbody>
                    </table>
                </div>
            </div>
            <button class="btn" type="submit" style="margin-top:14px;">Save agreement</button>
        </form>
    </div>
@endsection

@push('scripts')
    <script src="https://cdn.jsdelivr.net/npm/tinymce@7/tinymce.min.js"></script>
    <script>
        tinymce.init({
            selector: 'textarea.tinymce-agreement',
            height: 560,
            menubar: 'edit view insert format tools table',
            plugins: 'lists link table code autoresize',
            toolbar: 'undo redo | blocks | bold italic underline | alignleft aligncenter alignright | bullist numlist | link table | code',
            license_key: 'gpl',
            branding: false,
            promotion: false,
            convert_urls: false,
        });
    </script>
@endpush
