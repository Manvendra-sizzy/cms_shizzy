<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('hrms_policies_guidelines', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->longText('content');
            $table->boolean('active')->default(true)->index();
            $table->timestamps();
        });

        $now = now();

        DB::table('hrms_policies_guidelines')->insert([
            [
                'title' => 'Employee Code of Conduct',
                'content' => $this->employeeCodeOfConductContent(),
                'active' => true,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'title' => 'POSH Guidelines',
                'content' => $this->poshGuidelinesContent(),
                'active' => true,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'title' => 'Leave Policy',
                'content' => $this->leavePolicyContentFromDatabase(),
                'active' => true,
                'created_at' => $now,
                'updated_at' => $now,
            ],
        ]);
    }

    public function down(): void
    {
        Schema::dropIfExists('hrms_policies_guidelines');
    }

    private function employeeCodeOfConductContent(): string
    {
        return implode("\n", [
            '1. Professional behavior is expected in all workplace interactions.',
            '2. Employees must maintain confidentiality of internal, client, and financial information.',
            '3. Attendance and punctuality must be maintained as per assigned working schedules.',
            '4. Conflict of interest must be disclosed to reporting managers and HR.',
            '5. Company assets, credentials, and communication channels must be used responsibly.',
            '6. Any policy violations or misconduct should be reported to HR immediately.',
        ]);
    }

    private function poshGuidelinesContent(): string
    {
        return implode("\n", [
            'POSH (Prevention of Sexual Harassment) Guidelines:',
            '',
            '1. The organization maintains a zero-tolerance policy toward sexual harassment.',
            '2. Every employee has the right to a safe, respectful, and inclusive workplace.',
            '3. Unwelcome physical, verbal, or non-verbal conduct of a sexual nature is prohibited.',
            '4. Complaints can be raised with HR or the Internal Committee without fear of retaliation.',
            '5. All complaints will be handled with confidentiality, fairness, and timely resolution.',
            '6. Awareness and sensitization are mandatory for all employees.',
        ]);
    }

    private function leavePolicyContentFromDatabase(): string
    {
        if (! Schema::hasTable('leave_policies')) {
            return 'Leave policy details are currently unavailable. Please contact HR.';
        }

        $policies = DB::table('leave_policies')
            ->orderBy('name')
            ->get(['name', 'code', 'annual_allowance', 'carry_forward', 'max_carry_forward', 'requires_approval', 'is_paid', 'active']);

        if ($policies->isEmpty()) {
            return 'No leave policies have been configured yet. Please contact HR for current leave rules.';
        }

        $lines = ['Configured leave types:'];

        foreach ($policies as $policy) {
            $lines[] = sprintf(
                '- %s (%s): %s days/year, %s, carry forward: %s (max %s), status: %s',
                $policy->name,
                $policy->code,
                (int) $policy->annual_allowance,
                $policy->is_paid ? 'Paid' : 'Unpaid',
                $policy->carry_forward ? 'Yes' : 'No',
                (int) $policy->max_carry_forward,
                $policy->active ? 'Active' : 'Inactive'
            );

            if ($policy->requires_approval) {
                $lines[] = '  Approval required: Yes';
            }
        }

        return implode("\n", $lines);
    }
};

