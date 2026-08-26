@extends('layouts.app')
@section('title', 'Presentase Pencapaian')

@section('breadcrumb')
    <a href="{{ route('dashboard') }}">Beranda</a>
    <span class="sep">›</span>
    <strong>Data Pencapaian</strong>
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

    /* Judul halaman — clamp biar gak makan tempat di layar sempit,
       samain sama Trend kWh/Rp TS. */
    .trend-page-title { font-size: clamp(18px, 4.2vw, 22px); margin: 0 0 4px; }

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

    .trend-filter-left .info-icon {
        display: flex;
        align-items: center;
        justify-content: center;
        border-radius: 10px;
        flex-shrink: 0;
    }

    /* ===== Card "Rincian per Bulan" ===== */
    .trend-table-card { padding: 0; overflow: hidden; }
    .trend-table-head {
        display: flex; align-items: center; justify-content: space-between; gap: 12px;
        padding: 18px 22px; border-bottom: 1px solid var(--border); flex-wrap: wrap;
        background: #fafbfe;
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
    .trend-table-legend { display: flex; align-items: center; gap: 14px; font-size: 11.5px; color: #6b7690; }
    .trend-table-legend span { display: inline-flex; align-items: center; gap: 5px; }
    .trend-table-legend i { width: 8px; height: 8px; border-radius: 999px; display: inline-block; }
    .trend-table-legend .dot-best  { background: #16803c; }
    .trend-table-legend .dot-worst { background: #c62828; }

    /* ===== Bar ringkasan di dalam card (pengganti 4 kartu statistik
       yang dihapus) — nampilin Pencapaian Total, Rata-rata, Bulan
       Tertinggi & Terendah sebagai chip ringkas, biar informasinya
       tetap ada tanpa nambah card terpisah. ===== */
    .trend-summary-bar {
        display: flex; flex-wrap: wrap; gap: 10px;
        padding: 16px 22px; background: #fafbfe; border-bottom: 1px solid var(--border);
    }
    .trend-summary-chip {
        display: flex; align-items: center; gap: 10px;
        padding: 10px 14px; border-radius: 12px; background: #fff;
        border: 1px solid var(--border); min-width: 168px; flex: 1 1 168px;
    }
    .trend-summary-chip-icon {
        width: 30px; height: 30px; border-radius: 9px; flex-shrink: 0;
        display: flex; align-items: center; justify-content: center;
    }
    .trend-summary-chip-icon svg { width: 15px; height: 15px; }
    .trend-summary-chip.tone-blue   .trend-summary-chip-icon { background: #eaf0fb; color: #0b3d91; }
    .trend-summary-chip.tone-yellow .trend-summary-chip-icon { background: #fff6df; color: #b8860b; }
    .trend-summary-chip.tone-green  .trend-summary-chip-icon { background: #e5f7ec; color: #16803c; }
    .trend-summary-chip.tone-red    .trend-summary-chip-icon { background: #fdeaea; color: #c62828; }
    .trend-summary-chip-label { font-size: 11px; color: #9aa4c2; font-weight: 700; text-transform: uppercase; letter-spacing: .02em; }
    .trend-summary-chip-value { font-size: 14.5px; font-weight: 800; color: #1b2559; line-height: 1.25; }
    .trend-summary-chip-sub { font-size: 11px; color: #6b7690; margin-top: 1px; }

    .trend-table { width: 100%; border-collapse: collapse; }
    .trend-table thead th {
        white-space: nowrap; text-align: left; padding: 11px 22px; font-size: 11.5px;
        text-transform: uppercase; letter-spacing: .03em; color: #6b7690; font-weight: 700;
        background: #fafbfe; border-bottom: 1px solid var(--border);
        position: sticky; top: 0; z-index: 1;
    }
    .trend-table thead th:not(:first-child) { text-align: right; }
    .trend-table tbody td { padding: 13px 22px; font-size: 13.5px; color: var(--text-dark); border-bottom: 1px solid var(--border); }
    .trend-table tbody td:not(:first-child) { text-align: right; font-variant-numeric: tabular-nums; }
    .trend-table tbody tr:last-child td { border-bottom: none; }
    .trend-table tbody tr:hover td { background: #f6f8fd; }
    .trend-table tbody tr.row-best td { background: #f2faf5; }
    .trend-table tbody tr.row-best:hover td { background: #e8f6ed; }
    .trend-table tbody tr.row-worst td { background: #fdf4f4; }
    .trend-table tbody tr.row-worst:hover td { background: #fbe9e9; }
    .trend-table tbody tr.row-best td:first-child,
    .trend-table tbody tr.row-worst td:first-child { display: flex; align-items: center; gap: 6px; }
    .trend-table .row-star { font-size: 11px; line-height: 1; }

    .trend-table tfoot td {
        padding: 13px 22px; font-size: 13px; font-weight: 700; color: #1b2559;
        background: #fafbfe; border-top: 2px solid var(--border);
    }
    .trend-table tfoot td:not(:first-child) { text-align: right; font-variant-numeric: tabular-nums; }

    .diff-cell { display: inline-flex; align-items: center; gap: 4px; justify-content: flex-end; }
    .diff-cell svg { width: 12px; height: 12px; flex-shrink: 0; }

    .persen-badge {
        display: inline-flex; align-items: center; padding: 3px 10px;
        border-radius: 999px; font-size: 12.5px; font-weight: 700;
    }
    .persen-badge.tone-hijau { background: #e5f7ec; color: #16803c; }
    .persen-badge.tone-merah { background: #fdeaea; color: #c62828; }
    .persen-badge.tone-abu   { background: #eef0f6; color: #6b7690; }

    @media (max-width: 640px) {
        .trend-filter-card { padding: 14px 16px; flex-direction: column; align-items: stretch; }
        .trend-filter-left { width: 100%; }
        .trend-filter-form { width: 100%; }
        .trend-filter-form select { flex: 1; min-width: 0; }

        /* Tabel "Rincian per Bulan" jadi tumpukan card per-bulan di HP,
           daripada tabel lebar yang harus discroll ke samping. */
        .trend-table-head { padding: 16px; }
        .trend-table-legend { width: 100%; order: 3; }
        .trend-summary-bar { padding: 12px 16px; }
        .trend-summary-chip { min-width: 100%; }
        /* PERBAIKAN: .table-scroll (wrapper global, overflow-x:auto buat
           scroll tabel lebar di desktop) kadang lebarnya sepersekian pixel
           melebihi viewport di HP, jadi browser tetap nampilin jalur
           scroll horizontal tipis di kanan — kelihatan kayak garis/sliver
           "kepotong" di pinggir kanan tiap card row-best/row-worst. Di
           mode card-stack ini kita nggak butuh scroll ke samping lagi,
           jadi dimatikan total. */
        .table-scroll { overflow-x: hidden; }
        .trend-table thead { display: none; }
        .trend-table, .trend-table tbody, .trend-table tr, .trend-table td { display: block; width: 100%; }
        .trend-table tbody { padding: 10px; }
        .trend-table tbody tr {
            margin-bottom: 10px; border: 1px solid var(--border); border-radius: 12px;
            padding: 4px 14px; background: #fff;
            /* PERBAIKAN: garis "patah"/persegi nongol di pojok kanan
               baris row-best/row-worst — background warnanya ada di td
               di dalam tr, dan tanpa overflow:hidden di sini, background
               td itu nggak ikut kepotong lengkungan border-radius si tr,
               jadi pojoknya kelihatan kotak/patah walau border tr sudah
               rounded. */
            overflow: hidden;
        }
        .trend-table tbody tr:last-child { margin-bottom: 0; }
        .trend-table tbody tr.row-best { border-color: #b9e6c6; }
        .trend-table tbody tr.row-worst { border-color: #f3c2c2; }
        .trend-table tbody td {
            display: flex; align-items: center; justify-content: space-between;
            padding: 9px 0; border-bottom: 1px dashed var(--border); text-align: right;
        }
        .trend-table tbody td:first-child { display: flex; font-size: 14px; }
        .trend-table tbody tr td:last-child { border-bottom: none; }
        .trend-table tbody td::before {
            content: attr(data-label); font-size: 11px; font-weight: 700; color: #9aa4c2;
            text-transform: uppercase; letter-spacing: .03em; text-align: left;
        }
        .trend-table tbody td:first-child::before { content: none; }
        .trend-table tfoot { display: block; }
        .trend-table tfoot tr { display: block; margin: 4px 10px 10px; border-radius: 12px; background: #fafbfe; overflow: hidden; }
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

    @media (max-width: 420px) {
        .trend-filter-form { flex-direction: column; align-items: stretch; }
        .trend-filter-form select { width: 100%; }
    }
</style>
@endpush

@section('content')

<div style="display:flex;align-items:flex-start;justify-content:space-between;margin-bottom:16px;flex-wrap:wrap;gap:12px;">
    <div>
        <h2 class="trend-page-title">Data Pencapaian</h2>
        <p style="color:#6b7690;margin:0;font-size:14px;">Perbandingan nilai aktual terhadap target bulanan yang diinput di Edit Target.</p>
    </div>
</div>

{{--
    Urutan tab disamain sama index.blade: "Presentase Pencapaian"
    paling depan karena itu yang paling sering dicek duluan.
--}}
<div class="trend-tabs">
    <a href="{{ route('trend.pencapaian', request()->only('tahun', 'ulp', 'jenis')) }}" class="active">Data Pencapaian</a>
    <a href="{{ route('trend.kwh', request()->only('tahun', 'ulp')) }}">Trend kWh</a>
    <a href="{{ route('trend.ts', request()->only('tahun', 'ulp')) }}">Trend Rp TS</a>
</div>

<div class="card trend-filter-card">
    <div class="trend-filter-left">
        <div class="info-icon" style="width:34px;height:34px;background:rgba(255,255,255,.15);color:#ffce3a;">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="width:16px;height:16px;"><circle cx="12" cy="12" r="10"/><path d="m9 12 2 2 4-4"/></svg>
        </div>
        <div>
            <strong style="font-size:14px;color:#fff;">Filter Pencapaian</strong>
            <p style="margin:2px 0 0;font-size:12px;color:rgba(255,255,255,.85);">
                {{ $jenisOptions[$jenis] }} &middot; Tahun {{ $tahunAktif ?: '-' }} &middot;
                {{ $ulpAktif === 'semua' ? 'Semua ULP' : ($daftarUlp->firstWhere('kode', $ulpAktif)['nama'] ?? $ulpAktif) }}
            </p>
        </div>
    </div>

    <form method="GET" class="trend-filter-form">
        <select name="jenis" onchange="this.form.submit()">
            @foreach ($jenisOptions as $key => $label)
                <option value="{{ $key }}" {{ $jenis === $key ? 'selected' : '' }}>{{ $label }}</option>
            @endforeach
        </select>

        <select name="tahun" onchange="this.form.submit()">
            @forelse ($daftarTahun as $t)
                <option value="{{ $t }}" {{ (int) $tahunAktif === (int) $t ? 'selected' : '' }}>{{ $t }}</option>
            @empty
                <option value="">Belum ada data</option>
            @endforelse
        </select>

        <select name="ulp" onchange="this.form.submit()">
            <option value="semua" {{ $ulpAktif === 'semua' ? 'selected' : '' }}>Semua ULP</option>
            @foreach ($daftarUlp as $u)
                <option value="{{ $u['kode'] }}" {{ $ulpAktif === $u['kode'] ? 'selected' : '' }}>{{ $u['kode'] }} - {{ $u['nama'] }}</option>
            @endforeach
        </select>
    </form>
</div>

<div class="card trend-table-card">
    <div class="trend-table-head">
        <div class="trend-table-head-left">
            <div class="trend-table-head-icon">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M3 3v18h18"/><path d="M7 15l4-5 3 3 5-7"/></svg>
            </div>
            <div>
                <h3>Rincian per Bulan</h3>
                <p>Target vs aktual {{ $jenisOptions[$jenis] }} &mdash; Tahun {{ $tahunAktif ?: '-' }}</p>
            </div>
        </div>
        <div class="trend-table-legend">
            <span><i class="dot-best"></i> Bulan tertinggi</span>
            <span><i class="dot-worst"></i> Bulan terendah</span>
        </div>
    </div>

    {{-- ===== Bar ringkasan — pengganti 4 kartu statistik yang dihapus
         (Pencapaian Total, Rata-rata, Bulan Tertinggi, Bulan Terendah),
         supaya info-nya tetap ada tanpa nambah card terpisah di luar
         "Rincian per Bulan". ===== --}}
    <div class="trend-summary-bar">
        <div class="trend-summary-chip tone-blue">
            <div class="trend-summary-chip-icon">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><path d="m9 12 2 2 4-4"/></svg>
            </div>
            <div>
                <div class="trend-summary-chip-label">Pencapaian Total</div>
                <div class="trend-summary-chip-value">{{ $persenTotal !== null ? $persenTotal . '%' : 'Target belum diisi' }}</div>
                <div class="trend-summary-chip-sub">
                    {{ $jenis === 'kwh' ? number_format($totalAktual, 0, ',', '.') . ' / ' . number_format($totalTarget, 0, ',', '.') . ' KWH' : 'Rp ' . number_format($totalAktual, 0, ',', '.') . ' / Rp ' . number_format($totalTarget, 0, ',', '.') }}
                </div>
            </div>
        </div>

        <div class="trend-summary-chip tone-yellow">
            <div class="trend-summary-chip-icon">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="4" width="18" height="18" rx="2"/><path d="M16 2v4M8 2v4M3 10h18"/></svg>
            </div>
            <div>
                <div class="trend-summary-chip-label">Rata-rata Pencapaian</div>
                <div class="trend-summary-chip-value">{{ $rataRataPersen !== null ? $rataRataPersen . '%' : '-' }}</div>
                <div class="trend-summary-chip-sub">Dari bulan yang sudah ada targetnya</div>
            </div>
        </div>

        <div class="trend-summary-chip tone-green">
            <div class="trend-summary-chip-icon">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 2 3 7v6c0 5 4 8.5 9 9 5-.5 9-4 9-9V7l-9-5Z"/></svg>
            </div>
            <div>
                <div class="trend-summary-chip-label">Bulan Tertinggi</div>
                <div class="trend-summary-chip-value">{{ $bulanTertinggiLabel ?? '-' }}</div>
                <div class="trend-summary-chip-sub">{{ $bulanTertinggiPersen !== null ? $bulanTertinggiPersen . '% dari target' : 'Belum ada target' }}</div>
            </div>
        </div>

        <div class="trend-summary-chip tone-red">
            <div class="trend-summary-chip-icon">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 22 3 17V7l9-5 9 5v10Z"/><path d="M12 12v.01"/></svg>
            </div>
            <div>
                <div class="trend-summary-chip-label">Bulan Terendah</div>
                <div class="trend-summary-chip-value">{{ $bulanTerendahLabel ?? '-' }}</div>
                <div class="trend-summary-chip-sub">{{ $bulanTerendahPersen !== null ? $bulanTerendahPersen . '% dari target' : 'Belum ada target' }}</div>
            </div>
        </div>
    </div>

    @php
        // Total selisih setahun (buat baris footer tabel) — cuma dihitung
        // kalau targetnya udah diisi, biar gak nampilin angka yang salah.
        $totalSelisihTahun = $totalTarget > 0 ? ($totalAktual - $totalTarget) : null;
    @endphp

    <div class="table-scroll">
        <table class="trend-table">
            <thead>
                <tr>
                    <th>Bulan</th>
                    <th>Target</th>
                    <th>Aktual</th>
                    <th>Selisih</th>
                    <th>% Pencapaian</th>
                </tr>
            </thead>
            <tbody>
            @forelse ($tabelBulanan as $baris)
                @php
                    $rowClass = '';
                    if ($bulanTertinggiLabel && $baris['label'] === $bulanTertinggiLabel) {
                        $rowClass = 'row-best';
                    } elseif ($bulanTerendahLabel && $baris['label'] === $bulanTerendahLabel) {
                        $rowClass = 'row-worst';
                    }
                @endphp
                <tr class="{{ $rowClass }}">
                    <td data-label="Bulan">
                        @if ($rowClass === 'row-best')
                            <span class="row-star" title="Bulan tertinggi">🟢</span>
                        @elseif ($rowClass === 'row-worst')
                            <span class="row-star" title="Bulan terendah">🔴</span>
                        @endif
                        <strong>{{ $baris['label'] }}</strong>
                    </td>
                    <td data-label="Target">
                        @if ($baris['target'] > 0)
                            {{ $jenis === 'kwh' ? number_format($baris['target'], 0, ',', '.') : 'Rp ' . number_format($baris['target'], 0, ',', '.') }}
                        @else
                            <span style="color:#9aa4c2;">Belum diisi</span>
                        @endif
                    </td>
                    <td data-label="Aktual">{{ $jenis === 'kwh' ? number_format($baris['aktual'], 0, ',', '.') : 'Rp ' . number_format($baris['aktual'], 0, ',', '.') }}</td>
                    <td data-label="Selisih" style="color:{{ $baris['selisih'] >= 0 ? '#16803c' : '#c62828' }};">
                        @if ($baris['target'] > 0)
                            <span class="diff-cell">
                                @if ($baris['selisih'] >= 0)
                                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"><path d="M12 19V5"/><path d="m5 12 7-7 7 7"/></svg>
                                @else
                                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"><path d="M12 5v14"/><path d="m19 12-7 7-7-7"/></svg>
                                @endif
                                {{ $baris['selisih'] >= 0 ? '+' : '' }}{{ $jenis === 'kwh' ? number_format($baris['selisih'], 0, ',', '.') : 'Rp ' . number_format($baris['selisih'], 0, ',', '.') }}
                            </span>
                        @else
                            &mdash;
                        @endif
                    </td>
                    <td data-label="% Pencapaian">
                        @if ($baris['persen'] === null)
                            <span class="persen-badge tone-abu">Belum ada target</span>
                        @else
                            <span class="persen-badge {{ $baris['persen'] >= 100 ? 'tone-hijau' : 'tone-merah' }}">{{ $baris['persen'] }}%</span>
                        @endif
                    </td>
                </tr>
            @empty
                <tr><td colspan="5" style="text-align:center;color:#9aa4c2;padding:24px;">Belum ada data untuk tahun ini.</td></tr>
            @endforelse
            </tbody>
            @if (!empty($tabelBulanan))
                <tfoot>
                    <tr>
                        <td data-label="Total">Total</td>
                        <td data-label="Target">{{ $totalTarget > 0 ? ($jenis === 'kwh' ? number_format($totalTarget, 0, ',', '.') : 'Rp ' . number_format($totalTarget, 0, ',', '.')) : '—' }}</td>
                        <td data-label="Aktual">{{ $jenis === 'kwh' ? number_format($totalAktual, 0, ',', '.') : 'Rp ' . number_format($totalAktual, 0, ',', '.') }}</td>
                        <td data-label="Selisih" style="color:{{ $totalSelisihTahun !== null && $totalSelisihTahun < 0 ? '#c62828' : '#16803c' }};">
                            {{ $totalSelisihTahun === null ? '—' : (($totalSelisihTahun >= 0 ? '+' : '') . ($jenis === 'kwh' ? number_format($totalSelisihTahun, 0, ',', '.') : 'Rp ' . number_format($totalSelisihTahun, 0, ',', '.'))) }}
                        </td>
                        <td data-label="% Pencapaian">{{ $persenTotal !== null ? $persenTotal . '%' : '—' }}</td>
                    </tr>
                </tfoot>
            @endif
        </table>
    </div>
</div>
@endsection