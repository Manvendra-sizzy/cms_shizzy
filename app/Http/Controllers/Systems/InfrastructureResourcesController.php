<?php

namespace App\Http\Controllers\Systems;

use App\Http\Controllers\Controller;
use App\Modules\Systems\Models\InfrastructureResource;
use Illuminate\Http\Request;

class InfrastructureResourcesController extends Controller
{
    private const RESOURCE_TYPES = [
        'server',
        'cdn',
        'object_storage',
        'database',
        'email_sms',
        'domain_dns',
    ];

    private const RESOURCE_STATUSES = ['active', 'maintenance', 'inactive'];

    public function index(Request $request)
    {
        $query = InfrastructureResource::query()->orderBy('resource_type')->orderBy('name');

        if ($request->filled('resource_type')) {
            $query->where('resource_type', (string) $request->query('resource_type'));
        }

        return view('systems.infrastructure.index', [
            'resources' => $query->paginate(25)->withQueryString(),
            'resourceTypes' => self::RESOURCE_TYPES,
        ]);
    }

    public function create()
    {
        return view('systems.infrastructure.create', [
            'resourceTypes' => self::RESOURCE_TYPES,
            'statuses' => self::RESOURCE_STATUSES,
        ]);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'resource_type' => ['required', 'string', 'in:' . implode(',', self::RESOURCE_TYPES)],
            'name' => ['required', 'string', 'max:160'],
            'vendor' => ['nullable', 'string', 'max:120'],
            'description' => ['nullable', 'string', 'max:20000'],
            'access_url' => ['nullable', 'url', 'max:512'],
            'status' => ['required', 'string', 'in:' . implode(',', self::RESOURCE_STATUSES)],
        ]);

        InfrastructureResource::query()->create($data);

        return redirect()->route('systems.infrastructure.index')->with('status', 'Infrastructure resource created.');
    }

    public function edit(InfrastructureResource $resource)
    {
        return view('systems.infrastructure.edit', [
            'resource' => $resource,
            'resourceTypes' => self::RESOURCE_TYPES,
            'statuses' => self::RESOURCE_STATUSES,
        ]);
    }

    public function update(Request $request, InfrastructureResource $resource)
    {
        $data = $request->validate([
            'resource_type' => ['required', 'string', 'in:' . implode(',', self::RESOURCE_TYPES)],
            'name' => ['required', 'string', 'max:160'],
            'vendor' => ['nullable', 'string', 'max:120'],
            'description' => ['nullable', 'string', 'max:20000'],
            'access_url' => ['nullable', 'url', 'max:512'],
            'status' => ['required', 'string', 'in:' . implode(',', self::RESOURCE_STATUSES)],
        ]);

        $resource->update($data);

        return redirect()->route('systems.infrastructure.index')->with('status', 'Infrastructure resource updated.');
    }
}
