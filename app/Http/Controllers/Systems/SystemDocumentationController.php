<?php

namespace App\Http\Controllers\Systems;

use App\Http\Controllers\Controller;
use App\Modules\Systems\Models\System as SystemModel;
use Illuminate\Http\Request;

class SystemDocumentationController extends Controller
{
    public function update(Request $request, SystemModel $system)
    {
        $data = $request->validate([
            'overview' => ['nullable', 'string'],
            'architecture' => ['nullable', 'string'],
            'infrastructure_mapping' => ['nullable', 'string'],
            'deployment_process' => ['nullable', 'string'],
            'recovery_instructions' => ['nullable', 'string'],
            'external_integrations' => ['nullable', 'string'],
            'notes' => ['nullable', 'string'],
        ]);

        $documentation = $system->documentation()->firstOrCreate([], []);
        $documentation->update($data);

        return redirect()->route('systems.show', $system)->with('status', 'Documentation updated.');
    }
}
