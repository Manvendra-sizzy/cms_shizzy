<?php

namespace App\Http\Controllers\Systems;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Modules\Projects\Models\Project;
use App\Modules\Systems\Models\InfrastructureResource;
use App\Modules\Systems\Models\SupportScope;
use App\Modules\Systems\Models\System as SystemModel;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class SystemsController extends Controller
{
    private const SYSTEM_TYPES = ['wordpress', 'laravel', 'react', 'codeignitor', 'flutter', 'tool', 'infra'];
    private const SYSTEM_STATUSES = ['active', 'maintenance', 'archived', 'sunset'];
    private const SUPPORT_STATUSES = ['inactive', 'active', 'expired', 'on_hold'];

    public function index(Request $request)
    {
        /** @var User|null $user */
        $user = $request->user();
        $scopeIds = $user?->systemScopeIds();

        $query = SystemModel::query()->with(['project', 'supportScope'])->orderByDesc('id');

        if (is_array($scopeIds)) {
            if ($scopeIds === []) {
                $query->whereRaw('1 = 0');
            } else {
                $query->whereIn('id', $scopeIds);
            }
        }

        if ($request->filled('project_id')) {
            $query->where('project_id', (int) $request->query('project_id'));
        }
        if ($request->filled('status')) {
            $query->where('status', (string) $request->query('status'));
        }
        if ($search = trim((string) $request->query('q', ''))) {
            $query->where(function ($q) use ($search) {
                $q->where('system_name', 'like', '%' . $search . '%')
                    ->orWhere('system_type', 'like', '%' . $search . '%')
                    ->orWhere('tech_stack', 'like', '%' . $search . '%');
            });
        }

        return view('systems.systems.index', [
            'systems' => $query->paginate(20)->withQueryString(),
            'projects' => Project::query()->orderBy('name')->get(['id', 'name', 'project_code']),
            'statuses' => self::SYSTEM_STATUSES,
        ]);
    }

    public function create()
    {
        return view('systems.systems.create', $this->formData());
    }

    public function store(Request $request)
    {
        $data = $this->validatePayload($request, null);

        $system = SystemModel::query()->create($this->systemAttributes($data));
        $system->infrastructureResources()->sync($data['infrastructure_resource_ids'] ?? []);
        $system->documentation()->create([]);

        return redirect()->route('systems.show', $system)->with('status', 'System created.');
    }

    public function show(SystemModel $system)
    {
        $system->load([
            'project',
            'supportScope',
            'infrastructureResources',
            'documentation',
            'supportExtensions.extendedBy',
            'developmentLogs.changedBy',
        ]);

        return view('systems.systems.show', [
            'system' => $system,
            'developmentLogs' => $system->developmentLogs()->orderByDesc('change_date')->orderByDesc('id')->get(),
            'supportExtensions' => $system->supportExtensions()->orderByDesc('extended_at')->orderByDesc('id')->get(),
            'changeTypes' => SystemDevelopmentLogsController::changeTypes(),
            'deploymentStatuses' => SystemDevelopmentLogsController::deploymentStatuses(),
            'supportStatuses' => self::SUPPORT_STATUSES,
        ]);
    }

    public function edit(SystemModel $system)
    {
        $system->load('infrastructureResources');

        return view('systems.systems.edit', array_merge(
            $this->formData(),
            ['system' => $system]
        ));
    }

    public function update(Request $request, SystemModel $system)
    {
        $data = $this->validatePayload($request, $system);

        $existingSupportEndDate = optional($system->support_end_date)->toDateString();
        $incomingSupportEndDate = $data['support_end_date'] ?? null;
        if (
            $existingSupportEndDate !== null
            && $incomingSupportEndDate !== null
            && $incomingSupportEndDate !== $existingSupportEndDate
        ) {
            return back()
                ->withErrors(['support_end_date' => 'Use Extend Support action to change support end date.'])
                ->withInput();
        }

        $system->update($this->systemAttributes($data, $system));
        $system->infrastructureResources()->sync($data['infrastructure_resource_ids'] ?? []);

        return redirect()->route('systems.show', $system)->with('status', 'System updated.');
    }

    private function formData(): array
    {
        return [
            'projects' => Project::query()->orderBy('name')->get(['id', 'name', 'project_code']),
            'supportScopes' => SupportScope::query()->where('active', true)->orderBy('scope_name')->get(),
            'infrastructureResources' => InfrastructureResource::query()->where('status', 'active')->orderBy('name')->get(),
            'systemTypes' => self::SYSTEM_TYPES,
            'systemStatuses' => self::SYSTEM_STATUSES,
            'supportStatuses' => self::SUPPORT_STATUSES,
        ];
    }

    private function validatePayload(Request $request, ?SystemModel $system): array
    {
        $rules = [
            'project_id' => ['required', 'exists:projects,id'],
            'support_scope_id' => ['nullable', 'exists:support_scopes,id'],
            'system_name' => ['required', 'string', 'max:180'],
            'system_type' => ['required', 'string', 'in:' . implode(',', self::SYSTEM_TYPES)],
            'description' => ['nullable', 'string', 'max:20000'],
            'live_url' => ['nullable', 'url', 'max:512'],
            'admin_url' => ['nullable', 'url', 'max:512'],
            'repository_link' => ['nullable', 'url', 'max:512'],
            'tech_stack' => ['nullable', 'string', 'max:255'],
            'status' => ['required', 'string', 'in:' . implode(',', self::SYSTEM_STATUSES)],
            'support_start_date' => ['nullable', 'date'],
            'support_end_date' => ['nullable', 'date', 'after_or_equal:support_start_date'],
            'support_status' => ['required', 'string', 'in:' . implode(',', self::SUPPORT_STATUSES)],
            'infrastructure_resource_ids' => ['nullable', 'array'],
            'infrastructure_resource_ids.*' => ['integer', 'exists:infrastructure_resources,id'],
        ];

        $data = $request->validate($rules);

        $duplicateQuery = SystemModel::query()
            ->where('project_id', (int) $data['project_id'])
            ->whereRaw('LOWER(system_name) = ?', [mb_strtolower((string) $data['system_name'])]);
        if ($system) {
            $duplicateQuery->where('id', '!=', $system->id);
        }
        if ($duplicateQuery->exists()) {
            throw ValidationException::withMessages([
                'system_name' => 'System name already exists for this project.',
            ]);
        }

        return $data;
    }

    private function systemAttributes(array $data, ?SystemModel $system = null): array
    {
        $attributes = [
            'project_id' => (int) $data['project_id'],
            'support_scope_id' => $data['support_scope_id'] ?? null,
            'system_name' => trim((string) $data['system_name']),
            'system_type' => (string) $data['system_type'],
            'description' => $data['description'] ?? null,
            'live_url' => $data['live_url'] ?? null,
            'admin_url' => $data['admin_url'] ?? null,
            'repository_link' => $data['repository_link'] ?? null,
            'tech_stack' => $data['tech_stack'] ?? null,
            'status' => (string) $data['status'],
            'support_start_date' => $data['support_start_date'] ?? null,
            'support_status' => (string) $data['support_status'],
        ];

        if (! $system || $system->support_end_date === null) {
            $attributes['support_end_date'] = $data['support_end_date'] ?? null;
        }

        return $attributes;
    }
}
