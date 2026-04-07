<?php

namespace App\Http\Controllers\HRMS;

use App\Http\Controllers\Controller;
use App\Modules\HRMS\Leaves\Models\LeavePolicy;
use Illuminate\Http\Request;

class HRLeavePoliciesController extends Controller
{
    public function index()
    {
        $policies = LeavePolicy::query()->orderBy('name')->get();
        return view('hrms.hr.leave_policies.index', ['policies' => $policies]);
    }

    public function create()
    {
        return view('hrms.hr.leave_policies.create');
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'code' => ['required', 'string', 'max:32', 'unique:leave_policies,code'],
            'annual_allowance' => ['required', 'integer', 'min:0', 'max:366'],
            'carry_forward' => ['nullable'],
            'max_carry_forward' => ['nullable', 'integer', 'min:0', 'max:366'],
            'requires_approval' => ['nullable'],
            'active' => ['nullable'],
            'is_paid' => ['nullable'],
        ]);

        LeavePolicy::query()->create([
            'name' => $data['name'],
            'code' => strtoupper($data['code']),
            'annual_allowance' => (int) $data['annual_allowance'],
            'carry_forward' => (bool) ($data['carry_forward'] ?? false),
            'max_carry_forward' => (int) ($data['max_carry_forward'] ?? 0),
            'requires_approval' => (bool) ($data['requires_approval'] ?? false),
            'active' => (bool) ($data['active'] ?? false),
            'is_paid' => (bool) ($data['is_paid'] ?? true),
        ]);

        return redirect()->route('admin.hrms.leave_policies.index')->with('status', 'Leave policy created.');
    }
}
