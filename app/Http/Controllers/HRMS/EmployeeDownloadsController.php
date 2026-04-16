<?php

namespace App\Http\Controllers\HRMS;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Modules\HRMS\Documents\Models\HRDocument;
use App\Modules\HRMS\Employees\Models\EmployeeProfile;
use App\Modules\HRMS\Payroll\Models\SalarySlip;
use App\Models\EmployeeUploadedDocument;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;

class EmployeeDownloadsController extends Controller
{
    public function index()
    {
        /** @var User $user */
        $user = Auth::user();

        $profile = EmployeeProfile::query()->where('user_id', $user->id)->firstOrFail();

        $documents = HRDocument::query()
            ->where('employee_profile_id', $profile->id)
            ->orderByDesc('id')
            ->get();

        $uploadedDocs = EmployeeUploadedDocument::query()
            ->where('employee_profile_id', $profile->id)
            ->orderByDesc('uploaded_at')
            ->get();

        $slips = SalarySlip::query()
            ->with('payrollRun')
            ->where('employee_profile_id', $profile->id)
            ->orderByDesc('id')
            ->get();

        return view('hrms.employee.downloads.index', [
            'documents' => $documents,
            'uploadedDocs' => $uploadedDocs,
            'slips' => $slips,
        ]);
    }

    public function downloadUploadedDocument(EmployeeUploadedDocument $employeeUploadedDocument)
    {
        /** @var User $user */
        $user = Auth::user();

        $profile = EmployeeProfile::query()->where('user_id', $user->id)->firstOrFail();
        if ($employeeUploadedDocument->employee_profile_id !== $profile->id) {
            abort(403);
        }

        if (!Storage::disk('public')->exists($employeeUploadedDocument->file_path)) {
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

    public function downloadDocument(HRDocument $document)
    {
        /** @var User $user */
        $user = Auth::user();

        $profile = EmployeeProfile::query()->where('user_id', $user->id)->firstOrFail();

        if ($document->employee_profile_id !== $profile->id) {
            abort(403);
        }

        $bytes = null;
        $path = $document->file_path;

        if ($path && Storage::disk('local')->exists($path)) {
            try {
                $raw = Storage::disk('local')->get($path);
                if (str_ends_with(strtolower($path), '.pdf')) {
                    $bytes = $raw;
                } else {
                    // Backward compatibility: old stored HTML → convert to PDF at download time.
                    $dompdf = new \Dompdf\Dompdf([
                        'defaultFont' => 'DejaVu Sans',
                        'isRemoteEnabled' => true,
                    ]);
                    $dompdf->loadHtml($raw, 'UTF-8');
                    $dompdf->setPaper('A4', 'portrait');
                    $dompdf->render();
                    $bytes = $dompdf->output();
                }
            } catch (\Throwable $e) {
                $bytes = null;
            }
        }

        if ($bytes === null) {
            $html = view('hrms.shared.document', ['document' => $document])->render();
            $dompdf = new \Dompdf\Dompdf([
                'defaultFont' => 'DejaVu Sans',
                'isRemoteEnabled' => true,
            ]);
            $dompdf->loadHtml($html, 'UTF-8');
            $dompdf->setPaper('A4', 'portrait');
            $dompdf->render();
            $bytes = $dompdf->output();
        }

        return response($bytes, 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'attachment; filename="document-'.$document->id.'.pdf"',
        ]);
    }

    public function downloadSlip(SalarySlip $salarySlip)
    {
        /** @var User $user */
        $user = Auth::user();

        $profile = EmployeeProfile::query()->where('user_id', $user->id)->firstOrFail();

        if ($salarySlip->employee_profile_id !== $profile->id) {
            abort(403);
        }

        $run = $salarySlip->payrollRun()->first();
        if (! $run) {
            abort(404);
        }

        $salarySlip->load(['employeeProfile.user', 'employeeProfile.orgDepartment', 'employeeProfile.orgDesignation']);

        $html = view('hrms.shared.salary_slip', ['slip' => $salarySlip, 'run' => $run])->render();

        $dompdf = new \Dompdf\Dompdf([
            'defaultFont' => 'DejaVu Sans',
            'isRemoteEnabled' => true,
        ]);
        $dompdf->loadHtml($html, 'UTF-8');
        $dompdf->setPaper('A4', 'portrait');
        $dompdf->render();

        return response($dompdf->output(), 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'attachment; filename="'.$salarySlip->slip_number.'.pdf"',
        ]);
    }
}
