@extends('hrms.layout')

@section('content')
    <div class="card">
        <div class="row" style="justify-content:space-between;">
            <div>
                <h1>{{ $asset->name }}</h1>
                <p class="muted" style="margin:0;">
                    Category: <strong>{{ $asset->category?->name ?? '—' }}</strong>
                    · Status: <strong>{{ $asset->status }}</strong>
                    @if($asset->condition)
                        · Condition: <strong>{{ $asset->condition }}</strong>
                    @endif
                </p>
                <p class="muted" style="margin-top:6px;">
                    Code: <strong>{{ $asset->asset_code ?? '—' }}</strong>
                    · Serial: <strong>{{ $asset->serial_number ?? '—' }}</strong>
                </p>
            </div>
            <div class="row">
                <a class="pill" href="{{ route('assets.index') }}">All assets</a>
                <a class="pill" href="{{ route('assets.edit', $asset) }}">Edit</a>
            </div>
        </div>
    </div>

    <div class="grid" style="grid-template-columns:1fr 1fr;gap:14px;margin-top:14px;">
        <div class="card">
            <h2>Assignment</h2>
            @if(!$currentAssignment)
                <p class="muted">This asset is currently not assigned.</p>
                <form method="post" action="{{ route('assets.assign', $asset) }}" style="margin-top:10px;">
                    @csrf
                    <div class="field">
                        <label>Assign to employee</label>
                        <select name="employee_profile_id" required>
                            <option value="">Select employee</option>
                            @foreach($employees as $e)
                                <option value="{{ $e->id }}">{{ $e->employee_id }} — {{ $e->user?->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="field">
                        <label>Assignment date</label>
                        <input type="date" name="assigned_at" value="{{ old('assigned_at', now()->toDateString()) }}" required>
                    </div>
                    <div class="field">
                        <label>Remarks</label>
                        <textarea name="remarks">{{ old('remarks') }}</textarea>
                    </div>
                    <button class="btn" type="submit">Assign asset</button>
                </form>
            @else
                <p class="muted">
                    Currently with:
                    <strong>{{ $currentAssignment->employeeProfile?->employee_id }}</strong>
                    — {{ $currentAssignment->employeeProfile?->user?->name }}<br>
                    Since: <strong>{{ optional($currentAssignment->assigned_at)->format('Y-m-d') ?? '—' }}</strong>
                </p>

                <h3 style="font-size:14px;margin-top:12px;">Transfer</h3>
                <form method="post" action="{{ route('assets.transfer', $asset) }}" class="row" style="gap:10px;align-items:flex-end;">
                    @csrf
                    <div style="flex:1;min-width:200px;">
                        <label>New employee</label>
                        <select name="employee_profile_id" required>
                            <option value="">Select employee</option>
                            @foreach($employees as $e)
                                <option value="{{ $e->id }}">{{ $e->employee_id }} — {{ $e->user?->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div style="flex:1;min-width:160px;">
                        <label>Transfer date</label>
                        <input type="date" name="assigned_at" value="{{ old('assigned_at', now()->toDateString()) }}" required>
                    </div>
                    <div style="flex:1;min-width:200px;">
                        <label>Remarks</label>
                        <input name="remarks" value="{{ old('remarks') }}" placeholder="Optional">
                    </div>
                    <button class="btn" type="submit">Transfer</button>
                </form>

                <h3 style="font-size:14px;margin-top:12px;">Return to inventory</h3>
                <form method="post" action="{{ route('assets.return', $asset) }}" class="row" style="gap:10px;align-items:flex-end;">
                    @csrf
                    <div style="flex:1;min-width:160px;">
                        <label>Return date</label>
                        <input type="date" name="returned_at" value="{{ old('returned_at', now()->toDateString()) }}" required>
                    </div>
                    <div style="flex:2;min-width:220px;">
                        <label>Remarks</label>
                        <input name="remarks" value="{{ old('remarks') }}" placeholder="Optional">
                    </div>
                    <button class="btn" type="submit">Mark returned</button>
                </form>
            @endif
        </div>

        <div class="card">
            <h2>Details</h2>
            <table>
                <tbody>
                <tr>
                    <td>Purchase date</td>
                    <td class="muted">{{ optional($asset->purchase_date)->format('Y-m-d') ?? '—' }}</td>
                </tr>
                <tr>
                    <td>Purchase value</td>
                    <td class="muted">{{ $asset->purchase_value !== null ? $asset->purchase_value : '—' }}</td>
                </tr>
                <tr>
                    <td>Description</td>
                    <td class="muted" style="white-space:pre-wrap;">{{ $asset->description ?? '—' }}</td>
                </tr>
                </tbody>
            </table>
        </div>
    </div>

    <div class="card" style="margin-top:14px;">
        <h2>Assignment history</h2>
        @if($history->isEmpty())
            <p class="muted">No assignment history yet.</p>
        @else
            <table>
                <thead>
                <tr>
                    <th>Employee</th>
                    <th>Action</th>
                    <th>Assigned</th>
                    <th>Returned</th>
                    <th>Remarks</th>
                </tr>
                </thead>
                <tbody>
                @foreach($history as $row)
                    <tr>
                        <td>
                            <strong>{{ $row->employeeProfile?->employee_id }}</strong>
                            <div class="muted">{{ $row->employeeProfile?->user?->name }}</div>
                        </td>
                        <td class="muted">{{ $row->action_type }}</td>
                        <td class="muted">{{ optional($row->assigned_at)->format('Y-m-d') ?? '—' }}</td>
                        <td class="muted">{{ optional($row->returned_at)->format('Y-m-d') ?? '—' }}</td>
                        <td class="muted">{{ $row->remarks ?? '—' }}</td>
                    </tr>
                @endforeach
                </tbody>
            </table>
        @endif
    </div>
@endsection

