@extends('hrms.layout')

@section('content')
    <div class="card">
        <div class="row" style="justify-content:space-between;">
            <h1>Users</h1>
            <a class="pill" href="{{ route('admin.users.create') }}">Assign role</a>
        </div>

        <table>
            <thead>
            <tr>
                <th>ID</th>
                <th>Name</th>
                <th>Email</th>
                <th>Base Role</th>
                <th>Assigned Roles</th>
                <th>2FA</th>
            </tr>
            </thead>
            <tbody>
            @foreach($users as $u)
                <tr>
                    <td class="muted">#{{ $u->id }}</td>
                    <td><strong>{{ $u->name }}</strong></td>
                    <td class="muted">{{ $u->email }}</td>
                    <td>{{ $u->role }}</td>
                    <td>
                        @if($u->appRoleAssignments->isEmpty())
                            <span class="muted">—</span>
                        @else
                            @foreach($u->appRoleAssignments as $assignment)
                                <div>
                                    <strong>{{ $assignment->role?->name ?? 'Role' }}</strong>
                                    @if($assignment->role?->key === 'developer')
                                        <span class="muted">
                                            ({{ $assignment->all_projects ? 'All systems' : 'Selected: ' . $assignment->systems->count() }})
                                        </span>
                                    @endif
                                </div>
                            @endforeach
                        @endif
                    </td>
                    <td>
                        @if($u->two_factor_enabled_at)
                            <span class="muted">Enabled</span>
                        @else
                            <span class="muted">Not set</span>
                        @endif
                        <form method="post" action="{{ route('admin.users.twofactor.reset', $u) }}" style="margin-top:6px;">
                            @csrf
                            <button class="pill" type="submit">Reset 2FA</button>
                        </form>
                    </td>
                </tr>
            @endforeach
            </tbody>
        </table>

        <div style="margin-top:12px;">{{ $users->links() }}</div>
    </div>
@endsection

