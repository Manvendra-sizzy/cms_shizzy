<?php

namespace App\Http\Controllers\Assets;

use App\Http\Controllers\Controller;
use App\Modules\Assets\Models\Asset;
use App\Modules\Assets\Models\AssetCategory;
use App\Modules\Assets\Models\AssetAssignment;
use App\Modules\HRMS\Employees\Models\EmployeeProfile;
use Illuminate\Http\Request;

class AssetsController extends Controller
{
    public function index(Request $request)
    {
        $query = Asset::query()->with('category');

        if ($request->filled('category_id')) {
            $query->where('asset_category_id', (int) $request->category_id);
        }
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }
        if ($search = $request->get('q')) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', '%' . $search . '%')
                    ->orWhere('asset_code', 'like', '%' . $search . '%')
                    ->orWhere('serial_number', 'like', '%' . $search . '%');
            });
        }

        $assets = $query->orderByDesc('id')->paginate(25)->withQueryString();

        $categories = AssetCategory::query()->where('active', true)->orderBy('name')->get();

        return view('assets.assets.index', [
            'assets' => $assets,
            'categories' => $categories,
        ]);
    }

    public function create()
    {
        $categories = AssetCategory::query()->where('active', true)->orderBy('name')->get();

        return view('assets.assets.create', ['categories' => $categories]);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'asset_category_id' => ['required', 'exists:asset_categories,id'],
            'name' => ['required', 'string', 'max:255'],
            'condition' => ['nullable', 'string', 'max:64'],
            'asset_code' => ['nullable', 'string', 'max:128', 'unique:assets,asset_code'],
            'serial_number' => ['nullable', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:8000'],
            'purchase_date' => ['nullable', 'date'],
            'purchase_value' => ['nullable', 'numeric', 'min:0'],
            'status' => ['nullable', 'string', 'max:32'],
        ]);

        Asset::query()->create([
            'asset_category_id' => (int) $data['asset_category_id'],
            'name' => $data['name'],
            'condition' => $data['condition'] ?? null,
            'asset_code' => $data['asset_code'] ?? null,
            'serial_number' => $data['serial_number'] ?? null,
            'description' => $data['description'] ?? null,
            'purchase_date' => $data['purchase_date'] ?? null,
            'purchase_value' => $data['purchase_value'] ?? null,
            'status' => $data['status'] ?? 'in_stock',
        ]);

        return redirect()->route('assets.index')->with('status', 'Asset created.');
    }

    public function show(Asset $asset)
    {
        $asset->load(['category', 'assignments.employeeProfile.user', 'assignments.createdBy']);

        $currentAssignment = $asset->assignments
            ->whereNull('returned_at')
            ->sortByDesc('assigned_at')
            ->first();

        $employees = EmployeeProfile::query()
            ->with('user')
            ->where('status', 'active')
            ->orderBy('employee_id')
            ->get();

        $history = $asset->assignments()
            ->orderByDesc('assigned_at')
            ->orderByDesc('id')
            ->get();

        return view('assets.assets.show', [
            'asset' => $asset,
            'currentAssignment' => $currentAssignment,
            'employees' => $employees,
            'history' => $history,
        ]);
    }

    public function edit(Asset $asset)
    {
        $categories = AssetCategory::query()->where('active', true)->orderBy('name')->get();

        return view('assets.assets.edit', [
            'asset' => $asset,
            'categories' => $categories,
        ]);
    }

    public function update(Request $request, Asset $asset)
    {
        $data = $request->validate([
            'asset_category_id' => ['required', 'exists:asset_categories,id'],
            'name' => ['required', 'string', 'max:255'],
            'condition' => ['nullable', 'string', 'max:64'],
            'asset_code' => ['nullable', 'string', 'max:128', 'unique:assets,asset_code,' . $asset->id],
            'serial_number' => ['nullable', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:8000'],
            'purchase_date' => ['nullable', 'date'],
            'purchase_value' => ['nullable', 'numeric', 'min:0'],
            'status' => ['nullable', 'string', 'max:32'],
        ]);

        $asset->update([
            'asset_category_id' => (int) $data['asset_category_id'],
            'name' => $data['name'],
            'condition' => $data['condition'] ?? null,
            'asset_code' => $data['asset_code'] ?? null,
            'serial_number' => $data['serial_number'] ?? null,
            'description' => $data['description'] ?? null,
            'purchase_date' => $data['purchase_date'] ?? null,
            'purchase_value' => $data['purchase_value'] ?? null,
            'status' => $data['status'] ?? $asset->status,
        ]);

        return redirect()->route('assets.show', $asset)->with('status', 'Asset updated.');
    }
}
