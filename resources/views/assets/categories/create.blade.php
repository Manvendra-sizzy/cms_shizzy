@extends('hrms.layout')

@section('content')
    <div class="card form-card">
        <div class="row" style="justify-content:space-between;">
            <h1>Add asset category</h1>
            <a class="pill" href="{{ route('assets.categories.index') }}">Back</a>
        </div>

        <form method="post" action="{{ route('assets.categories.store') }}" style="margin-top:12px;">
            @csrf
            @include('assets.categories._form', ['category' => null])
            <div style="margin-top:12px;">
                <button class="btn" type="submit">Create</button>
            </div>
        </form>
    </div>
@endsection

