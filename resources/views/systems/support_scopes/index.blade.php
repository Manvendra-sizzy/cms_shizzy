@extends('hrms.layout')

@section('content')
    <div class="card">
        <div class="row" style="justify-content: space-between;">
            <h1>Support Scope Master</h1>
            <div class="row">
                <a class="pill" href="{{ route('systems.index') }}">Systems</a>
                <a class="pill" href="{{ route('systems.support_scopes.create') }}">Add Scope</a>
            </div>
        </div>
    </div>

    <div class="card" style="margin-top: 14px;">
        <div class="table-wrap">
            <table>
                <thead>
                <tr>
                    <th>Scope Name</th>
                    <th>SLA</th>
                    <th>Active</th>
                    <th></th>
                </tr>
                </thead>
                <tbody>
                @forelse($scopes as $scope)
                    <tr>
                        <td><strong>{{ $scope->scope_name }}</strong><br><span class="muted">{{ $scope->description }}</span></td>
                        <td>{{ $scope->sla_response_time ?: '—' }}</td>
                        <td>{{ $scope->active ? 'Yes' : 'No' }}</td>
                        <td><a class="pill" href="{{ route('systems.support_scopes.edit', $scope) }}">Edit</a></td>
                    </tr>
                @empty
                    <tr><td colspan="4" class="muted">No support scopes found.</td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
        <div style="margin-top: 10px;">{{ $scopes->links() }}</div>
    </div>
@endsection
