@extends('layouts.app')
@section('title', 'Daftar Pelanggan')

@section('breadcrumb')
    <a href="{{ route('dashboard') }}">Beranda</a>
    <span class="sep">›</span>
    <strong>Daftar Pelanggan</strong>
@endsection

@push('styles')
<style>
    :root {
        --plg-navy: #0b3d91;
        --plg-navy-dark: #071233;
        --plg-text-dark: #1b2559;
        --plg-text-muted: #6b7690;
        --plg-border: #e7eaf3;
        --plg-bg-soft: #f7f9fd;
        --plg-yellow: #ffd60a;
        --plg-blue-bg: #eaf1ff;
    }

    .plg-card { padding: 0; overflow: hidden; }

    .plg-toolbar {
        display: flex;
        align-items: center;
        gap: 12px;
        flex-wrap: wrap;
        padding: 18px 22px;
        border-bottom: 1px solid var(--border);
    }
    .plg-search-wrap { position: relative; flex: 1 1 240px; min-width: 200px; }
    .plg-search-wrap svg {
        position: absolute; left: 12px; top: 50%; transform: translateY(-50%);
        width: 15px; height: 15px; color: #9aa4c2; pointer-events: none;
    }
    .plg-search-input {
        width: 100%;
        box-sizing: border-box;
        border: 1px solid var(--border);
        border-radius: 9px;
        padding: 9px 14px 9px 36px;
        font-size: 13px;
        background: #fff;
    }
    .plg-search-input:focus { outline: none; border-color: var(--blue-primary); }

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
        min-width: 140px;
    }

    .plg-count-badge {
        background: #eaf0fb;
        color: var(--blue-primary);
        font-size: 12px;
        font-weight: 700;
        padding: 5px 12px;
        border-radius: 999px;
        white-space: nowrap;
    }

    .plg-table-scroll { overflow-x: auto; }
    .plg-table { width: 100%; border-collapse: collapse; font-size: 13px; min-width: 780px; }
    .plg-table th, .plg-table td {
        padding: 12px 16px;
        text-align: left;
        border-bottom: 1px solid #eef0f6;
        white-space: nowrap;
    }
    .plg-table thead th {
        background: #0b3d91;
        color: #fff;
        font-weight: 700;
        font-size: 11px;
        text-transform: uppercase;
        letter-spacing: .03em;
    }
    .plg-table tbody tr:nth-child(even) { background: #f8f9fc; }
    .plg-table tbody tr:hover { background: #eef2fb; }
    .plg-table td.plg-nama { font-weight: 700; color: #1b2559; }
    .plg-table td.plg-idpel { font-variant-numeric: tabular-nums; color: #6b7690; }

    .plg-gol-badge {
        display: inline-flex; align-items: center;
        padding: 3px 10px; border-radius: 999px;
        font-size: 11.5px; font-weight: 700;
        background: #eaf0fb; color: #0b3d91;
    }

    .plg-detail-btn {
        border: 1px solid var(--border);
        background: #fff;
        color: #0b3d91;
        font-size: 12px;
        font-weight: 700;
        padding: 6px 12px;
        border-radius: 999px;
        cursor: pointer;
        white-space: nowrap;
    }
    .plg-detail-btn:hover { background: #eaf0fb; }

    .plg-empty { text-align: center; color: #9aa4c2; padding: 40px; font-size: 13px; }

    .plg-pagination { padding: 16px 22px; border-top: 1px solid var(--border); }

    /* ===== Rapikan pagination bawaan Laravel ===== */
    .plg-pagination nav { display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: 10px; }
    .plg-pagination nav > div:first-child { display: none; }
    .plg-pagination nav > div:last-child { display: flex; align-items: center; gap: 4px; flex-wrap: wrap; }

    .plg-pagination svg {
        width: 16px !important;
        height: 16px !important;
        display: inline-block;
    }

    .plg-pagination nav span[aria-current="page"] span,
    .plg-pagination nav a,
    .plg-pagination nav span:not([aria-current="page"]) span {
        display: inline-flex !important;
        align-items: center;
        justify-content: center;
        min-width: 34px;
        height: 34px;
        padding: 0 8px;
        border-radius: 8px;
        border: 1px solid var(--border);
        background: #fff;
        color: #4a5578;
        font-size: 13px;
        font-weight: 600;
        text-decoration: none;
        box-sizing: border-box;
    }

    .plg-pagination nav a:hover {
        background: #eaf0fb;
        border-color: #c7d3f0;
        color: #0b3d91;
    }

    .plg-pagination nav span[aria-current="page"] span {
        background: #0b3d91 !important;
        border-color: #0b3d91 !important;
        color: #fff !important;
    }

    .plg-pagination nav > div:last-child > span:first-child span,
    .plg-pagination nav > div:last-child > span:last-child span {
        background: #f8f9fc;
        color: #c3c9dc;
    }

    /* ===== Modal Detail Pelanggan — gaya disamakan dengan Profil Saya
       (cover navy gradient + avatar kuning overlapping + badge + meta-list
       + section title beriskon+deskripsi), tapi READ-ONLY: field
       ditampilkan sebagai kotak nilai statis, bukan <input>. ===== */
    .plg-modal-overlay {
        display: none;
        position: fixed; inset: 0;
        background: rgba(15, 23, 60, .5);
        z-index: 1000;
        align-items: flex-start;
        justify-content: center;
        padding: 40px 20px;
        overflow-y: auto;
    }
    .plg-modal-overlay.is-open { display: flex; }

    .plg-modal {
        background: #fff;
        border-radius: 16px;
        width: 100%;
        max-width: 620px;
        box-shadow: 0 20px 60px rgba(15,23,60,.28);
        overflow: hidden;
        position: relative;
    }

    .plg-modal-cover {
        height: 88px;
        background: linear-gradient(120deg, var(--plg-navy) 0%, var(--plg-navy-dark) 100%);
    }
    .plg-modal-avatar {
        position: absolute;
        top: 46px;
        left: 50%;
        transform: translateX(-50%);
        z-index: 2;
        width: 84px; height: 84px; border-radius: 50%;
        background: var(--plg-yellow); color: var(--plg-navy-dark);
        display: flex; align-items: center; justify-content: center;
        font-size: 26px; font-weight: 800;
        border: 4px solid #fff;
    }

    .plg-modal-close {
        position: absolute; top: 14px; right: 14px; z-index: 3;
        width: 30px; height: 30px; border-radius: 999px;
        background: rgba(255,255,255,.18);
        border: none; color: #fff; cursor: pointer;
        display: flex; align-items: center; justify-content: center;
    }
    .plg-modal-close:hover { background: rgba(255,255,255,.3); }
    .plg-modal-close svg { width: 15px; height: 15px; }

    .plg-modal-head-body {
        padding: 50px 24px 22px;
        text-align: center;
    }
    .plg-modal-head-body h3 { margin: 4px 0 2px; font-size: 18px; font-weight: 800; color: var(--plg-text-dark); }
    .plg-modal-head-body .plg-modal-idpel { font-size: 13px; color: var(--plg-text-muted); }

    .plg-modal-badges { display: flex; justify-content: center; gap: 8px; margin-top: 14px; flex-wrap: wrap; }
    .plg-modal-badge {
        display: inline-flex; align-items: center; gap: 6px;
        padding: 4px 12px; border-radius: 20px;
        font-size: 12px; font-weight: 700;
        background: var(--plg-blue-bg); color: var(--plg-navy);
    }

    .plg-modal-readonly-note {
        display: flex; align-items: center; gap: 8px; justify-content: center;
        margin: 16px 24px 0;
        padding: 10px 14px;
        background: var(--plg-bg-soft);
        border: 1px solid var(--plg-border);
        border-radius: 10px;
        font-size: 12px;
        color: var(--plg-text-muted);
    }
    .plg-modal-readonly-note svg { width: 14px; height: 14px; flex-shrink: 0; color: #9aa4c2; }

    .plg-modal-body { max-height: 60vh; overflow-y: auto; padding: 4px 24px 24px; }

    .plg-modal-section-title { display: flex; align-items: center; gap: 10px; margin: 22px 0 4px; }
    .plg-modal-section-title .plg-section-icon {
        width: 32px; height: 32px; border-radius: 9px;
        background: var(--plg-blue-bg); color: var(--plg-navy);
        display: flex; align-items: center; justify-content: center; flex-shrink: 0;
    }
    .plg-modal-section-title .plg-section-icon svg { width: 16px; height: 16px; }
    .plg-modal-section-title h4 { margin: 0; font-size: 15px; font-weight: 800; color: var(--plg-text-dark); }
    .plg-modal-section-desc { margin: 4px 0 16px 42px; font-size: 12.5px; color: var(--plg-text-muted); }

    .plg-modal-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 14px 16px; }
    .plg-modal-field.span-2 { grid-column: span 2; }
    .plg-modal-field label { display: block; font-size: 12.5px; font-weight: 700; color: var(--plg-text-dark); margin-bottom: 6px; }

    .plg-modal-value-wrap { position: relative; }
    .plg-modal-value-wrap svg {
        position: absolute; left: 13px; top: 50%; transform: translateY(-50%);
        width: 15px; height: 15px; color: #9aa4c2; flex-shrink: 0;
    }
    .plg-modal-value {
        width: 100%;
        box-sizing: border-box;
        background: var(--plg-bg-soft);
        border: 1px solid var(--plg-border);
        border-radius: 10px;
        padding: 10px 14px 10px 40px;
        font-size: 13px;
        font-weight: 600;
        color: var(--plg-text-dark);
        word-break: break-word;
        min-height: 20px;
    }

    .plg-modal-divider { border: none; border-top: 1px solid var(--plg-border); margin: 20px 0 0; }

    .plg-modal-loading, .plg-modal-error {
        padding: 60px 24px; text-align: center; color: #9aa4c2; font-size: 13px;
    }

    @media (max-width: 640px) {
        .plg-modal-grid { grid-template-columns: 1fr; }
        .plg-modal-field.span-2 { grid-column: span 1; }
        .plg-toolbar { flex-direction: column; align-items: stretch; }
        .plg-search-wrap { flex: 1 1 100%; }
        .plg-pagination nav { justify-content: center; }
    }
</style>
@endpush

@section('content')

<div style="display:flex;align-items:flex-start;justify-content:space-between;margin-bottom:22px;flex-wrap:wrap;gap:12px;">
    <div>
        <h2 style="margin:0 0 4px;font-size:22px;">Daftar Pelanggan</h2>
        <p style="color:#6b7690;margin:0;font-size:14px;">Seluruh pelanggan dari dokumen yang sudah diupload</p>
    </div>
    <span class="plg-count-badge">{{ number_format($totalPelanggan, 0, ',', '.') }} Pelanggan</span>
</div>

<div class="card plg-card">
    <form method="GET" class="plg-toolbar">
        <div class="plg-search-wrap">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="11" cy="11" r="8"/><path d="m21 21-4.3-4.3"/></svg>
            <input type="text" name="search" value="{{ $search }}" class="plg-search-input" placeholder="Cari ID Pelanggan atau nama...">
        </div>

        <div class="filter-wrap">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M4 21V8a2 2 0 0 1 2-2h12a2 2 0 0 1 2 2v13M4 21h16M9 12h6M9 16h6"/></svg>
            <select name="golongan" onchange="this.form.submit()" class="filter-select">
                <option value="semua" {{ $golonganAktif === 'semua' ? 'selected' : '' }}>Semua Golongan</option>
                @foreach ($daftarGolongan as $g)
                    <option value="{{ $g }}" {{ $golonganAktif === $g ? 'selected' : '' }}>{{ $g }}</option>
                @endforeach
            </select>
        </div>

        <div class="filter-wrap">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M6 22V4a1 1 0 0 1 1-1h10a1 1 0 0 1 1 1v18"/><path d="M2 22h20"/></svg>
            <select name="ulp" onchange="this.form.submit()" class="filter-select">
                <option value="semua" {{ $ulpAktif === 'semua' ? 'selected' : '' }}>Semua ULP</option>
                @foreach ($daftarUlp as $u)
                    <option value="{{ $u['kode'] }}" {{ $ulpAktif === $u['kode'] ? 'selected' : '' }}>{{ $u['nama'] }}</option>
                @endforeach
            </select>
        </div>

        <button type="submit" class="btn" style="padding:9px 18px;border-radius:9px;">Cari</button>
    </form>

    <div class="plg-table-scroll">
        @if ($pelanggan->count() > 0)
            <table class="plg-table">
                <thead>
                    <tr>
                        <th>ID Pelanggan</th>
                        <th>Nama</th>
                        <th>Golongan</th>
                        <th>Daya</th>
                        <th>ULP</th>
                        <th style="text-align:right;">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($pelanggan as $p)
                        <tr>
                            <td class="plg-idpel">{{ $p->idpel }}</td>
                            <td class="plg-nama">{{ $p->nama }}</td>
                            <td><span class="plg-gol-badge">{{ $p->gol }}</span></td>
                            <td>{{ $p->daya }}</td>
                            <td>{{ \App\Models\DetailTagihanSusulan::namaUlp($p->ulp_kode) ?? '-' }}</td>
                            <td style="text-align:right;">
                                <button type="button" class="plg-detail-btn" onclick="bukaDetailPelanggan({{ $p->id }})">Lihat Detail</button>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        @else
            <p class="plg-empty">Tidak ada pelanggan yang cocok dengan pencarian/filter ini.</p>
        @endif
    </div>

    @if ($pelanggan->hasPages())
        <div class="plg-pagination">
            {{ $pelanggan->links() }}
        </div>
    @endif
</div>

{{-- ===== Modal Detail Pelanggan (gaya Profil Saya, read-only) ===== --}}
<div class="plg-modal-overlay" id="plgModalOverlay" onclick="if(event.target === this) tutupDetailPelanggan()">
    <div class="plg-modal">
        <div class="plg-modal-cover"></div>
        <div class="plg-modal-avatar" id="plgModalAvatar">--</div>
        <button type="button" class="plg-modal-close" onclick="tutupDetailPelanggan()">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M18 6 6 18"/><path d="m6 6 12 12"/></svg>
        </button>

        <div class="plg-modal-head-body">
            <h3 id="plgModalNama">Memuat...</h3>
            <div class="plg-modal-idpel" id="plgModalIdpel">-</div>

            <div class="plg-modal-badges" id="plgModalBadges"></div>
        </div>

        <div class="plg-modal-readonly-note">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="11" width="18" height="10" rx="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4"/></svg>
            <span>Detail ini bersifat baca saja &mdash; tidak bisa diubah dari halaman Daftar Pelanggan.</span>
        </div>

        <div class="plg-modal-body" id="plgModalBody">
            <div class="plg-modal-loading">Memuat detail pelanggan...</div>
        </div>
    </div>
</div>

@endsection

@push('scripts')
<script>
function formatRupiah(v) {
    return 'Rp ' + Number(v || 0).toLocaleString('id-ID');
}
function formatAngka(v) {
    return Number(v || 0).toLocaleString('id-ID');
}
function formatTanggal(v) {
    if (!v) return '-';
    var d = new Date(v);
    if (isNaN(d)) return v;
    return d.toLocaleDateString('id-ID', { day: '2-digit', month: 'long', year: 'numeric' });
}
function ambilInisial(nama) {
    if (!nama) return '--';
    var parts = nama.trim().split(/\s+/);
    var inisial = parts[0].charAt(0);
    if (parts.length > 1) inisial += parts[parts.length - 1].charAt(0);
    return inisial.toUpperCase();
}

/**
 * Bikin satu field read-only bergaya kotak (icon di kiri + nilai),
 * dipakai berulang di tiap section modal — samain sama .pf-input-wrap
 * di Profil Saya tapi <div> statis, bukan <input>.
 */
function fieldValue(label, value, iconSvg, span2) {
    return '' +
        '<div class="plg-modal-field' + (span2 ? ' span-2' : '') + '">' +
            '<label>' + label + '</label>' +
            '<div class="plg-modal-value-wrap">' +
                iconSvg +
                '<div class="plg-modal-value">' + (value || '-') + '</div>' +
            '</div>' +
        '</div>';
}

var ICON_TAG = '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M20.59 13.41 13.42 20.6a2 2 0 0 1-2.83 0L2 12V2h10l8.59 8.59a2 2 0 0 1 0 2.82Z"/><circle cx="7" cy="7" r="1"/></svg>';
var ICON_MAP = '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M6 22V4a1 1 0 0 1 1-1h10a1 1 0 0 1 1 1v18"/><path d="M2 22h20M10 6h.01M14 6h.01M10 10h.01M14 10h.01M10 14h.01M14 14h.01M10 18h4"/></svg>';
var ICON_BOLT = '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M13 2 3 14h9l-1 8 10-12h-9l1-8Z"/></svg>';
var ICON_HOME = '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="m3 9 9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"/><path d="M9 22V12h6v10"/></svg>';
var ICON_MONEY = '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="2" y="6" width="20" height="12" rx="2"/><circle cx="12" cy="12" r="3"/></svg>';
var ICON_CALENDAR = '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="4" width="18" height="18" rx="2"/><path d="M16 2v4M8 2v4M3 10h18"/></svg>';
var ICON_DOC = '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M6 3h9l5 5v13a1 1 0 0 1-1 1H6a1 1 0 0 1-1-1V4a1 1 0 0 1 1-1Z"/><path d="M9 12h6M9 16h6M9 8h2"/></svg>';

function sectionTitle(title, desc, iconSvg) {
    return '' +
        '<div class="plg-modal-section-title">' +
            '<div class="plg-section-icon">' + iconSvg + '</div>' +
            '<h4>' + title + '</h4>' +
        '</div>' +
        '<p class="plg-modal-section-desc">' + desc + '</p>';
}

function bukaDetailPelanggan(id) {
    var overlay = document.getElementById('plgModalOverlay');
    var body = document.getElementById('plgModalBody');

    document.getElementById('plgModalNama').textContent = 'Memuat...';
    document.getElementById('plgModalIdpel').textContent = '-';
    document.getElementById('plgModalAvatar').textContent = '--';
    document.getElementById('plgModalBadges').innerHTML = '';
    body.innerHTML = '<div class="plg-modal-loading">Memuat detail pelanggan...</div>';
    overlay.classList.add('is-open');

    fetch('/pelanggan/' + id + '/json')
        .then(function (res) {
            if (!res.ok) throw new Error('Gagal memuat data');
            return res.json();
        })
        .then(function (d) {
            document.getElementById('plgModalNama').textContent = d.nama || '-';
            document.getElementById('plgModalIdpel').textContent = 'ID Pelanggan: ' + (d.idpel || '-');
            document.getElementById('plgModalAvatar').textContent = ambilInisial(d.nama);

            document.getElementById('plgModalBadges').innerHTML =
                '<span class="plg-modal-badge">' + (d.gol || '-') + '</span>' +
                '<span class="plg-modal-badge">' + (d.ulp_nama || d.ulp_kode || 'ULP tidak diketahui') + '</span>';

            var laporanInfo = d.laporan
                ? ((d.laporan.bulan || '') + ' ' + (d.laporan.tahun || '') + ' &middot; ' + (d.laporan.unit_up3 || '-') + ' &middot; Versi ' + d.laporan.versi)
                : '-';

            body.innerHTML =
                sectionTitle('Identitas Pelanggan', 'Data dasar pelanggan sesuai dokumen yang diupload.', ICON_TAG) +
                '<div class="plg-modal-grid">' +
                    fieldValue('ID Pelanggan', d.idpel, ICON_TAG) +
                    fieldValue('Golongan', d.gol, ICON_BOLT) +
                    fieldValue('Daya', d.daya, ICON_BOLT) +
                    fieldValue('ULP', d.ulp_nama || d.ulp_kode, ICON_MAP) +
                    fieldValue('Alamat', d.alamat, ICON_HOME, true) +
                '</div>' +

                '<hr class="plg-modal-divider">' +

                sectionTitle('Data Tagihan', 'Rincian nilai KWH dan komponen Rp TS.', ICON_MONEY) +
                '<div class="plg-modal-grid">' +
                    fieldValue('KWH', formatAngka(d.kwh), ICON_BOLT) +
                    fieldValue('Beban', formatAngka(d.beban), ICON_BOLT) +
                    fieldValue('KWH Rupiah', formatRupiah(d.kwh_rupiah), ICON_MONEY) +
                    fieldValue('Rp TS', formatRupiah(d.ts), ICON_MONEY) +
                    fieldValue('Materai', formatRupiah(d.materai), ICON_MONEY) +
                    fieldValue('Segel', formatRupiah(d.segel), ICON_MONEY) +
                    fieldValue('Rp PPJ', formatRupiah(d.rpppj), ICON_MONEY) +
                    fieldValue('Rp UJL', formatRupiah(d.rpujl), ICON_MONEY) +
                    fieldValue('Rp PPN', formatRupiah(d.rpppn), ICON_MONEY) +
                    fieldValue('Total', formatRupiah(d.total), ICON_MONEY) +
                    fieldValue('Tunai', formatRupiah(d.tunai), ICON_MONEY) +
                    fieldValue('Angsuran', formatRupiah(d.angsuran), ICON_MONEY) +
                '</div>' +

                '<hr class="plg-modal-divider">' +

                sectionTitle('Dokumen & Registrasi', 'Informasi agenda dan sumber laporan pelanggan ini.', ICON_DOC) +
                '<div class="plg-modal-grid">' +
                    fieldValue('No Agenda', d.no_agenda, ICON_DOC, true) +
                    fieldValue('Tanggal Agenda', formatTanggal(d.tanggal_agenda), ICON_CALENDAR) +
                    fieldValue('Tanggal Register', formatTanggal(d.tanggal_register), ICON_CALENDAR) +
                    fieldValue('Nomor Register', d.nomor_register, ICON_DOC) +
                    fieldValue('Tanggal SPH', formatTanggal(d.tanggal_sph), ICON_CALENDAR) +
                    fieldValue('Nomor SPH', d.nomor_sph, ICON_DOC, true) +
                    fieldValue('Sumber Laporan', laporanInfo, ICON_DOC, true) +
                '</div>';
        })
        .catch(function () {
            body.innerHTML = '<div class="plg-modal-error">Gagal memuat detail pelanggan. Silakan coba lagi.</div>';
        });
}

function tutupDetailPelanggan() {
    document.getElementById('plgModalOverlay').classList.remove('is-open');
}

document.addEventListener('keydown', function (e) {
    if (e.key === 'Escape') tutupDetailPelanggan();
});
</script>
@endpush