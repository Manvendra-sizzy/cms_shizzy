@extends('hrms.layout')

@section('content')
    <div class="card">
        <div class="row" style="justify-content:space-between;">
            <div>
                <h1>Zoho Clients</h1>
                <p class="muted" style="margin-top:6px;">Listing is served from local database synced from Zoho Books.</p>
            </div>
            <form method="post" action="{{ route('admin.zoho_clients.sync') }}">
                @csrf
                <button class="btn" type="submit">Sync Clients</button>
            </form>
        </div>
    </div>

    <div class="card" style="margin-top:14px;">
        @if($clients->isEmpty())
            <p class="muted">No Zoho clients synced yet. Click <strong>Sync Clients</strong> to fetch from Zoho.</p>
        @else
            <div class="table-wrap">
                <table>
                    <thead>
                    <tr>
                        <th>Zoho Contact ID</th>
                        <th>Contact Name</th>
                        <th>Company Name</th>
                        <th>Email</th>
                        <th>Phone / Mobile</th>
                        <th>GST No</th>
                        <th>Outstanding Receivable Amount</th>
                        <th>Status</th>
                    </tr>
                    </thead>
                    <tbody>
                    @foreach($clients as $client)
                        <tr>
                            <td>{{ $client->zoho_contact_id }}</td>
                            <td>{{ $client->contact_name ?: '—' }}</td>
                            <td>{{ $client->company_name ?: '—' }}</td>
                            <td>{{ $client->email ?: '—' }}</td>
                            <td>
                                <div>{{ $client->phone ?: '—' }}</div>
                                <div class="muted" style="font-size:12px;">{{ $client->mobile ?: '—' }}</div>
                            </td>
                            <td>{{ $client->gst_no ?: '—' }}</td>
                            <td>{{ number_format((float) $client->outstanding_receivable_amount, 2) }}</td>
                            <td>{{ $client->status ?: '—' }}</td>
                        </tr>
                    @endforeach
                    </tbody>
                </table>
            </div>

            <div style="margin-top:14px;">
                {{ $clients->links() }}
            </div>
        @endif
    </div>
@endsection
