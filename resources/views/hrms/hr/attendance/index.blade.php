@extends('hrms.layout')

@section('content')
    <style>
        .attendance-kpis {
            grid-template-columns: repeat(5, minmax(0, 1fr));
        }
        .attendance-status {
            display: flex;
            flex-wrap: wrap;
            gap: 6px;
            align-items: center;
        }
        .attendance-badge {
            display: inline-block;
            padding: 3px 8px;
            border-radius: 999px;
            font-weight: 600;
            line-height: 1.1;
            white-space: nowrap;
        }
        .attendance-badge.less-hours {
            background: rgba(255,77,61,.12);
            border: 1px solid rgba(255,77,61,.24);
        }
        .attendance-badge.leave {
            background: rgba(34,197,94,.12);
            border: 1px solid rgba(34,197,94,.28);
        }
        .attendance-badge.half-day {
            background: rgba(59,130,246,.12);
            border: 1px solid rgba(59,130,246,.28);
        }
        .attendance-badge.absent {
            background: rgba(220,38,38,.12);
            border: 1px solid rgba(220,38,38,.28);
        }
        @media (max-width: 980px) {
            .attendance-kpis {
                grid-template-columns: repeat(2, minmax(0, 1fr));
            }
        }
        @media (max-width: 640px) {
            .attendance-kpis {
                grid-template-columns: 1fr;
            }
        }
        .attendance-kpi-present {
            border: 1px solid rgba(16, 185, 129, 0.4);
            background: linear-gradient(165deg, rgba(236, 253, 245, 0.95) 0%, #fff 55%);
            box-shadow: 0 6px 18px rgba(15, 23, 42, 0.07);
        }
        .attendance-kpi-present h2 {
            color: #065f46;
            font-size: 13px;
            letter-spacing: 0.04em;
            text-transform: uppercase;
        }
        .attendance-kpi-present .kpi {
            color: #047857;
            font-size: 28px;
        }
    </style>

    <div class="card">
        <div class="row" style="justify-content:space-between;align-items:flex-start;">
            <h1>Attendance</h1>
            <a class="pill" href="{{ route('admin.hrms.dashboard') }}">Back to HRMS</a>
        </div>

        <form method="get" class="row" style="gap:10px;align-items:flex-end;margin-top:10px;">
            <div class="field" style="margin:0;">
                <label>Date</label>
                <input type="date" name="date" value="{{ $date }}">
            </div>
            <div class="field" style="margin:0;min-width:260px;">
                <label>Employee</label>
                <input type="text" name="employee" value="{{ $employeeFilter }}" placeholder="Employee ID or name">
            </div>
            <button class="btn" type="submit">Filter</button>
            <a class="pill" href="{{ route('admin.hrms.attendance.index') }}">Reset</a>
        </form>
    </div>

    <div class="grid attendance-kpis" style="margin-top:14px;">
        <div class="card attendance-kpi-present"><h2>Working Day (9h+)</h2><div class="kpi">{{ $presentCount }}</div></div>
        <div class="card"><h2>Insufficient Working Hours</h2><div class="kpi">{{ $insufficientHoursCount }}</div></div>
        <div class="card"><h2>Half Day</h2><div class="kpi">{{ $halfDayCount }}</div></div>
        <div class="card"><h2>Employee Leave</h2><div class="kpi">{{ $employeeLeaveCount }}</div></div>
        <div class="card"><h2>Absent (Unpaid)</h2><div class="kpi">{{ $absentCount }}</div></div>
    </div>

    <div class="card" style="margin-top:14px;">
        <h2>Daily Punch Log</h2>
        <div class="table-wrap" style="margin-top:8px;">
            <table>
                <thead>
                <tr>
                    <th>Employee</th>
                    <th>Punch In</th>
                    <th>Punch Out</th>
                    <th>Premise Time</th>
                    <th>Status</th>
                </tr>
                </thead>
                <tbody>
                @forelse($rows as $row)
                    @php($attendance = $row['attendance'])
                    <tr>
                        <td><strong>{{ $row['employee']->employee_id }}</strong> — {{ $row['employee']->user?->name }}</td>
                        <td class="muted">{{ optional($attendance?->punch_in_at)->format('Y-m-d H:i:s') ?? '—' }}</td>
                        <td class="muted">{{ optional($attendance?->punch_out_at)->format('Y-m-d H:i:s') ?? '—' }}</td>
                        <td class="muted">
                            @if(!is_null($row['worked_minutes']))
                                {{ floor($row['worked_minutes'] / 60) }}h {{ $row['worked_minutes'] % 60 }}m
                            @else
                                —
                            @endif
                        </td>
                        <td class="attendance-status">
                            @if($row['status'] === 'present')
                                <strong>Working Day</strong>
                            @elseif($row['status'] === 'insufficient_hours')
                                <span class="attendance-badge less-hours">
                                    Insufficient Working Hours
                                </span>
                            @elseif($row['status'] === 'official_leave')
                                <span class="attendance-badge leave">Official Leave</span>
                            @elseif($row['status'] === 'employee_leave')
                                <span class="attendance-badge leave">Employee Leave</span>
                            @elseif($row['status'] === 'half_day')
                                <span class="attendance-badge half-day">Half Day</span>
                            @else
                                <span class="attendance-badge absent">Absent</span>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="5" class="muted">No employees found.</td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
    </div>
@endsection

