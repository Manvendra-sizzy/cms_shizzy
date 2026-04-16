<?php

namespace Database\Seeders;

use App\Models\EmploymentAgreementContent;
use Illuminate\Database\Seeder;

class EmploymentAgreementContentSeeder extends Seeder
{
    public function run(): void
    {
        if (EmploymentAgreementContent::query()->exists()) {
            return;
        }

        $path = base_path('employee_agreement_content.txt');
        if (! is_file($path)) {
            return;
        }

        $raw = file_get_contents($path);
        if ($raw === false || $raw === '') {
            return;
        }

        $html = '<div class="agreement-doc">'.nl2br(e($raw)).'</div>';

        EmploymentAgreementContent::query()->create([
            'body_html' => $html,
        ]);
    }
}
