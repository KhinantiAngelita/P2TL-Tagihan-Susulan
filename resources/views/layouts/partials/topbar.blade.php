{{--
    Topbar: breadcrumb otomatis (bisa ditimpa lewat @section('breadcrumb') di masing-masing view),
    tanggal hari ini berbahasa Indonesia + info triwulan, notifikasi, dan user dropdown.
--}}
@php
    $hariId  = ['Sunday'=>'Minggu','Monday'=>'Senin','Tuesday'=>'Selasa','Wednesday'=>'Rabu','Thursday'=>'Kamis','Friday'=>'Jumat','Saturday'=>'Sabtu'];
    $bulanId = ['January'=>'Januari','February'=>'Februari','March'=>'Maret','April'=>'April','May'=>'Mei','June'=>'Juni','July'=>'Juli','August'=>'Agustus','September'=>'September','October'=>'Oktober','November'=>'November','December'=>'Desember'];
    $now = \Carbon\Carbon::now();
    $tanggalId = $hariId[$now->format('l')].', '.$now->format('d').' '.$bulanId[$now->format('F')].' '.$now->format('Y');
    $triwulan = 'Triwulan '.['I','I','I','II','II','II','III','III','III','IV','IV','IV'][$now->month - 1];
@endphp
<header class="topbar">
    <div class="topbar-breadcrumb">
        @hasSection('breadcrumb')
            @yield('breadcrumb')
        @else
            <a href="{{ route('dashboard') }}">Beranda</a>
            <span class="sep">›</span>
            <strong>@yield('title', 'Dashboard')</strong>
        @endif
    </div>

    <div class="topbar-right">
        <div class="topbar-date">
            <strong>{{ $tanggalId }}</strong>
            <span>{{ $triwulan }} · {{ $now->year }}</span>
        </div>

        <button class="topbar-icon-btn" title="Notifikasi">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M18 8a6 6 0 0 0-12 0c0 7-3 9-3 9h18s-3-2-3-9"/><path d="M13.73 21a2 2 0 0 1-3.46 0"/></svg>
            <span class="topbar-icon-dot"></span>
        </button>

        <div class="topbar-user">
            <div class="user-avatar small">{{ $userInitials ?? 'AR' }}</div>
            <strong>{{ $userName ?? 'Ahmad Rizki' }}</strong>
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="m6 9 6 6 6-6"/></svg>
        </div>
    </div>
</header>