<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Modules\HRMS\Employees\Models\EmployeeProfile;
use App\Modules\HRMS\Leaves\Models\LeavePolicy;
use App\Services\HRMS\AttendanceLeaveSummaryService;
use Illuminate\Support\Facades\Log;

class RecalculateLeaveBalances extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'hrms:recalculate-leaves 
                            {--year= : The year to recalculate for (default: current year)}
                            {--dry-run : Show what would be updated without making changes}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Recalculate leave balances for all employees based on their joining dates';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $year = $this->option('year') ? (int) $this->option('year') : now()->year;
        $dryRun = $this->option('dry-run');

        $this->info("Starting leave balance recalculation for year: {$year}");
        if ($dryRun) {
            $this->warn("DRY RUN MODE - No changes will be made");
        }

        $yearStart = now()->setYear($year)->startOfYear()->toDateString();
        $yearEnd = now()->setYear($year)->endOfYear()->toDateString();

        $employees = EmployeeProfile::query()
            ->where('status', 'active')
            ->get();

        $policies = LeavePolicy::query()->where('active', true)->get();
        $summaryService = app(AttendanceLeaveSummaryService::class);

        $totalEmployees = $employees->count();
        $processedCount = 0;
        $updatedCount = 0;

        $this->output->progressStart($totalEmployees);

        foreach ($employees as $employee) {
            $processedCount++;
            $joiningDate = $employee->joining_date ?? $employee->join_date;
            $joiningDateStr = $joiningDate ? $joiningDate->format('Y-m-d') : null;

            $employeeChanges = [];

            foreach ($policies as $policy) {
                if (!$policy->is_paid) {
                    continue;
                }

                $oldAllowance = (float) $policy->annual_allowance;
                $newAllowance = $summaryService->calculateProportionateAllowance(
                    $oldAllowance,
                    $joiningDateStr,
                    $yearStart,
                    $yearEnd
                );

                // Only track if there's a difference
                if (abs($oldAllowance - $newAllowance) > 0.01) {
                    $employeeChanges[] = [
                        'policy' => $policy->code,
                        'old_allowance' => $oldAllowance,
                        'new_allowance' => $newAllowance,
                    ];
                }
            }

            if (!empty($employeeChanges)) {
                $updatedCount++;
                $this->line("");
                $this->info("Employee: {$employee->employee_id} - {$employee->user?->name}");
                $this->info("  Joining Date: " . ($joiningDateStr ?? 'Not set'));
                
                foreach ($employeeChanges as $change) {
                    $this->line("  {$change['policy']}: {$change['old_allowance']} → {$change['new_allowance']} days");
                }

                if (!$dryRun) {
                    // Log the recalculation for audit purposes
                    Log::info('Leave balance recalculated', [
                        'employee_id' => $employee->employee_id,
                        'employee_name' => $employee->user?->name,
                        'joining_date' => $joiningDateStr,
                        'year' => $year,
                        'changes' => $employeeChanges,
                    ]);
                }
            }

            $this->output->progressAdvance();
        }

        $this->output->progressFinish();

        $this->info("");
        $this->info("Recalculation complete!");
        $this->info("Total employees processed: {$processedCount}");
        $this->info("Employees with updated allowances: {$updatedCount}");

        if ($dryRun) {
            $this->warn("This was a DRY RUN. No actual changes were made.");
            $this->warn("Run without --dry-run to apply the changes.");
        }

        return Command::SUCCESS;
    }
}
