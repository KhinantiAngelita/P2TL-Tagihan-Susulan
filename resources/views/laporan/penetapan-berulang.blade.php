@extends('layouts.app')
@section('title', 'Penetapan Berulang')

@section('breadcrumb')
    <a href="{{ route('dashboard') }}">Beranda</a>
    <span class="sep">›</span>
    <strong>Penetapan Berulang</strong>
@endsection

@push('styles')
<style>
    .pb-page-title { font-size: clamp(18px, 4.2vw, 22px); margin: 0 0 3px; color: #1b2559; font-weight: 700; }

    .pb-header {
        display: flex; align-items: center; justify-content: space-between; gap: 14px;
        margin-bottom: 18px; flex-wrap: wrap;
    }
    .pb-header-left { display: flex; align-items: center; gap: 14px; }
    .pb-header-icon {
        width: 44px; height: 44px; border-radius: 13px; flex-shrink: 0;
        background: linear-gradient(135deg, #0b3d91, #4f7fff);
        display: flex; align-items: center; justify-content: center;
        color: #fff; box-shadow: 0 5px 14px rgba(11,61,145,.28);
    }
    .pb-header-icon svg { width: 21px; height: 21px; }
    .pb-header p { margin: 0; font-size: 14px; color: #6b7690; }

    .pb-export-btn {
        display: inline-flex; align-items: center; gap: 8px;
        border: none; background: linear-gradient(135deg, #1e7e34, #2fa84f);
        color: #fff; font-size: 13px; font-weight: 700;
        padding: 10px 18px; border-radius: 10px; cursor: pointer;
        text-decoration: none; box-shadow: 0 4px 12px rgba(30,126,52,.28);
        transition: opacity .15s, transform .15s;
    }
    .pb-export-btn:hover { opacity: .92; transform: translateY(-1px); }
    .pb-export-btn svg { width: 15px; height: 15px; }

    .pb-info-banner {
        display: flex; align-items: flex-start; gap: 10px;
        background: linear-gradient(135deg, rgba(56,97,251,.06), rgba(56,97,251,.02));
        border: 1px solid rgba(56,97,251,.18);
        border-radius: 10px; padding: 12px 16px; margin-bottom: 18px;
    }
    .pb-info-banner svg { width: 16px; height: 16px; color: var(--blue-primary, #0b3d91); flex-shrink: 0; margin-top: 2px; }
    .pb-info-banner p { margin: 0; font-size: 12.5px; color: #3d4566; line-height: 1.55; }
    .pb-info-banner strong { color: #1b2559; }

    .pb-jumlah-filter { display: flex; align-items: center; gap: 10px; flex-wrap: wrap; }
    .pb-jumlah-filter label { font-size: 12.5px; font-weight: 600; color: #6b7690; }

    .filter-wrap { position: relative; }
    .filter-wrap svg {
        position: absolute; left: 12px; top: 50%; transform: translateY(-50%);
        width: 14px; height: 14px; color: #9aa4c2; pointer-events: none;
    }
    .filter-select {
        border: 1px solid var(--border); border-radius: 9px;
        padding: 9px 14px 9px 34px; font-size: 13px; font-weight: 600;
        background: #fff; color: #1b2559; appearance: none; cursor: pointer;
        transition: border-color .15s;
    }
    .filter-select:hover { border-color: #c9d3f5; }
    .filter-select:focus { outline: none; border-color: var(--blue-primary, #0b3d91); box-shadow: 0 0 0 3px rgba(11,61,145,.1); }

    .pb-search-wrap { position: relative; }
    .pb-search-wrap svg {
        position: absolute; left: 12px; top: 50%; transform: translateY(-50%);
        width: 14px; height: 14px; color: #9aa4c2; pointer-events: none;
    }
    .pb-search-input {
        border: 1px solid var(--border); border-radius: 9px;
        padding: 9px 14px 9px 34px; font-size: 13px; font-weight: 500;
        background: #fff; color: #1b2559; width: 220px;
        transition: border-color .15s;
    }
    .pb-search-input:hover { border-color: #c9d3f5; }
    .pb-search-input:focus { outline: none; border-color: var(--blue-primary, #0b3d91); box-shadow: 0 0 0 3px rgba(11,61,145,.1); }

    .pb-chart-card { padding: 22px; margin-bottom: 16px; }
    .pb-chart-head { margin-bottom: 14px; }
    .pb-chart-head h3 { margin: 0 0 2px; font-size: 15px; color: #1b2559; }
    .pb-chart-head p { margin: 0; font-size: 12.5px; color: #6b7690; }
    .pb-chart-wrap { position: relative; height: 300px; }

    .pb-table-card { padding: 0; overflow: hidden; }
    .pb-table-head {
        display: flex; align-items: center; justify-content: space-between; gap: 12px;
        padding: 18px 22px; border-bottom: 1px solid var(--border); flex-wrap: wrap;
    }
    .pb-table-head h3 { margin: 0; font-size: 14.5px; color: #1b2559; }
    .pb-table-head p { margin: 2px 0 0; font-size: 12px; color: #6b7690; }

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

    .pivot-table { width: 100%; border-collapse: collapse; }
    .pivot-table th, .pivot-table td {
        padding: 12px 15px; font-size: 13px; text-align: center; white-space: nowrap;
        border-bottom: 1px solid #f0f2f8; border-right: 1px solid #f5f6fa;
        font-variant-numeric: tabular-nums;
    }
    .pivot-table th:last-child, .pivot-table td:last-child { border-right: none; }
    .pivot-table thead th {
        color: #6b7690; font-weight: 700; font-size: 11px;
        text-transform: uppercase; letter-spacing: .03em;
        background: #fafbfe; border-bottom: 2px solid var(--border);
    }
    .pivot-table thead th a {
        color: inherit; text-decoration: none; display: inline-block;
        min-width: 20px; padding: 2px 4px; border-radius: 6px; transition: .12s;
    }
    .pivot-table thead th a:hover { color: #fff; background: var(--blue-primary, #0b3d91); }
    .pivot-table thead th.col-max { background: #ffce3a; color: #6b4e00; }
    .pivot-table thead th.col-max a:hover { background: #6b4e00; color: #ffce3a; }
    .pivot-table tbody td.col-max { background: #fff8e6; font-weight: 700; color: #8a6300; }
    .pivot-table tbody tr:nth-child(even) td:not(.col-ulp):not(.col-max) { background: #fbfcfe; }
    .pivot-table thead th.col-ulp, .pivot-table tbody td.col-ulp {
        text-align: left; font-weight: 700; color: #1b2559;
        background: #fafbfe; position: sticky; left: 0; z-index: 1;
        box-shadow: 2px 0 4px rgba(20,30,80,.04);
    }
    .pivot-table tbody tr:hover td:not(.col-ulp) { background: #f2f5ff; }
    .pivot-table tbody tr:hover td.col-max { background: #ffefc2; }
    .pivot-table tbody td:empty::before { content: "–"; color: #d5dbe9; }
    .pivot-table tfoot td {
        padding: 12px 15px; font-size: 13px; font-weight: 800; color: #0b3d91;
        background: #eaf0fb; border-top: 2px solid var(--border);
    }
    .pivot-table tfoot td.col-ulp { text-align: left; position: sticky; left: 0; background: #eaf0fb; box-shadow: 2px 0 4px rgba(20,30,80,.04); }
    .pivot-table tfoot td.col-max { background: #ffce3a; color: #6b4e00; }

    .pb-active-filter {
        display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: 10px;
        padding: 12px 22px; background: #eaf0fb; border-bottom: 1px solid var(--border);
        font-size: 13px; color: #0b3d91;
    }
    .pb-active-filter strong { font-weight: 800; }
    .pb-active-filter a {
        font-size: 12.5px; font-weight: 700; color: #0b3d91;
        text-decoration: none; border: 1px solid #0b3d91; border-radius: 8px; padding: 5px 12px;
    }
    .pb-active-filter a:hover { background: #0b3d91; color: #fff; }

    .data-table { width: 100%; border-collapse: collapse; font-size: 13px; }
    .data-table thead th {
        text-align: left; color: #6b7690; font-weight: 700; font-size: 11px;
        text-transform: uppercase; letter-spacing: .03em;
        padding: 13px 22px; background: #fafbfe; border-bottom: 2px solid var(--border); white-space: nowrap;
    }
    .data-table thead th.num { text-align: right; }
    .data-table tbody td { padding: 13px 22px; border-bottom: 1px solid #f1f3f9; color: #1b2559; }
    .data-table tbody td.num { text-align: right; font-variant-numeric: tabular-nums; }
    .data-table tbody tr:nth-child(even) { background: #fbfcfe; }
    .data-table tbody tr:hover { background: #f2f5ff; }
    .data-table tbody tr:last-child td { border-bottom: none; }

    .jumlah-badge {
        display: inline-flex; align-items: center; justify-content: center;
        min-width: 28px; padding: 4px 10px; border-radius: 999px;
        background: #fdeaea; color: #c62828; font-weight: 800; font-size: 12.5px;
        letter-spacing: .01em;
    }
    .gol-pill {
        display: inline-block; background: #eef1f8; color: #3d4566;
        font-size: 11.5px; font-weight: 700; padding: 3px 10px; border-radius: 999px;
    }
    .idpel-text { font-variant-numeric: tabular-nums; color: #6b7690; font-size: 12.5px; }
    .nama-text { font-weight: 700; color: #1b2559; }

    .icon-btn {
        width: 34px; height: 34px; border: none; border-radius: 9px; cursor: pointer;
        display: flex; justify-content: center; align-items: center;
        background: #eef4ff; color: #0b3d91; transition: .18s;
    }
    .icon-btn:hover { background: #0b3d91; color: #fff; box-shadow: 0 4px 10px rgba(11,61,145,.25); transform: translateY(-1px); }

    .custom-modal {
        display: none; position: fixed; left: 0; top: 0; width: 100%; height: 100%;
        background: rgba(0,0,0,.4); backdrop-filter: blur(5px); z-index: 9999;
        overflow-y: auto; padding: 20px 0; align-items: center; justify-content: center;
    }
    .custom-modal-content {
        max-width: 95%; max-height: 85vh; margin: 0 auto; background: #fff;
        border-radius: 16px; overflow: hidden; display: flex; flex-direction: column;
        animation: pbPopup .25s;
    }
    @keyframes pbPopup {
        from { transform: translateY(-30px); opacity: 0; }
        to { transform: translateY(0); opacity: 1; }
    }
    .modal-body { padding: 22px; overflow-y: auto; flex: 1 1 auto; min-height: 0; }
    .modal-header-blue {
        padding: 20px 22px; background: linear-gradient(90deg,#003b94,#0f6bd9);
        display: flex; justify-content: space-between; align-items: center; color: #fff; gap: 12px;
    }
    .modal-header-blue .header-text { display: flex; align-items: center; min-width: 0; }
    .modal-header-blue .header-icon {
        width: 42px; height: 42px; border-radius: 12px; background: rgba(255,255,255,.15);
        display: flex; align-items: center; justify-content: center; margin-right: 14px; flex-shrink: 0;
    }
    .modal-header-blue .header-icon svg { width: 20px; height: 20px; color: #ffce3a; }
    .modal-header-blue h3 { margin: 0 0 2px; font-size: 16px; }
    .modal-header-blue p { margin: 0; font-size: 12.5px; opacity: .85; }
    .close-modal { border: none; background: none; font-size: 28px; color: #fff; cursor: pointer; flex-shrink: 0; }
</style>
@endpush

@section('content')

<div class="pb-header">
    <div class="pb-header-left">
        <span class="pb-header-icon">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M3 12a9 9 0 0 1 15-6.7L21 8"/><path d="M21 3v5h-5"/><path d="M21 12a9 9 0 0 1-15 6.7L3 16"/><path d="M8 16H3v5"/></svg>
        </span>
        <div>
            <h2 class="pb-page-title">Penetapan Berulang</h2>
            <p>Pelanggan yang muncul lebih dari sekali di data temuan P2TL.</p>
        </div>
    </div>

    {{-- Export Excel — bawa SEMUA query string filter yang lagi aktif
         (Tahun/TW/Bulan/Tanggal/ULP/Golongan/Non-Pelanggan/Jumlah/Search),
         jadi file yang di-download selalu sesuai apa yang lagi ditampilkan. --}}
    <a href="{{ route('laporan.penetapan-berulang.export', request()->query()) }}" class="pb-export-btn">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 15V3M6 9l6 6 6-6"/><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/></svg>
        Export Excel
    </a>
</div>

@if ($modeNonPelanggan !== 'sembunyikan' && $adaNonPelanggan)
    <div class="pb-info-banner">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><path d="M12 16v-4"/><path d="M12 8h.01"/></svg>
        <p>
            <strong>Catatan:</strong>
            Sebagian temuan tercatat dengan IDPEL "NONPELANG" (bukan pelanggan terdaftar, mis. sambungan ilegal) —
            IDPEL itu placeholder yang dipakai bersama banyak temuan berbeda, jadi khusus buat data ini,
            pelanggan dibedakan berdasarkan <strong>Nama</strong> per ULP (bukan IDPEL) supaya orang yang
            berbeda-beda gak ke-anggap satu pelanggan yang sama.
        </p>
    </div>
@endif

{{-- Filter Periode & ULP — partial yang sama dengan Menu Trend, plus
     tab Golongan Temuan & Non-Pelanggan yang cuma muncul di sini
     (dipicu flag $tampilkanGolonganFilter / $tampilkanNonPelangganFilter
     dari controller). TIDAK ada lagi filter card manual seperti
     versi lama (yang dulu pakai $ulpFilter tunggal) di bawah sini. --}}
@include('laporan.partials.filter-periode-ulp')

{{-- Ringkasan --}}
<div class="dash-stats" style="margin-bottom:16px;">
    <div class="dash-stat-card tone-red">
        <div class="dash-stat-top">
            <div class="dash-stat-icon">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M17 21v-2a4 4 0 0 0-4-4H7a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/></svg>
            </div>
            <h3>Pelanggan Berulang</h3>
        </div>
        <div class="dash-stat-value">{{ number_format($totalPelangganBerulang, 0, ',', '.') }}</div>
        <div class="dash-stat-sub">Muncul &ge; 2 kali di temuan P2TL</div>
    </div>

    <div class="dash-stat-card tone-blue">
        <div class="dash-stat-top">
            <div class="dash-stat-icon">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M6 22V4a1 1 0 0 1 1-1h10a1 1 0 0 1 1 1v18"/><path d="M2 22h20M10 6h.01M14 6h.01M10 10h.01M14 10h.01M10 14h.01M14 14h.01M10 18h4"/></svg>
            </div>
            <h3>ULP Terdampak</h3>
        </div>
        <div class="dash-stat-value">{{ number_format($totalUlpTerdampak, 0, ',', '.') }}</div>
        <div class="dash-stat-sub">Dari {{ $daftarUlp->count() }} ULP terdaftar</div>
    </div>

    <div class="dash-stat-card tone-yellow">
        <div class="dash-stat-top">
            <div class="dash-stat-icon">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 2 3 7v6c0 5 4 8.5 9 9 5-.5 9-4 9-9V7l-9-5Z"/></svg>
            </div>
            <h3>Pengulangan Tertinggi</h3>
        </div>
        <div class="dash-stat-value">{{ $pengulanganTertinggi }}x</div>
        <div class="dash-stat-sub">Kemunculan terbanyak satu pelanggan</div>
    </div>

    <div class="dash-stat-card tone-green">
        <div class="dash-stat-top">
            <div class="dash-stat-icon">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M3 3v18h18"/><path d="M18 17V9M13 17V5M8 17v-3"/></svg>
            </div>
            <h3>Rata-rata Pengulangan</h3>
        </div>
        <div class="dash-stat-value">{{ $rataRataPengulangan }}x</div>
        <div class="dash-stat-sub">Dari pelanggan yang berulang</div>
    </div>
</div>

{{-- Grafik batang: total pelanggan per jumlah kemunculan --}}
<div class="card pb-chart-card">
    <div class="pb-chart-head" style="display:flex;align-items:flex-start;justify-content:space-between;gap:12px;flex-wrap:wrap;">
        <div>
            <h3>Distribusi Jumlah Kemunculan</h3>
            <p>Total pelanggan (seluruh ULP) per jumlah kemunculan di data temuan P2TL</p>
        </div>
        <button type="button" class="copy-btn" onclick="salinTabelGambar('capture-pb-chart', this, 'Distribusi Jumlah Kemunculan')">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="9" y="9" width="13" height="13" rx="2"/><path d="M5 15H4a2 2 0 0 1-2-2V4a2 2 0 0 1 2-2h9a2 2 0 0 1 2 2v1"/></svg>
            Salin Gambar
        </button>
    </div>
    <div class="pb-chart-wrap" id="capture-pb-chart">
        <canvas id="chartJumlahKemunculan"></canvas>
    </div>
</div>

{{-- Pivot ULP x Jumlah Kemunculan --}}
<div class="card pb-table-card" style="margin-bottom:16px;">
    <div class="pb-table-head">
        <div>
            <h3>Rincian per ULP</h3>
            <p>Klik salah satu angka di header buat lihat daftar pelanggannya di bawah</p>
        </div>
        <button type="button" class="copy-btn" onclick="salinTabelGambar('capture-pb-pivot', this, 'Penetapan Berulang — Rincian per ULP')">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="9" y="9" width="13" height="13" rx="2"/><path d="M5 15H4a2 2 0 0 1-2-2V4a2 2 0 0 1 2-2h9a2 2 0 0 1 2 2v1"/></svg>
            Salin Gambar
        </button>
    </div>

    <div class="table-scroll" id="capture-pb-pivot">
        @if (count($pivotRows) > 0)
            <table class="pivot-table">
                <thead>
                    <tr>
                        <th class="col-ulp">ULP</th>
                        @foreach ($daftarJumlah as $j)
                            <th class="{{ $j === $pengulanganTertinggi ? 'col-max' : '' }}">
                                <a href="{{ url()->current() }}?{{ http_build_query(array_merge(request()->except('jumlah'), ['jumlah' => $j])) }}#daftar-pelanggan">{{ $j }}</a>
                            </th>
                        @endforeach
                        <th>Grand Total</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($pivotRows as $row)
                        <tr>
                            <td class="col-ulp">{{ $row['nama'] }}</td>
                            @foreach ($daftarJumlah as $j)
                                <td class="{{ $j === $pengulanganTertinggi ? 'col-max' : '' }}">{{ $row['kolom'][$j] ?? '' }}</td>
                            @endforeach
                            <td><strong>{{ $row['total'] }}</strong></td>
                        </tr>
                    @endforeach
                </tbody>
                <tfoot>
                    <tr>
                        <td class="col-ulp">Grand Total</td>
                        @foreach ($daftarJumlah as $j)
                            <td class="{{ $j === $pengulanganTertinggi ? 'col-max' : '' }}">{{ $grandTotalKolom[$j] }}</td>
                        @endforeach
                        <td>{{ $grandTotalKeseluruhan }}</td>
                    </tr>
                </tfoot>
            </table>
        @else
            <p style="text-align:center;color:#9aa4c2;padding:32px;font-size:13px;">Belum ada pelanggan yang muncul berulang untuk filter ini.</p>
        @endif
    </div>
</div>

{{-- Daftar Pelanggan --}}
<div class="card pb-table-card" id="daftar-pelanggan">
    <div class="pb-table-head">
        <div>
            <h3>Daftar Pelanggan</h3>
            <p>{{ $daftarPelanggan->count() }} pelanggan sesuai filter</p>
        </div>

        {{-- Filter Jumlah Muncul & pencarian — form GET terpisah, tapi
             tetap membawa SEMUA filter Periode/ULP/Golongan/Non-Pelanggan
             yang sedang aktif lewat hidden input biar gak ke-reset. --}}
        <form method="GET" class="pb-jumlah-filter">
            @foreach (request()->except(['jumlah', 'search', '_token']) as $key => $value)
                @if (is_array($value))
                    @foreach ($value as $v)
                        <input type="hidden" name="{{ $key }}[]" value="{{ $v }}">
                    @endforeach
                @else
                    <input type="hidden" name="{{ $key }}" value="{{ $value }}">
                @endif
            @endforeach

            <div class="pb-search-wrap">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg>
                <input type="text" name="search" value="{{ $searchFilter }}" placeholder="Cari IDPEL atau nama..." class="pb-search-input">
            </div>

            <label for="filterJumlahMuncul">Jumlah Muncul</label>
            <div class="filter-wrap">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M3 3v18h18"/><path d="M18 17V9M13 17V5M8 17v-3"/></svg>
                <select name="jumlah" id="filterJumlahMuncul" onchange="this.form.submit()" class="filter-select">
                    <option value="" {{ ! $jumlahFilter ? 'selected' : '' }}>Semua</option>
                    @foreach ($daftarJumlah as $j)
                        <option value="{{ $j }}" {{ (string) $jumlahFilter === (string) $j ? 'selected' : '' }}>{{ $j }}x</option>
                    @endforeach
                </select>
            </div>
        </form>
    </div>

    @if ($jumlahFilter || $searchFilter)
        <div class="pb-active-filter">
            <span>
                Menampilkan pelanggan
                @if ($searchFilter)
                    yang cocok dengan "<strong>{{ $searchFilter }}</strong>"
                @endif
                @if ($searchFilter && $jumlahFilter) & @endif
                @if ($jumlahFilter)
                    yang muncul <strong>{{ $jumlahFilter }}x</strong>
                @endif
            </span>
            <a href="{{ url()->current() }}?{{ http_build_query(request()->except(['jumlah', 'search'])) }}">Reset Filter Tabel</a>
        </div>
    @endif

    <div class="table-scroll">
        <table class="data-table">
            <thead>
                <tr>
                    <th>IDPEL</th>
                    <th>Nama</th>
                    <th>ULP</th>
                    <th>Golongan Terakhir</th>
                    <th>Daya</th>
                    <th>Nomor Agenda</th>
                    <th>Tanggal Penetapan</th>
                    <th class="num">Jumlah Muncul</th>
                    <th class="num">Total KWH</th>
                    <th class="num">Total TS</th>
                    <th>Aksi</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($daftarPelanggan as $p)
                    @php
                        // Golongan/Daya/Nomor Agenda/Tanggal Penetapan yang
                        // ditampilkan adalah dari temuan PALING BARU (temuan
                        // sudah diurutkan kronologis di controller), sama
                        // pola-nya kayak "Golongan Terakhir" sebelumnya.
                        $temuanTerakhir = end($p['temuan']);
                    @endphp
                    <tr>
                        <td><span class="idpel-text">{{ $p['idpel'] }}</span></td>
                        <td><span class="nama-text">{{ $p['nama'] }}</span></td>
                        <td>{{ $daftarUlpAssoc[$p['ulp']] ?? $p['ulp'] }}</td>
                        <td><span class="gol-pill">{{ $temuanTerakhir['gol'] ?? '-' }}</span></td>
                        <td>{{ $temuanTerakhir['daya'] ?? '-' }}</td>
                        <td class="idpel-text">{{ $temuanTerakhir['no_agenda'] ?? '-' }}</td>
                        <td>{{ $temuanTerakhir['tanggal_register'] ? \Carbon\Carbon::parse($temuanTerakhir['tanggal_register'])->format('d M Y') : '-' }}</td>
                        <td class="num"><span class="jumlah-badge">{{ $p['jumlah'] }}x</span></td>
                        <td class="num">{{ number_format($p['total_kwh'], 0, ',', '.') }}</td>
                        <td class="num">{{ number_format($p['total_ts'], 0, ',', '.') }}</td>
                        <td>
                            <button type="button" class="icon-btn btn-detail-temuan"
                                    data-idpel="{{ $p['idpel'] }}"
                                    data-nama="{{ $p['nama'] }}"
                                    data-temuan="{{ json_encode($p['temuan']) }}"
                                    title="Detail Riwayat Temuan">
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="width:16px;height:16px;">
                                    <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8S1 12 1 12z"/>
                                    <circle cx="12" cy="12" r="3"/>
                                </svg>
                            </button>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="11" style="text-align:center;color:#9aa4c2;padding:32px;">Tidak ada pelanggan yang cocok dengan filter ini.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

{{-- Modal Detail Riwayat Temuan --}}
<div id="detailTemuanModal" class="custom-modal">
    <div class="custom-modal-content" style="width:640px;">
        <div class="modal-header-blue">
            <div class="header-text">
                <div class="header-icon">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8S1 12 1 12z"/><circle cx="12" cy="12" r="3"/></svg>
                </div>
                <div>
                    <h3 id="detailTemuanNama">Riwayat Temuan</h3>
                    <p id="detailTemuanIdpel">IDPEL: -</p>
                </div>
            </div>
            <button class="close-modal" type="button">&times;</button>
        </div>
        <div class="modal-body">
            <table class="data-table" style="font-size:12.5px;">
                <thead>
                    <tr>
                        <th>No</th>
                        <th>Nama</th>
                        <th>Golongan</th>
                        <th>Nomor Agenda</th>
                        <th>Tanggal Penetapan</th>
                        <th class="num">KWH</th>
                    </tr>
                </thead>
                <tbody id="detailTemuanBody"></tbody>
            </table>
        </div>
    </div>
</div>
@endsection

@push('scripts')
@include('laporan.partials.copy-image-script')
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
    document.addEventListener('DOMContentLoaded', function () {
        function formatTanggal(iso) {
            if (! iso) return '-';
            var d = new Date(iso);
            if (isNaN(d.getTime())) return iso;
            return d.toLocaleDateString('id-ID', { day: '2-digit', month: 'short', year: 'numeric' });
        }

        function escapeHtml(str) {
            var div = document.createElement('div');
            div.textContent = str == null ? '' : String(str);
            return div.innerHTML;
        }

        document.addEventListener('click', function (e) {
            var btn = e.target.closest('.btn-detail-temuan');
            if (btn) {
                var temuan = JSON.parse(btn.dataset.temuan || '[]');
                var nama = escapeHtml(btn.dataset.nama || '-');
                document.getElementById('detailTemuanNama').textContent = btn.dataset.nama || 'Riwayat Temuan';
                document.getElementById('detailTemuanIdpel').textContent = 'IDPEL: ' + (btn.dataset.idpel || '-');

                var tbody = document.getElementById('detailTemuanBody');
                tbody.innerHTML = temuan.map(function (t, i) {
                    return '<tr>'
                        + '<td>' + (i + 1) + '</td>'
                        + '<td>' + nama + '</td>'
                        + '<td><span class="gol-pill">' + escapeHtml(t.gol || '-') + '</span></td>'
                        + '<td>' + escapeHtml(t.no_agenda || '-') + '</td>'
                        + '<td>' + formatTanggal(t.tanggal_register) + '</td>'
                        + '<td class="num">' + Number(t.kwh || 0).toLocaleString('id-ID') + '</td>'
                        + '</tr>';
                }).join('');

                document.getElementById('detailTemuanModal').style.display = 'flex';
            }

            var closeBtn = e.target.closest('.close-modal');
            if (closeBtn) {
                closeBtn.closest('.custom-modal').style.display = 'none';
            }

            if (e.target.classList.contains('custom-modal')) {
                e.target.style.display = 'none';
            }
        });
    });

    new Chart(document.getElementById('chartJumlahKemunculan'), {
        type: 'bar',
        data: {
            labels: {!! json_encode(array_map(fn ($j) => $j . 'x', $daftarJumlah)) !!},
            datasets: [{
                label: 'Jumlah Pelanggan',
                data: {!! json_encode(array_values($grandTotalKolom)) !!},
                backgroundColor: {!! json_encode(array_map(fn ($j) => $j === $pengulanganTertinggi ? '#ffce3a' : 'rgba(11,61,145,.75)', $daftarJumlah)) !!},
                borderRadius: 6,
            }]
        },
        options: {
            maintainAspectRatio: false,
            plugins: {
                legend: { display: false },
                tooltip: {
                    callbacks: {
                        label: function (ctx) {
                            return ' ' + Number(ctx.raw).toLocaleString('id-ID') + ' pelanggan';
                        }
                    }
                }
            },
            scales: {
                x: { grid: { display: false }, title: { display: true, text: 'Jumlah Kemunculan' } },
                y: {
                    beginAtZero: true,
                    ticks: { callback: v => Number(v).toLocaleString('id-ID') }
                }
            }
        }
    });
</script>
@endpush