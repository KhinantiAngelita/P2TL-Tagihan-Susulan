@php
    $namaBulanFilter = [1=>'Jan',2=>'Feb',3=>'Mar',4=>'Apr',5=>'Mei',6=>'Jun',7=>'Jul',8=>'Agu',9=>'Sep',10=>'Okt',11=>'Nov',12=>'Des'];
    $twTerpilih     = $filter['twTerpilih'] ?? [];
    $bulanTerpilih  = $filter['bulanTerpilih'] ?? [];
    $ulpTerpilih    = $filter['ulpTerpilih'] ?? [];
    $tglMulai       = $filter['tglMulai'] ?? null;
    $tglSelesai     = $filter['tglSelesai'] ?? null;

    $tampilkanTahunFilter = $tampilkanTahunFilter ?? false;
    $tampilkanJenisFilter = isset($jenisOptions);
    $tampilkanModeFilter  = isset($mode);

    // Opsi "Semua Tahun" di tab Tahun — CUMA buat Penetapan Berulang
    // (Trend selalu maksa 1 tahun terpilih, jadi flag ini default false
    // di sana dan opsi ini gak pernah nongol).
    $tampilkanSemuaTahunOpsi = $tampilkanSemuaTahunOpsi ?? false;

    // Tab Golongan Temuan & Non-Pelanggan — opt-in KHUSUS Penetapan
    // Berulang, dikontrol lewat flag eksplisit.
    $tampilkanGolonganFilter     = $tampilkanGolonganFilter ?? false;
    $tampilkanNonPelangganFilter = $tampilkanNonPelangganFilter ?? false;

    $tabFilter = [];
    if ($tampilkanTahunFilter) $tabFilter[] = 'tahun';
    if ($tampilkanJenisFilter) $tabFilter[] = 'jenis';
    $tabFilter[] = 'tw';
    $tabFilter[] = 'bulan';
    $tabFilter[] = 'tanggal';
    $tabFilter[] = 'ulp';
    if ($tampilkanGolonganFilter) $tabFilter[] = 'golongan';
    if ($tampilkanNonPelangganFilter) $tabFilter[] = 'nonpelang';
    if ($tampilkanModeFilter) $tabFilter[] = 'mode';

    $tabPertama = $tabFilter[0] ?? 'tw';
@endphp

