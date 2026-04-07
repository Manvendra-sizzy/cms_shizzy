<?php

namespace App\Http\Controllers\HRMS;

use App\Http\Controllers\Controller;
use App\Modules\HRMS\Employees\Models\EmployeeProfile;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;

class EmployeeProfileController extends Controller
{
    public function show()
    {
        /** @var \App\Models\User $user */
        $user = Auth::user();

        $profile = EmployeeProfile::query()
            ->with(['user', 'orgDepartment', 'orgDesignation', 'reportingManager.user'])
            ->where('user_id', $user->id)
            ->first();

        return view('hrms.employee.profile', ['profile' => $profile]);
    }

    public function updatePhoto(Request $request)
    {
        /** @var \App\Models\User $user */
        $user = Auth::user();

        $profile = EmployeeProfile::query()->where('user_id', $user->id)->first();
        if (! $profile) {
            return back()->withErrors(['photo' => 'Employee profile not found.']);
        }

        $request->validate([
            'profile_image' => ['required', 'file', 'mimes:jpg,jpeg,png,webp', 'max:5120'],
        ]);

        $path = $request->file('profile_image')->store('hrms/employee-dp', 'public');

        if ($profile->profile_image_path) {
            Storage::disk('public')->delete($profile->profile_image_path);
        }

        $profile->update(['profile_image_path' => $path]);

        return redirect()
            ->route('employee.profile.show')
            ->with('status', 'Profile image updated.');
    }
}
