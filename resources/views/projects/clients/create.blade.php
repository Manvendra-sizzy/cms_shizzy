@extends('hrms.layout')

@section('content')
    <div class="card form-card">
        <div class="row" style="justify-content:space-between;">
            <h1>Add client</h1>
            <a class="pill" href="{{ route('projects.clients.index') }}">Back</a>
        </div>

        <form method="post" action="{{ route('projects.clients.store', request()->only('redirect')) }}">
            @csrf
            @include('projects.clients._form', ['client' => null])
            <div style="margin-top:12px;">
                <button class="btn" type="submit">Create client</button>
            </div>
        </form>
    </div>
@endsection

