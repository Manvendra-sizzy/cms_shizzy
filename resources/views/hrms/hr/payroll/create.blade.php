@extends('hrms.layout')

@section('content')
    <div class="card form-card">
        <h1>New payroll run</h1>

        <form method="post" action="{{ route('admin.hrms.payroll.store') }}">
            @csrf
            <div class="form-grid cols-2">
                <div class="field">
                    <label>Period start</label>
                    <input name="period_start" type="date" value="{{ old('period_start') }}" required>
                </div>
                <div class="field">
                    <label>Period end</label>
                    <input name="period_end" type="date" value="{{ old('period_end') }}" required>
                </div>
            </div>
            <div class="field">
                <label>Notes</label>
                <textarea name="notes">{{ old('notes') }}</textarea>
            </div>
            <button class="btn" type="submit">Create run</button>
        </form>
    </div>
@endsection

