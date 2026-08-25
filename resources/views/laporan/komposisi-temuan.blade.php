@extends('layouts.app')
@section('title', 'Komposisi Temuan')

@section('breadcrumb')
    <a href="{{ route('dashboard') }}">Beranda</a>
    <span class="sep">›</span>
    <strong>Komposisi Temuan</strong>
@endsection

@push('styles')
<style>
    .trend-page-title { font-size: clamp(18px, 4.2vw, 22px); margin: 0 0 4px; color: #1b2559; font-weight: 700; }

    .trend-table-card {
        padding: 0; overflow: hidden; background: #fff;
        border: 1px solid var(--border); border-radius: 16px; box-sizing: border-box;
        box-shadow: 0 1px 2px rgba(16,24,64,.04);
    }
    .trend-table-head {
        display: flex; align-items: center; justify-content: space-between; gap: 12px;
        padding: 20px 22px; border-bottom: 1px solid var(--border); flex-wrap: wrap;
    }
    .trend-table-head-left { display: flex; align-items: center; gap: 12px; min-width: 0; }
    .trend-table-head-icon {
        width: 36px; height: 36px; border-radius: 11px; flex-shrink: 0;
        display: flex; align-items: center; justify-content: center;
        background: #eaf0fb; color: #0b3d91;
    }
    .trend-table-head-icon svg { width: 17px; height: 17px; }
    .trend-table-head h3 { margin: 0; font-size: 14.5px; color: #1b2559; font-weight: 700; }
    .trend-table-head p { margin: 2px 0 0; font-size: 12px; color: #8892a8; }

    .copy-btn {
        border: 1px solid var(--border);
        background: #fff;
        color: #0b3d91;
        font-size: 12px;
        font-weight: 700;
        padding: 7px 14px;
        border-radius: 9px;
        cursor: pointer;
        white-space: nowrap;
        transition: background .15s, color .15s;
        display: inline-flex;
        align-items: center;
        gap: 6px;
    }
    .copy-btn:hover { background: #eaf0fb; }
    .copy-btn:disabled { color: #16803c; border-color: #16803c; background: #e5f7ec; cursor: default; }

    .komposisi-table-scroll { overflow-x: auto; }

    /* ===== Tabel dasar — garis pemisah kolom soft di SEMUA sel
       (header, body, footer), bukan cuma header ===== */
    .rpt-table { width: 100%; border-collapse: separate; border-spacing: 0; }
    .rpt-table th, .rpt-table td {
        padding: 12px 16px; font-size: 12.5px; white-space: nowrap; text-align: right;
        border-right: 1px solid #eef0f6;
    }
    .rpt-table th:last-child, .rpt-table td:last-child { border-right: none; }

    /* ===== Header — warna cuma di sini, 2 baris (judul grup + sub-header) ===== */
    .rpt-table thead th {
        border-bottom: 1px solid var(--border);
        position: sticky; top: 0; z-index: 1;
    }
    .rpt-table thead tr.grp-row th {
        font-size: 11px; font-weight: 800; text-transform: uppercase; letter-spacing: .02em;
        padding-top: 10px; padding-bottom: 8px; border-bottom: none; text-align: center;
    }
    .rpt-table thead tr.sub-row th {
        font-size: 9.5px; font-weight: 700; text-transform: uppercase; letter-spacing: .03em;
        padding-top: 6px; padding-bottom: 9px;
    }

    .rpt-table thead th.grp-p { background: #e4ebfb; color: #1d4ed8; }
    .rpt-table thead th.grp-k { background: #fbedd9; color: #b45309; }
    .rpt-table thead th.grp-total { background: #eef0f6; color: #1b2559; }

    /* Garis pemisah putih tipis di dalam header berwarna (antar PLG/KWH/TS),
       lebih terang dari border abu biasa supaya kebaca di atas warna gelap */
    .rpt-table thead th.grp-p,
    .rpt-table thead th.grp-k,
    .rpt-table thead th.grp-total {
        border-right: 1px solid rgba(255,255,255,.55);
    }
    .rpt-table thead tr.grp-row th.grp-p:last-of-type,
    .rpt-table thead tr.grp-row th.grp-k:last-of-type,
    .rpt-table thead tr.grp-row th.grp-total:last-of-type {
        border-right: none;
    }
    /* Garis lebih tebal/gelap di batas ANTAR grup (P|K|Total), biar
       transisi antar kelompok kolom tetap kelihatan jelas */
    .rpt-table th.grp-start-outer, .rpt-table td.grp-start-outer {
        border-left: 1px solid #dde1ee;
    }

    /* ===== Body & footer — netral, garis kolom soft abu tipis ===== */
    .rpt-table tbody td { color: #1b2559; border-bottom: 1px solid #f1f2f8; }
    .rpt-table tbody tr:last-child td { border-bottom: none; }
    .rpt-table tbody tr:nth-child(even) td { background: #f8f9fc; }
    .rpt-table tbody tr:hover td { background: #eef2fb; }

    .rpt-table tfoot td {
        font-weight: 800; background: #f7f8fc; color: #1b2559; border-top: 1px solid var(--border);
    }

    /* Kolom No + UP3 sticky di kiri */
    .rpt-table th.col-no, .rpt-table td.col-no {
        width: 40px; min-width: 40px; text-align: left;
        position: sticky; left: 0; z-index: 2; background: #fff; color: #b3bad0;
    }
    .rpt-table th.col-nama, .rpt-table td.col-nama {
        min-width: 155px; text-align: left; font-weight: 700; color: #1b2559;
        position: sticky; left: 40px; z-index: 2; background: #fff;
    }
    .rpt-table thead th.col-no,
    .rpt-table thead th.col-nama { background: #eef0f6; color: #1b2559; z-index: 3; }
    .rpt-table tbody tr:nth-child(even) td.col-no,
    .rpt-table tbody tr:nth-child(even) td.col-nama { background: #f8f9fc; }
    .rpt-table tbody tr:hover td.col-no,
    .rpt-table tbody tr:hover td.col-nama { background: #eef2fb; }
    .rpt-table tfoot td.col-no,
    .rpt-table tfoot td.col-nama {
        position: sticky; left: 0; z-index: 3; background: #f7f8fc; text-align: left;
    }
    .rpt-table tfoot td.col-nama { left: 40px; }

    .filter-wrap { position: relative; }
    .filter-wrap svg {
        position: absolute; left: 12px; top: 50%; transform: translateY(-50%);
        width: 14px; height: 14px; color: #9aa4c2; pointer-events: none;
    }
    .filter-select {
        border: 1px solid var(--border);
        border-radius: 9px;
        padding: 8px 14px 8px 34px;
        font-size: 13px;
        font-weight: 600;
        background: #fff;
        color: #1b2559;
        appearance: none;
        cursor: pointer;
    }
    .filter-select:focus { outline: none; border-color: var(--blue-primary, #0b3d91); }

    @media (max-width: 640px) {
        .trend-table-head { padding: 16px; }
    }
</style>
@endpush

@section('content')

<div style="display:flex;align-items:flex-start;justify-content:space-between;margin-bottom:16px;flex-wrap:wrap;gap:12px;">
    <div>
        <h2 class="trend-page-title">Komposisi Temuan Gol P & K Per UP3</h2>
        <p style="color:#6b7690;margin:0;font-size:14px;">Rekap jumlah pelanggan, KWH, dan TS per UP3</p>
    </div>

    <form method="GET">
        <div class="filter-wrap">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="4" width="18" height="18" rx="2"/><path d="M16 2v4M8 2v4M3 10h18"/></svg>
            <select name="tahun" onchange="this.form.submit()" class="filter-select">
                @forelse ($daftarTahun as $t)
                    <option value="{{ $t }}" {{ (int) $tahunAktif === (int) $t ? 'selected' : '' }}>{{ $t }}</option>
                @empty
                    <option value="">Belum ada data</option>
                @endforelse
            </select>
        </div>
    </form>
</div>

@include('laporan.partials.filter-periode-ulp')

<div class="trend-table-card">
    <div class="trend-table-head">
        <div class="trend-table-head-left">
            <div class="trend-table-head-icon">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M3 3v18h18"/><path d="M7 15l4-5 3 3 5-7"/></svg>
            </div>
            <div>
                <h3>Rincian per UP3</h3>
                <p>Komposisi temuan golongan P vs K</p>
            </div>
        </div>
        <div>
            <button type="button" class="copy-btn" onclick="salinTabelGambar('capture-komposisi', this, 'Komposisi Temuan Gol P & K Per UP3')">📷 Salin Gambar</button>
        </div>
    </div>

    <div id="capture-komposisi">
    <div class="komposisi-table-scroll">
        @if (count($rows) > 0)
            <table class="rpt-table" id="tabel-komposisi">
                <thead>
                    <tr class="grp-row">
                        <th rowspan="2" class="col-no">No</th>
                        <th rowspan="2" class="col-nama">UP3</th>
                        <th colspan="3" class="grp-p grp-start-outer">Temuan P</th>
                        <th colspan="3" class="grp-k grp-start-outer">Temuan K</th>
                        <th colspan="3" class="grp-total grp-start-outer">Total</th>
                    </tr>
                    <tr class="sub-row">
                        <th class="grp-p grp-start-outer">PLG</th><th class="grp-p">KWH</th><th class="grp-p">TS</th>
                        <th class="grp-k grp-start-outer">PLG</th><th class="grp-k">KWH</th><th class="grp-k">TS</th>
                        <th class="grp-total grp-start-outer">KWH</th><th class="grp-total">% P KWH</th><th class="grp-total">% K KWH</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($rows as $i => $row)
                        <tr>
                            <td class="col-no">{{ $i + 1 }}</td>
                            <td class="col-nama">{{ $row['nama'] }}</td>

                            <td class="grp-start-outer">{{ number_format($row['p']['plg'], 0, ',', '.') }}</td>
                            <td>{{ number_format($row['p']['kwh'], 0, ',', '.') }}</td>
                            <td>{{ number_format($row['p']['ts'], 0, ',', '.') }}</td>

                            <td class="grp-start-outer">{{ number_format($row['k']['plg'], 0, ',', '.') }}</td>
                            <td>{{ number_format($row['k']['kwh'], 0, ',', '.') }}</td>
                            <td>{{ number_format($row['k']['ts'], 0, ',', '.') }}</td>

                            <td class="grp-start-outer">{{ number_format($row['total_kwh'], 0, ',', '.') }}</td>
                            <td>{{ number_format($row['persen_p'], 2, ',', '.') }}%</td>
                            <td>{{ number_format($row['persen_k'], 2, ',', '.') }}%</td>
                        </tr>
                    @endforeach
                </tbody>
                <tfoot>
                    <tr>
                        <td colspan="2" class="col-nama">UID JABAR</td>
                        <td class="grp-start-outer">{{ number_format($totalRingkasan['p']['plg'], 0, ',', '.') }}</td>
                        <td>{{ number_format($totalRingkasan['p']['kwh'], 0, ',', '.') }}</td>
                        <td>{{ number_format($totalRingkasan['p']['ts'], 0, ',', '.') }}</td>
                        <td class="grp-start-outer">{{ number_format($totalRingkasan['k']['plg'], 0, ',', '.') }}</td>
                        <td>{{ number_format($totalRingkasan['k']['kwh'], 0, ',', '.') }}</td>
                        <td>{{ number_format($totalRingkasan['k']['ts'], 0, ',', '.') }}</td>
                        <td class="grp-start-outer">{{ number_format($totalRingkasan['total_kwh'], 0, ',', '.') }}</td>
                        <td>{{ number_format($totalRingkasan['persen_p'], 2, ',', '.') }}%</td>
                        <td>{{ number_format($totalRingkasan['persen_k'], 2, ',', '.') }}%</td>
                    </tr>
                </tfoot>
            </table>
        @else
            <p style="text-align:center;color:#9aa4c2;padding:32px;font-size:13px;">Belum ada data untuk filter ini.</p>
        @endif
    </div>
    </div>
</div>

@endsection

@push('scripts')
@include('laporan.partials.copy-image-script')
@endpush