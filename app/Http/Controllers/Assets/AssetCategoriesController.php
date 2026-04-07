<?php

namespace App\Http\Controllers\Assets;

use App\Http\Controllers\Controller;
use App\Modules\Assets\Models\AssetCategory;
use Illuminate\Http\Request;

class AssetCategoriesController extends Controller
{
    public function index()
    {
        $categories = AssetCategory::query()
            ->orderBy('name')
            ->paginate(25);

        return view('assets.categories.index', ['categories' => $categories]);
    }

    public function create()
    {
        return view('assets.categories.create');
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255', 'unique:asset_categories,name'],
            'description' => ['nullable', 'string', 'max:4000'],
            'active' => ['nullable'],
        ]);

        AssetCategory::query()->create([
            'name' => $data['name'],
            'description' => $data['description'] ?? null,
            'active' => (bool) ($data['active'] ?? true),
        ]);

        return redirect()->route('assets.categories.index')->with('status', 'Category created.');
    }

    public function edit(AssetCategory $assetCategory)
    {
        return view('assets.categories.edit', ['category' => $assetCategory]);
    }

    public function update(Request $request, AssetCategory $assetCategory)
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255', 'unique:asset_categories,name,' . $assetCategory->id],
            'description' => ['nullable', 'string', 'max:4000'],
            'active' => ['nullable'],
        ]);

        $assetCategory->update([
            'name' => $data['name'],
            'description' => $data['description'] ?? null,
            'active' => (bool) ($data['active'] ?? true),
        ]);

        return redirect()->route('assets.categories.index')->with('status', 'Category updated.');
    }

    public function destroy(AssetCategory $assetCategory)
    {
        if ($assetCategory->assets()->exists()) {
            return back()->withErrors(['category' => 'Cannot delete a category that has assets.']);
        }

        $assetCategory->delete();

        return redirect()->route('assets.categories.index')->with('status', 'Category deleted.');
    }
}
