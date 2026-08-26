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
    @media (max-width: 900px) {
        .info-grid { grid-template-columns: repeat(2, 1fr); }
        .chart-grid { grid-template-columns: 1fr; }
    }
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
    .chart-grid-equal {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 16px;
        margin-bottom: 16px;
    }
    @media (max-width: 900px) {
        .chart-grid-equal { grid-template-columns: 1fr; }
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

    .filter-chart-card {
        display: flex;
        align-items: center;
        justify-content: space-between;
        flex-wrap: wrap;
        gap: 12px;
        padding: 16px 22px;
        margin-bottom: 16px;
    }

    /* Varian aktif dari .filter-chart-card — dipakai buat filter rentang tanggal
       di bagian paling atas, sengaja dikasih warna beda (gradient biru) biar
       kelihatan jelas kalau filter ini berlaku juga ke kartu ringkasan di
       bawahnya, bukan cuma ke grafik & tabel. */
    .filter-chart-card--active {
        background: linear-gradient(90deg, #003b94, #0f6bd9);
        border-color: transparent;
    }
    .filter-chart-card--active .info-icon {
        background: rgba(255, 255, 255, .15);
        color: #ffce3a;
    }
    .filter-chart-card--active .date-input {
        border-color: rgba(255, 255, 255, .4);
        background: rgba(255, 255, 255, .95);
    }
    .filter-chart-card--active .sep-dash {
        color: rgba(255, 255, 255, .7);
    }
    .filter-chart-card--active .btn-outline {
        border-color: rgba(255, 255, 255, .5);
        color: #fff;
        background: transparent;
    }
    .filter-chart-card--active .btn-outline:hover {
        background: rgba(255, 255, 255, .15);
    }

    .btn-export-pdf {
        display: inline-flex;
        align-items: center;
        gap: 7px;
        background: #c2412b;
        color: #fff;
        border: 1px solid #c2412b;
        border-radius: 9px;
        font-weight: 600;
        text-decoration: none;
        transition: .2s;
    }
    .btn-export-pdf:hover {
        background: #a4331f;
        border-color: #a4331f;
        color: #fff;
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
    .date-input {
        border: 1px solid var(--border);
        border-radius: 9px;
        padding: 8px 12px;
        font-size: 13px;
        background: #fff;
        color: #1b2559;
    }
    .date-range-wrap { display: flex; align-items: center; gap: 6px; }
    .date-range-wrap .sep-dash { color: #9aa4c2; font-size: 12.5px; }

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
        color:#e0433d;
    }

    .icon-btn.danger:hover{
        background:#e0433d;
        color:white;
    }

    .icon-btn.warning{
        background:#fff1e0;
        color:#c2650a;
    }

    .icon-btn.warning:hover{
        background:#c2650a;
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
        align-items:center;       
        justify-content:center; 
    }

    .custom-modal-content{
        width:900px;
        max-width:95%;
        max-height:85vh;
        margin:0 auto;
        background:white;
        border-radius:16px;
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
    .section-title {
        font-size: 19px;
        font-weight: 800;
        color: #1b2559;
        margin: 32px 0 16px;
        padding: 0 0 10px 14px;
        border-left: 4px solid var(--blue-primary);
        border-bottom: 2px solid #eaf0fb;
    }
    .section-title:first-of-type { margin-top: 0; }

    .golongan-info-list { margin-top: 14px; }
    .golongan-info-row {
        display: flex; align-items: center; justify-content: space-between;
        padding: 8px 0; border-bottom: 1px solid #f1f3f9; font-size: 12.5px;
    }
    .golongan-info-row:last-child { border-bottom: none; }
    .golongan-info-row .gol-name { font-weight: 700; color: #1b2559; }
    .golongan-info-row .gol-detail { color: #6b7690; text-align: right; }

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

    padding:20px 22px;

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
        width:100%;              /* tambahin ini */
        box-sizing:border-box;   /* biar padding gak bikin overflow */
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
    width:42px;height:42px;border-radius:12px;
    background:rgba(255,255,255,.15);
    display:flex;align-items:center;justify-content:center;
    margin-right:14px;flex-shrink:0;
}
    .modal-header-blue .header-icon svg{width:20px;height:20px;color:#ffce3a;}
    .modal-header-blue .header-text{display:flex;align-items:center;}
    .modal-header-blue h3{margin:0 0 2px;font-size:17px;}
    .modal-header-blue p{margin:0;font-size:12.5px;opacity:.85;}

    /* Varian merah — dipakai untuk modal yang bersifat destruktif (hapus),
       supaya konsisten dengan modal konfirmasi hapus di halaman Daftar Laporan. */
    .modal-header-danger{
        background:#e0433d;
    }
    .modal-header-danger .header-icon{
        background:rgba(255,255,255,.18);
    }
    .modal-header-danger .header-icon svg{
        color:#fff;
    }

    .form-group.full{grid-column:1 / -1;}
    .form-group .field-wrap{
        position:relative;
        width:100%;}

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

    /* ============ RESPONSIVE ============ */

    @media (max-width: 1024px) {
        .chart-grid { grid-template-columns: 1fr; }
    }

    @media (max-width: 900px) {
        .info-grid { grid-template-columns: repeat(2, 1fr); }
        .chart-grid { grid-template-columns: 1fr; }
        .chart-grid-equal { grid-template-columns: 1fr; }
        .dash-stats { grid-template-columns: 1fr 1fr; }
    }

    @media (max-width: 700px) {
        .modal-grid { grid-template-columns: 1fr; }
        .custom-modal { padding: 10px 0; }
        .custom-modal-content { width: 95%; max-height: 92vh; }
        .table-toolbar { flex-direction: column; align-items: stretch; }
        .table-toolbar form { flex-direction: column; align-items: stretch; }
        .search-input, .filter-select { width: 100%; box-sizing: border-box; }
        .search-wrap, .filter-wrap { width: 100%; }
        .table-toolbar .btn, .table-toolbar .btn-outline { width: 100%; text-align: center; }
        .filter-chart-card { flex-direction: column; align-items: stretch; }
        .filter-chart-card form { flex-direction: column; align-items: stretch; }
        .date-range-wrap { width: 100%; }
        .date-range-wrap .date-input { flex: 1; min-width: 0; }
    }

    @media (max-width: 560px) {
        .info-grid { grid-template-columns: 1fr; }
        .dash-stats { grid-template-columns: 1fr; }
        h2 { font-size: 19px !important; }
        .section-title { font-size: 16px; margin: 24px 0 12px; }
        div[style*="display:flex;align-items:flex-start;justify-content:space-between"] {
            flex-direction: column;
            align-items: stretch !important;
        }
        div[style*="display:flex;align-items:flex-end;gap:10px"] {
            flex-direction: column;
            align-items: stretch !important;
            width: 100%;
        }
        div[style*="display:flex;align-items:flex-end;gap:10px"] > div { width: 100%; }
        div[style*="display:flex;align-items:flex-end;gap:10px"] a.btn {
            width: 100%;
            justify-content: center;
        }
        .data-table { font-size: 11.5px; }
        .data-table thead th, .data-table tbody td { padding: 8px 10px; }
        .row-actions { gap: 4px; }
        .icon-btn { width: 30px; height: 30px; }
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
    <div style="display:flex;align-items:flex-end;gap:10px;">
    <div>
        <label style="display:block;font-size:11.5px;font-weight:600;color:#9aa4c2;text-transform:uppercase;letter-spacing:.03em;margin:0 0 4px 2px;visibility:hidden;">
            &nbsp;
        </label>
        <a href="{{ route('laporan.index') }}" class="btn btn-outline" style="padding:9px 14px;font-size:13px;white-space:nowrap;">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="width:14px;height:14px;"><path d="M19 12H5"/><path d="m12 19-7-7 7-7"/></svg>
            Kembali ke Daftar Laporan
        </a>
    </div>
    <div>
        <label style="display:block;font-size:11.5px;font-weight:600;color:#9aa4c2;text-transform:uppercase;letter-spacing:.03em;margin:0 0 4px 2px;visibility:hidden;">
            &nbsp;
        </label>
        <a href="{{ route('laporan.export-pdf', array_filter([
            'laporan'        => $laporan->id,
            'search'         => $search,
            'golongan'       => $golonganAktif !== 'semua' ? $golonganAktif : null,
            'ulp'            => $ulpAktif !== 'semua' ? $ulpAktif : null,
            'tanggal_dari'   => $tanggalDari,
            'tanggal_sampai' => $tanggalSampai,
        ])) }}" class="btn btn-export-pdf" target="_blank">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="width:15px;height:15px;">
            <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/>
            <path d="M14 2v6h6"/>
            <path d="M9 15h1a1 1 0 0 0 0-2H9v4"/>
            <path d="M13 13v4h1.5a1.5 1.5 0 0 0 0-3H13"/>
            <path d="M17 13v4M17 15h1.5"/>
        </svg>
        Export PDF
    </a>
    </div>
    </div>
</div>

<h3 class="section-title">Ringkasan Utama</h3>

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

{{-- Filter rentang tanggal — berlaku untuk kartu ringkasan, semua grafik,
     dan tabel "Semua Data Detail" (berdasarkan tanggal_register). --}}
<div class="card filter-chart-card filter-chart-card--active">
    <div style="display:flex;align-items:center;gap:10px;">
        <div class="info-icon" style="width:34px;height:34px;">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="width:16px;height:16px;"><rect x="3" y="4" width="18" height="18" rx="2"/><path d="M16 2v4M8 2v4M3 10h18"/></svg>
        </div>
        <div>
            <strong style="font-size:14px;color:#fff;">Filter Rentang Tanggal</strong>
            <p style="margin:2px 0 0;font-size:12px;color:rgba(255,255,255,.85);">
                @if ($tanggalDari || $tanggalSampai)
                    Menampilkan {{ $tanggalDari ? \Carbon\Carbon::parse($tanggalDari)->format('d M Y') : 'awal' }} s/d {{ $tanggalSampai ? \Carbon\Carbon::parse($tanggalSampai)->format('d M Y') : 'akhir' }} &middot; berlaku untuk kartu ringkasan, grafik &amp; tabel di bawah
                @else
                    Menampilkan seluruh data laporan ini &middot; berlaku untuk kartu ringkasan, grafik &amp; tabel di bawah
                @endif
            </p>
        </div>
    </div>

    <form method="GET" style="display:flex;align-items:center;gap:8px;flex-wrap:wrap;">
        <input type="hidden" name="search" value="{{ $search }}">
        <input type="hidden" name="golongan" value="{{ $golonganAktif }}">
        <input type="hidden" name="ulp" value="{{ $ulpAktif }}">

        <div class="date-range-wrap">
            <input type="date" name="tanggal_dari" value="{{ $tanggalDari }}" class="date-input" title="Tanggal dari">
            <span class="sep-dash">&ndash;</span>
            <input type="date" name="tanggal_sampai" value="{{ $tanggalSampai }}" class="date-input" title="Tanggal sampai">
        </div>

        <button type="submit" class="btn" style="background:#fff;color:#0b3d91;">Terapkan</button>
        @if ($tanggalDari || $tanggalSampai)
            <a href="{{ route('laporan.show', array_filter([
                'laporan'  => $laporan->id,
                'search'   => $search,
                'golongan' => $golonganAktif !== 'semua' ? $golonganAktif : null,
                'ulp'      => $ulpAktif !== 'semua' ? $ulpAktif : null,
            ])) }}" class="btn btn-outline">Reset</a>
        @endif
    </form>
