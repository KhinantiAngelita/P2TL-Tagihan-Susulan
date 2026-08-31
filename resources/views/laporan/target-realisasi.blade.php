@extends('layouts.app')
@section('title', 'Target vs Realisasi')

@section('breadcrumb')
    <a href="{{ route('dashboard') }}">Beranda</a>
    <span class="sep">›</span>
    <strong>Target vs Realisasi</strong>
@endsection

@push('styles')
<style>
    /* Styling Kartu & Tabel disamakan dengan Menu Pencapaian */
    .trend-page-title { font-size: clamp(18px, 4.2vw, 22px); margin: 0 0 4px; color: #1b2559; font-weight: 700; }

    .trend-table-card { padding: 0; overflow: hidden; background: #fff; border: 1px solid var(--border); border-radius: 14px; box-sizing: border-box; }
    .trend-table-head {
        display: flex; align-items: center; justify-content: space-between; gap: 12px;
        padding: 18px 22px; border-bottom: 1px solid var(--border); flex-wrap: wrap;
    }
    .trend-table-head-left { display: flex; align-items: center; gap: 12px; min-width: 0; }
    .trend-table-head-icon {
        width: 34px; height: 34px; border-radius: 10px; flex-shrink: 0;
        display: flex; align-items: center; justify-content: center;
        background: #eaf0fb; color: #0b3d91;
    }
    .trend-table-head-icon svg { width: 16px; height: 16px; }
    .trend-table-head h3 { margin: 0; font-size: 14.5px; color: #1b2559; }
    .trend-table-head p { margin: 2px 0 0; font-size: 12px; color: #6b7690; }

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
    }
    .copy-btn svg { width: 14px; height: 14px; flex-shrink: 0; }
    .copy-btn:hover { background: #eaf0fb; }
    .copy-btn:disabled { opacity: .6; cursor: default; }

    /* ===== Garis pemisah kolom soft — border-right tipis di semua
       th/td, dihilangkan di kolom terakhir supaya gak nempel tepi. ===== */
    .trend-table { width: 100%; border-collapse: collapse; }
    .trend-table th, .trend-table td { border-right: 1px solid #eef0f6; }
    .trend-table th:last-child, .trend-table td:last-child { border-right: none; }

    .trend-table thead th {
        white-space: nowrap; text-align: left; padding: 11px 22px; font-size: 11.5px;
        text-transform: uppercase; letter-spacing: .03em; color: #6b7690; font-weight: 800;
        background: #eef0f6; border-bottom: 1px solid var(--border);
        position: sticky; top: 0; z-index: 1;
    }
    /* Kolom No dan Unit Pelaksana rata kiri, sisanya rata kanan.
       Dipakai :not(:first-child) + :not(:nth-child(2)) supaya aman
       walau ada colspan di baris header (bukan dihitung by index). */
    .trend-table thead th.text-right,
    .trend-table thead tr:not(.sub-row) th:nth-child(n+3) { text-align: right; }
    .trend-table thead tr:not(.sub-row) th:nth-child(3) { text-align: center; }

    /* ===== Warna soft di header kolom Target & Realisasi (sub-row
       kedua saja) — biru lembut untuk Target, hijau lembut untuk
       Realisasi, teks di-bold lebih tegas biar menonjol. Kolom %
       dibiarkan netral karena badge-nya sendiri sudah berwarna. ===== */
    .trend-table thead tr.sub-row th {
        text-align: right; font-weight: 800;
    }
    .trend-table thead tr.sub-row th.col-target {
        background: #e4ebfb; color: #1d4ed8; font-weight: 800; border-right-color: rgba(255,255,255,.6);
    }
    .trend-table thead tr.sub-row th.col-realisasi {
        background: #e3f6ea; color: #15803d; font-weight: 800; border-right-color: rgba(255,255,255,.6);
    }

    .trend-table tbody td { padding: 13px 22px; font-size: 13.5px; color: var(--text-dark, #1b2559); border-bottom: 1px solid var(--border); }
    .trend-table tbody td.text-left { text-align: left; font-weight: 600; }
    .trend-table tbody td:not(.text-left) { text-align: right; font-variant-numeric: tabular-nums; }
    .trend-table tbody tr:last-child td { border-bottom: none; }
    .trend-table tbody tr:hover td { background: #f6f8fd; }

    .trend-table tfoot td {
        padding: 13px 22px; font-size: 13px; font-weight: 700; color: #1b2559;
        background: #fafbfe; border-top: 2px solid var(--border);
    }
    /* PERBAIKAN: dulu pakai td:nth-child(n+3), padahal sel pertama
       tfoot pakai colspan="2" — nth-child tidak menghitung colspan,
       jadi kolom Target ikut ke-skip dari rata kanan. Sekarang pakai
       :not(:first-child) supaya kebal terhadap colspan. */
    .trend-table tfoot td:first-child { text-align: left; }
    .trend-table tfoot td:not(:first-child) { text-align: right; font-variant-numeric: tabular-nums; }

    /* Badge Persentase Lembut (Soft Palette) ala Menu Pencapaian */
    .persen-badge {
        display: inline-flex; align-items: center; padding: 3px 10px;
        border-radius: 999px; font-size: 12.5px; font-weight: 700;
    }
    .persen-badge.tone-hijau { background: #e5f7ec; color: #16803c; }
    .persen-badge.tone-kuning { background: #fff6df; color: #b8860b; }
    .persen-badge.tone-merah  { background: #fdeaea; color: #c62828; }
    .persen-badge.tone-abu    { background: #eef0f6; color: #6b7690; font-weight: 500; font-size: 11px; }

    /* Filter Tahun Header */
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

    /* Responsif HP */
    @media (max-width: 640px) {
        .trend-table-head { padding: 16px; flex-direction: column; align-items: stretch; }
        .trend-table thead { display: none; }
        .trend-table, .trend-table tbody, .trend-table tr, .trend-table td { display: block; width: 100%; border-right: none; }
        .trend-table tbody { padding: 10px; }
        .trend-table tbody tr {
            margin-bottom: 10px; border: 1px solid var(--border); border-radius: 12px;
            padding: 4px 14px; background: #fff;
        }
        .trend-table tbody tr:last-child { margin-bottom: 0; }
        .trend-table tbody td {
            display: flex; align-items: center; justify-content: space-between;
            padding: 9px 0; border-bottom: 1px dashed var(--border); text-align: right;
        }
        .trend-table tbody td.text-left { display: flex; font-size: 14px; }
        .trend-table tbody td:first-child::before { content: none; }
        .trend-table tbody tr td:last-child { border-bottom: none; }
        .trend-table tbody td::before {
            content: attr(data-label); font-size: 11px; font-weight: 700; color: #9aa4c2;
            text-transform: uppercase; letter-spacing: .03em; text-align: left;
        }
        .trend-table tfoot { display: block; }
        .trend-table tfoot tr { display: block; margin: 4px 10px 10px; border-radius: 12px; background: #fafbfe; }
        .trend-table tfoot td {
            display: flex; align-items: center; justify-content: space-between; text-align: right;
            border-top: none; padding: 8px 14px;
        }
        .trend-table tfoot td::before {
            content: attr(data-label); font-size: 11px; font-weight: 700; color: #6b7690;
            text-transform: uppercase; letter-spacing: .03em; text-align: left;
        }
        .trend-table tfoot td:first-child::before { content: none; }
    }
</style>
@endpush

@section('content')

<div style="display:flex;align-items:flex-start;justify-content:space-between;margin-bottom:16px;flex-wrap:wrap;gap:12px;">
    <div>
        <h2 class="trend-page-title">Target vs Realisasi KWH Per ULP</h2>
        <p style="color:#6b7690;margin:0;font-size:14px;">{{ $filterInfoText }}</p>
    </div>
</div>

{{-- Komponen Filter Periode & ULP Terpadu --}}
@php
    $tampilkanTahunFilter = true;
@endphp
@include('laporan.partials.filter-periode-ulp')

<div class="card trend-table-card">
    <div class="trend-table-head">
        <div class="trend-table-head-left">
            <div class="trend-table-head-icon">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M3 3v18h18"/><path d="M7 15l4-5 3 3 5-7"/></svg>
            </div>
            <div>
                <h3>Rincian ULP</h3>
                <p>Target vs Realisasi KWH per Unit Pelaksana</p>
            </div>
        </div>
        <div>
            <button type="button" class="copy-btn" onclick="salinTabelGambar('capture-target-realisasi', this, 'Target vs Realisasi KWH Per ULP')">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="9" y="9" width="13" height="13" rx="2"/><path d="M5 15H4a2 2 0 0 1-2-2V4a2 2 0 0 1 2-2h9a2 2 0 0 1 2 2v1"/></svg>
                Salin Gambar
            </button>
        </div>
    </div>

    <div id="capture-target-realisasi">
    <div class="table-scroll">
        @if (count($rows) > 0)
            <table class="trend-table" id="tabel-target-realisasi">
                <thead>
                    <tr>
                        <th rowspan="2" style="width: 50px;">No</th>
                        <th rowspan="2">Unit Pelaksana</th>
                        <th colspan="3">Periode Terpilih</th>
                    </tr>
                    <tr class="sub-row">
                        <th class="col-target">Target</th>
                        <th class="col-realisasi">Aktual</th>
                        <th>%</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($rows as $i => $row)
                        @php
                            // Logika Warna Badge Persentase (Soft Palette)
                            // <70 Merah, 70-99.99 Kuning, >=100 Hijau
                            if ($row['target'] <= 0) {
                                $classPersen = 'tone-abu';
                            } elseif ($row['persen'] < 70) {
                                $classPersen = 'tone-merah';
                            } elseif ($row['persen'] < 100) {
                                $classPersen = 'tone-kuning';
                            } else {
                                $classPersen = 'tone-hijau';
                            }
                        @endphp
                        <tr>
                            <td data-label="No">{{ $i + 1 }}</td>
                            <td data-label="Unit Pelaksana" class="text-left">{{ $row['nama'] }}</td>
                            <td data-label="Target">{{ number_format($row['target'], 0, ',', '.') }}</td>
                            <td data-label="Realisasi">{{ number_format($row['realisasi'], 0, ',', '.') }}</td>
                            <td data-label="% Pencapaian">
                                @if ($row['target'] <= 0)
                                    <span class="persen-badge tone-abu">Belum ada target</span>
                                @else
                                    <span class="persen-badge {{ $classPersen }}">{{ number_format($row['persen'], 2, ',', '.') }}%</span>
                                @endif
                            </td>
                        </tr>
                    @endforeach
                </tbody>
                <tfoot>
                    @php
                        if ($totalTarget <= 0) {
                            $classTotalPersen = 'tone-abu';
                        } elseif ($totalPersen < 70) {
                            $classTotalPersen = 'tone-merah';
                        } elseif ($totalPersen < 100) {
                            $classTotalPersen = 'tone-kuning';
                        } else {
                            $classTotalPersen = 'tone-hijau';
                        }
                    @endphp
                    <tr>
                        <td data-label="Total" colspan="2">UID JABAR</td>
                        <td data-label="Target">{{ number_format($totalTarget, 0, ',', '.') }}</td>
                        <td data-label="Realisasi">{{ number_format($totalRealisasi, 0, ',', '.') }}</td>
                        <td data-label="% Pencapaian">
                            @if ($totalTarget <= 0)
                                <span class="persen-badge tone-abu">Belum ada target</span>
                            @else
                                <span class="persen-badge {{ $classTotalPersen }}">{{ number_format($totalPersen, 2, ',', '.') }}%</span>
                            @endif
                        </td>
                    </tr>
                </tfoot>
            </table>
        @else
            <p style="text-align:center;color:#9aa4c2;padding:32px;font-size:13px;">Belum ada data target/realisasi untuk filter ini.</p>
        @endif
    </div>
    </div>
</div>

@endsection

@push('scripts')
@include('laporan.partials.copy-image-script')
@endpush