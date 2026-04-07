@extends('hrms.layout')

@section('content')
    <div class="card form-card">
        <div class="row" style="justify-content:space-between;">
            <h1>Edit asset</h1>
            <a class="pill" href="{{ route('assets.show', $asset) }}">Back</a>
        </div>

        <form method="post" action="{{ route('assets.update', $asset) }}" style="margin-top:12px;">
            @csrf
            @method('PUT')
            @include('assets.assets._form', ['asset' => $asset])
            <div style="margin-top:12px;">
                <button class="btn" type="submit">Save</button>
            </div>
        </form>
    </div>
@endsection

