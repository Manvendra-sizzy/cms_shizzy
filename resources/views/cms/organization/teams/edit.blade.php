@extends('hrms.layout')

@section('content')
    <div class="card form-card">
        <div class="row" style="justify-content:space-between;">
            <h1>Edit Team</h1>
            <a class="pill" href="{{ route('admin.organization.teams.index') }}">Back</a>
        </div>

        <form method="post" action="{{ route('admin.organization.teams.update', $team) }}" style="margin-top:12px;">
            @csrf
            @method('PUT')
            <div class="form-grid cols-2">
                <div class="field">
                    <label>Department</label>
                    <select name="department_id" required>
                        @foreach($departments as $d)
                            <option value="{{ $d->id }}" @selected(old('department_id', $team->department_id)==$d->id)>{{ $d->code }} - {{ $d->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="field">
                    <label>Name</label>
                    <input name="name" value="{{ old('name', $team->name) }}" required>
                </div>
                <div class="field">
                    <label>Code</label>
                    <input name="code" value="{{ old('code', $team->code) }}" required>
                </div>
            </div>
            <label style="display:flex;gap:8px;align-items:center;margin: 6px 0 14px;">
                <input type="checkbox" name="active" value="1" @checked(old('active', $team->active)) style="width:auto;">
                Active
            </label>
            <button class="btn" type="submit">Save</button>
        </form>
    </div>
@endsection

