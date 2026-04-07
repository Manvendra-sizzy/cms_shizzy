@extends('hrms.layout')

@section('content')
    <div class="card">
        <div class="row" style="justify-content:space-between;">
            <h1>Zoho Clients</h1>
            <div class="row">
                <a class="pill" href="{{ route('projects.index') }}">Projects</a>
            </div>
        </div>

        <div class="row" style="justify-content:space-between;">
            <h2 style="margin-bottom:0;">Synced from Zoho Books</h2>
            @if(\Illuminate\Support\Facades\Route::has('admin.zoho_clients.index'))
                <a class="pill" href="{{ route('admin.zoho_clients.index') }}">Open Zoho Sync Page</a>
            @endif
        </div>

        @if(($zohoClients ?? collect())->isEmpty())
            <p class="muted" style="margin-top:10px;">No Zoho clients synced yet.</p>
        @else
            <div class="table-wrap" style="margin-top:10px;">
                <table>
                    <thead>
                    <tr>
                        <th>Zoho Contact ID</th>
                        <th>Contact Name</th>
                        <th>Company Name</th>
                        <th>Email</th>
                        <th>Phone / Mobile</th>
                        <th>GST No</th>
                        <th>Outstanding</th>
                        <th>Status</th>
                    </tr>
                    </thead>
                    <tbody>
                    @foreach($zohoClients as $z)
                        <tr>
                            <td>{{ $z->zoho_contact_id }}</td>
                            <td><strong>{{ $z->contact_name ?: '—' }}</strong></td>
                            <td class="muted">{{ $z->company_name ?: '—' }}</td>
                            <td class="muted">{{ $z->email ?: '—' }}</td>
                            <td>
                                <div class="muted">{{ $z->phone ?: '—' }}</div>
                                <div class="muted" style="font-size:12px;">{{ $z->mobile ?: '—' }}</div>
                            </td>
                            <td class="muted">{{ $z->gst_no ?: '—' }}</td>
                            <td class="muted">{{ number_format((float) $z->outstanding_receivable_amount, 2) }}</td>
                            <td class="muted">{{ $z->status ?: '—' }}</td>
                        </tr>
                    @endforeach
                    </tbody>
                </table>
            </div>

            <div style="margin-top:12px;">
                {{ $zohoClients->links() }}
            </div>
        @endif
    </div>
@endsection

