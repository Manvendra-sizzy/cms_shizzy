@extends('hrms.layout')

@section('content')
    <div class="card">
        <div class="row" style="justify-content:space-between;">
            <h1>Designations</h1>
            <a class="pill" href="{{ route('admin.organization.index') }}">Back</a>
        </div>

        <div class="card" style="margin-top:12px;max-width:720px;">
            <h2>Create Designation</h2>
            <form method="post" action="{{ route('admin.organization.designations.store') }}">
                @csrf
                <div class="grid" style="grid-template-columns:1fr 1fr;gap:12px;">
                    <div class="field">
                        <label>Name</label>
                        <input name="name" value="{{ old('name') }}" required>
                    </div>
                    <div class="field">
                        <label>Code</label>
                        <input name="code" value="{{ old('code') }}" required>
                    </div>
                </div>
                <button class="btn" type="submit">Create</button>
            </form>
        </div>

        <div class="card" style="margin-top:12px;">
            <h2>All Designations</h2>
            @if($designations->isEmpty())
                <p class="muted">No designations yet.</p>
            @else
                <table>
                    <thead>
                    <tr>
                        <th>Code</th>
                        <th>Name</th>
                        <th>Status</th>
                        <th></th>
                    </tr>
                    </thead>
                    <tbody>
                    @foreach($designations as $d)
                        <tr>
                            <td><strong>{{ $d->code }}</strong></td>
                            <td>{{ $d->name }}</td>
                            <td class="muted">{{ $d->active ? 'Active' : 'Inactive' }}</td>
                            <td class="row" style="justify-content:flex-end;">
                                <a class="pill" href="{{ route('admin.organization.designations.edit', $d) }}">Edit</a>
                                <form method="post" action="{{ route('admin.organization.designations.destroy', $d) }}">
                                    @csrf
                                    @method('DELETE')
                                    <button class="pill" type="submit">Delete</button>
                                </form>
                            </td>
                        </tr>
                    @endforeach
                    </tbody>
                </table>
            @endif
        </div>
    </div>
@endsection

