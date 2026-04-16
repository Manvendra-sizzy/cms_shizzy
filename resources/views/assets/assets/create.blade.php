@extends('hrms.layout')

@section('content')
    <div class="card form-card">
        <div class="row" style="justify-content:space-between;">
            <h1>Add asset</h1>
            <a class="pill" href="{{ route('assets.index') }}">Back</a>
        </div>

        <form method="post" action="{{ route('assets.store') }}" style="margin-top:12px;">
            @csrf
            @include('assets.assets._form', ['asset' => null])
            <div style="margin-top:12px;">
                <button class="btn" type="submit">Create asset</button>
            </div>
        </form>
    </div>
@endsection

