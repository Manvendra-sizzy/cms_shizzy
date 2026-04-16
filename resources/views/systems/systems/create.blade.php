@extends('hrms.layout')

@section('content')
    <div class="card form-card">
        <h1>Create System</h1>
        <form method="post" action="{{ route('systems.store') }}">
            @csrf
            @include('systems.systems._form')
            <button class="btn" type="submit">Create System</button>
        </form>
    </div>
@endsection
