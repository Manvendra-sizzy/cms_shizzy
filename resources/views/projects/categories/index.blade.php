@extends('hrms.layout')

@section('content')
    <style>
        .pc-shell { display: grid; gap: 14px; }
        .pc-hero {
            background: linear-gradient(135deg, #0f172a, #1e40af 58%, #2563eb);
            border-radius: 16px;
            color: #fff;
            padding: 18px;
            border: 1px solid rgba(255,255,255,.2);
            box-shadow: 0 12px 26px rgba(15,23,42,.22);
        }
        .pc-hero h1 { color: #fff; margin-bottom: 6px; }
        .pc-hero .muted { color: rgba(255,255,255,.86); }
        .pc-kpi {
            border: 1px solid var(--border);
            border-radius: 14px;
            padding: 14px;
            background: #fff;
        }
        .pc-kpi .label { color: var(--muted); font-size: 12px; }
        .pc-kpi .value { color: #0f172a; font-size: 26px; font-weight: 700; margin-top: 6px; }
        .pc-table td { vertical-align: middle; }
        .pc-input { min-width: 240px; }
        .pc-status { min-width: 140px; }
    </style>

    <div class="pc-shell">
        <div class="pc-hero">
            <div class="row" style="justify-content:space-between;align-items:flex-start;">
                <div>
                    <h1>Project Categories</h1>
                    <p class="muted" style="margin:0;">Create and update categories used across projects. Existing categories are preserved.</p>
                </div>
                <div class="row">
                    <a class="pill" href="{{ route('projects.index') }}" style="background:#fff;border-color:#fff;color:#1e3a8a;">Projects</a>
                    <a class="pill" href="{{ route('projects.create') }}" style="background:#fff;border-color:#fff;color:#1e3a8a;">Create project</a>
                </div>
            </div>
        </div>

        <div class="grid cols-3">
            <div class="pc-kpi">
                <div class="label">Total categories</div>
                <div class="value">{{ $categories->total() }}</div>
            </div>
            <div class="pc-kpi">
                <div class="label">Active categories (this page)</div>
                <div class="value">{{ $categories->where('active', true)->count() }}</div>
            </div>
            <div class="pc-kpi">
                <div class="label">Inactive categories (this page)</div>
                <div class="value">{{ $categories->where('active', false)->count() }}</div>
            </div>
        </div>

        <div class="card">
            <h2>Add category</h2>
            <form method="post" action="{{ route('projects.categories.store') }}" class="row" style="align-items:flex-end;gap:10px;">
                @csrf
                <div style="flex:1;min-width:220px;">
                    <label>Category name</label>
                    <input name="name" value="{{ old('name') }}" maxlength="64" required placeholder="e.g. Content Marketing">
                </div>
                <div style="width:170px;">
                    <label>Status</label>
                    <select name="active">
                        <option value="1" @selected(old('active', '1') === '1')>Active</option>
                        <option value="0" @selected(old('active') === '0')>Inactive</option>
                    </select>
                </div>
                <button class="btn" type="submit">Add category</button>
            </form>
        </div>

        <div class="card" style="overflow:hidden;">
            <h2>Categories</h2>
            @if($categories->isEmpty())
                <p class="muted">No categories available.</p>
            @else
                <div class="table-wrap pc-table">
                    <table>
                        <thead>
                        <tr>
                            <th style="width:55%;">Name</th>
                            <th style="width:25%;">Status</th>
                            <th style="width:20%;">Action</th>
                        </tr>
                        </thead>
                        <tbody>
                        @foreach($categories as $category)
                            <tr>
                                <td>
                                    <form method="post" action="{{ route('projects.categories.update', $category) }}" class="row" style="gap:8px;align-items:center;">
                                        @csrf
                                        @method('PUT')
                                        <input class="pc-input" name="name" value="{{ $category->name }}" maxlength="64" required>
                                </td>
                                <td>
                                        <select class="pc-status" name="active">
                                            <option value="1" @selected($category->active)>Active</option>
                                            <option value="0" @selected(!$category->active)>Inactive</option>
                                        </select>
                                </td>
                                <td>
                                        <button class="pill" type="submit">Save</button>
                                    </form>
                                </td>
                            </tr>
                        @endforeach
                        </tbody>
                    </table>
                </div>

                <div style="margin-top:12px;">
                    {{ $categories->links() }}
                </div>
            @endif
        </div>
    </div>
@endsection

