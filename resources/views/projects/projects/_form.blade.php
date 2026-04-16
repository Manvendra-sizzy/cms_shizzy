<div class="row" style="justify-content:space-between;align-items:center;">
    <div class="field" style="flex:1;margin:0;">
        <label>Client</label>
        @php
            $projectIsInternal = (bool) ($project->is_internal ?? false);
            $defaultClientSelection = $projectIsInternal ? '__internal__' : ($project->zoho_client_id ?? $selectedClientId ?? '');
            $selectedClient = old('zoho_client_id', $defaultClientSelection);
        @endphp
        <select name="zoho_client_id" required>
            <option value="">Select client</option>
            <option value="__internal__" @selected($selectedClient === '__internal__')>Internal Project</option>
            @foreach($clients as $c)
                <option value="{{ $c->id }}" @selected((string) $selectedClient === (string) $c->id)>
                    {{ $c->contact_name ?: ($c->company_name ?: trim(($c->first_name ?? '').' '.($c->last_name ?? ''))) }} @if($c->email) — {{ $c->email }} @endif
                </option>
            @endforeach
        </select>
    </div>
</div>

<div class="field">
    <label>Project name</label>
    <input name="name" value="{{ old('name', $project->name ?? '') }}" required>
</div>

<div class="form-grid cols-2">
    <div class="field">
        <label>Category</label>
        @php
            $selectedCategory = old('category', $project->category ?? '');
            $isCustomCategory = $selectedCategory !== '' && !in_array($selectedCategory, $categories, true);
        @endphp
        <select id="projectCategorySelect">
            <option value="">Select category</option>
            @foreach($categories as $cat)
                <option value="{{ $cat }}" @selected($selectedCategory === $cat)>{{ $cat }}</option>
            @endforeach
            <option value="__new__" @selected($isCustomCategory)>+ Create new category</option>
        </select>
        <input
            id="projectCategoryCustom"
            style="margin-top:8px;{{ $isCustomCategory ? '' : 'display:none;' }}"
            value="{{ $isCustomCategory ? $selectedCategory : '' }}"
            maxlength="64"
            placeholder="Type new category name"
        >
        <input id="projectCategoryValue" type="hidden" name="category" value="{{ $selectedCategory }}">
        <div style="margin-top:8px;">
            <a class="pill" href="{{ route('projects.categories.index') }}">Manage categories</a>
        </div>
    </div>
    <div class="field">
        <label>Project type</label>
        <select name="project_type" required>
            <option value="one_time" @selected(old('project_type', $project->project_type ?? 'one_time') === 'one_time')>One-time</option>
            <option value="recurring" @selected(old('project_type', $project->project_type ?? '') === 'recurring')>Recurring</option>
        </select>
    </div>
</div>

<div class="form-grid cols-2">
    <div class="field">
        <label>Billing type</label>
        <select name="billing_type" required>
            <option value="fixed" @selected(old('billing_type', $project->billing_type ?? 'fixed') === 'fixed')>Fixed</option>
            <option value="prorata" @selected(old('billing_type', $project->billing_type ?? '') === 'prorata')>Pro-rata</option>
        </select>
    </div>
    <div class="field">
        <label>Project folder</label>
        <input name="project_folder" value="{{ old('project_folder', $project->project_folder ?? '') }}" placeholder="e.g. Google Drive / SharePoint link">
    </div>
</div>

<div class="form-grid cols-2">
    <div class="field">
        <label>Project manager</label>
        <select name="project_manager_employee_profile_id">
            <option value="">—</option>
            @foreach($employees as $e)
                <option value="{{ $e->id }}" @selected(old('project_manager_employee_profile_id', $project->project_manager_employee_profile_id ?? null) == $e->id)>
                    {{ $e->employee_id }} — {{ $e->user?->name }}
                </option>
            @endforeach
        </select>
    </div>
    <div class="field">
        <label>Account manager</label>
        <select name="account_manager_employee_profile_id">
            <option value="">—</option>
            @foreach($employees as $e)
                <option value="{{ $e->id }}" @selected(old('account_manager_employee_profile_id', $project->account_manager_employee_profile_id ?? null) == $e->id)>
                    {{ $e->employee_id }} — {{ $e->user?->name }}
                </option>
            @endforeach
        </select>
    </div>
</div>

<div class="field">
    <label>Description</label>
    <textarea name="description">{{ old('description', $project->description ?? '') }}</textarea>
</div>

@once
    @push('scripts')
        <script>
            document.addEventListener('DOMContentLoaded', function () {
                const categorySelect = document.getElementById('projectCategorySelect');
                const customCategoryInput = document.getElementById('projectCategoryCustom');
                const categoryValueInput = document.getElementById('projectCategoryValue');

                if (!categorySelect || !customCategoryInput || !categoryValueInput) {
                    return;
                }

                const syncCategoryValue = function () {
                    const isCreatingNew = categorySelect.value === '__new__';
                    customCategoryInput.style.display = isCreatingNew ? 'block' : 'none';

                    if (isCreatingNew) {
                        customCategoryInput.required = true;
                        categoryValueInput.value = customCategoryInput.value.trim();
                    } else {
                        customCategoryInput.required = false;
                        categoryValueInput.value = categorySelect.value;
                    }
                };

                categorySelect.addEventListener('change', syncCategoryValue);
                customCategoryInput.addEventListener('input', syncCategoryValue);
                syncCategoryValue();
            });
        </script>
    @endpush
@endonce

