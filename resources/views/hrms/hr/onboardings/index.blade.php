@extends('hrms.layout')

@section('content')
    <div class="card">
        <div class="row" style="justify-content:space-between;align-items:center;">
            <div>
                <h1>Employee Onboardings</h1>
                <p class="muted">Track pre-onboarding, submissions, approvals, and agreement status.</p>
            </div>
            <a class="btn" href="{{ route('admin.hrms.onboardings.create') }}">Start onboarding</a>
        </div>

        <form method="get" class="row" style="gap:10px;margin-top:12px;">
            <input type="text" name="search" value="{{ request('search') }}" placeholder="Name or email">
            <select name="status">
                <option value="">All statuses</option>
                @foreach($statuses as $status)
                    <option value="{{ $status }}" @selected(request('status')===$status)>{{ ucwords(str_replace('_',' ', $status)) }}</option>
                @endforeach
            </select>
            <button class="pill" type="submit">Filter</button>
        </form>

        <div class="row" style="gap:8px;flex-wrap:wrap;margin-top:10px;">
            @foreach($statuses as $s)
                <span class="pill">{{ ucwords(str_replace('_',' ', $s)) }}: {{ (int)($counts[$s] ?? 0) }}</span>
            @endforeach
        </div>

        <div class="table-wrap" style="margin-top:12px;">
            <table>
                <thead><tr><th>Name</th><th>Email</th><th>Type</th><th>Status</th><th>Link sent</th><th>Zoho Sign</th><th></th></tr></thead>
                <tbody>
                @forelse($items as $item)
                    <tr>
                        <td>{{ $item->full_name }}</td>
                        <td>{{ $item->email }}</td>
                        <td>{{ ucfirst(str_replace('_',' ', $item->employee_type)) }}</td>
                        <td>{{ ucwords(str_replace('_',' ', $item->status)) }}</td>
                        <td>{{ optional($item->link_sent_at)->format('Y-m-d H:i') ?? '—' }}</td>
                        <td>{{ $item->zoho_sign_status ?: '—' }}</td>
                        <td><a class="pill" href="{{ route('admin.hrms.onboardings.show', $item) }}">View</a></td>
                    </tr>
                @empty
                    <tr><td colspan="7" class="muted">No onboarding records found.</td></tr>
                @endforelse
                </tbody>
            </table>
        </div>

        <div style="margin-top:12px;">{{ $items->links() }}</div>
    </div>
@endsection

