@extends('layouts.app')
@section('title', 'Komposisi Temuan')

@section('breadcrumb')
    <a href="{{ route('dashboard') }}">Beranda</a>
    <span class="sep">›</span>
    <strong>Komposisi Temuan</strong>
@endsection

@push('styles')
<style>
    .komposisi-card {
        padding: 24px;
        background: #fff;
        border: 1px solid var(--border);
        border-radius: 14px;
        box-sizing: border-box;
    }
    .komposisi-table-scroll {
        overflow-x: auto;
        border-radius: 10px;
        border: 1px solid var(--border);
    }
    .komposisi-table {
        width: 100%;
        border-collapse: collapse;
        font-size: 12.5px;
        min-width: 900px;
    }
    .komposisi-table th, .komposisi-table td {
        padding: 10px 12px;
        text-align: center;
        border-bottom: 1px solid #eef0f6;
        border-right: 1px solid #eef0f6;
        white-space: nowrap;
    }
    .komposisi-table thead th {
        color: #fff;
        font-weight: 700;
        font-size: 11px;
        text-transform: uppercase;
    }
    .komposisi-table thead tr:first-child th.col-no,
    .komposisi-table thead tr:first-child th.col-up3 { background: #0b3d91; vertical-align: middle; }
    .komposisi-table thead th.grp-p { background: #b3001f; }
    .komposisi-table thead th.grp-k { background: #8a6d1f; }
    .komposisi-table thead th.grp-total { background: #0b1f4d; }

    .komposisi-table th.col-no, .komposisi-table td.col-no { width: 42px; }
    .komposisi-table th.col-up3, .komposisi-table td.col-up3 { text-align: left; font-weight: 700; color: #1b2559; min-width: 150px; }
    .komposisi-table thead th.col-up3 { color: #fff; }

    .komposisi-table tbody tr:nth-child(even) { background: #f8f9fc; }
    .komposisi-table tbody tr:hover { background: #eef2fb; }

    .komposisi-table td.cell-persen { position: relative; text-align: right; padding-right: 10px; }
    .komposisi-persen-bar {
        position: absolute; left: 4px; top: 4px; bottom: 4px;
        width: calc(var(--pct) * 0.01 * 30px);
        max-width: 30px;
        background: #34c77b;
        border-radius: 3px;
        opacity: .8;
    }
    .komposisi-persen-text { position: relative; z-index: 1; }

    .komposisi-table tfoot td {
        font-weight: 800;
        background: #0b3d91;
        color: #fff;
        border-bottom: none;
    }

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
        background: #fff;
        appearance: none;
    }
</style>
@endpush

@section('content')

<div style="display:flex;align-items:flex-start;justify-content:space-between;margin-bottom:22px;flex-wrap:wrap;gap:12px;">
    <div>
        <h2 style="margin:0 0 4px;font-size:22px;">Komposisi Temuan Gol P & K Per UP3</h2>
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

<div class="komposisi-card">
    <div class="komposisi-table-scroll">
        @if (count($rows) > 0)
            <table class="komposisi-table">
                <thead>
                    <tr>
                        <th rowspan="2" class="col-no">No</th>
                        <th rowspan="2" class="col-up3">UP3</th>
                        <th colspan="3" class="grp-p">Temuan P</th>
                        <th colspan="3" class="grp-k">Temuan K</th>
                        <th colspan="3" class="grp-total">Total</th>
                    </tr>
                    <tr>
                        <th class="grp-p">PLG</th><th class="grp-p">KWH</th><th class="grp-p">TS</th>
                        <th class="grp-k">PLG</th><th class="grp-k">KWH</th><th class="grp-k">TS</th>
                        <th class="grp-total">KWH</th><th class="grp-total">% P KWH</th><th class="grp-total">% K KWH</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($rows as $i => $row)
                        <tr>
                            <td class="col-no">{{ $i + 1 }}</td>
                            <td class="col-up3">{{ $row['nama'] }}</td>

                            <td class="grp-p">{{ number_format($row['p']['plg'], 0, ',', '.') }}</td>
                            <td class="grp-p">{{ number_format($row['p']['kwh'], 0, ',', '.') }}</td>
                            <td class="grp-p">{{ number_format($row['p']['ts'], 0, ',', '.') }}</td>

                            <td class="grp-k">{{ number_format($row['k']['plg'], 0, ',', '.') }}</td>
                            <td class="grp-k">{{ number_format($row['k']['kwh'], 0, ',', '.') }}</td>
                            <td class="grp-k">{{ number_format($row['k']['ts'], 0, ',', '.') }}</td>

                            <td class="grp-total">{{ number_format($row['total_kwh'], 0, ',', '.') }}</td>
                            <td class="grp-total cell-persen">
                                <span class="komposisi-persen-bar" style="--pct: {{ $row['persen_p'] }}%"></span>
                                <span class="komposisi-persen-text">{{ number_format($row['persen_p'], 2, ',', '.') }}%</span>
                            </td>
                            <td class="grp-total cell-persen">
                                <span class="komposisi-persen-bar" style="--pct: {{ $row['persen_k'] }}%"></span>
                                <span class="komposisi-persen-text">{{ number_format($row['persen_k'], 2, ',', '.') }}%</span>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
                <tfoot>
                    <tr>
                        <td colspan="2">UID JABAR</td>
                        <td class="grp-p">{{ number_format($totalRingkasan['p']['plg'], 0, ',', '.') }}</td>
                        <td class="grp-p">{{ number_format($totalRingkasan['p']['kwh'], 0, ',', '.') }}</td>
                        <td class="grp-p">{{ number_format($totalRingkasan['p']['ts'], 0, ',', '.') }}</td>
                        <td class="grp-k">{{ number_format($totalRingkasan['k']['plg'], 0, ',', '.') }}</td>
                        <td class="grp-k">{{ number_format($totalRingkasan['k']['kwh'], 0, ',', '.') }}</td>
                        <td class="grp-k">{{ number_format($totalRingkasan['k']['ts'], 0, ',', '.') }}</td>
                        <td class="grp-total">{{ number_format($totalRingkasan['total_kwh'], 0, ',', '.') }}</td>
                        <td class="grp-total">{{ number_format($totalRingkasan['persen_p'], 2, ',', '.') }}%</td>
                        <td class="grp-total">{{ number_format($totalRingkasan['persen_k'], 2, ',', '.') }}%</td>
                    </tr>
                </tfoot>
            </table>
        @else
            <p style="text-align:center;color:#9aa4c2;padding:32px;font-size:13px;">Belum ada data untuk tahun ini.</p>
        @endif
    </div>
</div>

@endsection