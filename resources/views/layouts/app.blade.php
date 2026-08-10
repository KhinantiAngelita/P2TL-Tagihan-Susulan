<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@yield('title', 'Dashboard') — Sistem Laporan P2TL</title>
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4"></script>
    <style>
        :root{
            --navy-900:#0a1c42;
            --navy-800:#0d2159;
            --blue-primary:#0b3d91;
            --yellow:#ffc700;
            --yellow-dark:#f0b400;
            --bg:#f4f6fb;
            --text-dark:#1b2559;
            --text-muted:#6b7690;
            --border:#e7eaf3;
            --radius:12px;
        }
        *{box-sizing:border-box;}
        body{
            font-family:'Segoe UI',Arial,sans-serif;
            margin:0;
            background:var(--bg);
            color:var(--text-dark);
        }
        a{color:inherit;}

        /* ===== Layout shell ===== */
        .app-shell{display:flex;min-height:100vh;}
        .main-area{flex:1;margin-left:260px;display:flex;flex-direction:column;min-height:100vh;}
        .main-content{padding:28px 32px;flex:1;}

        /* ===== Sidebar ===== */
        .sidebar{
            width:260px;background:linear-gradient(180deg,#03045e 0%,#023e8a 100%);color:#fff;
            position:fixed;top:0;left:0;bottom:0;display:flex;flex-direction:column;
            padding:20px 16px;overflow-y:auto;
        }
        .sidebar-brand{display:flex;align-items:center;gap:12px;padding:6px 8px 20px;}
        .brand-logo{
            width:44px;height:44px;background:var(--yellow);color:#03045e;
            border-radius:10px;display:flex;align-items:center;justify-content:center;
            font-weight:800;font-size:13px;flex-shrink:0;
        }
        .brand-text{display:flex;flex-direction:column;line-height:1.3;}
        .brand-text strong{font-size:14px;}
        .brand-text span{font-size:11px;color:#93a0c9;}

        .sidebar-label{font-size:11px;letter-spacing:.08em;color:#6a78a8;text-transform:uppercase;padding:14px 10px 8px;font-weight:600;}

        .sidebar-nav{display:flex;flex-direction:column;gap:4px;flex:1;}
        .nav-item{
            display:flex;align-items:center;gap:12px;padding:11px 14px;border-radius:10px;
            text-decoration:none;color:#c3ccec;font-size:14px;font-weight:600;transition:background .15s;
        }
        .nav-item:hover{background:rgba(255,255,255,.06);}
        .nav-item.active{background:var(--yellow);color:var(--navy-900);}
        .nav-icon{width:20px;height:20px;flex-shrink:0;display:flex;}
        .nav-icon svg{width:20px;height:20px;}
        .nav-text{flex:1;}
        .nav-chevron{font-size:16px;}

        .sidebar-user{
            display:flex;align-items:center;gap:10px;padding:14px 10px;margin-top:10px;
            border-top:1px solid rgba(255,255,255,.1);
        }
        .user-avatar{
            width:38px;height:38px;border-radius:50%;background:var(--yellow);color:var(--navy-900);
            display:flex;align-items:center;justify-content:center;font-weight:800;font-size:13px;flex-shrink:0;
        }
        .user-avatar.small{width:30px;height:30px;font-size:12px;}
        .user-info{display:flex;flex-direction:column;flex:1;line-height:1.3;min-width:0;}
        .user-info strong{font-size:13px;color:#fff;}
        .user-info span{font-size:11px;color:#93a0c9;}
        .user-logout{color:#93a0c9;display:flex;}
        .user-logout svg{width:18px;height:18px;}
        .user-logout:hover{color:#fff;}

        /* ===== Topbar ===== */
        .topbar{
            height:74px;background:#fff;border-bottom:1px solid var(--border);
            display:flex;align-items:center;justify-content:space-between;
            padding:0 32px;position:sticky;top:0;z-index:20;
        }
        .topbar-breadcrumb{font-size:14px;color:var(--text-muted);display:flex;align-items:center;gap:8px;}
        .topbar-breadcrumb a{text-decoration:none;color:var(--text-muted);}
        .topbar-breadcrumb strong{color:var(--text-dark);font-weight:700;}
        .topbar-breadcrumb .sep{color:#c2c9de;}

        .topbar-right{display:flex;align-items:center;gap:20px;}
        .topbar-date{display:flex;flex-direction:column;text-align:right;line-height:1.3;}
        .topbar-date strong{font-size:13px;color:var(--text-dark);}
        .topbar-date span{font-size:11px;color:var(--text-muted);}

        .topbar-icon-btn{
            width:38px;height:38px;border-radius:50%;border:1px solid var(--border);background:#fff;
            display:flex;align-items:center;justify-content:center;cursor:pointer;position:relative;color:var(--text-muted);
        }
        .topbar-icon-btn svg{width:18px;height:18px;}
        .topbar-icon-dot{position:absolute;top:8px;right:9px;width:7px;height:7px;border-radius:50%;background:var(--yellow-dark);}

        .topbar-user{display:flex;align-items:center;gap:8px;cursor:pointer;}
        .topbar-user strong{font-size:13px;}
        .topbar-user svg{width:14px;height:14px;color:var(--text-muted);}

        /* ===== Content building blocks (dipakai di semua halaman) ===== */
        .card{background:#fff;border-radius:var(--radius);padding:22px;margin-bottom:20px;box-shadow:0 1px 4px rgba(20,30,80,.06);border:1px solid var(--border);}
        .grid{display:grid;grid-template-columns:repeat(auto-fit,minmax(220px,1fr));gap:16px;margin-bottom:20px;}
        .grid-2{display:grid;grid-template-columns:1.4fr 1fr;gap:16px;margin-bottom:20px;}
        @media(max-width:900px){.grid-2{grid-template-columns:1fr;}}

        .stat-card{background:#fff;border-radius:var(--radius);padding:20px;border:1px solid var(--border);box-shadow:0 1px 4px rgba(20,30,80,.06);}
        .stat-card.highlight{border-color:var(--yellow);box-shadow:0 0 0 1px var(--yellow) inset;}
        .stat-card .stat-top{display:flex;align-items:center;gap:12px;margin-bottom:10px;}
        .stat-icon{width:38px;height:38px;border-radius:10px;display:flex;align-items:center;justify-content:center;flex-shrink:0;}
        .stat-icon svg{width:19px;height:19px;}
        .stat-icon.blue{background:#eaf1ff;color:var(--blue-primary);}
        .stat-icon.yellow{background:#fff6da;color:var(--yellow-dark);}
        .stat-icon.green{background:#e6f7ea;color:#1a9c4a;}
        .stat-icon.purple{background:#f1ecff;color:#7c4dff;}
        .stat-card h3{margin:0;font-size:13px;color:var(--text-muted);font-weight:600;}
        .stat-card .stat-value{font-size:24px;font-weight:800;color:var(--text-dark);}
        .stat-card .stat-sub{font-size:12px;color:var(--text-muted);margin-top:4px;}

        table{width:100%;border-collapse:collapse;}
        th,td{padding:11px 12px;border-bottom:1px solid var(--border);text-align:left;font-size:13.5px;}
        th{color:var(--text-muted);font-weight:700;font-size:12px;text-transform:uppercase;letter-spacing:.03em;}

        .btn{background:var(--blue-primary);color:#fff;padding:9px 18px;border-radius:8px;text-decoration:none;display:inline-flex;align-items:center;gap:6px;border:none;font-weight:700;font-size:13.5px;cursor:pointer;}
        .btn-yellow{background:var(--yellow);color:var(--navy-900);}
        .btn-outline{background:#fff;color:var(--blue-primary);border:1px solid var(--border);}

        .badge{display:inline-flex;padding:4px 11px;border-radius:20px;background:#eaf1ff;color:var(--blue-primary);font-size:12px;font-weight:700;}

        .alert-success{background:#e6f7ea;color:#17643a;padding:10px 16px;border-radius:8px;margin-bottom:16px;}
        .alert-error{background:#fdecea;color:#9d2b1f;padding:10px 16px;border-radius:8px;margin-bottom:16px;}
    </style>
    @stack('styles')
</head>
<body>
    <div class="app-shell">
        @include('layouts.partials.sidebar')

        <div class="main-area">
            @include('layouts.partials.topbar')

            <div class="main-content">
                @if (session('success'))
                    <div class="alert-success">{{ session('success') }}</div>
                @endif
                @if ($errors->any())
                    <div class="alert-error">{{ $errors->first() }}</div>
                @endif

                @yield('content')
            </div>
        </div>
    </div>

    @stack('scripts')
</body>
</html>