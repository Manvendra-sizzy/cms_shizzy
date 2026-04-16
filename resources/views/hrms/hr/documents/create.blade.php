@extends('hrms.layout')

@section('content')
    <style>
        .rte-toolbar {
            display: flex;
            flex-wrap: wrap;
            gap: 8px;
            margin-bottom: 8px;
        }
        .rte-btn {
            width: auto;
            min-width: 38px;
            padding: 7px 10px;
            border: 1px solid #d4dcea;
            border-radius: 8px;
            background: #fff;
            color: #0f172a;
            cursor: pointer;
            font-size: 12px;
            font-weight: 600;
        }
        .rte-editor {
            border: 1px solid #d4dcea;
            border-radius: 10px;
            min-height: 180px;
            padding: 10px 12px;
            background: #fff;
            overflow: auto;
        }
        .rte-editor:focus {
            outline: none;
            border-color: #8baefb;
            box-shadow: 0 0 0 4px rgba(38,99,255,.16);
        }
    </style>

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
                        @foreach($documentTypes as $key => $label)
                            <option value="{{ $key }}" @selected(old('type')==$key)>{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
            </div>
            <div class="field">
                <label id="body-label">Body (optional)</label>
                <div class="rte-toolbar">
                    <button type="button" class="rte-btn" data-cmd="bold"><strong>B</strong></button>
                    <button type="button" class="rte-btn" data-cmd="italic"><em>I</em></button>
                    <button type="button" class="rte-btn" data-cmd="underline"><u>U</u></button>
                    <button type="button" class="rte-btn" data-cmd="insertUnorderedList">• List</button>
                    <button type="button" class="rte-btn" data-cmd="insertOrderedList">1. List</button>
                    <button type="button" class="rte-btn" data-cmd="formatBlock" data-value="p">P</button>
                    <button type="button" class="rte-btn" data-cmd="formatBlock" data-value="h3">H3</button>
                    <button type="button" class="rte-btn" id="rte-clear">Clear</button>
                </div>
                <div id="body-editor" class="rte-editor" contenteditable="true"></div>
                <textarea id="body-input" name="body" style="display:none;">{{ old('body') }}</textarea>
                <div class="muted" style="margin-top:6px;font-size:12px;">Formatting is preserved in the issued document.</div>
            </div>
            <button class="btn" type="submit">Issue</button>
        </form>
    </div>
@endsection

@push('scripts')
<script>
    (function () {
        const requiredTypes = new Set([
            'appreciation_letter',
            'show_cause_notice',
            'warning_letter',
            'internship_appointment_letter',
        ]);
        const typeSelect = document.querySelector('select[name="type"]');
        const editor = document.getElementById('body-editor');
        const bodyInput = document.getElementById('body-input');
        const bodyLabel = document.getElementById('body-label');
        const form = document.querySelector('form[action="{{ route('admin.hrms.documents.store') }}"]');
        if (!typeSelect || !editor || !bodyInput || !form || !bodyLabel) return;

        editor.innerHTML = bodyInput.value || '';

        const syncBody = function () {
            bodyInput.value = editor.innerHTML.trim();
        };

        const refreshRequiredState = function () {
            const required = requiredTypes.has(typeSelect.value);
            bodyLabel.textContent = required ? 'Body *' : 'Body (optional)';
        };

        document.querySelectorAll('.rte-btn[data-cmd]').forEach(function (btn) {
            btn.addEventListener('click', function () {
                const cmd = btn.getAttribute('data-cmd');
                const val = btn.getAttribute('data-value');
                editor.focus();
                if (cmd === 'formatBlock' && val) {
                    document.execCommand(cmd, false, val);
                } else if (cmd) {
                    document.execCommand(cmd, false, null);
                }
                syncBody();
            });
        });

        const clearBtn = document.getElementById('rte-clear');
        if (clearBtn) {
            clearBtn.addEventListener('click', function () {
                editor.innerHTML = '';
                syncBody();
            });
        }

        editor.addEventListener('input', syncBody);
        typeSelect.addEventListener('change', refreshRequiredState);
        form.addEventListener('submit', syncBody);
        refreshRequiredState();
    })();
</script>
@endpush

