<?php

namespace App\Console\Commands;

use App\Models\AttendanceDay;
use App\Services\HRMS\CalendarService;
use App\Services\TelegramBotService;
use Carbon\Carbon;
use Illuminate\Console\Command;

class SendTelegramDailyAttendanceDigest extends Command
{
    protected $signature = 'cms:telegram:daily-attendance';

    protected $description = 'Send a Telegram message with how many employees have punched in today (IST).';

    public function handle(TelegramBotService $telegram, CalendarService $calendar): int
    {
        if (! $telegram->isConfigured()) {
            $this->warn('Telegram not configured: set TELEGRAM_BOT_TOKEN and TELEGRAM_CHAT_ID in .env');

            return self::SUCCESS;
        }

        $today = Carbon::now('Asia/Kolkata')->startOfDay();
        $dateLabel = $today->format('D, d M Y');
        $working = $calendar->isWorkingDay($today);

        $present = AttendanceDay::query()
            ->whereDate('work_date', $today->toDateString())
            ->whereNotNull('punch_in_at')
            ->count();

        $lines = [
            '<b>Shizzy CMS — Daily attendance</b>',
            'Date (IST): '.e($dateLabel),
        ];

        if (! $working) {
            $lines[] = '<i>Calendar: non-working day (weekend/holiday).</i>';
        }

        $lines[] = '';
        $lines[] = '<b>Present (punched in today): '.$present.'</b>';
        $lines[] = '<i>Scheduled run 09:15 IST — count is punch-ins recorded so far.</i>';

        $text = implode("\n", $lines);

        if (! $telegram->sendHtml($text)) {
            $this->error('Telegram request failed (see logs).');

            return self::FAILURE;
        }

        $this->info("Telegram sent: {$present} present.");

        return self::SUCCESS;
    }
}
