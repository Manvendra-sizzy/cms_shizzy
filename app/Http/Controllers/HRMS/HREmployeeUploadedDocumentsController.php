<?php

namespace App\Http\Controllers\HRMS;

use App\Http\Controllers\Controller;
use App\Models\EmployeeUploadedDocument;
use App\Models\User;
use App\Modules\HRMS\Employees\Models\EmployeeProfile;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;

class HREmployeeUploadedDocumentsController extends Controller
{
    public function store(Request $request, EmployeeProfile $employeeProfile)
    {
        /** @var User $user */
        $user = Auth::user();

        $data = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'file' => ['required', 'file', 'mimes:pdf,jpg,jpeg,png', 'max:10240'],
        ]);

        $path = $request->file('file')->store('hrms/employee-documents', 'public');

        EmployeeUploadedDocument::query()->create([
            'employee_profile_id' => $employeeProfile->id,
            'title' => $data['title'],
            'file_path' => $path,
            'uploaded_by_user_id' => $user->id,
            'uploaded_at' => now(),
        ]);

        return back()->with('status', 'Document uploaded.');
    }

    public function download(EmployeeUploadedDocument $employeeUploadedDocument)
    {
        if (! Storage::disk('public')->exists($employeeUploadedDocument->file_path)) {
            abort(404);
        }

        $contents = Storage::disk('public')->get($employeeUploadedDocument->file_path);
        $name = preg_replace('/[^A-Za-z0-9._-]+/', '-', $employeeUploadedDocument->title) ?: 'document';
        if (! str_contains($name, '.')) {
            $name .= '.bin';
        }

        return response($contents, 200, [
            'Content-Type' => 'application/octet-stream',
            'Content-Disposition' => 'attachment; filename="'.$name.'"',
        ]);
    }
}

