@extends('hrms.layout')

@section('content')
    @php
        $resourceTypeLabels = [
            'server' => 'Server',
            'cdn' => 'CDN',
            'object_storage' => 'Object Storage',
            'database' => 'Database',
            'email_sms' => 'SMTP',
            'domain_dns' => 'Domain DNS',
        ];
        $documentation = $system->documentation;
    @endphp

    <style>
        .tabs {
            display: flex;
            gap: 8px;
            flex-wrap: wrap;
            margin-top: 10px;
            margin-bottom: 14px;
        }
        .tab-btn {
            border: 1px solid #d7e0ef;
            border-radius: 10px;
            background: #f8fbff;
            color: #334155;
            font-weight: 700;
            font-size: 13px;
            padding: 8px 12px;
            cursor: pointer;
        }
        .tab-btn.is-active {
            background: #2563eb;
            color: #fff;
            border-color: #2563eb;
        }
        .tab-panel { display: none; }
        .tab-panel.is-active { display: block; }
    </style>

    <div class="card">
        <div class="row" style="justify-content: space-between;">
            <h1>{{ $system->system_name }}</h1>
            <div class="row">
                <a class="pill" href="{{ route('systems.edit', $system) }}">Edit</a>
                <a class="pill" href="{{ route('systems.index') }}">Back to Systems</a>
            </div>
        </div>

        <div class="tabs" role="tablist" aria-label="System details tabs">
            <button class="tab-btn is-active" type="button" data-tab="overview" role="tab" aria-selected="true">Overview</button>
            <button class="tab-btn" type="button" data-tab="infra-support" role="tab" aria-selected="false">Infrastructure & Support</button>
            <button class="tab-btn" type="button" data-tab="documentation" role="tab" aria-selected="false">Documentation</button>
            <button class="tab-btn" type="button" data-tab="development-log" role="tab" aria-selected="false">Development Log</button>
        </div>

        <div class="tab-panel is-active" data-panel="overview" role="tabpanel">
            <div class="grid cols-3" style="margin-top: 6px;">
                <div class="card">
                    <h2>Basic Info</h2>
                    <p><strong>Project:</strong> {{ $system->project?->name }} ({{ $system->project?->project_code }})</p>
                    <p><strong>Type:</strong> {{ strtoupper($system->system_type) }}</p>
                    <p><strong>Status:</strong> {{ ucfirst($system->status) }}</p>
                    <p><strong>Tech Stack:</strong> {{ $system->tech_stack ?: '—' }}</p>
                </div>
                <div class="card">
                    <h2>URLs</h2>
                    <p><strong>Live:</strong> @if($system->live_url)<a href="{{ $system->live_url }}" target="_blank">{{ $system->live_url }}</a>@else — @endif</p>
                    <p><strong>Admin:</strong> @if($system->admin_url)<a href="{{ $system->admin_url }}" target="_blank">{{ $system->admin_url }}</a>@else — @endif</p>
                    <p><strong>Repository:</strong> @if($system->repository_link)<a href="{{ $system->repository_link }}" target="_blank">{{ $system->repository_link }}</a>@else — @endif</p>
                </div>
                <div class="card">
                    <h2>Support Summary</h2>
                    <p><strong>Scope:</strong> {{ $system->supportScope?->scope_name ?: '—' }}</p>
                    <p><strong>Status:</strong> {{ ucfirst(str_replace('_', ' ', $system->support_status)) }}</p>
                    <p><strong>Start:</strong> {{ optional($system->support_start_date)->toDateString() ?: '—' }}</p>
                    <p><strong>End:</strong> {{ optional($system->support_end_date)->toDateString() ?: '—' }}</p>
                </div>
            </div>

            <div class="card" style="margin-top: 14px;">
                <h2>Description</h2>
                <p>{{ $system->description ?: 'No description.' }}</p>
            </div>
        </div>

        <div class="tab-panel" data-panel="infra-support" role="tabpanel">
            <div class="grid cols-3" style="margin-top: 6px;">
                <div class="card">
                    <h2>Infrastructure Mapping</h2>
                    @if($system->infrastructureResources->isEmpty())
                        <p class="muted">No infrastructure mapped.</p>
                    @else
                        <ul>
                            @foreach($system->infrastructureResources as $resource)
                                <li>{{ $resourceTypeLabels[$resource->resource_type] ?? ucfirst(str_replace('_', ' ', $resource->resource_type)) }} - {{ $resource->name }}{{ $resource->vendor ? ' (' . $resource->vendor . ')' : '' }}</li>
                            @endforeach
                        </ul>
                    @endif
                    <a class="pill" href="{{ route('systems.edit', $system) }}">Update mapping</a>
                </div>

                <div class="card">
                    <h2>Extend Support</h2>
                    <form method="post" action="{{ route('systems.support_extensions.store', $system) }}">
                        @csrf
                        <div class="field">
                            <label for="new_end_date">New End Date *</label>
                            <input type="date" id="new_end_date" name="new_end_date" required>
                        </div>
                        <div class="field">
                            <label for="extension_reason">Reason *</label>
                            <textarea id="extension_reason" name="reason" required></textarea>
                        </div>
                        <button class="btn" type="submit">Extend Support</button>
                    </form>
                </div>

                <div class="card">
                    <h2>Support Extension Log</h2>
                    @if($supportExtensions->isEmpty())
                        <p class="muted">No extensions yet.</p>
                    @else
                        <ul>
                            @foreach($supportExtensions as $ext)
                                <li>
                                    {{ optional($ext->extended_at)->toDateString() }}:
                                    {{ optional($ext->previous_end_date)->toDateString() }} -> {{ optional($ext->new_end_date)->toDateString() }}
                                    <br><span class="muted">By {{ $ext->extendedBy?->name ?: 'Unknown' }} | {{ $ext->reason }}</span>
                                </li>
                            @endforeach
                        </ul>
                    @endif
                </div>
            </div>
        </div>

        <div class="tab-panel" data-panel="documentation" role="tabpanel">
            <div class="card" style="margin-top: 6px;">
                <h2>Documentation</h2>
                <form method="post" action="{{ route('systems.documentation.update', $system) }}">
                    @csrf
                    @method('PUT')
                    <div class="field"><label>Overview</label><textarea name="overview">{{ old('overview', $documentation->overview ?? '') }}</textarea></div>
                    <div class="field"><label>Architecture</label><textarea name="architecture">{{ old('architecture', $documentation->architecture ?? '') }}</textarea></div>
                    <div class="field"><label>Infrastructure Mapping</label><textarea name="infrastructure_mapping">{{ old('infrastructure_mapping', $documentation->infrastructure_mapping ?? '') }}</textarea></div>
                    <div class="field"><label>Deployment Process</label><textarea name="deployment_process">{{ old('deployment_process', $documentation->deployment_process ?? '') }}</textarea></div>
                    <div class="field"><label>Recovery Instructions</label><textarea name="recovery_instructions">{{ old('recovery_instructions', $documentation->recovery_instructions ?? '') }}</textarea></div>
                    <div class="field"><label>External Integrations</label><textarea name="external_integrations">{{ old('external_integrations', $documentation->external_integrations ?? '') }}</textarea></div>
                    <div class="field"><label>Notes</label><textarea name="notes">{{ old('notes', $documentation->notes ?? '') }}</textarea></div>
                    <button class="btn" type="submit">Save Documentation</button>
                </form>
            </div>
        </div>

        <div class="tab-panel" data-panel="development-log" role="tabpanel">
            <div class="card" style="margin-top: 6px;">
                <h2>Development Log</h2>
                <form method="post" action="{{ route('systems.development_logs.store', $system) }}">
                    @csrf
                    <div class="form-grid cols-2">
                        <div class="field"><label>Title *</label><input type="text" name="title" required></div>
                        <div class="field"><label>Version</label><input type="text" name="version"></div>
                    </div>
                    <div class="form-grid cols-2">
                        <div class="field">
                            <label>Change Type *</label>
                            <select name="change_type" required>
                                @foreach($changeTypes as $type)
                                    <option value="{{ $type }}">{{ ucfirst(str_replace('_', ' ', $type)) }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="field">
                            <label>Deployment Status *</label>
                            <select name="deployment_status" required>
                                @foreach($deploymentStatuses as $dStatus)
                                    <option value="{{ $dStatus }}">{{ ucfirst(str_replace('_', ' ', $dStatus)) }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                    <div class="field"><label>Change Date *</label><input type="date" name="change_date" value="{{ now()->toDateString() }}" required></div>
                    <div class="field"><label>Description *</label><textarea name="description" required></textarea></div>
                    <button class="btn" type="submit">Add Development Log</button>
                </form>

                <div class="table-wrap" style="margin-top: 14px;">
                    <table>
                        <thead>
                        <tr>
                            <th>Date</th>
                            <th>Title</th>
                            <th>Type</th>
                            <th>Deployment</th>
                            <th>Changed By</th>
                            <th></th>
                        </tr>
                        </thead>
                        <tbody>
                        @forelse($developmentLogs as $log)
                            <tr>
                                <td>{{ optional($log->change_date)->toDateString() }}</td>
                                <td>{{ $log->title }}@if($log->version)<br><span class="muted">v{{ $log->version }}</span>@endif</td>
                                <td>{{ ucfirst(str_replace('_', ' ', $log->change_type)) }}</td>
                                <td>{{ ucfirst(str_replace('_', ' ', $log->deployment_status)) }}</td>
                                <td>{{ $log->changedBy?->name ?: 'Unknown' }}</td>
                                <td>
                                    <form method="post" action="{{ route('systems.development_logs.destroy', [$system, $log]) }}" onsubmit="return confirm('Delete log?');">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="btn danger">Delete</button>
                                    </form>
                                </td>
                            </tr>
                            <tr>
                                <td colspan="6" class="muted">{{ $log->description }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="6" class="muted">No development logs yet.</td></tr>
                        @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
    <script>
        (function () {
            const tabButtons = document.querySelectorAll('.tab-btn[data-tab]');
            const panels = document.querySelectorAll('.tab-panel[data-panel]');
            if (!tabButtons.length || !panels.length) return;

            function activate(tabName) {
                tabButtons.forEach((btn) => {
                    const active = btn.dataset.tab === tabName;
                    btn.classList.toggle('is-active', active);
                    btn.setAttribute('aria-selected', active ? 'true' : 'false');
                });
                panels.forEach((panel) => {
                    panel.classList.toggle('is-active', panel.dataset.panel === tabName);
                });
            }

            tabButtons.forEach((btn) => {
                btn.addEventListener('click', function () {
                    activate(btn.dataset.tab);
                });
            });
        })();
    </script>
@endpush
