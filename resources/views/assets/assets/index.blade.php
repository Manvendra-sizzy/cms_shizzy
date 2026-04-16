@extends('hrms.layout')

@section('content')
    <div class="card">
        <div class="row" style="justify-content:space-between;">
            <h1>Assets</h1>
            <div class="row">
                <a class="pill" href="{{ route('assets.categories.index') }}">Categories</a>
                <a class="pill" href="{{ route('assets.create') }}">Add asset</a>
            </div>
        </div>

        <form method="get" class="row" style="margin-top:12px;gap:10px;align-items:flex-end;">
            <div class="field" style="max-width:200px;margin-bottom:0;">
                <label>Category</label>
                <select name="category_id">
                    <option value="">All</option>
                    @foreach($categories as $cat)
                        <option value="{{ $cat->id }}" @selected(request('category_id') == $cat->id)>{{ $cat->name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="field" style="max-width:180px;margin-bottom:0;">
                <label>Status</label>
                <select name="status">
                    <option value="">All</option>
                    @foreach(['in_stock','assigned','retired','lost'] as $st)
                        <option value="{{ $st }}" @selected(request('status') === $st)>{{ $st }}</option>
                    @endforeach
                </select>
            </div>
            <div class="field" style="flex:1;min-width:200px;margin-bottom:0;">
                <label>Search</label>
                <input name="q" value="{{ request('q') }}" placeholder="Name / code / serial">
            </div>
            <button class="pill" type="submit">Filter</button>
        </form>

        @if($assets->isEmpty())
            <p class="muted" style="margin-top:12px;">No assets found.</p>
        @else
            <table style="margin-top:12px;">
                <thead>
                <tr>
                    <th>Name</th>
                    <th>Category</th>
                    <th>Code</th>
                    <th>Serial</th>
                    <th>Status</th>
                    <th></th>
                </tr>
                </thead>
                <tbody>
                @foreach($assets as $a)
                    <tr>
                        <td><strong>{{ $a->name }}</strong><div class="muted">{{ $a->condition }}</div></td>
                        <td class="muted">{{ $a->category?->name ?? '—' }}</td>
                        <td class="muted">{{ $a->asset_code ?? '—' }}</td>
                        <td class="muted">{{ $a->serial_number ?? '—' }}</td>
                        <td><strong>{{ $a->status }}</strong></td>
                        <td><a class="pill" href="{{ route('assets.show', $a) }}">View</a></td>
                    </tr>
                @endforeach
                </tbody>
            </table>

            {{ $assets->links() }}
        @endif
    </div>
@endsection

