<?php

namespace App\Services\HRMS;

use App\Models\EmploymentAgreementContent;
use App\Modules\HRMS\Employees\Models\EmployeeProfile;
use App\Modules\HRMS\Onboarding\Models\EmployeeOnboarding;
use Dompdf\Dompdf;
use Illuminate\Support\Facades\Storage;
use RuntimeException;

class EmploymentAgreementPdfService
{
    /**
     * @return array{disk: string, path: string, page_count: positive-int, bytes: string}
     */
    public function generateAndStore(EmployeeOnboarding $onboarding, ?EmployeeProfile $profile = null): array
    {
        $onboarding->loadMissing(['designation', 'department', 'team']);

        $template = EmploymentAgreementContent::resolveTemplateHtml();
        if (! is_string($template) || trim($template) === '') {
            throw new RuntimeException(
                'Employment agreement template is not configured. Add it in HRMS -> Employment agreement '
                .'or keep employee_agreement_content.txt at the project root.'
            );
        }

        $agreementBodyHtml = EmploymentAgreementContent::mergePlaceholders($template, $onboarding, $profile);

        $html = view('hrms.onboarding.employment_agreement_pdf', [
            'onboarding' => $onboarding,
            'profile' => $profile,
            'agreementBodyHtml' => $agreementBodyHtml,
        ])->render();

        $dompdf = new Dompdf([
            'defaultFont' => 'DejaVu Sans',
            'isRemoteEnabled' => true,
        ]);
        $dompdf->loadHtml($html, 'UTF-8');
        $dompdf->setPaper('A4', 'portrait');
        $dompdf->render();

        $pageCount = max(1, (int) $dompdf->getCanvas()->get_page_count());
        $bytes = $dompdf->output();

        $relative = 'hrms/onboarding/'.$onboarding->id.'/employment-agreement-'.now()->format('YmdHis').'.pdf';
        Storage::disk('local')->put($relative, $bytes);

        return [
            'disk' => 'local',
            'path' => $relative,
            'page_count' => $pageCount,
            'bytes' => $bytes,
        ];
    }

    public function absolutePath(string $disk, string $path): string
    {
        return Storage::disk($disk)->path($path);
    }
}
