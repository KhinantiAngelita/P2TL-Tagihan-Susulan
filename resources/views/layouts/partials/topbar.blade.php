{{--
    Topbar: breadcrumb otomatis (bisa ditimpa lewat @section('breadcrumb') di masing-masing view),
    tanggal hari ini berbahasa Indonesia + info triwulan, dropdown notifikasi (data asli dari DB),
    dan user dropdown.
--}}
@php
    $hariId  = ['Sunday'=>'Minggu','Monday'=>'Senin','Tuesday'=>'Selasa','Wednesday'=>'Rabu','Thursday'=>'Kamis','Friday'=>'Jumat','Saturday'=>'Sabtu'];
    $bulanId = ['January'=>'Januari','February'=>'Februari','March'=>'Maret','April'=>'April','May'=>'Mei','June'=>'Juni','July'=>'Juli','August'=>'Agustus','September'=>'September','October'=>'Oktober','November'=>'November','December'=>'Desember'];
    $now = \Carbon\Carbon::now();
    $tanggalId = $hariId[$now->format('l')].', '.$now->format('d').' '.$bulanId[$now->format('F')].' '.$now->format('Y');
    $triwulan = 'Triwulan '.['I','I','I','II','II','II','III','III','III','IV','IV','IV'][$now->month - 1];

    $displayName = $userName ?? auth()->user()?->name ?? 'Ahmad Rizki';
    $displayInitials = $userInitials ?? strtoupper(
        collect(explode(' ', trim($displayName)))->map(fn ($w) => mb_substr($w, 0, 1))->take(2)->implode('')
    ) ?: 'AR';
    $displayRole = auth()->user()
        ? (auth()->user()->isSuperAdmin() ? 'Super Admin' : 'Pengguna')
        : ($userRole ?? 'Pengguna');

    // Notifikasi asli dari database (Laravel database notifications), khusus user yang login.
    $recentNotifications = auth()->check() ? auth()->user()->notifications()->latest()->limit(6)->get() : collect();
    $unreadCount = auth()->check() ? auth()->user()->unreadNotifications()->count() : 0;
@endphp
<header class="topbar">
    <div class="topbar-left">
        {{-- Tombol hamburger — cuma tampil di layar sempit (lihat .hamburger-btn
             di layout utama). Klik ini yang men-trigger openSidebar() di script
             layouts/app.blade.php lewat id sidebarToggleBtn. --}}
        <button type="button" class="hamburger-btn" id="sidebarToggleBtn" aria-label="Buka menu navigasi">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="3" y1="6" x2="21" y2="6"/><line x1="3" y1="12" x2="21" y2="12"/><line x1="3" y1="18" x2="21" y2="18"/></svg>
        </button>

        <div class="topbar-breadcrumb">
            @hasSection('breadcrumb')
                @yield('breadcrumb')
            @else
                <a href="{{ route('dashboard') }}">Beranda</a>
                <span class="sep">›</span>
                <strong>@yield('title', 'Dashboard')</strong>
            @endif
        </div>
    </div>

    <div class="topbar-right">
        <div class="topbar-date">
            <strong>{{ $tanggalId }}</strong>
            <span>{{ $triwulan }} · {{ $now->year }}</span>
        </div>

        <div style="position:relative;">
            <button class="topbar-icon-btn" id="notifBtn" type="button" title="Notifikasi">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M18 8a6 6 0 0 0-12 0c0 7-3 9-3 9h18s-3-2-3-9"/><path d="M13.73 21a2 2 0 0 1-3.46 0"/></svg>
                @if ($unreadCount > 0)
                    <span class="topbar-icon-dot"></span>
                @endif
            </button>

            <div id="notifPanel" style="display:none;position:absolute;right:0;top:48px;width:340px;
                background:#fff;border:1px solid var(--border);border-radius:12px;
                box-shadow:0 10px 30px rgba(20,30,80,.15);z-index:50;overflow:hidden;">
                <div style="display:flex;align-items:center;justify-content:space-between;padding:14px 16px;border-bottom:1px solid var(--border);">
                    <strong style="font-size:14px;color:var(--text-dark);">Notifikasi</strong>
                    @if ($unreadCount > 0)
                        <form action="{{ route('notifications.readAll') }}" method="POST" style="margin:0;">
                            @csrf
                            <button type="submit" style="background:none;border:none;color:var(--blue-primary);font-size:12px;font-weight:700;cursor:pointer;padding:0;">
                                Tandai semua dibaca
                            </button>
                        </form>
                    @endif
                </div>

                <div style="max-height:340px;overflow-y:auto;">
                    @forelse ($recentNotifications as $n)
                        <form action="{{ route('notifications.read', $n->id) }}" method="POST" style="margin:0;">
                            @csrf
                            <button type="submit" style="display:flex;gap:10px;width:100%;text-align:left;padding:12px 16px;
                                border:none;border-bottom:1px solid var(--border);cursor:pointer;
                                background:{{ $n->read_at ? '#fff' : '#f3f6ff' }};">
                                <span style="width:8px;height:8px;border-radius:50%;background:var(--blue-primary);
                                    margin-top:6px;flex-shrink:0;{{ $n->read_at ? 'visibility:hidden;' : '' }}"></span>
                                <span style="flex:1;">
                                    <span style="display:block;font-size:13px;color:var(--text-dark);font-weight:{{ $n->read_at ? '500' : '700' }};line-height:1.4;">
                                        {{ $n->data['pesan'] ?? $n->data['judul'] ?? 'Notifikasi' }}
                                    </span>
                                    <span style="display:block;font-size:11.5px;color:var(--text-muted);margin-top:3px;">
                                        {{ $n->created_at->locale('id')->diffForHumans() }}
                                    </span>
                                </span>
                            </button>
                        </form>
                    @empty
                        <div style="padding:26px 16px;text-align:center;font-size:13px;color:var(--text-muted);">
                            Belum ada notifikasi.
                        </div>
                    @endforelse
                </div>

                @if (Route::has('notifications.index'))
                    <a href="{{ route('notifications.index') }}" style="display:block;text-align:center;padding:11px;
                        font-size:12.5px;color:var(--blue-primary);font-weight:700;text-decoration:none;border-top:1px solid var(--border);">
                        Lihat Semua Notifikasi
                    </a>
                @endif
            </div>
        </div>

        <a href="{{ Route::has('profile.show') ? route('profile.show') : '#' }}" class="topbar-user" style="text-decoration:none;color:inherit;">
            <div class="user-avatar small">{{ $displayInitials }}</div>
            <div style="line-height:1.2">
                <strong style="display:block">{{ $displayName }}</strong>
                <span style="font-size:11px;color:#8a93ad">{{ $displayRole }}</span>
            </div>
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="m6 9 6 6 6-6"/></svg>
        </a>
    </div>
</header>

<script>
(function () {
    const btn = document.getElementById('notifBtn');
    const panel = document.getElementById('notifPanel');
    if (!btn || !panel) return;

    btn.addEventListener('click', function (e) {
        e.stopPropagation();
        panel.style.display = panel.style.display === 'none' ? 'block' : 'none';
    });

    document.addEventListener('click', function (e) {
        if (!panel.contains(e.target) && e.target !== btn) {
            panel.style.display = 'none';
        }
    });
})();
</script>