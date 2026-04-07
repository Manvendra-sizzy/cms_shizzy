@extends('hrms.layout')

@section('content')
    <div class="card form-card">
        <div class="row" style="justify-content:space-between;align-items:flex-start;">
            <h1>Edit reimbursement</h1>
            <a class="pill" href="{{ route('projects.show', $project) }}">Back to project</a>
        </div>

        <p class="muted" style="margin-top:6px;">
            Project: <strong>{{ $project->name }}</strong> ({{ $project->project_code }})
        </p>

        <form method="post" action="{{ route('projects.revenue.reimbursements.update', [$project, $reimbursement]) }}" class="form-wrap" style="margin-top:14px;">
            @csrf
            @method('PUT')

            <div class="form-grid cols-2">
                <div class="field">
                    <label>Date</label>
                    <input name="spent_date" type="date" value="{{ old('spent_date', optional($reimbursement->spent_date)->format('Y-m-d')) }}">
                </div>
                <div class="field">
                    <label>Spend amount (₹)</label>
                    <input name="spend_amount" type="number" step="0.01" min="0" required value="{{ old('spend_amount', $reimbursement->spend_amount) }}">
                </div>
                <div class="field" style="grid-column:1 / -1;">
                    <label>Description</label>
                    <input name="description" value="{{ old('description', $reimbursement->description) }}">
                </div>
                <div class="field">
                    <label>Markup type</label>
                    <select name="markup_type" required>
                        <option value="percent" @selected(old('markup_type', $reimbursement->markup_type)==='percent')>Percent</option>
                        <option value="fixed" @selected(old('markup_type', $reimbursement->markup_type)==='fixed')>Fixed</option>
                    </select>
                </div>
                <div class="field">
                    <label>Markup value</label>
                    <input name="markup_value" type="number" step="0.01" min="0" value="{{ old('markup_value', $reimbursement->markup_value) }}">
                </div>
                <div class="field">
                    <label>Status</label>
                    <select name="status" required>
                        <option value="not_billed" @selected(old('status', $reimbursement->status)==='not_billed')>Not billed</option>
                        <option value="billed" @selected(old('status', $reimbursement->status)==='billed')>Billed</option>
                        <option value="recovered" @selected(old('status', $reimbursement->status)==='recovered')>Recovered</option>
                    </select>
                </div>
                <div class="field">
                    <label>Link to stream (optional)</label>
                    <select name="project_revenue_stream_id">
                        <option value="">—</option>
                        @foreach($streams as $s)
                            <option value="{{ $s->id }}" @selected(old('project_revenue_stream_id', $reimbursement->project_revenue_stream_id)==$s->id)>{{ $s->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="field" style="grid-column:1 / -1;">
                    <label>Notes</label>
                    <textarea name="notes">{{ old('notes', $reimbursement->notes) }}</textarea>
                </div>
            </div>

            <button class="btn" type="submit">Save changes</button>
        </form>
    </div>
@endsection

