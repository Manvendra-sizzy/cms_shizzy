<?php

namespace App\Console\Commands;

use App\Mail\CmsTestMail;
use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Mail;

class SendCmsTestEmail extends Command
{
    protected $signature = 'cms:send-test-email {--to= : Recipient email address}';

    protected $description = 'Send a test CMS email using the configured SMTP settings';

    public function handle(): int
    {
        $to = (string) $this->option('to');

        if ($to === '') {
            $to = User::query()->where('role', User::ROLE_ADMIN)->value('email') ?? '';
        }

        if (!is_string($to) || $to === '') {
            $this->error('No recipient email found for admin users.');
            return 1;
        }

        Mail::to($to)->send(new CmsTestMail());

        $this->info("Test email sent to: {$to}");
        return 0;
    }
}

