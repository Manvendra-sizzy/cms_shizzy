<?php

namespace App\Http\Controllers\Assets;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Modules\Assets\Models\Asset;
use App\Modules\Assets\Models\AssetAssignment;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AssetAssignmentsController extends Controller
{
    public function assign(Request $request, Asset $asset)
    {
        /** @var User $user */
        $user = Auth::user();

        $data = $request->validate([
            'employee_profile_id' => ['required', 'exists:employee_profiles,id'],
            'assigned_at' => ['required', 'date'],
            'remarks' => ['nullable', 'string', 'max:4000'],
        ]);

        $open = AssetAssignment::query()
            ->where('asset_id', $asset->id)
            ->whereNull('returned_at')
            ->exists();

        if ($open) {
            return back()->withErrors(['assignment' => 'Asset is already assigned. Use transfer or return first.']);
        }

        AssetAssignment::query()->create([
            'asset_id' => $asset->id,
            'employee_profile_id' => (int) $data['employee_profile_id'],
            'assigned_at' => $data['assigned_at'],
            'returned_at' => null,
            'action_type' => 'assigned',
            'remarks' => $data['remarks'] ?? null,
            'created_by_user_id' => $user?->id,
        ]);

        $asset->update(['status' => 'assigned']);

        return back()->with('status', 'Asset assigned.');
    }

    public function transfer(Request $request, Asset $asset)
    {
        /** @var User $user */
        $user = Auth::user();

        $data = $request->validate([
            'employee_profile_id' => ['required', 'exists:employee_profiles,id'],
            'assigned_at' => ['required', 'date'],
            'remarks' => ['nullable', 'string', 'max:4000'],
        ]);

        $current = AssetAssignment::query()
            ->where('asset_id', $asset->id)
            ->whereNull('returned_at')
            ->latest('assigned_at')
            ->first();

        if (! $current) {
            return back()->withErrors(['assignment' => 'No current assignment to transfer from. Use Assign instead.']);
        }

        $current->update([
            'returned_at' => $data['assigned_at'],
            'action_type' => 'transferred',
        ]);

        AssetAssignment::query()->create([
            'asset_id' => $asset->id,
            'employee_profile_id' => (int) $data['employee_profile_id'],
            'assigned_at' => $data['assigned_at'],
            'returned_at' => null,
            'action_type' => 'transferred',
            'remarks' => $data['remarks'] ?? null,
            'created_by_user_id' => $user?->id,
        ]);

        $asset->update(['status' => 'assigned']);

        return back()->with('status', 'Asset transferred.');
    }

    public function returnAsset(Request $request, Asset $asset)
    {
        /** @var User $user */
        $user = Auth::user();

        $data = $request->validate([
            'returned_at' => ['required', 'date'],
            'remarks' => ['nullable', 'string', 'max:4000'],
        ]);

        $current = AssetAssignment::query()
            ->where('asset_id', $asset->id)
            ->whereNull('returned_at')
            ->latest('assigned_at')
            ->first();

        if (! $current) {
            return back()->withErrors(['assignment' => 'Asset is not currently assigned.']);
        }

        $current->update([
            'returned_at' => $data['returned_at'],
            'action_type' => 'returned',
            'remarks' => $data['remarks'] ?? $current->remarks,
        ]);

        $asset->update(['status' => 'in_stock']);

        return back()->with('status', 'Asset returned to inventory.');
    }
}
