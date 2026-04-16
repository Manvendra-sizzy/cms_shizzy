@extends('hrms.layout')

@section('content')
    <div class="card form-card">
        <h1>Add employee</h1>
        <p class="muted">If you leave password blank, a temporary one will be generated. Employee IDs start at EXE001.</p>

        <form method="post" action="{{ route('admin.hrms.employees.store') }}" enctype="multipart/form-data" class="form-wrap">
            @csrf
            <div class="form-grid cols-2">
                <div class="field">
                    <label>Profile image (DP) *</label>
                    <input name="profile_image" type="file" accept="image/*" required>
                </div>
                <div class="field">
                    <label>Name</label>
                    <input name="name" value="{{ old('name') }}" required>
                </div>
                <div class="field">
                    <label>Employee type</label>
                    <div style="display:flex;gap:12px;flex-wrap:wrap;">
                        @foreach($employeeTypes as $typeKey => $typeLabel)
                            <label style="display:flex;align-items:center;gap:8px;padding:8px 12px;border:1px solid rgba(0,0,0,.12);border-radius:12px;background:#fff;">
                                <input type="radio" name="employee_type" value="{{ $typeKey }}" @checked(old('employee_type', 'intern') === $typeKey) style="width:auto;">
                                <span>{{ $typeLabel }}</span>
                            </label>
                        @endforeach
                    </div>
                </div>
                <div class="field">
                    <label>Personal email address</label>
                    <input name="personal_email" type="email" value="{{ old('personal_email') }}" required>
                </div>
                <div class="field">
                    <label>Personal mobile number</label>
                    <input name="personal_mobile" value="{{ old('personal_mobile') }}" required>
                </div>
                <div class="field">
                    <label>Official email address</label>
                    <input name="official_email" type="email" value="{{ old('official_email') }}" required>
                </div>
                <div class="field">
                    <label>Password (optional)</label>
                    <input name="password" type="text" value="{{ old('password') }}">
                </div>
                <div class="field" id="internship-period-wrap">
                    <label>Internship period</label>
                    <select name="internship_period_months">
                        <option value="">Select…</option>
                        @foreach($internshipPeriods as $period)
                            <option value="{{ $period }}" @selected((int) old('internship_period_months') === (int) $period)>{{ $period }} month{{ $period > 1 ? 's' : '' }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="field" id="probation-period-wrap">
                    <label>Probation period (months)</label>
                    <input name="probation_period_months" type="number" min="1" max="36" value="{{ old('probation_period_months') }}">
                </div>
                <div class="field">
                    <label>Joining date</label>
                    <input name="joining_date" type="date" value="{{ old('joining_date') }}" required>
                </div>
                <div class="field">
                    <label>Department</label>
                    <select name="department_id" required>
                        <option value="">Select…</option>
                        @foreach($departments as $department)
                            <option value="{{ $department->id }}" @selected(old('department_id')==$department->id)>{{ $department->code }} - {{ $department->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="field">
                    <label>Teams</label>
                    @php($oldTeamIds = collect(old('team_ids', []))->map(fn($v) => (int) $v)->all())
                    <div style="border:1px solid rgba(0,0,0,.10);border-radius:16px;padding:10px 12px;max-height:190px;overflow:auto;background:rgba(255,255,255,.92);box-shadow:0 1px 0 rgba(0,0,0,.04), inset 0 1px 0 rgba(255,255,255,.6);">
                        @foreach($teams as $team)
                            <label style="display:flex;align-items:flex-start;gap:10px;padding:8px 4px;cursor:pointer;margin:0;">
                                <input
                                    type="checkbox"
                                    name="team_ids[]"
                                    value="{{ $team->id }}"
                                    @checked(in_array($team->id, $oldTeamIds, true))
                                    style="width:auto;max-width:none;padding:0;border-radius:6px;box-shadow:none;background:transparent;margin-top:2px;"
                                >
                                <span style="line-height:1.25;">
                                    <strong style="font-weight:600;">{{ $team->code }}</strong>
                                    <span class="muted">— {{ $team->name }}</span>
                                </span>
                            </label>
                        @endforeach
                    </div>
                </div>
                <div class="field">
                    <label>Designation</label>
                    <select name="designation_id" required>
                        <option value="">Select…</option>
                        @foreach($designations as $designation)
                            <option value="{{ $designation->id }}" @selected(old('designation_id')==$designation->id)>{{ $designation->code }} - {{ $designation->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="field">
                    <label>PAN card upload</label>
                    <input name="pan_card" type="file" required>
                </div>
                <div class="field">
                    <label>ID card upload</label>
                    <input name="id_card" type="file" required>
                </div>
                <div class="field">
                    <label>Signed contract (optional)</label>
                    <input name="signed_contract" type="file">
                </div>
                <div class="field">
                    <label>Bank account number</label>
                    <input name="bank_account_number" value="{{ old('bank_account_number') }}" required>
                </div>
                <div class="field">
                    <label>IFSC code</label>
                    <input name="bank_ifsc_code" value="{{ old('bank_ifsc_code') }}" required>
                </div>
                <div class="field">
                    <label>Bank name</label>
                    <input name="bank_name" value="{{ old('bank_name') }}" required>
                </div>
                <div class="field">
                    <label>Salary (monthly)</label>
                    <input name="salary" type="number" step="0.01" min="0" value="{{ old('salary') }}" required>
                </div>
            </div>
            <button class="btn" type="submit">Create</button>
        </form>
    </div>
@endsection

@push('scripts')
<script>
    (function () {
        const internWrap = document.getElementById('internship-period-wrap');
        const probationWrap = document.getElementById('probation-period-wrap');
        const internSelect = internWrap ? internWrap.querySelector('select') : null;
        const probationInput = probationWrap ? probationWrap.querySelector('input') : null;

        function selectedType() {
            const checked = document.querySelector('input[name="employee_type"]:checked');
            return checked ? checked.value : '';
        }

        function syncLifecycleFields() {
            const type = selectedType();
            const isIntern = type === 'intern';
            if (internWrap) {
                internWrap.style.display = isIntern ? '' : 'none';
            }
            if (probationWrap) {
                probationWrap.style.display = isIntern ? 'none' : '';
            }
            if (internSelect) {
                internSelect.disabled = !isIntern;
            }
            if (probationInput) {
                probationInput.disabled = isIntern;
            }
        }

        document.querySelectorAll('input[name="employee_type"]').forEach(function (radio) {
            radio.addEventListener('change', syncLifecycleFields);
        });
        syncLifecycleFields();
    })();
</script>
@endpush

