<?php

namespace App\Console\Commands;

use App\Services\TelegramBotService;
use Illuminate\Console\Command;

class SendTelegramTestNotification extends Command
{
    protected $signature = 'cms:telegram:test';

    protected $description = 'Send a test Telegram message that looks like a leave application (type, working days).';

    public function handle(TelegramBotService $telegram): int
    {
        if (! $telegram->isConfigured()) {
            $this->error('Telegram is not configured. Set TELEGRAM_BOT_TOKEN and TELEGRAM_CHAT_ID in .env, then run: php artisan config:clear');

            return self::FAILURE;
        }

        if (! $telegram->sendLeaveApplicationTestDemo()) {
            $this->error('Failed to send (check storage/logs/laravel.log for telegram.send_* entries).');

            return self::FAILURE;
        }

        $this->info('Test leave-style notification sent. Check Telegram.');

        return self::SUCCESS;
    }
}
