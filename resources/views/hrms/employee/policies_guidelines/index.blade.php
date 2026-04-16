@extends('hrms.layout')

@section('content')
    <div class="card">
        <div class="row" style="justify-content:space-between;align-items:flex-start;">
            <h1>Policies &amp; Guidelines</h1>
            <a class="pill" href="{{ route('employee.dashboard') }}">Back</a>
        </div>
        <p class="muted">Company policies applicable to all employees.</p>
    </div>

    @if(($policies ?? collect())->isEmpty())
        <div class="card" style="margin-top:14px;">
            <p class="muted">No policy entries available right now.</p>
        </div>
    @else
        @foreach($policies as $policy)
            <div class="card" style="margin-top:14px;">
                <h2 style="margin-bottom:10px;">{{ $policy->title }}</h2>
                <div style="white-space:pre-wrap;">{{ $policy->content }}</div>
            </div>
        @endforeach
    @endif
@endsection

