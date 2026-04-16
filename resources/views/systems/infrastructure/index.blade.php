@extends('hrms.layout')

@section('content')
    @php
        $resourceTypeLabels = [
            'server' => 'Server',
            'cdn' => 'CDN',
            'object_storage' => 'Object Storage',
            'database' => 'Database',
            'email_sms' => 'SMTP',
            'domain_dns' => 'Domain DNS',
        ];
    @endphp
    <div class="card">
        <div class="row" style="justify-content: space-between;">
            <h1>Infrastructure Resources</h1>
            <div class="row">
                <a class="pill" href="{{ route('systems.index') }}">Systems</a>
                <a class="pill" href="{{ route('systems.infrastructure.create') }}">Add Resource</a>
            </div>
        </div>
    </div>

    <div class="card" style="margin-top: 14px;">
        <div class="table-wrap">
            <table>
                <thead>
                <tr>
                    <th>Type</th>
                    <th>Name</th>
                    <th>Vendor</th>
                    <th>Status</th>
                    <th></th>
                </tr>
                </thead>
                <tbody>
                @forelse($resources as $resource)
                    <tr>
                        <td>{{ $resourceTypeLabels[$resource->resource_type] ?? ucfirst(str_replace('_', ' ', $resource->resource_type)) }}</td>
                        <td>{{ $resource->name }}</td>
                        <td>{{ $resource->vendor ?: '—' }}</td>
                        <td>{{ ucfirst($resource->status) }}</td>
                        <td><a class="pill" href="{{ route('systems.infrastructure.edit', $resource) }}">Edit</a></td>
                    </tr>
                @empty
                    <tr><td colspan="5" class="muted">No infrastructure resources yet.</td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
        <div style="margin-top: 10px;">{{ $resources->links() }}</div>
    </div>
@endsection
