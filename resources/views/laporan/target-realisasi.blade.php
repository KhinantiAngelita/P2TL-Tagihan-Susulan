@extends('layouts.app')
@section('title', 'Target vs Realisasi')

@section('breadcrumb')
    <a href="{{ route('dashboard') }}">Beranda</a>
    <span class="sep">›</span>
    <strong>Target vs Realisasi</strong>
@endsection

@push('styles')
<style>
    .tr-card {
        padding: 24px;
        background: #fff;
        border: 1px solid var(--border);
        border-radius: 14px;
        box-sizing: border-box;
    }
    .tr-table-scroll { overflow-x: auto; border-radius: 10px; border: 1px solid var(--border); }
    .tr-table { width: 100%; border-collapse: collapse; font-size: 13px; min-width: 600px; }
    .tr-table th, .tr-table td {
        padding: 10px 14px;
        text-align: center;
        border-bottom: 1px solid #eef0f6;
        border-right: 1px solid #eef0f6;
        white-space: nowrap;
    }
    .tr-table thead th {
        background: #0b3d91;
        color: #fff;
        font-weight: 700;
        font-size: 11px;
        text-transform: uppercase;
        vertical-align: middle;
    }
    .tr-table th.col-no, .tr-table td.col-no { width: 42px; }
    .tr-table th.col-up, .tr-table td.col-up { text-align: left; font-weight: 700; color: #1b2559; min-width: 170px; }
    .tr-table thead th.col-up { color: #fff; }
    .tr-table tbody tr:nth-child(even) { background: #f8f9fc; }
    .tr-table tbody tr:hover { background: #eef2fb; }
    .tr-table td.col-persen { font-weight: 800; color: #fff; }
    .tr-table td.persen-hijau { background: #34c77b; }
    .tr-table td.persen-kuning { background: #ffce3a; color: #1b2559; }
    .tr-table tfoot td { font-weight: 800; background: #0b3d91; color: #fff; border-bottom: none; }

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
        <h2 style="margin:0 0 4px;font-size:22px;">Target vs Realisasi KWH Per ULP</h2>
        <p style="color:#6b7690;margin:0;font-size:14px;">TW {{ ['I','II','III','IV'][$twAktif - 1] }} {{ $tahunAktif }}</p>
    </div>

    <form method="GET" style="display:flex;gap:8px;flex-wrap:wrap;">
        <div class="filter-wrap">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="4" width="18" height="18" rx="2"/><path d="M16 2v4M8 2v4M3 10h18"/></svg>
            <select name="tw" onchange="this.form.submit()" class="filter-select">
                <option value="1" {{ $twAktif === 1 ? 'selected' : '' }}>Triwulan I</option>
                <option value="2" {{ $twAktif === 2 ? 'selected' : '' }}>Triwulan II</option>
                <option value="3" {{ $twAktif === 3 ? 'selected' : '' }}>Triwulan III</option>
                <option value="4" {{ $twAktif === 4 ? 'selected' : '' }}>Triwulan IV</option>
            </select>
        </div>
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

<div class="tr-card">
    <div class="tr-table-scroll">
        @if (count($rows) > 0)
            <table class="tr-table">
                <thead>
                    <tr>
                        <th class="col-no" rowspan="2">No</th>
                        <th class="col-up" rowspan="2">Unit Pelaksana</th>
                        <th colspan="3">TW {{ ['I','II','III','IV'][$twAktif - 1] }}</th>
                    </tr>
                    <tr>
                        <th>Target</th><th>Realisasi</th><th>%</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($rows as $i => $row)
                        <tr>
                            <td class="col-no">{{ $i + 1 }}</td>
                            <td class="col-up">{{ $row['nama'] }}</td>
                            <td>{{ number_format($row['target'], 0, ',', '.') }}</td>
                            <td>{{ number_format($row['realisasi'], 0, ',', '.') }}</td>
                            <td class="col-persen {{ $row['persen'] >= 100 ? 'persen-hijau' : 'persen-kuning' }}">
                                {{ number_format($row['persen'], 2, ',', '.') }}%
                            </td>
                        </tr>
                    @endforeach
                </tbody>
                <tfoot>
                    <tr>
                        <td colspan="2">UID JABAR</td>
                        <td>{{ number_format($totalTarget, 0, ',', '.') }}</td>
                        <td>{{ number_format($totalRealisasi, 0, ',', '.') }}</td>
                        <td class="col-persen {{ $totalPersen >= 100 ? 'persen-hijau' : 'persen-kuning' }}">
                            {{ number_format($totalPersen, 2, ',', '.') }}%
                        </td>
                    </tr>
                </tfoot>
            </table>
        @else
            <p style="text-align:center;color:#9aa4c2;padding:32px;font-size:13px;">Belum ada data target/realisasi untuk periode ini.</p>
        @endif
    </div>
</div>

@endsection