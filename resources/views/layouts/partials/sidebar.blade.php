{{--
    Sidebar navigasi utama.
    Item tanpa route asli (Analitik) masih diarahkan ke "#" — tinggal ganti
    route('nama.route') begitu halamannya jadi.

    Responsive: di layar <= 980px sidebar ini jadi off-canvas (digeser keluar
    layar via CSS transform di layouts/app.blade.php) dan dibuka lewat tombol
    hamburger di topbar. Tombol X di bawah cuma tampil di mobile untuk nutup.
--}}
<aside class="sidebar" id="mainSidebar">
    <div class="sidebar-brand">
        <div class="brand-logo">PLN</div>
        <div class="brand-text">
            <strong>PT PLN (Persero)</strong>
            <span>Sistem Laporan</span>
        </div>
        <button type="button" class="sidebar-close-btn" id="sidebarCloseBtn" title="Tutup Menu" aria-label="Tutup Menu">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M18 6 6 18"/><path d="m6 6 12 12"/></svg>
        </button>
    </div>

    <div class="sidebar-label">Menu Utama</div>

    <nav class="sidebar-nav">
        <a href="{{ route('dashboard') }}" class="nav-item {{ request()->routeIs('dashboard') ? 'active' : '' }}">
            <span class="nav-icon">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="3" width="7" height="7" rx="1.5"/><rect x="14" y="3" width="7" height="7" rx="1.5"/><rect x="3" y="14" width="7" height="7" rx="1.5"/><rect x="14" y="14" width="7" height="7" rx="1.5"/></svg>
            </span>
            <span class="nav-text">Dashboard</span>
            @if(request()->routeIs('dashboard'))<span class="nav-chevron">›</span>@endif
        </a>

        <a href="{{ route('laporan.create') }}" class="nav-item {{ request()->routeIs('laporan.create') ? 'active' : '' }}">
            <span class="nav-icon">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 16V4M12 4l-4 4M12 4l4 4"/><path d="M4 16v3a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2v-3"/></svg>
            </span>
            <span class="nav-text">Upload Excel</span>
            @if(request()->routeIs('laporan.create'))<span class="nav-chevron">›</span>@endif
        </a>

        <a href="{{ route('laporan.index') }}" class="nav-item {{ request()->routeIs('laporan.index', 'laporan.show') ? 'active' : '' }}">
            <span class="nav-icon">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M6 3h9l5 5v13a1 1 0 0 1-1 1H6a1 1 0 0 1-1-1V4a1 1 0 0 1 1-1Z"/><path d="M9 12h6M9 16h6M9 8h2"/></svg>
            </span>
            <span class="nav-text">Daftar Laporan</span>
            @if(request()->routeIs('laporan.index', 'laporan.show'))<span class="nav-chevron">›</span>@endif
        </a>

        <div class="nav-group">
            <button type="button"
                    class="nav-item nav-item-toggle {{ request()->routeIs('trend.*') ? 'active' : '' }}"
                    id="trendMenuToggle"
                    aria-controls="trendSubmenu"
                    aria-expanded="{{ request()->routeIs('trend.*') ? 'true' : 'false' }}">
                <span class="nav-icon">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M3 3v18h18"/><path d="M7 15l4-5 3 3 5-7"/></svg>
                </span>
                <span class="nav-text">Trend</span>
                <svg class="nav-caret" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round"><path d="m6 9 6 6 6-6"/></svg>
            </button>
            <div class="nav-submenu {{ request()->routeIs('trend.*') ? 'is-open' : '' }}" id="trendSubmenu">
                <a href="{{ route('trend.kwh') }}" class="nav-subitem {{ request()->routeIs('trend.kwh') ? 'active' : '' }}">Trend kWh</a>
                <a href="{{ route('trend.ts') }}" class="nav-subitem {{ request()->routeIs('trend.ts') ? 'active' : '' }}">Trend Rp TS</a>
            </div>
        </div>

        @if (auth()->check() && auth()->user()->isSuperAdmin())
            <a href="{{ route('admin.users.index') }}" class="nav-item {{ request()->routeIs('admin.users.*') ? 'active' : '' }}">
                <span class="nav-icon">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M17 21v-2a4 4 0 0 0-4-4H7a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/></svg>
                </span>
                <span class="nav-text">Manajemen User</span>
                @if(request()->routeIs('admin.users.*'))<span class="nav-chevron">›</span>@endif
            </a>
        @endif

        {{-- Slot "Pengaturan" sekarang mengarah ke halaman Profil Saya (bisa update nama/email/password) --}}
        <a href="{{ route('profile.show') }}" class="nav-item {{ request()->routeIs('profile.*') ? 'active' : '' }}">
            <span class="nav-icon">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="3"/><path d="M19.4 15a1.7 1.7 0 0 0 .34 1.87l.06.06a2 2 0 1 1-2.83 2.83l-.06-.06a1.7 1.7 0 0 0-1.87-.34 1.7 1.7 0 0 0-1.04 1.56V21a2 2 0 1 1-4 0v-.09A1.7 1.7 0 0 0 9 19.4a1.7 1.7 0 0 0-1.87.34l-.06.06a2 2 0 1 1-2.83-2.83l.06-.06A1.7 1.7 0 0 0 4.6 15a1.7 1.7 0 0 0-1.56-1.04H3a2 2 0 1 1 0-4h.09A1.7 1.7 0 0 0 4.6 9a1.7 1.7 0 0 0-.34-1.87l-.06-.06a2 2 0 1 1 2.83-2.83l.06.06A1.7 1.7 0 0 0 9 4.6a1.7 1.7 0 0 0 1.04-1.56V3a2 2 0 1 1 4 0v.09A1.7 1.7 0 0 0 15 4.6a1.7 1.7 0 0 0 1.87-.34l.06-.06a2 2 0 1 1 2.83 2.83l-.06.06A1.7 1.7 0 0 0 19.4 9a1.7 1.7 0 0 0 1.56 1.04H21a2 2 0 1 1 0 4h-.09a1.7 1.7 0 0 0-1.56 1.04Z"/></svg>
            </span>
            <span class="nav-text">Profil Saya</span>
            @if(request()->routeIs('profile.*'))<span class="nav-chevron">›</span>@endif
        </a>
    </nav>

    <div class="sidebar-user">
        <a href="{{ Route::has('profile.show') ? route('profile.show') : '#' }}" style="display:flex;align-items:center;gap:10px;text-decoration:none;color:inherit;flex:1">
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