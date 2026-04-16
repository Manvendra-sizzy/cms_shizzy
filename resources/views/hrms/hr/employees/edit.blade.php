@extends('hrms.layout')

@section('content')
    <div class="card form-card">
        <div class="row" style="justify-content:space-between;">
            <h1>Edit employee</h1>
            <a class="pill" href="{{ route('admin.hrms.employees.show', $employee) }}">Back</a>
        </div>

        <form method="post" action="{{ route('admin.hrms.employees.update', $employee) }}" enctype="multipart/form-data" class="form-wrap" style="margin-top:12px;">
            @csrf
            @method('PUT')
            <div class="form-grid cols-2">
                <div class="field">
                    <label>Name</label>
                    <input name="name" value="{{ old('name', $employee->user->name) }}" required>
                </div>
                <div class="field">
                    <label>Personal email address</label>
                    <input name="personal_email" type="email" value="{{ old('personal_email', $employee->personal_email) }}" required>
                </div>
                <div class="field">
                    <label>Personal mobile number</label>
                    <input name="personal_mobile" value="{{ old('personal_mobile', $employee->personal_mobile) }}" required>
                </div>
                <div class="field">
                    <label>Official email address</label>
                    <input name="official_email" type="email" value="{{ old('official_email', $employee->official_email) }}" required>
                </div>
                <div class="field">
                    <label>Joining date</label>
                    <input name="joining_date" type="date" value="{{ old('joining_date', optional($employee->joining_date)->toDateString()) }}" required>
                </div>
                <div class="field">
                    <label>Department</label>
                    <select name="department_id" required>
                        <option value="">Select…</option>
                        @foreach($departments as $department)
                            <option value="{{ $department->id }}" @selected(old('department_id', $employee->department_id)==$department->id)>{{ $department->code }} - {{ $department->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="field">
                    <label>Team</label>
                    <select name="team_id">
                        <option value="">Select…</option>
                        @foreach($teams as $team)
                            <option value="{{ $team->id }}" @selected(old('team_id', $employee->team_id)==$team->id)>{{ $team->code }} - {{ $team->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="field">
                    <label>Designation</label>
                    <select name="designation_id" required>
                        <option value="">Select…</option>
                        @foreach($designations as $designation)
                            <option value="{{ $designation->id }}" @selected(old('designation_id', $employee->designation_id)==$designation->id)>{{ $designation->code }} - {{ $designation->name }}</option>
                        @endforeach
                    </select>
                </div>
            </div>

            <div class="card" style="margin-top:14px;">
                <h2>Status</h2>
                @php $currentStatus = old('status', $employee->status ?? 'active'); @endphp
                <div class="field">
                    <label>Employee status</label>
                    <select name="status" required>
                        <option value="active" @selected($currentStatus==='active')>Active</option>
                        <option value="inactive" @selected($currentStatus==='inactive')>Inactive</option>
                        <option value="former" @selected($currentStatus==='former')>Former Employee</option>
                    </select>
                </div>
                <div class="form-grid cols-2">
                    <div class="field">
                        <label>Inactive date (required when setting Inactive)</label>
                        <input name="inactive_at" type="date" value="{{ old('inactive_at', optional($employee->inactive_at)->toDateString()) }}">
                    </div>
                    <div class="field">
                        <label>Inactive remarks</label>
                        <textarea name="inactive_remarks">{{ old('inactive_remarks', $employee->inactive_remarks) }}</textarea>
                    </div>
                    <div class="field">
                        <label>Separation type (Resigned / Terminated / Retired)</label>
                        <select name="separation_type">
                            <option value="">None</option>
                            @foreach(['resigned','terminated','retired'] as $type)
                                <option value="{{ $type }}" @selected(old('separation_type', $employee->separation_type)===$type)>{{ ucfirst($type) }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="field">
                        <label>Separation effective date</label>
                        <input name="separation_effective_at" type="date" value="{{ old('separation_effective_at', optional($employee->separation_effective_at)->toDateString()) }}">
                    </div>
                    <div class="field" style="grid-column:1 / -1;">
                        <label>Separation remarks</label>
                        <textarea name="separation_remarks">{{ old('separation_remarks', $employee->separation_remarks) }}</textarea>
                    </div>
                </div>
                <p class="muted" style="margin-top:6px;">
                    Active → Inactive requires inactive date and remarks. Active/Inactive → Former Employee requires type, effective date, and remarks.
                    Former Employee status cannot be changed.
                </p>
            </div>

            <button class="btn" type="submit" style="margin-top:14px;">Save changes</button>
        </form>
    </div>
@endsection

