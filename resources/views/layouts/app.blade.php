<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <title>@yield('title', 'Profiles') · FansAPI</title>
        <link rel="preconnect" href="https://fonts.googleapis.com">
        <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
        <link href="https://fonts.googleapis.com/css2?family=Fraunces:opsz,wght@9..144,520;9..144,640&family=Manrope:wght@400;500;600;700&display=swap" rel="stylesheet">
        <style>
            :root {
                --paper: #f3efe6;
                --ink: #1f1b16;
                --muted: #6f675e;
                --line: #ddd4c6;
                --surface: #fffaf1;
                --accent: #245c45;
                --accent-soft: #dce8e1;
                --danger: #8f2d22;
                --danger-soft: #f3ddd8;
                --pending: #8a5a12;
                --pending-soft: #f3e6cc;
                --shadow: 0 18px 40px -28px rgba(31, 27, 22, 0.45);
            }

            * { box-sizing: border-box; }

            html, body {
                margin: 0;
                min-height: 100%;
                background:
                    radial-gradient(1200px 500px at 10% -10%, #e7efe8 0%, transparent 55%),
                    var(--paper);
                color: var(--ink);
                font-family: Manrope, ui-sans-serif, system-ui, sans-serif;
            }

            a { color: inherit; text-decoration: none; }

            .shell {
                width: min(1040px, calc(100% - 32px));
                margin: 0 auto;
                padding: 36px 0 64px;
            }

            .topbar {
                display: flex;
                align-items: baseline;
                justify-content: space-between;
                gap: 16px;
                margin-bottom: 36px;
            }

            .brand {
                display: flex;
                flex-direction: column;
                gap: 2px;
            }

            .brand small {
                letter-spacing: 0.16em;
                text-transform: uppercase;
                font-size: 11px;
                color: var(--muted);
            }

            .brand strong {
                font-family: Fraunces, ui-serif, Georgia, serif;
                font-size: 28px;
                font-weight: 640;
            }

            .panel {
                background: var(--surface);
                border: 1px solid var(--line);
                border-radius: 20px;
                box-shadow: var(--shadow);
            }

            .page-head {
                display: flex;
                justify-content: space-between;
                align-items: flex-end;
                gap: 16px;
                padding: 28px 28px 0;
            }

            .page-head h1,
            .hero h1 {
                margin: 0;
                font-family: Fraunces, ui-serif, Georgia, serif;
                font-size: 34px;
                font-weight: 640;
                letter-spacing: -0.03em;
            }

            .page-head p,
            .lede {
                margin: 8px 0 0;
                color: var(--muted);
                line-height: 1.5;
            }

            .table-wrap { overflow-x: auto; }

            table {
                width: 100%;
                border-collapse: collapse;
                margin-top: 8px;
            }

            th, td {
                text-align: left;
                padding: 16px 28px;
                border-top: 1px solid var(--line);
                white-space: nowrap;
            }

            th {
                font-size: 12px;
                letter-spacing: 0.08em;
                text-transform: uppercase;
                color: var(--muted);
                font-weight: 600;
            }

            tbody tr { transition: background 140ms ease; }
            tbody tr:hover { background: #f7f1e6; }

            .username {
                font-weight: 700;
            }

            .username span {
                color: var(--accent);
                font-weight: 600;
                margin-right: 1px;
            }

            .mono {
                font-variant-numeric: tabular-nums;
                font-feature-settings: "tnum";
            }

            .muted { color: var(--muted); }

            .pill {
                display: inline-flex;
                align-items: center;
                gap: 6px;
                border-radius: 999px;
                padding: 5px 10px;
                font-size: 12px;
                font-weight: 700;
                letter-spacing: 0.02em;
            }

            .pill::before {
                content: "";
                width: 7px;
                height: 7px;
                border-radius: 50%;
                background: currentColor;
            }

            .pill-ok { color: var(--accent); background: var(--accent-soft); }
            .pill-failed { color: var(--danger); background: var(--danger-soft); }
            .pill-pending { color: var(--pending); background: var(--pending-soft); }

            .empty {
                padding: 56px 28px;
                text-align: center;
            }

            .empty strong {
                display: block;
                font-family: Fraunces, ui-serif, Georgia, serif;
                font-size: 24px;
                margin-bottom: 8px;
            }

            .back {
                color: var(--muted);
                font-size: 14px;
                font-weight: 600;
            }

            .back:hover { color: var(--ink); }

            .hero {
                padding: 28px 28px 8px;
            }

            .hero .handle {
                color: var(--accent);
            }

            .uuid {
                margin-top: 10px;
                display: inline-block;
                padding: 6px 10px;
                border-radius: 8px;
                background: #f3eee3;
                color: var(--muted);
                font-size: 12px;
                letter-spacing: 0.02em;
            }

            .stats {
                display: grid;
                grid-template-columns: repeat(3, 1fr);
                gap: 16px;
                padding: 24px 28px 28px;
            }

            .stat {
                padding: 18px 18px 16px;
                border: 1px solid var(--line);
                border-radius: 16px;
                background: #fcf8ef;
            }

            .stat span {
                display: block;
                color: var(--muted);
                font-size: 12px;
                letter-spacing: 0.08em;
                text-transform: uppercase;
                font-weight: 600;
                margin-bottom: 8px;
            }

            .stat strong {
                font-family: Fraunces, ui-serif, Georgia, serif;
                font-size: 30px;
                font-weight: 640;
            }

            .meta {
                display: grid;
                grid-template-columns: 1fr 1fr;
                gap: 0;
                border-top: 1px solid var(--line);
            }

            .meta div {
                padding: 22px 28px;
            }

            .meta div + div {
                border-left: 1px solid var(--line);
            }

            .meta span {
                display: block;
                color: var(--muted);
                font-size: 12px;
                letter-spacing: 0.08em;
                text-transform: uppercase;
                font-weight: 600;
                margin-bottom: 8px;
            }

            .failure {
                grid-column: 1 / -1;
                border-top: 1px solid var(--line);
                border-left: 0 !important;
                background: #fbf4f1;
            }

            @media (max-width: 720px) {
                .page-head, .topbar { flex-direction: column; align-items: flex-start; }
                .stats, .meta { grid-template-columns: 1fr; }
                .meta div + div { border-left: 0; border-top: 1px solid var(--line); }
                th, td { padding: 14px 18px; }
                .page-head, .hero { padding-left: 18px; padding-right: 18px; }
            }
        </style>
    </head>
    <body>
        <div class="shell">
            <header class="topbar">
                <a class="brand" href="{{ route('profiles.index') }}">
                    <small>FansAPI</small>
                    <strong>Profiles</strong>
                </a>
                @yield('nav')
            </header>
            @yield('content')
        </div>
    </body>
</html>