</div>

{{-- Kartu statistik — nilainya ikut Filter Rentang Tanggal di atas. --}}
<div class="dash-stats">
    <div class="dash-stat-card tone-yellow">
        <div class="dash-stat-top">
            <div class="dash-stat-icon">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="22 12 18 12 15 21 9 3 6 12 2 12"/></svg>
            </div>
            <h3>Total KWH</h3>
        </div>
        <div class="dash-stat-value">{{ number_format($totalKwh, 0, ',', '.') }}</div>
        <div class="dash-stat-sub">KWH sesuai rentang tanggal terpilih</div>
    </div>

    <div class="dash-stat-card tone-blue">
        <div class="dash-stat-top">
            <div class="dash-stat-icon">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="2" y="5" width="20" height="14" rx="2"/><path d="M2 10h20"/></svg>
            </div>
            <h3>Rp. TS</h3>
        </div>
        <div class="dash-stat-value">Rp {{ number_format($totalTs, 0, ',', '.') }}</div>
        <div class="dash-stat-sub">Total TS sesuai rentang tanggal terpilih</div>
    </div>

    <div class="dash-stat-card tone-purple">
        <div class="dash-stat-top">
            <div class="dash-stat-icon">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 2 2 7l10 5 10-5-10-5Z"/><path d="m2 17 10 5 10-5"/><path d="m2 12 10 5 10-5"/></svg>
            </div>
            <h3>Penetapan</h3>
        </div>
        <div class="dash-stat-value">Rp {{ number_format($totalPenetapan, 0, ',', '.') }}</div>
        <div class="dash-stat-sub">Total tunai + angsuran sesuai rentang tanggal terpilih</div>
    </div>
