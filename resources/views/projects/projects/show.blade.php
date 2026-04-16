@extends('hrms.layout')

@section('content')
    <style>
        .project-shell { display:grid; gap:14px; }
        .project-hero {
            background: linear-gradient(135deg, #0f172a, #1e3a8a 55%, #2563eb);
            border-radius: 16px;
            color: #fff;
            padding: 18px;
            border: 1px solid rgba(255,255,255,.18);
            box-shadow: 0 12px 28px rgba(15,23,42,.22);
        }
        .project-hero h1 { color:#fff; margin-bottom:6px; }
        .project-hero .muted { color: rgba(255,255,255,.86); }
        .project-meta-chips { display:flex; flex-wrap:wrap; gap:8px; margin-top:10px; }
        .project-meta-chip {
            border:1px solid rgba(255,255,255,.28);
            border-radius:999px;
            padding:6px 10px;
            background: rgba(255,255,255,.12);
            font-size:12px;
        }
        .project-meta-chip.premium-cta {
            border-color: rgba(255,255,255,.46);
            background: linear-gradient(135deg, rgba(255,255,255,.32), rgba(255,255,255,.14));
            color: #fff;
            font-weight: 700;
            box-shadow: 0 10px 22px rgba(2,6,23,.30), inset 0 1px 0 rgba(255,255,255,.38);
            padding: 8px 14px;
            transition: transform .14s ease, box-shadow .14s ease, background .14s ease;
        }
        .project-meta-chip.premium-cta:hover {
            transform: translateY(-1px) scale(1.01);
            box-shadow: 0 14px 28px rgba(2,6,23,.36), inset 0 1px 0 rgba(255,255,255,.5);
            background: linear-gradient(135deg, rgba(255,255,255,.42), rgba(255,255,255,.2));
        }
        .project-kpi-grid { display:grid; grid-template-columns:repeat(3,minmax(0,1fr)); gap:14px; }
        .project-kpi-card { border:1px solid var(--border); border-radius:14px; background:#fff; padding:14px; }
        .project-kpi-label { color:var(--muted); font-size:12px; }
        .project-kpi-value { font-size:24px; font-weight:700; margin-top:6px; color:#0f172a; }
        .project-surface { border:1px solid var(--border); border-radius:16px; background:#fff; padding:14px; }
        .project-section-title { margin:0 0 10px; font-size:15px; font-weight:700; color:#334155; }
        .project-status-inline {
            margin-top: 12px;
            border: 1px solid rgba(255,255,255,.28);
            border-radius: 12px;
            padding: 10px;
            background: rgba(255,255,255,.10);
        }
        .project-status-inline .field label { color: rgba(255,255,255,.9); }
        .project-status-inline .field input,
        .project-status-inline .field select,
        .project-status-inline .field textarea {
            background: rgba(255,255,255,.96);
        }
        .status-modal-overlay {
            position: fixed;
            inset: 0;
            background: rgba(15,23,42,.45);
            display: none;
            align-items: center;
            justify-content: center;
            z-index: 1000;
            padding: 16px;
        }
        .status-modal {
            width: 100%;
            max-width: 620px;
            background: #fff;
            border: 1px solid var(--border);
            border-radius: 16px;
            box-shadow: 0 22px 48px rgba(2,6,23,.28);
            padding: 16px;
        }
        .status-modal-title {
            margin: 0;
            font-size: 20px;
            font-family: var(--font-accent);
            color: #0f172a;
        }
        @media (max-width: 980px) { .project-kpi-grid { grid-template-columns: 1fr; } }
    </style>

    <div class="project-shell">
    <div class="project-hero">
        <div class="row" style="justify-content:space-between;align-items:flex-start;">
            <div>
                <h1>{{ $project->name }} <span class="muted">({{ $project->project_code }})</span></h1>
                <p class="muted" style="margin:0;">Client: <strong>{{ $project->is_internal ? 'Internal Project' : ($project->zohoClient?->contact_name ?: ($project->zohoClient?->company_name ?: $project->client?->name ?? '—')) }}</strong></p>
                <div class="project-meta-chips">
                    <span class="project-meta-chip">Category: <strong>{{ $project->category }}</strong></span>
                    <span class="project-meta-chip">Type: <strong>{{ $project->project_type }}</strong></span>
                    <span class="project-meta-chip">Billing: <strong>{{ $project->billing_type }}</strong></span>
                    <span class="project-meta-chip">Status: <strong>{{ ucfirst($project->status === 'delivered' ? 'completed' : $project->status) }}</strong></span>
                    <button class="project-meta-chip premium-cta" type="button" id="openStatusModalBtn" style="cursor:pointer;">Change status</button>
                </div>
                <p class="muted" style="margin-top:10px;">
                    PM: <strong>{{ $project->projectManager?->user?->name ?? '—' }}</strong>
                    · AM: <strong>{{ $project->accountManager?->user?->name ?? '—' }}</strong>
                </p>
                @if($project->project_folder)
                    <p class="muted" style="margin-top:8px;">Project folder: <strong>{{ $project->project_folder }}</strong></p>
                @endif
            </div>
            <div class="row" style="justify-content:flex-end;">
                <a class="pill" href="{{ route('projects.index') }}" style="background:#fff;border-color:#fff;color:#1e3a8a;">All projects</a>
                @if(!$project->is_internal)
                    <a class="pill" href="{{ route('projects.finances.show', $project) }}" style="background:#fff;border-color:#fff;color:#1e3a8a;">Project Finances</a>
                @endif
                <a class="pill" href="{{ route('projects.edit', $project) }}" style="background:#fff;border-color:#fff;color:#1e3a8a;">Edit</a>
            </div>
        </div>
    </div>

    @if($project->is_internal)
        <div class="project-surface">
            <div class="project-section-title">Internal Project</div>
            <p class="muted" style="margin:0;">Finances are disabled for internal projects.</p>
        </div>
    @endif

    <div class="project-kpi-grid">
        <div class="project-kpi-card">
            <div class="project-kpi-label">Team Members</div>
            <div class="project-kpi-value">{{ $project->teamMembers->count() }}</div>
        </div>
        <div class="project-kpi-card">
            <div class="project-kpi-label">Status Timeline Entries</div>
            <div class="project-kpi-value">{{ $logs->count() }}</div>
        </div>
        <div class="project-kpi-card">
            <div class="project-kpi-label">Project Category</div>
            <div class="project-kpi-value" style="font-size:20px;">{{ $project->category }}</div>
        </div>
    </div>

    <div class="grid" style="grid-template-columns: 1fr 1fr; gap: 14px;">
        <div class="project-surface">
            <div class="project-section-title">Project team</div>
            <form method="post" action="{{ route('projects.team.store', $project) }}" class="row" style="align-items:flex-end;">
                @csrf
                <div style="flex:1;min-width:220px;">
                    <label>Employee</label>
                    <select name="employee_profile_id" required>
                        <option value="">Select employee</option>
                        @foreach($employees as $e)
                            <option value="{{ $e->id }}">{{ $e->employee_id }} — {{ $e->user?->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div style="flex:1;min-width:220px;">
                    <label>Role</label>
                    <input name="role_title" value="{{ old('role_title') }}" placeholder="e.g. SEO Lead">
                </div>
                <button class="btn" type="submit">Add / Update</button>
            </form>

            <div style="margin-top:12px;">
                @if($project->teamMembers->isEmpty())
                    <p class="muted">No team members yet.</p>
                @else
                    <div class="table-wrap">
                    <table>
                        <thead>
                        <tr>
                            <th>Employee</th>
                            <th>Role</th>
                            <th></th>
                        </tr>
                        </thead>
                        <tbody>
                        @foreach($project->teamMembers as $m)
                            <tr>
                                <td><strong>{{ $m->employeeProfile?->employee_id }}</strong> — {{ $m->employeeProfile?->user?->name }}</td>
                                <td class="muted">{{ $m->role_title ?? '—' }}</td>
                                <td>
                                    <form method="post" action="{{ route('projects.team.destroy', [$project, $m]) }}" onsubmit="return confirm('Remove this team member?');">
                                        @csrf
                                        @method('DELETE')
                                        <button class="pill" type="submit">Remove</button>
                                    </form>
                                </td>
                            </tr>
                        @endforeach
                        </tbody>
                    </table>
                    </div>
                @endif
            </div>
        </div>
    </div>
    <div class="project-surface">
        <div class="project-section-title">Status timeline</div>
        @if($logs->isEmpty())
            <p class="muted">No status changes yet.</p>
        @else
            <div class="table-wrap">
            <table>
                <thead>
                <tr>
                    <th>Date</th>
                    <th>From</th>
                    <th>To</th>
                    <th>Remark</th>
                    <th>By</th>
                </tr>
                </thead>
                <tbody>
                @foreach($logs as $l)
                    <tr>
                        <td>{{ $l->effective_date?->format('Y-m-d') ?? '—' }}</td>
                        <td class="muted">{{ $l->from_status ?? '—' }}</td>
                        <td><strong>{{ $l->to_status }}</strong></td>
                        <td class="muted">{{ $l->remark }}</td>
                        <td class="muted">{{ $l->changedBy?->name ?? '—' }}</td>
                    </tr>
                @endforeach
                </tbody>
            </table>
            </div>
        @endif
    </div>

    <div class="project-surface">
        <div class="row" style="justify-content:space-between;">
            <div class="project-section-title" style="margin-bottom:0;">Zoho Invoices (Mapped by Project ID)</div>
            @if(\Illuminate\Support\Facades\Route::has('admin.zoho_invoices.index'))
                <a class="pill" href="{{ route('admin.zoho_invoices.index') }}">Open Zoho Invoices</a>
            @endif
        </div>
        <p class="muted" style="margin-top:8px;">Project mapping key: <strong>{{ $project->project_code }}</strong></p>

        @if(($zohoInvoices ?? collect())->isEmpty())
            <p class="muted">No synced Zoho invoices mapped to this project yet.</p>
        @else
            <div class="table-wrap">
                <table>
                    <thead>
                    <tr>
                        <th>Invoice Number</th>
                        <th>Zoho Invoice ID</th>
                        <th>Customer ID</th>
                        <th>Project ID</th>
                        <th>Status</th>
                        <th>Total</th>
                        <th>Balance</th>
                        <th>Invoice Date</th>
                        <th>Due Date</th>
                        <th></th>
                    </tr>
                    </thead>
                    <tbody>
                    @foreach($zohoInvoices as $invoice)
                        <tr>
                            <td>{{ $invoice->invoice_number ?: '—' }}</td>
                            <td>{{ $invoice->zoho_invoice_id }}</td>
                            <td>{{ $invoice->zoho_customer_id ?: '—' }}</td>
                            <td>{{ $invoice->project_id ?: '—' }}</td>
                            <td>{{ $invoice->status ?: '—' }}</td>
                            <td>{{ number_format((float) $invoice->total, 2) }}</td>
                            <td>{{ number_format((float) $invoice->balance, 2) }}</td>
                            <td>{{ $invoice->invoice_date?->format('Y-m-d') ?: '—' }}</td>
                            <td>{{ $invoice->due_date?->format('Y-m-d') ?: '—' }}</td>
                            <td>
                                <a class="pill" href="{{ route('admin.zoho_invoices.download', $invoice) }}">Download</a>
                            </td>
                        </tr>
                    @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </div>

    @if($project->description)
        <div class="project-surface">
            <div class="project-section-title">Description</div>
            <p class="muted" style="white-space:pre-wrap;">{{ $project->description }}</p>
        </div>
    @endif

    <div id="statusModalOverlay" class="status-modal-overlay">
        <div class="status-modal">
            <div class="row" style="justify-content:space-between;align-items:center;">
                <h3 class="status-modal-title">Change Project Status</h3>
                <button class="pill" type="button" id="closeStatusModalBtn">Close</button>
            </div>
            <form method="post" action="{{ route('projects.status.store', $project) }}" style="margin-top:10px;">
                @csrf
                <div class="grid" style="grid-template-columns: 1fr 180px; gap:10px;">
                    <div class="field" style="margin-bottom:0;">
                        <label>Status</label>
                        <select name="to_status" required>
                            <option value="active" @selected(old('to_status', $project->status === 'delivered' ? 'completed' : $project->status) === 'active')>Active</option>
                            <option value="hold" @selected(old('to_status') === 'hold')>Hold</option>
                            <option value="cancelled" @selected(old('to_status') === 'cancelled')>Cancelled</option>
                            <option value="completed" @selected(old('to_status', $project->status === 'delivered' ? 'completed' : '') === 'completed')>Completed</option>
                        </select>
                    </div>
                    <div class="field" style="margin-bottom:0;">
                        <label>Date</label>
                        <input type="date" name="effective_date" value="{{ old('effective_date', now()->toDateString()) }}" required>
                    </div>
                    <div class="field" style="grid-column:1 / -1; margin-bottom:0;">
                        <label>Remarks</label>
                        <textarea name="remark" required>{{ old('remark') }}</textarea>
                    </div>
                </div>
                <button class="btn" type="submit" style="margin-top:10px;">Update status</button>
            </form>
        </div>
    </div>
    </div>

    @push('scripts')
        <script>
            document.addEventListener('DOMContentLoaded', function () {
                const openBtn = document.getElementById('openStatusModalBtn');
                const closeBtn = document.getElementById('closeStatusModalBtn');
                const overlay = document.getElementById('statusModalOverlay');
                if (!openBtn || !closeBtn || !overlay) return;

                const openModal = function () { overlay.style.display = 'flex'; };
                const closeModal = function () { overlay.style.display = 'none'; };

                openBtn.addEventListener('click', openModal);
                closeBtn.addEventListener('click', closeModal);
                overlay.addEventListener('click', function (event) {
                    if (event.target === overlay) {
                        closeModal();
                    }
                });
            });
        </script>
    @endpush
@endsection

