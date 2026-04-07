@extends('hrms.layout')

@section('content')
    <div class="card">
        <div class="row" style="justify-content:space-between;">
            <div>
                <h1>Zoho Invoices</h1>
                <p class="muted" style="margin-top:6px;">Listing is served from local database synced from Zoho Books.</p>
            </div>
            <form method="post" action="{{ route('admin.zoho_invoices.sync') }}">
                @csrf
                <button class="btn" type="submit">Sync Invoices</button>
            </form>
        </div>
    </div>

    <div class="card" style="margin-top:14px;">
        @if($invoices->isEmpty())
            <p class="muted">No Zoho invoices synced yet. Click <strong>Sync Invoices</strong> to fetch from Zoho.</p>
        @else
            <div class="table-wrap">
                <table>
                    <thead>
                    <tr>
                        <th>Invoice Number</th>
                        <th>Zoho Invoice ID</th>
                        <th>Customer ID</th>
                        <th>Project ID</th>
                        <th>Status</th>
                        <th>Total</th>
                        <th>Balance</th>
                        <th>Invoice Date</th>
                        <th>Due Date</th>
                        <th></th>
                    </tr>
                    </thead>
                    <tbody>
                    @foreach($invoices as $invoice)
                        <tr>
                            <td>{{ $invoice->invoice_number ?: '—' }}</td>
                            <td>{{ $invoice->zoho_invoice_id }}</td>
                            <td>{{ $invoice->zoho_customer_id ?: '—' }}</td>
                            <td>{{ $invoice->project_id ?: '—' }}</td>
                            <td>{{ $invoice->status ?: '—' }}</td>
                            <td>{{ number_format((float) $invoice->total, 2) }}</td>
                            <td>{{ number_format((float) $invoice->balance, 2) }}</td>
                            <td>{{ $invoice->invoice_date?->format('Y-m-d') ?: '—' }}</td>
                            <td>{{ $invoice->due_date?->format('Y-m-d') ?: '—' }}</td>
                            <td>
                                <a class="pill" href="{{ route('admin.zoho_invoices.download', $invoice) }}">Download</a>
                            </td>
                        </tr>
                    @endforeach
                    </tbody>
                </table>
            </div>

            <div style="margin-top:14px;">
                {{ $invoices->links() }}
            </div>
        @endif
    </div>
@endsection
