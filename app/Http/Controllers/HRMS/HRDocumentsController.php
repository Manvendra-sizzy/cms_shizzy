<?php

namespace App\Http\Controllers\HRMS;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Modules\HRMS\Documents\Models\HRDocument;
use App\Modules\HRMS\Employees\Models\EmployeeProfile;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use App\Mail\HrDocumentIssuedMail;

class HRDocumentsController extends Controller
{
    public function index()
    {
        $documents = HRDocument::query()
            ->with(['employeeProfile.user', 'issuedBy'])
            ->orderByDesc('id')
            ->paginate(20);

        return view('hrms.hr.documents.index', ['documents' => $documents]);
    }

    public function create()
    {
        $employees = EmployeeProfile::query()->with('user')->orderBy('employee_id')->get();

        return view('hrms.hr.documents.create', ['employees' => $employees]);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'employee_profile_id' => ['required', 'exists:employee_profiles,id'],
            'type' => ['required', 'string', 'max:64'],
            'title' => ['required', 'string', 'max:255'],
            'body' => ['nullable', 'string'],
        ]);

        /** @var User $user */
        $user = Auth::user();

        $doc = HRDocument::query()->create([
            'employee_profile_id' => (int) $data['employee_profile_id'],
            'issued_by_user_id' => $user->id,
            'type' => $data['type'],
            'title' => $data['title'],
            'body' => $data['body'] ?? null,
            'issued_at' => now(),
        ]);

        if (empty($doc->document_hash)) {
            $seed = $doc->id.'|'.$doc->issued_at?->toDateTimeString().'|'.Str::random(16);
            $doc->update(['document_hash' => strtoupper(hash('sha256', $seed))]);
            $doc->refresh();
        }

        $html = view('hrms.shared.document', ['document' => $doc])->render();
        $dompdf = new \Dompdf\Dompdf([
            'defaultFont' => 'DejaVu Sans',
            'isRemoteEnabled' => true,
        ]);
        $dompdf->loadHtml($html, 'UTF-8');
        $dompdf->setPaper('A4', 'portrait');
        $dompdf->render();

        $path = "hrms/documents/document-{$doc->id}.pdf";
        Storage::disk('local')->put($path, $dompdf->output());
        $doc->update(['file_path' => $path]);

        // Notify the employee about the issued document.
        try {
            $doc->load('employeeProfile.user');
            $email = $doc->employeeProfile?->user?->email;
            if (is_string($email) && $email !== '') {
                Mail::to($email)->send(new HrDocumentIssuedMail($doc));
            }
        } catch (\Throwable $e) {
            Log::warning('CMS email notification failed for document issued', [
                'document_id' => $doc->id,
                'error' => $e->getMessage(),
            ]);
        }

        return redirect()->route('admin.hrms.documents.index')->with('status', 'Document issued.');
    }

    public function download(HRDocument $document)
    {
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
            // Fallback: render HTML and convert to PDF.
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
}
