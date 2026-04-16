<?php

namespace App\Http\Controllers\HRMS;

use App\Http\Controllers\Controller;
use App\Models\EmploymentAgreementContent;
use Illuminate\Http\Request;

class HREmploymentAgreementController extends Controller
{
    public function edit()
    {
        $content = EmploymentAgreementContent::query()->first();

        return view('hrms.hr.employment_agreement.edit', [
            'content' => $content,
        ]);
    }

    public function update(Request $request)
    {
        $data = $request->validate([
            'body_html' => ['required', 'string'],
        ]);

        $row = EmploymentAgreementContent::query()->first();
        if ($row) {
            $row->update(['body_html' => $data['body_html']]);
        } else {
            EmploymentAgreementContent::query()->create(['body_html' => $data['body_html']]);
        }

        return redirect()
            ->route('admin.hrms.employment_agreement.edit')
            ->with('status', 'Employment agreement saved.');
    }
}
