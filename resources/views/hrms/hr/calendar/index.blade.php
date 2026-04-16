@extends('hrms.layout')

@section('content')
    <div class="card form-card" style="max-width:640px;">
        <h1>Holiday calendar</h1>
        <p class="muted">Sundays and 2nd &amp; 4th Saturdays are always non-working. Add national holidays, festivals, or other days off below.</p>

        <form method="post" action="{{ route('admin.hrms.calendar.store') }}" style="margin-top:16px;">
            @csrf
            <div class="row" style="gap:12px;align-items:flex-end;">
                <div class="field" style="margin:0;">
                    <label>Date</label>
                    <input type="date" name="observed_on" value="{{ old('observed_on') }}" required>
                </div>
                <div class="field" style="margin:0;flex:1;">
                    <label>Title</label>
                    <input name="title" value="{{ old('title') }}" placeholder="e.g. Republic Day" required>
                </div>
                <button class="btn" type="submit">Add holiday</button>
            </div>
        </form>
    </div>

    <div class="card" style="margin-top:14px;">
        <h2>Additional holidays</h2>
        @if($holidays->isEmpty())
            <p class="muted">No extra holidays defined yet.</p>
        @else
            <table>
                <thead>
                <tr><th>Date</th><th>Title</th><th></th></tr>
                </thead>
                <tbody>
                @foreach($holidays as $h)
                    <tr>
                        <td>{{ $h->observed_on->format('Y-m-d') }}</td>
                        <td>{{ $h->title }}</td>
                        <td>
                            <form method="post" action="{{ route('admin.hrms.calendar.destroy', $h) }}" onsubmit="return confirm('Remove this holiday?');">
                                @csrf
                                @method('DELETE')
                                <button class="btn danger" type="submit">Remove</button>
                            </form>
                        </td>
                    </tr>
                @endforeach
                </tbody>
            </table>
            {{ $holidays->links() }}
        @endif
    </div>
@endsection
