<?php

namespace App\Console\Commands;

use App\Models\User;
use App\Modules\HRMS\Employees\Models\EmployeeProfile;
use App\Modules\HRMS\Payroll\Models\PayrollRun;
use App\Modules\HRMS\Payroll\Models\SalarySlip;
use App\Modules\HRMS\Reimbursements\Models\ReimbursementRequest;
use App\Services\HRMS\AttendanceLeaveSummaryService;
use App\Services\HRMS\SalarySlipGenerationService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class RegenerateSalarySlipsCommand extends Command
{
    protected $signature = 'hrms:salary-slips:regenerate
                            {--payroll-run= : Only this payroll run ID}
                            {--dry-run : Show what would happen without writing}
                            {--no-mail : Do not send salary slip emails}
                            {--user= : User ID to record as processed_by (default: first user)}';

    protected $description = 'Delete all salary slips (optionally one payroll run), revert linked reimbursements, and regenerate using current salary rules while preserving extra earnings/deduction lines.';

    public function handle(
        AttendanceLeaveSummaryService $summaryService,
        SalarySlipGenerationService $slipGeneration
    ): int {
        $dryRun = (bool) $this->option('dry-run');
        $noMail = (bool) $this->option('no-mail');
        $runFilter = $this->option('payroll-run');

        $query = SalarySlip::query()->orderBy('payroll_run_id')->orderBy('id');
        if ($runFilter !== null && $runFilter !== '') {
            $query->where('payroll_run_id', (int) $runFilter);
        }

        $slips = $query->get();
        if ($slips->isEmpty()) {
            $this->info('No salary slips found.');

            return self::SUCCESS;
        }

        $processedByUserId = $this->resolveProcessedByUserId();
        if ($processedByUserId === null) {
            return self::FAILURE;
        }

        /** @var array<int, list<array{slipInput: array, payroll_run_id: int}>> $byRun */
        $byRun = [];
        foreach ($slips as $slip) {
            $runId = (int) $slip->payroll_run_id;
            if (! isset($byRun[$runId])) {
                $byRun[$runId] = [];
            }
            $byRun[$runId][] = [
                'slipInput' => SalarySlipGenerationService::slipInputFromExisting($slip),
                'payroll_run_id' => $runId,
            ];
        }

        $this->info('Slips to replace: '.$slips->count().' across '.count($byRun).' payroll run(s).');
        if ($dryRun) {
            $this->warn('Dry run — no changes made.');

            return self::SUCCESS;
        }

        $slipIds = $slips->pluck('id')->all();

        DB::transaction(function () use ($slips, $slipIds, $slipGeneration, $summaryService, $byRun, $processedByUserId, $noMail): void {
            // Unlink reimbursements paid on these slips so they can match again after regeneration.
            ReimbursementRequest::query()
                ->whereIn('salary_slip_id', $slipIds)
                ->update([
                    'salary_slip_id' => null,
                    'paid_amount' => 0,
                    'last_paid_at' => null,
                    'status' => 'approved',
                ]);

            foreach ($slips as $slip) {
                $path = $slip->file_path;
                if (is_string($path) && $path !== '' && Storage::disk('local')->exists($path)) {
                    Storage::disk('local')->delete($path);
                }
            }

            SalarySlip::query()->whereIn('id', $slipIds)->delete();

            $runIds = array_keys($byRun);
            PayrollRun::query()->whereIn('id', $runIds)->update([
                'status' => 'draft',
                'processed_at' => null,
                'processed_by_user_id' => null,
            ]);

            foreach ($byRun as $runId => $items) {
                $run = PayrollRun::query()->findOrFail($runId);
                foreach ($items as $item) {
                    $input = $item['slipInput'];
                    $emp = EmployeeProfile::query()->find($input['employee_profile_id']);
                    if (! $emp) {
                        continue;
                    }
                    $slipGeneration->createSlipFromInput(
                        $run,
                        $emp,
                        $input,
                        $summaryService,
                        'INR',
                        ! $noMail
                    );
                }

                $run->refresh();
                if ($run->salarySlips()->exists()) {
                    $run->update([
                        'status' => 'processed',
                        'processed_by_user_id' => $processedByUserId,
                        'processed_at' => now(),
                    ]);
                }
            }
        });

        $this->info('Salary slips regenerated successfully.');

        return self::SUCCESS;
    }

    private function resolveProcessedByUserId(): ?int
    {
        $opt = $this->option('user');
        if ($opt !== null && $opt !== '') {
            $id = (int) $opt;
            if (! User::query()->whereKey($id)->exists()) {
                $this->error("User ID {$id} does not exist.");

                return null;
            }

            return $id;
        }

        $first = User::query()->orderBy('id')->value('id');

        return $first !== null ? (int) $first : 1;
    }
}
