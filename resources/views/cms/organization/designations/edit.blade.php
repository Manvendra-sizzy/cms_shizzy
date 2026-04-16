@extends('hrms.layout')

@section('content')
    <div class="card form-card">
        <div class="row" style="justify-content:space-between;">
            <h1>Edit Designation</h1>
            <a class="pill" href="{{ route('admin.organization.designations.index') }}">Back</a>
        </div>

        <form method="post" action="{{ route('admin.organization.designations.update', $designation) }}" style="margin-top:12px;">
            @csrf
            @method('PUT')
            <div class="form-grid cols-2">
                <div class="field">
                    <label>Name</label>
                    <input name="name" value="{{ old('name', $designation->name) }}" required>
                </div>
                <div class="field">
                    <label>Code</label>
                    <input name="code" value="{{ old('code', $designation->code) }}" required>
                </div>
            </div>
            <label style="display:flex;gap:8px;align-items:center;margin: 6px 0 14px;">
                <input type="checkbox" name="active" value="1" @checked(old('active', $designation->active)) style="width:auto;">
                Active
            </label>
            <button class="btn" type="submit">Save</button>
        </form>
    </div>
@endsection

