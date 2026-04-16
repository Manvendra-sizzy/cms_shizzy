<?php

namespace App\Http\Controllers\HRMS;

use App\Http\Controllers\Controller;
use App\Models\Holiday;
use App\Services\HRMS\CalendarService;
use Illuminate\Http\Request;

class HRHolidayCalendarController extends Controller
{
    public function index()
    {
        $holidays = Holiday::query()->orderByDesc('observed_on')->paginate(40);

        return view('hrms.hr.calendar.index', ['holidays' => $holidays]);
    }

    public function store(Request $request, CalendarService $calendar)
    {
        $data = $request->validate([
            'observed_on' => ['required', 'date'],
            'title' => ['required', 'string', 'max:255'],
        ]);

        Holiday::query()->updateOrCreate(
            ['observed_on' => $data['observed_on']],
            ['title' => $data['title']]
        );

        $calendar->refreshHolidayCache();

        return back()->with('status', 'Holiday saved. Sundays and 2nd/4th Saturdays are always off.');
    }

    public function destroy(Holiday $holiday, CalendarService $calendar)
    {
        $holiday->delete();
        $calendar->refreshHolidayCache();

        return back()->with('status', 'Holiday removed.');
    }
}
