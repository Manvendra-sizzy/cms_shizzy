@extends('hrms.layout')

@section('content')
    <div class="card">
        <div class="row" style="justify-content:space-between;align-items:flex-start;">
            <h1>Salary slips — {{ $employee->user->name }} ({{ $employee->employee_id }})</h1>
            <a class="pill" href="{{ route('admin.hrms.employees.show', $employee) }}">Back to profile</a>
        </div>

        @if($slips->isEmpty())
            <p class="muted" style="margin-top:12px;">No salary slips yet.</p>
        @else
            <div class="table-wrap" style="margin-top:12px;">
                <table>
                    <thead>
                    <tr>
                        <th>Slip #</th>
                        <th>Period</th>
                        <th>Issued</th>
                        <th>Gross</th>
                        <th>Deductions</th>
                        <th>Net</th>
                        <th></th>
                    </tr>
                    </thead>
                    <tbody>
                    @foreach($slips as $slip)
                        <tr>
                            <td><strong>{{ $slip->slip_number }}</strong></td>
                            <td class="muted">
                                @if($slip->payrollRun)
                                    {{ $slip->payrollRun->period_start->format('Y-m-d') }} → {{ $slip->payrollRun->period_end->format('Y-m-d') }}
                                @else
                                    —
                                @endif
                            </td>
                            <td class="muted">{{ optional($slip->issued_at)->format('Y-m-d') ?? '—' }}</td>
                            <td>{{ $slip->currency }} {{ number_format($slip->gross, 2) }}</td>
                            <td>{{ $slip->currency }} {{ number_format($slip->deductions, 2) }}</td>
                            <td><strong>{{ $slip->currency }} {{ number_format($slip->net, 2) }}</strong></td>
                            <td><a class="pill" href="{{ route('admin.hrms.payroll.slips.download', $slip) }}">Download</a></td>
                        </tr>
                    @endforeach
                    </tbody>
                </table>
            </div>
            <div style="margin-top:12px;">{{ $slips->links() }}</div>
        @endif
    </div>
@endsection
