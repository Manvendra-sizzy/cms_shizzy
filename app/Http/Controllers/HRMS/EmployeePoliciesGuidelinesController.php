<?php

namespace App\Http\Controllers\HRMS;

use App\Http\Controllers\Controller;
use App\Models\HrmsPolicyGuideline;

class EmployeePoliciesGuidelinesController extends Controller
{
    public function index()
    {
        $policies = HrmsPolicyGuideline::query()
            ->where('active', true)
            ->orderBy('title')
            ->get();

        return view('hrms.employee.policies_guidelines.index', [
            'policies' => $policies,
        ]);
    }
}

