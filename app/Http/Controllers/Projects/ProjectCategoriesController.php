<?php

namespace App\Http\Controllers\Projects;

use App\Http\Controllers\Controller;
use App\Modules\Projects\Models\Project;
use App\Modules\Projects\Models\ProjectCategory;
use Illuminate\Http\Request;

class ProjectCategoriesController extends Controller
{
    public function index()
    {
        $this->syncLegacyCategories();

        $categories = ProjectCategory::query()
            ->orderBy('name')
            ->paginate(30);

        return view('projects.categories.index', ['categories' => $categories]);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:64'],
            'active' => ['nullable'],
        ]);

        $name = trim((string) $data['name']);

        $exists = ProjectCategory::query()
            ->whereRaw('LOWER(name) = ?', [mb_strtolower($name)])
            ->first();

        if ($exists) {
            return back()->withErrors(['name' => 'Category already exists.']);
        }

        ProjectCategory::query()->create([
            'name' => $name,
            'active' => (bool) ($data['active'] ?? true),
        ]);

        return redirect()->route('projects.categories.index')->with('status', 'Category added.');
    }

    public function update(Request $request, ProjectCategory $projectCategory)
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:64'],
            'active' => ['nullable'],
        ]);

        $name = trim((string) $data['name']);

        $exists = ProjectCategory::query()
            ->where('id', '!=', $projectCategory->id)
            ->whereRaw('LOWER(name) = ?', [mb_strtolower($name)])
            ->exists();

        if ($exists) {
            return back()->withErrors(['name' => 'Category already exists.']);
        }

        $projectCategory->update([
            'name' => $name,
            'active' => (bool) ($data['active'] ?? false),
        ]);

        return redirect()->route('projects.categories.index')->with('status', 'Category updated.');
    }

    private function syncLegacyCategories(): void
    {
        $legacyCategories = Project::query()
            ->select('category')
            ->whereNotNull('category')
            ->distinct()
            ->pluck('category')
            ->map(fn ($value) => trim((string) $value))
            ->filter(fn ($value) => $value !== '')
            ->values();

        foreach ($legacyCategories as $name) {
            $exists = ProjectCategory::query()
                ->whereRaw('LOWER(name) = ?', [mb_strtolower($name)])
                ->exists();

            if (!$exists) {
                ProjectCategory::query()->create([
                    'name' => $name,
                    'active' => true,
                ]);
            }
        }
    }
}

