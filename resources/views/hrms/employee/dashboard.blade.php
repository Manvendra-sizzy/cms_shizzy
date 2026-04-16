@extends('hrms.layout')

@section('content')
    @if(!empty($noticeBoard))
        @php
            $insufficient = $noticeBoard['prev_day_insufficient_hours'] ?? null;
            $todayWarning = $noticeBoard['today_warning'] ?? null;
        @endphp
        @if($insufficient || $todayWarning)
            <div class="card" style="margin-bottom:14px;">
                <h1 style="margin-bottom:8px;">Notice Board</h1>
                <div class="grid cols-3">
                    @if($insufficient)
                        <div class="card" style="padding:12px;border-color:rgba(255,77,61,.25);background:rgba(255,77,61,.06);">
                            <div style="font-weight:600;">Insufficient Working Hours</div>
                            <div class="muted" style="margin-top:4px;">
                                Previous day ({{ $insufficient['date'] }}): {{ floor($insufficient['worked_minutes'] / 60) }}h {{ $insufficient['worked_minutes'] % 60 }}m.
                            </div>
                        </div>
                    @endif

                    @if($todayWarning)
                        <div class="card" style="padding:12px;border-color:rgba(220,38,38,.30);background:rgba(220,38,38,.08);">
                            <div style="font-weight:600;">Today's Warning</div>
                            <div class="muted" style="margin-top:4px;">
                                {{ $todayWarning['message'] }}
                            </div>
                            <div class="muted" style="margin-top:4px;">
                                This month violation days: <strong>{{ $todayWarning['violation_days'] }}</strong>
                            </div>
                        </div>
                    @endif
                </div>
            </div>
        @endif
    @endif

    @if(!empty($latestInsufficientWarning))
        <div class="card" style="margin-bottom:14px;background:rgba(255,77,61,.08);border-color:rgba(255,77,61,.28);">
            <div style="font-weight:600;">Reminder: Insufficient Working Hours</div>
            <div class="muted" style="margin-top:4px;">
                Your recent attendance day {{ optional($latestInsufficientWarning->work_date)->format('Y-m-d') }} was below 9 hours.
                Repeated insufficient days can lead to ID lock.
            </div>
        </div>
    @endif

    @if(!empty($latestAbsentWarning))
        <div class="card" style="margin-bottom:14px;background:rgba(245,158,11,.10);border-color:rgba(245,158,11,.3);">
            <div style="font-weight:600;">Reminder: Absent without leave</div>
            <div class="muted" style="margin-top:4px;">
                You were absent on {{ optional($latestAbsentWarning->work_date)->format('Y-m-d') }} without approved leave.
                Consecutive absence can trigger ID lock.
            </div>
        </div>
    @endif

    <div class="card">
        <h1>Employee dashboard</h1>

        @if($profile)
            <div class="row" style="gap:16px;align-items:flex-start;">
                <div style="width:80px;height:80px;flex-shrink:0;border-radius:999px;overflow:hidden;border:1px solid rgba(0,0,0,.08);background:#fff;display:flex;align-items:center;justify-content:center;">
                    @php($photoSrc = !empty($profile->profile_image_path) ? route('files.public', ['path' => ltrim($profile->profile_image_path, '/')]) . '?v=' . (optional($profile->updated_at)->timestamp ?? time()) : 'https://shizzy.in/images/shizzy-logo-icon.png')
                    <img src="{{ $photoSrc }}" alt="Profile" style="width:80px;height:80px;object-fit:cover;">
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
                    </div>
                </div>
            </div>
        @else
            <p class="muted">Your employee profile hasn’t been created yet. Please contact HR.</p>
        @endif

        <div style="margin-top:14px;" class="row">
            <a class="pill" href="{{ route('employee.attendance.index') }}">Attendance</a>
            <a class="pill" href="{{ route('employee.leaves.index') }}">My leaves (pending: {{ $pendingLeavesCount }})</a>
            <a class="pill" href="{{ route('employee.reimbursements.index') }}">Reimbursements (pending: {{ $pendingReimbursementsCount }})</a>
            <a class="pill" href="{{ route('employee.downloads.index') }}">Downloads</a>
            <a class="pill" href="{{ route('employee.policies_guidelines.index') }}">Policies &amp; Guidelines</a>
            <a class="pill" href="{{ route('employee.password.edit') }}">Change password</a>
            <a class="pill" href="{{ route('employee.profile.show') }}">Change profile image</a>
            @if(auth()->user()?->hasModule('projects'))
                <a class="pill" href="{{ route('projects.index') }}">Projects</a>
            @endif
        </div>
    </div>
@endsection

