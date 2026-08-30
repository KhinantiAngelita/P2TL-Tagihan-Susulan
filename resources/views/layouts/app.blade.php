<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@yield('title', 'Dashboard') — Sistem Laporan P2TL</title>

    {{-- Favicon (ikon tab browser) — pakai file logo yang sama dengan
         yang dipakai di sidebar (.brand-logo img). --}}
    <link rel="icon" type="image/jpg" href="{{ asset('logo/Logo 3.jpg') }}">

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
            --sidebar-w:260px;
            --sidebar-w-collapsed:78px;
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
        .app-shell{display:flex;min-height:100vh;max-width:100vw;overflow-x:hidden;}
        /*
            PENTING: .sidebar itu position:fixed, artinya dia udah KELUAR dari
            flow flex .app-shell. Kalau .main-area dikasih flex:1, dia bakal
            dihitung mengisi SELURUH lebar .app-shell (bukan sisa setelah
            sidebar), terus margin-left di bawah ini nambahin 260px lagi di
            atas itu → totalnya kelebihan lebar sejumlah lebar sidebar dan
            bikin body horizontal-scroll (kepotong di kanan). Makanya di sini
            sengaja pakai width: calc(100% - var(--sidebar-w)) yang eksplisit,
            BUKAN flex:1, biar lebarnya emang sisa viewport dikurangi sidebar.
        */
        .main-area{
            width:calc(100% - var(--sidebar-w));
            margin-left:var(--sidebar-w);
            display:flex;flex-direction:column;min-height:100vh;min-width:0;
            transition:margin-left .2s ease,width .2s ease;
        }
        .main-area.is-collapsed{
            width:calc(100% - var(--sidebar-w-collapsed));
            margin-left:var(--sidebar-w-collapsed);
        }
        .main-content{padding:28px 32px;flex:1;min-width:0;overflow-x:hidden;}

        /* ===== Sidebar ===== */
        .sidebar{
            width:var(--sidebar-w);background:linear-gradient(180deg,#03045e 0%,#023e8a 100%);color:#fff;
            position:fixed;top:0;left:0;bottom:0;display:flex;flex-direction:column;
            padding:20px 16px;overflow-x:hidden;overflow-y:auto;
            z-index:100;
            transition:transform .25s ease,width .2s ease,padding .2s ease;
        }
        .sidebar-brand{display:flex;align-items:center;gap:12px;padding:6px 8px 20px;}
        .brand-logo{
            width:44px;height:44px;background:transparent;
            border-radius:10px;display:flex;align-items:center;justify-content:center;
            font-weight:800;font-size:13px;flex-shrink:0;overflow:hidden;
        }
        .brand-logo img{
            width:100%;height:100%;object-fit:contain;display:block;
        }
        .brand-text{display:flex;flex-direction:column;line-height:1.3;white-space:nowrap;}
        .brand-text strong{font-size:14px;}
        .brand-text span{font-size:11px;color:#93a0c9;}

        .sidebar-close-btn{
            display:none;margin-left:auto;background:none;border:none;color:#c3ccec;
            cursor:pointer;padding:6px;border-radius:8px;flex-shrink:0;
        }
        .sidebar-close-btn svg{width:20px;height:20px;}

        /* Tombol minimize/expand — cuma tampil di desktop (lihat media query
           di bawah). Ditaro sebelah brand text, geser ke pojok kanan kalau
           sidebar lagi diperlebar, geser ke tengah kalau lagi diciutkan. */
        .sidebar-collapse-btn{
            display:flex;align-items:center;justify-content:center;margin-left:auto;
            width:30px;height:30px;flex-shrink:0;background:rgba(255,255,255,.08);border:none;
            color:#c3ccec;cursor:pointer;border-radius:8px;transition:background .15s,transform .2s ease;
        }
        .sidebar-collapse-btn:hover{background:rgba(255,255,255,.16);color:#fff;}
        .sidebar-collapse-btn svg{width:16px;height:16px;transition:transform .2s ease;}

        .sidebar-label{font-size:11px;letter-spacing:.08em;color:#6a78a8;text-transform:uppercase;padding:14px 10px 8px;font-weight:600;white-space:nowrap;}

        .sidebar-nav{display:flex;flex-direction:column;gap:4px;flex:1;}
        .nav-item{
            display:flex;align-items:center;gap:12px;padding:11px 14px;border-radius:10px;
            text-decoration:none;color:#c3ccec;font-size:14px;font-weight:600;transition:background .15s;
            white-space:nowrap;overflow:hidden;
        }
        .nav-item:hover{background:rgba(255,255,255,.06);}
        .nav-item.active{background:var(--yellow);color:var(--navy-900);}
        .nav-icon{width:20px;height:20px;flex-shrink:0;display:flex;}
        .nav-icon svg{width:20px;height:20px;}
        .nav-text{flex:1;overflow:hidden;text-overflow:ellipsis;}
        .nav-chevron{font-size:16px;}

        /* ---------- Menu dengan submenu (contoh: Trend) ---------- */
        .nav-group{display:flex;flex-direction:column;}
        .nav-item-toggle{
            width:100%;background:none;border:none;cursor:pointer;font-family:inherit;
            text-align:left;
        }
        .nav-caret{width:15px;height:15px;margin-left:auto;flex-shrink:0;transition:transform .18s ease;}
        .nav-item-toggle[aria-expanded="true"] .nav-caret{transform:rotate(180deg);}
        .nav-item-toggle.active{background:var(--yellow);color:var(--navy-900);}
        .nav-item-toggle.active .nav-caret{color:var(--navy-900);}

        .nav-submenu{
            display:none;flex-direction:column;gap:2px;
            padding:4px 0 4px 34px;margin-bottom:2px;
        }
        .nav-submenu.is-open{display:flex;}
        .nav-subitem{
            padding:9px 14px;border-radius:8px;text-decoration:none;
            color:#a9b3d6;font-size:13.5px;font-weight:600;white-space:nowrap;
        }
        .nav-subitem:hover{background:rgba(255,255,255,.07);color:#fff;}
        .nav-subitem.active{background:rgba(255,199,0,.16);color:var(--yellow);}

        .sidebar-user{
            display:flex;align-items:center;gap:10px;padding:14px 10px;margin-top:10px;
            border-top:1px solid rgba(255,255,255,.1);
        }
        .user-avatar{
            width:38px;height:38px;border-radius:50%;background:var(--yellow);color:var(--navy-900);
            display:flex;align-items:center;justify-content:center;font-weight:800;font-size:13px;flex-shrink:0;
        }
        .user-avatar.small{width:30px;height:30px;font-size:12px;}
        .user-info{display:flex;flex-direction:column;flex:1;line-height:1.3;min-width:0;white-space:nowrap;}
        .user-info strong{font-size:13px;color:#fff;overflow:hidden;text-overflow:ellipsis;}
        .user-info span{font-size:11px;color:#93a0c9;overflow:hidden;text-overflow:ellipsis;}
        .user-logout{color:#93a0c9;display:flex;flex-shrink:0;}
        .user-logout svg{width:18px;height:18px;}
        .user-logout:hover{color:#fff;}

        /* ===== Sidebar diciutkan (desktop only) ===== */
        .sidebar.is-collapsed{width:var(--sidebar-w-collapsed);padding-left:10px;padding-right:10px;}
        .sidebar.is-collapsed .sidebar-brand{justify-content:center;padding-left:0;padding-right:0;}
        .sidebar.is-collapsed .brand-text,
        .sidebar.is-collapsed .sidebar-label,
        .sidebar.is-collapsed .nav-text,
        .sidebar.is-collapsed .nav-chevron,
        .sidebar.is-collapsed .nav-caret,
        .sidebar.is-collapsed .nav-submenu,
        .sidebar.is-collapsed .user-info{display:none;}
        .sidebar.is-collapsed .sidebar-collapse-btn{margin-left:0;transform:rotate(180deg);}
        .sidebar.is-collapsed .nav-item,
        .sidebar.is-collapsed .nav-item-toggle{justify-content:center;padding:11px 0;gap:0;}
        .sidebar.is-collapsed .sidebar-user{justify-content:center;padding:14px 0;}

        /* Overlay backdrop untuk sidebar mobile */
        .sidebar-overlay{
            display:none;position:fixed;inset:0;background:rgba(7,18,51,.5);
            z-index:90;
        }

        /* Tombol hamburger — hanya tampil di layar sempit */
        .hamburger-btn{
            display:none;width:38px;height:38px;border-radius:10px;border:1px solid var(--border);
            background:#fff;align-items:center;justify-content:center;cursor:pointer;color:var(--text-dark);
            flex-shrink:0;
        }
        .hamburger-btn svg{width:20px;height:20px;}

        /* ===== Topbar ===== */
        .topbar{
            height:74px;background:#fff;border-bottom:1px solid var(--border);
            display:flex;align-items:center;justify-content:space-between;
            padding:0 32px;position:sticky;top:0;z-index:20;
            gap:12px;
        }
        .topbar-breadcrumb{font-size:14px;color:var(--text-muted);display:flex;align-items:center;gap:8px;min-width:0;overflow:hidden;white-space:nowrap;text-overflow:ellipsis;}
        .topbar-breadcrumb a{text-decoration:none;color:var(--text-muted);}
        .topbar-breadcrumb strong{color:var(--text-dark);font-weight:700;}
        .topbar-breadcrumb .sep{color:#c2c9de;}

        .topbar-left{display:flex;align-items:center;gap:14px;min-width:0;}

        .topbar-right{display:flex;align-items:center;gap:20px;flex-shrink:0;}
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

        /* ===== Stat cards (dipakai di dashboard, detail laporan, dll — SATU sumber,
           jangan diduplikat/inline lagi di masing-masing file blade) ===== */
        .dash-stats{display:grid;grid-template-columns:repeat(auto-fit,minmax(220px,1fr));gap:16px;margin-bottom:20px;}
        .dash-stat-card{position:relative;background:#fff;border-radius:14px;padding:20px 22px;border:1px solid var(--border);box-shadow:0 1px 4px rgba(20,30,80,.05);overflow:hidden;transition:box-shadow .18s,transform .18s;}
        .dash-stat-card:hover{box-shadow:0 8px 20px rgba(20,30,80,.09);transform:translateY(-2px);}
        .dash-stat-card::before{content:"";position:absolute;top:0;left:0;right:0;height:3px;}
        .dash-stat-card.tone-yellow::before{background:var(--yellow);}
        .dash-stat-card.tone-blue::before{background:var(--blue-primary);}
        .dash-stat-card.tone-green::before{background:#1a9c4a;}
        .dash-stat-card.tone-purple::before{background:#7c4dff;}

        .dash-stat-top{display:flex;align-items:center;gap:12px;margin-bottom:14px;}
        .dash-stat-icon{width:40px;height:40px;border-radius:11px;display:flex;align-items:center;justify-content:center;flex-shrink:0;}
        .dash-stat-icon svg{width:19px;height:19px;}
        .tone-yellow .dash-stat-icon{background:#fff6da;color:#b98600;}
        .tone-blue .dash-stat-icon{background:#eaf1ff;color:var(--blue-primary);}
        .tone-green .dash-stat-icon{background:#e6f7ea;color:#1a9c4a;}
        .tone-purple .dash-stat-icon{background:#f1ecff;color:#7c4dff;}
        .dash-stat-top h3{margin:0;font-size:13px;color:var(--text-muted);font-weight:700;}

        .dash-stat-value{font-size:25px;font-weight:800;color:var(--text-dark);letter-spacing:-.2px;word-break:break-word;}
        .dash-stat-sub{display:flex;align-items:center;gap:6px;margin-top:6px;font-size:12px;color:var(--text-muted);flex-wrap:wrap;}
        .dash-stat-sub svg{width:13px;height:13px;flex-shrink:0;}

        table{width:100%;border-collapse:collapse;}
        th,td{padding:11px 12px;border-bottom:1px solid var(--border);text-align:left;font-size:13.5px;}
        th{color:var(--text-muted);font-weight:700;font-size:12px;text-transform:uppercase;letter-spacing:.03em;}

        .btn{background:var(--blue-primary);color:#fff;padding:9px 18px;border-radius:8px;text-decoration:none;display:inline-flex;align-items:center;gap:6px;border:none;font-weight:700;font-size:13.5px;cursor:pointer;}
        .btn-yellow{background:var(--yellow);color:var(--navy-900);}
        .btn-outline{background:#fff;color:var(--blue-primary);border:1px solid var(--border);}

        .badge{display:inline-flex;padding:4px 11px;border-radius:20px;background:#eaf1ff;color:var(--blue-primary);font-size:12px;font-weight:700;}

        .alert-success{background:#e6f7ea;color:#17643a;padding:10px 16px;border-radius:8px;margin-bottom:16px;}
        .alert-error{background:#fdecea;color:#9d2b1f;padding:10px 16px;border-radius:8px;margin-bottom:16px;}

        .table-empty-state{text-align:center;padding:26px 0;color:#9aa4c2;font-size:13.5px;}
        .table-empty-state svg{width:26px;height:26px;margin-bottom:8px;opacity:.6;}

        /* Wrapper generik biar tabel bisa discroll horizontal di layar sempit
           tanpa merusak layout tabel itu sendiri */
        .table-scroll{width:100%;overflow-x:auto;-webkit-overflow-scrolling:touch;}

        /* ============ RESPONSIVE ============ */
        @media (min-width: 981px){
            /* Tombol minimize cuma relevan di desktop — di mobile sidebar
               sudah off-canvas jadi minimize gak diperlukan. */
            .sidebar-collapse-btn{display:flex;}
        }

        @media (max-width: 980px){
            .main-area,.main-area.is-collapsed{margin-left:0 !important;width:100% !important;}
            .sidebar{transform:translateX(-100%);box-shadow:0 0 40px rgba(0,0,0,.3);width:var(--sidebar-w) !important;padding:20px 16px !important;}
            .sidebar.is-open{transform:translateX(0);}
            .sidebar.is-collapsed .brand-text,
            .sidebar.is-collapsed .sidebar-label,
            .sidebar.is-collapsed .nav-text,
            .sidebar.is-collapsed .nav-chevron,
            .sidebar.is-collapsed .nav-caret,
            .sidebar.is-collapsed .user-info{display:revert;}
            .sidebar.is-collapsed .nav-submenu.is-open{display:flex;}
            .sidebar.is-collapsed .nav-item,
            .sidebar.is-collapsed .nav-item-toggle{justify-content:flex-start;padding:11px 14px;gap:12px;}
            .sidebar.is-collapsed .sidebar-user{justify-content:flex-start;padding:14px 10px;}
            .sidebar-close-btn{display:flex;align-items:center;justify-content:center;}
            .sidebar-collapse-btn{display:none;}
            .sidebar-overlay.is-open{display:block;}
            .hamburger-btn{display:flex;}
            .topbar{padding:0 20px;}
            .main-content{padding:22px 20px;}
        }

        @media (max-width: 640px){
            .topbar{height:64px;padding:0 14px;}
            .main-content{padding:16px 14px;}
            .topbar-date{display:none;}
            .topbar-right{gap:12px;}
            .card{padding:16px;}
            .dash-stat-value{font-size:21px;}
        }

        @media (max-width: 420px){
            .topbar-user span{display:none;}
            .topbar-user svg{display:none;}
        }
    </style>
    @stack('styles')
</head>
<body>
    <div class="app-shell">
        <div class="sidebar-overlay" id="sidebarOverlay"></div>
        @include('layouts.partials.sidebar')

        <div class="main-area" id="mainArea">
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

    <script>
        (function () {
            const sidebar = document.querySelector('.sidebar');
            const mainArea = document.getElementById('mainArea');
            const overlay = document.getElementById('sidebarOverlay');
            const openBtn = document.getElementById('sidebarToggleBtn');
            const closeBtn = document.getElementById('sidebarCloseBtn');
            const collapseBtn = document.getElementById('sidebarCollapseBtn');
            const STORAGE_KEY = 'sidebar-collapsed';

            function openSidebar() {
                sidebar.classList.add('is-open');
                overlay.classList.add('is-open');
            }
            function closeSidebar() {
                sidebar.classList.remove('is-open');
                overlay.classList.remove('is-open');
            }

            if (openBtn) openBtn.addEventListener('click', openSidebar);
            if (closeBtn) closeBtn.addEventListener('click', closeSidebar);
            overlay.addEventListener('click', closeSidebar);

            // Tutup sidebar otomatis kalau resize ke desktop
            window.addEventListener('resize', function () {
                if (window.innerWidth > 980) closeSidebar();
            });

            // ===== Minimize/expand sidebar (desktop) =====
            function applyCollapsed(isCollapsed) {
                sidebar.classList.toggle('is-collapsed', isCollapsed);
                mainArea.classList.toggle('is-collapsed', isCollapsed);
                if (collapseBtn) collapseBtn.setAttribute('aria-expanded', isCollapsed ? 'false' : 'true');
            }

            // Terapkan preferensi tersimpan sebelum user sempat interaksi
            try {
                applyCollapsed(window.innerWidth > 980 && localStorage.getItem(STORAGE_KEY) === '1');
            } catch (e) { /* localStorage gak tersedia — abaikan, default expanded */ }

            if (collapseBtn) {
                collapseBtn.addEventListener('click', function () {
                    const next = !sidebar.classList.contains('is-collapsed');
                    applyCollapsed(next);
                    try { localStorage.setItem(STORAGE_KEY, next ? '1' : '0'); } catch (e) {}
                });
            }

            // Expand/collapse menu yang punya submenu (mis. "Trend")
            document.querySelectorAll('.nav-item-toggle').forEach(function (toggle) {
                toggle.addEventListener('click', function () {
                    // Kalau sidebar lagi diciutkan, buka dulu sidebarnya
                    // biar submenu-nya keliatan, baru toggle submenu-nya.
                    if (sidebar.classList.contains('is-collapsed')) {
                        applyCollapsed(false);
                        try { localStorage.setItem(STORAGE_KEY, '0'); } catch (e) {}
                    }
                    const submenu = document.getElementById(toggle.getAttribute('aria-controls'));
                    if (!submenu) return;
                    const isOpen = submenu.classList.toggle('is-open');
                    toggle.setAttribute('aria-expanded', isOpen ? 'true' : 'false');
                });
            });
        })();
    </script>
</body>
</html>