<?php

namespace App\Http\Controllers\HRMS;

use App\Http\Controllers\Controller;
use App\Models\HrmsPolicyGuideline;
use Illuminate\Http\Request;

class HRPoliciesGuidelinesController extends Controller
{
    public function index()
    {
        $policies = HrmsPolicyGuideline::query()->orderBy('title')->orderByDesc('id')->get();

        return view('hrms.hr.policies_guidelines.index', [
            'policies' => $policies,
        ]);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'content' => ['required', 'string'],
            'active' => ['nullable'],
        ]);

        HrmsPolicyGuideline::query()->create([
            'title' => $data['title'],
            'content' => $data['content'],
            'active' => (bool) ($data['active'] ?? true),
        ]);

        return redirect()
            ->route('admin.hrms.policies_guidelines.index')
            ->with('status', 'Policy/Guideline added.');
    }
}

