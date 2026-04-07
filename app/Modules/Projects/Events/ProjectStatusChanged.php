<?php

namespace App\Modules\Projects\Events;

use App\Modules\Projects\Models\Project;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class ProjectStatusChanged
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public readonly Project $project,
        public readonly string $fromStatus,
        public readonly string $toStatus,
        public readonly string $effectiveDate,
        public readonly string $remark,
        public readonly int $changedByUserId
    ) {
    }
}
