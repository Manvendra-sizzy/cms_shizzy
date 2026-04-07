@extends('hrms.layout')

@section('content')
    <div class="card form-card">
        <div class="row" style="justify-content:space-between;">
            <h1>Change employment status</h1>
            <a class="pill" href="{{ route('admin.hrms.employees.show', $employee) }}">Back</a>
        </div>

        <p class="muted" style="margin-top:8px;">Current status: <strong>{{ ucfirst($employee->status ?? 'active') }}</strong></p>

        <form method="post" action="{{ route('admin.hrms.employees.status.update', $employee) }}" style="margin-top:12px;" class="status-form">
            @csrf
            @method('PUT')

            @php $current = old('status', $employee->status ?? 'active'); @endphp

            <div class="field">
                <label>New status</label>
                <select name="status" id="new-status" required>
                    <option value="active" @selected($current==='active')>Active</option>
                    <option value="inactive" @selected($current==='inactive')>Inactive</option>
                    <option value="former" @selected($current==='former')>Former Employee</option>
                </select>
            </div>

            <div id="inactive-fields" class="status-dependent" data-show-when="inactive" style="display:none; margin-top:12px;">
                <div class="form-grid cols-2" style="gap:12px;">
                    <div class="field">
                        <label>Inactive date</label>
                        <input name="inactive_at" type="date" value="{{ old('inactive_at', optional($employee->inactive_at)->toDateString()) }}">
                    </div>
                    <div class="field">
                        <label>Inactive remarks</label>
                        <textarea name="inactive_remarks" rows="3">{{ old('inactive_remarks', $employee->inactive_remarks) }}</textarea>
                    </div>
                </div>
            </div>

            <div id="former-fields" class="status-dependent" data-show-when="former" style="display:none; margin-top:12px;">
                <div class="form-grid cols-2" style="gap:12px;">
                    <div class="field">
                        <label>Exit type</label>
                        <select name="separation_type">
                            <option value="">None</option>
                            @foreach(['resigned','terminated','retired'] as $type)
                                <option value="{{ $type }}" @selected(old('separation_type', $employee->separation_type)===$type)>{{ ucfirst($type) }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="field">
                        <label>Effective date</label>
                        <input name="separation_effective_at" type="date" value="{{ old('separation_effective_at', optional($employee->separation_effective_at)->toDateString()) }}">
                    </div>
                    <div class="field" style="grid-column:1 / -1;">
                        <label>Exit remarks</label>
                        <textarea name="separation_remarks" rows="3">{{ old('separation_remarks', $employee->separation_remarks) }}</textarea>
                    </div>
                </div>
            </div>

            <button class="btn" type="submit" style="margin-top:14px;">Update status</button>
        </form>
    </div>

    @push('scripts')
    <script>
        (function() {
            var sel = document.getElementById('new-status');
            function toggle() {
                var v = (sel && sel.value) || '';
                document.querySelectorAll('.status-dependent').forEach(function(block) {
                    var showWhen = block.getAttribute('data-show-when');
                    block.style.display = (showWhen === v) ? '' : 'none';
                });
            }
            if (sel) {
                sel.addEventListener('change', toggle);
                toggle();
            }
        })();
    </script>
    @endpush
@endsection
