@extends('hrms.layout')

@section('content')
    <div class="card">
        <div class="row" style="justify-content:space-between;">
            <h1>Salary – {{ $employee->user->name }} ({{ $employee->employee_id }})</h1>
            <a class="pill" href="{{ route('admin.hrms.employees.show', $employee) }}">Back</a>
        </div>

        <p class="muted" style="margin-top:8px;">
            Current salary: <strong>{{ $employee->current_salary ? '₹'.number_format($employee->current_salary,2) : 'Not set' }}</strong>
        </p>

        <div class="card" style="margin-top:12px;max-width:720px;">
            <h2>Amend salary</h2>
            <form method="post" action="{{ route('admin.hrms.employees.salary.amend', $employee) }}">
                @csrf
                <div class="grid" style="grid-template-columns:1fr 1fr;gap:12px;">
                    <div class="field">
                        <label>Effective date</label>
                        <input name="effective_date" type="date" value="{{ old('effective_date') }}" required>
                    </div>
                    <div class="field">
                        <label>Revised salary</label>
                        <input name="amount" type="number" step="0.01" min="0" value="{{ old('amount') }}" required>
                    </div>
                </div>
                <div class="field">
                    <label>Reason</label>
                    <textarea name="reason">{{ old('reason') }}</textarea>
                </div>
                <button class="btn" type="submit">Save amendment</button>
            </form>
        </div>

        <div class="card" style="margin-top:12px;">
            <h2>Salary history</h2>
            @if($history->isEmpty())
                <p class="muted">No salary history yet.</p>
            @else
                <table>
                    <thead>
                    <tr>
                        <th>Effective date</th>
                        <th>Amount</th>
                        <th>Reason</th>
                    </tr>
                    </thead>
                    <tbody>
                    @foreach($history as $row)
                        <tr>
                            <td class="muted">{{ $row->effective_date->format('Y-m-d') }}</td>
                            <td>₹{{ number_format($row->amount, 2) }}</td>
                            <td class="muted">{{ $row->reason ?? '—' }}</td>
                        </tr>
                    @endforeach
                    </tbody>
                </table>
            @endif
        </div>
    </div>
@endsection

