@extends('hrms.layout')

@section('content')
    <div class="card">
        <div class="row" style="justify-content:space-between;">
            <h1>Asset categories</h1>
            <div class="row">
                <a class="pill" href="{{ route('assets.index') }}">Assets</a>
                <a class="pill" href="{{ route('assets.categories.create') }}">Add category</a>
            </div>
        </div>

        @if($categories->isEmpty())
            <p class="muted">No categories yet.</p>
        @else
            <table>
                <thead>
                <tr>
                    <th>Name</th>
                    <th>Description</th>
                    <th>Status</th>
                    <th></th>
                </tr>
                </thead>
                <tbody>
                @foreach($categories as $c)
                    <tr>
                        <td><strong>{{ $c->name }}</strong></td>
                        <td class="muted">{{ $c->description ?? '—' }}</td>
                        <td class="muted">{{ $c->active ? 'Active' : 'Inactive' }}</td>
                        <td>
                            <div class="row">
                                <a class="pill" href="{{ route('assets.categories.edit', $c) }}">Edit</a>
                                <form method="post" action="{{ route('assets.categories.destroy', $c) }}" onsubmit="return confirm('Delete this category?');">
                                    @csrf
                                    @method('DELETE')
                                    <button class="pill" type="submit">Delete</button>
                                </form>
                            </div>
                        </td>
                    </tr>
                @endforeach
                </tbody>
            </table>

            {{ $categories->links() }}
        @endif
    </div>
@endsection

