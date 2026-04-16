@extends('hrms.layout')

@section('content')
    <div class="card form-card">
        <div class="row" style="justify-content:space-between;align-items:flex-start;">
            <h1>My profile</h1>
            <div class="row" style="gap:8px;">
                <a class="pill" href="{{ route('employee.reimbursements.index') }}">Reimbursements</a>
                <a class="pill" href="{{ route('employee.dashboard') }}">Back</a>
            </div>
        </div>

        @if(!$profile)
            <p class="muted">Your employee profile hasn’t been created yet. Please contact HR.</p>
        @else
            <div class="row" style="gap:16px;align-items:flex-start;margin-top:10px;">
                <div style="width:90px;height:90px;flex-shrink:0;border-radius:999px;overflow:hidden;border:1px solid rgba(0,0,0,.08);background:#fff;display:flex;align-items:center;justify-content:center;">
                    @php($photoSrc = !empty($profile->profile_image_path) ? route('files.public', ['path' => ltrim($profile->profile_image_path, '/')]) . '?v=' . (optional($profile->updated_at)->timestamp ?? time()) : 'https://shizzy.in/images/shizzy-logo-icon.png')
                    <img src="{{ $photoSrc }}" alt="Profile" style="width:90px;height:90px;object-fit:cover;">
                </div>

                <div style="flex:1;min-width:0;">
                    <div style="font-size:18px;font-weight:600;">{{ $profile->user?->name ?? '—' }}</div>
                    <div class="muted" style="margin-top:4px;">{{ $profile->employee_id }}</div>
                    <div style="margin-top:8px;">
                        <span style="display:inline-flex;align-items:center;padding:4px 12px;border-radius:999px;font-size:12px;font-weight:700;background:#ecfeff;color:#155e75;border:1px solid #a5f3fc;">
                            {{ $profile->badgeLabel() }}
                        </span>
                    </div>

                    <div class="row" style="margin-top:10px;gap:16px;">
                        <div><span class="muted">Department</span><div><strong>{{ $profile->orgDepartment?->name ?? '—' }}</strong></div></div>
                        <div><span class="muted">Designation</span><div><strong>{{ $profile->orgDesignation?->name ?? '—' }}</strong></div></div>
                        <div><span class="muted">Reporting manager</span><div><strong>{{ $profile->reportingManager?->user?->name ?? '—' }}</strong></div></div>
                        <div><span class="muted">Employee type</span><div><strong>{{ \App\Services\HRMS\EmployeeLifecycleService::employeeTypeLabels()[$profile->employee_type ?? ''] ?? '—' }}</strong></div></div>
                    </div>
                </div>
            </div>

            <div class="card" style="margin-top:14px;padding:12px 16px;">
                <h2 style="margin:0 0 10px;">Change profile image</h2>
                <form method="post" action="{{ route('employee.profile.photo.update') }}" enctype="multipart/form-data" class="form-wrap">
                    @csrf
                    <div class="field" style="margin:0;">
                        <label>Choose image (JPG, PNG or WebP, max 5 MB)</label>
                        <input type="file" name="profile_image" accept="image/jpeg,image/png,image/webp" required>
                    </div>
                    <div class="row" style="margin-top:10px;">
                        <button class="btn" type="submit">Update photo</button>
                    </div>
                </form>
            </div>
        @endif
    </div>
@endsection