</div>

<h3 class="section-title">Analisis Golongan Tarif</h3>

{{-- Chart: distribusi golongan (dengan info jumlah pelanggan/KWH/persen) & komposisi P vs K --}}
<div class="chart-grid">
    <div class="chart-card">
        <div class="chart-card-head">
            <div>
                <h4>Distribusi KWH per Golongan</h4>
                <p class="chart-sub">Total pemakaian KWH berdasarkan golongan tarif</p>
            </div>
            <span class="chart-badge">{{ $laporan->bulan }} {{ $laporan->tahun }}</span>
        </div>

        <canvas id="chartGolongan" height="110"></canvas>
    </div>
        <div class="chart-card">
        <div class="chart-card-head">
            <div>
                <h4>Komposisi Golongan P vs K</h4>
                <p class="chart-sub">Perbandingan jumlah pelanggan</p>
            </div>
        </div>
        <div style="height:280px;position:relative;">
            <canvas id="chartKomposisiPK"></canvas>
        </div>
    </div>
</div>

<h3 class="section-title">Tren Harian</h3>

{{-- Chart: tren harian KWH & TS --}}
<div class="chart-grid-equal">
    <div class="chart-card">
        <div class="chart-card-head">
            <div>
                <h4>Tren KWH Harian</h4>
                <p class="chart-sub">Total pemakaian KWH per hari &mdash; {{ $laporan->bulan }} {{ $laporan->tahun }}</p>
            </div>
        </div>
        <canvas id="chartTrenKwh" height="150"></canvas>
    </div>

    <div class="chart-card">
        <div class="chart-card-head">
            <div>
                <h4>Tren TS Harian</h4>
                <p class="chart-sub">Total TS (Rp) per hari &mdash; {{ $laporan->bulan }} {{ $laporan->tahun }}</p>
            </div>
        </div>
        <canvas id="chartTrenTs" height="150"></canvas>
    </div>
