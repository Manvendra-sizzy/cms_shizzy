@extends('hrms.layout')

@section('content')
    <div class="card">
        <div class="row" style="justify-content: space-between;">
            <h1>Systems</h1>
            <div class="row">
                <a class="pill" href="{{ route('systems.infrastructure.index') }}">Infrastructure</a>
                <a class="pill" href="{{ route('systems.support_scopes.index') }}">Support Scopes</a>
                <a class="pill" href="{{ route('systems.create') }}">Add System</a>
            </div>
        </div>

        <form method="get" class="form-grid cols-2" style="margin-top: 12px;">
            <div class="field">
                <label for="project_id">Project</label>
                <select name="project_id" id="project_id">
                    <option value="">All projects</option>
                    @foreach($projects as $project)
                        <option value="{{ $project->id }}" @selected((string) request('project_id') === (string) $project->id)>
                            {{ $project->name }} ({{ $project->project_code }})
                        </option>
                    @endforeach
                </select>
            </div>
            <div class="field">
                <label for="status">Status</label>
                <select name="status" id="status">
                    <option value="">All statuses</option>
                    @foreach($statuses as $status)
                        <option value="{{ $status }}" @selected(request('status') === $status)>{{ ucfirst($status) }}</option>
                    @endforeach
                </select>
            </div>
            <div class="field">
                <label for="q">Search</label>
                <input type="text" id="q" name="q" value="{{ request('q') }}" placeholder="System name, type, stack">
            </div>
            <div class="field" style="display:flex;align-items:end;">
                <button class="btn" type="submit">Apply Filters</button>
            </div>
        </form>
    </div>

    <div class="card" style="margin-top: 14px;">
        <div class="table-wrap">
            <table>
                <thead>
                <tr>
                    <th>System</th>
                    <th>Project</th>
                    <th>Type</th>
                    <th>Status</th>
                    <th>Support</th>
                    <th></th>
                </tr>
                </thead>
                <tbody>
                @forelse($systems as $system)
                    <tr>
                        <td>
                            <strong>{{ $system->system_name }}</strong><br>
                            <span class="muted">{{ $system->tech_stack ?: '—' }}</span>
                        </td>
                        <td>{{ $system->project?->name ?? '—' }}</td>
                        <td>{{ strtoupper($system->system_type) }}</td>
                        <td>{{ ucfirst($system->status) }}</td>
                        <td>
                            {{ ucfirst(str_replace('_', ' ', $system->support_status)) }}<br>
                            <span class="muted">{{ optional($system->support_end_date)->toDateString() ?: 'No end date' }}</span>
                        </td>
                        <td><a class="pill" href="{{ route('systems.show', $system) }}">Open</a></td>
                    </tr>
                @empty
                    <tr><td colspan="6" class="muted">No systems found.</td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
        <div style="margin-top: 10px;">{{ $systems->links() }}</div>
    </div>
@endsection
