@extends('hrms.layout')

@section('content')
    <div class="card form-card">
        <h1>Issue document</h1>

        <form method="post" action="{{ route('admin.hrms.documents.store') }}" class="form-wrap">
            @csrf
            <div class="field">
                <label>Employee</label>
                <select name="employee_profile_id" required>
                    <option value="">Select…</option>
                    @foreach($employees as $emp)
                        <option value="{{ $emp->id }}" @selected(old('employee_profile_id')==$emp->id)>
                            {{ $emp->employee_id }} — {{ $emp->user->name }} ({{ $emp->user->email }})
                        </option>
                    @endforeach
                </select>
            </div>
            <div class="form-grid cols-2">
                <div class="field">
                    <label>Type</label>
                    <select name="type" required>
                        @foreach([
                            'appreciation_letter' => 'Appreciation Letter',
                            'relieving_letter' => 'Relieving Letter',
                            'employment_letter' => 'Employment Letter',
                            'permanent_employment_letter' => 'Permanent Employment Letter',
                            'show_cause_notice' => 'Show-Cause Notice',
                            'warning_letter' => 'Warning Letter',
                        ] as $key => $label)
                            <option value="{{ $key }}" @selected(old('type')==$key)>{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="field">
                    <label>Title</label>
                    <input name="title" value="{{ old('title') }}" required>
                </div>
            </div>
            <div class="field">
                <label>Body (optional)</label>
                <textarea name="body">{{ old('body') }}</textarea>
            </div>
            <button class="btn" type="submit">Issue</button>
        </form>
    </div>
@endsection

