@extends('hrms.layout')

@section('content')
    <div class="card">
        <div class="row" style="justify-content:space-between;">
            <h1>Leave policies</h1>
            <a class="pill" href="{{ route('admin.hrms.leave_policies.create') }}">Add policy</a>
        </div>

        <table>
            <thead>
            <tr>
                <th>Code</th>
                <th>Name</th>
                <th>Allowance</th>
                <th>Carry forward</th>
                <th>Approval</th>
                <th>Paid</th>
                <th>Status</th>
            </tr>
            </thead>
            <tbody>
            @foreach($policies as $p)
                <tr>
                    <td><strong>{{ $p->code }}</strong></td>
                    <td>{{ $p->name }}</td>
                    <td class="muted">{{ $p->annual_allowance }} / year</td>
                    <td class="muted">{{ $p->carry_forward ? 'Yes' : 'No' }} (max {{ $p->max_carry_forward }})</td>
                    <td class="muted">{{ $p->requires_approval ? 'Required' : 'Not required' }}</td>
                    <td class="muted">{{ $p->is_paid ? 'Yes' : 'Unpaid' }}</td>
                    <td class="muted">{{ $p->active ? 'Active' : 'Inactive' }}</td>
                </tr>
            @endforeach
            </tbody>
        </table>
    </div>
@endsection

