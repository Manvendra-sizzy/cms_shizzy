@extends('hrms.layout')

@section('content')
    <div class="card form-card">
        <div class="row" style="justify-content:space-between;">
            <h1>Edit project <span class="muted">{{ $project->project_code }}</span></h1>
            <a class="pill" href="{{ route('projects.show', $project) }}">Back</a>
        </div>

        <form method="post" action="{{ route('projects.update', $project) }}" style="margin-top:12px;">
            @csrf
            @method('PUT')
            @include('projects.projects._form', ['project' => $project, 'selectedClientId' => $project->is_internal ? '__internal__' : $project->zoho_client_id])
            <div style="margin-top:12px;">
                <button class="btn" type="submit">Save</button>
            </div>
        </form>
    </div>
@endsection

