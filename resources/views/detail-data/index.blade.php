@extends('layouts.app')
@section('title', 'Detail Laporan')

@section('breadcrumb')
    <a href="{{ route('dashboard') }}">Beranda</a>
    <span class="sep">›</span>
    <a href="{{ route('laporan.index') }}">Daftar Laporan</a>
    <span class="sep">›</span>
    <strong>Detail Laporan</strong>
@endsection

@push('styles')
<style>
    .info-grid { display: grid; grid-template-columns: repeat(4, 1fr); gap: 16px; }
    .info-item { display: flex; align-items: center; gap: 12px; }
    .info-icon {
        width: 40px; height: 40px; border-radius: 11px; flex-shrink: 0;
        background: #eaf0fb; color: var(--blue-primary);
        display: flex; align-items: center; justify-content: center;
    }
    .info-icon svg { width: 18px; height: 18px; }
    .info-label { font-size: 12px; color: var(--text-muted); margin: 0 0 3px; }
    .info-value { font-size: 14.5px; font-weight: 700; color: #1b2559; margin: 0; }

    /* Kartu statistik pakai .dash-stats / .dash-stat-card dari layouts/app.blade.php
       — jangan didefinisikan ulang di sini biar gak ada 2 sumber style yang beda nama
       dan style-nya gak sinkron kayak kemarin. */

    .chart-grid {
        display: grid;
        grid-template-columns: 2fr 1fr;
        gap: 16px;
        margin-bottom: 16px;
    }
    .chart-card {
        background: #fff;
        border: 1px solid var(--border);
        border-radius: 14px;
        padding: 20px 22px;
    }
    .chart-card-head {
        display: flex;
        align-items: flex-start;
        justify-content: space-between;
        margin-bottom: 14px;
    }
    .chart-card h4 { margin: 0 0 2px; font-size: 14.5px; color: #1b2559; }
    .chart-card .chart-sub { font-size: 12.5px; color: var(--text-muted); margin: 0; }
    .chart-badge {
        background: #eaf0fb; color: var(--blue-primary);
        font-size: 12px; font-weight: 600;
        padding: 5px 12px; border-radius: 999px;
        white-space: nowrap;
    }

    .table-toolbar {
        display: flex;
        align-items: center;
        justify-content: space-between;
        flex-wrap: wrap;
        gap: 10px;
        padding: 18px 22px;
        border-bottom: 1px solid var(--border);
    }
    .search-wrap { position: relative; }
    .search-wrap svg {
        position: absolute; left: 12px; top: 50%; transform: translateY(-50%);
        width: 15px; height: 15px; color: #9aa4c2; pointer-events: none;
    }
    .search-input {
        border: 1px solid var(--border);
        border-radius: 9px;
        padding: 8px 12px 8px 34px;
        font-size: 13px;
        width: 220px;
    }
    .filter-wrap { position: relative; }
    .filter-wrap svg {
        position: absolute; left: 12px; top: 50%; transform: translateY(-50%);
        width: 14px; height: 14px; color: #9aa4c2; pointer-events: none;
    }
    .filter-select {
        border: 1px solid var(--border);
        border-radius: 9px;
        padding: 8px 12px 8px 32px;
        font-size: 13px;
        background: #fff;
        appearance: none;
    }

    .data-table { width: 100%; border-collapse: collapse; font-size: 13px; }
    .data-table thead th {
        text-align: left;
        color: var(--text-muted);
        font-weight: 600;
        font-size: 12px;
        padding: 12px 22px;
        border-bottom: 1px solid var(--border);
    }
    .data-table tbody td {
        padding: 12px 22px;
        border-bottom: 1px solid #f1f3f9;
        vertical-align: middle;
    }
    .data-table tbody tr:hover { background: #f9fafd; }
    .data-table tbody tr:last-child td { border-bottom: none; }

    .gol-pill {
        display: inline-block;
        background: #eef1f8;
        color: #3d4566;
        font-size: 11.5px;
        font-weight: 600;
        padding: 3px 9px;
        border-radius: 6px;
    }
    .idpel-link { color: var(--blue-primary); font-weight: 700; text-decoration: none; }
    .idpel-link:hover { text-decoration: underline; }
    .truncate-cell { max-width: 220px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; display: inline-block; }

    .row-actions{
        display:flex;
        align-items:center;
        gap:8px;
    }

    .icon-btn{
        width:36px;
        height:36px;
        border:none;
        border-radius:10px;
        cursor:pointer;
        display:flex;
        justify-content:center;
        align-items:center;
        background:#eef4ff;
        color:#0b3d91;
        transition:.2s;
    }

    .icon-btn:hover{
        background:#0b3d91;
        color:#fff;
    }

    .icon-btn.danger{
        background:#ffe8e8;
        color:#d11a2a;
    }

    .icon-btn.danger:hover{
        background:#d11a2a;
        color:white;
    }

    @media (max-width: 900px) {
        .info-grid { grid-template-columns: repeat(2, 1fr); }
        .chart-grid { grid-template-columns: 1fr; }
    }

    .custom-modal{
        display:none;
        position:fixed;
        left:0;
        top:0;
        width:100%;
        height:100%;
        background:rgba(0,0,0,.4);
        backdrop-filter:blur(5px);
        z-index:9999;
        overflow-y:auto;
        padding:20px 0;
    }

    .custom-modal-content{
        width:900px;
        max-width:95%;
        max-height:85vh;
        margin:0 auto;
        background:white;
        border-radius:20px;
        overflow:hidden;
        display:flex;
        flex-direction:column;
        animation:popup .25s;
    }

    .modal-body{
        padding:25px;
        overflow-y:auto;
        flex:1 1 auto;
        min-height:0;
    }

    @keyframes popup{

    from{

    transform:translateY(-30px);

    opacity:0;

    }

    to{

    transform:translateY(0);

    opacity:1;

    }

    }

    .modal-section{
    background:#f8f9fc;
    border:1px solid var(--border);
    border-radius:12px;
    padding:16px 18px;
    margin-bottom:16px;
}
.modal-section:last-of-type{ margin-bottom:0; }

.section-label{
    display:flex;
    align-items:center;
    gap:8px;
    font-size:13px;
    font-weight:800;
    color:#0b3d91;
    text-transform:uppercase;
    letter-spacing:.04em;
    margin:0 0 14px;
    padding-bottom:10px;
    border-bottom:2px solid #dde4f5;
}
.section-label svg{ width:16px; height:16px; color:#0b3d91; flex-shrink:0; }

    .modal-header-blue{

    padding:20px 25px;

    background:linear-gradient(90deg,#003b94,#0f6bd9);

    display:flex;

    justify-content:space-between;

    align-items:center;

    color:white;

    }

    .close-modal{

    border:none;

    background:none;

    font-size:30px;

    color:white;

    cursor:pointer;

    }

    .modal-grid{

    display:grid;

    grid-template-columns:repeat(2,1fr);

    gap:18px;

    }

    .form-group{

    display:flex;

    flex-direction:column;

    gap:6px;

    }

    .form-group label{

    font-size:13px;

    font-weight:600;

    color:#666;

    }

    .form-group input{

    padding:12px;

    border-radius:10px;

    border:1px solid #ddd;

    background:#f8f9fb;

    }

    .total-box{

    margin-top:25px;

    padding:18px;

    background:#f5f9ff;

    border-radius:12px;

    display:flex;

    justify-content:space-between;

    align-items:center;

    }

    .total-box h2{

    margin:0;

    color:#0b3d91;

    }
    .modal-header-blue .header-icon{
    width:44px;height:44px;border-radius:12px;
    background:rgba(255,255,255,.15);
    display:flex;align-items:center;justify-content:center;
    margin-right:14px;flex-shrink:0;
}
    .modal-header-blue .header-icon svg{width:20px;height:20px;color:#ffce3a;}
    .modal-header-blue .header-text{display:flex;align-items:center;}
    .modal-header-blue h3{margin:0 0 2px;font-size:17px;}
    .modal-header-blue p{margin:0;font-size:12.5px;opacity:.85;}

    .form-group.full{grid-column:1 / -1;}
    .form-group .field-wrap{position:relative;}
    .form-group .field-wrap svg{
        position:absolute;left:12px;top:50%;transform:translateY(-50%);
        width:15px;height:15px;color:#9aa4c2;pointer-events:none;
    }
    .form-group input{padding-left:34px;}
    .form-group input[readonly]{background:#eef1f7;color:#555;}

    .modal-footer{
        display:flex;justify-content:flex-end;gap:10px;
        padding:18px 25px;border-top:1px solid var(--border);
    }
</style>
@endpush

@section('content')

<div style="display:flex;align-items:flex-start;justify-content:space-between;margin-bottom:22px;flex-wrap:wrap;gap:12px;">
    <div>
        <h2 style="margin:0 0 4px;font-size:22px;">Detail Laporan</h2>
        <p style="color:#6b7690;margin:0;font-size:14px;">
            Rekap tagihan pelanggan &mdash; Bulan {{ $laporan->bulan }} {{ $laporan->tahun }} &middot; {{ $laporan->unit_up3 }}
        </p>
    </div>
    <div style="display:flex;gap:8px;">
        <a href="#" class="btn btn-outline">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="width:15px;height:15px;"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><path d="M7 10l5 5 5-5"/><path d="M12 15V3"/></svg>
            Export
        </a>
        <a href="{{ route('detail-data.edit', $laporan) }}" class="btn">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="width:15px;height:15px;"><path d="M17 3a2.85 2.83 0 1 1 4 4L7.5 20.5 2 22l1.5-5.5Z"/></svg>
            Edit Laporan
        </a>
    </div>
</div>

@if (session('success'))
    <div style="margin-bottom:16px;border-radius:10px;background:#e6f7ea;border:1px solid #b9e6c4;color:#17803c;font-size:13.5px;padding:12px 16px;">
        {{ session('success') }}
    </div>
@endif

{{-- Info laporan --}}
<div class="card" style="padding:18px 22px;margin-bottom:22px;">
    <div class="info-grid">
        <div class="info-item">
            <div class="info-icon">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M6 22V4a1 1 0 0 1 1-1h10a1 1 0 0 1 1 1v18"/><path d="M2 22h20M10 6h.01M14 6h.01M10 10h.01M14 10h.01M10 14h.01M14 14h.01M10 18h4"/></svg>
            </div>
            <div>
                <p class="info-label">Unit Induk</p>
                <p class="info-value">{{ $laporan->unit_induk ?? '-' }}</p>
            </div>
        </div>
        <div class="info-item">
            <div class="info-icon">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M6 22V4a1 1 0 0 1 1-1h10a1 1 0 0 1 1 1v18"/><path d="M2 22h20M10 6h.01M14 6h.01M10 10h.01M14 10h.01M10 14h.01M14 14h.01M10 18h4"/></svg>
            </div>
            <div>
                <p class="info-label">Unit UP3</p>
                <p class="info-value">{{ $laporan->unit_up3 ?? '-' }}</p>
            </div>
        </div>
        <div class="info-item">
            <div class="info-icon">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="4" width="18" height="18" rx="2"/><path d="M16 2v4M8 2v4M3 10h18"/></svg>
            </div>
            <div>
                <p class="info-label">Bulan / Tahun</p>
                <p class="info-value">{{ $laporan->bulan }} {{ $laporan->tahun }}</p>
            </div>
        </div>
        <div class="info-item">
            <div class="info-icon">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><path d="M14 2v6h6M9 13h6M9 17h6"/></svg>
            </div>
            <div>
                <p class="info-label">Judul Laporan</p>
                <p class="info-value">{{ $laporan->judul_laporan ?? '-' }}</p>
            </div>
        </div>
    </div>
</div>

{{-- Kartu statistik — sekarang pakai .dash-stats / .dash-stat-card, sama persis kayak dashboard --}}
<div class="dash-stats">
    <div class="dash-stat-card tone-yellow">
        <div class="dash-stat-top">
            <div class="dash-stat-icon">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M23 6l-9.5 9.5-5-5L1 18"/><path d="M17 6h6v6"/></svg>
            </div>
            <h3>Total Keseluruhan</h3>
        </div>
        <div class="dash-stat-value">Rp {{ number_format($laporan->total_keseluruhan, 0, ',', '.') }}</div>
        <div class="dash-stat-sub">Seluruh tagihan laporan ini</div>
    </div>

    <div class="dash-stat-card tone-blue">
        <div class="dash-stat-top">
            <div class="dash-stat-icon">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="2" y="5" width="20" height="14" rx="2"/><path d="M2 10h20"/></svg>
            </div>
            <h3>Total Tunai</h3>
        </div>
        <div class="dash-stat-value">Rp {{ number_format($laporan->total_tunai, 0, ',', '.') }}</div>
        <div class="dash-stat-sub">{{ $persenTunai }}% dari total</div>
    </div>

    <div class="dash-stat-card tone-purple">
        <div class="dash-stat-top">
            <div class="dash-stat-icon">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 2 2 7l10 5 10-5-10-5Z"/><path d="m2 17 10 5 10-5"/><path d="m2 12 10 5 10-5"/></svg>
            </div>
            <h3>Total Angsuran</h3>
        </div>
        <div class="dash-stat-value">Rp {{ number_format($laporan->total_angsuran, 0, ',', '.') }}</div>
        <div class="dash-stat-sub">{{ $persenAngsuran }}% dari total</div>
    </div>

    <div class="dash-stat-card tone-green">
        <div class="dash-stat-top">
            <div class="dash-stat-icon">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M8 6h13M8 12h13M8 18h13M3 6h.01M3 12h.01M3 18h.01"/></svg>
            </div>
            <h3>Jumlah Baris</h3>
        </div>
        <div class="dash-stat-value">{{ $laporan->jumlah_baris }}</div>
        <div class="dash-stat-sub">Data aktif</div>
    </div>
</div>

{{-- Chart: distribusi golongan & tunai vs angsuran --}}
<div class="chart-grid">
    <div class="chart-card">
        <div class="chart-card-head">
            <div>
                <h4>Distribusi per Golongan Tarif</h4>
                <p class="chart-sub">Total tagihan per golongan (Rp)</p>
            </div>
            <span class="chart-badge">{{ $laporan->bulan }} {{ $laporan->tahun }}</span>
        </div>
        <canvas id="chartGolongan" height="110"></canvas>
    </div>
    <div class="chart-card">
        <div class="chart-card-head">
            <div>
                <h4>Tunai vs Angsuran</h4>
                <p class="chart-sub">Proporsi pembayaran</p>
            </div>
        </div>
        <canvas id="chartTunaiAngsuran" height="180"></canvas>
    </div>
</div>

{{-- Chart: tren harian --}}
<div class="chart-card" style="margin-bottom:22px;">
    <div class="chart-card-head">
        <div>
            <h4>Tren Harian</h4>
            <p class="chart-sub">Tagihan tunai dan angsuran per hari &mdash; {{ $laporan->bulan }} {{ $laporan->tahun }}</p>
        </div>
    </div>
    <canvas id="chartTren" height="90"></canvas>
</div>

{{-- Tabel semua data detail --}}
<div class="card" style="padding:0;overflow:hidden;">
    <div class="table-toolbar">
        <div>
            <strong style="font-size:14.5px;color:#1b2559;">Semua Data Detail</strong>
            <div style="font-size:12.5px;color:#6b7690;margin-top:2px;">
                {{ $rows->total() }} dari {{ $laporan->jumlah_baris }} baris ditampilkan
            </div>
        </div>

        <form method="GET" style="display:flex;gap:8px;flex-wrap:wrap;">
            <div class="search-wrap">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="11" cy="11" r="8"/><path d="m21 21-4.3-4.3"/></svg>
                <input type="text" name="search" value="{{ $search }}" placeholder="Cari IDPEL atau nama..." class="search-input">
            </div>

            <div class="filter-wrap">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M22 3H2l8 9.46V19l4 2v-8.54L22 3Z"/></svg>
                <select name="golongan" onchange="this.form.submit()" class="filter-select">
                    <option value="semua" {{ $golonganAktif === 'semua' ? 'selected' : '' }}>Semua Golongan</option>
                    @foreach ($daftarGolongan as $g)
                        <option value="{{ $g }}" {{ $golonganAktif === $g ? 'selected' : '' }}>{{ $g }}</option>
                    @endforeach
                </select>
            </div>

            <button type="submit" class="btn">Cari</button>
        </form>
    </div>

    <div style="overflow-x:auto;">
        <table class="data-table">
            <thead>
                <tr>
                    <th>No</th>
                    <th>IDPEL</th>
                    <th>Nama</th>
                    <th>Gol</th>
                    <th>Alamat</th>
                    <th>Daya (VA)</th>
                    <th>Total</th>
                    <th>Tgl Register</th>
                    <th>Aksi</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($rows as $i => $row)
                    <tr>
                        <td>{{ $row->no ?? ($rows->firstItem() + $i) }}</td>
                        <td>
                            <a href="{{ route('detail-data.show', $row->id) }}" class="idpel-link">{{ $row->idpel }}</a>
                        </td>
                        <td>{{ $row->nama }}</td>
                        <td><span class="gol-pill">{{ $row->gol }}</span></td>
                        <td><span class="truncate-cell" title="{{ $row->alamat }}">{{ $row->alamat }}</span></td>
                        <td style="color:#6b7690;">{{ $row->daya }}</td>
                        <td style="font-weight:700;color:#1b2559;">Rp{{ number_format($row->total, 0, ',', '.') }}</td>
                        <td style="color:#6b7690;">{{ optional($row->tanggal_register)->format('d/m/Y') }}</td>
                        <td>
                        <div class="row-actions">

                            {{-- Detail --}}
                            <button
                                type="button"
                                class="icon-btn btn-detail"
                                data-id="{{ $row->id }}"
                                title="Detail">

                                <svg xmlns="http://www.w3.org/2000/svg"
                                    width="18"
                                    height="18"
                                    fill="none"
                                    stroke="currentColor"
                                    stroke-width="2"
                                    stroke-linecap="round"
                                    stroke-linejoin="round"
                                    viewBox="0 0 24 24">

                                    <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8S1 12 1 12z"/>
                                    <circle cx="12" cy="12" r="3"/>

                                </svg>

                            </button>


                            {{-- Edit --}}
                            <button
                                type="button"
                                class="icon-btn btn-edit"
                                data-id="{{ $row->id }}"
                                title="Edit">

                                <svg xmlns="http://www.w3.org/2000/svg"
                                    width="18"
                                    height="18"
                                    fill="none"
                                    stroke="currentColor"
                                    stroke-width="2"
                                    stroke-linecap="round"
                                    stroke-linejoin="round"
                                    viewBox="0 0 24 24">

                                    <path d="M17 3a2.85 2.83 0 1 1 4 4L7.5 20.5 2 22l1.5-5.5Z"/>

                                </svg>

                            </button>


                            {{-- Hapus --}}
                            <form
                                action="{{ route('detail-data.destroy',$row->id) }}"
                                method="POST"
                                style="display:inline"
                                onsubmit="return confirm('Yakin ingin menghapus data ini?')">

                                @csrf
                                @method('DELETE')

                                <button
                                    type="submit"
                                    class="icon-btn danger"
                                    title="Hapus">

                                    <svg xmlns="http://www.w3.org/2000/svg"
                                        width="18"
                                        height="18"
                                        fill="none"
                                        stroke="currentColor"
                                        stroke-width="2"
                                        stroke-linecap="round"
                                        stroke-linejoin="round"
                                        viewBox="0 0 24 24">

                                        <path d="M3 6h18"/>
                                        <path d="M8 6V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"/>
                                        <path d="m19 6-1 14a2 2 0 0 1-2 2H8a2 2 0 0 1-2-2L5 6"/>

                                    </svg>

                                </button>

                            </form>

                        </div>
                    </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="9" style="text-align:center;color:#9aa4c2;padding:32px;">Tidak ada data yang cocok.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    @if ($rows->hasPages())
        <div style="padding:16px 22px;border-top:1px solid var(--border);display:flex;align-items:center;justify-content:space-between;">
            <span style="font-size:12.5px;color:#6b7690;">
                Halaman {{ $rows->currentPage() }} dari {{ $rows->lastPage() }}
            </span>
            <div style="display:flex;gap:6px;">
                @if ($rows->onFirstPage())
                    <span class="btn btn-outline" style="padding:7px 14px;font-size:12.5px;opacity:.5;">‹ Sebelumnya</span>
                @else
                    <a href="{{ $rows->previousPageUrl() }}" class="btn btn-outline" style="padding:7px 14px;font-size:12.5px;">‹ Sebelumnya</a>
                @endif

                @if ($rows->hasMorePages())
                    <a href="{{ $rows->nextPageUrl() }}" class="btn btn-outline" style="padding:7px 14px;font-size:12.5px;">Selanjutnya ›</a>
                @else
                    <span class="btn btn-outline" style="padding:7px 14px;font-size:12.5px;opacity:.5;">Selanjutnya ›</span>
                @endif
            </div>
        </div>
    @endif
</div>

{{-- ================= MODAL DETAIL ================= --}}
<div id="detailModal" class="custom-modal">
    <div class="custom-modal-content" style="width:1000px;">
        <div class="modal-header-blue">
            <div class="header-text">
                <div class="header-icon">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8S1 12 1 12z"/><circle cx="12" cy="12" r="3"/></svg>
                </div>
                <div>
                    <h3>Detail Data Pelanggan</h3>
                    <p id="detailAgenda">No. Agenda: -</p>
                </div>
            </div>
            <button class="close-modal" type="button">&times;</button>
        </div>

        <div class="modal-body">

            <div class="modal-section">
                <p class="section-label">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M19 21v-2a4 4 0 0 0-4-4H9a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>
                    Data Pelanggan
                </p>
                <div class="modal-grid">
                    <div class="form-group"><label>No Agenda</label><input id="d_no_agenda" readonly></div>
                    <div class="form-group"><label>IDPEL</label><input id="d_idpel" readonly></div>
                    <div class="form-group full"><label>Nama</label><input id="d_nama" readonly></div>
                    <div class="form-group"><label>Golongan</label><input id="d_gol" readonly></div>
                    <div class="form-group"><label>Daya (VA)</label><input id="d_daya" readonly></div>
                    <div class="form-group full"><label>Alamat</label><input id="d_alamat" readonly></div>
                </div>
            </div>

            <div class="modal-section">
                <p class="section-label">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="22 12 18 12 15 21 9 3 6 12 2 12"/></svg>
                    Tagihan Susulan
                </p>
                <div class="modal-grid">
                    <div class="form-group"><label>KWH</label><input id="d_kwh" readonly></div>
                    <div class="form-group"><label>Beban (Rp)</label><input id="d_beban" readonly></div>
                    <div class="form-group"><label>KWH (Rp)</label><input id="d_kwh_rupiah" readonly></div>
                    <div class="form-group"><label>TS</label><input id="d_ts" readonly></div>
                </div>
            </div>

            <div class="modal-section">
                <p class="section-label">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="1" y="4" width="22" height="16" rx="2" ry="2"/><line x1="1" y1="10" x2="23" y2="10"/></svg>
                    Rupiah Biaya Lain-lain
                </p>
                <div class="modal-grid">
                    <div class="form-group"><label>Materai</label><input id="d_materai" readonly></div>
                    <div class="form-group"><label>Segel</label><input id="d_segel" readonly></div>
                    <div class="form-group"><label>Materia</label><input id="d_materia" readonly></div>
                    <div class="form-group"><label>RPPPJ</label><input id="d_rpppj" readonly></div>
                    <div class="form-group"><label>RPUJL</label><input id="d_rpujl" readonly></div>
                    <div class="form-group"><label>RPPPN</label><input id="d_rpppn" readonly></div>
                </div>
            </div>

            <div class="modal-section">
                <p class="section-label">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M23 6l-9.5 9.5-5-5L1 18"/><path d="M17 6h6v6"/></svg>
                    Penetapan
                </p>
                <div class="modal-grid">
                    <div class="form-group"><label>Tunai</label><input id="d_tunai" readonly></div>
                    <div class="form-group"><label>Angsuran</label><input id="d_angsuran" readonly></div>
                    <div class="form-group"><label>Tanggal Register</label><input id="d_tanggal_register" readonly></div>
                    <div class="form-group"><label>Nomor Register</label><input id="d_nomor_register" readonly></div>
                    <div class="form-group"><label>Tanggal SPH</label><input id="d_tanggal_sph" readonly></div>
                    <div class="form-group"><label>Nomor SPH</label><input id="d_nomor_sph" readonly></div>
                </div>
            </div>

            <div class="total-box">
                <span>Total</span>
                <h2 id="d_total"></h2>
            </div>

        </div>
    </div>
</div>

{{-- ================= MODAL EDIT ================= --}}
<div id="editModal" class="custom-modal">
    <div class="custom-modal-content" style="width:1000px;">
        <form id="editForm" method="POST">
            @csrf
            @method('PUT')

            <div class="modal-header-blue">
                <div class="header-text">
                    <div class="header-icon">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M17 3a2.85 2.83 0 1 1 4 4L7.5 20.5 2 22l1.5-5.5Z"/></svg>
                    </div>
                    <div>
                        <h3>Edit Data Pelanggan</h3>
                        <p>No. Agenda: <span id="editAgenda">-</span></p>
                    </div>
                </div>
                <button class="close-modal" type="button">&times;</button>
            </div>

            <div class="modal-body" style="max-height:70vh;overflow-y:auto;">

                <p class="section-label">Data Pelanggan</p>
                <div class="modal-grid">
                    <div class="form-group">
                        <label>No Agenda</label>
                        <div class="field-wrap">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="4" y1="9" x2="20" y2="9"/><line x1="4" y1="15" x2="20" y2="15"/><line x1="10" y1="3" x2="8" y2="21"/><line x1="16" y1="3" x2="14" y2="21"/></svg>
                            <input type="text" name="no_agenda" id="e_no_agenda">
                        </div>
                    </div>
                    <div class="form-group">
                        <label>IDPEL</label>
                        <div class="field-wrap">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="9" y="2" width="6" height="4" rx="1"/><path d="M19 4h-2a2 2 0 0 0-2-2H9a2 2 0 0 0-2 2H5a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2V6a2 2 0 0 0-2-2Z"/></svg>
                            <input type="text" name="idpel" id="e_idpel" required>
                        </div>
                    </div>
                    <div class="form-group full">
                        <label>Nama Pelanggan</label>
                        <div class="field-wrap">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M19 21v-2a4 4 0 0 0-4-4H9a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>
                            <input type="text" name="nama" id="e_nama" required>
                        </div>
                    </div>
                    <div class="form-group">
                        <label>Golongan</label>
                        <div class="field-wrap">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M22 3H2l8 9.46V19l4 2v-8.54L22 3Z"/></svg>
                            <input type="text" name="gol" id="e_gol">
                        </div>
                    </div>
                    <div class="form-group">
                        <label>Daya (VA)</label>
                        <div class="field-wrap">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polygon points="13 2 3 14 12 14 11 22 21 10 12 10 13 2"/></svg>
                            <input type="text" name="daya" id="e_daya">
                        </div>
                    </div>
                    <div class="form-group full">
                        <label>Alamat</label>
                        <div class="field-wrap">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M6 22V4a1 1 0 0 1 1-1h10a1 1 0 0 1 1 1v18"/><path d="M2 22h20M10 6h.01M14 6h.01M10 10h.01M14 10h.01M10 14h.01M14 14h.01M10 18h4"/></svg>
                            <input type="text" name="alamat" id="e_alamat">
                        </div>
                    </div>
                </div>

                <p class="section-label spaced">Tagihan Susulan</p>
                <div class="modal-grid">
                    <div class="form-group">
                        <label>KWH</label>
                        <div class="field-wrap"><input type="number" step="1" name="kwh" id="e_kwh"></div>
                    </div>
                    <div class="form-group">
                        <label>Beban (Rp)</label>
                        <div class="field-wrap"><input type="number" step="1" name="beban" id="e_beban"></div>
                    </div>
                    <div class="form-group">
                        <label>KWH (Rp)</label>
                        <div class="field-wrap"><input type="number" step="1" name="kwh_rupiah" id="e_kwh_rupiah"></div>
                    </div>
                    <div class="form-group">
                        <label>TS</label>
                        <div class="field-wrap"><input type="number" step="1" name="ts" id="e_ts"></div>
                    </div>
                </div>

                <p class="section-label spaced">Rupiah Biaya Lain-lain</p>
                <div class="modal-grid">
                    <div class="form-group"><label>Materai</label><div class="field-wrap"><input type="number" step="1" name="materai" id="e_materai"></div></div>
                    <div class="form-group"><label>Segel</label><div class="field-wrap"><input type="number" step="1" name="segel" id="e_segel"></div></div>
                    <div class="form-group"><label>Materia</label><div class="field-wrap"><input type="number" step="1" name="materia" id="e_materia"></div></div>
                    <div class="form-group"><label>RPPPJ</label><div class="field-wrap"><input type="number" step="1" name="rpppj" id="e_rpppj"></div></div>
                    <div class="form-group"><label>RPUJL</label><div class="field-wrap"><input type="number" step="1" name="rpujl" id="e_rpujl"></div></div>
                    <div class="form-group"><label>RPPPN</label><div class="field-wrap"><input type="number" step="1" name="rpppn" id="e_rpppn"></div></div>
                </div>

                <p class="section-label spaced">Penetapan</p>
                <div class="modal-grid">
                    <div class="form-group">
                        <label>Tunai (Rp)</label>
                        <div class="field-wrap">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="1" y="4" width="22" height="16" rx="2" ry="2"/><line x1="1" y1="10" x2="23" y2="10"/></svg>
                            <input type="number" step="1" name="tunai" id="e_tunai">
                        </div>
                    </div>
                    <div class="form-group">
                        <label>Angsuran (Rp)</label>
                        <div class="field-wrap">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polygon points="12 2 2 7 12 12 22 7 12 2"/><polyline points="2 17 12 22 22 17"/><polyline points="2 12 12 17 22 12"/></svg>
                            <input type="number" step="1" name="angsuran" id="e_angsuran">
                        </div>
                    </div>
                    <div class="form-group">
                        <label>Tanggal Register</label>
                        <div class="field-wrap">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="4" width="18" height="18" rx="2"/><path d="M16 2v4M8 2v4M3 10h18"/></svg>
                            <input type="date" name="tanggal_register" id="e_tanggal_register">
                        </div>
                    </div>
                    <div class="form-group">
                        <label>Nomor Register</label>
                        <div class="field-wrap"><input type="text" name="nomor_register" id="e_nomor_register"></div>
                    </div>
                    <div class="form-group">
                        <label>Tanggal SPH</label>
                        <div class="field-wrap">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="4" width="18" height="18" rx="2"/><path d="M16 2v4M8 2v4M3 10h18"/></svg>
                            <input type="date" name="tanggal_sph" id="e_tanggal_sph">
                        </div>
                    </div>
                    <div class="form-group">
                        <label>Nomor SPH</label>
                        <div class="field-wrap"><input type="text" name="nomor_sph" id="e_nomor_sph"></div>
                    </div>
                </div>

                <div class="total-box">
                    <div>
                        <span>Total (Tunai + Angsuran)</span>
                        <p style="margin:4px 0 0;font-size:12px;color:#9aa4c2;">Dihitung otomatis</p>
                    </div>
                    <h2 id="e_total">Rp 0</h2>
                </div>
            </div>

            <div class="modal-footer">
                <button type="button" class="btn btn-outline close-modal">Batal</button>
                <button type="submit" class="btn">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="width:15px;height:15px;"><polyline points="20 6 9 17 4 12"/></svg>
                    Simpan Perubahan
                </button>
            </div>
        </form>
    </div>
</div>

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
    new Chart(document.getElementById('chartGolongan'), {
        type: 'bar',
        data: {
            labels: {!! json_encode($distribusiGolongan->keys()) !!},
            datasets: [{
                data: {!! json_encode($distribusiGolongan->values()) !!},
                backgroundColor: ['#ffce3a', '#0b3d91', '#3d63b8', '#6b8fd6'],
                borderRadius: 4,
            }]
        },
        options: {
            plugins: { legend: { display: false } },
            scales: { y: { beginAtZero: true } }
        }
    });

    new Chart(document.getElementById('chartTunaiAngsuran'), {
        type: 'doughnut',
        data: {
            labels: ['Tunai', 'Angsuran'],
            datasets: [{
                data: [{{ $laporan->total_tunai }}, {{ $laporan->total_angsuran }}],
                backgroundColor: ['#0b3d91', '#ffce3a'],
            }]
        },
        options: {
            cutout: '70%',
            plugins: { legend: { position: 'bottom' } }
        }
    });

    new Chart(document.getElementById('chartTren'), {
        type: 'line',
        data: {
            labels: {!! json_encode($trenHarian->pluck('tanggal')->map(fn ($t) => \Carbon\Carbon::parse($t)->format('d M'))) !!},
            datasets: [
                {
                    label: 'Tunai',
                    data: {!! json_encode($trenHarian->pluck('tunai')) !!},
                    borderColor: '#0b3d91',
                    tension: 0.3,
                },
                {
                    label: 'Angsuran',
                    data: {!! json_encode($trenHarian->pluck('angsuran')) !!},
                    borderColor: '#ffce3a',
                    tension: 0.3,
                }
            ]
        },
        options: {
            plugins: { legend: { position: 'top' } },
            scales: { y: { beginAtZero: true } }
        }
    });

    document.addEventListener('DOMContentLoaded', function () {

    const showUrlTemplate   = @json(route('detail-data.show', ['detail' => '__ID__']));
    const updateUrlTemplate = @json(route('detail-data.update', ['detail' => '__ID__']));

    function formatRupiah(n){
        return 'Rp ' + Number(n || 0).toLocaleString('id-ID');
    }
    function fmtDate(d){ return d ? d.substring(0, 10) : ''; }

    function fillDetail(data){
        document.getElementById('detailAgenda').textContent = 'No. Agenda: ' + (data.no_agenda ?? '-');
        document.getElementById('d_no_agenda').value = data.no_agenda ?? '';
        document.getElementById('d_idpel').value = data.idpel ?? '';
        document.getElementById('d_nama').value = data.nama ?? '';
        document.getElementById('d_gol').value = data.gol ?? '';
        document.getElementById('d_daya').value = data.daya ?? '';
        document.getElementById('d_alamat').value = data.alamat ?? '';
        document.getElementById('d_kwh').value = data.kwh ?? 0;
        document.getElementById('d_beban').value = formatRupiah(data.beban);
        document.getElementById('d_kwh_rupiah').value = formatRupiah(data.kwh_rupiah);
        document.getElementById('d_ts').value = formatRupiah(data.ts);
        document.getElementById('d_materai').value = formatRupiah(data.materai);
        document.getElementById('d_segel').value = formatRupiah(data.segel);
        document.getElementById('d_materia').value = formatRupiah(data.materia);
        document.getElementById('d_rpppj').value = formatRupiah(data.rpppj);
        document.getElementById('d_rpujl').value = formatRupiah(data.rpujl);
        document.getElementById('d_rpppn').value = formatRupiah(data.rpppn);
        document.getElementById('d_tunai').value = formatRupiah(data.tunai);
        document.getElementById('d_angsuran').value = formatRupiah(data.angsuran);
        document.getElementById('d_tanggal_register').value = fmtDate(data.tanggal_register);
        document.getElementById('d_nomor_register').value = data.nomor_register ?? '';
        document.getElementById('d_tanggal_sph').value = fmtDate(data.tanggal_sph);
        document.getElementById('d_nomor_sph').value = data.nomor_sph ?? '';
        document.getElementById('d_total').textContent = formatRupiah(data.total);
    }

    function fillEdit(data, id){
        document.getElementById('editForm').action = updateUrlTemplate.replace('__ID__', id);
        document.getElementById('editAgenda').textContent = data.no_agenda ?? '-';
        document.getElementById('e_no_agenda').value = data.no_agenda ?? '';
        document.getElementById('e_idpel').value = data.idpel ?? '';
        document.getElementById('e_nama').value = data.nama ?? '';
        document.getElementById('e_gol').value = data.gol ?? '';
        document.getElementById('e_daya').value = data.daya ?? '';
        document.getElementById('e_alamat').value = data.alamat ?? '';
        document.getElementById('e_kwh').value = data.kwh ?? 0;
        document.getElementById('e_beban').value = data.beban ?? 0;
        document.getElementById('e_kwh_rupiah').value = data.kwh_rupiah ?? 0;
        document.getElementById('e_ts').value = data.ts ?? 0;
        document.getElementById('e_materai').value = data.materai ?? 0;
        document.getElementById('e_segel').value = data.segel ?? 0;
        document.getElementById('e_materia').value = data.materia ?? 0;
        document.getElementById('e_rpppj').value = data.rpppj ?? 0;
        document.getElementById('e_rpujl').value = data.rpujl ?? 0;
        document.getElementById('e_rpppn').value = data.rpppn ?? 0;
        document.getElementById('e_tunai').value = data.tunai ?? 0;
        document.getElementById('e_angsuran').value = data.angsuran ?? 0;
        document.getElementById('e_tanggal_register').value = fmtDate(data.tanggal_register);
        document.getElementById('e_nomor_register').value = data.nomor_register ?? '';
        document.getElementById('e_tanggal_sph').value = fmtDate(data.tanggal_sph);
        document.getElementById('e_nomor_sph').value = data.nomor_sph ?? '';
        recalcEditTotal();
    }

    function recalcEditTotal(){
        const tunai = parseFloat(document.getElementById('e_tunai').value) || 0;
        const angsuran = parseFloat(document.getElementById('e_angsuran').value) || 0;
        document.getElementById('e_total').textContent = formatRupiah(tunai + angsuran);
    }
    document.getElementById('e_tunai').addEventListener('input', recalcEditTotal);
    document.getElementById('e_angsuran').addEventListener('input', recalcEditTotal);

    // Event delegation — tetap jalan walau tabel di-render ulang via pagination/filter
    document.addEventListener('click', function (e) {
        const detailBtn = e.target.closest('.btn-detail');
        const editBtn   = e.target.closest('.btn-edit');
        const closeBtn  = e.target.closest('.close-modal');

        if (detailBtn) {
            const id = detailBtn.dataset.id;
            fetch(showUrlTemplate.replace('__ID__', id))
                .then(res => { if (!res.ok) throw new Error(res.status); return res.json(); })
                .then(data => { fillDetail(data); document.getElementById('detailModal').style.display = 'block'; })
                .catch(err => { console.error(err); alert('Gagal memuat detail data.'); });
        }

        if (editBtn) {
            const id = editBtn.dataset.id;
            fetch(showUrlTemplate.replace('__ID__', id))
                .then(res => { if (!res.ok) throw new Error(res.status); return res.json(); })
                .then(data => { fillEdit(data, id); document.getElementById('editModal').style.display = 'block'; })
                .catch(err => { console.error(err); alert('Gagal memuat data untuk edit.'); });
        }

        if (closeBtn) {
            closeBtn.closest('.custom-modal').style.display = 'none';
        }

        if (e.target.classList.contains('custom-modal')) {
            e.target.style.display = 'none';
        }
    });

});

    // ---- Tutup modal (tombol X, Batal, klik backdrop) ----
    document.querySelectorAll('.close-modal').forEach(function(btn){
        btn.addEventListener('click', function(){
            this.closest('.custom-modal').style.display = 'none';
        });
    });
    window.addEventListener('click', function(e){
        document.querySelectorAll('.custom-modal').forEach(function(modal){
            if (e.target === modal) modal.style.display = 'none';
        });
    });

</script>
@endpush

@endsection