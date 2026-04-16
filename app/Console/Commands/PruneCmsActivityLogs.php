<?php

namespace App\Console\Commands;

use App\Models\CmsActivityLog;
use Illuminate\Console\Command;

class PruneCmsActivityLogs extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'cms:logs:prune {--days=15 : Keep only this many recent days}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Delete CMS activity logs older than N days';

    public function handle(): int
    {
        $days = max(1, (int) $this->option('days'));
        $cutoff = now()->subDays($days);

        $deleted = CmsActivityLog::query()
            ->where('created_at', '<', $cutoff)
            ->delete();

        $this->info("Deleted {$deleted} CMS activity logs older than {$days} days.");

        return self::SUCCESS;
    }
}

