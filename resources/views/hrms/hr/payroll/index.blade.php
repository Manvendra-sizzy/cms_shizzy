@extends('hrms.layout')

@section('content')
    <div class="card">
        <div class="row" style="justify-content:space-between;align-items:flex-start;">
            <div>
                <h1>Payroll</h1>
                <p class="muted" style="margin:6px 0 0 0;">Payroll history and run new payroll.</p>
            </div>
            <a class="pill" href="{{ route('admin.hrms.payroll.create') }}">Run new payroll</a>
        </div>

        <table>
            <thead>
            <tr>
                <th>ID</th>
                <th>Period</th>
                <th>Status</th>
                <th>Processed</th>
                <th></th>
            </tr>
            </thead>
            <tbody>
            @foreach($runs as $run)
                <tr>
                    <td class="muted">#{{ $run->id }}</td>
                    <td><strong>{{ $run->period_start->format('Y-m-d') }}</strong> → {{ $run->period_end->format('Y-m-d') }}</td>
                    <td class="muted">{{ $run->status }}</td>
                    <td class="muted">{{ optional($run->processed_at)->format('Y-m-d H:i') ?? '—' }}</td>
                    <td><a class="pill" href="{{ route('admin.hrms.payroll.show', $run) }}">Open</a></td>
                </tr>
            @endforeach
            </tbody>
        </table>

        <div style="margin-top:12px;">{{ $runs->links() }}</div>
    </div>
@endsection

