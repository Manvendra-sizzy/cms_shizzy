<?php

namespace App\Http\Controllers\HRMS;

use App\Http\Controllers\Controller;
use App\Models\EmployeeEmergencyContact;
use App\Modules\HRMS\Employees\Models\EmployeeProfile;
use Illuminate\Http\Request;

class HREmployeeEmergencyContactsController extends Controller
{
    public function edit(EmployeeProfile $employeeProfile)
    {
        $contacts = EmployeeEmergencyContact::query()
            ->where('employee_profile_id', $employeeProfile->id)
            ->orderBy('slot')
            ->get()
            ->keyBy('slot');

        return view('hrms.hr.employees.emergency_contacts', [
            'employee' => $employeeProfile->load('user'),
            'c1' => $contacts->get(1),
            'c2' => $contacts->get(2),
        ]);
    }

    public function update(Request $request, EmployeeProfile $employeeProfile)
    {
        $data = $request->validate([
            'contacts' => ['required', 'array'],
            'contacts.1.name' => ['nullable', 'string', 'max:255'],
            'contacts.1.phone' => ['nullable', 'string', 'max:32'],
            'contacts.1.relation' => ['nullable', 'string', 'max:64'],
            'contacts.2.name' => ['nullable', 'string', 'max:255'],
            'contacts.2.phone' => ['nullable', 'string', 'max:32'],
            'contacts.2.relation' => ['nullable', 'string', 'max:64'],
        ]);

        foreach ([1, 2] as $slot) {
            $row = $data['contacts'][$slot] ?? [];
            $hasAny = !empty($row['name']) || !empty($row['phone']) || !empty($row['relation']);
            if (! $hasAny) {
                EmployeeEmergencyContact::query()
                    ->where('employee_profile_id', $employeeProfile->id)
                    ->where('slot', $slot)
                    ->delete();
                continue;
            }

            EmployeeEmergencyContact::query()->updateOrCreate(
                ['employee_profile_id' => $employeeProfile->id, 'slot' => $slot],
                [
                    'name' => $row['name'] ?? null,
                    'phone' => $row['phone'] ?? null,
                    'relation' => $row['relation'] ?? null,
                ]
            );
        }

        return redirect()
            ->route('admin.hrms.employees.show', $employeeProfile)
            ->with('status', 'Emergency contacts saved.');
    }
}

