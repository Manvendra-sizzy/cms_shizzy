@extends('hrms.layout')

@section('content')
    <div class="card">
        <h1>Salary Structure</h1>
        <p class="muted">
            Define components used for payslip generation.
            If you add the <strong>Remaining</strong> component, it will automatically fill the leftover amount so the earnings total matches the gross basis.
        </p>

        <p class="muted" style="margin-top:8px;">Current earning total: <strong>{{ $totalPercent }}%</strong></p>

        <div class="card form-card" style="margin-top:12px;">
            <h2>Add component</h2>
            <form method="post" action="{{ route('admin.hrms.salary_structure.store') }}" class="form-wrap">
                @csrf
                <div class="form-grid cols-2">
                    <div class="field">
                        <label>Name</label>
                        <input name="name" value="{{ old('name') }}" required>
                    </div>
                    <div class="field">
                        <label>Code</label>
                        <input name="code" value="{{ old('code') }}" required>
                    </div>
                    <div class="field">
                        <label>Type</label>
                        <select name="type" required>
                            @foreach(['percent_of_gross','percent_of_component','fixed','deduction_percent_of_gross','remaining'] as $type)
                                <option value="{{ $type }}" @selected(old('type')===$type)>{{ $type }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="field">
                        <label>Value</label>
                        <input name="value" type="number" step="0.01" value="{{ old('value') }}">
                    </div>
                    <div class="field">
                        <label>Base component code (for percent_of_component)</label>
                        <input name="base_component_code" value="{{ old('base_component_code') }}">
                    </div>
                    <div class="field">
                        <label>Sequence</label>
                        <input name="sequence" type="number" min="0" value="{{ old('sequence', 0) }}">
                    </div>
                </div>
                <button class="btn" type="submit">Add</button>
            </form>
        </div>

        <div class="card" style="margin-top:12px;">
            <h2>Components</h2>
            @if($components->isEmpty())
                <p class="muted">No components defined.</p>
            @else
                <table>
                    <thead>
                    <tr>
                        <th>Seq</th>
                        <th>Code</th>
                        <th>Name</th>
                        <th>Type</th>
                        <th>Value</th>
                        <th>Base</th>
                        <th>Status</th>
                        <th></th>
                    </tr>
                    </thead>
                    <tbody>
                    @foreach($components as $c)
                        <tr>
                            <td>{{ $c->sequence }}</td>
                            <td><strong>{{ $c->code }}</strong></td>
                            <td>{{ $c->name }}</td>
                            <td class="muted">{{ $c->type }}</td>
                            <td>{{ $c->value }}</td>
                            <td class="muted">{{ $c->base_component_code ?? '—' }}</td>
                            <td class="muted">{{ $c->active ? 'Active' : 'Inactive' }}</td>
                            <td>
                                <form method="post" action="{{ route('admin.hrms.salary_structure.update', $c) }}">
                                    @csrf
                                    @method('PUT')
                                    <input type="hidden" name="name" value="{{ $c->name }}">
                                    <input type="hidden" name="type" value="{{ $c->type }}">
                                    <input type="hidden" name="value" value="{{ $c->value }}">
                                    <input type="hidden" name="base_component_code" value="{{ $c->base_component_code }}">
                                    <input type="hidden" name="sequence" value="{{ $c->sequence }}">
                                    <label style="display:flex;gap:6px;align-items:center;">
                                        <input type="checkbox" name="active" value="1" @checked($c->active) style="width:auto;">
                                        Active
                                    </label>
                                    <button class="btn" type="submit">Save</button>
                                </form>
                                <form method="post" action="{{ route('admin.hrms.salary_structure.destroy', $c) }}" style="margin-top:6px;">
                                    @csrf
                                    @method('DELETE')
                                    <button class="btn danger" type="submit">Delete</button>
                                </form>
                            </td>
                        </tr>
                    @endforeach
                    </tbody>
                </table>
            @endif
        </div>
    </div>
@endsection