</div>

    <div class="chart-card" style="margin-bottom:22px;">
        <div class="chart-card-head">
            <div>
                <h4>Tren Golongan P vs K</h4>
                <p class="chart-sub">Jumlah pelanggan golongan P dan K per hari &mdash; {{ $laporan->bulan }} {{ $laporan->tahun }}</p>
            </div>
        </div>
        <div style="height:180px;">
            <canvas id="chartTrenPK"></canvas>
        </div>
    </div>

<h3 class="section-title">Data Detail</h3>

{{-- Tabel semua data detail --}}
<div class="card" style="padding:0;overflow:hidden;">
    <div class="table-toolbar">
        <div>
            <strong style="font-size:14.5px;color:#1b2559;">Semua Data Detail</strong>
            <div style="font-size:12.5px;color:#6b7690;margin-top:2px;">
                {{ $rows->total() }} dari {{ $laporan->jumlah_baris }} baris ditampilkan
            </div>
        </div>

        <form method="GET" style="display:flex;gap:8px;flex-wrap:wrap;align-items:center;">
            {{-- Filter tanggal dikontrol dari card "Filter Rentang Tanggal" di atas, tetap
                 dibawa lewat hidden input biar gak ke-reset saat search/golongan/ulp disubmit --}}
            <input type="hidden" name="tanggal_dari" value="{{ $tanggalDari }}">
            <input type="hidden" name="tanggal_sampai" value="{{ $tanggalSampai }}">

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

            <div class="filter-wrap">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M17 21v-2a4 4 0 0 0-4-4H7a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/></svg>
                <select name="ulp" onchange="this.form.submit()" class="filter-select">
                    <option value="semua" {{ $ulpAktif === 'semua' ? 'selected' : '' }}>Semua ULP</option>
                    @foreach ($daftarUlp as $u)
                        <option value="{{ $u['kode'] }}" {{ $ulpAktif === $u['kode'] ? 'selected' : '' }}>
                            {{ $u['kode'] }}{{ $u['nama'] ? ' - '.$u['nama'] : '' }}
                        </option>
                    @endforeach
                </select>
            </div>

            <button type="submit" class="btn">Cari</button>
            @if ($search || ($golonganAktif && $golonganAktif !== 'semua') || ($ulpAktif && $ulpAktif !== 'semua'))
                <a href="{{ route('laporan.show', array_filter([
                    'laporan'        => $laporan->id,
                    'tanggal_dari'   => $tanggalDari,
                    'tanggal_sampai' => $tanggalSampai,
                ])) }}" class="btn btn-outline">Reset</a>
            @endif
        </form>
    </div>

    <div style="overflow-x:auto;">
        <table class="data-table">
            <thead>
                <tr>
                    <th>No</th>
                    <th>ULP</th>
                    <th>Tanggal Agenda</th>
                    <th>IDPEL</th>
                    <th>Nama</th>
                    <th>Gol</th>
                    <th>Tarif</th>
                    <th>Daya (VA)</th>
                    <th>KWH</th>
                    <th>TS</th>
                    <th>Aksi</th>
                </tr>
            </thead>
            <tbody>
            @forelse ($rows as $i => $row)
                <tr>
                    <td>{{ $row->no ?? ($rows->firstItem() + $i) }}</td>
                    <td>
                        {{ $row->ulp ?? '-' }}
                        @if ($row->ulp_nama)
                            <div style="font-size:11px;color:#9aa4c2;">{{ $row->ulp_nama }}</div>
                        @endif
                    </td>
                    <td style="color:#6b7690;">
                        {{ $row->tanggal_agenda?->format('d/m/Y') ?? '-' }}
                    </td>
                    <td>
                        <a href="{{ route('detail-data.show', $row->id) }}" class="idpel-link">{{ $row->idpel }}</a>
                    </td>
                    <td>{{ $row->nama }}</td>
                    <td><span class="gol-pill">{{ $row->gol }}</span></td>
                    <td>{{ $row->tarif ?? '-' }}</td>
                    <td style="color:#6b7690;">{{ $row->daya_va ?? '-' }}</td>
                    <td style="color:#6b7690;">{{ number_format($row->kwh, 0, ',', '.') }}</td>
                    <td style="color:#6b7690;">{{ number_format($row->ts, 0, ',', '.') }}</td>
                    <td>
                        <div class="row-actions">
                            {{-- Detail --}}
                            <button type="button" class="icon-btn btn-detail" data-id="{{ $row->id }}" title="Detail">
                                <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" viewBox="0 0 24 24">
                                    <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8S1 12 1 12z"/>
                                    <circle cx="12" cy="12" r="3"/>
                                </svg>
                            </button>
                            
                            {{-- Hapus --}}
                            <button type="button" class="icon-btn danger btn-delete"
                                    data-id="{{ $row->id }}"
                                    data-nama="{{ $row->nama }}"
                                    data-idpel="{{ $row->idpel }}"
                                    title="Hapus">
                                <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" viewBox="0 0 24 24">
                                    <path d="M3 6h18"/>
                                    <path d="M8 6V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"/>
                                    <path d="m19 6-1 14a2 2 0 0 1-2 2H8a2 2 0 0 1-2-2L5 6"/>
                                </svg>
                            </button>
                        </div>
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="11" style="text-align:center;color:#9aa4c2;padding:32px;">Tidak ada data yang cocok.</td>
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

