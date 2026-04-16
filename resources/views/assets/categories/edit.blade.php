@extends('hrms.layout')

@section('content')
    <div class="card form-card">
        <div class="row" style="justify-content:space-between;">
            <h1>Edit asset category</h1>
            <a class="pill" href="{{ route('assets.categories.index') }}">Back</a>
        </div>

        <form method="post" action="{{ route('assets.categories.update', $category) }}" style="margin-top:12px;">
            @csrf
            @method('PUT')
            @include('assets.categories._form', ['category' => $category])
            <div style="margin-top:12px;">
                <button class="btn" type="submit">Save</button>
            </div>
        </form>
    </div>
@endsection

