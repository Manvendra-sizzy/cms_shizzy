@extends('hrms.layout')

@section('content')
    <div class="card form-card">
        <h1>Policies &amp; Guidelines</h1>
        <p class="muted">Add policy entries that are visible to all employees.</p>

        <form method="post" action="{{ route('admin.hrms.policies_guidelines.store') }}" class="form-wrap" style="margin-top:12px;">
            @csrf
            <div class="field">
                <label>Title</label>
                <input type="text" name="title" value="{{ old('title') }}" required>
            </div>
            <div class="field">
                <label>Content</label>
                <textarea name="content" rows="10" required>{{ old('content') }}</textarea>
            </div>
            <div class="field row" style="align-items:center;">
                <label style="display:flex;align-items:center;gap:8px;margin:0;">
                    <input type="checkbox" name="active" value="1" @checked(old('active', '1') === '1') style="width:auto;max-width:none;padding:0;margin:0;">
                    Active (visible to employees)
                </label>
            </div>
            <button class="btn" type="submit">Add Policy</button>
        </form>
    </div>

    <div class="card" style="margin-top:14px;">
        <h1>Existing Entries</h1>
        @if(($policies ?? collect())->isEmpty())
            <p class="muted">No entries found.</p>
        @else
            <div class="table-wrap">
                <table>
                    <thead>
                    <tr>
                        <th>Title</th>
                        <th>Status</th>
                        <th>Preview</th>
                    </tr>
                    </thead>
                    <tbody>
                    @foreach($policies as $policy)
                        <tr>
                            <td><strong>{{ $policy->title }}</strong></td>
                            <td class="muted">{{ $policy->active ? 'Active' : 'Inactive' }}</td>
                            <td class="muted">{{ \Illuminate\Support\Str::limit($policy->content, 160) }}</td>
                        </tr>
                    @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </div>
@endsection

