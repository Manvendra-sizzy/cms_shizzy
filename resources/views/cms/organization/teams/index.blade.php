@extends('hrms.layout')

@section('content')
    <div class="card">
        <div class="row" style="justify-content:space-between;">
            <h1>Teams</h1>
            <a class="pill" href="{{ route('admin.organization.index') }}">Back</a>
        </div>

        <div class="card" style="margin-top:12px;max-width:820px;">
            <h2>Create Team</h2>
            <form method="post" action="{{ route('admin.organization.teams.store') }}">
                @csrf
                <div class="grid" style="grid-template-columns:1fr 1fr;gap:12px;">
                    <div class="field">
                        <label>Department</label>
                        <select name="department_id" required>
                            <option value="">Select…</option>
                            @foreach($departments as $d)
                                <option value="{{ $d->id }}" @selected(old('department_id')==$d->id)>{{ $d->code }} - {{ $d->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="field">
                        <label>Name</label>
                        <input name="name" value="{{ old('name') }}" required>
                    </div>
                    <div class="field">
                        <label>Code</label>
                        <input name="code" value="{{ old('code') }}" required>
                    </div>
                </div>
                <button class="btn" type="submit">Create</button>
            </form>
        </div>

        <div class="card" style="margin-top:12px;">
            <h2>All Teams</h2>
            @if($teams->isEmpty())
                <p class="muted">No teams yet.</p>
            @else
                <table>
                    <thead>
                    <tr>
                        <th>Department</th>
                        <th>Code</th>
                        <th>Name</th>
                        <th>Status</th>
                        <th></th>
                    </tr>
                    </thead>
                    <tbody>
                    @foreach($teams as $t)
                        <tr>
                            <td class="muted">{{ $t->department?->code }} - {{ $t->department?->name }}</td>
                            <td><strong>{{ $t->code }}</strong></td>
                            <td>{{ $t->name }}</td>
                            <td class="muted">{{ $t->active ? 'Active' : 'Inactive' }}</td>
                            <td class="row" style="justify-content:flex-end;">
                                <a class="pill" href="{{ route('admin.organization.teams.edit', $t) }}">Edit</a>
                                <form method="post" action="{{ route('admin.organization.teams.destroy', $t) }}">
                                    @csrf
                                    @method('DELETE')
                                    <button class="pill" type="submit">Delete</button>
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

