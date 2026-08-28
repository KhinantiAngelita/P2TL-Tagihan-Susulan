{{--
    Sidebar navigasi utama.
    Responsive: di layar <= 980px sidebar ini jadi off-canvas via CSS transform.
    Minimize (desktop): di layar > 980px, sidebar bisa diciutkan jadi icon-only.
--}}
<aside class="sidebar" id="mainSidebar">
    <div class="sidebar-brand">
        <div class="brand-logo">
            <img src="{{ asset('logo/Logo 1.png') }}" alt="Logo PLN">
        </div>
        <div class="brand-text">
            <strong>PT PLN (Persero)</strong>
            <span>Sistem Laporan</span>
        </div>

        <button type="button" class="sidebar-collapse-btn" id="sidebarCollapseBtn" title="Ciutkan Menu" aria-label="Ciutkan/Perlebar Menu" aria-expanded="true">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="m15 18-6-6 6-6"/></svg>
        </button>

        <button type="button" class="sidebar-close-btn" id="sidebarCloseBtn" title="Tutup Menu" aria-label="Tutup Menu">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M18 6 6 18"/><path d="m6 6 12 12"/></svg>
        </button>
    </div>

    <div class="sidebar-label">Menu Utama</div>

    <nav class="sidebar-nav">
        {{-- Dashboard --}}
        <a href="{{ route('dashboard') }}" class="nav-item {{ request()->routeIs('dashboard') ? 'active' : '' }}" title="Dashboard">
            <span class="nav-icon">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="3" width="7" height="7" rx="1.5"/><rect x="14" y="3" width="7" height="7" rx="1.5"/><rect x="3" y="14" width="7" height="7" rx="1.5"/><rect x="14" y="14" width="7" height="7" rx="1.5"/></svg>
            </span>
            <span class="nav-text">Dashboard</span>
            @if(request()->routeIs('dashboard'))<span class="nav-chevron">›</span>@endif
        </a>

        {{-- Upload Excel (Ikon File Upload / Spreadsheet) --}}
        <a href="{{ route('laporan.create') }}" class="nav-item {{ request()->routeIs('laporan.create') ? 'active' : '' }}" title="Upload Excel">
            <span class="nav-icon">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M14.5 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V7.5L14.5 2z"/><polyline points="14 2 14 8 20 8"/><path d="M12 12v6"/><path d="M15 15l-3-3-3 3"/></svg>
            </span>
            <span class="nav-text">Upload Excel</span>
            @if(request()->routeIs('laporan.create'))<span class="nav-chevron">›</span>@endif
        </a>

        {{-- Export PDF --}}
        <a href="{{ route('export-pdf.index') }}" class="nav-item {{ request()->routeIs('export-pdf.*') ? 'active' : '' }}" title="Export PDF">
            <span class="nav-icon">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M6 3h9l5 5v13a1 1 0 0 1-1 1H6a1 1 0 0 1-1-1V4a1 1 0 0 1 1-1Z"/><path d="M12 11v6m0 0-2.5-2.5M12 17l2.5-2.5"/></svg>
            </span>
            <span class="nav-text">Export PDF</span>
            @if(request()->routeIs('export-pdf.*'))<span class="nav-chevron">›</span>@endif
        </a>

        {{-- Daftar Laporan (Ikon Dokumen/Arsip) --}}
        <a href="{{ route('laporan.index') }}" class="nav-item {{ request()->routeIs('laporan.index', 'laporan.show') ? 'active' : '' }}" title="Daftar Laporan">
            <span class="nav-icon">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M4 19.5v-15A2.5 2.5 0 0 1 6.5 2H20v20H6.5a2.5 2.5 0 0 1-2.5-2.5Z"/><path d="M6 6h10"/><path d="M6 10h10"/><path d="M6 14h6"/></svg>
            </span>
            <span class="nav-text">Daftar Laporan</span>
            @if(request()->routeIs('laporan.index', 'laporan.show'))<span class="nav-chevron">›</span>@endif
        </a>

        {{-- Daftar Pelanggan (Ikon Group/Banyak Orang) --}}
        <a href="{{ route('pelanggan.index') }}" class="nav-item {{ request()->routeIs('pelanggan.*') ? 'active' : '' }}" title="Daftar Pelanggan">
            <span class="nav-icon">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M22 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/></svg>
            </span>
            <span class="nav-text">Daftar Pelanggan</span>
            @if(request()->routeIs('pelanggan.*'))<span class="nav-chevron">›</span>@endif
        </a>

        {{-- Submenu Laporan (Ikon Grafik Analitik / Bar Chart) --}}
        <div class="nav-group">
            <button type="button"
                    class="nav-item nav-item-toggle {{ request()->routeIs('laporan.gol-tarif', 'laporan.komposisi-temuan', 'laporan.target-realisasi') ? 'active' : '' }}"
                    id="laporanMenuToggle"
                    aria-controls="laporanSubmenu"
                    aria-expanded="{{ request()->routeIs('laporan.gol-tarif', 'laporan.komposisi-temuan', 'laporan.target-realisasi') ? 'true' : 'false' }}">
                <span class="nav-icon">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="18" y1="20" x2="18" y2="10"/><line x1="12" y1="20" x2="12" y2="4"/><line x1="6" y1="20" x2="6" y2="14"/></svg>
                </span>
                <span class="nav-text">Laporan</span>
                <svg class="nav-caret" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round"><path d="m6 9 6 6 6-6"/></svg>
            </button>
            <div class="nav-submenu {{ request()->routeIs('laporan.gol-tarif', 'laporan.komposisi-temuan', 'laporan.target-realisasi') ? 'is-open' : '' }}" id="laporanSubmenu">
                <a href="{{ route('laporan.target-realisasi') }}" class="nav-subitem {{ request()->routeIs('laporan.target-realisasi') ? 'active' : '' }}">Target vs Realisasi</a>
                <a href="{{ route('laporan.gol-tarif') }}" class="nav-subitem {{ request()->routeIs('laporan.gol-tarif') ? 'active' : '' }}">Gol Tarif</a>
                <a href="{{ route('laporan.komposisi-temuan') }}" class="nav-subitem {{ request()->routeIs('laporan.komposisi-temuan') ? 'active' : '' }}">Komposisi Temuan</a>
            </div>
        </div>

        {{-- Submenu Trend (Ikon Tren Naik / Line Chart) --}}
        <div class="nav-group">
            <button type="button"
                    class="nav-item nav-item-toggle {{ request()->routeIs('trend.*') ? 'active' : '' }}"
                    id="trendMenuToggle"
                    title="Trend"
                    aria-controls="trendSubmenu"
                    aria-expanded="{{ request()->routeIs('trend.*') ? 'true' : 'false' }}">
                <span class="nav-icon">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="23 6 13.5 15.5 8.5 10.5 1 18"/><polyline points="17 6 23 6 23 12"/></svg>
                </span>
                <span class="nav-text">Trend</span>
                <svg class="nav-caret" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round"><path d="m6 9 6 6 6-6"/></svg>
            </button>
            <div class="nav-submenu {{ request()->routeIs('trend.*') ? 'is-open' : '' }}" id="trendSubmenu">
                <a href="{{ route('trend.pencapaian') }}" class="nav-subitem {{ request()->routeIs('trend.pencapaian') ? 'active' : '' }}">Data Pencapaian</a>
                <a href="{{ route('trend.kwh') }}" class="nav-subitem {{ request()->routeIs('trend.kwh') ? 'active' : '' }}">Trend kWh</a>
                <a href="{{ route('trend.ts') }}" class="nav-subitem {{ request()->routeIs('trend.ts') ? 'active' : '' }}">Trend Rp TS</a>
            </div>
        </div>

        {{-- Edit Target (Ikon Target / Bullseye / Panah Sasaran) --}}
        <a href="{{ route('edit-target.index') }}" class="nav-item {{ request()->routeIs('edit-target.*') ? 'active' : '' }}" title="Edit Target">
            <span class="nav-icon">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><circle cx="12" cy="12" r="6"/><circle cx="12" cy="12" r="2"/></svg>
            </span>
            <span class="nav-text">Edit Target</span>
        </a>

        {{-- Manajemen User (Ikon Perisai / Pengguna Admin) --}}
        @if (auth()->check() && auth()->user()->isSuperAdmin())
            <a href="{{ route('admin.users.index') }}" class="nav-item {{ request()->routeIs('admin.users.*') ? 'active' : '' }}" title="Manajemen User">
                <span class="nav-icon">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/></svg>
                </span>
                <span class="nav-text">Manajemen User</span>
                @if(request()->routeIs('admin.users.*'))<span class="nav-chevron">›</span>@endif
            </a>
        @endif

        {{-- Profil Saya (Ikon Pengaturan / User Settings) --}}
        <a href="{{ route('profile.show') }}" class="nav-item {{ request()->routeIs('profile.*') ? 'active' : '' }}" title="Profil Saya">
            <span class="nav-icon">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>
            </span>
            <span class="nav-text">Profil Saya</span>
            @if(request()->routeIs('profile.*'))<span class="nav-chevron">›</span>@endif
        </a>
    </nav>

    <div class="sidebar-user">
        <a href="{{ Route::has('profile.show') ? route('profile.show') : '#' }}" title="{{ $userName ?? auth()->user()?->name ?? 'Ahmad Rizki' }}" style="display:flex;align-items:center;gap:10px;text-decoration:none;color:inherit;flex:1;min-width:0">
            <div class="user-avatar">{{ $userInitials ?? 'AR' }}</div>
            <div class="user-info">
                <strong>{{ $userName ?? auth()->user()?->name ?? 'Ahmad Rizki' }}</strong>
                <span>{{ auth()->user()?->isSuperAdmin() ? 'Super Admin' : ($userRole ?? 'Pengguna') }}</span>
            </div>
        </a>
        @if (Route::has('logout'))
            <form action="{{ route('logout') }}" method="POST" style="margin:0">
                @csrf
                <button type="submit" class="user-logout" title="Keluar"
                        style="background:none;border:none;padding:0;cursor:pointer;font:inherit;color:inherit;">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"/><path d="M16 17l5-5-5-5"/><path d="M21 12H9"/></svg>
                </button>
            </form>
        @endif
    </div>
</aside>