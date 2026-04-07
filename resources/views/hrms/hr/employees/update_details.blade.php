@extends('hrms.layout')

@section('content')
    <div class="card">
        <div class="row" style="justify-content:space-between;align-items:flex-start;">
            <h1>Update Employee Details</h1>
            <a class="pill" href="{{ route('admin.hrms.employees.show', $employee) }}">Back</a>
        </div>
        <p class="muted" style="margin-top:6px;">
            Employee: <strong>{{ $employee->user->name }}</strong> ({{ $employee->employee_id }})
        </p>
    </div>

    <div class="card form-card" style="margin-top:14px;">
        <h1>Selective update</h1>
        <p class="muted">Choose one attribute, provide the new value, and update. Each change is logged.</p>

        <form method="post" action="{{ route('admin.hrms.employees.update_details.update', $employee) }}" class="form-wrap" enctype="multipart/form-data" style="margin-top:12px;">
            @csrf

            <div class="form-grid cols-2">
                <div class="field">
                    <label>Attribute</label>
                    <select name="field" id="field" required>
                        <option value="">Select…</option>
                        <option value="name">Name</option>
                        <option value="codename">Codename (login id)</option>
                        <option value="personal_email">Personal email</option>
                        <option value="personal_mobile">Personal mobile</option>
                        <option value="official_email">Official email</option>
                        <option value="department_id">Department</option>
                        <option value="team_id">Team</option>
                        <option value="team_ids">Teams (multiple)</option>
                        <option value="designation_id">Designation</option>
                        <option value="joining_date">Joining date</option>
                        <option value="bank_account_number">Bank account number</option>
                        <option value="bank_ifsc_code">IFSC code</option>
                        <option value="bank_name">Bank name</option>
                        <option value="is_remote">Remote employee</option>
                        <option value="reporting_manager_employee_profile_id">Reporting manager</option>
                        <option value="profile_image">Profile image (DP)</option>
                        <option value="signed_contract">Signed contract</option>
                        <option value="upload_document">Upload additional document</option>
                    </select>
                </div>

                <div class="field" id="value-wrap">
                    <label>New value</label>
                    <input name="value" id="value-input" value="">
                </div>

                <div class="field" id="file-wrap" style="display:none;">
                    <label>File</label>
                    <input type="file" name="file">
                </div>

                <div class="field" id="doc-title-wrap" style="display:none;">
                    <label>Document title</label>
                    <input name="doc_title" value="">
                </div>

                <div class="field" id="dept-wrap" style="display:none;">
                    <label>Department</label>
                    <select name="value_dept">
                        @foreach($departments as $d)
                            <option value="{{ $d->id }}">{{ $d->name }}</option>
                        @endforeach
                    </select>
                </div>

                <div class="field" id="team-wrap" style="display:none;">
                    <label>Team</label>
                    <select name="value_team">
                        <option value="">—</option>
                        @foreach($teams as $t)
                            <option value="{{ $t->id }}">{{ $t->name }}</option>
                        @endforeach
                    </select>
                </div>

                <div class="field" id="teams-wrap" style="display:none;">
                    <label>Teams (multiple)</label>
                    <div style="border:1px solid rgba(0,0,0,.10);border-radius:16px;padding:10px 12px;max-height:190px;overflow:auto;background:rgba(255,255,255,.92);box-shadow:0 1px 0 rgba(0,0,0,.04), inset 0 1px 0 rgba(255,255,255,.6);">
                        @foreach($teams as $t)
                            <label style="display:flex;align-items:flex-start;gap:10px;padding:8px 4px;cursor:pointer;margin:0;">
                                <input type="checkbox" name="value[]" value="{{ $t->id }}" style="width:auto;max-width:none;padding:0;border-radius:6px;box-shadow:none;background:transparent;margin-top:2px;">
                                <span style="line-height:1.25;">{{ $t->name }}</span>
                            </label>
                        @endforeach
                    </div>
                </div>

                <div class="field" id="des-wrap" style="display:none;">
                    <label>Designation</label>
                    <select name="value_des">
                        @foreach($designations as $des)
                            <option value="{{ $des->id }}">{{ $des->name }}</option>
                        @endforeach
                    </select>
                </div>

                <div class="field" id="mgr-wrap" style="display:none;">
                    <label>Reporting manager</label>
                    <select name="value_mgr">
                        <option value="">—</option>
                        @foreach($managers as $m)
                            <option value="{{ $m->id }}">{{ $m->employee_id }} — {{ $m->user?->name }}</option>
                        @endforeach
                    </select>
                </div>

                <div class="field" id="remote-wrap" style="display:none;">
                    <label>Remote employee</label>
                    <select name="value_remote">
                        <option value="0">No</option>
                        <option value="1">Yes</option>
                    </select>
                </div>

                <div class="field" id="date-wrap" style="display:none;">
                    <label>Date</label>
                    <input type="date" name="value_date">
                </div>
            </div>

            <button class="btn" type="submit" style="margin-top:10px;">Update</button>
        </form>
    </div>

    @push('scripts')
        <script>
            (function () {
                var field = document.getElementById('field');
                var valueWrap = document.getElementById('value-wrap');
                var valueInput = document.getElementById('value-input');
                var fileWrap = document.getElementById('file-wrap');
                var docTitleWrap = document.getElementById('doc-title-wrap');
                var deptWrap = document.getElementById('dept-wrap');
                var teamWrap = document.getElementById('team-wrap');
                var teamsWrap = document.getElementById('teams-wrap');
                var desWrap = document.getElementById('des-wrap');
                var mgrWrap = document.getElementById('mgr-wrap');
                var remoteWrap = document.getElementById('remote-wrap');
                var dateWrap = document.getElementById('date-wrap');

                function hideAll() {
                    valueWrap.style.display = '';
                    fileWrap.style.display = 'none';
                    docTitleWrap.style.display = 'none';
                    deptWrap.style.display = 'none';
                    teamWrap.style.display = 'none';
                    teamsWrap.style.display = 'none';
                    desWrap.style.display = 'none';
                    mgrWrap.style.display = 'none';
                    remoteWrap.style.display = 'none';
                    dateWrap.style.display = 'none';
                    valueInput.name = 'value';
                    valueInput.disabled = false;
                }

                function onChange() {
                    hideAll();
                    var v = field.value;
                    function useSelect(wrap, sel) {
                        valueWrap.style.display = 'none';
                        valueInput.disabled = true;
                        wrap.style.display = '';
                        sel.setAttribute('name', 'value');
                    }
                    if (v === 'department_id') { useSelect(deptWrap, deptWrap.querySelector('select')); }
                    else if (v === 'team_id') { useSelect(teamWrap, teamWrap.querySelector('select')); }
                    else if (v === 'team_ids') {
                        valueWrap.style.display = 'none';
                        valueInput.disabled = true;
                        teamsWrap.style.display = '';
                    }
                    else if (v === 'designation_id') { useSelect(desWrap, desWrap.querySelector('select')); }
                    else if (v === 'joining_date') {
                        valueWrap.style.display = 'none';
                        valueInput.disabled = true;
                        dateWrap.style.display = '';
                        dateWrap.querySelector('input').setAttribute('name', 'value');
                    }
                    else if (v === 'reporting_manager_employee_profile_id') { useSelect(mgrWrap, mgrWrap.querySelector('select')); }
                    else if (v === 'is_remote') { useSelect(remoteWrap, remoteWrap.querySelector('select')); }
                    else if (v === 'profile_image' || v === 'signed_contract') { valueWrap.style.display = 'none'; valueInput.disabled = true; fileWrap.style.display = ''; }
                    else if (v === 'upload_document') { valueWrap.style.display = 'none'; valueInput.disabled = true; fileWrap.style.display = ''; docTitleWrap.style.display = ''; }
                }

                if (field) {
                    field.addEventListener('change', onChange);
                    onChange();
                }
            })();
        </script>
    @endpush

    <div class="card" style="margin-top:14px;">
        <h1>Employee Log</h1>
        @if(($logs ?? collect())->isEmpty())
            <p class="muted">No changes recorded yet.</p>
        @else
            <div class="table-wrap">
                <table>
                    <thead>
                    <tr>
                        <th>Date</th>
                        <th>Field</th>
                        <th>From</th>
                        <th>To</th>
                        <th>By</th>
                    </tr>
                    </thead>
                    <tbody>
                    @foreach($logs as $log)
                        <tr>
                            <td class="muted">{{ optional($log->changed_at)->format('Y-m-d H:i') }}</td>
                            <td><strong>{{ $log->field }}</strong></td>
                            <td class="muted">{{ $log->meta['old_label'] ?? $log->old_value ?? '—' }}</td>
                            <td class="muted">{{ $log->meta['new_label'] ?? $log->new_value ?? '—' }}</td>
                            <td class="muted">{{ $log->changedBy?->name ?? '—' }}</td>
                        </tr>
                    @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </div>
@endsection

