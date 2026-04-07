@extends('hrms.layout')

@section('content')
    <div class="card form-card">
        <h1>Apply for leave</h1>

        <form method="post" action="{{ route('employee.leaves.store') }}">
            @csrf
            <div class="field">
                <label>Leave type</label>
                <select name="leave_policy_id" required>
                    <option value="">Select…</option>
                    @foreach($policies as $p)
                        <option value="{{ $p->id }}" @selected(old('leave_policy_id')==$p->id)>
                            {{ $p->code }} — {{ $p->name }}
                        </option>
                    @endforeach
                </select>
            </div>
            <div class="form-grid cols-2">
                <div class="field">
                    <label>Start date</label>
                    <input name="start_date" type="date" value="{{ old('start_date') }}" required>
                </div>
                <div class="field">
                    <label>End date</label>
                    <input name="end_date" type="date" value="{{ old('end_date') }}" required>
                </div>
            </div>
            <label style="display:flex;gap:8px;align-items:center;margin:10px 0 6px;">
                <input type="checkbox" name="is_half_day" value="1" style="width:auto;" @checked(old('is_half_day'))>
                Half day (uses 0.5 leave day)
            </label>
            <div class="field">
                <label>Reason (optional)</label>
                <textarea name="reason">{{ old('reason') }}</textarea>
            </div>
            <button class="btn" type="submit">Submit request</button>
        </form>
    </div>
@endsection

