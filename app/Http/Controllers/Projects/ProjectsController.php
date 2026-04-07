<?php

namespace App\Http\Controllers\Projects;

use App\Http\Controllers\Controller;
use App\Models\ZohoInvoice;
use App\Models\User;
use App\Modules\Projects\Contracts\EmployeeDirectoryContract;
use App\Modules\Projects\Contracts\ZohoClientDirectoryContract;
use App\Modules\Projects\Models\Project;
use App\Modules\Projects\Models\ProjectCategory;
use App\Modules\Projects\Models\ProjectStatusLog;
use App\Modules\Projects\Models\ProjectRevenueStream;
use App\Modules\Projects\Models\ProjectReimbursement;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class ProjectsController extends Controller
{
    public function __construct(
        private readonly ZohoClientDirectoryContract $zohoClientDirectory,
        private readonly EmployeeDirectoryContract $employeeDirectory
    ) {
    }

    public const CATEGORIES = [
        'Social Media',
        'SEO',
        'Online Advertising',
        'Web Development',
        'Tech Support',
        'Hosting',
    ];

    public function index()
    {
        $projects = Project::query()
            ->with(['zohoClient', 'client', 'projectManager.user', 'accountManager.user'])
            ->orderByRaw("
                CASE
                    WHEN status = 'active' THEN 1
                    WHEN status IN ('hold', 'on_hold') THEN 2
                    WHEN status = 'delivered' THEN 3
                    WHEN status = 'cancelled' THEN 4
                    ELSE 5
                END
            ")
            ->orderByDesc('id')
            ->paginate(20);

        return view('projects.projects.index', ['projects' => $projects]);
    }

    public function create()
    {
        $clients = $this->zohoClientDirectory->getSelectableClients();
        $employees = $this->employeeDirectory->getActiveEmployees();

        return view('projects.projects.create', [
            'clients' => $clients,
            'employees' => $employees,
            'categories' => $this->categoryOptions(),
            'selectedClientId' => request('zoho_client_id', ''),
        ]);
    }

    public function store(Request $request)
    {
        /** @var User $user */
        $user = Auth::user();

        $data = $request->validate([
            'zoho_client_id' => ['required', 'string', 'max:32'],
            'name' => ['required', 'string', 'max:255'],
            'category' => ['required', 'string', 'max:64'],
            'project_type' => ['required', 'string', 'in:one_time,recurring'],
            'billing_type' => ['required', 'string', 'in:fixed,prorata'],
            'description' => ['nullable', 'string', 'max:8000'],
            'project_manager_employee_profile_id' => ['nullable', 'exists:employee_profiles,id'],
            'account_manager_employee_profile_id' => ['nullable', 'exists:employee_profiles,id'],
            'project_folder' => ['nullable', 'string', 'max:255'],
        ]);

        $nextNumber = (int) (DB::table('projects')
                ->selectRaw("COALESCE(MAX(CAST(SUBSTRING(project_code, 3) AS UNSIGNED)), 0) as max_num")
                ->value('max_num')) + 1;
        $projectCode = 'SZ' . str_pad((string) $nextNumber, 3, '0', STR_PAD_LEFT);

        $selectedClient = (string) $data['zoho_client_id'];
        $isInternal = $selectedClient === '__internal__';
        $zohoClientId = null;
        if (!$isInternal) {
            $zohoClientId = (int) $selectedClient;
            $zohoExists = $this->zohoClientDirectory->existsById($zohoClientId);
            if (!$zohoExists) {
                return back()->withErrors(['zoho_client_id' => 'Please select a valid Zoho client.'])->withInput();
            }
        }

        $project = Project::query()->create([
            'project_client_id' => null,
            'zoho_client_id' => $zohoClientId,
            'is_internal' => $isInternal,
            'project_code' => $projectCode,
            'name' => $data['name'],
            'category' => $data['category'],
            'project_type' => $data['project_type'],
            'billing_type' => $data['billing_type'],
            'description' => $data['description'] ?? null,
            'project_manager_employee_profile_id' => $data['project_manager_employee_profile_id'] ?? null,
            'account_manager_employee_profile_id' => $data['account_manager_employee_profile_id'] ?? null,
            'project_folder' => $data['project_folder'] ?? null,
            'status' => 'active',
            'created_by_user_id' => $user?->id,
        ]);

        ProjectStatusLog::query()->create([
            'project_id' => $project->id,
            'from_status' => null,
            'to_status' => 'active',
            'effective_date' => Carbon::today()->toDateString(),
            'remark' => 'Project created.',
            'changed_by_user_id' => $user?->id,
        ]);

        return redirect()->route('projects.show', $project)->with('status', "Project created ({$projectCode}).");
    }

    public function show(Project $project)
    {
        $project->load([
            'zohoClient',
            'client',
            'projectManager.user',
            'accountManager.user',
            'statusLogs.changedBy',
            'teamMembers.employeeProfile.user',
            'revenueStreams.invoices.payments',
            'revenueStreams.payments',
            'reimbursements',
        ]);

        $employees = $this->employeeDirectory->getActiveEmployees();

        $logs = $project->statusLogs()->orderByDesc('effective_date')->orderByDesc('id')->get();

        // Financial summary (aggregated across revenue streams).
        $streams = $project->revenueStreams ?? collect();
        $expectedTotal = (float) $streams->sum(fn (ProjectRevenueStream $s) => (float) ($s->expected_total_value ?? 0));
        $invoicedTotal = (float) $streams->sum(fn (ProjectRevenueStream $s) => (float) $s->invoices->sum('amount'));
        $receivedTotal = (float) $streams->sum(fn (ProjectRevenueStream $s) => (float) $s->payments->sum('amount'));
        $pendingExpected = max(0, round($expectedTotal - $receivedTotal, 2));
        $pendingInvoiced = max(0, round($invoicedTotal - $receivedTotal, 2));

        $today = Carbon::today();
        $overdueInvoices = [];
        $pendingInvoices = [];
        foreach ($streams as $s) {
            foreach ($s->invoices as $inv) {
                $invPaid = (float) $inv->payments->sum('amount');
                $invPending = max(0, round(((float) $inv->amount) - $invPaid, 2));
                $isPaid = ($inv->status === 'paid') || ($invPending <= 0.001);
                if (!$isPaid) {
                    $pendingInvoices[] = $inv;
                    if ($inv->due_date && Carbon::parse($inv->due_date)->lt($today)) {
                        $overdueInvoices[] = $inv;
                    }
                }
            }
        }

        $upcomingBilling = $streams
            ->filter(fn (ProjectRevenueStream $s) => $s->next_billing_date && Carbon::parse($s->next_billing_date)->betweenIncluded($today, $today->copy()->addDays(7)))
            ->values();

        $unrecoveredReimbursements = ($project->reimbursements ?? collect())
            ->filter(fn (ProjectReimbursement $r) => $r->status !== 'recovered')
            ->values();

        $zohoInvoices = ZohoInvoice::query()
            ->where('project_id', $project->project_code)
            ->orderByDesc('invoice_date')
            ->orderByDesc('id')
            ->get();

        return view('projects.projects.show', [
            'project' => $project,
            'logs' => $logs,
            'employees' => $employees,
            'categories' => $this->categoryOptions(),
            'financialSummary' => [
                'expected_total' => round($expectedTotal, 2),
                'invoiced_total' => round($invoicedTotal, 2),
                'received_total' => round($receivedTotal, 2),
                'pending_expected' => $pendingExpected,
                'pending_invoiced' => $pendingInvoiced,
            ],
            'alerts' => [
                'overdue_invoices' => $overdueInvoices,
                'pending_invoices' => $pendingInvoices,
                'upcoming_billing' => $upcomingBilling,
                'unrecovered_reimbursements' => $unrecoveredReimbursements,
            ],
            'zohoInvoices' => $zohoInvoices,
        ]);
    }

    public function edit(Project $project)
    {
        $clients = $this->zohoClientDirectory->getSelectableClients();
        $employees = $this->employeeDirectory->getActiveEmployees();

        return view('projects.projects.edit', [
            'project' => $project,
            'clients' => $clients,
            'employees' => $employees,
            'categories' => $this->categoryOptions(),
        ]);
    }

    public function update(Request $request, Project $project)
    {
        $data = $request->validate([
            'zoho_client_id' => ['required', 'string', 'max:32'],
            'name' => ['required', 'string', 'max:255'],
            'category' => ['required', 'string', 'max:64'],
            'project_type' => ['required', 'string', 'in:one_time,recurring'],
            'billing_type' => ['required', 'string', 'in:fixed,prorata'],
            'description' => ['nullable', 'string', 'max:8000'],
            'project_manager_employee_profile_id' => ['nullable', 'exists:employee_profiles,id'],
            'account_manager_employee_profile_id' => ['nullable', 'exists:employee_profiles,id'],
            'project_folder' => ['nullable', 'string', 'max:255'],
        ]);

        $selectedClient = (string) $data['zoho_client_id'];
        $isInternal = $selectedClient === '__internal__';
        $zohoClientId = null;
        if (!$isInternal) {
            $zohoClientId = (int) $selectedClient;
            $zohoExists = $this->zohoClientDirectory->existsById($zohoClientId);
            if (!$zohoExists) {
                return back()->withErrors(['zoho_client_id' => 'Please select a valid Zoho client.'])->withInput();
            }
        }

        $project->update([
            'project_client_id' => null,
            'zoho_client_id' => $zohoClientId,
            'is_internal' => $isInternal,
            'name' => $data['name'],
            'category' => $data['category'],
            'project_type' => $data['project_type'],
            'billing_type' => $data['billing_type'],
            'description' => $data['description'] ?? null,
            'project_manager_employee_profile_id' => $data['project_manager_employee_profile_id'] ?? null,
            'account_manager_employee_profile_id' => $data['account_manager_employee_profile_id'] ?? null,
            'project_folder' => $data['project_folder'] ?? null,
        ]);

        return redirect()->route('projects.show', $project)->with('status', 'Project updated.');
    }

    private function categoryOptions(): array
    {
        $managedCategories = ProjectCategory::query()
            ->where('active', true)
            ->orderBy('name')
            ->pluck('name')
            ->map(fn ($value) => trim((string) $value))
            ->filter(fn ($value) => $value !== '')
            ->values()
            ->all();

        $dbCategories = Project::query()
            ->select('category')
            ->whereNotNull('category')
            ->distinct()
            ->pluck('category')
            ->map(fn ($value) => trim((string) $value))
            ->filter(fn ($value) => $value !== '')
            ->values()
            ->all();

        $categories = array_values(array_unique(array_merge($managedCategories, self::CATEGORIES, $dbCategories)));
        sort($categories, SORT_NATURAL | SORT_FLAG_CASE);

        return $categories;
    }
}
