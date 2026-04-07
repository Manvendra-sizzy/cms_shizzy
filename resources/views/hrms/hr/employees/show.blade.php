@extends('hrms.layout')

@section('content')
    <div class="card">
        <div class="row" style="gap:12px;align-items:center;">
            <div style="width:54px;height:54px;border-radius:999px;overflow:hidden;border:1px solid rgba(0,0,0,.08);background:#fff;display:flex;align-items:center;justify-content:center;">
                @php($photoSrc = !empty($employee->profile_image_path) ? route('files.public', ['path' => ltrim($employee->profile_image_path, '/')]) . '?v=' . (optional($employee->updated_at)->timestamp ?? time()) : 'https://shizzy.in/images/shizzy-logo-icon.png')
                <img src="{{ $photoSrc }}" alt="Profile" style="width:54px;height:54px;object-fit:cover;">
            </div>
            <div>
                <h1 style="margin:0;">{{ $employee->user->name }} <span class="muted">({{ $employee->employee_id }})</span></h1>
                <div class="muted" style="margin-top:4px;">{{ $employee->user->email }}</div>
                <div class="muted" style="margin-top:2px;">Codename: <strong>{{ $employee->user->codename ?? '—' }}</strong></div>
            </div>
        </div>

        <div class="row" style="margin-top:12px;gap:18px;">
            <div><span class="muted">Department</span><div><strong>{{ $employee->orgDepartment?->name ?? '—' }}</strong></div></div>
            <div>
                <span class="muted">Teams</span>
                <div>
                    <strong>
                        @php($teamNames = ($employee->orgTeams ?? collect())->pluck('name')->filter()->values())
                        {{ $teamNames->isEmpty() ? '—' : $teamNames->implode(', ') }}
                    </strong>
                </div>
            </div>
            <div><span class="muted">Designation</span><div><strong>{{ $employee->orgDesignation?->name ?? '—' }}</strong></div></div>
            <div><span class="muted">Status</span><div><strong>{{ $employee->status }}</strong></div></div>
            <div><span class="muted">Remote</span><div><strong>{{ $employee->is_remote ? 'Yes' : 'No' }}</strong></div></div>
        </div>

        <div style="margin-top:14px;" class="row">
            <a class="pill" href="{{ route('admin.hrms.employees.status.show', $employee) }}">Change Employee Status</a>
            <a class="pill" href="{{ route('admin.hrms.employees.salary.show', $employee) }}">Salary</a>
            <a class="pill" href="{{ route('admin.hrms.employees.emergency_contacts.edit', $employee) }}">Emergency Contact</a>
            <form method="post" action="{{ route('admin.hrms.employees.password.reset', $employee) }}" style="display:inline;">
                @csrf
                <button class="pill" type="submit" onclick="return confirm('Reset this employee\\'s password? A new temporary password will be generated.');">
                    Reset login password
                </button>
            </form>

            <a class="pill" href="{{ route('admin.hrms.documents.create') }}">Issue Document</a>
            <a class="pill" href="{{ route('admin.hrms.leave_approvals.index') }}">Leave Approvals</a>
            <a class="pill" href="{{ route('admin.hrms.employees.salary_slips.index', $employee) }}">Salary Slips</a>
            <a class="pill" href="{{ route('admin.hrms.employees.update_details.index', $employee) }}">Update Details</a>
        </div>
    </div>

    <div class="card" style="margin-top:14px;">
        <h1>Monthly attendance &amp; leave summary</h1>
        <form method="get" class="row" style="gap:10px;margin-bottom:14px;">
            <select name="summary_month">
                @for($m = 1; $m <= 12; $m++)
                    <option value="{{ $m }}" @selected($summaryMonth == $m)>{{ date('F', mktime(0,0,0,$m,1)) }}</option>
                @endfor
            </select>
            <input type="number" name="summary_year" value="{{ $summaryYear }}" min="2020" max="2035" style="width:90px;">
            <button class="pill" type="submit">Show</button>
        </form>
        <table>
            <tbody>
            <tr><td>Working days</td><td><strong>{{ $monthlySummary['working_days'] }}</strong></td></tr>
            <tr><td>Present</td><td><strong>{{ $monthlySummary['present_days'] }}</strong></td></tr>
            <tr><td>Paid leave</td><td><strong>{{ $monthlySummary['paid_leave_days'] }}</strong></td></tr>
            <tr><td>Unpaid leave</td><td><strong>{{ $monthlySummary['unpaid_leave_days'] }}</strong></td></tr>
            <tr><td>LOP days</td><td><strong>{{ $monthlySummary['lop_days'] }}</strong></td></tr>
            </tbody>
        </table>
        @if(!empty($monthlySummary['leave_breakdown']))
            <p class="muted" style="margin-top:10px;">Paid leave by type:
                @foreach($monthlySummary['leave_breakdown'] as $code => $d)
                    {{ $code }}: {{ $d }}@if(!$loop->last), @endif
                @endforeach
            </p>
        @endif
    </div>

    <div class="card" style="margin-top:14px;">
        <h1>Leave balance (this year)</h1>
        @if(($balances ?? collect())->isEmpty())
            <p class="muted">No leave policies configured yet.</p>
        @else
            <table>
                <thead>
                <tr>
                    <th>Leave type</th>
                    <th>Allowance</th>
                    <th>Utilized</th>
                    <th>Remaining</th>
                </tr>
                </thead>
                <tbody>
                @foreach($balances as $b)
                    <tr>
                        <td><strong>{{ $b['policy']->code }}</strong> <span class="muted">{{ $b['policy']->name }}</span></td>
                        <td class="muted">{{ $b['allowance'] ?? '—' }}</td>
                        <td class="muted">{{ $b['used'] }}</td>
                        <td><strong>{{ $b['remaining'] ?? '—' }}</strong></td>
                    </tr>
                @endforeach
                </tbody>
            </table>
        @endif
    </div>

    <div class="card" style="margin-top:14px;">
        <div class="row" style="justify-content:space-between;align-items:flex-start;">
            <h1>Documents</h1>
        </div>

        <h2>Issued documents</h2>
        @if(($documents ?? collect())->isEmpty())
            <p class="muted">No issued documents.</p>
        @else
            <div class="table-wrap">
                <table>
                    <thead>
                    <tr>
                        <th>ID</th>
                        <th>Type</th>
                        <th>Title</th>
                        <th>Issued</th>
                        <th></th>
                    </tr>
                    </thead>
                    <tbody>
                    @foreach($documents as $doc)
                        <tr>
                            <td class="muted">{{ $doc->id }}</td>
                            <td><strong>{{ str_replace('_', ' ', ucwords($doc->type, '_')) }}</strong></td>
                            <td class="muted">{{ $doc->title }}</td>
                            <td class="muted">{{ optional($doc->issued_at)->format('Y-m-d') ?? '—' }}</td>
                            <td><a class="pill" href="{{ route('admin.hrms.documents.download', $doc) }}">Download</a></td>
                        </tr>
                    @endforeach
                    </tbody>
                </table>
            </div>
        @endif

        <h2 style="margin-top:14px;">Profile documents</h2>
        <div class="row">
            @if(!empty($employee->pan_card_path))
                <a class="pill" href="{{ route('files.public', ['path' => ltrim($employee->pan_card_path, '/')]) }}" target="_blank" rel="noopener">View PAN</a>
            @endif
            @if(!empty($employee->id_card_path))
                <a class="pill" href="{{ route('files.public', ['path' => ltrim($employee->id_card_path, '/')]) }}" target="_blank" rel="noopener">View ID</a>
            @endif
            @if(!empty($employee->signed_contract_path))
                <a class="pill" href="{{ route('files.public', ['path' => ltrim($employee->signed_contract_path, '/')]) }}" target="_blank" rel="noopener">View Signed Contract</a>
            @endif
        </div>

        <h2 style="margin-top:14px;">Additional documents</h2>
        <form method="post" action="{{ route('admin.hrms.employees.uploaded_documents.store', $employee) }}" enctype="multipart/form-data" class="form-wrap" style="margin-top:10px;">
            @csrf
            <div class="form-grid cols-2">
                <div class="field">
                    <label>Title</label>
                    <input name="title" required>
                </div>
                <div class="field">
                    <label>File</label>
                    <input type="file" name="file" required>
                </div>
            </div>
            <button class="btn" type="submit">Upload</button>
        </form>

        @if(($uploadedDocs ?? collect())->isNotEmpty())
            <div class="table-wrap" style="margin-top:12px;">
                <table>
                    <thead><tr><th>Title</th><th>Uploaded</th><th></th></tr></thead>
                    <tbody>
                    @foreach($uploadedDocs as $ud)
                        <tr>
                            <td><strong>{{ $ud->title }}</strong></td>
                            <td class="muted">{{ optional($ud->uploaded_at)->format('Y-m-d') ?? '—' }}</td>
                            <td><a class="pill" href="{{ route('admin.hrms.employees.uploaded_documents.download', $ud) }}">Download</a></td>
                        </tr>
                    @endforeach
                    </tbody>
                </table>
            </div>
        @else
            <p class="muted">No additional documents uploaded.</p>
        @endif
    </div>

    <div class="grid" style="grid-template-columns:1fr 1fr;gap:14px;margin-top:14px;">
        <div class="card">
            <h1>Projects</h1>
            @if(($projects ?? collect())->isEmpty())
                <p class="muted">No projects assigned.</p>
            @else
                <table>
                    <thead>
                    <tr>
                        <th>Project</th>
                        <th>Client</th>
                        <th>Role</th>
                        <th>Status</th>
                    </tr>
                    </thead>
                    <tbody>
                    @foreach($projects as $pt)
                        <tr>
                            <td>
                                <strong>{{ $pt->project?->project_code }}</strong>
                                <div class="muted">{{ $pt->project?->name }}</div>
                            </td>
                            <td class="muted">{{ $pt->project?->client?->name ?? '—' }}</td>
                            <td class="muted">{{ $pt->role_title ?? '—' }}</td>
                            <td><strong>{{ $pt->project?->status ?? '—' }}</strong></td>
                        </tr>
                    @endforeach
                    </tbody>
                </table>
            @endif
        </div>

        <div class="card">
            <h1>Assets</h1>
            @if(($assets ?? collect())->isEmpty())
                <p class="muted">No assets currently assigned.</p>
            @else
                <table>
                    <thead>
                    <tr>
                        <th>Asset</th>
                        <th>Category</th>
                        <th>Code / Serial</th>
                        <th>Assigned on</th>
                    </tr>
                    </thead>
                    <tbody>
                    @foreach($assets as $aa)
                        <tr>
                            <td>
                                <strong>{{ $aa->asset?->name }}</strong>
                                <div class="muted">{{ $aa->asset?->condition }}</div>
                            </td>
                            <td class="muted">{{ $aa->asset?->category?->name ?? '—' }}</td>
                            <td class="muted">
                                {{ $aa->asset?->asset_code ?? '—' }}
                                @if($aa->asset?->serial_number)
                                    <br><span class="muted">SN: {{ $aa->asset->serial_number }}</span>
                                @endif
                            </td>
                            <td class="muted">{{ optional($aa->assigned_at)->format('Y-m-d') ?? '—' }}</td>
                        </tr>
                    @endforeach
                    </tbody>
                </table>
            @endif
        </div>
    </div>

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
            <div style="margin-top:12px;">{{ $logs->links() }}</div>
        @endif
    </div>
@endsection

