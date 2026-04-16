@php
    $selectedInfrastructure = old('infrastructure_resource_ids', isset($system) ? $system->infrastructureResources->pluck('id')->all() : []);
    $resourceTypeLabels = [
        'server' => 'Server',
        'cdn' => 'CDN',
        'object_storage' => 'Object Storage',
        'database' => 'Database',
        'email_sms' => 'SMTP',
        'domain_dns' => 'Domain DNS',
    ];
@endphp

<div class="form-grid cols-2">
    <div class="field">
        <label for="project_id">Project *</label>
        <select name="project_id" id="project_id" required>
            <option value="">Select project</option>
            @foreach($projects as $project)
                <option value="{{ $project->id }}" @selected((string) old('project_id', $system->project_id ?? '') === (string) $project->id)>
                    {{ $project->name }} ({{ $project->project_code }})
                </option>
            @endforeach
        </select>
    </div>
    <div class="field">
        <label for="system_name">System Name *</label>
        <input type="text" name="system_name" id="system_name" value="{{ old('system_name', $system->system_name ?? '') }}" required>
    </div>
</div>

<div class="form-grid cols-2">
    <div class="field">
        <label for="system_type">System Type *</label>
        <select name="system_type" id="system_type" required>
            @foreach($systemTypes as $type)
                <option value="{{ $type }}" @selected(old('system_type', $system->system_type ?? '') === $type)>{{ strtoupper($type) }}</option>
            @endforeach
        </select>
    </div>
    <div class="field">
        <label for="status">Status *</label>
        <select name="status" id="status" required>
            @foreach($systemStatuses as $status)
                <option value="{{ $status }}" @selected(old('status', $system->status ?? 'active') === $status)>{{ ucfirst(str_replace('_', ' ', $status)) }}</option>
            @endforeach
        </select>
    </div>
</div>

<div class="field">
    <label for="description">Description</label>
    <textarea name="description" id="description">{{ old('description', $system->description ?? '') }}</textarea>
</div>

<div class="form-grid cols-2">
    <div class="field">
        <label for="live_url">Live URL</label>
        <input type="url" name="live_url" id="live_url" value="{{ old('live_url', $system->live_url ?? '') }}">
    </div>
    <div class="field">
        <label for="admin_url">Admin URL</label>
        <input type="url" name="admin_url" id="admin_url" value="{{ old('admin_url', $system->admin_url ?? '') }}">
    </div>
</div>

<div class="form-grid cols-2">
    <div class="field">
        <label for="repository_link">Repository Link</label>
        <input type="url" name="repository_link" id="repository_link" value="{{ old('repository_link', $system->repository_link ?? '') }}">
    </div>
    <div class="field">
        <label for="tech_stack">Tech Stack</label>
        <input type="text" name="tech_stack" id="tech_stack" value="{{ old('tech_stack', $system->tech_stack ?? '') }}">
    </div>
</div>

<div class="form-grid cols-2">
    <div class="field">
        <label for="support_scope_id">Support Scope</label>
        <select name="support_scope_id" id="support_scope_id">
            <option value="">None</option>
            @foreach($supportScopes as $scope)
                <option value="{{ $scope->id }}" @selected((string) old('support_scope_id', $system->support_scope_id ?? '') === (string) $scope->id)>
                    {{ $scope->scope_name }}
                </option>
            @endforeach
        </select>
    </div>
    <div class="field">
        <label for="support_status">Support Status *</label>
        <select name="support_status" id="support_status" required>
            @foreach($supportStatuses as $supportStatus)
                <option value="{{ $supportStatus }}" @selected(old('support_status', $system->support_status ?? 'inactive') === $supportStatus)>{{ ucfirst(str_replace('_', ' ', $supportStatus)) }}</option>
            @endforeach
        </select>
    </div>
</div>

<div class="form-grid cols-2">
    <div class="field">
        <label for="support_start_date">Support Start Date</label>
        <input type="date" name="support_start_date" id="support_start_date" value="{{ old('support_start_date', optional($system->support_start_date ?? null)?->toDateString()) }}">
    </div>
    <div class="field">
        <label for="support_end_date">Support End Date</label>
        <input type="date" name="support_end_date" id="support_end_date" value="{{ old('support_end_date', optional($system->support_end_date ?? null)?->toDateString()) }}">
        @if(isset($system) && $system->support_end_date)
            <p class="muted">Once set, use Extend Support from the details page to change this date.</p>
        @endif
    </div>
</div>

<div class="field">
    <label for="infrastructure_resource_ids">Infrastructure Mapping</label>
    <select name="infrastructure_resource_ids[]" id="infrastructure_resource_ids" multiple size="8">
        @foreach($infrastructureResources as $resource)
            <option value="{{ $resource->id }}" @selected(in_array($resource->id, array_map('intval', $selectedInfrastructure), true))>
                {{ $resourceTypeLabels[$resource->resource_type] ?? ucfirst(str_replace('_', ' ', $resource->resource_type)) }} - {{ $resource->name }}{{ $resource->vendor ? ' (' . $resource->vendor . ')' : '' }}
            </option>
        @endforeach
    </select>
</div>
