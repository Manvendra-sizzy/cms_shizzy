@extends('hrms.layout')

@section('content')
    <div class="card">
        <div class="row" style="justify-content:space-between;">
            <h1>My leaves</h1>
            <a class="pill" href="{{ route('employee.leaves.create') }}">Apply leave</a>
        </div>

        <div class="card" style="margin-top:12px;">
            <div class="row" style="justify-content:space-between;align-items:center;">
                <h2 style="margin:0;">Leave calendar</h2>
                <div class="row" style="gap:10px;">
                    @php
                        $activeMonthStart = $monthStart ?? now();
                        $activeLabel = $activeMonthStart->format('F Y');
                        $prev = $activeMonthStart->copy()->subMonthNoOverflow();
                        $next = $activeMonthStart->copy()->addMonthNoOverflow();
                    @endphp
                    <a class="pill" href="{{ route('employee.leaves.index', ['month' => $prev->month, 'year' => $prev->year]) }}">Prev</a>
                    <div class="muted" style="font-weight:700;">{{ $activeLabel }}</div>
                    <a class="pill" href="{{ route('employee.leaves.index', ['month' => $next->month, 'year' => $next->year]) }}">Next</a>
                </div>
            </div>

            <div class="table-wrap" style="margin-top:12px;">
                <table>
                    <thead>
                    <tr>
                        <th>Mon</th>
                        <th>Tue</th>
                        <th>Wed</th>
                        <th>Thu</th>
                        <th>Fri</th>
                        <th>Sat</th>
                        <th>Sun</th>
                    </tr>
                    </thead>
                    <tbody>
                    @foreach(($weeks ?? []) as $week)
                        <tr>
                            @foreach($week as $day)
                                @php
                                    $dateStr = $day->format('Y-m-d');
                                    $inMonth = $day->month === (int)($month ?? $day->month);
                                    $leaveSlots = $leavesByDate[$dateStr] ?? [];
                                    $isSunday = $day->isSunday();
                                    $isSaturday = $day->isSaturday();
                                    $isSatOff = $calendar?->isSecondOrFourthSaturday($day);
                                    $isAdminHoliday = $calendar?->isAdminHoliday($day);
                                @endphp
                                <td style="vertical-align:top; width:14.28%; {{ $inMonth ? '' : 'opacity:.55;' }}">
                                    @php
                                        $hasHalfLeave = false;
                                        $hasLeave = count($leaveSlots) > 0;
                                        $firstLeave = $hasLeave ? $leaveSlots[0] : null;
                                        if ($hasLeave) {
                                            foreach ($leaveSlots as $ls) {
                                                if (($ls['is_half'] ?? false) === true) { $hasHalfLeave = true; break; }
                                            }
                                        }

                                        $attendance = $attendanceByDate[$dateStr] ?? null;
                                        $hasPunch = !empty($attendance) && !empty($attendance->punch_in_at);
                                        $workFraction = !empty($attendance) ? (float) ($attendance->work_fraction ?? 1.0) : 1.0;

                                        $state = 'green';
                                        $stateLabel = '';

                                        $todayStr = $todayStr ?? now()->toDateString();
                                        $isFuture = $dateStr > $todayStr;

                                        $isOffDay = $isSunday || $isSatOff || $isAdminHoliday;

                                        // Future dates: never show Red (absent). Only show:
                                        // - Green: working days (no approved leave)
                                        // - Orange: approved leave / Saturday / Sunday / designated holidays
                                        // - Yellow: approved half-day leave or half-day attendance
                                        // Past dates: Red means absent (no attendance) on working days.
                                        if ($hasHalfLeave) {
                                            $state = 'yellow';
                                            $stateLabel = 'Half';
                                        } elseif ($hasLeave) {
                                            $state = 'orange';
                                        } elseif ($isOffDay) {
                                            $state = 'orange';
                                        } elseif ($isFuture) {
                                            $state = 'green';
                                        } else {
                                            // Past working day: present/half/absent based on attendance.
                                            if ($hasPunch && $workFraction < 1.0) {
                                                $state = 'yellow';
                                                $stateLabel = 'Half';
                                            } elseif ($hasPunch) {
                                                $state = 'green';
                                                $stateLabel = '';
                                            } else {
                                                $state = 'red';
                                                $stateLabel = 'Absent';
                                            }
                                        }

                                        $bg = 'rgba(34,197,94,.12)';
                                        $border = 'rgba(34,197,94,.28)';
                                        $textColor = 'var(--text)';

                                        if ($state === 'orange') {
                                            $bg = 'rgba(255,77,61,.12)';
                                            $border = 'rgba(255,77,61,.28)';
                                        } elseif ($state === 'red') {
                                            $bg = 'rgba(239,68,68,.12)';
                                            $border = 'rgba(239,68,68,.28)';
                                        } elseif ($state === 'yellow') {
                                            $bg = 'rgba(245,158,11,.14)';
                                            $border = 'rgba(245,158,11,.28)';
                                        }
                                    @endphp

                                    <div style="background:{{ $bg }}; border:1px solid {{ $border }}; border-radius:12px; padding:8px 8px; min-height:44px;">
                                        <div style="font-weight:800; margin-bottom:6px; display:flex; justify-content:space-between; gap:8px;">
                                            <span>{{ $day->day }}</span>
                                            @if($isSunday)
                                                <span class="muted" style="font-size:10px; font-weight:700;">S</span>
                                            @endif
                                        </div>

                                        @if($hasHalfLeave)
                                            <div style="font-weight:800; font-size:11px;">
                                                {{ $firstLeave['policy_name'] ?? 'Leave' }}
                                                <span class="muted" style="font-weight:700;">(Half)</span>
                                            </div>
                                        @elseif($hasLeave)
                                            <div style="font-weight:800; font-size:11px;">
                                                {{ $firstLeave['policy_name'] ?? 'Leave' }}
                                            </div>
                                        @else
                                            @if($isAdminHoliday)
                                                <div class="muted" style="font-size:11px;">Holiday</div>
                                            @elseif($isSunday)
                                                <div class="muted" style="font-size:11px;">Off</div>
                                            @elseif($isSatOff)
                                                <div class="muted" style="font-size:11px;">Sat (Off)</div>
                                            @elseif($day->isSaturday())
                                                <div class="muted" style="font-size:11px;">Sat</div>
                                            @elseif($state === 'red')
                                                <div style="font-size:11px; font-weight:800; color:rgb(220,38,38);">Absent</div>
                                            @elseif($state === 'yellow')
                                                <div style="font-size:11px; font-weight:800; color:rgb(180,83,9);">Half</div>
                                            @else
                                                <div style="font-size:11px; font-weight:700;" class="muted">&nbsp;</div>
                                            @endif
                                        @endif

                                        @if($hasLeave && count($leaveSlots) > 1)
                                            <div class="muted" style="font-size:11px; margin-top:4px;">+{{ count($leaveSlots)-1 }} more</div>
                                        @endif
                                    </div>
                                </td>
                            @endforeach
                        </tr>
                    @endforeach
                    </tbody>
                </table>
            </div>

            <p class="muted" style="margin-top:10px; font-size:12px;">
                Shows weekends/holidays and your approved leave requests.
                Half-day leaves are labeled as “Half”.
            </p>
        </div>

        <div class="card" style="margin-top:12px;">
            <h2>Leave balance (this year)</h2>
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

        <div class="card" style="margin-top:12px;">
            <h2>Leave history</h2>
        @if($requests->isEmpty())
            <p class="muted">No leave requests yet.</p>
        @else
            <table>
                <thead>
                <tr>
                    <th>Policy</th>
                    <th>Dates</th>
                    <th>Days</th>
                    <th>Status</th>
                    <th>Reason</th>
                </tr>
                </thead>
                <tbody>
                @foreach($requests as $r)
                    <tr>
                        <td><strong>{{ $r->policy->code }}</strong> <span class="muted">{{ $r->policy->name }}</span></td>
                        <td class="muted">{{ $r->start_date->format('Y-m-d') }} → {{ $r->end_date->format('Y-m-d') }}</td>
                        <td>{{ $r->days }}</td>
                        <td><strong>{{ $r->status }}</strong></td>
                        <td class="muted">{{ $r->reason ?? '—' }}</td>
                    </tr>
                @endforeach
                </tbody>
            </table>
        @endif
        </div>
    </div>
@endsection

