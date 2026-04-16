@extends('hrms.layout')

@section('content')
    <div class="card form-card">
        <h1>Edit System</h1>
        <form method="post" action="{{ route('systems.update', $system) }}">
            @csrf
            @method('PUT')
            @include('systems.systems._form', ['system' => $system])
            <button class="btn" type="submit">Update System</button>
        </form>
    </div>
@endsection
