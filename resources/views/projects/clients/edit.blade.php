@extends('hrms.layout')

@section('content')
    <div class="card form-card">
        <div class="row" style="justify-content:space-between;">
            <h1>Edit client</h1>
            <a class="pill" href="{{ route('projects.clients.index') }}">Back</a>
        </div>

        <form method="post" action="{{ route('projects.clients.update', $client) }}">
            @csrf
            @method('PUT')
            @include('projects.clients._form', ['client' => $client])
            <div style="margin-top:12px;">
                <button class="btn" type="submit">Save</button>
            </div>
        </form>
    </div>
@endsection

