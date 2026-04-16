<?php

namespace App\Services\HRMS;

use App\Models\Holiday;
use Carbon\Carbon;
use Carbon\CarbonInterface;
class CalendarService
{
    /** @var array<string, true> */
    protected array $extraHolidayDates = [];

    public function __construct()
    {
        $this->warmHolidayCache();
    }

    protected function warmHolidayCache(): void
    {
        $this->extraHolidayDates = Holiday::query()
            ->pluck('observed_on')
            ->mapWithKeys(fn ($d) => [Carbon::parse($d)->format('Y-m-d') => true])
            ->all();
    }

    public function refreshHolidayCache(): void
    {
        $this->warmHolidayCache();
    }

    public function saturdayOrdinalInMonth(CarbonInterface $date): int
    {
        if (!$date->isSaturday()) {
            return 0;
        }
        $n = 0;
        for ($c = $date->copy()->startOfMonth(); $c->lte($date); $c->addDay()) {
            if ($c->isSaturday()) {
                $n++;
            }
        }

        return $n;
    }

    public function isSecondOrFourthSaturday(CarbonInterface $date): bool
    {
        $ord = $this->saturdayOrdinalInMonth($date);

        return $ord === 2 || $ord === 4;
    }

    public function isAdminHoliday(CarbonInterface $date): bool
    {
        return isset($this->extraHolidayDates[$date->format('Y-m-d')]);
    }

    /**
     * Working day: not Sunday, not 2nd/4th Saturday, not admin-marked holiday.
     */
    public function isWorkingDay(CarbonInterface $date): bool
    {
        if ($date->isSunday()) {
            return false;
        }
        if ($date->isSaturday() && $this->isSecondOrFourthSaturday($date)) {
            return false;
        }
        if ($this->isAdminHoliday($date)) {
            return false;
        }

        return true;
    }

    /**
     * @return list<Carbon>
     */
    public function workingDaysBetween(CarbonInterface $start, CarbonInterface $end): array
    {
        $out = [];
        for ($d = $start->copy()->startOfDay(); $d->lte($end); $d->addDay()) {
            if ($this->isWorkingDay($d)) {
                $out[] = $d->copy();
            }
        }

        return $out;
    }

    public function countWorkingDaysBetween(CarbonInterface $start, CarbonInterface $end): int
    {
        return count($this->workingDaysBetween($start, $end));
    }
}
