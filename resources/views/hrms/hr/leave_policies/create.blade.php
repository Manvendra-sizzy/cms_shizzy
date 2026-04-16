@extends('hrms.layout')

@section('content')
    <div class="card form-card">
        <h1>Add leave policy</h1>

        <form method="post" action="{{ route('admin.hrms.leave_policies.store') }}">
            @csrf
            <div class="form-grid cols-2">
                <div class="field">
                    <label>Name</label>
                    <input name="name" value="{{ old('name') }}" required>
                </div>
                <div class="field">
                    <label>Code</label>
                    <input name="code" value="{{ old('code') }}" placeholder="CL" required>
                </div>
                <div class="field">
                    <label>Annual allowance (days)</label>
                    <input name="annual_allowance" type="number" min="0" max="366" value="{{ old('annual_allowance', 0) }}" required>
                </div>
                <div class="field">
                    <label>Max carry forward</label>
                    <input name="max_carry_forward" type="number" min="0" max="366" value="{{ old('max_carry_forward', 0) }}">
                </div>
            </div>
            <div class="row" style="margin: 10px 0 14px;">
                <label style="display:flex;gap:8px;align-items:center;">
                    <input type="checkbox" name="carry_forward" value="1" style="width:auto;">
                    Carry forward enabled
                </label>
                <label style="display:flex;gap:8px;align-items:center;">
                    <input type="checkbox" name="requires_approval" value="1" style="width:auto;" checked>
                    Requires approval
                </label>
                <label style="display:flex;gap:8px;align-items:center;">
                    <input type="checkbox" name="active" value="1" style="width:auto;" checked>
                    Active
                </label>
                <label style="display:flex;gap:8px;align-items:center;">
                    <input type="checkbox" name="is_paid" value="1" style="width:auto;" checked>
                    Paid leave (uncheck for unpaid / LOP)
                </label>
            </div>
            <button class="btn" type="submit">Create</button>
        </form>
    </div>
@endsection

