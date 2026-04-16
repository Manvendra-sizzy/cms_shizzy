@extends('hrms.layout')

@section('content')
    <div class="card form-card">
        <h1>Change password</h1>
        <p class="muted">Choose a strong password you don’t reuse elsewhere.</p>

        <form method="post" action="{{ route('employee.password.update') }}" class="form-wrap" style="margin-top:12px;">
            @csrf
            @method('put')

            <div class="form-grid cols-2">
                <div class="field" style="grid-column:1/-1;">
                    <label>Current password</label>
                    <input type="password" name="current_password" required autocomplete="current-password">
                </div>
                <div class="field">
                    <label>New password</label>
                    <input type="password" name="password" required autocomplete="new-password">
                </div>
                <div class="field">
                    <label>Confirm new password</label>
                    <input type="password" name="password_confirmation" required autocomplete="new-password">
                </div>
            </div>

            <div class="row" style="margin-top:10px;">
                <button class="btn" type="submit">Update password</button>
                <a class="pill" href="{{ route('employee.dashboard') }}">Back</a>
            </div>
        </form>
    </div>
@endsection

