@extends('hrms.layout')

@section('content')
    <div class="card form-card">
        <div class="row" style="justify-content:space-between;align-items:flex-start;">
            <h1>Edit invoice</h1>
            <a class="pill" href="{{ route('projects.show', $project) }}">Back to project</a>
        </div>

        <p class="muted" style="margin-top:6px;">
            Project: <strong>{{ $project->name }}</strong> ({{ $project->project_code }})<br>
            Stream: <strong>{{ $stream->name }}</strong>
        </p>

        <form method="post" action="{{ route('projects.revenue.invoices.update', [$project, $stream, $invoice]) }}" class="form-wrap" style="margin-top:14px;">
            @csrf
            @method('PUT')

            <div class="form-grid cols-2">
                <div class="field">
                    <label>Invoice #</label>
                        <input name="invoice_number" value="{{ old('invoice_number', $invoice->invoice_number) }}" required>
                </div>
                <div class="field">
                    <label>Status</label>
                    <select name="status" required>
                        @foreach(['draft'=>'Draft','sent'=>'Sent','paid'=>'Paid','cancelled'=>'Cancelled'] as $k=>$v)
                            <option value="{{ $k }}" @selected(old('status', $invoice->status)===$k)>{{ $v }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="field">
                    <label>Invoice date</label>
                        <input name="invoice_date" type="date" value="{{ old('invoice_date', optional($invoice->invoice_date)->format('Y-m-d')) }}" required>
                </div>
                <div class="field">
                    <label>Due date</label>
                    <input name="due_date" type="date" value="{{ old('due_date', optional($invoice->due_date)->format('Y-m-d')) }}">
                </div>
                <div class="field">
                    <label>Amount (₹)</label>
                    <input name="amount" type="number" step="0.01" min="0" required value="{{ old('amount', $invoice->amount) }}">
                </div>
                <div class="field" style="grid-column:1 / -1;">
                    <label>Notes</label>
                        <textarea name="notes" required>{{ old('notes', $invoice->notes) }}</textarea>
                </div>

                    <div class="field" style="grid-column:1 / -1;">
                        <label>Upload Invoice PDF (optional)</label>
                        <input name="invoice_pdf" type="file" accept="application/pdf">
                    </div>
            </div>

            <button class="btn" type="submit">Save changes</button>
        </form>
    </div>
@endsection

