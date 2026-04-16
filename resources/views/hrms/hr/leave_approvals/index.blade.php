@extends('hrms.layout')

@section('content')
    <div class="card">
        <h1>Pending leave approvals</h1>
        <p class="muted">Allocate each working day in the range to leave types (e.g. 2 EL + 3 UL). Sum must equal calendar working days.</p>

        @error('alloc')
            <p style="color:#c00;margin-bottom:10px;">{{ $message }}</p>
        @enderror

        @if($requests->isEmpty())
            <p class="muted">No pending requests.</p>
        @else
            <table>
                <thead>
                <tr>
                    <th>Employee</th>
                    <th>Requested</th>
                    <th>Dates</th>
                    <th>Working days</th>
                    <th>Reason</th>
                    <th>Approve (days per type)</th>
                    <th></th>
                </tr>
                </thead>
                <tbody>
                @foreach($requests as $r)
                    <tr>
                        <td><strong>{{ $r->employeeProfile->employee_id }}</strong> — {{ $r->employeeProfile->user->name }}</td>
                        <td class="muted">{{ $r->policy->code }}</td>
                        <td class="muted">{{ $r->start_date->format('Y-m-d') }} → {{ $r->end_date->format('Y-m-d') }}</td>
                        <td><strong>{{ $r->working_days_expected }}</strong></td>
                        <td class="muted">{{ Str::limit($r->reason ?? '—', 40) }}</td>
                        <td>
                            <form method="post" action="{{ route('admin.hrms.leave_approvals.approve', $r) }}" class="row" style="flex-wrap:wrap;gap:6px;align-items:flex-end;">
                                @csrf
                                @foreach($policies as $p)
                                    <div class="field" style="margin:0;">
                                        <label style="font-size:11px;">{{ $p->code }}</label>
                                        <input type="number" name="alloc[{{ $p->id }}]" min="0" step="0.5"
                                               value="{{ old('alloc')[$p->id] ?? ($p->id == $r->leave_policy_id ? $r->working_days_expected : 0) }}"
                                               style="width:52px;">
                                    </div>
                                @endforeach
                                <button class="btn" type="submit">Approve</button>
                            </form>
                        </td>
                        <td>
                            <form method="post" action="{{ route('admin.hrms.leave_approvals.reject', $r) }}">
                                @csrf
                                <button class="btn danger" type="submit">Reject</button>
                            </form>
                        </td>
                    </tr>
                @endforeach
                </tbody>
            </table>
        @endif
    </div>
@endsection
