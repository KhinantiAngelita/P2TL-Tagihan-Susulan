@extends('layouts.app')
@section('title', 'Gol Tarif')

@section('breadcrumb')
    <a href="{{ route('dashboard') }}">Beranda</a>
    <span class="sep">›</span>
    <strong>Gol Tarif</strong>
@endsection

@push('styles')
<style>
    .trend-page-title { font-size: clamp(18px, 4.2vw, 22px); margin: 0 0 4px; color: #1b2559; font-weight: 700; }

    .goltarif-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(360px, 1fr));
        gap: 20px;
        margin-bottom: 22px;
        align-items: stretch;
    }
    @media (max-width: 1200px) { .goltarif-grid { gap: 14px; } }
    @media (max-width: 900px) {
        .goltarif-grid { grid-template-columns: 1fr; gap: 16px; }
        .goltarif-card { height: auto !important; }
    }

    /* ===== Card Prabayar/Paskabayar — bentuk original dipertahankan
       (chart di atas, tabel scroll box di bawah dengan header & footer
       bar penuh warna + kolom pertama sticky), warnanya diganti jadi
       SOFT/PASTEL, bukan navy/orange solid kuat seperti sebelumnya. ===== */
    .goltarif-card {
        padding: 24px;
        background: #fff;
        border: 1px solid var(--border);
        border-radius: 16px;
        box-sizing: border-box;
        min-width: 0;
        display: flex;
        flex-direction: column;
        height: 100%;
        box-shadow: 0 1px 2px rgba(16,24,64,.04);
    }

    .goltarif-card-head {
        display: flex; align-items: center; justify-content: space-between;
        margin-bottom: 18px; padding-bottom: 14px;
        border-bottom: 1px solid #f1f3f9; flex-shrink: 0; gap: 12px; flex-wrap: wrap;
    }
    .goltarif-card h3 {
        margin: 0 0 3px; font-size: 15.5px; color: #1b2559;
        display: flex; align-items: center; gap: 8px;
    }
    .goltarif-card h3 .dot { width: 9px; height: 9px; border-radius: 50%; flex-shrink: 0; }
    .goltarif-card h3 .dot.prabayar   { background: #5a7fd6; }
    .goltarif-card h3 .dot.paskabayar { background: #e6a15a; }
    .goltarif-card .sub { margin: 0; font-size: 12.5px; color: #6b7690; }

    .goltarif-year-badge {
        background: #eaf0fb; color: var(--blue-primary);
        font-size: 12px; font-weight: 700; padding: 5px 12px;
        border-radius: 999px; white-space: nowrap; flex-shrink: 0;
    }
    .goltarif-head-actions { display: flex; align-items: center; gap: 8px; flex-shrink: 0; margin-left: auto; flex-wrap: wrap; }

    .copy-btn {
        border: 1px solid var(--border); background: #fff; color: #0b3d91;
        font-size: 12px; font-weight: 700; padding: 6px 14px; border-radius: 8px;
        cursor: pointer; white-space: nowrap; transition: background .15s, color .15s;
        display: inline-flex; align-items: center; gap: 6px;
    }
    .copy-btn svg { width: 14px; height: 14px; flex-shrink: 0; }
    .copy-btn:hover { background: #eaf0fb; }
    .copy-btn:disabled { opacity: .6; cursor: default; }

    .goltarif-chart-wrap { position: relative; height: 300px; margin-bottom: 20px; flex-shrink: 0; }
    @media (max-width: 900px) { .goltarif-chart-wrap { height: 260px; } }

    .goltarif-table-scroll {
        overflow-x: auto; overflow-y: auto;
        border-radius: 12px; border: 1px solid var(--border);
        flex: 1 1 auto; min-height: 0;
    }
    @media (min-width: 901px) { .goltarif-table-scroll { max-height: 320px; } }

    .goltarif-table {
        width: 100%; border-collapse: collapse; font-size: 12.5px; min-width: 480px;
        font-variant-numeric: tabular-nums;
    }
    .goltarif-table th, .goltarif-table td {
        padding: 11px 14px; text-align: right; border-bottom: 1px solid #f1f3f9; white-space: nowrap;
    }

    /* Kolom pertama (Tarif/Daya) sticky, background solid putih + garis pemisah */
    .goltarif-table th:first-child, .goltarif-table td:first-child {
        text-align: left; position: sticky; left: 0; z-index: 2; background: #fff; padding-right: 16px;
    }
    .goltarif-table th:first-child::after, .goltarif-table td:first-child::after {
        content: ''; position: absolute; top: 0; right: 0; bottom: 0; width: 2px; background: #dde3f5; z-index: 3;
    }
    .goltarif-table tbody tr:nth-child(even) td:first-child { background: #f8f9fc; }
    .goltarif-table tbody td:first-child { font-weight: 700; color: #1b2559; }

    /* Kolom Total ditonjolkan, kolom % dibuat sekunder/muted */
    .goltarif-table td:nth-last-child(2) { font-weight: 800; color: #1b2559; background: rgba(90,127,214,.04); }
    .goltarif-table td:last-child { color: #6b7690; font-weight: 600; font-size: 11.5px; }
    .goltarif-table thead th:last-child { opacity: .85; }

    /* ===== Header bar & footer bar — soft/pastel, teks putih tetap
       terbaca karena warnanya masih cukup medium (bukan pucat banget). ===== */
    .goltarif-table thead th {
        color: #fff; font-weight: 700; font-size: 11px; text-transform: uppercase;
        letter-spacing: .04em; white-space: nowrap; position: sticky; top: 0; z-index: 4;
    }
    /* Sel pojok (header kolom pertama) harus paling atas: sticky top+left sekaligus,
       jadi wajib z-index lebih tinggi dari td/th kolom pertama biasa (z-index 2),
       supaya baris body yang lewat di baliknya tidak "menembus" saat scroll ke bawah. */
    .goltarif-table thead th:first-child { z-index: 5; }
    .goltarif-table tfoot td {
        font-weight: 800; color: #fff; border-bottom: none; position: sticky; bottom: 0;
        border-top: 1px solid rgba(255,255,255,.35); z-index: 4;
    }
    .goltarif-table tfoot td:first-child { z-index: 5; }
    .goltarif-table tfoot td:nth-last-child(2) { background: transparent; color: #fff; }
    .goltarif-table tfoot td:last-child { color: rgba(255,255,255,.85); font-size: 12px; }

    .goltarif-card.tone-prabayar .goltarif-table thead th,
    .goltarif-card.tone-prabayar .goltarif-table thead th:first-child,
    .goltarif-card.tone-prabayar .goltarif-table tfoot td,
    .goltarif-card.tone-prabayar .goltarif-table tfoot td:first-child { background: #e4ebfb; color: #1d4ed8; }

    .goltarif-card.tone-paskabayar .goltarif-table thead th,
    .goltarif-card.tone-paskabayar .goltarif-table thead th:first-child,
    .goltarif-card.tone-paskabayar .goltarif-table tfoot td,
    .goltarif-card.tone-paskabayar .goltarif-table tfoot td:first-child { background: #fbedd9; color: #b45309; }

    /* Background sekarang pastel/terang, jadi garis pemisah & teks kolom Total/%
       di footer perlu warna gelap tipis (bukan putih transparan lagi)
       supaya tetap kontras dengan background barunya. */
    .goltarif-card.tone-prabayar .goltarif-table thead th:first-child::after,
    .goltarif-card.tone-prabayar .goltarif-table tfoot td:first-child::after { background: rgba(29,78,216,.25); }
    .goltarif-card.tone-paskabayar .goltarif-table thead th:first-child::after,
    .goltarif-card.tone-paskabayar .goltarif-table tfoot td:first-child::after { background: rgba(180,83,9,.25); }

    .goltarif-card.tone-prabayar .goltarif-table tfoot td { border-top-color: rgba(29,78,216,.2); }
    .goltarif-card.tone-paskabayar .goltarif-table tfoot td { border-top-color: rgba(180,83,9,.2); }

    .goltarif-card.tone-prabayar .goltarif-table tfoot td:nth-last-child(2) { color: #1d4ed8; }
    .goltarif-card.tone-paskabayar .goltarif-table tfoot td:nth-last-child(2) { color: #b45309; }
    .goltarif-card.tone-prabayar .goltarif-table tfoot td:last-child { color: rgba(29,78,216,.75); }
    .goltarif-card.tone-paskabayar .goltarif-table tfoot td:last-child { color: rgba(180,83,9,.75); }

    .goltarif-table tbody tr:nth-child(even) { background: #f8f9fc; }
    .goltarif-table tbody tr:hover { background: #eef2fb; }
    .goltarif-table tbody tr:hover td:first-child { background: #eef2fb; }
    .goltarif-table tbody tr:last-child td { border-bottom: none; }

    .goltarif-empty { text-align: center; color: #9aa4c2; padding: 32px; font-size: 13px; }

    .filter-wrap { position: relative; }
    .filter-wrap svg { position: absolute; left: 12px; top: 50%; transform: translateY(-50%); width: 14px; height: 14px; color: #9aa4c2; pointer-events: none; }
    .filter-select {
        border: 1px solid var(--border); border-radius: 9px; padding: 8px 14px 8px 34px;
        font-size: 13px; background: #fff; appearance: none; min-width: 110px;
    }

    .goltarif-title-select-wrap { position: relative; display: inline-flex; align-items: center; }
    .goltarif-title-select {
        appearance: none; -webkit-appearance: none; -moz-appearance: none;
        border: none; background: transparent; color: #1b2559; font: inherit; font-weight: 700;
        padding: 0 18px 0 0; margin: 0; cursor: pointer; max-width: 240px;
    }
    .goltarif-title-select:hover { color: #0b3d91; }
    .goltarif-title-select:focus { outline: none; color: #0b3d91; }
    .goltarif-title-select-wrap svg {
        position: absolute; right: 0; top: 50%; transform: translateY(-50%);
        width: 11px; height: 11px; color: #9aa4c2; pointer-events: none;
    }

    /* ===== Card Rekap KWH per ULP — tetap struktur & warna pastel per
       golongan seperti sudah disepakati sebelumnya. ===== */
    .ulp-card {
        padding: 24px; background: #fff; border: 1px solid var(--border);
        border-radius: 16px; box-sizing: border-box; margin-bottom: 20px;
        box-shadow: 0 1px 2px rgba(16,24,64,.04);
    }
    .ulp-card-head {
        display: flex; align-items: center; justify-content: space-between;
        margin-bottom: 18px; padding-bottom: 14px; border-bottom: 1px solid #f1f3f9; gap: 12px; flex-wrap: wrap;
    }
    .ulp-card h3 { margin: 0 0 3px; font-size: 15.5px; color: #1b2559; }
    .ulp-card .sub { margin: 0; font-size: 12.5px; color: #6b7690; }

    .ulp-table-scroll { overflow-x: auto; border-radius: 12px; border: 1px solid var(--border); }
    .ulp-table { width: 100%; border-collapse: collapse; font-size: 12px; min-width: 1180px; }
    .ulp-table th, .ulp-table td {
        padding: 9px 10px; text-align: center; border-bottom: 1px solid #eef0f6; border-right: 1px solid #eef0f6; white-space: nowrap;
    }

    .ulp-table thead th { font-weight: 800; font-size: 10.5px; text-transform: uppercase; letter-spacing: .02em; }
    .ulp-table thead tr.grp-row th { padding-top: 10px; padding-bottom: 8px; text-align: center; border-bottom: none; }
    .ulp-table thead tr.sub-row th { font-size: 9.5px; font-weight: 700; padding-top: 6px; padding-bottom: 9px; }

    .ulp-table thead tr:first-child th.col-ulp { background: #eef0f6; color: #1b2559; vertical-align: middle; }

    .ulp-table thead th.grp-p1 { background: #e3f6ea; color: #15803d; }
    .ulp-table thead th.grp-p2 { background: #e4ebfb; color: #1d4ed8; }
    .ulp-table thead th.grp-p3 { background: #fbedd9; color: #b45309; }
    .ulp-table thead th.grp-p4 { background: #fbe2e2; color: #b91c1c; }
    .ulp-table thead th.grp-k1 { background: #ece5fc; color: #6d28d9; }
    .ulp-table thead th.grp-k2 { background: #dcf1fc; color: #0369a1; }
    .ulp-table thead th.grp-k3 { background: #dcf6e5; color: #15803d; }
    .ulp-table thead th.grp-total { background: #eef0f6; color: #1b2559; }

    .ulp-table thead th.grp-p1, .ulp-table thead th.grp-p2, .ulp-table thead th.grp-p3, .ulp-table thead th.grp-p4,
    .ulp-table thead th.grp-k1, .ulp-table thead th.grp-k2, .ulp-table thead th.grp-k3, .ulp-table thead th.grp-total {
        border-left: 1px solid rgba(255,255,255,.7);
    }

    .ulp-table th, .ulp-table td { box-sizing: border-box; }

    /* Hanya kolom ULP yang di-freeze. Kolom No sengaja tidak sticky lagi supaya
       tidak perlu dua batas sticky yang harus align pixel-perfect (itu penyebab
       garis/celah putih sebelumnya) — cukup satu batas sticky di kiri. */
    /* Kolom ULP sekarang kolom PERTAMA tabel (nomor urut digabung sebagai badge
       kecil di dalamnya) supaya sticky-nya stabil — sticky di kolom yang bukan
       kolom pertama gampang salah hitung offset dan ketiban kolom sebelumnya
       saat discroll. */
    .ulp-table th.col-ulp, .ulp-table td.col-ulp { position: sticky; left: 0; z-index: 2; text-align: left; font-weight: 700; min-width: 170px; background: #fff; }
    .ulp-table td.col-ulp { color: #1b2559; }
    .ulp-table th.col-ulp { color: #1b2559; }
    .ulp-table tbody tr:nth-child(even) td.col-ulp { background: #f8f9fc; }

    .ulp-no {
        display: inline-flex; align-items: center; justify-content: center;
        width: 20px; height: 20px; border-radius: 6px; margin-right: 8px;
        background: #eef0f6; color: #6b7690; font-size: 10.5px; font-weight: 800;
        flex-shrink: 0;
    }

    .ulp-table tbody tr:nth-child(even) { background: #f8f9fc; }
    .ulp-table tbody tr:hover { background: #eef2fb; }

    .ulp-table td.cell-persen { position: relative; text-align: right; padding-right: 10px; }
    .persen-bar {
        position: absolute; left: 4px; top: 4px; bottom: 4px;
        width: calc(var(--pct) * 0.01 * 30px); max-width: 30px;
        background: #a7e0bf; border-radius: 3px; opacity: .8;
    }
    .persen-text { position: relative; z-index: 1; }

    .ulp-table tfoot td { font-weight: 800; background: #f7f8fc; color: #1b2559; border-top: 1px solid var(--border); border-bottom: none; }
    .ulp-table tfoot td:first-child { position: sticky; left: 0; z-index: 3; }
</style>
@endpush

@section('content')

@php
    $kolomUlpKTampil = collect($kolomUlpK)->reject(fn ($g) => strtoupper($g) === 'K4')->values();
@endphp

<div style="display:flex;align-items:flex-start;justify-content:space-between;margin-bottom:22px;flex-wrap:wrap;gap:12px;">
    <div>
        <h2 class="trend-page-title">Laporan Gol Tarif</h2>
        <p style="color:#6b7690;margin:0;font-size:14px;">Distribusi Rp TS berdasarkan golongan tarif &mdash; Prabayar vs Paskabayar</p>
    </div>
</div>

@php
    // Tahun dipindah jadi tab di panel Filter Periode & ULP (bukan
    // dropdown terpisah lagi di pojok kanan atas) — $tahunAktif &
    // $daftarTahun udah otomatis ada di halaman ini (dari controller
    // yang sama, kepake sebelumnya buat dropdown lama), jadi cukup
    // set flag ini di sini tanpa perlu ubah controller sama sekali.
    $tampilkanTahunFilter = true;
@endphp
@include('laporan.partials.filter-periode-ulp')

<div class="goltarif-grid">

    {{-- ===== PRABAYAR ===== --}}
    <div class="goltarif-card tone-prabayar">
        <div class="goltarif-card-head">
            <div>
                <h3>
                    <span class="dot prabayar"></span>
                    <div class="goltarif-title-select-wrap">
                        <select id="prabayarViewSelect" class="goltarif-title-select" onchange="toggleGolTarifView(this.value)">
                            <option value="tarif">Gol Tarif Prabayar</option>
                            <option value="daya">Gol per Daya Prabayar</option>
                        </select>
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M6 9l6 6 6-6"/></svg>
                    </div>
                </h3>
                <p class="sub" id="prabayarSub">Distribusi Rp TS per golongan</p>
            </div>
            <div class="goltarif-head-actions">
                <span class="goltarif-year-badge">{{ $tahunAktif ?: '-' }}</span>
                <button type="button" class="copy-btn" id="prabayarCopyBtn" onclick="salinTabelGambar('capture-prabayar', this, document.getElementById('prabayarViewSelect').selectedOptions[0].text)">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="9" y="9" width="13" height="13" rx="2"/><path d="M5 15H4a2 2 0 0 1-2-2V4a2 2 0 0 1 2-2h9a2 2 0 0 1 2 2v1"/></svg>
                    Salin Gambar
                </button>
            </div>
        </div>

        <div id="capture-prabayar">

            {{-- ===== View: Gol Tarif (default) ===== --}}
            <div id="view-tarif">
                <div class="goltarif-chart-wrap">
                    <canvas id="chartPrabayar"></canvas>
                </div>

                <div class="goltarif-table-scroll">
                    @if (count($prabayar) > 0)
                        <table class="goltarif-table" id="tabel-prabayar">
                            <thead>
                                <tr>
                                    <th>Tarif</th>
                                    @foreach ($kolomGol as $g)
                                        <th>{{ $g }}</th>
                                    @endforeach
                                    <th>Total</th>
                                    <th>%</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($prabayar as $baris)
                                    <tr>
                                        <td>{{ $baris['label'] }}</td>
                                        @foreach ($kolomGol as $g)
                                            <td>{{ number_format($baris['nilai'][$g], 0, ',', '.') }}</td>
                                        @endforeach
                                        <td>{{ number_format($baris['total'], 0, ',', '.') }}</td>
                                        <td>{{ number_format($baris['persen'], 2, ',', '.') }}%</td>
                                    </tr>
                                @endforeach
                            </tbody>
                            <tfoot>
                                <tr>
                                    <td>TOTAL</td>
                                    @foreach ($kolomGol as $g)
                                        <td>{{ number_format($totalPrabayar[$g], 0, ',', '.') }}</td>
                                    @endforeach
                                    <td>{{ number_format($totalPrabayar['grand_total'], 0, ',', '.') }}</td>
                                    <td>100,00%</td>
                                </tr>
                            </tfoot>
                        </table>
                    @else
                        <p class="goltarif-empty">Belum ada data prabayar untuk filter ini.</p>
                    @endif
                </div>
            </div>

            {{-- ===== View: Gol per Daya ===== --}}
            <div id="view-daya" style="display:none;">
                <div class="goltarif-chart-wrap">
                    <canvas id="chartPrabayarDaya"></canvas>
                </div>

                <div class="goltarif-table-scroll">
                    @if (count($prabayarPerDaya) > 0)
                        <table class="goltarif-table" id="tabel-prabayar-daya">
                            <thead>
                                <tr>
                                    <th>Daya</th>
                                    @foreach ($kolomGol as $g)
                                        <th>{{ $g }}</th>
                                    @endforeach
                                    <th>Total</th>
                                    <th>%</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($prabayarPerDaya as $baris)
                                    <tr>
                                        <td>{{ $baris['label'] }}</td>
                                        @foreach ($kolomGol as $g)
                                            <td>{{ number_format($baris['nilai'][$g], 0, ',', '.') }}</td>
                                        @endforeach
                                        <td>{{ number_format($baris['total'], 0, ',', '.') }}</td>
                                        <td>{{ number_format($baris['persen'], 2, ',', '.') }}%</td>
                                    </tr>
                                @endforeach
                            </tbody>
                            <tfoot>
                                <tr>
                                    <td>TOTAL</td>
                                    @foreach ($kolomGol as $g)
                                        <td>{{ number_format($totalPrabayarPerDaya[$g], 0, ',', '.') }}</td>
                                    @endforeach
                                    <td>{{ number_format($totalPrabayarPerDaya['grand_total'], 0, ',', '.') }}</td>
                                    <td>100,00%</td>
                                </tr>
                            </tfoot>
                        </table>
                    @else
                        <p class="goltarif-empty">Belum ada data gol per daya untuk filter ini.</p>
                    @endif
                </div>
            </div>

        </div>
    </div>

    {{-- ===== PASKABAYAR ===== --}}
    <div class="goltarif-card tone-paskabayar">
        <div class="goltarif-card-head">
            <div>
                <h3><span class="dot paskabayar"></span> Gol Tarif Paskabayar</h3>
                <p class="sub">Distribusi Rp TS per golongan</p>
            </div>
            <div class="goltarif-head-actions">
                <span class="goltarif-year-badge">{{ $tahunAktif ?: '-' }}</span>
                <button type="button" class="copy-btn" onclick="salinTabelGambar('capture-paskabayar', this, 'Gol Tarif Paskabayar')">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="9" y="9" width="13" height="13" rx="2"/><path d="M5 15H4a2 2 0 0 1-2-2V4a2 2 0 0 1 2-2h9a2 2 0 0 1 2 2v1"/></svg>
                    Salin Gambar
                </button>
            </div>
        </div>

        <div id="capture-paskabayar">
            <div class="goltarif-chart-wrap">
                <canvas id="chartPaskabayar"></canvas>
            </div>

            <div class="goltarif-table-scroll">
                @if (count($paskabayar) > 0)
                    <table class="goltarif-table" id="tabel-paskabayar">
                        <thead>
                            <tr>
                                <th>Tarif</th>
                                @foreach ($kolomGol as $g)
                                    <th>{{ $g }}</th>
                                @endforeach
                                <th>Total</th>
                                <th>%</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($paskabayar as $baris)
                                <tr>
                                    <td>{{ $baris['label'] }}</td>
                                    @foreach ($kolomGol as $g)
                                        <td>{{ number_format($baris['nilai'][$g], 0, ',', '.') }}</td>
                                    @endforeach
                                    <td>{{ number_format($baris['total'], 0, ',', '.') }}</td>
                                    <td>{{ number_format($baris['persen'], 2, ',', '.') }}%</td>
                                </tr>
                            @endforeach
                        </tbody>
                        <tfoot>
                            <tr>
                                <td>TOTAL</td>
                                @foreach ($kolomGol as $g)
                                    <td>{{ number_format($totalPaskabayar[$g], 0, ',', '.') }}</td>
                                @endforeach
                                <td>{{ number_format($totalPaskabayar['grand_total'], 0, ',', '.') }}</td>
                                <td>100,00%</td>
                            </tr>
                        </tfoot>
                    </table>
                @else
                    <p class="goltarif-empty">Belum ada data paskabayar untuk filter ini.</p>
                @endif
            </div>
        </div>
    </div>
</div>

{{-- ===== TABEL PER ULP — GOLONGAN P ===== --}}
<div class="ulp-card">
    <div class="ulp-card-head">
        <div>
            <h3>Rekap KWH per ULP — Golongan P</h3>
            <p class="sub">Jumlah Pelanggan, KWH, dan persentase &mdash; UID Jawa Barat</p>
        </div>
        <div class="goltarif-head-actions">
            <span class="goltarif-year-badge">{{ $tahunAktif ?: '-' }}</span>
            <button type="button" class="copy-btn" onclick="salinTabelGambar('capture-ulp-p', this, 'Rekap KWH per ULP — Golongan P')">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="9" y="9" width="13" height="13" rx="2"/><path d="M5 15H4a2 2 0 0 1-2-2V4a2 2 0 0 1 2-2h9a2 2 0 0 1 2 2v1"/></svg>
                Salin Gambar
            </button>
        </div>
    </div>

    <div id="capture-ulp-p">
        <div class="ulp-table-scroll">
            @if (count($ulpRowsP) > 0)
                <table class="ulp-table" id="tabel-ulp-p">
                    <thead>
                        <tr class="grp-row">
                            <th rowspan="2" class="col-ulp">ULP</th>
                            @foreach ($kolomUlpP as $g)
                                @php $key = strtolower($g); @endphp
                                <th colspan="3" class="grp-{{ $key }}">{{ $g }}</th>
                            @endforeach
                            <th colspan="3" class="grp-total">Total</th>
                        </tr>
                        <tr class="sub-row">
                            @foreach ($kolomUlpP as $g)
                                @php $key = strtolower($g); @endphp
                                <th class="grp-{{ $key }}">Pelanggan</th><th class="grp-{{ $key }}">KWH</th><th class="grp-{{ $key }}">%</th>
                            @endforeach
                            <th class="grp-total">Pelanggan</th><th class="grp-total">KWH</th><th class="grp-total">%</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($ulpRowsP as $i => $row)
                            <tr>
                                <td class="col-ulp"><span class="ulp-no">{{ $i + 1 }}</span>{{ $row['nama'] }}</td>

                                @foreach ($kolomUlpP as $g)
                                    @php $key = strtolower($g); @endphp
                                    <td>{{ number_format($row[$key]['plg'], 0, ',', '.') }}</td>
                                    <td>{{ number_format($row[$key]['kwh'], 0, ',', '.') }}</td>
                                    <td class="cell-persen">
                                        <span class="persen-bar" style="--pct: {{ $row[$key]['persen'] }}%"></span>
                                        <span class="persen-text">{{ number_format($row[$key]['persen'], 2, ',', '.') }}%</span>
                                    </td>
                                @endforeach

                                <td>{{ number_format($row['total']['plg'], 0, ',', '.') }}</td>
                                <td>{{ number_format($row['total']['kwh'], 0, ',', '.') }}</td>
                                <td class="cell-persen">
                                    <span class="persen-bar" style="--pct: {{ $row['total']['persen'] }}%"></span>
                                    <span class="persen-text">{{ number_format($row['total']['persen'], 2, ',', '.') }}%</span>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                    <tfoot>
                        <tr>
                            <td class="col-ulp">UID JABAR</td>
                            @foreach ($kolomUlpP as $g)
                                @php $key = strtolower($g); @endphp
                                <td>{{ number_format($ulpTotalP[$key]['plg'], 0, ',', '.') }}</td>
                                <td>{{ number_format($ulpTotalP[$key]['kwh'], 0, ',', '.') }}</td>
                                <td>{{ number_format($ulpTotalP[$key]['persen'], 2, ',', '.') }}%</td>
                            @endforeach
                            <td>{{ number_format($ulpTotalP['total']['plg'], 0, ',', '.') }}</td>
                            <td>{{ number_format($ulpTotalP['total']['kwh'], 0, ',', '.') }}</td>
                            <td>{{ number_format($ulpTotalP['total']['persen'], 2, ',', '.') }}%</td>
                        </tr>
                    </tfoot>
                </table>
            @else
                <p class="goltarif-empty">Belum ada data KWH golongan P per ULP untuk filter ini.</p>
            @endif
        </div>
    </div>
</div>

{{-- ===== TABEL PER ULP — GOLONGAN K ===== --}}
<div class="ulp-card">
    <div class="ulp-card-head">
        <div>
            <h3>Rekap KWH per ULP — Golongan K</h3>
            <p class="sub">Jumlah Pelanggan, KWH, dan persentase &mdash; UID Jawa Barat</p>
        </div>
        <div class="goltarif-head-actions">
            <span class="goltarif-year-badge">{{ $tahunAktif ?: '-' }}</span>
            <button type="button" class="copy-btn" onclick="salinTabelGambar('capture-ulp-k', this, 'Rekap KWH per ULP — Golongan K')">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="9" y="9" width="13" height="13" rx="2"/><path d="M5 15H4a2 2 0 0 1-2-2V4a2 2 0 0 1 2-2h9a2 2 0 0 1 2 2v1"/></svg>
                Salin Gambar
            </button>
        </div>
    </div>

    <div id="capture-ulp-k">
        <div class="ulp-table-scroll">
            @if (count($ulpRowsK) > 0)
                <table class="ulp-table" id="tabel-ulp-k">
                    <thead>
                        <tr class="grp-row">
                            <th rowspan="2" class="col-ulp">ULP</th>
                            @foreach ($kolomUlpKTampil as $g)
                                @php $key = strtolower($g); @endphp
                                <th colspan="3" class="grp-{{ $key }}">{{ $g }}</th>
                            @endforeach
                            <th colspan="3" class="grp-total">Total</th>
                        </tr>
                        <tr class="sub-row">
                            @foreach ($kolomUlpKTampil as $g)
                                @php $key = strtolower($g); @endphp
                                <th class="grp-{{ $key }}">Pelanggan</th><th class="grp-{{ $key }}">KWH</th><th class="grp-{{ $key }}">%</th>
                            @endforeach
                            <th class="grp-total">Pelanggan</th><th class="grp-total">KWH</th><th class="grp-total">%</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($ulpRowsK as $i => $row)
                            <tr>
                                <td class="col-ulp"><span class="ulp-no">{{ $i + 1 }}</span>{{ $row['nama'] }}</td>

                                @foreach ($kolomUlpKTampil as $g)
                                    @php $key = strtolower($g); @endphp
                                    <td>{{ number_format($row[$key]['plg'], 0, ',', '.') }}</td>
                                    <td>{{ number_format($row[$key]['kwh'], 0, ',', '.') }}</td>
                                    <td class="cell-persen">
                                        <span class="persen-bar" style="--pct: {{ $row[$key]['persen'] }}%"></span>
                                        <span class="persen-text">{{ number_format($row[$key]['persen'], 2, ',', '.') }}%</span>
                                    </td>
                                @endforeach

                                <td>{{ number_format($row['total']['plg'], 0, ',', '.') }}</td>
                                <td>{{ number_format($row['total']['kwh'], 0, ',', '.') }}</td>
                                <td class="cell-persen">
                                    <span class="persen-bar" style="--pct: {{ $row['total']['persen'] }}%"></span>
                                    <span class="persen-text">{{ number_format($row['total']['persen'], 2, ',', '.') }}%</span>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                    <tfoot>
                        <tr>
                            <td class="col-ulp">UID JABAR</td>
                            @foreach ($kolomUlpKTampil as $g)
                                @php $key = strtolower($g); @endphp
                                <td>{{ number_format($ulpTotalK[$key]['plg'], 0, ',', '.') }}</td>
                                <td>{{ number_format($ulpTotalK[$key]['kwh'], 0, ',', '.') }}</td>
                                <td>{{ number_format($ulpTotalK[$key]['persen'], 2, ',', '.') }}%</td>
                            @endforeach
                            <td>{{ number_format($ulpTotalK['total']['plg'], 0, ',', '.') }}</td>
                            <td>{{ number_format($ulpTotalK['total']['kwh'], 0, ',', '.') }}</td>
                            <td>{{ number_format($ulpTotalK['total']['persen'], 2, ',', '.') }}%</td>
                        </tr>
                    </tfoot>
                </table>
            @else
                <p class="goltarif-empty">Belum ada data KWH golongan K per ULP untuk filter ini.</p>
            @endif
        </div>
    </div>
</div>

@endsection

@push('scripts')
<script>
function toggleGolTarifView(view) {
    document.getElementById('view-tarif').style.display = view === 'tarif' ? '' : 'none';
    document.getElementById('view-daya').style.display  = view === 'daya'  ? '' : 'none';

    var sub = document.getElementById('prabayarSub');
    if (sub) {
        sub.textContent = view === 'tarif'
            ? 'Distribusi Rp TS per golongan'
            : 'Distribusi Rp TS per daya';
    }
}

(function () {
    if (typeof Chart === 'undefined') {
        console.error('Chart.js belum termuat.');
        return;
    }

    function buatPie(canvasId, labels, data) {
        var canvas = document.getElementById(canvasId);
        if (!canvas) return;

        var existing = Chart.getChart(canvas);
        if (existing) existing.destroy();

        new Chart(canvas, {
            type: 'pie',
            data: {
                labels: labels,
                datasets: [{
                    data: data,
                    backgroundColor: ['#5a7fd6', '#e6a15a', '#9aa4c2', '#f0cf6f', '#6bbf8f', '#e07a9e', '#7c93d6', '#8fa8e0'],
                    borderWidth: 2,
                    borderColor: '#fff',
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                layout: { padding: { bottom: 8 } },
                plugins: {
                    legend: {
                        position: 'bottom',
                        align: 'center',
                        labels: {
                            boxWidth: 10,
                            font: { size: 11 },
                            padding: 10,
                            usePointStyle: true,
                        }
                    },
                    tooltip: {
                        callbacks: {
                            label: function (ctx) {
                                var val = Number(ctx.raw).toLocaleString('id-ID');
                                var total = ctx.dataset.data.reduce(function (a, b) { return a + b; }, 0);
                                var pct = total > 0 ? (ctx.raw / total * 100).toFixed(2) : 0;
                                return ctx.label + ': Rp ' + val + ' (' + pct + '%)';
                            }
                        }
                    }
                }
            }
        });
    }

    var prabayarLabels = @json(collect($prabayar)->where('total', '>', 0)->pluck('label')->values());
    var prabayarData   = @json(collect($prabayar)->where('total', '>', 0)->pluck('total')->values());
    buatPie('chartPrabayar', prabayarLabels, prabayarData);

    var prabayarDayaLabels = @json(collect($prabayarPerDaya)->where('total', '>', 0)->pluck('label')->values());
    var prabayarDayaData   = @json(collect($prabayarPerDaya)->where('total', '>', 0)->pluck('total')->values());
    buatPie('chartPrabayarDaya', prabayarDayaLabels, prabayarDayaData);

    var paskabayarLabels = @json(collect($paskabayar)->where('total', '>', 0)->pluck('label')->values());
    var paskabayarData   = @json(collect($paskabayar)->where('total', '>', 0)->pluck('total')->values());
    buatPie('chartPaskabayar', paskabayarLabels, paskabayarData);
})();
</script>
@include('laporan.partials.copy-image-script')
@endpush