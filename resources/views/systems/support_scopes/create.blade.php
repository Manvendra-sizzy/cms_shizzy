@extends('hrms.layout')

@section('content')
    <div class="card form-card">
        <h1>Create Support Scope</h1>
        <form method="post" action="{{ route('systems.support_scopes.store') }}">
            @csrf
            <div class="field"><label>Scope Name *</label><input type="text" name="scope_name" required></div>
            <div class="field"><label>Description</label><textarea name="description"></textarea></div>
            <div class="field"><label>Included Services</label><textarea name="included_services"></textarea></div>
            <div class="field"><label>Excluded Services</label><textarea name="excluded_services"></textarea></div>
            <div class="field"><label>SLA (Response Time)</label><input type="text" name="sla_response_time" placeholder="e.g. 4 business hours"></div>
            <div class="field"><label><input type="checkbox" name="active" value="1" checked> Active</label></div>
            <button class="btn" type="submit">Create Scope</button>
        </form>
    </div>
@endsection
