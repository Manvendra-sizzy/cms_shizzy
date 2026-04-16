@extends('hrms.layout')

@section('content')
    <div class="card">
        <h1>Attendance</h1>

        <div id="geo-status" class="muted" style="margin-top:6px;display:none;"></div>

        @if(!$isWorkingToday)
            <p class="muted">Today is not a working day (weekend or holiday).</p>
        @else
            <div class="row" style="gap:16px;align-items:center;margin-top:12px;">
                <div>
                    @if($todayRow && $todayRow->punch_in_at)
                        <p>Punch in: <strong>{{ $todayRow->punch_in_at->format('H:i') }}</strong></p>
                    @else
                        <form method="post" action="{{ route('employee.attendance.punch_in') }}" class="geo-form">
                            @csrf
                            <input type="hidden" name="lat">
                            <input type="hidden" name="lng">
                            <button class="btn" type="submit">Punch in</button>
                        </form>
                    @endif
                </div>
                <div>
                    @if($todayRow && $todayRow->punch_in_at)
                        @if($todayRow->punch_out_at)
                            <p>Punch out: <strong>{{ $todayRow->punch_out_at->format('H:i') }}</strong></p>
                        @else
                            <form method="post" action="{{ route('employee.attendance.punch_out') }}" class="geo-form">
                                @csrf
                                <input type="hidden" name="lat">
                                <input type="hidden" name="lng">
                                <button class="btn" type="submit">Punch out</button>
                            </form>
                        @endif
                    @endif
                </div>
            </div>
        @endif

        @error('punch')
            <p style="color:#c00;margin-top:10px;">{{ $message }}</p>
        @enderror
    </div>

    <div class="card" style="margin-top:14px;">
        <h2>Monthly summary</h2>
        <form method="get" action="{{ route('employee.attendance.index') }}" class="row" style="gap:10px;margin-bottom:14px;">
            <select name="month">
                @for($m = 1; $m <= 12; $m++)
                    <option value="{{ $m }}" @selected($month == $m)>{{ date('F', mktime(0,0,0,$m,1)) }}</option>
                @endfor
            </select>
            <input type="number" name="year" value="{{ $year }}" min="2020" max="2035" style="width:90px;">
            <button class="pill" type="submit">Show</button>
        </form>
        <table>
            <tbody>
            <tr><td>Working days</td><td><strong>{{ $monthly['working_days'] }}</strong></td></tr>
            <tr><td>Days present (punch in)</td><td><strong>{{ $monthly['present_days'] }}</strong></td></tr>
            <tr><td>Paid leave days</td><td><strong>{{ $monthly['paid_leave_days'] }}</strong></td></tr>
            <tr><td>Unpaid leave (LOP)</td><td><strong>{{ $monthly['unpaid_leave_days'] }}</strong></td></tr>
            <tr><td>LOP (absent + unpaid)</td><td><strong>{{ $monthly['lop_days'] }}</strong></td></tr>
            </tbody>
        </table>
        @if(!empty($monthly['leave_breakdown']))
            <h3 style="font-size:14px;margin-top:14px;">Paid leave breakdown</h3>
            <ul class="muted">
                @foreach($monthly['leave_breakdown'] as $code => $d)
                    <li>{{ $code }}: {{ $d }} day(s)</li>
                @endforeach
            </ul>
        @endif
    </div>

    <div class="card" style="margin-top:14px;">
        <h2>Recent attendance</h2>
        @if($recent->isEmpty())
            <p class="muted">No records yet.</p>
        @else
            <table>
                <thead>
                <tr><th>Date</th><th>In</th><th>Out</th></tr>
                </thead>
                <tbody>
                @foreach($recent as $row)
                    <tr>
                        <td>{{ $row->work_date->format('Y-m-d') }}</td>
                        <td class="muted">{{ $row->punch_in_at ? $row->punch_in_at->format('H:i') : '—' }}</td>
                        <td class="muted">{{ $row->punch_out_at ? $row->punch_out_at->format('H:i') : '—' }}</td>
                    </tr>
                @endforeach
                </tbody>
            </table>
        @endif
    </div>

    @push('scripts')
        <script>
            (function () {
                var statusEl = document.getElementById('geo-status');
                function showStatus(msg) {
                    if (!statusEl) return;
                    statusEl.style.display = '';
                    statusEl.textContent = msg;
                }
                function setCoords(lat, lng) {
                    document.querySelectorAll('form.geo-form').forEach(function (f) {
                        var latEl = f.querySelector('input[name="lat"]');
                        var lngEl = f.querySelector('input[name="lng"]');
                        if (latEl) latEl.value = String(lat);
                        if (lngEl) lngEl.value = String(lng);
                    });
                }
                function initGeo() {
                    if (!navigator.geolocation) {
                        showStatus('Geolocation is not supported on this device/browser.');
                        return;
                    }
                    showStatus('Getting location…');
                    navigator.geolocation.getCurrentPosition(function (pos) {
                        setCoords(pos.coords.latitude, pos.coords.longitude);
                        showStatus('Location captured.');
                    }, function () {
                        showStatus('Please enable location permission to punch in/out.');
                    }, {
                        enableHighAccuracy: true,
                        timeout: 10000,
                        maximumAge: 0
                    });
                }
                initGeo();
            })();
        </script>
    @endpush
@endsection
