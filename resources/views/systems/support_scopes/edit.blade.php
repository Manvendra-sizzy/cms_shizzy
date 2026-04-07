@extends('hrms.layout')

@section('content')
    <div class="card form-card">
        <h1>Edit Support Scope</h1>
        <form method="post" action="{{ route('systems.support_scopes.update', $scope) }}">
            @csrf
            @method('PUT')
            <div class="field"><label>Scope Name *</label><input type="text" name="scope_name" value="{{ old('scope_name', $scope->scope_name) }}" required></div>
            <div class="field"><label>Description</label><textarea name="description">{{ old('description', $scope->description) }}</textarea></div>
            <div class="field"><label>Included Services</label><textarea name="included_services">{{ old('included_services', $scope->included_services) }}</textarea></div>
            <div class="field"><label>Excluded Services</label><textarea name="excluded_services">{{ old('excluded_services', $scope->excluded_services) }}</textarea></div>
            <div class="field"><label>SLA (Response Time)</label><input type="text" name="sla_response_time" value="{{ old('sla_response_time', $scope->sla_response_time) }}"></div>
            <div class="field"><label><input type="checkbox" name="active" value="1" @checked(old('active', $scope->active))> Active</label></div>
            <button class="btn" type="submit">Update Scope</button>
        </form>
    </div>
@endsection
