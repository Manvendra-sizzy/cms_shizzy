@extends('hrms.layout')

@section('content')
    <div class="card form-card">
        <div class="row" style="justify-content:space-between;align-items:flex-start;">
            <h1>Emergency Contacts</h1>
            <a class="pill" href="{{ route('admin.hrms.employees.show', $employee) }}">Back</a>
        </div>

        <p class="muted" style="margin-top:6px;">
            Employee: <strong>{{ $employee->user->name }}</strong> ({{ $employee->employee_id }})
        </p>

        <form method="post" action="{{ route('admin.hrms.employees.emergency_contacts.update', $employee) }}" class="form-wrap" style="margin-top:14px;">
            @csrf
            @method('PUT')

            <div class="card" style="margin-top:10px;">
                <h2>Contact 1</h2>
                <div class="form-grid cols-2">
                    <div class="field">
                        <label>Name</label>
                        <input name="contacts[1][name]" value="{{ old('contacts.1.name', $c1->name ?? '') }}">
                    </div>
                    <div class="field">
                        <label>Phone</label>
                        <input name="contacts[1][phone]" value="{{ old('contacts.1.phone', $c1->phone ?? '') }}">
                    </div>
                    <div class="field" style="grid-column:1 / -1;">
                        <label>Relation</label>
                        <input name="contacts[1][relation]" value="{{ old('contacts.1.relation', $c1->relation ?? '') }}">
                    </div>
                </div>
            </div>

            <div class="card" style="margin-top:10px;">
                <h2>Contact 2</h2>
                <div class="form-grid cols-2">
                    <div class="field">
                        <label>Name</label>
                        <input name="contacts[2][name]" value="{{ old('contacts.2.name', $c2->name ?? '') }}">
                    </div>
                    <div class="field">
                        <label>Phone</label>
                        <input name="contacts[2][phone]" value="{{ old('contacts.2.phone', $c2->phone ?? '') }}">
                    </div>
                    <div class="field" style="grid-column:1 / -1;">
                        <label>Relation</label>
                        <input name="contacts[2][relation]" value="{{ old('contacts.2.relation', $c2->relation ?? '') }}">
                    </div>
                </div>
            </div>

            <button class="btn" type="submit" style="margin-top:12px;">Save</button>
        </form>
    </div>
@endsection

