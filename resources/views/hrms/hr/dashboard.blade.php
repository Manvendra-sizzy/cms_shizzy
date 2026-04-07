@extends('hrms.layout')

@section('content')
    @php
        $totalEmp = max(0, (int) ($employeesCount ?? 0));
        $present = (int) ($presentEmployeesToday ?? 0);
        $pct = $totalEmp > 0 ? min(100, round(($present / $totalEmp) * 100)) : 0;
    @endphp
    <style>
        .hr-att-snap {
            margin-bottom: 14px;
            border-radius: 16px;
            overflow: hidden;
            border: 1px solid rgba(15, 23, 42, 0.08);
            background: linear-gradient(125deg, #0f172a 0%, #1e3a5f 42%, #0f766e 100%);
            box-shadow: 0 10px 28px rgba(15, 23, 42, 0.18);
        }
        .hr-att-snap-inner {
            display: flex;
            flex-wrap: wrap;
            align-items: stretch;
            justify-content: space-between;
            gap: 16px;
            padding: 16px 18px;
            color: #fff;
        }
        .hr-att-snap-main { flex: 1; min-width: 220px; }
        .hr-att-snap-kicker {
            font-size: 11px;
            font-weight: 700;
            letter-spacing: 0.12em;
            text-transform: uppercase;
            color: rgba(255, 255, 255, 0.72);
            margin: 0 0 6px;
        }
        .hr-att-snap-row {
            display: flex;
            flex-wrap: wrap;
            align-items: baseline;
            gap: 10px 14px;
        }
        .hr-att-snap-num {
            font-size: 42px;
            font-weight: 800;
            line-height: 1;
            letter-spacing: -0.03em;
            font-family: var(--font-accent, inherit);
        }
        .hr-att-snap-word {
            font-size: 15px;
            font-weight: 600;
            color: rgba(255, 255, 255, 0.92);
        }
        .hr-att-snap-date {
            margin: 8px 0 0;
            font-size: 13px;
            color: rgba(255, 255, 255, 0.78);
        }
        .hr-att-snap-bar-outer {
            margin-top: 12px;
            height: 8px;
            border-radius: 999px;
            background: rgba(255, 255, 255, 0.18);
            overflow: hidden;
        }
        .hr-att-snap-bar-inner {
            height: 100%;
            border-radius: 999px;
            background: linear-gradient(90deg, #5eead4, #34d399);
            box-shadow: 0 0 12px rgba(52, 211, 153, 0.45);
            transition: width 0.35s ease;
        }
        .hr-att-snap-foot {
            margin: 8px 0 0;
            font-size: 12px;
            color: rgba(255, 255, 255, 0.7);
        }
        .hr-att-snap-aside {
            display: flex;
            align-items: center;
        }
        .hr-att-snap-cta.pill {
            background: rgba(255, 255, 255, 0.95);
            border-color: rgba(255, 255, 255, 0.95);
            color: #0f172a;
            font-weight: 600;
        }
        .hr-att-snap-cta.pill:hover {
            background: #fff;
            border-color: #fff;
        }
        @media (max-width: 560px) {
            .hr-att-snap-num { font-size: 34px; }
        }
    </style>
    <div class="hr-att-snap">
        <div class="hr-att-snap-inner">
            <div class="hr-att-snap-main">
                <p class="hr-att-snap-kicker">Today · attendance</p>
                <div class="hr-att-snap-row">
                    <span class="hr-att-snap-num">{{ $present }}</span>
                    <span class="hr-att-snap-word">employees punched in</span>
                </div>
                <p class="hr-att-snap-date">{{ now()->format('l, j M Y') }}</p>
                @if($totalEmp > 0)
                    <div class="hr-att-snap-bar-outer" role="img" aria-label="{{ $pct }} percent of employees present">
                        <div class="hr-att-snap-bar-inner" style="width: {{ $pct }}%;"></div>
                    </div>
                    <p class="hr-att-snap-foot">{{ $present }} of {{ $totalEmp }} employee profile(s) · {{ $pct }}% punched in today</p>
                @else
                    <p class="hr-att-snap-foot">No employee profiles yet — add staff to track attendance here.</p>
                @endif
            </div>
            <div class="hr-att-snap-aside">
                <a class="pill hr-att-snap-cta" href="{{ route('admin.hrms.attendance.index') }}">Open attendance</a>
            </div>
        </div>
    </div>

    <div class="grid cols-3" style="grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));">
        <div class="card">
            <h2>Employees</h2>
            <div class="kpi">{{ $employeesCount }}</div>
            <div style="margin-top:10px;"><a class="pill" href="{{ route('admin.hrms.employees.index') }}">Manage</a></div>
        </div>
        <div class="card">
            <h2>Pending leave requests</h2>
            <div class="kpi">{{ $pendingLeavesCount }}</div>
            <div style="margin-top:10px;"><a class="pill" href="{{ route('admin.hrms.leave_approvals.index') }}">Review</a></div>
        </div>
        <div class="card">
            <h2>Pending reimbursements</h2>
            <div class="kpi">{{ $pendingReimbursementsCount }}</div>
            <div style="margin-top:10px;"><a class="pill" href="{{ route('admin.hrms.reimbursement_approvals.index') }}">Review</a></div>
        </div>
        <div class="card">
            <h2>Payroll</h2>
            <div class="kpi">{{ $payrollRunsCount }}</div>
            <div style="margin-top:10px;">
                <a class="pill" href="{{ route('admin.hrms.payroll.index') }}">Open</a>
            </div>
        </div>
    </div>

    <div class="card" style="margin-top:14px;">
        <h1>Quick links</h1>
        <div class="row">
            <a class="pill" href="{{ route('admin.hrms.documents.index') }}">Documents</a>
            <a class="pill" href="{{ route('admin.hrms.leave_policies.index') }}">Leave Policies</a>
            <a class="pill" href="{{ route('admin.hrms.reimbursement_approvals.index') }}">Reimbursement approvals</a>
            <a class="pill" href="{{ route('admin.hrms.policies_guidelines.index') }}">Policies &amp; Guidelines</a>
            <a class="pill" href="{{ route('admin.hrms.calendar.index') }}">Calendar</a>
            <a class="pill" href="{{ route('admin.hrms.payroll.index') }}">Payroll</a>
            <a class="pill" href="{{ route('admin.hrms.employees.create') }}">Add Employee</a>
            <a class="pill" href="{{ route('admin.hrms.attendance.index') }}">Attendance</a>
            <a class="pill" href="{{ route('admin.hrms.employees.attendance_locks.index') }}">Attendance Locks</a>
            <a class="pill" href="{{ route('admin.hrms.attendance_adjustments.index') }}">Attendance Adjustment</a>
        </div>
    </div>
@endsection

