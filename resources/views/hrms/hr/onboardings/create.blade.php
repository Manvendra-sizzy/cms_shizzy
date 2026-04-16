@extends('hrms.layout')

@section('content')
    <style>
        .onboarding-create-card { max-width: 1200px; }
        .onboarding-create-grid {
            display: grid;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: 12px;
            align-items: start;
        }
        .onboarding-span-2 { grid-column: 1 / -1; }
        @media (max-width: 900px) {
            .onboarding-create-grid { grid-template-columns: 1fr; }
            .onboarding-span-2 { grid-column: auto; }
        }
    </style>
    <div class="card form-card onboarding-create-card">
        <h1>Start Employee Onboarding</h1>
        <p class="muted">Enter role, org, and compensation (these are fixed for the candidate). Gross salary uses the same split as payroll slips: basic 50%, HRA 50% of basic, remainder other allowance. A secure link will be emailed for them to complete the rest.</p>

        <form method="post" action="{{ route('admin.hrms.onboardings.store') }}" style="margin-top:12px;" id="onboarding-create-form">
            @csrf
            <div class="onboarding-create-grid">
                <div class="field"><label>Full name *</label><input name="full_name" value="{{ old('full_name') }}" required></div>
                <div class="field"><label>Personal email *</label><input name="personal_email" type="email" value="{{ old('personal_email') }}" required title="Used for Zoho Sign and notifications"></div>
                <div class="field"><label>Phone</label><input name="phone" value="{{ old('phone') }}"></div>
                <div class="field">
                    <label>Employee type *</label>
                    <select name="employee_type" required>
                        @foreach($employeeTypes as $k => $v)
                            <option value="{{ $k }}" @selected(old('employee_type')===$k)>{{ $v }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="field">
                    <label>Department *</label>
                    <select name="department_id" required><option value="">Select</option>@foreach($departments as $d)<option value="{{ $d->id }}" @selected((string)old('department_id')===(string)$d->id)>{{ $d->name }}</option>@endforeach</select>
                </div>
                <div class="field">
                    <label>Designation *</label>
                    <select name="designation_id" required><option value="">Select</option>@foreach($designations as $d)<option value="{{ $d->id }}" @selected((string)old('designation_id')===(string)$d->id)>{{ $d->name }}</option>@endforeach</select>
                </div>
                <div class="field">
                    <label>Team</label>
                    <select name="team_id"><option value="">Select</option>@foreach($teams as $t)<option value="{{ $t->id }}" @selected((string)old('team_id')===(string)$t->id)>{{ $t->name }}</option>@endforeach</select>
                </div>
                <div class="field">
                    <label>Joining date *</label>
                    <input name="joining_date" type="date" value="{{ old('joining_date') }}" required>
                </div>
                <div class="field">
                    <label>Gross salary (monthly) *</label>
                    <input name="gross_salary" id="gross_salary" type="text" inputmode="decimal" value="{{ old('gross_salary') }}" required placeholder="e.g. 100000">
                </div>
                <div class="field">
                    <label>Agreement date (for employment contract)</label>
                    <input name="agreement_made_date" type="date" value="{{ old('agreement_made_date') }}" title="Printed on the agreement as [DATE]; defaults to today if left blank on PDF.">
                </div>
                <div class="field onboarding-span-2">
                    <label>Address (optional)</label>
                    <textarea name="address" rows="3" placeholder="Prefill for the agreement if known; the candidate will confirm on the onboarding form.">{{ old('address') }}</textarea>
                </div>
                <div class="card onboarding-span-2" style="margin-top:0;padding:12px 14px;background:rgba(248,250,252,0.95);">
                    <strong style="font-size:13px;">Calculated from gross (payroll-aligned)</strong>
                    <div id="salary-breakdown-preview" class="muted" style="margin-top:8px;font-size:13px;line-height:1.5;"></div>
                </div>
                <label class="row onboarding-span-2" style="gap:8px;margin-top:0;"><input type="checkbox" name="send_now" value="1" checked> Send onboarding link immediately</label>
            </div>
            <div style="margin-top:12px;"><button class="btn" type="submit">Create onboarding</button></div>
        </form>
    </div>
    @push('scripts')
    <script>
        (function () {
            const input = document.getElementById('gross_salary');
            const out = document.getElementById('salary-breakdown-preview');
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
                    'Other allowance (balance): <strong>' + fmt(other) + '</strong><br>' +
                    'Gross check: <strong>' + fmt(basic + hra + other) + '</strong>';
            }
            input.addEventListener('input', render);
            input.addEventListener('change', render);
            render();
        })();
    </script>
    @endpush
@endsection
