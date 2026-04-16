@extends('hrms.layout')

@section('content')
    <div class="card">
        <div class="row" style="justify-content:space-between;">
            <h1>Employees</h1>
            <a class="pill" href="{{ route('admin.hrms.employees.create') }}">Add employee</a>
        </div>

        <table>
            <thead>
            <tr>
                <th>Employee ID</th>
                <th>Name</th>
                <th>Badge</th>
                <th>Email</th>
                <th>Dept</th>
                <th>Designation</th>
                <th>Status</th>
                <th></th>
            </tr>
            </thead>
            <tbody>
            @foreach($employees as $emp)
                <tr>
                    <td><strong>{{ $emp->employee_id }}</strong></td>
                    <td>{{ $emp->user->name }}</td>
                    <td>
                        <span style="display:inline-flex;align-items:center;padding:4px 10px;border-radius:999px;font-size:12px;font-weight:600;background:#eef2ff;color:#3730a3;border:1px solid #c7d2fe;">
                            {{ $emp->badgeLabel() }}
                        </span>
                    </td>
                    <td class="muted">{{ $emp->user->email }}</td>
                    <td>{{ $emp->orgDepartment?->name ?? '—' }}</td>
                    <td>{{ $emp->orgDesignation?->name ?? '—' }}</td>
                    <td class="muted">{{ ucfirst($emp->status) }}</td>
                    <td class="row" style="justify-content:flex-end;">
                        <a class="pill" href="{{ route('admin.hrms.employees.show', $emp) }}">View</a>
                    </td>
                </tr>
            @endforeach
            </tbody>
        </table>

        <div style="margin-top:12px;">{{ $employees->links() }}</div>
    </div>
@endsection

