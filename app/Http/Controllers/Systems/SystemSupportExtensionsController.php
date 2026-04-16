<?php

namespace App\Http\Controllers\Systems;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Modules\Systems\Models\SupportExtension;
use App\Modules\Systems\Models\System as SystemModel;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class SystemSupportExtensionsController extends Controller
{
    public function store(Request $request, SystemModel $system)
    {
        /** @var User|null $user */
        $user = Auth::user();

        $data = $request->validate([
            'new_end_date' => ['required', 'date'],
            'reason' => ['required', 'string', 'max:4000'],
        ]);

        if (! $system->support_end_date) {
            return back()->withErrors([
                'new_end_date' => 'Set an initial support end date in system edit first.',
            ])->withInput();
        }

        $previousEnd = $system->support_end_date->toDateString();
        $newEnd = (string) $data['new_end_date'];
        if ($newEnd <= $previousEnd) {
            return back()->withErrors([
                'new_end_date' => 'New support end date must be later than current support end date.',
            ])->withInput();
        }

        $system->update([
            'support_end_date' => $newEnd,
            'support_status' => 'active',
        ]);

        SupportExtension::query()->create([
            'system_id' => $system->id,
            'previous_end_date' => $previousEnd,
            'new_end_date' => $newEnd,
            'extended_by_user_id' => $user?->id,
            'reason' => $data['reason'],
            'extended_at' => now(),
        ]);

        return redirect()->route('systems.show', $system)->with('status', 'Support extended successfully.');
    }
}
