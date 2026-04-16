<?php

namespace App\Http\Controllers\Systems;

use App\Http\Controllers\Controller;
use App\Modules\Systems\Models\SupportScope;
use Illuminate\Http\Request;

class SupportScopesController extends Controller
{
    public function index()
    {
        return view('systems.support_scopes.index', [
            'scopes' => SupportScope::query()->orderBy('scope_name')->paginate(25),
        ]);
    }

    public function create()
    {
        return view('systems.support_scopes.create');
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'scope_name' => ['required', 'string', 'max:150', 'unique:support_scopes,scope_name'],
            'description' => ['nullable', 'string', 'max:20000'],
            'included_services' => ['nullable', 'string', 'max:20000'],
            'excluded_services' => ['nullable', 'string', 'max:20000'],
            'sla_response_time' => ['nullable', 'string', 'max:120'],
            'active' => ['nullable', 'boolean'],
        ]);

        SupportScope::query()->create([
            'scope_name' => trim((string) $data['scope_name']),
            'description' => $data['description'] ?? null,
            'included_services' => $data['included_services'] ?? null,
            'excluded_services' => $data['excluded_services'] ?? null,
            'sla_response_time' => $data['sla_response_time'] ?? null,
            'active' => (bool) ($data['active'] ?? true),
        ]);

        return redirect()->route('systems.support_scopes.index')->with('status', 'Support scope created.');
    }

    public function edit(SupportScope $scope)
    {
        return view('systems.support_scopes.edit', ['scope' => $scope]);
    }

    public function update(Request $request, SupportScope $scope)
    {
        $data = $request->validate([
            'scope_name' => ['required', 'string', 'max:150', 'unique:support_scopes,scope_name,' . $scope->id],
            'description' => ['nullable', 'string', 'max:20000'],
            'included_services' => ['nullable', 'string', 'max:20000'],
            'excluded_services' => ['nullable', 'string', 'max:20000'],
            'sla_response_time' => ['nullable', 'string', 'max:120'],
            'active' => ['nullable', 'boolean'],
        ]);

        $scope->update([
            'scope_name' => trim((string) $data['scope_name']),
            'description' => $data['description'] ?? null,
            'included_services' => $data['included_services'] ?? null,
            'excluded_services' => $data['excluded_services'] ?? null,
            'sla_response_time' => $data['sla_response_time'] ?? null,
            'active' => (bool) ($data['active'] ?? false),
        ]);

        return redirect()->route('systems.support_scopes.index')->with('status', 'Support scope updated.');
    }
}