@push('styles')
<style>
    .fp-trigger {
        display: inline-flex;
        align-items: center;
        gap: 8px;
        border: none;
        background: linear-gradient(135deg, #0b3d91, #2f6fdb);
        color: #fff;
        font-size: 12.5px;
        font-weight: 700;
        padding: 9px 16px;
        border-radius: 999px;
        cursor: pointer;
        box-shadow: 0 3px 10px rgba(11,61,145,.28);
        transition: opacity .15s, transform .15s;
    }
    .fp-trigger:hover { opacity: .92; transform: translateY(-1px); }
    .fp-trigger svg { width: 13px; height: 13px; flex-shrink: 0; }
    .fp-trigger .fp-count {
        background: rgba(255,255,255,.28);
        border-radius: 999px;
        padding: 2px 8px;
        font-size: 10.5px;
        font-weight: 800;
    }

    .fp-active-card {
        background: #f8f9fc;
        border: 1px solid #eef0f6;
        border-left: 3px solid #2f6fdb;
        border-radius: 12px;
        padding: 13px 16px;
        margin-top: 10px;
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 14px;
        flex-wrap: wrap;
        box-shadow: 0 2px 6px rgba(11,31,77,.04);
    }
    .fp-active-card-left {
        display: flex;
        align-items: center;
        gap: 10px;
        flex-wrap: wrap;
        min-width: 0;
    }
    .fp-active-card-icon {
        width: 28px; height: 28px; border-radius: 999px; flex-shrink: 0;
        display: flex; align-items: center; justify-content: center;
        background: #eaf0fb; color: #2f6fdb;
    }
    .fp-active-card-icon svg { width: 14px; height: 14px; }
    .fp-active-chips { display: flex; align-items: center; gap: 6px; flex-wrap: wrap; }
    .fp-active-chip {
        display: inline-flex; align-items: center;
        background: #fff; border: 1px solid #e5e9f5; color: #1b2559;
        font-size: 12px; font-weight: 600;
        padding: 4px 11px; border-radius: 999px; white-space: nowrap;
    }
    .fp-active-card-right {
        display: flex;
        gap: 6px;
        flex-shrink: 0;
    }
    .fp-btn-ubah {
        background: #fff;
        border: 1px solid #d0d7de;
        color: #24292f;
        font-size: 11.5px;
        font-weight: 600;
        padding: 5px 12px;
        border-radius: 6px;
        cursor: pointer;
        text-decoration: none;
    }
    .fp-btn-ubah:hover { background: #f3f4f6; }

    .fp-panel {
        display: none;
        background: #fff;
        border-radius: 14px;
        margin-top: 10px;
        margin-bottom: 14px;
        overflow: hidden;
        box-shadow: 0 4px 18px rgba(11,31,77,.08);
        border: 1px solid #e5e9f5;
    }

    .fp-panel-head {
        background: linear-gradient(135deg, #0b3d91, #2f6fdb);
        padding: 13px 20px;
        font-size: 13px;
        font-weight: 700;
        color: #fff;
        display: flex;
        align-items: center;
        justify-content: space-between;
    }
    .fp-panel-head-title { display: flex; align-items: center; gap: 7px; }
    .fp-panel-head svg { width: 14px; height: 14px; }
    .fp-close-btn { background: none; border: none; color: #fff; cursor: pointer; font-size: 16px; font-weight: bold; }

    .fp-body-layout {
        display: grid;
        grid-template-columns: 240px 1fr;
        min-height: 280px;
    }
    @media (max-width: 768px) {
        .fp-body-layout { grid-template-columns: 1fr; }
    }

    .fp-menu-sidebar {
        background: #f8f9fc;
        border-right: 1px solid #eef0f6;
        padding: 12px;
        display: flex;
        flex-direction: column;
        gap: 4px;
    }
    .fp-menu-item {
        display: flex;
        align-items: center;
        justify-content: space-between;
        padding: 10px 14px;
        border-radius: 8px;
        font-size: 12.5px;
        font-weight: 600;
        color: #4a5578;
        cursor: pointer;
        background: transparent;
        border: none;
        width: 100%;
        text-align: left;
        transition: all .15s;
    }
    .fp-menu-item:hover { background: #edf2f7; color: #1b2559; }
    .fp-menu-item.active { background: #0b3d91; color: #fff; }
    .fp-menu-badge {
        background: rgba(11,61,145,.15);
        color: #0b3d91;
        font-size: 10.5px;
        padding: 1px 6px;
        border-radius: 999px;
    }
    .fp-menu-item.active .fp-menu-badge { background: rgba(255,255,255,0.3); color: #fff; }

    .fp-content-area {
        padding: 20px;
        background: #fff;
    }
    .fp-section-content {
        display: none;
    }
    .fp-section-content.active {
        display: block;
    }

    .fp-group-label {
        font-size: 12px;
        font-weight: 700;
        color: #1b2559;
        text-transform: uppercase;
        letter-spacing: .03em;
        margin: 0 0 12px;
    }
    .fp-group-desc {
        font-size: 12px;
        color: #6b7690;
        margin: -8px 0 14px;
        line-height: 1.5;
    }

    .fp-pill-row { display: flex; flex-wrap: wrap; gap: 8px; }
    .fp-chip { position: relative; }
    .fp-chip input {
        position: absolute; opacity: 0; width: 100%; height: 100%; margin: 0; cursor: pointer;
    }
    .fp-chip span {
        display: inline-block;
        padding: 7px 14px;
        border: 1px solid #e5e9f5;
        border-radius: 999px;
        font-size: 12.5px;
        font-weight: 600;
        color: #4a5578;
        user-select: none;
        transition: all .15s;
    }
    .fp-chip input:checked + span {
        border-color: transparent;
        background: linear-gradient(135deg, #0b3d91, #2f6fdb);
        color: #fff;
    }

    .fp-tgl-field { margin-bottom: 12px; }
    .fp-tgl-field label { display: block; font-size: 11.5px; font-weight: 700; color: #6b7690; margin-bottom: 5px; }
    .fp-tgl-field input[type="date"] {
        width: 100%;
        border: 1px solid #e5e9f5;
        border-radius: 8px;
        padding: 9px 12px;
        font-size: 13px;
        box-sizing: border-box;
        color: #1b2559;
    }
    .fp-tgl-field input[type="date"]:focus { border-color: #2f6fdb; outline: none; }

    .fp-ulp-search {
        width: 100%;
        border: 1px solid #e5e9f5;
        border-radius: 8px;
        padding: 8px 11px;
        font-size: 12.5px;
        box-sizing: border-box;
        margin-bottom: 10px;
    }
    .fp-ulp-search:focus { border-color: #2f6fdb; outline: none; }
    .fp-ulp-list {
        display: flex;
        flex-direction: column;
        gap: 2px;
        max-height: 180px;
        overflow-y: auto;
        border: 1px solid #f1f3f9;
        border-radius: 8px;
        padding: 6px;
    }
    .fp-ulp-row {
        display: flex;
        align-items: center;
        gap: 8px;
        font-size: 13px;
        color: #4a5578;
        padding: 6px 8px;
        border-radius: 6px;
        cursor: pointer;
    }
    .fp-ulp-row:hover { background: #f8f9fc; }
    .fp-ulp-empty { font-size: 12px; color: #9aa4c2; }

    .fp-golongan-grid {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(110px, 1fr));
        gap: 6px;
        margin-bottom: 12px;
    }
    .fp-golongan-item {
        display: flex; align-items: center; gap: 7px;
        padding: 8px 10px; border-radius: 8px;
        font-size: 13px; color: #1b2559;
        border: 1px solid #e5e9f5;
        cursor: pointer;
        transition: background .15s, border-color .15s;
    }
    .fp-golongan-item:hover { background: #f4f7ff; }
    .fp-golongan-item input { width: 14px; height: 14px; accent-color: #0b3d91; cursor: pointer; }
    .fp-golongan-pilih-semua {
        background: none; border: none; color: #0b3d91;
        font-size: 12px; font-weight: 700; cursor: pointer;
        padding: 4px 0; margin-bottom: 8px;
    }
    .fp-golongan-pilih-semua:hover { text-decoration: underline; }
    .fp-golongan-empty { font-size: 12px; color: #9aa4c2; }

    .fp-radio-row {
        display: flex; flex-direction: column; gap: 8px;
    }
    .fp-radio-item {
        display: flex; align-items: flex-start; gap: 10px;
        padding: 10px 12px; border-radius: 9px;
        border: 1px solid #e5e9f5; cursor: pointer;
        transition: background .15s, border-color .15s;
    }
    .fp-radio-item:hover { background: #f8f9fc; }
    .fp-radio-item input { margin-top: 3px; width: 14px; height: 14px; accent-color: #0b3d91; cursor: pointer; flex-shrink: 0; }
    .fp-radio-item-text strong { display: block; font-size: 13px; color: #1b2559; margin-bottom: 2px; }
    .fp-radio-item-text span { font-size: 11.5px; color: #6b7690; line-height: 1.4; }

    .fp-footer {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 10px;
        padding: 13px 20px;
        background: #f8f9fc;
        border-top: 1px solid #eef0f6;
        flex-wrap: wrap;
    }
    .fp-footer-left { font-size: 11.5px; color: #9aa4c2; }
    .fp-footer-actions { display: flex; gap: 8px; }
    .fp-btn-apply, .fp-btn-reset {
        border-radius: 999px;
        padding: 8px 16px;
        font-size: 12.5px;
        font-weight: 700;
        cursor: pointer;
        text-decoration: none;
        display: inline-flex;
        align-items: center;
        border: none;
    }
    .fp-btn-apply { background: linear-gradient(135deg, #0b3d91, #2f6fdb); color: #fff; }
    .fp-btn-apply:hover { opacity: .9; }
    .fp-btn-reset { background: #eef0f6; color: #6b7690; }
    .fp-btn-reset:hover { background: #e4e7f0; }
</style>
@endpush

@php
    $resetQuery = ['tahun' => $tahunAktif];
    if ($tampilkanJenisFilter) $resetQuery['jenis'] = $jenisAktif ?? array_key_first($jenisOptions);
    if ($tampilkanModeFilter)  $resetQuery['mode']  = $mode;
    $resetHref = url()->current() . '?' . http_build_query($resetQuery);

    $golonganDianggapAktif = $tampilkanGolonganFilter
        && $golonganFilter
        && count($golonganFilter) < (isset($daftarGolongan) ? count($daftarGolongan) : 0);
    $nonPelangganDianggapAktif = $tampilkanNonPelangganFilter
        && ($modeNonPelanggan ?? 'sembunyikan') !== 'sembunyikan';

    $jumlahFilterAktif = count($twTerpilih) + count($bulanTerpilih) + count($ulpTerpilih)
        + ($tglMulai || $tglSelesai ? 1 : 0)
        + ($golonganDianggapAktif ? 1 : 0)
        + ($nonPelangganDianggapAktif ? 1 : 0);
@endphp

<div style="margin-bottom:18px;">
    <button type="button" class="fp-trigger" onclick="fpTogglePanel()">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="4" width="18" height="18" rx="2"/><path d="M16 2v4M8 2v4M3 10h18"/></svg>
        Filter Periode &amp; ULP
        @if ($jumlahFilterAktif)
            <span class="fp-count">{{ $jumlahFilterAktif }}</span>
        @endif
    </button>

    @php
        $tampilkanRingkasanSelalu = $tampilkanTahunFilter || $tampilkanJenisFilter || $tampilkanModeFilter || $tampilkanGolonganFilter || $tampilkanNonPelangganFilter;
    @endphp

    @if ($jumlahFilterAktif || $tampilkanRingkasanSelalu)
        <div class="fp-active-card">
            <div class="fp-active-card-left">
                <div class="fp-active-card-icon">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><path d="M12 16v-4M12 8h.01"/></svg>
                </div>
                <div class="fp-active-chips">
                    @foreach (explode(' · ', $filterInfoText) as $bagian)
                        <span class="fp-active-chip">{{ $bagian }}</span>
                    @endforeach
                </div>
            </div>
            <div class="fp-active-card-right">
                <a href="{{ $resetHref }}" class="fp-btn-ubah" style="color: #d93838;">Reset</a>
            </div>
        </div>
    @endif

    <div id="filter-panel" class="fp-panel">
        <div class="fp-panel-head">
            <div class="fp-panel-head-title">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="4" width="18" height="18" rx="2"/><path d="M16 2v4M8 2v4M3 10h18"/></svg>
                Pilih Kategori Filter
            </div>
            <button type="button" class="fp-close-btn" onclick="fpTogglePanel()">&times;</button>
        </div>

        <form method="GET" id="fp-form">
            @unless ($tampilkanTahunFilter)
                <input type="hidden" name="tahun" value="{{ $tahunAktif }}">
            @endunless
            @if ($tampilkanJenisFilter && !isset($jenisAktif))
                <input type="hidden" name="jenis" value="{{ request('jenis', array_key_first($jenisOptions)) }}">
            @endif
            @isset($mode)
                @unless ($tampilkanModeFilter)
                    <input type="hidden" name="mode" value="{{ $mode }}">
                @endunless
            @endisset

            @if (request()->has('jumlah'))
                <input type="hidden" name="jumlah" value="{{ request('jumlah') }}">
            @endif
            @if (request()->has('search'))
                <input type="hidden" name="search" value="{{ request('search') }}">
            @endif

            <div class="fp-body-layout">
                <div class="fp-menu-sidebar">
                    @if ($tampilkanTahunFilter)
                        <button type="button" class="fp-menu-item {{ $tabPertama === 'tahun' ? 'active' : '' }}" data-target="tahun">
                            <span>Tahun</span>
                        </button>
                    @endif

                    @if ($tampilkanJenisFilter)
                        <button type="button" class="fp-menu-item {{ $tabPertama === 'jenis' ? 'active' : '' }}" data-target="jenis">
                            <span>Jenis</span>
                        </button>
                    @endif

                    <button type="button" class="fp-menu-item {{ $tabPertama === 'tw' ? 'active' : '' }}" data-target="tw">
                        <span>Triwulan</span>
                        @if(count($twTerpilih)) <span class="fp-menu-badge">{{ count($twTerpilih) }}</span> @endif
                    </button>
                    <button type="button" class="fp-menu-item" data-target="bulan">
                        <span>Bulan</span>
                        @if(count($bulanTerpilih)) <span class="fp-menu-badge">{{ count($bulanTerpilih) }}</span> @endif
                    </button>
                    <button type="button" class="fp-menu-item" data-target="tanggal">
                        <span>Rentang Tanggal</span>
                        @if($tglMulai || $tglSelesai) <span class="fp-menu-badge">1</span> @endif
                    </button>
                    <button type="button" class="fp-menu-item" data-target="ulp">
                        <span>ULP</span>
                        @if(count($ulpTerpilih)) <span class="fp-menu-badge">{{ count($ulpTerpilih) }}</span> @endif
                    </button>

                    @if ($tampilkanGolonganFilter)
                        <button type="button" class="fp-menu-item" data-target="golongan">
                            <span>Golongan Temuan</span>
                            @if($golonganDianggapAktif) <span class="fp-menu-badge">{{ count($golonganFilter) }}</span> @endif
                        </button>
                    @endif

                    @if ($tampilkanNonPelangganFilter)
                        <button type="button" class="fp-menu-item" data-target="nonpelang">
                            <span>Non-Pelanggan</span>
                            @if($nonPelangganDianggapAktif) <span class="fp-menu-badge">1</span> @endif
                        </button>
                    @endif

                    @if ($tampilkanModeFilter)
                        <button type="button" class="fp-menu-item" data-target="mode">
                            <span>Tampilan</span>
                        </button>
                    @endif
                </div>

                <div class="fp-content-area">
                    @if ($tampilkanTahunFilter)
                        <div id="section-tahun" class="fp-section-content {{ $tabPertama === 'tahun' ? 'active' : '' }}">
                            <p class="fp-group-label">Pilih Tahun</p>
                            <div class="fp-pill-row">
                                @if ($tampilkanSemuaTahunOpsi)
                                    <label class="fp-chip">
                                        <input type="radio" name="tahun" value="" {{ ! $tahunAktif ? 'checked' : '' }}>
                                        <span>Semua Tahun</span>
                                    </label>
                                @endif
                                @forelse ($daftarTahun as $t)
                                    <label class="fp-chip">
                                        <input type="radio" name="tahun" value="{{ $t }}" {{ (string) $tahunAktif === (string) $t ? 'checked' : '' }}>
                                        <span>{{ $t }}</span>
                                    </label>
                                @empty
                                    <span class="fp-ulp-empty">Belum ada data tahun.</span>
                                @endforelse
                            </div>
                        </div>
                    @endif

                    @if ($tampilkanJenisFilter)
                        <div id="section-jenis" class="fp-section-content {{ $tabPertama === 'jenis' ? 'active' : '' }}">
                            <p class="fp-group-label">Pilih Jenis</p>
                            <div class="fp-pill-row">
                                @foreach ($jenisOptions as $key => $label)
                                    <label class="fp-chip">
                                        <input type="radio" name="jenis" value="{{ $key }}" {{ ($jenisAktif ?? request('jenis')) === $key ? 'checked' : '' }}>
                                        <span>{{ $label }}</span>
                                    </label>
                                @endforeach
                            </div>
                        </div>
                    @endif

                    <div id="section-tw" class="fp-section-content {{ $tabPertama === 'tw' ? 'active' : '' }}">
                        <p class="fp-group-label">Pilih Triwulan</p>
                        <div class="fp-pill-row">
                            @foreach (['I','II','III','IV'] as $i => $labelTw)
                                <label class="fp-chip">
                                    <input type="checkbox" name="tw[]" value="{{ $i + 1 }}" {{ in_array($i + 1, $twTerpilih) ? 'checked' : '' }}>
                                    <span>TW {{ $labelTw }}</span>
                                </label>
                            @endforeach
                        </div>
                    </div>

                    <div id="section-bulan" class="fp-section-content">
                        <p class="fp-group-label">Pilih Bulan</p>
                        <div class="fp-pill-row">
                            @foreach ($namaBulanFilter as $angka => $nama)
                                <label class="fp-chip">
                                    <input type="checkbox" name="bulan[]" value="{{ $angka }}" {{ in_array($angka, $bulanTerpilih) ? 'checked' : '' }}>
                                    <span>{{ $nama }}</span>
                                </label>
                            @endforeach
                        </div>
                    </div>

                    <div id="section-tanggal" class="fp-section-content">
                        <p class="fp-group-label">Pilih Rentang Tanggal</p>
                        <div class="fp-tgl-field">
                            <label>Dari tanggal</label>
                            <input type="date" name="tgl_mulai" value="{{ $tglMulai }}">
                        </div>
                        <div class="fp-tgl-field">
                            <label>Sampai tanggal</label>
                            <input type="date" name="tgl_selesai" value="{{ $tglSelesai }}">
                        </div>
                    </div>

                    <div id="section-ulp" class="fp-section-content">
                        <p class="fp-group-label">Pilih ULP</p>
                        @if (count($daftarUlp))
                            <input type="text" class="fp-ulp-search" placeholder="Cari nama ULP..." oninput="fpFilterUlp(this.value)">
                            <div class="fp-ulp-list" id="fp-ulp-list">
                                @foreach ($daftarUlp as $u)
                                    <label class="fp-ulp-row" data-nama="{{ strtolower($u['nama']) }}">
                                        <input type="checkbox" name="ulp[]" value="{{ $u['kode'] }}" {{ in_array($u['kode'], $ulpTerpilih) ? 'checked' : '' }}>
                                        {{ $u['nama'] }}
                                    </label>
                                @endforeach
                            </div>
                        @else
                            <span class="fp-ulp-empty">Belum ada data ULP.</span>
                        @endif
                    </div>

                    @if ($tampilkanGolonganFilter)
                        <div id="section-golongan" class="fp-section-content">
                            <p class="fp-group-label">Golongan Temuan</p>
                            <p class="fp-group-desc">
                                Kemunculan dengan golongan yang tidak dicentang tidak ikut
                                dihitung sebagai "muncul", baik di pivot maupun ringkasan.
                            </p>
                            @if (count($daftarGolongan))
                                <button type="button" class="fp-golongan-pilih-semua" onclick="fpToggleSemuaGolongan()">Pilih/Batalkan Semua</button>
                                <div class="fp-golongan-grid">
                                    @foreach ($daftarGolongan as $g)
                                        <label class="fp-golongan-item">
                                            <input type="checkbox" name="golongan[]" class="fp-golongan-checkbox" value="{{ $g }}" {{ (! $golonganFilter || in_array($g, $golonganFilter, true)) ? 'checked' : '' }}>
                                            <span>{{ $g }}</span>
                                        </label>
                                    @endforeach
                                </div>
                            @else
                                <span class="fp-golongan-empty">Belum ada data golongan.</span>
                            @endif
                        </div>
                    @endif

                    @if ($tampilkanNonPelangganFilter)
                        <div id="section-nonpelang" class="fp-section-content">
                            <p class="fp-group-label">Perlakuan Non-Pelanggan</p>
                            <p class="fp-group-desc">
                                IDPEL "NONPELANG" dipakai bersama oleh banyak temuan berbeda
                                (bukan pelanggan terdaftar).
                            </p>
                            <div class="fp-radio-row">
                                <label class="fp-radio-item">
                                    <input type="radio" name="nonpelang" value="sembunyikan" {{ ($modeNonPelanggan ?? 'sembunyikan') === 'sembunyikan' ? 'checked' : '' }}>
                                    <span class="fp-radio-item-text">
                                        <strong>Sembunyikan Non-Pelanggan</strong>
                                        <span>Temuan NONPELANG tidak ikut dihitung sama sekali (default).</span>
                                    </span>
                                </label>
                                <label class="fp-radio-item">
                                    <input type="radio" name="nonpelang" value="sertakan" {{ ($modeNonPelanggan ?? '') === 'sertakan' ? 'checked' : '' }}>
                                    <span class="fp-radio-item-text">
                                        <strong>Sertakan Non-Pelanggan</strong>
                                        <span>Ikut dihitung, dikelompokkan per Nama (bukan IDPEL).</span>
                                    </span>
                                </label>
                                <label class="fp-radio-item">
                                    <input type="radio" name="nonpelang" value="hanya" {{ ($modeNonPelanggan ?? '') === 'hanya' ? 'checked' : '' }}>
                                    <span class="fp-radio-item-text">
                                        <strong>Hanya Non-Pelanggan</strong>
                                        <span>Pelanggan biasa (IDPEL asli) disembunyikan.</span>
                                    </span>
                                </label>
                            </div>
                        </div>
                    @endif

                    @if ($tampilkanModeFilter)
                        <div id="section-mode" class="fp-section-content">
                            <p class="fp-group-label">Tampilan Grafik</p>
                            <div class="fp-pill-row">
                                <label class="fp-chip">
                                    <input type="radio" name="mode" value="bulanan" {{ $mode === 'bulanan' ? 'checked' : '' }}>
                                    <span>Bulanan</span>
                                </label>
                                <label class="fp-chip">
                                    <input type="radio" name="mode" value="kumulatif" {{ $mode === 'kumulatif' ? 'checked' : '' }}>
                                    <span>Komulatif</span>
                                </label>
                            </div>
                        </div>
                    @endif
                </div>
            </div>

            <div class="fp-footer">
                <span class="fp-footer-left">{{ $jumlahFilterAktif ? $jumlahFilterAktif . ' filter aktif saat ini' : 'Belum ada filter dipilih' }}</span>
                <div class="fp-footer-actions">
                    <a href="{{ $resetHref }}" class="fp-btn-reset">Reset Semua</a>
                    <button type="submit" class="fp-btn-apply">Terapkan Filter</button>
                </div>
            </div>
        </form>
    </div>
</div>

<div id="filter-info-text" style="display:none">{{ $filterInfoText }}</div>

<script>
function fpTogglePanel() {
    var panel = document.getElementById('filter-panel');
    if (!panel) return;
    panel.style.display = panel.style.display === 'block' ? 'none' : 'block';
}

document.querySelectorAll('.fp-menu-item').forEach(function (btn) {
    btn.addEventListener('click', function () {
        var target = this.dataset.target;
        document.querySelectorAll('.fp-section-content').forEach(function (sec) {
            sec.classList.remove('active');
        });
        document.querySelectorAll('.fp-menu-item').forEach(function (b) {
            b.classList.remove('active');
        });
        var section = document.getElementById('section-' + target);
        if (section) section.classList.add('active');
        this.classList.add('active');
    });
});

function fpFilterUlp(kata) {
    kata = kata.toLowerCase().trim();
    document.querySelectorAll('#fp-ulp-list .fp-ulp-row').forEach(function (row) {
        row.style.display = row.dataset.nama.indexOf(kata) !== -1 ? '' : 'none';
    });
}

function fpToggleSemuaGolongan() {
    var boxes = document.querySelectorAll('.fp-golongan-checkbox');
    var adaYangKosong = Array.prototype.some.call(boxes, function (b) { return !b.checked; });
    boxes.forEach(function (b) { b.checked = adaYangKosong; });
}
(function () {
    var SCROLL_KEY = 'scrollpos-' + window.location.pathname;

    if ('scrollRestoration' in history) {
        history.scrollRestoration = 'manual';
    }

    function simpanScroll() {
        try { sessionStorage.setItem(SCROLL_KEY, String(window.scrollY)); } catch (e) {}
    }

    // Delegated: nyakup SEMUA <a href> di halaman (tab filter, tab
    // Komulatif/Bulanan, tab Trend kWh/Rp TS/Pencapaian) + submit form
    // filter — bukan cuma tombol reset tertentu.
    document.addEventListener('click', function (e) {
        var a = e.target.closest('a[href]');
        if (a && a.origin === window.location.origin) simpanScroll();
    }, true);

    var fpForm = document.getElementById('fp-form');
    if (fpForm) fpForm.addEventListener('submit', simpanScroll, true);

    window.addEventListener('load', function () {
        var saved;
        try { saved = sessionStorage.getItem(SCROLL_KEY); } catch (e) { saved = null; }
        if (saved === null) return;

        var y = parseInt(saved, 10) || 0;
        requestAnimationFrame(function () {
            requestAnimationFrame(function () {
                window.scrollTo(0, y);
                try { sessionStorage.removeItem(SCROLL_KEY); } catch (e) {}
            });
        });
    });
})();

</script>