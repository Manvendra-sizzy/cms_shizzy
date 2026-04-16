<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $title ?? 'CMS' }}</title>
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Lexend:wght@500;600;700;800&family=Poppins:wght@400;500;600;700&display=swap');
        *, *::before, *::after { box-sizing: border-box; }
        :root {
            --bg: #f6f8fc;
            --bg-accent: #eef3ff;
            --surface: #ffffff;
            --surface-soft: #f8faff;
            --text: #0f172a;
            --muted: #64748b;
            --accent: #2663ff;
            --accent-strong: #1d4ed8;
            --danger: #dc2626;
            --ok: #16a34a;
            --border: #e2e8f0;
            --ring: rgba(38,99,255,.16);
            --shadow-sm: 0 2px 10px rgba(15, 23, 42, 0.05);
            --shadow-md: 0 14px 38px rgba(15, 23, 42, 0.08);
            --font-body: 'Poppins', ui-sans-serif, system-ui, -apple-system, Segoe UI, Arial, sans-serif;
            --font-accent: 'Lexend', 'Poppins', ui-sans-serif, system-ui, -apple-system, Segoe UI, Arial, sans-serif;
        }
        html, body { height: 100%; }
        body {
            margin: 0;
            font-family: var(--font-body);
            background:
                radial-gradient(circle at 10% -25%, var(--bg-accent), transparent 48%),
                radial-gradient(circle at 100% 0%, #eaf2ff, transparent 42%),
                var(--bg);
            color: var(--text);
            line-height: 1.56;
        }
        a { color: var(--accent); text-decoration: none; }
        a:hover { color: var(--accent-strong); text-decoration: none; }
        .wrap {
            max-width: 1320px;
            margin: 0 auto;
            padding: 24px 18px 28px;
            min-width: 0;
            min-height: 100vh;
            display: flex;
            flex-direction: column;
        }
        .top {
            position: sticky;
            top: 10px;
            z-index: 80;
            display: flex;
            gap: 12px;
            align-items: center;
            justify-content: space-between;
            margin-bottom: 18px;
            flex-wrap: wrap;
            padding: 14px 16px;
            border-radius: 16px;
            border: 1px solid rgba(226, 232, 240, 0.9);
            background: rgba(255,255,255,.92);
            backdrop-filter: blur(8px);
            box-shadow: var(--shadow-sm);
        }
        .brand {
            font-family: var(--font-accent);
            font-weight: 700;
            letter-spacing: .2px;
            color: var(--text);
            font-size: 20px;
            line-height: 1.1;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        .brand .mark {
            color: #1e293b;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            font-weight: 800;
        }
        .cms-logo {
            width: 40px;
            height: 40px;
            border-radius: 999px;
            border: 1px solid rgba(226, 232, 240, 0.85);
            box-shadow: 0 8px 18px rgba(2, 6, 23, 0.1);
            object-fit: cover;
            background: #fff;
        }
        .nav {
            display: flex;
            gap: 8px;
            flex-wrap: wrap;
            align-items: center;
            justify-content: flex-end;
        }
        .nav-toggle {
            display: none;
            min-height: 38px;
            height: 38px;
            min-width: 44px;
            padding: 0 12px;
            border-radius: 12px;
            border: 1px solid #dbe5f2;
            background: linear-gradient(180deg, #ffffff, #f8fbff);
            color: #1e293b;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            box-shadow: 0 2px 0 rgba(255,255,255,.95), 0 8px 20px rgba(15, 23, 42, 0.06);
        }
        .nav-toggle svg {
            width: 18px;
            height: 18px;
            stroke: currentColor;
            fill: none;
            stroke-width: 2;
            stroke-linecap: round;
            stroke-linejoin: round;
        }
        .nav-toggle:hover { border-color: var(--accent); color: var(--accent); }
        .nav form { margin: 0; }
        .top .pill {
            min-height: 38px;
            height: 38px;
            padding-top: 0;
            padding-bottom: 0;
        }
        .pill {
            padding: 9px 14px;
            border-radius: 12px;
            background: linear-gradient(180deg, #ffffff, #f8fbff);
            border: 1px solid #dbe5f2;
            color: #1e293b;
            box-shadow: 0 2px 0 rgba(255,255,255,.95), 0 8px 20px rgba(15, 23, 42, 0.06);
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            line-height: 1;
            font-size: 13px;
            font-weight: 600;
            min-height: 40px;
            transition: transform .12s ease, box-shadow .16s ease, border-color .16s ease, background-color .16s ease, color .16s ease, filter .16s ease;
            gap: 8px;
        }
        .btn-icon {
            width: 15px;
            height: 15px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            flex: 0 0 15px;
        }
        .btn-icon svg {
            width: 15px;
            height: 15px;
            stroke: currentColor;
            fill: none;
            stroke-width: 2;
            stroke-linecap: round;
            stroke-linejoin: round;
        }
        .pill:hover {
            background: var(--accent);
            border-color: var(--accent);
            color: #fff;
            text-decoration: none;
            box-shadow: var(--shadow-md);
            transform: translateY(-1px);
            filter: saturate(1.1);
        }
        button.pill:hover { text-decoration: none; }
        button.pill { font: inherit; }
        .dropdown { position: relative; display: inline-flex; }
        .dropdown-menu {
            display: none;
            position: absolute;
            top: calc(100% + 10px);
            left: 0;
            background: #fff;
            border: 1px solid var(--border);
            border-radius: 14px;
            padding: 8px;
            min-width: 190px;
            box-shadow: 0 20px 34px rgba(2, 6, 23, .14);
            z-index: 100;
        }
        .dropdown:hover .dropdown-menu,
        .dropdown:focus-within .dropdown-menu {
            display: block;
        }
        .dropdown-menu a {
            display: block;
            padding: 9px 12px;
            border-radius: 9px;
            border: 1px solid transparent;
            color: #1e293b;
            text-decoration: none;
            margin-bottom: 5px;
            font-weight: 500;
            font-size: 13px;
        }
        .dropdown-menu a:last-child { margin-bottom: 0; }
        .dropdown-menu a:hover {
            background: #edf3ff;
            border-color: #d4e3ff;
            color: #1743b7;
        }
        .card {
            background: linear-gradient(180deg, #fff, #fbfdff);
            border: 1px solid var(--border);
            border-radius: 16px;
            padding: 18px;
            box-shadow: var(--shadow-sm);
            min-width: 0;
            overflow-wrap: break-word;
        }
        .grid { display: grid; gap: 14px; min-width: 0; }
        .grid.cols-3 { grid-template-columns: repeat(3, minmax(0, 1fr)); }
        @media (max-width: 960px) { .grid.cols-3 { grid-template-columns: 1fr; } }
        h1 {
            margin: 0 0 10px;
            font-size: 25px;
            color: var(--text);
            font-family: var(--font-accent);
            letter-spacing: .1px;
            line-height: 1.2;
        }
        h2 {
            margin: 0 0 10px;
            font-size: 16px;
            color: #334155;
            font-weight: 700;
            padding-bottom: 8px;
            border-bottom: 1px solid #ecf1f8;
            display: inline-block;
            font-family: var(--font-accent);
            letter-spacing: .1px;
        }
        .card h2 {
            display: block;
            text-align: left;
            margin-bottom: 12px;
        }
        table {
            width: 100%;
            border-collapse: separate;
            border-spacing: 0;
            min-width: 0;
            border: 1px solid #e7edf5;
            border-radius: 12px;
            overflow: hidden;
            background: #fff;
        }
        th, td {
            text-align: left;
            padding: 12px 12px;
            border-bottom: 1px solid #edf2f8;
            vertical-align: top;
        }
        tr:last-child td { border-bottom: none; }
        th {
            font-size: 11px;
            color: #64748b;
            font-weight: 700;
            letter-spacing: .4px;
            text-transform: uppercase;
            background: var(--surface-soft);
            position: sticky;
            top: 0;
            z-index: 1;
        }
        .table-wrap {
            width: 100%;
            max-width: 100%;
            overflow-x: auto;
            overflow-y: hidden;
            -webkit-overflow-scrolling: touch;
            margin-top: 12px;
        }
        .card > .table-wrap:first-child,
        .form-wrap > .table-wrap:first-child,
        .grid > .table-wrap:first-child { margin-top: 0; }
        .table-wrap table {
            width: 100%;
            min-width: 0;
            max-width: 100%;
        }
        .muted { color: var(--muted); }
        .row { display: flex; gap: 12px; flex-wrap: wrap; align-items: center; }
        .field { margin-bottom: 12px; min-width: 0; }
        .field input,
        .field select,
        option,
        optgroup,
        .field textarea {
            width: 100%;
            max-width: 100%;
            padding: 11px 12px;
            border-radius: 10px;
            border: 1px solid #d4dcea;
            background: #fff;
            color: var(--text);
            transition: border-color .15s ease, box-shadow .15s ease, background-color .15s ease;
            font-family: var(--font-body);
            font-size: 14px;
        }
        select, option, optgroup, input, textarea, button { font-family: var(--font-body); }
        .field textarea { border-radius: 12px; min-height: 130px; resize: vertical; }
        .field select {
            appearance: none;
            -webkit-appearance: none;
            -moz-appearance: none;
            padding-right: 40px;
            background-image:
                linear-gradient(45deg, transparent 50%, rgba(51,65,85,.6) 50%),
                linear-gradient(135deg, rgba(51,65,85,.6) 50%, transparent 50%);
            background-position: calc(100% - 18px) 50%, calc(100% - 12px) 50%;
            background-size: 6px 6px, 6px 6px;
            background-repeat: no-repeat;
        }
        .field input:focus,
        .field select:focus,
        .field textarea:focus {
            outline: none;
            border-color: #8baefb;
            box-shadow: 0 0 0 4px var(--ring);
            background: #fff;
        }
        label {
            display: block;
            font-size: 12px;
            color: #64748b;
            margin-bottom: 6px;
            font-weight: 600;
            letter-spacing: .15px;
        }
        .btn {
            display: inline-block;
            cursor: pointer;
            padding: 10px 17px;
            border-radius: 12px;
            border: 1px solid #1f4fcb;
            background: linear-gradient(180deg, #3f7cff, #245ad8);
            color: #fff;
            font-weight: 600;
            letter-spacing: .1px;
            box-shadow: 0 10px 20px rgba(37,99,235,.26), inset 0 1px 0 rgba(255,255,255,.28);
            transition: transform .12s ease, box-shadow .12s ease, filter .12s ease, border-color .12s ease;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 9px;
            min-height: 40px;
        }
        .btn:hover {
            color: #fff;
            text-decoration: none;
            filter: brightness(1.03) saturate(1.05);
            box-shadow: 0 14px 28px rgba(37,99,235,.34), inset 0 1px 0 rgba(255,255,255,.32);
            transform: translateY(-1px);
        }
        a.btn:focus-visible {
            color: #fff;
            outline: 2px solid rgba(37, 99, 235, 0.55);
            outline-offset: 2px;
        }
        .btn.danger {
            border-color: #b91c1c;
            background: linear-gradient(180deg, #ef4444, #dc2626);
            box-shadow: 0 10px 20px rgba(220,38,38,.24), inset 0 1px 0 rgba(255,255,255,.24);
        }
        .btn.danger:hover {
            color: #fff;
            text-decoration: none;
            box-shadow: 0 14px 26px rgba(220,38,38,.32), inset 0 1px 0 rgba(255,255,255,.26);
        }
        .flash {
            margin: 10px 0 0;
            padding: 11px 13px;
            border-radius: 11px;
            background: rgba(22,163,74,.1);
            border: 1px solid rgba(22,163,74,.24);
            color: #14532d;
        }
        .errors {
            margin: 10px 0 0;
            padding: 11px 13px;
            border-radius: 11px;
            background: rgba(220,38,38,.07);
            border: 1px solid rgba(220,38,38,.22);
            color: #991b1b;
        }
        .kpi {
            font-size: 22px;
            font-weight: 700;
            color: #0f172a;
            font-family: var(--font-accent);
            line-height: 1;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            min-width: 64px;
            min-height: 64px;
            padding: 8px 12px;
            border-radius: 999px;
            border: 2px solid #d6e3ff;
            background: radial-gradient(circle at 30% 25%, #ffffff, #f3f8ff);
            box-shadow: inset 0 1px 0 rgba(255,255,255,.9), 0 10px 22px rgba(37,99,235,.12);
            text-align: center;
            margin-left: 0;
        }
        .form-grid { display: grid; gap: 12px; min-width: 0; }
        .form-grid.cols-2 { grid-template-columns: repeat(2, minmax(0, 1fr)); }
        @media (max-width: 700px) { .form-grid.cols-2 { grid-template-columns: 1fr; } }
        .form-card { max-width: 920px; width: 100%; }
        .auth-card { max-width: 520px; width: 100%; margin: 0 auto; }
        .form-wrap { min-width: 0; max-width: 100%; }
        .action-row { display: flex; gap: 10px; align-items: center; flex-wrap: wrap; margin-top: 8px; }
        nav[role="navigation"] svg {
            width: 15px;
            height: 15px;
            max-width: 15px;
            max-height: 15px;
            display: inline-block;
            vertical-align: middle;
        }
        nav[role="navigation"] a,
        nav[role="navigation"] span {
            font-size: 13px;
            line-height: 1.2;
        }
        .footer {
            margin-top: auto;
            padding: 18px 0 8px;
            color: #64748b;
            font-size: 12px;
            text-align: center;
            letter-spacing: .2px;
        }
        @media (max-width: 768px) {
            .wrap { padding: 14px 10px 20px; }
            .top { position: static; top: auto; padding: 12px; gap: 10px; }
            .brand { width: calc(100% - 56px); justify-content: flex-start; }
            .nav-toggle { display: inline-flex; }
            .nav {
                display: none;
                width: 100%;
                justify-content: flex-start;
                gap: 8px;
                margin-top: 4px;
                padding-top: 8px;
                border-top: 1px solid #e6edf8;
            }
            .top.nav-open .nav { display: flex; }
            .top .pill { width: 100%; justify-content: flex-start; }
            .dropdown { width: 100%; display: block; }
            .dropdown > .pill { width: 100%; justify-content: flex-start; }
            .dropdown:hover .dropdown-menu,
            .dropdown:focus-within .dropdown-menu {
                display: none;
            }
            .dropdown.dropdown-open .dropdown-menu {
                display: block;
            }
            .dropdown-menu { left: 0; right: 0; min-width: 0; width: 100%; }
            .dropdown-menu a { font-size: 14px; padding: 10px 12px; }
            .card { padding: 14px; }
            th, td { padding: 10px 8px; }
            .action-row .btn, .action-row .pill { width: 100%; }
            .table-wrap table {
                min-width: 620px;
                width: max-content;
                max-width: none;
            }
            .kpi {
                min-width: 56px;
                min-height: 56px;
                font-size: 18px;
                padding: 7px 10px;
            }
        }
    </style>
</head>
<body>
    <div class="wrap">
        <div class="top">
            <a
                class="brand"
                href="@auth{{ auth()->user()?->isAdmin() ? route('admin.dashboard') : route('employee.dashboard') }}@else{{ route('login') }}@endauth"
                aria-label="Go to dashboard"
            >
                <img
                    src="https://shizzy.in/images/shizzy-logo-icon.png"
                    alt="Shizzy logo"
                    class="cms-logo"
                />
                <span class="mark">CMS</span>
            </a>
            <button class="nav-toggle" type="button" aria-label="Toggle navigation" aria-expanded="false">
                <svg viewBox="0 0 24 24" aria-hidden="true">
                    <path d="M3 6h18M3 12h18M3 18h18"></path>
                </svg>
            </button>
            <div class="nav">
                @auth
                    <a class="pill" href="{{ route('dashboard') }}">Dashboard</a>
                    @if(!auth()->user()?->isAdmin() && \Illuminate\Support\Facades\Route::has('security.twofactor.show'))
                        <a class="pill" href="{{ route('security.twofactor.show') }}">Two-Factor Security</a>
                    @endif
                    @if(auth()->user()?->isAdmin())
                        @if(\Illuminate\Support\Facades\Route::has('admin.users.index'))
                            <a class="pill" href="{{ route('admin.users.index') }}">User management</a>
                        @endif
                        @if(\Illuminate\Support\Facades\Route::has('admin.organization.index'))
                            <a class="pill" href="{{ route('admin.organization.index') }}">Organization Structure</a>
                        @endif

                        <div class="dropdown">
                            <button class="pill" type="button" aria-label="Tools">
                                Tools
                            </button>
                            <div class="dropdown-menu" role="menu" aria-label="Tools menu">
                                @if(\Illuminate\Support\Facades\Route::has('admin.tools.logs.index'))
                                    <a href="{{ route('admin.tools.logs.index') }}">Logs</a>
                                @endif
                                @if(\Illuminate\Support\Facades\Route::has('admin.tools.zoho.index'))
                                    <a href="{{ route('admin.tools.zoho.index') }}">Zoho OAuth Setup</a>
                                @endif
                                @if(\Illuminate\Support\Facades\Route::has('admin.zoho_clients.index'))
                                    <a href="{{ route('admin.zoho_clients.index') }}">Zoho Clients</a>
                                @endif
                                @if(\Illuminate\Support\Facades\Route::has('admin.zoho_invoices.index'))
                                    <a href="{{ route('admin.zoho_invoices.index') }}">Zoho Invoices</a>
                                @endif
                                @if(\Illuminate\Support\Facades\Route::has('security.twofactor.show'))
                                    <a href="{{ route('security.twofactor.show') }}">Two-Factor Security</a>
                                @endif
                            </div>
                        </div>
                    @endif
                    <form method="post" action="{{ route('logout') }}" style="display:inline;">
                        @csrf
                        <button class="pill" type="submit" aria-label="Lockout" title="Lockout" style="display:inline-flex;align-items:center;gap:8px;">
                            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" viewBox="0 0 16 16" aria-hidden="true">
                                <path d="M6 2a2 2 0 0 1 2-2h5a2 2 0 0 1 2 2v12a2 2 0 0 1-2 2H8a2 2 0 0 1-2-2v-2h1v2a1 1 0 0 0 1 1h5a1 1 0 0 0 1-1V2a1 1 0 0 0-1-1H8a1 1 0 0 0-1 1v2H6z"/>
                                <path d="M.146 8.354a.5.5 0 0 1 0-.708l3-3a.5.5 0 1 1 .708.708L1.707 7.5H10.5a.5.5 0 0 1 0 1H1.707l2.147 2.146a.5.5 0 0 1-.708.708z"/>
                            </svg>
                        </button>
                    </form>
                @else
                    <a class="pill" href="{{ route('login') }}">Login</a>
                @endauth
            </div>
        </div>

        @if (session('status'))
            <div class="flash">{{ session('status') }}</div>
        @endif

        @if (session('warning'))
            <div class="flash" style="background:#fff7ed;border-color:#fdba74;color:#9a3412;">{{ session('warning') }}</div>
        @endif

        @if (isset($errors) && $errors->any())
            <div class="errors">
                <div><strong>Fix the following:</strong></div>
                <ul>
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <div style="margin-top: 14px; min-width: 0;">
            @yield('content')
        </div>

        <div class="footer">EXIN INTERNET SERVICES PRIVATE LIMITED</div>
    </div>
    @stack('scripts')
    <script>
        (function () {
            const ICONS = {
                dashboard: '<svg viewBox="0 0 24 24"><path d="M3 13h8V3H3v10Zm10 8h8V11h-8v10ZM13 3v6h8V3h-8ZM3 21h8v-6H3v6Z"/></svg>',
                systems: '<svg viewBox="0 0 24 24"><path d="M12 3v3m0 12v3M4.9 4.9l2.1 2.1m10 10 2.1 2.1M3 12h3m12 0h3M4.9 19.1 7 17m10-10 2.1-2.1"/><circle cx="12" cy="12" r="4"/></svg>',
                users: '<svg viewBox="0 0 24 24"><path d="M16 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="8.5" cy="7" r="4"/><path d="M20 8v6M23 11h-6"/></svg>',
                organization: '<svg viewBox="0 0 24 24"><rect x="3" y="3" width="7" height="5" rx="1"/><rect x="14" y="3" width="7" height="5" rx="1"/><rect x="8.5" y="16" width="7" height="5" rx="1"/><path d="M6.5 8v4m11-4v4M6.5 12h11m-5.5 0v4"/></svg>',
                security: '<svg viewBox="0 0 24 24"><path d="M12 2 4 5v6c0 5.2 3.4 9.9 8 11 4.6-1.1 8-5.8 8-11V5l-8-3Z"/><path d="M9.5 12.5 11 14l3.5-3.5"/></svg>',
                tools: '<svg viewBox="0 0 24 24"><path d="m14.7 6.3 3 3-8.4 8.4H6.3v-3l8.4-8.4ZM15.4 5.6l1.9-1.9a2.1 2.1 0 0 1 3 3l-1.9 1.9"/></svg>',
                logs: '<svg viewBox="0 0 24 24"><path d="M4 4h16v16H4z"/><path d="M8 8h8M8 12h8M8 16h5"/></svg>',
                zoho: '<svg viewBox="0 0 24 24"><path d="M3 7h18v10H3z"/><path d="m3 9 9 6 9-6"/></svg>',
                login: '<svg viewBox="0 0 24 24"><path d="M15 3h4a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2h-4"/><path d="M10 17l5-5-5-5"/><path d="M15 12H3"/></svg>',
                logout: '<svg viewBox="0 0 24 24"><path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"/><path d="M16 17l5-5-5-5"/><path d="M21 12H9"/></svg>',
                create: '<svg viewBox="0 0 24 24"><path d="M12 5v14M5 12h14"/></svg>',
                edit: '<svg viewBox="0 0 24 24"><path d="m4 20 4-.8 9.8-9.8a2 2 0 1 0-2.8-2.8L5.2 16.4 4 20z"/><path d="m13.5 7.5 3 3"/></svg>',
                save: '<svg viewBox="0 0 24 24"><path d="M5 3h12l2 2v16H5z"/><path d="M8 3v6h8V3M8 21v-6h8v6"/></svg>',
                update: '<svg viewBox="0 0 24 24"><path d="M3 12a9 9 0 1 0 3-6.7"/><path d="M3 3v6h6"/></svg>',
                delete: '<svg viewBox="0 0 24 24"><path d="M3 6h18M9 6V4h6v2m-7 0 1 13h6l1-13"/></svg>',
                view: '<svg viewBox="0 0 24 24"><path d="M2 12s4-7 10-7 10 7 10 7-4 7-10 7S2 12 2 12z"/><circle cx="12" cy="12" r="3"/></svg>',
                default: '<svg viewBox="0 0 24 24"><path d="M12 2 3 7l9 5 9-5-9-5Zm-9 9 9 5 9-5M3 15l9 5 9-5"/></svg>'
            };

            function pickIconKey(label, href, cls) {
                const t = (label || '').toLowerCase();
                const h = (href || '').toLowerCase();
                const c = (cls || '').toLowerCase();
                if (c.includes('danger') || t.includes('delete') || t.includes('remove')) return 'delete';
                if (t.includes('dashboard')) return 'dashboard';
                if (t.includes('system')) return 'systems';
                if (t.includes('user')) return 'users';
                if (t.includes('organization')) return 'organization';
                if (t.includes('2fa') || t.includes('security') || t.includes('authenticator')) return 'security';
                if (t.includes('tool')) return 'tools';
                if (t.includes('log')) return 'logs';
                if (t.includes('zoho')) return 'zoho';
                if (t.includes('login') || t.includes('sign in')) return 'login';
                if (t.includes('logout') || t.includes('lockout')) return 'logout';
                if (t.includes('create') || t.includes('add ')) return 'create';
                if (t.includes('edit')) return 'edit';
                if (t.includes('save')) return 'save';
                if (t.includes('update') || t.includes('apply')) return 'update';
                if (t.includes('open') || t.includes('view') || t.includes('show')) return 'view';
                if (h.includes('/dashboard')) return 'dashboard';
                if (h.includes('/systems')) return 'systems';
                if (h.includes('/users')) return 'users';
                return 'default';
            }

            function decorateButton(button) {
                if (!button || button.querySelector('svg')) return;
                const label = (button.textContent || '').replace(/\s+/g, ' ').trim();
                if (!label) return;
                const key = pickIconKey(label, button.getAttribute('href') || '', button.className || '');
                const iconWrap = document.createElement('span');
                iconWrap.className = 'btn-icon';
                iconWrap.setAttribute('aria-hidden', 'true');
                iconWrap.innerHTML = ICONS[key] || ICONS.default;
                button.insertBefore(iconWrap, button.firstChild);
            }

            document.querySelectorAll('.pill, .btn').forEach(decorateButton);

            // Ensure every data table is horizontally scrollable on small screens.
            document.querySelectorAll('table').forEach(function (table) {
                if (table.closest('.table-wrap')) return;
                const wrapper = document.createElement('div');
                wrapper.className = 'table-wrap';
                table.parentNode.insertBefore(wrapper, table);
                wrapper.appendChild(table);
            });

            const top = document.querySelector('.top');
            const navToggle = document.querySelector('.nav-toggle');
            if (top && navToggle) {
                navToggle.addEventListener('click', function () {
                    const isOpen = top.classList.toggle('nav-open');
                    navToggle.setAttribute('aria-expanded', isOpen ? 'true' : 'false');
                });
            }

            document.querySelectorAll('.dropdown > .pill').forEach(function (btn) {
                btn.addEventListener('click', function () {
                    if (window.matchMedia('(max-width: 768px)').matches) {
                        const dropdown = btn.closest('.dropdown');
                        if (dropdown) {
                            dropdown.classList.toggle('dropdown-open');
                        }
                    }
                });
            });
        })();
    </script>
</body>
</html>
