<?php

namespace App\Http\Controllers\Projects;

use App\Http\Controllers\Controller;
use App\Modules\Projects\Contracts\BillingInvoiceGatewayContract;
use App\Modules\Projects\Models\Project;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Throwable;

class ProjectFinancesController extends Controller
{
    public function __construct(
        private readonly BillingInvoiceGatewayContract $billingInvoiceGateway
    ) {
    }

    public function show(Project $project)
    {
        if ($project->is_internal) {
            return redirect()->route('projects.show', $project)
                ->withErrors(['finances' => 'Finances are disabled for internal projects.']);
        }

        $project->load([
            'zohoClient',
            'client',
            'projectManager.user',
            'accountManager.user',
            'revenueStreams',
        ]);

        $streams = $project->revenueStreams->sortByDesc('id')->values();

        $zohoInvoices = $this->billingInvoiceGateway->getProjectInvoices((string) $project->project_code);

        return view('projects.projects.finances.show', [
            'project' => $project,
            'streams' => $streams,
            'zohoInvoices' => $zohoInvoices,
        ]);
    }

    public function openZohoInvoice(Project $project, ZohoInvoice $zohoInvoice)
    {
        if ($project->is_internal) {
            return redirect()->route('projects.show', $project)
                ->withErrors(['finances' => 'Finances are disabled for internal projects.']);
        }

        if ((string) $zohoInvoice->project_id !== (string) $project->project_code) {
            abort(404);
        }

        try {
            $pdfBytes = $this->billingInvoiceGateway->downloadInvoicePdf((string) $zohoInvoice->zoho_invoice_id);
            $fileBase = $zohoInvoice->invoice_number ?: $zohoInvoice->zoho_invoice_id;
            $safeFileBase = preg_replace('/[^A-Za-z0-9\-_]+/', '_', (string) $fileBase);
            $filename = trim((string) $safeFileBase, '_') . '.pdf';

            return response($pdfBytes, 200, [
                'Content-Type' => 'application/pdf',
                'Content-Disposition' => 'inline; filename="' . ($filename !== '.pdf' ? $filename : 'zoho-invoice.pdf') . '"',
            ]);
        } catch (Throwable $e) {
            report($e);

            return redirect()
                ->route('projects.finances.show', $project)
                ->withErrors([
                    'zoho_invoice_open' => 'Unable to open Zoho invoice PDF: ' . $e->getMessage(),
                ]);
        }
    }

