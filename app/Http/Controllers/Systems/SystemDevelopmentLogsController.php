<?php

namespace App\Http\Controllers\Systems;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Modules\Systems\Models\System as SystemModel;
use App\Modules\Systems\Models\SystemDevelopmentLog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class SystemDevelopmentLogsController extends Controller
{
    public static function changeTypes(): array
    {
        return ['bug_fix', 'feature', 'refactor', 'infra_change'];
    }

    public static function deploymentStatuses(): array
    {
        return ['planned', 'in_progress', 'deployed', 'rolled_back'];
    }

    public function store(Request $request, SystemModel $system)
    {
        /** @var User|null $user */
        $user = Auth::user();

        $data = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'description' => ['required', 'string', 'max:20000'],
            'change_type' => ['required', 'string', 'in:' . implode(',', self::changeTypes())],
            'version' => ['nullable', 'string', 'max:64'],
            'change_date' => ['required', 'date'],
            'deployment_status' => ['required', 'string', 'in:' . implode(',', self::deploymentStatuses())],
        ]);

        SystemDevelopmentLog::query()->create([
            'system_id' => $system->id,
            'title' => $data['title'],
            'description' => $data['description'],
            'change_type' => $data['change_type'],
            'version' => $data['version'] ?? null,
            'changed_by_user_id' => $user?->id,
            'change_date' => $data['change_date'],
            'deployment_status' => $data['deployment_status'],
        ]);

        return redirect()->route('systems.show', $system)->with('status', 'Development log added.');
    }

    public function update(Request $request, SystemModel $system, SystemDevelopmentLog $log)
    {
        if ($log->system_id !== $system->id) {
            abort(404);
        }

        $data = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'description' => ['required', 'string', 'max:20000'],
            'change_type' => ['required', 'string', 'in:' . implode(',', self::changeTypes())],
            'version' => ['nullable', 'string', 'max:64'],
            'change_date' => ['required', 'date'],
            'deployment_status' => ['required', 'string', 'in:' . implode(',', self::deploymentStatuses())],
        ]);

        $log->update($data);

        return redirect()->route('systems.show', $system)->with('status', 'Development log updated.');
    }

    public function destroy(SystemModel $system, SystemDevelopmentLog $log)
    {
        if ($log->system_id !== $system->id) {
            abort(404);
        }

        $log->delete();

        return redirect()->route('systems.show', $system)->with('status', 'Development log deleted.');
    }
}
