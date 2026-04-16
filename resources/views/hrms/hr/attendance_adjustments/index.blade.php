@extends('hrms.layout')

@section('content')
    <style>
        .adj-cal {
            display: grid;
            grid-template-columns: repeat(7, minmax(0, 1fr));
            gap: 8px;
            margin-top: 10px;
        }
        .adj-cal-head {
            font-size: 11px;
            font-weight: 700;
            color: #64748b;
            text-transform: uppercase;
            letter-spacing: .35px;
            padding: 6px 4px;
            text-align: center;
        }
        .adj-day {
            border: 1px solid #dbe3f1;
            border-radius: 10px;
            min-height: 62px;
            padding: 6px;
            background: #fff;
            display: flex;
            flex-direction: column;
            gap: 6px;
        }
        .adj-day.out {
            opacity: .38;
            background: #f8fafc;
        }
        .adj-day.off {
            background: #f8fafc;
            border-style: dashed;
        }
        .adj-day-top {
            font-size: 12px;
            font-weight: 600;
            color: #334155;
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 6px;
        }
        .adj-chip {
            font-size: 10px;
            color: #64748b;
            border: 1px solid #dbe3f1;
            border-radius: 999px;
            padding: 1px 6px;
            background: #fff;
        }
        .adj-day.off .adj-chip {
            border-color: #d1d5db;
            color: #6b7280;
            background: #f3f4f6;
        }
        .adj-check {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            font-size: 12px;
            color: #0f172a;
        }
        .adj-check input[type="checkbox"] {
            width: auto;
            margin: 0;
        }
    </style>

    @php
        $singleStatus = old('status', 'present');
        $bulkStatus = old('bulk_status', 'present');
        $dayNames = ['Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat', 'Sun'];
    @endphp

    <div class="card form-card">
        <h1>Attendance Adjustment</h1>
        <p class="muted">Use this to mark an employee as present, absent, or on a specific leave type for a date (manual correction).</p>

        <form method="post" action="{{ route('admin.hrms.attendance_adjustments.store') }}" class="form-wrap" style="margin-top:12px;">
            @csrf

            <div class="form-grid cols-2">
                <div class="field">
                    <label>Employee</label>
                    <select name="employee_profile_id" required>
                        <option value="">Select…</option>
                        @foreach($employees as $emp)
                            <option value="{{ $emp->id }}" @selected((string)old('employee_profile_id', $selectedEmployeeId) === (string)$emp->id)>
                                {{ $emp->employee_id }} — {{ $emp->user?->name }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div class="field">
                    <label>Date</label>
                    <input type="date" name="work_date" value="{{ old('work_date', $selectedDate) }}" required>
                </div>
                <div class="field">
                    <label>Status</label>
                    <select name="status" required>
                        <option value="present" @selected($singleStatus==='present')>Present</option>
                        <option value="half_day" @selected($singleStatus==='half_day')>Half Day</option>
                        <option value="absent" @selected($singleStatus==='absent')>Absent</option>
                        @if(!empty($leavePolicies) && $leavePolicies->count())
                            <optgroup label="Leave">
                                @foreach($leavePolicies as $p)
                                    <option value="leave:{{ $p->id }}" @selected($singleStatus==="leave:$p->id")>
                                        {{ $p->name }} ({{ $p->code }}){{ $p->is_paid ? '' : ' — Unpaid' }}
                                    </option>
                                @endforeach
                            </optgroup>
                            <optgroup label="Half-day leave">
                                @foreach($leavePolicies as $p)
                                    <option value="leave_half:{{ $p->id }}" @selected($singleStatus==="leave_half:$p->id")>
                                        Half-day {{ $p->name }} ({{ $p->code }}){{ $p->is_paid ? '' : ' — Unpaid' }}
                                    </option>
                                @endforeach
                            </optgroup>
                        @endif
                    </select>
                </div>
            </div>

            <button class="btn" type="submit">Save adjustment</button>
        </form>
    </div>

    <div class="card form-card" style="margin-top:14px;">
        <h1>Bulk Attendance Adjustment (Calendar)</h1>
        <p class="muted">Select month + employee, then choose working days. Off days are disabled and ignored.</p>

        <form method="get" action="{{ route('admin.hrms.attendance_adjustments.index') }}" class="form-wrap" style="margin-top:12px;">
            <div class="form-grid cols-2">
                <div class="field">
                    <label>Employee</label>
                    <select name="employee_profile_id" required>
                        <option value="">Select…</option>
                        @foreach($employees as $emp)
                            <option value="{{ $emp->id }}" @selected((string)request('employee_profile_id', $selectedEmployeeId) === (string)$emp->id)>
                                {{ $emp->employee_id }} — {{ $emp->user?->name }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div class="field">
                    <label>Month</label>
                    <input type="month" name="month" value="{{ request('month', $selectedMonth) }}" required>
                </div>
            </div>
            <button class="pill" type="submit">Load calendar</button>
        </form>

        <form method="post" action="{{ route('admin.hrms.attendance_adjustments.bulk_store') }}" class="form-wrap" style="margin-top:12px;">
            @csrf
            <input type="hidden" name="employee_profile_id" value="{{ request('employee_profile_id', $selectedEmployeeId) }}">
            <input type="hidden" name="month" value="{{ request('month', $selectedMonth) }}">

            <div class="form-grid cols-2">
                <div class="field">
                    <label>Status (apply to selected days)</label>
                    <select name="status" required>
                        <option value="present" @selected($bulkStatus==='present')>Present</option>
                        <option value="half_day" @selected($bulkStatus==='half_day')>Half Day</option>
                        <option value="absent" @selected($bulkStatus==='absent')>Absent</option>
                        @if(!empty($leavePolicies) && $leavePolicies->count())
                            <optgroup label="Leave">
                                @foreach($leavePolicies as $p)
                                    <option value="leave:{{ $p->id }}" @selected($bulkStatus==="leave:$p->id")>
                                        {{ $p->name }} ({{ $p->code }}){{ $p->is_paid ? '' : ' — Unpaid' }}
                                    </option>
                                @endforeach
                            </optgroup>
                            <optgroup label="Half-day leave">
                                @foreach($leavePolicies as $p)
                                    <option value="leave_half:{{ $p->id }}" @selected($bulkStatus==="leave_half:$p->id")>
                                        Half-day {{ $p->name }} ({{ $p->code }}){{ $p->is_paid ? '' : ' — Unpaid' }}
                                    </option>
                                @endforeach
                            </optgroup>
                        @endif
                    </select>
                </div>
                <div class="field" style="display:flex; align-items:flex-end; gap:8px;">
                    <button class="pill" type="button" data-cal-action="select-all">Select all working days</button>
                    <button class="pill" type="button" data-cal-action="clear-all">Clear</button>
                </div>
            </div>

            <div class="adj-cal">
                @foreach($dayNames as $name)
                    <div class="adj-cal-head">{{ $name }}</div>
                @endforeach

                @foreach($weeks as $week)
                    @foreach($week as $day)
                        <div class="adj-day {{ $day['in_month'] ? '' : 'out' }} {{ $day['is_working'] ? '' : 'off' }}">
                            <div class="adj-day-top">
                                <span>{{ $day['day'] }}</span>
                                <span class="adj-chip">{{ $day['is_working'] ? 'Working' : 'Off' }}</span>
                            </div>
                            @if($day['in_month'] && $day['is_working'])
                                <label class="adj-check">
                                    <input type="checkbox" class="adj-workday" name="work_dates[]" value="{{ $day['ymd'] }}"
                                        @checked(in_array($day['ymd'], old('work_dates', []), true))>
                                    <span>Select</span>
                                </label>
                            @else
                                <span class="muted" style="font-size:11px;">Not selectable</span>
                            @endif
                        </div>
                    @endforeach
                @endforeach
            </div>

            <div style="margin-top:12px;">
                <button class="btn" type="submit">Apply bulk adjustment</button>
            </div>
        </form>
    </div>
@endsection

@push('scripts')
<script>
    (function () {
        const all = Array.from(document.querySelectorAll('.adj-workday'));
        const selectAllBtn = document.querySelector('[data-cal-action="select-all"]');
        const clearBtn = document.querySelector('[data-cal-action="clear-all"]');
        if (selectAllBtn) {
            selectAllBtn.addEventListener('click', function () {
                all.forEach(function (el) { el.checked = true; });
            });
        }
        if (clearBtn) {
            clearBtn.addEventListener('click', function () {
                all.forEach(function (el) { el.checked = false; });
            });
        }
    })();
</script>
@endpush

