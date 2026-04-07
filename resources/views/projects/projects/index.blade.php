@extends('hrms.layout')

@section('content')
    <div class="card">
        <div class="row" style="justify-content:space-between;align-items:flex-start;">
            <div>
                <h1>Projects View</h1>
                <p class="muted" style="margin:0;">Modern workspace to manage all active and upcoming projects.</p>
            </div>
            <div class="row" style="justify-content:flex-end;">
                <a class="pill" href="{{ route('projects.categories.index') }}">Categories</a>
                <a class="pill" href="{{ route('projects.create') }}">Create project</a>
                <a class="pill" href="{{ route('projects.finances.radar') }}">Project Finance Radar</a>
            </div>
        </div>

        @if($projects->isEmpty())
            <p class="muted">No projects yet.</p>
        @else
            <div class="grid cols-3" style="margin-top:12px;">
                <div class="card">
                    <div class="muted">Total projects</div>
                    <div class="kpi">{{ $projects->total() }}</div>
                </div>
                <div class="card">
                    <div class="muted">Active projects on page</div>
                    <div class="kpi">{{ $projects->where('status', 'active')->count() }}</div>
                </div>
                <div class="card">
                    <div class="muted">Categories in view</div>
                    <div class="kpi">{{ $projects->pluck('category')->filter()->unique()->count() }}</div>
                </div>
            </div>

            <div class="table-wrap" style="margin-top:14px;">
                <table>
                    <thead>
                    <tr>
                        <th>Project ID</th>
                        <th>Name</th>
                        <th>Client</th>
                        <th>Category</th>
                        <th>Status</th>
                        <th>PM</th>
                        <th>AM</th>
                        <th></th>
                    </tr>
                    </thead>
                    <tbody>
                    @foreach($projects as $p)
                        <tr>
                            <td><strong>{{ $p->project_code }}</strong></td>
                            <td>{{ $p->name }}</td>
                            <td class="muted">
                                @if($p->is_internal)
                                    Internal Project
                                @else
                                    {{ $p->zohoClient?->contact_name ?: ($p->zohoClient?->company_name ?: $p->client?->name ?? '—') }}
                                @endif
                            </td>
                            <td>
                                <span class="pill" style="padding:6px 10px;border-radius:999px;background:#eff6ff;border-color:#bfdbfe;color:#1e3a8a;">
                                    {{ $p->category }}
                                </span>
                            </td>
                            <td>
                                @php($isActive = ($p->status ?? '') === 'active')
                                <span class="pill" style="padding:6px 10px;border-radius:999px;{{ $isActive ? 'background:#ecfdf3;border-color:#a7f3d0;color:#065f46;' : 'background:#fff7ed;border-color:#fed7aa;color:#9a3412;' }}">
                                    {{ ucfirst($p->status) }}
                                </span>
                            </td>
                            <td class="muted">{{ $p->projectManager?->user?->name ?? '—' }}</td>
                            <td class="muted">{{ $p->accountManager?->user?->name ?? '—' }}</td>
                            <td><a class="pill" href="{{ route('projects.show', $p) }}">Open</a></td>
                        </tr>
                    @endforeach
                    </tbody>
                </table>
            </div>

            {{ $projects->links() }}
        @endif
    </div>
@endsection