    public function radar(Request $request)
    {
        $now = Carbon::today();
        $requestedMonth = (string) $request->query('month', Carbon::now()->format('Y-m'));
        $monthToken = preg_match('/^\d{4}-\d{2}$/', $requestedMonth) ? $requestedMonth : Carbon::now()->format('Y-m');
        $monthStart = Carbon::createFromFormat('Y-m-d', $monthToken . '-01')->startOfMonth();
        $monthEnd = $monthStart->copy()->endOfMonth();
        $monthLabel = $monthStart->format('M Y');
        $isCurrentMonthView = $monthStart->isSameMonth(Carbon::now());
        $prevMonthToken = $monthStart->copy()->subMonth()->format('Y-m');
        $nextMonthToken = $monthStart->copy()->addMonth()->format('Y-m');
        $currentMonthStart = Carbon::now()->startOfMonth();
        $currentMonthEnd = Carbon::now()->endOfMonth();

        /** @var Collection<int, Project> $projects */
        $projects = Project::query()
            ->where('is_internal', false)
            ->with([
                'revenueStreams.invoices.payments',
                'revenueStreams.invoices',
                'revenueStreams.payments',
                'reimbursements',
                'zohoClient',
                'client',
                'projectManager.user',
                'accountManager.user',
            ])
            ->get();

        $totalExpectedDue = 0.0;
        $totalRetainerExpectedDue = 0.0;
        $totalOtherExpectedDue = 0.0;
        $totalInvoicedMonth = 0.0;
        $totalReceivedMonth = 0.0;
        $totalRetainerCollectable = 0.0;
        $totalOneTimePending = 0.0;
        $totalFixedMonthlyRetainers = 0.0;
        $totalFixedMonthlyInstallments = 0.0;
        $totalFixedMonthlyLifetimes = 0.0;

        $rows = collect();

        foreach ($projects as $project) {
            $streams = $project->revenueStreams->where('active', true)->values();

            $allInvoices = $streams->flatMap(fn ($s) => $s->invoices);
            $allPayments = $streams->flatMap(fn ($s) => $s->payments);

            $expectedDueThisMonth = 0.0;
            $retainerExpectedDueThisMonth = 0.0;
            $otherExpectedDueThisMonth = 0.0;

            foreach ($streams as $s) {
                if (!$s->next_billing_date) {
                    continue;
                }

                $next = Carbon::parse($s->next_billing_date);
                if (!$next->betweenIncluded($monthStart, $monthEnd)) {
                    continue;
                }

                $expected = (float) ($s->expected_total_value ?? 0);
                $expectedDueThisMonth += $expected;

                if (($s->type ?? '') === 'retainer') {
                    $retainerExpectedDueThisMonth += $expected;
                } else {
                    $otherExpectedDueThisMonth += $expected;
                }
            }

            $invoicedMonth = (float) $allInvoices
                ->filter(fn ($inv) => $inv->invoice_date && Carbon::parse($inv->invoice_date)->betweenIncluded($monthStart, $monthEnd))
                ->sum('amount');

            $receivedMonth = (float) $allPayments
                ->filter(fn ($p) => $p->payment_date && Carbon::parse($p->payment_date)->betweenIncluded($monthStart, $monthEnd))
                ->sum('amount');

            $projectInvoicedTotal = (float) $allInvoices->sum('amount');
            $projectReceivedTotal = (float) $allPayments->sum('amount');
            $projectPendingTotal = max(0, round($projectInvoicedTotal - $projectReceivedTotal, 2));

            $retainerStreams = $streams->filter(fn ($s) => ($s->type ?? '') === 'retainer');
            $retainerInvoicedTotal = (float) $retainerStreams->flatMap(fn ($s) => $s->invoices)->sum('amount');
            $retainerReceivedTotal = (float) $retainerStreams->flatMap(fn ($s) => $s->payments)->sum('amount');
            $retainerCollectable = max(0, round($retainerInvoicedTotal - $retainerReceivedTotal, 2));

            $fixedMonthlyRetainers = (float) $retainerStreams
                ->sum(fn ($s) => $this->normalizedMonthlyExpected((float) ($s->expected_total_value ?? 0), (string) ($s->billing_cycle ?? '')));
            $fixedMonthlyInstallments = (float) $streams
                ->filter(fn ($s) => ($s->type ?? '') === 'installment')
                ->sum(fn ($s) => $this->normalizedMonthlyExpected((float) ($s->expected_total_value ?? 0), (string) ($s->billing_cycle ?? '')));
            $fixedMonthlyLifetimes = (float) $streams
                ->filter(fn ($s) => ($s->type ?? '') === 'lifetime')
                ->sum(fn ($s) => $this->normalizedMonthlyExpected((float) ($s->expected_total_value ?? 0), (string) ($s->billing_cycle ?? 'lifetime')));

            $oneTimePending = ($project->project_type ?? '') === 'one_time'
                ? $projectPendingTotal
                : 0.0;

            $pendingInvoices = $allInvoices->filter(function ($inv) {
                if (!$inv->status) {
                    return true;
                }
                return !in_array($inv->status, ['paid', 'cancelled'], true);
            });

            $overdueInvoices = $allInvoices->filter(function ($inv) use ($now) {
                if ($inv->status === 'paid' || $inv->status === 'cancelled') {
                    return false;
                }
                if (!$inv->due_date) {
                    return false;
                }
                return Carbon::parse($inv->due_date)->lt($now);
            });

            $delayedInvoices = $allInvoices->filter(function ($inv) use ($now) {
                if ($inv->status === 'cancelled') {
                    return false;
                }
                if (!$inv->due_date) {
                    return false;
                }
                if (!Carbon::parse($inv->due_date)->lt($now)) {
                    return false;
                }

                // Delayed if invoice not yet paid OR it was paid after due-date.
                if ($inv->status !== 'paid') {
                    return true;
                }

                $maxPaymentDate = $inv->payments->max('payment_date');
                if (!$maxPaymentDate) {
                    return true;
                }

                return Carbon::parse($maxPaymentDate)->gt(Carbon::parse($inv->due_date));
            });

            $missingInvoiceStreamsCount = 0;
            foreach ($streams as $s) {
                if (!$s->next_billing_date) {
                    continue;
                }

                $next = Carbon::parse($s->next_billing_date);
                if (!$next->betweenIncluded($monthStart, $monthEnd)) {
                    continue;
                }

                $hasInvoiceForThisMonth = $s->invoices->contains(function ($inv) use ($monthStart, $monthEnd) {
                    if (!$inv->invoice_date) {
                        return false;
                    }
                    return $inv->status !== 'cancelled'
                        && Carbon::parse($inv->invoice_date)->betweenIncluded($monthStart, $monthEnd);
                });

                if (!$hasInvoiceForThisMonth) {
                    $missingInvoiceStreamsCount++;
                }
            }

            $atRisk = ($project->status ?? 'active') !== 'active'
                || $overdueInvoices->count() > 0
                || $pendingInvoices->count() > 0
                || $missingInvoiceStreamsCount > 0;

            $rows->push([
                'project' => $project,
                'expected_due_this_month' => $expectedDueThisMonth,
                'retainer_expected_due_this_month' => $retainerExpectedDueThisMonth,
                'other_expected_due_this_month' => $otherExpectedDueThisMonth,
                'invoiced_month' => $invoicedMonth,
                'received_month' => $receivedMonth,
                'retainer_collectable' => $retainerCollectable,
                'one_time_pending' => $oneTimePending,
                'pending_total' => $projectPendingTotal,
                'fixed_monthly_retainers' => round($fixedMonthlyRetainers, 2),
                'fixed_monthly_installments' => round($fixedMonthlyInstallments, 2),
                'fixed_monthly_lifetimes' => round($fixedMonthlyLifetimes, 2),
                'fixed_monthly_total' => round($fixedMonthlyRetainers + $fixedMonthlyInstallments + $fixedMonthlyLifetimes, 2),
                'pending_invoices_count' => $pendingInvoices->count(),
                'overdue_invoices_count' => $overdueInvoices->count(),
                'delayed_invoices_count' => $delayedInvoices->count(),
                'missing_invoice_streams_count' => $missingInvoiceStreamsCount,
                'at_risk' => $atRisk,
            ]);

            $totalExpectedDue += $expectedDueThisMonth;
            $totalRetainerExpectedDue += $retainerExpectedDueThisMonth;
            $totalOtherExpectedDue += $otherExpectedDueThisMonth;
            $totalInvoicedMonth += $invoicedMonth;
            $totalReceivedMonth += $receivedMonth;
            $totalRetainerCollectable += $retainerCollectable;
            $totalOneTimePending += $oneTimePending;
            $totalFixedMonthlyRetainers += $fixedMonthlyRetainers;
            $totalFixedMonthlyInstallments += $fixedMonthlyInstallments;
            $totalFixedMonthlyLifetimes += $fixedMonthlyLifetimes;
        }

        $rows = $rows->sortByDesc(function ($row) {
            return (int) $row['missing_invoice_streams_count'];
        })->values();

        $projectCodes = $projects
            ->pluck('project_code')
            ->filter(fn ($code) => is_string($code) && trim($code) !== '')
            ->values()
            ->all();

        $zohoInvoices = $this->billingInvoiceGateway->getInvoicesForProjectCodesBetween(
            $projectCodes,
            $monthStart->toDateString(),
            $monthEnd->toDateString()
        );
        $zohoTotalInvoices = (int) $zohoInvoices->count();
        $zohoTotalInvoiceAmount = (float) $zohoInvoices->sum('total');
        $zohoTotalBalance = (float) $zohoInvoices->sum('balance');
        $zohoTotalCollected = max(0, round($zohoTotalInvoiceAmount - $zohoTotalBalance, 2));
        $zohoOverdueCount = (int) $zohoInvoices
            ->filter(function ($inv) use ($now) {
                if (!$inv->due_date) {
                    return false;
                }

                $balance = (float) ($inv->balance ?? 0);
                return $balance > 0.001 && Carbon::parse($inv->due_date)->lt($now);
            })
            ->count();

        $currentMonthZohoInvoices = $this->billingInvoiceGateway->getInvoicesForProjectCodesBetween(
            $projectCodes,
            $currentMonthStart->toDateString(),
            $currentMonthEnd->toDateString()
        );

        $currentMonthInvoiceStats = [
            'invoice_count' => (int) $currentMonthZohoInvoices->count(),
            'invoice_amount' => round((float) $currentMonthZohoInvoices->sum('total'), 2),
            'balance' => round((float) $currentMonthZohoInvoices->sum('balance'), 2),
            'collected' => round(max(0, (float) $currentMonthZohoInvoices->sum('total') - (float) $currentMonthZohoInvoices->sum('balance')), 2),
        ];

        $projectByCode = $projects->keyBy('project_code');
        $clientSummaryMap = [];
        $categorySummaryMap = [];
        foreach ($zohoInvoices as $inv) {
            $mappedProject = $projectByCode->get((string) $inv->project_id);

            $clientLabel = 'Unmapped Client';
            $categoryLabel = 'Uncategorized';
            if ($mappedProject) {
                $clientLabel = $mappedProject->is_internal
                    ? 'Internal Project'
                    : ($mappedProject->zohoClient?->contact_name ?: ($mappedProject->zohoClient?->company_name ?: 'Unmapped Client'));
                $categoryLabel = trim((string) ($mappedProject->category ?? '')) ?: 'Uncategorized';
            }

            if (!isset($clientSummaryMap[$clientLabel])) {
                $clientSummaryMap[$clientLabel] = ['label' => $clientLabel, 'invoice_count' => 0, 'invoice_amount' => 0.0, 'balance' => 0.0, 'collected' => 0.0];
            }
            if (!isset($categorySummaryMap[$categoryLabel])) {
                $categorySummaryMap[$categoryLabel] = ['label' => $categoryLabel, 'invoice_count' => 0, 'invoice_amount' => 0.0, 'balance' => 0.0, 'collected' => 0.0];
            }

            $amount = (float) ($inv->total ?? 0);
            $balance = (float) ($inv->balance ?? 0);
            $collected = max(0, $amount - $balance);

            $clientSummaryMap[$clientLabel]['invoice_count']++;
            $clientSummaryMap[$clientLabel]['invoice_amount'] += $amount;
            $clientSummaryMap[$clientLabel]['balance'] += $balance;
            $clientSummaryMap[$clientLabel]['collected'] += $collected;

            $categorySummaryMap[$categoryLabel]['invoice_count']++;
            $categorySummaryMap[$categoryLabel]['invoice_amount'] += $amount;
            $categorySummaryMap[$categoryLabel]['balance'] += $balance;
            $categorySummaryMap[$categoryLabel]['collected'] += $collected;
        }

        $clientSummary = collect(array_values($clientSummaryMap))
            ->map(function (array $row) {
                $row['invoice_amount'] = round((float) $row['invoice_amount'], 2);
                $row['balance'] = round((float) $row['balance'], 2);
                $row['collected'] = round((float) $row['collected'], 2);
                return $row;
            })
            ->sortByDesc('invoice_amount')
            ->values();

        $categorySummary = collect(array_values($categorySummaryMap))
            ->map(function (array $row) {
                $row['invoice_amount'] = round((float) $row['invoice_amount'], 2);
                $row['balance'] = round((float) $row['balance'], 2);
                $row['collected'] = round((float) $row['collected'], 2);
                return $row;
            })
            ->sortByDesc('invoice_amount')
            ->values();

        $missingRows = $rows->filter(fn ($r) => $r['missing_invoice_streams_count'] > 0);
        $pendingRows = $rows->filter(fn ($r) => $r['pending_invoices_count'] > 0);
        $delayedRows = $rows->filter(fn ($r) => $r['delayed_invoices_count'] > 0);
        $atRiskRows = $rows->filter(fn ($r) => $r['at_risk']);

        return view('projects.projects.finances.radar', [
            'monthLabel' => $monthLabel,
            'totals' => [
                'expected_due' => $totalExpectedDue,
                'retainer_expected_due' => $totalRetainerExpectedDue,
                'other_expected_due' => $totalOtherExpectedDue,
                'invoiced_month' => $totalInvoicedMonth,
                'received_month' => $totalReceivedMonth,
                'retainer_collectable' => round($totalRetainerCollectable, 2),
                'one_time_pending' => round($totalOneTimePending, 2),
                'earnings_summary_total' => round($totalRetainerCollectable + $totalOneTimePending, 2),
                'fixed_monthly_retainers' => round($totalFixedMonthlyRetainers, 2),
                'fixed_monthly_installments' => round($totalFixedMonthlyInstallments, 2),
                'fixed_monthly_lifetimes' => round($totalFixedMonthlyLifetimes, 2),
                'fixed_monthly_total' => round($totalFixedMonthlyRetainers + $totalFixedMonthlyInstallments + $totalFixedMonthlyLifetimes, 2),
                'zoho_total_invoices' => $zohoTotalInvoices,
                'zoho_total_invoice_amount' => round($zohoTotalInvoiceAmount, 2),
                'zoho_total_balance' => round($zohoTotalBalance, 2),
                'zoho_total_collected' => round($zohoTotalCollected, 2),
                'zoho_overdue_count' => $zohoOverdueCount,
            ],
            'rows' => $rows,
            'notifications' => [
                'missing_invoices' => $missingRows,
                'pending_invoices' => $pendingRows,
                'delayed_payments' => $delayedRows,
                'at_risk_projects' => $atRiskRows,
            ],
            'monthContext' => [
                'selected_month' => $monthToken,
                'month_label' => $monthLabel,
                'prev_month' => $prevMonthToken,
                'next_month' => $nextMonthToken,
                'is_current_month_view' => $isCurrentMonthView,
            ],
            'currentMonthInvoiceStats' => $currentMonthInvoiceStats,
            'clientSummary' => $clientSummary,
            'categorySummary' => $categorySummary,
        ]);
    }

    private function normalizedMonthlyExpected(float $expectedValue, string $billingCycle): float
    {
        if ($expectedValue <= 0) {
            return 0.0;
        }

        return match ($billingCycle) {
            'annual', 'lifetime' => round($expectedValue / 12, 2),
            'quarterly' => round($expectedValue / 3, 2),
            'monthly' => round($expectedValue, 2),
            default => round($expectedValue, 2),
        };
    }
}

