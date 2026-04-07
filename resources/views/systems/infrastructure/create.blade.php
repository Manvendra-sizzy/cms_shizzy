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
    <div class="card form-card">
        <h1>Add Infrastructure Resource</h1>
        <form method="post" action="{{ route('systems.infrastructure.store') }}">
            @csrf
            <div class="field">
                <label>Resource Type *</label>
                <select name="resource_type" required>
                    @foreach($resourceTypes as $type)
                        <option value="{{ $type }}">{{ $resourceTypeLabels[$type] ?? ucfirst(str_replace('_', ' ', $type)) }}</option>
                    @endforeach
                </select>
            </div>
            <div class="field"><label>Name *</label><input type="text" name="name" required></div>
            <div class="field"><label>Vendor</label><input type="text" name="vendor"></div>
            <div class="field"><label>Access URL</label><input type="url" name="access_url"></div>
            <div class="field">
                <label>Status *</label>
                <select name="status" required>
                    @foreach($statuses as $status)
                        <option value="{{ $status }}">{{ ucfirst($status) }}</option>
                    @endforeach
                </select>
            </div>
            <div class="field"><label>Description</label><textarea name="description"></textarea></div>
            <button class="btn" type="submit">Create Resource</button>
        </form>
    </div>
@endsection