{{-- ================= MODAL DELETE ================= --}}
<div id="deleteModal" class="custom-modal">
    <div class="custom-modal-content" style="width:420px;">

        <div class="modal-header-blue modal-header-danger">
            <div class="header-text">
                <div class="header-icon">
                    <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" viewBox="0 0 24 24">
                        <path d="M3 6h18"/>
                        <path d="M8 6V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"/>
                        <path d="m19 6-1 14a2 2 0 0 1-2 2H8a2 2 0 0 1-2-2L5 6"/>
                    </svg>
                </div>
                <div>
                    <h3>Hapus Data Pelanggan</h3>
                    <p>Tindakan ini tidak dapat dibatalkan</p>
                </div>
            </div>
            <button class="close-modal" type="button">&times;</button>
        </div>

        <div class="modal-body" style="text-align:center;padding:24px 22px 18px;">
            <p style="margin:0 0 8px;font-size:13.5px;color:#6b7690;">
                Kamu akan menghapus data pelanggan
            </p>
            <strong style="display:block;font-size:15px;color:#1b2559;">
                <span id="del_nama"></span>
                <span style="font-weight:500;color:#9aa4c2;"> &middot; </span>
                <span id="del_idpel" style="color:#6b7690;font-weight:500;"></span>
            </strong>
        </div>

        <div class="modal-footer" style="padding:14px 22px;">
            <button type="button" class="btn btn-outline close-modal">Batal</button>

            <form id="deleteForm" action="" method="POST" style="display:inline">
                @csrf
                @method('DELETE')
                <button type="submit" class="btn" style="background:#e0433d;border-color:#e0433d;">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="width:15px;height:15px;">
                        <path d="M3 6h18"/><path d="M8 6V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"/><path d="m19 6-1 14a2 2 0 0 1-2 2H8a2 2 0 0 1-2-2L5 6"/>
                    </svg>
                    Ya, Hapus
                </button>
            </form>
        </div>

    </div>
