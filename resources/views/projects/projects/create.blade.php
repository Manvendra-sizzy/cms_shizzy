@extends('hrms.layout')

@section('content')
    <div class="card form-card">
        <div class="row" style="justify-content:space-between;">
            <h1>Create project</h1>
            <a class="pill" href="{{ route('projects.index') }}">Back</a>
        </div>

        <form method="post" action="{{ route('projects.store') }}" style="margin-top:12px;">
            @csrf
            @include('projects.projects._form', ['project' => null])
            <div style="margin-top:12px;">
                <button class="btn" type="submit">Create project</button>
            </div>
        </form>
    </div>
@endsection

