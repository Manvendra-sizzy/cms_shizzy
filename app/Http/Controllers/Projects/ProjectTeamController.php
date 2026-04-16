<?php

namespace App\Http\Controllers\Projects;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Modules\Projects\Models\Project;
use App\Modules\Projects\Models\ProjectTeamMember;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ProjectTeamController extends Controller
{
    public function store(Request $request, Project $project)
    {
        /** @var User $user */
        $user = Auth::user();

        $data = $request->validate([
            'employee_profile_id' => ['required', 'exists:employee_profiles,id'],
            'role_title' => ['nullable', 'string', 'max:255'],
        ]);

        ProjectTeamMember::query()->updateOrCreate(
            [
                'project_id' => $project->id,
                'employee_profile_id' => (int) $data['employee_profile_id'],
            ],
            [
                'role_title' => $data['role_title'] ?? null,
                'added_by_user_id' => $user->id,
            ],
        );

        return back()->with('status', 'Team member saved.');
    }

    public function destroy(Project $project, ProjectTeamMember $projectTeamMember)
    {
        if ($projectTeamMember->project_id !== $project->id) {
            abort(404);
        }

        $projectTeamMember->delete();

        return back()->with('status', 'Team member removed.');
    }
}
