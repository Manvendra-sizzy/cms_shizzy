@extends('hrms.layout')

@section('content')
    <div class="card">
        <h1>Admin dashboard</h1>
        <div class="grid cols-3" style="margin-top:12px;">
            <div class="card">
                <h2>Total users</h2>
                <div class="kpi">{{ $usersCount }}</div>
            </div>
            <div class="card">
                <h2>Admins</h2>
                <div class="kpi">{{ $adminsCount }}</div>
            </div>
            <div class="card">
                <h2>Employees</h2>
                <div class="kpi">{{ $employeesCount }}</div>
            </div>
        </div>
        <div class="card" style="margin-top:14px;">
            <h2>Modules</h2>
            <div class="kpi">{{ $modulesCount }}</div>
            <div class="row" style="margin-top: 10px;">
                <a class="pill" href="{{ route('admin.hrms.dashboard') }}">HRMS</a>
                @if(!empty($projectsUrl))
                    <a class="pill" href="{{ $projectsUrl }}">Projects</a>
                @endif
                @if(!empty($assetsUrl))
                    <a class="pill" href="{{ $assetsUrl }}">Asset Management</a>
                @endif
                @if(!empty($systemsUrl))
                    <a class="pill" href="{{ $systemsUrl }}">Systems</a>
                @endif
            </div>
        </div>
    </div>

    <div class="card" style="margin-top:14px;">
        <h2>Departments &amp; teams</h2>
        <p class="muted">Active departments and their teams (from Organization Structure).</p>
        @if(($departments ?? collect())->isEmpty())
            <p class="muted">No departments yet. Use Organization Structure to add departments and teams.</p>
        @else
            <table>
                <thead>
                <tr>
                    <th>Department</th>
                    <th>Teams</th>
                </tr>
                </thead>
                <tbody>
                @foreach($departments as $dept)
                    <tr>
                        <td><strong>{{ $dept->name }}</strong></td>
                        <td class="muted">
                            @if($dept->teams->isEmpty())
                                —
                            @else
                                @foreach($dept->teams as $team)
                                    {{ $team->name }}@if(!$loop->last)<br>@endif
                                @endforeach
                            @endif
                        </td>
                    </tr>
                @endforeach
                </tbody>
            </table>
        @endif
    </div>
@endsection

