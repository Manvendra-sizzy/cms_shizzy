<?php

namespace App\Modules\Projects\Listeners;

use App\Modules\Projects\Events\ProjectStatusChanged;
use App\Models\CmsActivityLog;
use App\Models\User;
use App\Services\TelegramBotService;

class SyncProjectStatusTimeline
{
    public function __construct(
        private readonly TelegramBotService $telegramBotService
    ) {
    }

    public function handle(ProjectStatusChanged $event): void
    {
        $actor = User::query()->find($event->changedByUserId);

        CmsActivityLog::query()->create([
            'user_id' => $event->changedByUserId,
            'user_email' => $actor?->email,
            'action_key' => 'projects.status.changed',
            'method' => 'EVENT',
            'route_name' => 'projects.status.store',
            'url' => route('projects.show', ['project' => $event->project->id], false),
            'ip_address' => null,
            'user_agent' => 'event-listener',
            'status_code' => 200,
            'context' => [
                'project_id' => $event->project->id,
                'project_code' => $event->project->project_code,
                'project_name' => $event->project->name,
                'from_status' => $event->fromStatus,
                'to_status' => $event->toStatus,
                'effective_date' => $event->effectiveDate,
                'remark' => $event->remark,
            ],
        ]);

        $this->sendProjectStatusNotification($event, $actor);
    }

    private function sendProjectStatusNotification(ProjectStatusChanged $event, ?User $actor): void
    {
        if (!config('services.telegram.notify_project_status_changed', true)) {
            return;
        }

        $projectCode = (string) ($event->project->project_code ?: ('#' . $event->project->id));
        $projectName = (string) ($event->project->name ?: 'Untitled Project');
        $actorName = (string) ($actor?->name ?: 'Unknown user');

        $message = implode("\n", [
            '<b>Project status changed</b>',
            '<b>Project:</b> ' . e($projectName) . ' (' . e($projectCode) . ')',
            '<b>From:</b> ' . e($event->fromStatus ?: 'n/a'),
            '<b>To:</b> ' . e($event->toStatus),
            '<b>Effective date:</b> ' . e($event->effectiveDate),
            '<b>Changed by:</b> ' . e($actorName),
            '<b>Remark:</b> ' . e($event->remark),
        ]);

        $this->telegramBotService->sendHtml($message);
    }
}
