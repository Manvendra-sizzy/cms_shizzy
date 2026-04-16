@extends('hrms.layout')

@section('content')
    <div class="card form-card">
        <div class="row" style="justify-content:space-between;align-items:flex-start;">
            <h1>Apply for reimbursement</h1>
            <a class="pill" href="{{ route('employee.reimbursements.index') }}">Back</a>
        </div>

        <form method="post" action="{{ route('employee.reimbursements.store') }}" enctype="multipart/form-data" class="form-wrap" style="margin-top:12px;">
            @csrf
            <div class="form-grid cols-2">
                <div class="field">
                    <label for="title">Title <span style="color:#b91c1c;">*</span></label>
                    <input id="title" name="title" type="text" value="{{ old('title') }}" required maxlength="255" placeholder="e.g. Client visit — cab">
                </div>
                <div class="field">
                    <label for="category">Category</label>
                    <input id="category" name="category" type="text" value="{{ old('category') }}" maxlength="120" placeholder="Travel, meals, medical…">
                </div>
            </div>
            <div class="form-grid cols-2">
                <div class="field">
                    <label for="expense_date">Expense date <span style="color:#b91c1c;">*</span></label>
                    <input id="expense_date" name="expense_date" type="date" value="{{ old('expense_date') }}" required>
                </div>
                <div class="field">
                    <label for="amount">Amount <span style="color:#b91c1c;">*</span></label>
                    <input id="amount" name="amount" type="number" step="0.01" min="0.01" value="{{ old('amount') }}" required>
                </div>
            </div>
            <div class="field">
                <label for="description">Description</label>
                <textarea id="description" name="description" placeholder="Details HR may need to verify the claim">{{ old('description') }}</textarea>
            </div>
            <div class="field">
                <label for="receipt">Receipt (PDF or image, max 5 MB)</label>
                <input id="receipt" name="receipt" type="file" accept=".pdf,image/jpeg,image/png,image/webp">
            </div>
            <div class="action-row">
                <button class="btn" type="submit">Submit request</button>
            </div>
        </form>
    </div>
@endsection
