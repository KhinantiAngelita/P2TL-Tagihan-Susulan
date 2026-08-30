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

    .trend-page-title { font-size: clamp(18px, 4.2vw, 22px); margin: 0 0 4px; }

    /* ===== Filter card (gradient) — sekarang isinya cuma Tahun + toggle
       mode. Filter ULP/Bulan/Triwulan/Rentang Tanggal dipindah total ke
       panel "Filter Periode & ULP" (partial shared dengan Menu Laporan),
       yang di-render terpisah tepat di bawah card ini. ===== */
    .trend-filter-card {
        display: flex; align-items: center; justify-content: space-between;
        flex-wrap: wrap; gap: 14px;
        padding: 16px 22px; margin-bottom: 14px;
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

    .trend-chart-note {
        display: flex; align-items: center; gap: 6px;
        font-size: 11.5px; color: #9aa4c2; margin: 0 0 14px;
    }
    .trend-chart-note svg { width: 13px; height: 13px; flex-shrink: 0; }

    .trend-chart-canvas-wrap { position: relative; height: 350px; width: 100%; }

    /* ===== Tabel Target, Realisasi, Jumlah Pelanggan & % Pencapaian
       tepat di bawah grafik — kolom per bulan sejajar dengan batang di
       atasnya. Ini satu-satunya tabel rincian per bulan di halaman ini
       (card "Rincian per Bulan" terpisah sudah dihapus, datanya
       digabung ke sini). ===== */
    .trend-chart-tr-wrap {
        margin-top: 16px;
        border: 1px solid var(--border);
        border-radius: 12px;
        overflow: hidden;
    }
    .trend-chart-tr-note {
        display: flex; align-items: center; gap: 6px;
        font-size: 11px; color: #9aa4c2; padding: 9px 14px;
        background: #fafbfe; border-bottom: 1px solid var(--border);
    }
    .trend-chart-tr-note svg { width: 12px; height: 12px; flex-shrink: 0; }

    /* ===== Tabel horizontal (bulan = kolom, Target/Realisasi/Pelanggan/%
       = baris), dipakai di dalam .trend-chart-tr-wrap tepat di bawah
       grafik.
       RAPIH-RAPIH: lebar kolom bulan diseragamkan (min-width tetap)
       biar gak "lompat-lompat" tiap baris, angka pakai tabular-nums
       biar rata kanan-per-digit, border vertikal dibikin lebih tipis
       (cuma pemisah halus, bukan garis tebal), dan tiap baris dikasih
       tint warna sangat tipis senada ikonnya biar mata gampang nyortir
       baris mana lagi dilihat tanpa harus baca label kiri berulang. ===== */
    .trend-hz-table { width: 100%; border-collapse: collapse; table-layout: fixed; }
    .trend-hz-table th, .trend-hz-table td {
        padding: 12px 14px; font-size: 12.5px; text-align: center; white-space: nowrap;
        border-bottom: 1px solid var(--border); border-right: 1px solid #eef0f6;
        transition: background .12s;
        font-variant-numeric: tabular-nums;
    }
    .trend-hz-table th:not(:first-child), .trend-hz-table td:not(:first-child) {
        width: 100px;
    }
    .trend-hz-table th:last-child, .trend-hz-table td:last-child { border-right: none; }
    .trend-hz-table th:first-child, .trend-hz-table td:first-child {
        width: 168px;
        text-align: left; font-weight: 700; color: var(--text-dark);
        background: #fafbfe; position: sticky; left: 0; z-index: 1;
        display: flex; align-items: center; gap: 8px;
        border-right: 1px solid var(--border);
        box-shadow: 2px 0 4px rgba(20,30,80,.04);
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
        color: var(--text-muted); font-weight: 700; font-size: 11px;
        text-transform: uppercase; letter-spacing: .03em; background: #fafbfe;
        border-bottom: 2px solid var(--border);
    }
    .trend-hz-table thead th:first-child {
        text-align: left;
        color: #c3c9dc;
        font-weight: 600;
        text-transform: none;
        letter-spacing: normal;
        padding-left: 14px;
        display: table-cell;
    }
    .trend-hz-table thead th:not(:first-child) { transition: background .12s; cursor: default; }
    .trend-hz-table thead th:not(:first-child):hover,
    .trend-hz-table tbody tr td:not(:first-child):hover {
        background: #eef2ff !important;
    }
    .trend-hz-table tbody tr:last-child td { border-bottom: none; }
    .trend-hz-table tbody td { color: var(--text-dark); font-weight: 500; }

    .trend-hz-table tbody tr.row-target td:not(:first-child) { color: #c0246b; background: rgba(192,36,107,.035); }
    .trend-hz-table tbody tr.row-realisasi td:not(:first-child) {
        color: #1a9c4a; font-weight: 700; background: rgba(26,156,74,.045);
    }
    .trend-hz-table tbody tr.row-pelanggan td:not(:first-child) { color: #0f6bd9; font-weight: 600; background: rgba(15,107,217,.035); }
    .persen-text { font-weight: 700; }
    td.tone-hijau { background: #eafaf0; }
    td.tone-hijau .persen-text { color: #16803c; }
    td.tone-oren { background: #fff4e5; }
    td.tone-oren .persen-text { color: #c47a06; }
    td.tone-merah { background: #fdecec; }
    td.tone-merah .persen-text { color: #c62828; }
    td.tone-abu { background: #f4f5f9; }
    td.tone-abu .persen-text { color: #9aa4c2; }

    .dash-stat-card.tone-pink::before { background: #d81b60; }
    .tone-pink .dash-stat-icon { background: #fde6f0; color: #d81b60; }

    .dash-stat-card.tone-abu::before { background: #9aa4c2; }
    .tone-abu .dash-stat-icon { background: #eef0f6; color: #6b7690; }
    .dash-stat-detail-link {
        color: inherit; text-decoration: none; font-weight: 700;
        display: inline-flex; align-items: center; gap: 4px;
    }
    .dash-stat-detail-link:hover { text-decoration: underline; }

    .copyable-card { position: relative; }

    .copy-btn {
        border: 1px solid var(--border);
        background: #fff;
        color: #0b3d91;
        font-size: 12px;
        font-weight: 700;
        padding: 6px 14px;
        border-radius: 8px;
        cursor: pointer;
        white-space: nowrap;
        transition: background .15s, color .15s;
        display: inline-flex;
        align-items: center;
        gap: 6px;
        flex-shrink: 0;
    }
    .copy-btn svg { width: 14px; height: 14px; flex-shrink: 0; }
    .copy-btn:hover { background: #eaf0fb; }
    .copy-btn:disabled { opacity: .6; cursor: default; }

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

        .trend-hz-table th, .trend-hz-table td { padding: 9px 10px; font-size: 12px; }
        .trend-hz-table th:not(:first-child), .trend-hz-table td:not(:first-child) { width: 78px; }
        .trend-hz-table th:first-child, .trend-hz-table td:first-child { width: 140px; }
    }

    @media (max-width: 420px) {
        .dash-stats { grid-template-columns: 1fr; }
        .trend-filter-form { flex-direction: column; align-items: stretch; }
        .trend-filter-form select { width: 100%; }
        .trend-chart-canvas-wrap { height: 260px; }
        .trend-hz-table th, .trend-hz-table td { padding: 8px 8px; font-size: 11.5px; }
        .trend-hz-table th:not(:first-child), .trend-hz-table td:not(:first-child) { width: 70px; }
        .trend-hz-table th:first-child, .trend-hz-table td:first-child { width: 122px; }
    }
</style>
@endpush

@php
    $totalTargetTahunIni = $mode === 'kumulatif'
        ? (float) (end($targetData) ?: 0)
        : (float) array_sum($targetData);

    $selisihTahunIni = $totalTahunIni - $totalTargetTahunIni;

    $persenPerBulanMentah = collect($labels)->map(function ($label, $i) use ($tabelBulanan, $targetData, $mode, $data) {
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

<div class="trend-tabs">
    <a href="{{ route('trend.pencapaian', request()->except(['mode'])) }}">Presentase Pencapaian</a>
    <a href="{{ route('trend.kwh', request()->except([])) }}" class="{{ $metric === 'kwh' ? 'active' : '' }}">Trend kWh</a>
    <a href="{{ route('trend.ts', request()->except([])) }}" class="{{ $metric === 'ts' ? 'active' : '' }}">Trend Rp TS</a>
</div>

{{-- Panel Filter Periode & ULP — sekarang juga nampung Tahun & Tampilan
     (Bulanan/Komulatif) sebagai tab tambahan, khusus buat halaman Trend
     ini ($tampilkanTahunFilter=true & $mode dikirim dari controller).
     Triwulan / Bulan / Rentang Tanggal / ULP tetap sama persis dengan
     yang dipakai di Menu Laporan. --}}
@include('laporan.partials.filter-periode-ulp')

<div class="dash-stats">
    <div class="dash-stat-card tone-blue copyable-card">
        <div class="dash-stat-top">
            <div class="dash-stat-icon">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M3 3v18h18"/><path d="M7 15l4-5 3 3 5-7"/></svg>
            </div>
            <h3>Total {{ $tahunAktif ?: '-' }}</h3>
        </div>
        <div class="dash-stat-value">
            {{ $metric === 'kwh' ? number_format($totalTahunIni, 0, ',', '.') . ' KWH' : 'Rp ' . number_format($totalTahunIni, 0, ',', '.') }}
        </div>
        <div class="dash-stat-sub">Sesuai filter periode &amp; ULP terpilih</div>
    </div>

    <div class="dash-stat-card tone-yellow copyable-card">
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

    <div class="dash-stat-card tone-green copyable-card">
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

    <div class="dash-stat-card {{ $totalTargetTahunIni == 0 ? 'tone-abu' : ($selisihTahunIni > 0 ? 'tone-pink' : 'tone-green') }} copyable-card">
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
                <a href="{{ route('trend.pencapaian', request()->except(['mode'])) }}" class="dash-stat-detail-link">
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
            <p>{{ $mode === 'kumulatif' ? 'Akumulasi nilai dari bulan pertama sampai bulan berjalan' : 'Nilai per bulan (tidak diakumulasi)' }} &mdash; Tahun {{ $tahunAktif ?: '-' }}</p>
        </div>
        <div style="display:flex;flex-direction:column;align-items:flex-end;gap:8px;">
            <button type="button" class="copy-btn" onclick="salinTabelGambar('capture-trend-chart', this, 'Trend {{ $metric === 'kwh' ? 'kWh' : 'Rp TS' }} — {{ $mode === 'kumulatif' ? 'Komulatif' : 'Bulanan' }}')">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="9" y="9" width="13" height="13" rx="2"/><path d="M5 15H4a2 2 0 0 1-2-2V4a2 2 0 0 1 2-2h9a2 2 0 0 1 2 2v1"/></svg>
                Salin Gambar
            </button>
            <span class="chart-badge" style="background:#eaf0fb;color:#0b3d91;">{{ empty($filter['ulpTerpilih']) ? 'Semua ULP' : count($filter['ulpTerpilih']) . ' ULP dipilih' }}</span>
        </div>
    </div>

    <div id="capture-trend-chart">
        <p class="trend-chart-note">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><path d="M12 16v-4"/><path d="M12 8h.01"/></svg>
            Angka lengkap Target, Realisasi, Jumlah Pelanggan &amp; % Pencapaian ada di tabel bawah grafik.
        </p>

        <div class="trend-chart-canvas-wrap">
            <canvas id="trendChart"></canvas>
        </div>

        {{-- Tabel Target, Realisasi, Jumlah Pelanggan & % Pencapaian
             tepat di bawah grafik — kolom per bulan sejajar dengan
             urutan batang di atasnya. Menggantikan card "Rincian per
             Bulan" yang dulu terpisah di bawah halaman (sudah dihapus).
             Angka T/R/Pelanggan/% dulu sempat ditulis mengambang di
             atas tiap batang, sekarang batangnya cuma nampilin ringkasan
             % & jumlah pelanggan di dalamnya (lihat drawBarLabelsPlugin),
             sementara angka lengkapnya ada di tabel ini. --}}
        <div class="trend-chart-tr-wrap">
            <p class="trend-chart-tr-note">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="3" width="18" height="18" rx="2"/><path d="M3 9h18M9 21V9"/></svg>
                Target, Realisasi, Jumlah Pelanggan &amp; % Pencapaian per bulan (angka lengkap, sejajar dengan batang di atas)
            </p>
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
                                <td>{{ number_format($nilaiTarget, 0, ',', '.') }}</td>
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
                                <td>{{ number_format($nilaiRealisasi, 0, ',', '.') }}</td>
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
</div>
@endsection

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

    var persenPerBulan = @json($persenPerBulanMentah);
    var jumlahPelangganPerBulan = @json($jumlahPelangganData);
    var nilaiRealisasiPerBulan = @json($data);

    // Plugin buat nulis % Pencapaian & Jumlah Pelanggan LANGSUNG DI
    // DALAM tiap batang (vertically centered), bukan lagi mengambang di
    // atas batang. Info Target (T) & Realisasi (R) sekarang dipindah ke
    // tabel di bawah grafik (lihat .trend-chart-tr-wrap di HTML) —
    // dengan begitu batangnya lebih bersih, cuma nampilin ringkasan
    // performa (% & jumlah pelanggan) yang paling relevan dilihat
    // sekilas per batang.
    var drawBarLabelsPlugin = {
        id: 'drawBarLabels',
        afterDatasetsDraw: function (chart) {
            var ctx = chart.ctx;
            var barDataset = chart.getDatasetMeta(0);
            if (!barDataset || !barDataset.data) return;

            ctx.save();
            ctx.textAlign = 'center';
            ctx.textBaseline = 'middle';

            var lineGap = 13;

            barDataset.data.forEach(function (bar, i) {
                var persen = persenPerBulan[i];
                var jumlahPelanggan = jumlahPelangganPerBulan[i];
                var nilaiRealisasi = nilaiRealisasiPerBulan[i];

                // Gak ada batang yang keliatan (realisasi 0/kosong) ->
                // gak ada "di dalam batang" buat ditulisin apa-apa.
                var adaRealisasi = nilaiRealisasi !== null && nilaiRealisasi !== undefined && nilaiRealisasi > 0;
                if (!adaRealisasi) return;

                var teksPersen = persen === null ? '-' : persen + '%';
                var teksPelanggan = (jumlahPelanggan === null || jumlahPelanggan === undefined)
                    ? '-'
                    : Number(jumlahPelanggan).toLocaleString('id-ID') + ' plg';

                var warnaPersen = persen === null
                    ? '#d7e0f5'
                    : (persen >= 100 ? '#8CFFB8' : '#FFB3B3');

                var baris = [
                    { text: teksPersen, font: '700 11px inherit', color: warnaPersen },
                    { text: teksPelanggan, font: '600 9.5px inherit', color: '#e6edfb' },
                ];

                var xTengah = bar.x;
                var yTengahBatang = (bar.y + bar.base) / 2;
                var yMulai = yTengahBatang - ((baris.length - 1) * lineGap) / 2;

                baris.forEach(function (line, idx) {
                    ctx.font = line.font;
                    ctx.fillStyle = line.color;
                    ctx.fillText(line.text, xTengah, yMulai + idx * lineGap);
                });
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
                    yAxisID: 'y1',
                },
            ]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            interaction: { mode: 'index', intersect: false },
            layout: { padding: { top: 16 } },
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
                        },
                        // Baris tambahan setelah label Realisasi & Target
                        // (yang muncul otomatis dari callback "label" di
                        // atas, sekali per dataset) — nampilin % Pencapaian
                        // & Jumlah Pelanggan bulan itu juga, biar tooltip
                        // pas di-hover isinya lengkap sama kayak yang
                        // ditulis di dalam batangnya.
                        afterBody: function (tooltipItems) {
                            if (!tooltipItems.length) return [];
                            var i = tooltipItems[0].dataIndex;
                            var persen = persenPerBulan[i];
                            var jumlahPelanggan = jumlahPelangganPerBulan[i];

                            return [
                                '% Pencapaian: ' + (persen === null ? '-' : persen + '%'),
                                'Jumlah Pelanggan: ' + ((jumlahPelanggan === null || jumlahPelanggan === undefined) ? '-' : Number(jumlahPelanggan).toLocaleString('id-ID'))
                            ];
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