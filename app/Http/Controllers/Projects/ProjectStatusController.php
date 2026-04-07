<?php

namespace App\Http\Controllers\Projects;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Modules\Projects\Events\ProjectStatusChanged;
use App\Modules\Projects\Models\Project;
use App\Modules\Projects\Models\ProjectStatusLog;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ProjectStatusController extends Controller
{
    public function store(Request $request, Project $project)
    {
        /** @var User $user */
        $user = Auth::user();

        $data = $request->validate([
            'to_status' => ['required', 'string', 'in:active,hold,cancelled,completed'],
            'effective_date' => ['required', 'date'],
            'remark' => ['required', 'string', 'max:4000'],
        ]);

        $from = $project->status;
        $to = $data['to_status'];

        if ($from === $to) {
            return back()->withErrors(['to_status' => 'Status is already set to this value.']);
        }

        $project->update(['status' => $to]);

        ProjectStatusLog::query()->create([
            'project_id' => $project->id,
            'from_status' => $from,
            'to_status' => $to,
            'effective_date' => Carbon::parse($data['effective_date'])->toDateString(),
            'remark' => $data['remark'],
            'changed_by_user_id' => $user->id,
        ]);

        ProjectStatusChanged::dispatch(
            $project->fresh(),
            (string) $from,
            (string) $to,
            Carbon::parse($data['effective_date'])->toDateString(),
            (string) $data['remark'],
            (int) $user->id
        );

        return back()->with('status', 'Project status updated.');
    }
}