</div>

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script src="https://cdn.jsdelivr.net/npm/chartjs-plugin-datalabels"></script>
<script>
    new Chart(document.getElementById('chartGolongan'), {
        type: 'bar',
        data: {
            labels: {!! json_encode($distribusiGolonganDetail->pluck('gol')) !!},
            datasets: [{
                data: {!! json_encode($distribusiGolonganDetail->pluck('total_kwh')) !!},
                backgroundColor: ['#ffce3a', '#0b3d91', '#3d63b8', '#6b8fd6', '#1a9c4a'],
                borderRadius: 4,
                minBarLength: 6,
            }]
        },
        plugins: [ChartDataLabels],
        options: {
            layout: { padding: { top: 34 } },
            plugins: {
                legend: { display: false },
                tooltip: {
                    callbacks: {
                        label: function(ctx) {
                            const jml = {!! json_encode($distribusiGolonganDetail->pluck('jumlah_pelanggan')) !!}[ctx.dataIndex];
                            return [' ' + Number(ctx.raw).toLocaleString('id-ID') + ' KWH', ' ' + jml.toLocaleString('id-ID') + ' pelanggan'];
                        }
                    }
                },
                datalabels: {
                    anchor: 'end',
                    align: 'top',
                    offset: 4,
                    color: '#1b2559',
                    font: { size: 10.5, weight: '700' },
                    lineHeight: 1.3,
                    formatter: function(value, ctx) {
                        const jml = {!! json_encode($distribusiGolonganDetail->pluck('jumlah_pelanggan')) !!}[ctx.dataIndex];
                        const persen = {!! json_encode($distribusiGolonganDetail->pluck('persen_kwh')) !!}[ctx.dataIndex];
                        const kwhFormatted = Number(value).toLocaleString('id-ID');
                        return kwhFormatted + ' KWH\n' + jml.toLocaleString('id-ID') + ' plg  •  ' + persen.toString().replace('.', ',') + '%';
                    }
                }
            },
            scales: {
                y: {
                    beginAtZero: true,
                    ticks: { callback: v => Number(v).toLocaleString('id-ID') }
                }
            }
        }
});

    new Chart(document.getElementById('chartTrenKwh'), {
    type: 'line',
    data: {
        labels: {!! json_encode($trenHarian->pluck('tanggal')->map(fn ($t) => \Carbon\Carbon::parse($t)->format('d M'))) !!},
        datasets: [{
            label: 'KWH',
            data: {!! json_encode($trenHarian->pluck('kwh')) !!},
            borderColor: '#0b3d91',
            backgroundColor: 'rgba(11,61,145,.08)',
            fill: true,
            tension: 0.3,
        }]
    },
    options: {
        plugins: {
            legend: { display: false },
            tooltip: {
                callbacks: {
                    label: function(ctx) {
                        return ' ' + Number(ctx.raw).toLocaleString('id-ID') + ' KWH';
                    }
                }
            }
        },
        scales: {
            y: {
                beginAtZero: true,
                ticks: { callback: v => Number(v).toLocaleString('id-ID') }
            }
        }
    }
});

    new Chart(document.getElementById('chartTrenTs'), {
        type: 'line',
        data: {
            labels: {!! json_encode($trenHarian->pluck('tanggal')->map(fn ($t) => \Carbon\Carbon::parse($t)->format('d M'))) !!},
            datasets: [{
                label: 'TS',
                data: {!! json_encode($trenHarian->pluck('ts')) !!},
                borderColor: '#ffce3a',
                backgroundColor: 'rgba(255,206,58,.12)',
                fill: true,
                tension: 0.3,
            }]
        },
        options: {
            plugins: {
                legend: { display: false },
                tooltip: {
                    callbacks: {
                        label: function(ctx) {
                            return ' Rp ' + Number(ctx.raw).toLocaleString('id-ID');
                        }
                    }
                }
            },
            scales: {
                y: {
                    beginAtZero: true,
                    ticks: { callback: v => 'Rp ' + Number(v).toLocaleString('id-ID') }
                }
            }
        }
    });

    new Chart(document.getElementById('chartKomposisiPK'), {
        type: 'doughnut',
        data: {
            labels: [
                'Golongan P ({{ number_format($totalPelangganP, 0, ",", ".") }})',
                'Golongan K ({{ number_format($totalPelangganK, 0, ",", ".") }})'
            ],
            datasets: [{
                data: [{{ $totalPelangganP }}, {{ $totalPelangganK }}],
                backgroundColor: ['#0b3d91', '#ffce3a'],
            }]
        },
        plugins: [ChartDataLabels],
        options: {
            maintainAspectRatio: false,
            cutout: '55%',
            plugins: {
                legend: {
                    position: 'bottom',
                    labels: { boxWidth: 10, font: { size: 11.5, weight: '600' }, padding: 14 }
                },
                tooltip: {
                    callbacks: {
                        label: function(ctx) {
                            return ' ' + ctx.label + ' pelanggan';
                        }
                    }
                },
                datalabels: {
                    color: '#fff',
                    font: { size: 13, weight: '800' },
                    formatter: function(value, ctx) {
                        const total = ctx.dataset.data.reduce((a, b) => a + b, 0);
                        const persen = total > 0 ? (value / total * 100).toFixed(1) : 0;
                        return persen.toString().replace('.', ',') + '%';
                    }
                }
            }
        }
    });

    new Chart(document.getElementById('chartTrenPK'), {
        type: 'line',
        data: {
            labels: {!! json_encode($trenPK->pluck('tanggal')->map(fn ($t) => \Carbon\Carbon::parse($t)->format('d M'))) !!},
            datasets: [
                {
                    label: 'Golongan P',
                    data: {!! json_encode($trenPK->pluck('jumlah_p')) !!},
                    borderColor: '#0b3d91',
                    tension: 0.3,
                },
                {
                    label: 'Golongan K',
                    data: {!! json_encode($trenPK->pluck('jumlah_k')) !!},
                    borderColor: '#ffce3a',
                    tension: 0.3,
                }
            ]
        },
        options: {
            maintainAspectRatio: false,
            plugins: {
                legend: { position: 'top' },
                tooltip: {
                    callbacks: {
                        label: function(ctx) {
                            return ' ' + ctx.dataset.label + ': ' + Number(ctx.raw).toLocaleString('id-ID') + ' pelanggan';
                        }
                    }
                }
            },
            scales: { y: { beginAtZero: true } }
        }
    });

    document.addEventListener('DOMContentLoaded', function () {

    const showUrlTemplate   = @json(route('detail-data.show', ['detail' => '__ID__']));
    const updateUrlTemplate = @json(route('detail-data.update', ['detail' => '__ID__']));
    const destroyUrlTemplate = @json(route('detail-data.destroy', ['detail' => '__ID__']));

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

    // Event delegation — tetap jalan walau tabel di-render ulang via pagination/filter
    document.addEventListener('click', function (e) {
        const detailBtn = e.target.closest('.btn-detail');
        const closeBtn  = e.target.closest('.close-modal');
        const deleteBtn = e.target.closest('.btn-delete');

        if (detailBtn) {
            const id = detailBtn.dataset.id;
            fetch(showUrlTemplate.replace('__ID__', id))
                .then(res => { if (!res.ok) throw new Error(res.status); return res.json(); })
                .then(data => { fillDetail(data); document.getElementById('detailModal').style.display = 'flex'; })
                .catch(err => { console.error(err); alert('Gagal memuat detail data.'); });
        }

        if (closeBtn) {
            closeBtn.closest('.custom-modal').style.display = 'none';
        }

        if (deleteBtn) {
            const id    = deleteBtn.dataset.id;
            const nama  = deleteBtn.dataset.nama;
            const idpel = deleteBtn.dataset.idpel;

            document.getElementById('del_nama').textContent  = nama;
            document.getElementById('del_idpel').textContent = idpel;
            document.getElementById('deleteForm').action = destroyUrlTemplate.replace('__ID__', id);

            document.getElementById('deleteModal').style.display = 'flex';
        }

        if (e.target.classList.contains('custom-modal')) {
            e.target.style.display = 'none';
        }

    });

});

    document.addEventListener('DOMContentLoaded', function () {
        const dataPeriode = @json(
            $daftarLaporanBulan->groupBy('tahun')->map(function ($items) {
                return $items->map(fn ($i) => ['bulan' => $i->bulan, 'url' => route('laporan.show', $i->id)]);
            })
        );

        const tahunAktif = "{{ $laporan->tahun }}";
        const bulanAktif = "{{ $laporan->bulan }}";

        const tahunSelect = document.getElementById('tahunSelect');
        const bulanSelect = document.getElementById('bulanSelect');

        if (tahunSelect && bulanSelect) {
            Object.keys(dataPeriode).sort((a, b) => b - a).forEach(tahun => {
                const opt = document.createElement('option');
                opt.value = tahun;
                opt.textContent = tahun;
                if (tahun === tahunAktif) opt.selected = true;
                tahunSelect.appendChild(opt);
            });

            function isiBulan(tahun) {
                bulanSelect.innerHTML = '';
                (dataPeriode[tahun] || []).forEach(item => {
                    const opt = document.createElement('option');
                    opt.value = item.url;
                    opt.textContent = item.bulan;
                    if (tahun === tahunAktif && item.bulan === bulanAktif) opt.selected = true;
                    bulanSelect.appendChild(opt);
                });
            }

            isiBulan(tahunSelect.value);

            tahunSelect.addEventListener('change', function () {
                isiBulan(this.value);
                bulanSelect.dispatchEvent(new Event('change'));
            });

            bulanSelect.addEventListener('change', function () {
                if (this.value) window.location.href = this.value;
            });
        }
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