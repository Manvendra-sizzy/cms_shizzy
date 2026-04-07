@extends('hrms.layout')

@section('content')
    <div class="card form-card">
        <h1>Assign Role to Employee</h1>
        <p class="muted">Select an existing employee and assign a business role. No duplicate user creation is needed.</p>

        <form method="post" action="{{ route('admin.users.store') }}">
            @csrf
            <div class="form-grid cols-2">
                <div class="field">
                    <label>Employee *</label>
                    <select name="employee_profile_id" required>
                        <option value="">Select employee</option>
                        @foreach($employees as $employee)
                            <option value="{{ $employee->id }}" @selected((string) old('employee_profile_id') === (string) $employee->id)>
                                {{ $employee->employee_id }} - {{ $employee->user?->name }} ({{ $employee->user?->email }})
                            </option>
                        @endforeach
                    </select>
                </div>
                <div class="field">
                    <label>Role *</label>
                    <select name="role_key" id="role_key" required>
                        <option value="">Select role</option>
                        @foreach($roles as $role)
                            <option value="{{ $role->key }}" @selected(old('role_key') === $role->key)>{{ $role->name }}</option>
                        @endforeach
                    </select>
                </div>
            </div>

            <div class="card" id="developer_scope_card" style="margin-top:12px; display:none;">
                <h2>Developer System Scope</h2>
                <label style="display:flex;gap:8px;align-items:center; margin-bottom:10px;">
                    <input type="checkbox" name="all_systems" id="all_systems" value="1" @checked(old('all_systems')) style="width:auto;">
                    Assign all systems
                </label>
                <div class="field">
                    <label for="system_ids">Assign chosen systems</label>
                    <select name="system_ids[]" id="system_ids" multiple size="10">
                        @foreach($systems as $system)
                            <option value="{{ $system->id }}" @selected(in_array($system->id, array_map('intval', old('system_ids', [])), true))>
                                {{ $system->system_name }}
                            </option>
                        @endforeach
                    </select>
                    <p class="muted">Choose specific systems if “Assign all systems” is unchecked.</p>
                </div>
            </div>

            <div class="field" style="margin-top:12px;">
                <label style="display:flex;gap:8px;align-items:center;">
                    <input type="checkbox" name="active" value="1" @checked(old('active', true)) style="width:auto;">
                    Active assignment
                </label>
            </div>

            <div style="margin-top:12px;">
                <button class="btn" type="submit">Assign Role</button>
            </div>
        </form>
    </div>
@endsection

@push('scripts')
    <script>
        (function () {
            const roleSelect = document.getElementById('role_key');
            const devCard = document.getElementById('developer_scope_card');
            const allSystems = document.getElementById('all_systems');
            const systemSelect = document.getElementById('system_ids');

            function refreshDeveloperScope() {
                const isDeveloper = roleSelect && roleSelect.value === 'developer';
                devCard.style.display = isDeveloper ? 'block' : 'none';
                const disableSystems = !isDeveloper || (allSystems && allSystems.checked);
                systemSelect.disabled = disableSystems;
            }

            if (roleSelect) roleSelect.addEventListener('change', refreshDeveloperScope);
            if (allSystems) allSystems.addEventListener('change', refreshDeveloperScope);
            refreshDeveloperScope();
        })();
    </script>
@endpush

