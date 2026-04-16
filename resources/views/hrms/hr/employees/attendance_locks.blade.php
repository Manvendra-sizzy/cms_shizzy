@extends('hrms.layout')

@section('content')
    <div class="card">
        <div class="row" style="justify-content:space-between;align-items:flex-start;">
            <h1>Attendance Locks</h1>
            <a class="pill" href="{{ route('admin.hrms.dashboard') }}">Back to HRMS</a>
        </div>
        <p class="muted">Employees locked due to 3+ missed punch-outs in the current month.</p>

        @if($employees->isEmpty())
            <p class="muted" style="margin-top:12px;">No locked employees.</p>
        @else
            <div class="table-wrap" style="margin-top:12px;">
                <table>
                    <thead>
                    <tr>
                        <th>Employee</th>
                        <th>Locked At</th>
                        <th>Reason</th>
                        <th>Unlock</th>
                    </tr>
                    </thead>
                    <tbody>
                    @foreach($employees as $employee)
                        <tr>
                            <td><strong>{{ $employee->employee_id }}</strong> — {{ $employee->user?->name }}</td>
                            <td class="muted">{{ optional($employee->attendance_locked_at)->format('Y-m-d H:i:s') ?? '—' }}</td>
                            <td class="muted">{{ $employee->attendance_lock_reason ?? '—' }}</td>
                            <td>
                                <form method="post" action="{{ route('admin.hrms.employees.attendance_locks.unlock', $employee) }}" class="row" style="gap:8px;align-items:center;">
                                    @csrf
                                    <input type="text" name="unlock_note" placeholder="Optional note" style="width:180px;">
                                    <button class="pill" type="submit">Unlock</button>
                                </form>
                            </td>
                        </tr>
                    @endforeach
                    </tbody>
                </table>
            </div>
            <div style="margin-top:12px;">{{ $employees->links() }}</div>
        @endif
    </div>
@endsection

