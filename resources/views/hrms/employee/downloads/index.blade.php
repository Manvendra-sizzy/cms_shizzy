@extends('hrms.layout')

@section('content')
    <div class="grid" style="grid-template-columns:1fr 1fr;gap:14px;">
        <div class="card">
            <h1>Official documents</h1>
            @if($documents->isEmpty())
                <p class="muted">No documents available.</p>
            @else
                <table>
                    <thead>
                    <tr>
                        <th>Type</th>
                        <th>Title</th>
                        <th>Issued</th>
                        <th></th>
                    </tr>
                    </thead>
                    <tbody>
                    @foreach($documents as $doc)
                        <tr>
                            <td class="muted">{{ $doc->type }}</td>
                            <td><strong>{{ $doc->title }}</strong></td>
                            <td class="muted">{{ optional($doc->issued_at)->format('Y-m-d') ?? '—' }}</td>
                            <td><a class="pill" href="{{ route('employee.documents.download', $doc) }}">Download</a></td>
                        </tr>
                    @endforeach
                    </tbody>
                </table>
            @endif
        </div>

        <div class="card">
            <h1>Salary slips</h1>
            @if($slips->isEmpty())
                <p class="muted">No salary slips available.</p>
            @else
                <table>
                    <thead>
                    <tr>
                        <th>Period</th>
                        <th>Slip #</th>
                        <th>Net</th>
                        <th></th>
                    </tr>
                    </thead>
                    <tbody>
                    @foreach($slips as $slip)
                        <tr>
                            <td class="muted">
                                {{ $slip->payrollRun->period_start->format('Y-m-d') }} → {{ $slip->payrollRun->period_end->format('Y-m-d') }}
                            </td>
                            <td><strong>{{ $slip->slip_number }}</strong></td>
                            <td>{{ $slip->currency }} <strong>{{ $slip->net }}</strong></td>
                            <td><a class="pill" href="{{ route('employee.salary_slips.download', $slip) }}">Download</a></td>
                        </tr>
                    @endforeach
                    </tbody>
                </table>
            @endif
        </div>
    </div>

    <div class="card" style="margin-top:14px;">
        <h1>My uploaded documents</h1>
        @if(($uploadedDocs ?? collect())->isEmpty())
            <p class="muted">No uploaded documents available.</p>
        @else
            <div class="table-wrap">
                <table>
                    <thead>
                    <tr>
                        <th>Title</th>
                        <th>Uploaded</th>
                        <th></th>
                    </tr>
                    </thead>
                    <tbody>
                    @foreach($uploadedDocs as $doc)
                        <tr>
                            <td><strong>{{ $doc->title }}</strong></td>
                            <td class="muted">{{ optional($doc->uploaded_at)->format('Y-m-d') ?? '—' }}</td>
                            <td><a class="pill" href="{{ route('employee.uploaded_documents.download', $doc) }}">Download</a></td>
                        </tr>
                    @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </div>
@endsection

