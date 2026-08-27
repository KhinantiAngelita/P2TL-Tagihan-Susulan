@extends('layouts.app')
@section('title', $metric === 'kwh' ? 'Trend kWh' : 'Trend Rp TS')

@section('breadcrumb')
    <a href="{{ route('dashboard') }}">Beranda</a>
    <span class="sep">›</span>
    <strong>{{ $metric === 'kwh' ? 'Trend kWh' : 'Trend Rp TS' }}</strong>
@endsection

@push('styles')
<style>
    .trend-tabs {
        display: flex; background: #f0f2f8; border-radius: 10px; padding: 4px; gap: 2px; margin-bottom: 18px;
        max-width: 100%; overflow-x: auto; -webkit-overflow-scrolling: touch;
    }
    .trend-tabs a {
        padding: 9px 18px; font-size: 13.5px; font-weight: 700; border-radius: 8px;
        text-decoration: none; color: var(--text-muted); white-space: nowrap; flex-shrink: 0;
    }
    .trend-tabs a.active { background: #fff; color: var(--blue-primary); box-shadow: 0 1px 3px rgba(20,30,80,.12); }

    /* ===== Header halaman — dikasih clamp biar judul gak makan tempat
       kebanyakan di layar sempit tapi tetap gede di desktop. ===== */
    .trend-page-title { font-size: clamp(18px, 4.2vw, 22px); margin: 0 0 4px; }

    /* ===== Filter card (gradient, kayak versi awal) ===== */
    .trend-filter-card {
        display: flex; align-items: center; justify-content: space-between;
        flex-wrap: wrap; gap: 14px;
        padding: 16px 22px; margin-bottom: 18px;
        background: linear-gradient(90deg, #003b94, #0f6bd9);
        border-color: transparent;
    }
    .trend-filter-left { display: flex; align-items: center; gap: 14px; flex-wrap: wrap; min-width: 0; }
    .trend-filter-left > div:last-child { min-width: 0; }
    .trend-filter-left > div:last-child p { overflow-wrap: break-word; }
    .trend-filter-form { display: flex; align-items: center; gap: 10px; flex-wrap: wrap; }
    .trend-filter-form select {
        padding: 9px 14px; border-radius: 9px; border: 1px solid rgba(255,255,255,.4);
        background: rgba(255,255,255,.95); font-size: 13.5px; font-weight: 600; color: #1b2559;
        min-width: 140px;
    }
    .trend-mode-toggle { display: inline-flex; background: rgba(255,255,255,.15); border-radius: 9px; padding: 3px; gap: 2px; }
    .trend-mode-toggle a {
        padding: 8px 16px; font-size: 13px; font-weight: 700; border-radius: 7px;
        text-decoration: none; color: #fff;
    }
    .trend-mode-toggle a.active { background: #ffce3a; color: #071233; }

    .trend-filter-left .info-icon {
        display: flex;
        align-items: center;
        justify-content: center;
        border-radius: 10px;
        flex-shrink: 0;
    }

    .chart-badge {
        display: inline-flex;
        align-items: center;
        padding: 6px 14px;
        border-radius: 20px;
        font-size: 12.5px;
        font-weight: 700;
        white-space: nowrap;
        max-width: 100%;
        overflow: hidden;
        text-overflow: ellipsis;
    }

    /* ===== Chart card ===== */
    .trend-chart-card { padding: 22px; margin-bottom: 20px; }
    .trend-chart-head { display: flex; align-items: flex-start; justify-content: space-between; flex-wrap: wrap; gap: 8px; margin-bottom: 6px; }
    .trend-chart-head h3 { margin: 0 0 2px; font-size: 16px; color: #1b2559; }
    .trend-chart-head p { margin: 0; font-size: 12.5px; color: #6b7690; }

    /* Catatan kecil di bawah judul chart, jelasin arti label yang
       digambar langsung di atas tiap bar (% pencapaian & jumlah
       pelanggan per bulan) — biar gak butuh legend terpisah. */
    .trend-chart-note {
        display: flex; align-items: center; gap: 6px;
        font-size: 11.5px; color: #9aa4c2; margin: 0 0 14px;
    }
    .trend-chart-note svg { width: 13px; height: 13px; flex-shrink: 0; }

    /* Tinggi ditambah dikit dari sebelumnya (320 -> 350) buat kasih
       ruang label % & jumlah pelanggan di atas tiap bar biar gak
       kepotong/nabrak sama bagian atas card. */
    .trend-chart-canvas-wrap { position: relative; height: 350px; width: 100%; }

    /* ===== Tabel horizontal (bulan = kolom, Target/Realisasi/%/Pelanggan
       = baris) ===== */
    .trend-hz-table-wrap { padding: 0; overflow: hidden; }
    .trend-hz-table-head {
        display: flex; align-items: center; gap: 12px;
        padding: 18px 22px; border-bottom: 1px solid var(--border);
        background: #fafbfe;
    }
    .trend-hz-table-head .icon {
        width: 34px; height: 34px; border-radius: 9px; flex-shrink: 0;
        display: flex; align-items: center; justify-content: center;
        background: #eaf0fb; color: #0b3d91;
    }
    .trend-hz-table-head .icon svg { width: 17px; height: 17px; }
    .trend-hz-table-head strong { font-size: 14.5px; color: #1b2559; display: block; }
    .trend-hz-table-head span { font-size: 12px; color: #9aa4c2; }

    .trend-hz-table { width: 100%; border-collapse: collapse; }
    .trend-hz-table th, .trend-hz-table td {
        padding: 13px 16px; font-size: 13px; text-align: center; white-space: nowrap;
        border-bottom: 1px solid var(--border); border-right: 1px solid var(--border);
        transition: background .12s;
    }
    .trend-hz-table th:first-child, .trend-hz-table td:first-child {
        text-align: left; font-weight: 700; color: var(--text-dark);
        background: #fafbfe; position: sticky; left: 0; z-index: 1;
        display: flex; align-items: center; gap: 8px;
    }
    .trend-hz-table th:first-child .row-icon,
    .trend-hz-table td:first-child .row-icon {
        width: 22px; height: 22px; border-radius: 6px; flex-shrink: 0;
        display: inline-flex; align-items: center; justify-content: center;
    }
    .trend-hz-table td:first-child .row-icon svg { width: 12px; height: 12px; }
    .row-icon.tone-target    { background: #fde6f0; color: #c0246b; }
    .row-icon.tone-realisasi { background: #e5f7ec; color: #1a9c4a; }
    .row-icon.tone-persen    { background: #fff6e0; color: #b8860b; }
    .row-icon.tone-pelanggan { background: #eaf0fb; color: #0f6bd9; }

    .trend-hz-table thead th {
        color: var(--text-muted); font-weight: 700; font-size: 11.5px;
        text-transform: uppercase; letter-spacing: .03em; background: #fafbfe;
    }
    .trend-hz-table thead th:first-child {
        text-align: left;
        color: #c3c9dc;
        font-weight: 600;
        text-transform: none;
        letter-spacing: normal;
        padding-left: 16px;
        display: table-cell;
    }
    .trend-hz-table thead th:not(:first-child) { transition: background .12s; cursor: default; }
    .trend-hz-table thead th:not(:first-child):hover,
    .trend-hz-table tbody tr td:not(:first-child):hover {
        background: #f4f7ff;
    }
    .trend-hz-table tbody tr:last-child td { border-bottom: none; }
    .trend-hz-table tbody td { color: var(--text-dark); font-weight: 500; }

    /* baris Target */
    .trend-hz-table tbody tr.row-target td:not(:first-child) { color: #c0246b; }
    /* baris Realisasi */
    .trend-hz-table tbody tr.row-realisasi td:not(:first-child) {
        color: #1a9c4a; font-weight: 700;
    }
    /* baris Jumlah Pelanggan */
    .trend-hz-table tbody tr.row-pelanggan td:not(:first-child) { color: #0f6bd9; font-weight: 600; }
    /* baris % Pencapaian — background soft di kolom (bukan cuma teks),
       3 tone: hijau (tercapai), oren (mendekati), merah (jauh dari
       target). Background & teks dua-duanya ngikutin tone yang sama. */
    .persen-text { font-weight: 700; }
    td.tone-hijau { background: #eafaf0; }
    td.tone-hijau .persen-text { color: #16803c; }
    td.tone-oren { background: #fff4e5; }
    td.tone-oren .persen-text { color: #c47a06; }
    td.tone-merah { background: #fdecec; }
    td.tone-merah .persen-text { color: #c62828; }
    td.tone-abu { background: #f4f5f9; }
    td.tone-abu .persen-text { color: #9aa4c2; }

    /* ===== Card "Selisih dari Target" — sengaja dibikin ringkas =====
       Detail lengkap (persen per bulan, ranking bulan tertinggi/
       terendah, badge ijo/merah) udah ada di tab "Presentase
       Pencapaian". Di sini cukup kasih tau selisih nominalnya aja +
       link ke sana, biar gak dobel nampilin kesimpulan yang sama
       dengan cara berbeda. */
    .dash-stat-card.tone-pink::before { background: #d81b60; }
    .tone-pink .dash-stat-icon { background: #fde6f0; color: #d81b60; }

    /* Tone netral buat card "Selisih dari Target" pas target belum
       diisi sama sekali — sengaja dibedain dari tone-pink/tone-green
       biar gak keliatan kayak ada angka selisih beneran padahal
       gak ada target buat dibandingin. */
    .dash-stat-card.tone-abu::before { background: #9aa4c2; }
    .tone-abu .dash-stat-icon { background: #eef0f6; color: #6b7690; }
    .dash-stat-detail-link {
        color: inherit; text-decoration: none; font-weight: 700;
        display: inline-flex; align-items: center; gap: 4px;
    }
    /* PERBAIKAN: selector ini sebelumnya kepotong jadi ":hove" (tanpa
       "r") dan nyambung langsung tanpa "{ }" ke selector .copyable-card
       di bawahnya — browser baca ini sebagai SATU selector gabungan
       yang invalid (".dash-stat-detail-link:hove .copyable-card"), jadi
       seluruh rule di-skip diam-diam oleh browser, dan
       ".copyable-card { position: relative; }" gak pernah kepasang.
       Sekarang dipisah jadi 2 rule yang valid. */
    .dash-stat-detail-link:hover { text-decoration: underline; }

    /* ===== Tombol "Salin Gambar" di tiap card ===== */
    .copyable-card { position: relative; }

    .card-copy-btn {
        display: inline-flex;
        align-items: center;
        gap: 6px;
        padding: 7px 14px;
        border: 1px solid var(--border);
        border-radius: 8px;
        background: #fff;
        color: #1b2559;
        font-size: 12.5px;
        font-weight: 600;
        cursor: pointer;
        white-space: nowrap;
        flex-shrink: 0;
        transition: background .15s, border-color .15s, color .15s;
    }
    .card-copy-btn svg { width: 14px; height: 14px; flex-shrink: 0; }

    .card-copy-btn:hover { background: #f4f6fb; border-color: #c7cede; }
    .card-copy-btn:disabled { opacity: .6; cursor: default; }

    @media (max-width: 640px) {
        .card-copy-btn { font-size: 12px; padding: 6px 12px; }
        .card-copy-btn-label { display: none; }
    }

    /* Dikunci 2 kolom mulai tablet biar kartunya gak terlalu lebar/gepeng
       di lebar-lebar "aneh" (samain sama tab Presentase Pencapaian). */
    @media (max-width: 900px) {
        .dash-stats { grid-template-columns: repeat(2, 1fr); }
    }

    @media (max-width: 640px) {
        .trend-filter-card { padding: 14px 16px; flex-direction: column; align-items: stretch; }
        .trend-filter-left { width: 100%; }
        .trend-filter-right { width: 100%; }
        .trend-filter-form { width: 100%; }
        .trend-filter-form select { flex: 1; min-width: 0; }
        .trend-mode-toggle { width: 100%; justify-content: center; }
        .trend-mode-toggle a { flex: 1; text-align: center; }
        .trend-chart-canvas-wrap { height: 300px; }
        .trend-chart-head { flex-direction: column; align-items: flex-start; }
        .chart-badge { align-self: flex-start; }

        /* Tabel horizontal (Target/Realisasi/%/Pelanggan per bulan) —
           tetap discroll ke samping di HP (udah dibungkus .table-scroll),
           tapi padding & font dikecilin dikit biar lebih banyak kolom
           bulan yang keliatan sekali swipe. */
        .trend-hz-table th, .trend-hz-table td { padding: 10px 12px; font-size: 12px; }
        .trend-hz-table-head { padding: 14px 16px; }
    }

    @media (max-width: 420px) {
        .dash-stats { grid-template-columns: 1fr; }
        .trend-filter-form { flex-direction: column; align-items: stretch; }
        .trend-filter-form select { width: 100%; }
        .trend-chart-canvas-wrap { height: 260px; }
        .trend-hz-table th, .trend-hz-table td { padding: 8px 10px; font-size: 11.5px; }
    }
</style>
@endpush

@php
    // Fallback sementara: kalau controller belum kirim $targetData,
    // isi 0 semua biar chart & tabel tetap render tanpa error.
    $targetData = $targetData ?? array_fill(0, count($labels), 0);

    // Fallback: kalau controller belum kirim $jumlahPelangganData (array
    // jumlah pelanggan unik per bulan, urutan sama kayak $labels), isi
    // null semua biar tabel/chart tetap render dengan tampilan "-"
    // daripada error undefined variable.
    $jumlahPelangganData = $jumlahPelangganData ?? array_fill(0, count($labels), null);

    // Total target tahun berjalan, disesuaikan sama mode tampilan:
    // - mode kumulatif -> $targetData udah berupa angka akumulasi, jadi
    //   total-nya tinggal ambil elemen terakhir.
    // - mode bulanan -> $targetData angka per bulan, jadi total-nya
    //   dijumlah semua.
    $totalTargetTahunIni = $mode === 'kumulatif'
        ? (float) (end($targetData) ?: 0)
        : (float) array_sum($targetData);

    // Selisih realisasi vs target: positif berarti realisasi MELEBIHI
    // target (kurang bagus buat metrik susut/TS), negatif berarti masih
    // di bawah target.
    $selisihTahunIni = $totalTahunIni - $totalTargetTahunIni;

    // % Pencapaian PER BULAN — ini yang ditampilkan langsung di atas
    // tiap bar chart & di baris tabel, BUKAN angka total setahun.
    // Dihitung dari $data & $targetData mentah (nilai per bulan asli,
    // sebelum ikut mode kumulatif), makanya dihitung ulang di sini pakai
    // pembagian aktual-per-bulan / target-per-bulan yang konsisten
    // walau mode tampilan lagi "Komulatif".
    $persenPerBulanMentah = collect($labels)->map(function ($label, $i) use ($tabelBulanan, $targetData, $mode, $data) {
        // Ambil nilai & target PER BULAN (bukan versi kumulatif), biar
        // %-nya tetap benar walau toggle mode di URL lagi "kumulatif".
        $nilaiBulanIni  = $tabelBulanan[$i]['nilai'] ?? ($mode === 'kumulatif' ? null : ($data[$i] ?? 0));
        $targetBulanIni = $mode === 'kumulatif'
            ? ($i === 0 ? ($targetData[0] ?? 0) : ($targetData[$i] - $targetData[$i - 1]))
            : ($targetData[$i] ?? 0);

        if ($nilaiBulanIni === null) {
            return null;
        }

        return $targetBulanIni > 0 ? round($nilaiBulanIni / $targetBulanIni * 100, 1) : null;
    });
@endphp

@section('content')

<div style="display:flex;align-items:flex-start;justify-content:space-between;margin-bottom:16px;flex-wrap:wrap;gap:12px;">
    <div>
        <h2 class="trend-page-title">{{ $metric === 'kwh' ? 'Trend Pemakaian kWh' : 'Trend Rp TS' }}</h2>
        <p style="color:#6b7690;margin:0;font-size:14px;">
            {{ $metric === 'kwh' ? 'Tren pemakaian kWh tagihan susulan per bulan.' : 'Tren nilai Rp TS (tagihan susulan) per bulan.' }}
        </p>
    </div>
</div>

{{--
    Urutan tab: "Presentase Pencapaian" ditaro paling depan karena itu
    yang paling sering dicek duluan ("udah sesuai target belum") —
    baru kalau perlu drill-down ke nilai aktualnya, pindah ke Trend
    kWh / Rp TS. Tab ini sebelumnya malah gak ada sama sekali di
    halaman index, sekarang ditambahin biar bisa pindah tab tanpa
    balik ke menu dulu.
--}}
<div class="trend-tabs">
    <a href="{{ route('trend.pencapaian', request()->only('tahun', 'ulp')) }}">Presentase Pencapaian</a>
    <a href="{{ route('trend.kwh', request()->only('tahun', 'ulp', 'mode')) }}" class="{{ $metric === 'kwh' ? 'active' : '' }}">Trend kWh</a>
    <a href="{{ route('trend.ts', request()->only('tahun', 'ulp', 'mode')) }}" class="{{ $metric === 'ts' ? 'active' : '' }}">Trend Rp TS</a>
</div>

<div class="card trend-filter-card">
    <div class="trend-filter-left">
        <div class="info-icon" style="width:34px;height:34px;background:rgba(255,255,255,.15);color:#ffce3a;">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="width:16px;height:16px;"><path d="M3 3v18h18"/><path d="M7 15l4-5 3 3 5-7"/></svg>
        </div>
        <div>
            <strong style="font-size:14px;color:#fff;">Filter Trend</strong>
            <p style="margin:2px 0 0;font-size:12px;color:rgba(255,255,255,.85);">
                Tahun {{ $tahunAktif ?: '-' }} &middot; {{ $ulpAktif === 'semua' ? 'Semua ULP' : ($daftarUlp->firstWhere('kode', $ulpAktif)['nama'] ?? $ulpAktif) }}
            </p>
        </div>
    </div>

    <div class="trend-filter-right">
        <form method="GET" class="trend-filter-form">
            <input type="hidden" name="mode" value="{{ $mode }}">

            <select name="tahun" onchange="this.form.submit()">
                @forelse ($daftarTahun as $t)
                    <option value="{{ $t }}" {{ (int) $tahunAktif === (int) $t ? 'selected' : '' }}>{{ $t }}</option>
                @empty
                    <option value="">Belum ada data</option>
                @endforelse
            </select>

            <select name="ulp" onchange="this.form.submit()">
                <option value="semua" {{ (string) $ulpAktif === 'semua' ? 'selected' : '' }}>Semua ULP</option>
                @foreach ($daftarUlp as $u)
                    <option value="{{ $u['kode'] }}" {{ (string) $ulpAktif === (string) $u['kode'] ? 'selected' : '' }}>{{ $u['kode'] }} - {{ $u['nama'] }}</option>
                @endforeach
            </select>
        </form>

        <div class="trend-mode-toggle">
            <a href="{{ request()->fullUrlWithQuery(['mode' => 'bulanan']) }}" class="{{ $mode === 'bulanan' ? 'active' : '' }}">Bulan</a>
            <a href="{{ request()->fullUrlWithQuery(['mode' => 'kumulatif']) }}" class="{{ $mode === 'kumulatif' ? 'active' : '' }}">Komulatif</a>
        </div>
    </div>
</div>

<div class="dash-stats">
    <div class="dash-stat-card tone-blue copyable-card" data-copy-name="total-{{ $tahunAktif }}">
        <div class="dash-stat-top">
            <div class="dash-stat-icon">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M3 3v18h18"/><path d="M7 15l4-5 3 3 5-7"/></svg>
            </div>
            <h3>Total {{ $tahunAktif ?: '-' }}</h3>
        </div>
        <div class="dash-stat-value">
            {{ $metric === 'kwh' ? number_format($totalTahunIni, 0, ',', '.') . ' KWH' : 'Rp ' . number_format($totalTahunIni, 0, ',', '.') }}
        </div>
        <div class="dash-stat-sub">Sesuai filter tahun &amp; ULP terpilih</div>
    </div>

    <div class="dash-stat-card tone-yellow copyable-card" data-copy-name="rata-rata-bulan">
        <div class="dash-stat-top">
            <div class="dash-stat-icon">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="4" width="18" height="18" rx="2"/><path d="M16 2v4M8 2v4M3 10h18"/></svg>
            </div>
            <h3>Rata-rata / Bulan</h3>
        </div>
        <div class="dash-stat-value">
            {{ $metric === 'kwh' ? number_format($rataRataBulanan, 0, ',', '.') . ' KWH' : 'Rp ' . number_format($rataRataBulanan, 0, ',', '.') }}
        </div>
        <div class="dash-stat-sub">Rata-rata dari bulan yang ada datanya</div>
    </div>

    <div class="dash-stat-card tone-green copyable-card" data-copy-name="bulan-tertinggi">
        <div class="dash-stat-top">
            <div class="dash-stat-icon">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 2 3 7v6c0 5 4 8.5 9 9 5-.5 9-4 9-9V7l-9-5Z"/></svg>
            </div>
            <h3>Bulan Tertinggi</h3>
        </div>
        <div class="dash-stat-value">{{ $bulanTertinggiLabel ?? '-' }}</div>
        <div class="dash-stat-sub">
            {{ $bulanTertinggiLabel ? ($metric === 'kwh' ? number_format($bulanTertinggiNilai, 0, ',', '.') . ' KWH' : 'Rp ' . number_format($bulanTertinggiNilai, 0, ',', '.')) : 'Belum ada data' }}
        </div>
    </div>

    <div class="dash-stat-card {{ $totalTargetTahunIni == 0 ? 'tone-abu' : ($selisihTahunIni > 0 ? 'tone-pink' : 'tone-green') }} copyable-card" data-copy-name="selisih-target">
        <div class="dash-stat-top">
            <div class="dash-stat-icon">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M8 3v3m8-3v3M4 21V8a2 2 0 0 1 2-2h12a2 2 0 0 1 2 2v13M4 21h16M9 12h6M9 16h6"/></svg>
            </div>
            <h3>Selisih dari Target</h3>
        </div>
        @if ($totalTargetTahunIni == 0)
            <div class="dash-stat-value" style="color:#6b7690;">Target belum diisi</div>
            <div class="dash-stat-sub">Isi target dulu di Edit Target biar selisihnya bisa dihitung</div>
        @else
            <div class="dash-stat-value" style="color:{{ $selisihTahunIni > 0 ? '#d81b60' : '#1a9c4a' }};word-break:break-word;">
                {{ $selisihTahunIni > 0 ? '+' : '' }}{{ $metric === 'kwh' ? number_format($selisihTahunIni, 0, ',', '.') . ' KWH' : 'Rp ' . number_format($selisihTahunIni, 0, ',', '.') }}
            </div>
            <div class="dash-stat-sub">
                <a href="{{ route('trend.pencapaian', request()->only('tahun', 'ulp')) }}" class="dash-stat-detail-link">
                    Lihat detail pencapaian
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M5 12h14"/><path d="m12 5 7 7-7 7"/></svg>
                </a>
            </div>
        @endif
    </div>
</div>

<div class="card trend-chart-card copyable-card">
    <div class="trend-chart-head">
        <div>
            <h3>{{ $mode === 'kumulatif' ? 'Trend Komulatif' : 'Trend Bulanan' }} — {{ $metric === 'kwh' ? 'kWh' : 'Rp TS' }}</h3>
            <p>{{ $mode === 'kumulatif' ? 'Akumulasi nilai dari Januari sampai bulan berjalan' : 'Nilai per bulan (tidak diakumulasi)' }} &mdash; Tahun {{ $tahunAktif ?: '-' }}</p>
        </div>
        <div style="display:flex;flex-direction:column;align-items:flex-end;gap:8px;">
            <button type="button" class="card-copy-btn" onclick="salinTabelGambar('capture-trend-chart', this, 'Trend {{ $metric === 'kwh' ? 'kWh' : 'Rp TS' }} — {{ $mode === 'kumulatif' ? 'Komulatif' : 'Bulanan' }}')">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="9" y="9" width="13" height="13" rx="2"/><path d="M5 15H4a2 2 0 0 1-2-2V4a2 2 0 0 1 2-2h9a2 2 0 0 1 2 2v1"/></svg>
                <span class="card-copy-btn-label">Salin Gambar</span>
            </button>
            <span class="chart-badge" style="background:#eaf0fb;color:#0b3d91;">{{ $ulpAktif === 'semua' ? 'Semua ULP' : (($daftarUlp->firstWhere('kode', $ulpAktif)['nama'] ?? null) ? $ulpAktif . ' - ' . $daftarUlp->firstWhere('kode', $ulpAktif)['nama'] : $ulpAktif) }}</span>
        </div>
    </div>

    <div id="capture-trend-chart">
        {{-- Keterangan singkat: label di atas tiap bar = % pencapaian &
             jumlah pelanggan BULAN ITU, digambar langsung di chart. --}}
        <p class="trend-chart-note">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><path d="M12 16v-4"/><path d="M12 8h.01"/></svg>
            Angka di atas tiap batang = % pencapaian &amp; jumlah pelanggan bulan itu.
        </p>

        <div class="trend-chart-canvas-wrap">
            <canvas id="trendChart"></canvas>
        </div>
    </div>
</div>

<div class="card trend-hz-table-wrap copyable-card">
    <div class="trend-hz-table-head">
        <div class="icon">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="3" width="18" height="18" rx="2"/><path d="M3 9h18M9 21V9"/></svg>
        </div>
        <div>
            <strong>Rincian per Bulan</strong>
            <span>Target, Realisasi, % Pencapaian &amp; Jumlah Pelanggan — Tahun {{ $tahunAktif ?: '-' }}</span>
        </div>
        <button type="button" class="card-copy-btn" style="margin-left:auto;" onclick="salinTabelGambar('capture-trend-tabel', this, 'Rincian per Bulan — {{ $metric === 'kwh' ? 'kWh' : 'Rp TS' }}')">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="9" y="9" width="13" height="13" rx="2"/><path d="M5 15H4a2 2 0 0 1-2-2V4a2 2 0 0 1 2-2h9a2 2 0 0 1 2 2v1"/></svg>
            <span class="card-copy-btn-label">Salin Gambar</span>
        </button>
    </div>

    <div id="capture-trend-tabel">
    <div class="table-scroll">
        <table class="trend-hz-table">
            <thead>
                <tr>
                    <th>Kategori</th>
                    @foreach ($labels as $label)
                        <th>{{ $label }}</th>
                    @endforeach
                </tr>
            </thead>
            <tbody>
                <tr class="row-target">
                    <td>
                        <span class="row-icon tone-target">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="9"/><circle cx="12" cy="12" r="4"/><circle cx="12" cy="12" r=".5" fill="currentColor"/></svg>
                        </span>
                        Target
                    </td>
                    @foreach ($targetData as $nilaiTarget)
                        <td>{{ number_format($nilaiTarget, 2, ',', '.') }}</td>
                    @endforeach
                </tr>
                <tr class="row-realisasi">
                    <td>
                        <span class="row-icon tone-realisasi">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M20 6 9 17l-5-5"/></svg>
                        </span>
                        Realisasi
                    </td>
                    @foreach ($data as $nilaiRealisasi)
                        <td>{{ number_format($nilaiRealisasi, 2, ',', '.') }}</td>
                    @endforeach
                </tr>
                <tr class="row-pelanggan">
                    <td>
                        <span class="row-icon tone-pelanggan">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/></svg>
                        </span>
                        Jumlah Pelanggan
                    </td>
                    @foreach ($jumlahPelangganData as $jmlPelanggan)
                        <td>{{ $jmlPelanggan !== null ? number_format($jmlPelanggan, 0, ',', '.') : '-' }}</td>
                    @endforeach
                </tr>
                <tr class="row-persen">
                    <td>
                        <span class="row-icon tone-persen">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M3 3v18h18"/><path d="M7 15l4-5 3 3 5-7"/></svg>
                        </span>
                        % Pencapaian
                    </td>
                    @foreach ($persenPerBulanMentah as $persen)
                        @php
                            $toneP = $persen === null
                                ? 'abu'
                                : ($persen >= 100 ? 'hijau' : ($persen >= 80 ? 'oren' : 'merah'));
                        @endphp
                        <td class="tone-{{ $toneP }}">
                            <span class="persen-text">{{ $persen === null ? '-' : $persen . '%' }}</span>
                        </td>
                    @endforeach
                </tr>
            </tbody>
        </table>
    </div>
    </div>
</div>
@endsection

{{--
    CATATAN:
    1. Chart.js sudah di-load sekali di layouts/app.blade.php, gak di-load
       ulang di sini.
    2. Canvas dibungkus div ber-tinggi pasti (.trend-chart-canvas-wrap) +
       maintainAspectRatio:false, biar tingginya konsisten.
    3. Chart.getChart() dipakai buat destroy instance lama sebelum bikin
       yang baru — cegah error "Canvas is already in use".
    4. Semua string JS yang disisipkan dari Blade pakai @json() atau
       {!! !!}, BUKAN {{ }} — karena {{ }} otomatis nge-htmlspecialchars
       tanda kutip jadi &#039; dan bikin JS-nya gagal di-parse browser
       (Unexpected token '&').
    5. Model chart DISAMAIN sama trend/pencapaian.blade.php: mixed chart
       (bar buat nilai realisasi + line buat target), BUKAN dua garis
       seperti sebelumnya. Dataset diberi label "Realisasi" (bukan
       "Aktual") biar konsisten sama istilah yang dipakai di tabel
       "Rincian per Bulan" dan card statistik di halaman ini.
    6. % Pencapaian & Jumlah Pelanggan PER BULAN ditampilkan LANGSUNG di
       atas tiap batang chart (custom plugin drawBarLabels, ctx.fillText
       manual — bukan lewat tooltip hover, dan bukan angka total/agregat
       setahun). Info yang sama juga ada di baris tabel "Rincian per
       Bulan" sebagai referensi lengkap yang bisa discroll.
    7. Custom plugin drawBarLabels dipakai (bukan library eksternal
       kayak chartjs-plugin-datalabels) biar gak nambah dependency CDN
       baru — cukup pakai Chart.js API bawaan (afterDatasetsDraw).
    8. PERBAIKAN: sebelumnya ada potongan kode yang ke-duplikat/nyangkut
       di tengah objek `new Chart(...)` — ada string '#ffce3a' yang
       nyasar di luar objek manapun, dan key "options" muncul DUA KALI.
       Itu bikin seluruh <script> di halaman ini gagal di-parse browser
       (SyntaxError), jadi BUKAN CUMA chart/Target-nya yang gak jalan,
       tombol "Salin Gambar" di semua card juga ikut mati total karena
       satu tag <script> yang sama gagal di-parse dari awal. Sudah
       dibersihkan jadi satu objek Chart yang valid.
--}}
@push('scripts')
@include('laporan.partials.copy-image-script')
<script>
(function () {
    var canvas = document.getElementById('trendChart');
    if (!canvas) return;

    if (typeof Chart === 'undefined') {
        console.error('Chart.js belum termuat — cek Network tab, kemungkinan CDN diblokir.');
        return;
    }

    var existing = Chart.getChart(canvas);
    if (existing) existing.destroy();

    // Data per bulan buat label di atas tiap bar — dikirim dari PHP,
    // urutannya sejajar sama $labels/$data.
    var persenPerBulan = @json($persenPerBulanMentah);
    var jumlahPelangganPerBulan = @json($jumlahPelangganData);
    var nilaiRealisasiPerBulan = @json($data);
    var nilaiTargetPerBulan = @json($targetData);
    var metricPrefix = {!! json_encode($metric === 'kwh' ? '' : 'Rp ') !!};
    var metricSuffix = {!! json_encode($metric === 'kwh' ? ' KWH' : '') !!};

    function formatNilai(v) {
        return metricPrefix + Number(v).toLocaleString('id-ID') + metricSuffix;
    }

    // ===== Custom plugin: gambar teks "% pencapaian" & "jumlah
    // pelanggan" langsung di atas tiap batang Realisasi, permanen di
    // canvas (bukan tooltip hover). =====
    var drawBarLabelsPlugin = {
        id: 'drawBarLabels',
        afterDatasetsDraw: function (chart) {
            var ctx = chart.ctx;
            var barDataset = chart.getDatasetMeta(0); // dataset 0 = Realisasi (bar)
            if (!barDataset || !barDataset.data) return;

            ctx.save();

            barDataset.data.forEach(function (bar, i) {
                var persen = persenPerBulan[i];
                var jumlahPelanggan = jumlahPelangganPerBulan[i];
                var nilaiRealisasi = nilaiRealisasiPerBulan[i];
                var nilaiTarget = nilaiTargetPerBulan[i];

                // Skip kalau semua data kosong/nol, biar canvas gak
                // penuh teks buat bulan yang gak ada apa-apanya.
                var adaRealisasi = nilaiRealisasi !== null && nilaiRealisasi !== undefined && nilaiRealisasi > 0;
                var adaTarget = nilaiTarget !== null && nilaiTarget !== undefined && nilaiTarget > 0;
                if (!adaRealisasi && !adaTarget && persen === null && (jumlahPelanggan === null || jumlahPelanggan === undefined)) {
                    return;
                }

                var teksPersen = persen === null ? '-' : persen + '%';
                var teksPelanggan = (jumlahPelanggan === null || jumlahPelanggan === undefined)
                    ? '-'
                    : Number(jumlahPelanggan).toLocaleString('id-ID') + ' plg';
                var teksRealisasi = adaRealisasi ? 'R: ' + formatNilai(nilaiRealisasi) : null;
                var teksTarget = adaTarget ? 'T: ' + formatNilai(nilaiTarget) : null;

                var x = bar.x;
                var y = bar.y - 8; // sedikit di atas ujung batang
                var lineGap = 12;

                ctx.textAlign = 'center';
                ctx.textBaseline = 'alphabetic';

                var baris = [];

                // Baris paling atas: nilai Target (kalau ada)
                if (teksTarget) {
                    baris.push({ text: teksTarget, font: '600 9.5px inherit', color: '#b8860b' });
                }
                // Nilai Realisasi
                if (teksRealisasi) {
                    baris.push({ text: teksRealisasi, font: '700 9.5px inherit', color: '#0b3d91' });
                }
                // % pencapaian (warna ijo/merah sesuai capaian)
                baris.push({
                    text: teksPersen,
                    font: '700 10.5px inherit',
                    color: (persen !== null && persen >= 100) ? '#16803c' : (persen === null ? '#9aa4c2' : '#c62828')
                });
                // Baris paling bawah (paling dekat batang): jumlah pelanggan
                baris.push({ text: teksPelanggan, font: '600 9.5px inherit', color: '#0f6bd9' });

                // Gambar dari bawah ke atas biar urutannya: pelanggan (dekat
                // batang) -> % -> realisasi -> target (paling atas)
                for (var b = baris.length - 1; b >= 0; b--) {
                    var offsetIndex = baris.length - 1 - b;
                    ctx.font = baris[b].font;
                    ctx.fillStyle = baris[b].color;
                    ctx.fillText(baris[b].text, x, y - (offsetIndex * lineGap));
                }
            });

            ctx.restore();
        }
    };

    new Chart(canvas, {
        data: {
            labels: @json($labels),
            datasets: [
                {
                    type: 'bar',
                    label: 'Realisasi',
                    data: @json($data),
                    backgroundColor: 'rgba(11,61,145,.75)',
                    borderRadius: 6,
                    order: 2,
                },
                {
                    type: 'line',
                    label: 'Target',
                    data: @json($targetData),
                    borderColor: '#ffce3a',
                    backgroundColor: '#ffce3a',
                    borderWidth: 2.5,
                    pointRadius: 4,
                    pointBackgroundColor: '#ffce3a',
                    tension: 0.3,
                    order: 1,
                    // PERBAIKAN: Target dipisah ke sumbu Y sendiri (y1) —
                    // skalanya jauh lebih kecil dibanding Realisasi (bisa
                    // ribuan kali lipat bedanya), jadi kalau dipaksa satu
                    // sumbu sama Realisasi, garis Target keinjek rata di
                    // dasar chart dan kelihatannya kayak "gak kepanggil"
                    // padahal datanya sebenernya ada & ke-render.
                    yAxisID: 'y1',
                },
            ]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            interaction: { mode: 'index', intersect: false },
            // Ruang ekstra di atas biar label % & jumlah pelanggan gak
            // kepotong sama batas atas canvas pas nilainya mendekati
            // puncak sumbu Y.
            layout: { padding: { top: 58 } },
            plugins: {
                legend: {
                    display: true,
                    position: 'bottom',
                    labels: { usePointStyle: true, pointStyle: 'circle', boxWidth: 8, boxHeight: 8, padding: 18 }
                },
                tooltip: {
                    callbacks: {
                        label: function (ctx) {
                            var val = Number(ctx.raw).toLocaleString('id-ID');
                            var prefix = {!! json_encode($metric === 'kwh' ? '' : 'Rp ') !!};
                            var suffix = {!! json_encode($metric === 'kwh' ? ' KWH' : '') !!};
                            return ctx.dataset.label + ': ' + prefix + val + suffix;
                        }
                    }
                }
            },
            scales: {
                x: { grid: { display: false } },
                y: {
                    beginAtZero: true,
                    grid: { color: '#eef0f6' },
                    ticks: {
                        callback: function (v) {
                            return Number(v).toLocaleString('id-ID');
                        }
                    }
                },
                // Sumbu Y kedua khusus buat garis Target, skala independen
                // dari sumbu Y utama (Realisasi) — lihat komentar di
                // dataset Target di atas.
                y1: {
                    position: 'right',
                    beginAtZero: true,
                    grid: { display: false },
                    ticks: {
                        color: '#b8860b',
                        callback: function (v) {
                            return Number(v).toLocaleString('id-ID');
                        }
                    }
                }
            }
        },
        plugins: [drawBarLabelsPlugin]
    });
})();
</script>
@endpush