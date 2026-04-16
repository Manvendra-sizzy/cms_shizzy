@extends('hrms.layout')

@section('content')
    <div class="card">
        <h1>Organization Structure</h1>
        <p class="muted">Choose what you want to manage.</p>

        <div class="grid cols-3" style="margin-top: 12px;">
            <div class="card">
                <h2>Department</h2>
                <p class="muted">Create, edit, and delete departments.</p>
                <a class="pill" href="{{ route('admin.organization.departments.index') }}">Open</a>
            </div>
            <div class="card">
                <h2>Team</h2>
                <p class="muted">Create, edit, and delete teams (linked to departments).</p>
                <a class="pill" href="{{ route('admin.organization.teams.index') }}">Open</a>
            </div>
            <div class="card">
                <h2>Designation</h2>
                <p class="muted">Create, edit, and delete designations (standalone).</p>
                <a class="pill" href="{{ route('admin.organization.designations.index') }}">Open</a>
            </div>
        </div>
    </div>
@endsection

