<?php

namespace App\Http\Controllers\HRMS;

use App\Http\Controllers\Controller;
use App\Models\AttendanceDay;
use App\Models\User;
use App\Modules\HRMS\Employees\Models\EmployeeProfile;
use App\Services\HRMS\AttendanceLeaveSummaryService;
use App\Services\HRMS\CalendarService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class EmployeeAttendanceController extends Controller
{
    protected const OFFICE_LAT = 28.41973770;
    protected const OFFICE_LNG = 77.04088460;
    protected const GEOFENCE_METERS = 500.0;

    public function index(CalendarService $calendar, AttendanceLeaveSummaryService $summaryService)
    {
        /** @var User $user */
        $user = Auth::user();
        $profile = EmployeeProfile::query()->where('user_id', $user->id)->firstOrFail();

        $today = Carbon::today();
        $isWorkingToday = $calendar->isWorkingDay($today);

        $todayRow = AttendanceDay::query()
            ->where('employee_profile_id', $profile->id)
            ->whereDate('work_date', $today)
            ->first();

        $recent = AttendanceDay::query()
            ->where('employee_profile_id', $profile->id)
            ->orderByDesc('work_date')
            ->limit(20)
            ->get();

        $year = (int) request('year', now()->year);
        $month = (int) request('month', now()->month);
        $monthly = $summaryService->summarizePeriod($profile, Carbon::create($year, $month, 1), Carbon::create($year, $month, 1)->endOfMonth());

        return view('hrms.employee.attendance.index', [
            'profile' => $profile,
            'isWorkingToday' => $isWorkingToday,
            'todayRow' => $todayRow,
            'recent' => $recent,
            'monthly' => $monthly,
            'year' => $year,
            'month' => $month,
        ]);
    }

    public function punchIn(CalendarService $calendar)
    {
        /** @var User $user */
        $user = Auth::user();
        $profile = EmployeeProfile::query()->where('user_id', $user->id)->firstOrFail();

        $data = request()->validate([
            'lat' => ['required', 'numeric'],
            'lng' => ['required', 'numeric'],
        ]);

        $now = now('Asia/Kolkata');
        $today = $now->copy()->startOfDay();
        if (! $calendar->isWorkingDay($today)) {
            return back()->withErrors(['punch' => 'Today is not a working day.']);
        }

        if (! $this->isWithinPunchWindow($now)) {
            return back()->withErrors(['punch' => 'Punch in is allowed only between 08:00 AM and 08:00 PM (IST).']);
        }

        if (! $profile->is_remote && ! $this->withinGeofence((float) $data['lat'], (float) $data['lng'])) {
            return back()->withErrors(['punch' => 'You must be within 500 meters of the office to punch in/out.']);
        }

        $row = AttendanceDay::query()->firstOrCreate(
            [
                'employee_profile_id' => $profile->id,
                'work_date' => $today->toDateString(),
            ],
            ['punch_in_at' => null, 'punch_out_at' => null]
        );

        if ($row->punch_in_at) {
            return back()->with('status', 'You already punched in today.');
        }

        $row->update(['punch_in_at' => $now]);

        return back()->with('status', 'Punched in successfully.');
    }

    public function punchOut(CalendarService $calendar)
    {
        /** @var User $user */
        $user = Auth::user();
        $profile = EmployeeProfile::query()->where('user_id', $user->id)->firstOrFail();

        $data = request()->validate([
            'lat' => ['required', 'numeric'],
            'lng' => ['required', 'numeric'],
        ]);

        $now = now('Asia/Kolkata');
        $today = $now->copy()->startOfDay();
        if (! $calendar->isWorkingDay($today)) {
            return back()->withErrors(['punch' => 'Today is not a working day.']);
        }

        if (! $this->isWithinPunchWindow($now)) {
            return back()->withErrors(['punch' => 'Punch out is allowed only between 08:00 AM and 08:00 PM (IST).']);
        }

        if (! $profile->is_remote && ! $this->withinGeofence((float) $data['lat'], (float) $data['lng'])) {
            return back()->withErrors(['punch' => 'You must be within 500 meters of the office to punch in/out.']);
        }

        $row = AttendanceDay::query()
            ->where('employee_profile_id', $profile->id)
            ->whereDate('work_date', $today)
            ->first();

        if (! $row || ! $row->punch_in_at) {
            return back()->withErrors(['punch' => 'Punch in first.']);
        }

        if ($row->punch_out_at) {
            return back()->with('status', 'You already punched out today.');
        }

        $row->update(['punch_out_at' => $now]);

        return back()->with('status', 'Punched out successfully.');
    }

    protected function isWithinPunchWindow(\Carbon\CarbonInterface $nowIst): bool
    {
        $minutes = ((int) $nowIst->format('H')) * 60 + (int) $nowIst->format('i');
        $start = 8 * 60;   // 08:00
        $end = 20 * 60;    // 20:00
        return $minutes >= $start && $minutes <= $end;
    }

    protected function withinGeofence(float $lat, float $lng): bool
    {
        $dist = $this->haversineMeters($lat, $lng, self::OFFICE_LAT, self::OFFICE_LNG);
        return $dist <= self::GEOFENCE_METERS;
    }

    protected function haversineMeters(float $lat1, float $lng1, float $lat2, float $lng2): float
    {
        $r = 6371000.0; // Earth radius (meters), WGS84 mean
        $phi1 = deg2rad($lat1);
        $phi2 = deg2rad($lat2);
        $dPhi = deg2rad($lat2 - $lat1);
        $dLam = deg2rad($lng2 - $lng1);

        $a = sin($dPhi / 2) ** 2 + cos($phi1) * cos($phi2) * (sin($dLam / 2) ** 2);
        $c = 2 * atan2(sqrt($a), sqrt(1 - $a));

        return $r * $c;
    }
}
