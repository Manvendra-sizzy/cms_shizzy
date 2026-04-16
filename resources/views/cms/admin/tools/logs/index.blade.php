@extends('hrms.layout')

@section('content')
    <style>
        .tools-logs-pagination nav svg {
            width: 16px;
            height: 16px;
            display: inline-block;
            vertical-align: middle;
        }
        .tools-logs-pagination nav span,
        .tools-logs-pagination nav a {
            font-size: 13px;
            line-height: 1.2;
        }
    </style>

    <div class="card">
        <h1>Tools</h1>
        <h2>Activity Logs</h2>
        <p class="muted" style="margin-top:6px;">Retention: last 15 days (older logs are auto-deleted daily).</p>

        <form method="get" action="{{ route('admin.tools.logs.index') }}">
            <div class="form-grid cols-2">
                <div class="field">
                    <label>Search (action, URL, user, IP)</label>
                    <input name="q" type="text" value="{{ $filters['q'] ?? '' }}" placeholder="e.g. employees.update_details.update" />
                </div>

                <div class="field">
                    <label>User email</label>
                    <input name="user_email" type="text" value="{{ $filters['user_email'] ?? '' }}" placeholder="e.g. admin@company.com" />
                </div>

                <div class="field">
                    <label>Method</label>
                    <select name="method">
                        <option value="">Any</option>
                        @php $methodVal = $filters['method'] ?? '' @endphp
                        <option value="GET" @selected($methodVal === 'GET')>GET</option>
                        <option value="POST" @selected($methodVal === 'POST')>POST</option>
                        <option value="PUT" @selected($methodVal === 'PUT')>PUT</option>
                        <option value="PATCH" @selected($methodVal === 'PATCH')>PATCH</option>
                        <option value="DELETE" @selected($methodVal === 'DELETE')>DELETE</option>
                    </select>
                </div>

                <div class="field">
                    <label>Date from</label>
                    <input name="from_date" type="date" value="{{ $filters['from_date'] ?? '' }}" />
                </div>

                <div class="field">
                    <label>Date to</label>
                    <input name="to_date" type="date" value="{{ $filters['to_date'] ?? '' }}" />
                </div>

                <div class="field" style="display:flex;align-items:flex-end;gap:10px;">
                    <button class="btn" type="submit" style="white-space:nowrap;">Search</button>
                    <a class="pill" href="{{ route('admin.tools.logs.index') }}" style="white-space:nowrap;">Reset</a>
                </div>
            </div>
        </form>
    </div>

    <div class="card" style="margin-top:14px;">
        <div class="row" style="justify-content:space-between;">
            <h2 style="margin-bottom:0;">Log Entries</h2>
            <div class="muted" style="font-size:13px;">
                Showing {{ $logs->firstItem() }} - {{ $logs->lastItem() }} of {{ $logs->total() }}
            </div>
        </div>

        @if($logs->isEmpty())
            <p class="muted" style="margin-top:12px;">No activity logs found for the selected filters.</p>
        @else
            <div class="table-wrap" style="margin-top:12px;">
                <table>
                    <thead>
                    <tr>
                        <th style="min-width:170px;">Timestamp</th>
                        <th style="min-width:170px;">User</th>
                        <th style="min-width:240px;">Action</th>
                        <th>Method</th>
                        <th style="min-width:170px;">Route</th>
                        <th style="min-width:140px;">IP</th>
                        <th>Status</th>
                    </tr>
                    </thead>
                    <tbody>
                    @foreach($logs as $log)
                        <tr>
                            <td>{{ $log->created_at?->format('Y-m-d H:i:s') }}</td>
                            <td>
                                <div><strong>{{ $log->user_email ?: 'Unknown user' }}</strong></div>
                                <div class="muted" style="font-size:12px;">User ID: {{ $log->user_id ?? '—' }}</div>
                            </td>
                            <td>
                                <div><strong>{{ $log->action_key }}</strong></div>
                                @if(!empty($log->url))
                                    <div class="muted" style="font-size:12px;word-break:break-word;">{{ \Illuminate\Support\Str::limit($log->url, 60) }}</div>
                                @endif
                            </td>
                            <td>{{ $log->method }}</td>
                            <td>{{ $log->route_name ?: '—' }}</td>
                            <td>{{ $log->ip_address ?: '—' }}</td>
                            <td>{{ $log->status_code }}</td>
                        </tr>
                    @endforeach
                    </tbody>
                </table>
            </div>

            <div class="tools-logs-pagination" style="margin-top:14px;">
                {{ $logs->links() }}
            </div>
        @endif
    </div>
@endsection

