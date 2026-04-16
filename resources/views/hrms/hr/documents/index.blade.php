@extends('hrms.layout')

@section('content')
    <div class="card">
        <div class="row" style="justify-content:space-between;">
            <h1>Documents</h1>
            <a class="pill" href="{{ route('admin.hrms.documents.create') }}">Issue document</a>
        </div>

        <table>
            <thead>
            <tr>
                <th>ID</th>
                <th>Employee</th>
                <th>Type</th>
                <th>Issued</th>
                <th></th>
            </tr>
            </thead>
            <tbody>
            @foreach($documents as $doc)
                <tr>
                    <td class="muted">#{{ $doc->id }}</td>
                    <td><strong>{{ $doc->employeeProfile->employee_id }}</strong> — {{ $doc->employeeProfile->user->name }}</td>
                    <td class="muted">{{ $doc->typeLabel() }}</td>
                    <td class="muted">{{ optional($doc->issued_at)->format('Y-m-d') ?? '—' }}</td>
                    <td><a class="pill" href="{{ route('admin.hrms.documents.download', $doc) }}">Download</a></td>
                </tr>
            @endforeach
            </tbody>
        </table>

        <div style="margin-top:12px;">{{ $documents->links() }}</div>
    </div>
@endsection

