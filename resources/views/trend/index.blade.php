@extends('layouts.app')
@section('title', $metric === 'kwh' ? 'Trend kWh' : 'Trend Rp TS')

@section('breadcrumb')
    <a href="{{ route('dashboard') }}">Beranda</a>
    <span class="sep">›</span>
    <strong>{{ $metric === 'kwh' ? 'Trend kWh' : 'Trend Rp TS' }}</strong>
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

    /* ===== Header halaman — dikasih clamp biar judul gak makan tempat
       kebanyakan di layar sempit tapi tetap gede di desktop. ===== */
    .trend-page-title { font-size: clamp(18px, 4.2vw, 22px); margin: 0 0 4px; }

    /* ===== Filter card (gradient, kayak versi awal) ===== */
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
    .trend-mode-toggle { display: inline-flex; background: rgba(255,255,255,.15); border-radius: 9px; padding: 3px; gap: 2px; }
    .trend-mode-toggle a {
        padding: 8px 16px; font-size: 13px; font-weight: 700; border-radius: 7px;
        text-decoration: none; color: #fff;
    }
    .trend-mode-toggle a.active { background: #ffce3a; color: #071233; }

    .trend-filter-left .info-icon {
        display: flex;
        align-items: center;
        justify-content: center;
        border-radius: 10px;
        flex-shrink: 0;
    }

    .chart-badge {
        display: inline-flex;
        align-items: center;
        padding: 6px 14px;
        border-radius: 20px;
        font-size: 12.5px;
        font-weight: 700;
        white-space: nowrap;
        max-width: 100%;
        overflow: hidden;
        text-overflow: ellipsis;
    }

    /* ===== Chart card ===== */
    .trend-chart-card { padding: 22px; margin-bottom: 20px; }
    .trend-chart-head { display: flex; align-items: flex-start; justify-content: space-between; flex-wrap: wrap; gap: 8px; margin-bottom: 16px; }
    .trend-chart-head h3 { margin: 0 0 2px; font-size: 16px; color: #1b2559; }
    .trend-chart-head p { margin: 0; font-size: 12.5px; color: #6b7690; }

    .trend-chart-canvas-wrap { position: relative; height: 320px; width: 100%; }

    /* ===== Tabel horizontal (bulan = kolom, Target/Realisasi = baris) ===== */
    .trend-hz-table-wrap { padding: 0; overflow: hidden; }
    .trend-hz-table { width: 100%; border-collapse: collapse; }
    .trend-hz-table th, .trend-hz-table td {
        padding: 13px 16px; font-size: 13px; text-align: center; white-space: nowrap;
        border-bottom: 1px solid var(--border); border-right: 1px solid var(--border);
    }
    .trend-hz-table th:first-child, .trend-hz-table td:first-child {
        text-align: left; font-weight: 700; color: var(--text-dark);
        background: #fafbfe; position: sticky; left: 0; z-index: 1;
    }
    .trend-hz-table thead th {
        color: var(--text-muted); font-weight: 700; font-size: 11.5px;
        text-transform: uppercase; letter-spacing: .03em; background: #fafbfe;
    }
    .trend-hz-table tbody tr:last-child td { border-bottom: none; }
    .trend-hz-table tbody td { color: var(--text-dark); font-weight: 500; }
    .trend-hz-table tbody tr:first-child td { color: #c0246b; } /* baris Target */
    .trend-hz-table tbody tr:last-child td:not(:first-child) { color: #1a9c4a; } /* baris Realisasi */

    /* ===== Card "Selisih dari Target" — sengaja dibikin ringkas =====
       Detail lengkap (persen per bulan, ranking bulan tertinggi/
       terendah, badge ijo/merah) udah ada di tab "Presentase
       Pencapaian". Di sini cukup kasih tau selisih nominalnya aja +
       link ke sana, biar gak dobel nampilin kesimpulan yang sama
       dengan cara berbeda. */
    .dash-stat-card.tone-pink::before { background: #d81b60; }
    .tone-pink .dash-stat-icon { background: #fde6f0; color: #d81b60; }

    /* Tone netral buat card "Selisih dari Target" pas target belum
       diisi sama sekali — sengaja dibedain dari tone-pink/tone-green
       biar gak keliatan kayak ada angka selisih beneran padahal
       gak ada target buat dibandingin. */
    .dash-stat-card.tone-abu::before { background: #9aa4c2; }
    .tone-abu .dash-stat-icon { background: #eef0f6; color: #6b7690; }
    .dash-stat-detail-link {
        color: inherit; text-decoration: none; font-weight: 700;
        display: inline-flex; align-items: center; gap: 4px;
    }
    .dash-stat-detail-link:hover { text-decoration: underline; }
    .dash-stat-detail-link svg { width: 12px; height: 12px; }

    .trend-filter-right { display: flex; align-items: center; gap: 10px; flex-wrap: wrap; }

    /* Dikunci 2 kolom mulai tablet biar kartunya gak terlalu lebar/gepeng
       di lebar-lebar "aneh" (samain sama tab Presentase Pencapaian). */
    @media (max-width: 900px) {
        .dash-stats { grid-template-columns: repeat(2, 1fr); }
    }

    @media (max-width: 640px) {
        .trend-filter-card { padding: 14px 16px; flex-direction: column; align-items: stretch; }
        .trend-filter-left { width: 100%; }
        .trend-filter-right { width: 100%; }
        .trend-filter-form { width: 100%; }
        .trend-filter-form select { flex: 1; min-width: 0; }
        .trend-mode-toggle { width: 100%; justify-content: center; }
        .trend-mode-toggle a { flex: 1; text-align: center; }
        .trend-chart-canvas-wrap { height: 260px; }
        .trend-chart-head { flex-direction: column; align-items: flex-start; }
        .chart-badge { align-self: flex-start; }

        /* Tabel horizontal (Target/Realisasi per bulan) — tetap
           discroll ke samping di HP (udah dibungkus .table-scroll),
           tapi padding & font dikecilin dikit biar lebih banyak
           kolom bulan yang keliatan sekali swipe. */
        .trend-hz-table th, .trend-hz-table td { padding: 10px 12px; font-size: 12px; }
    }

    @media (max-width: 420px) {
        .dash-stats { grid-template-columns: 1fr; }
        .trend-filter-form { flex-direction: column; align-items: stretch; }
        .trend-filter-form select { width: 100%; }
        .trend-chart-canvas-wrap { height: 220px; }
        .trend-hz-table th, .trend-hz-table td { padding: 8px 10px; font-size: 11.5px; }
    }
</style>
@endpush

@php
    // Fallback sementara: kalau controller belum kirim $targetData,
    // isi 0 semua biar chart & tabel tetap render tanpa error.
    $targetData = $targetData ?? array_fill(0, count($labels), 0);

    // Total target tahun berjalan, disesuaikan sama mode tampilan:
    // - mode kumulatif -> $targetData udah berupa angka akumulasi, jadi
    //   total-nya tinggal ambil elemen terakhir.
    // - mode bulanan -> $targetData angka per bulan, jadi total-nya
    //   dijumlah semua.
    $totalTargetTahunIni = $mode === 'kumulatif'
        ? (float) (end($targetData) ?: 0)
        : (float) array_sum($targetData);

    // Selisih realisasi vs target: positif berarti realisasi MELEBIHI
    // target (kurang bagus buat metrik susut/TS), negatif berarti masih
    // di bawah target.
    $selisihTahunIni = $totalTahunIni - $totalTargetTahunIni;
@endphp

@section('content')

<div style="display:flex;align-items:flex-start;justify-content:space-between;margin-bottom:16px;flex-wrap:wrap;gap:12px;">
    <div>
        <h2 class="trend-page-title">{{ $metric === 'kwh' ? 'Trend Pemakaian kWh' : 'Trend Rp TS' }}</h2>
        <p style="color:#6b7690;margin:0;font-size:14px;">
            {{ $metric === 'kwh' ? 'Tren pemakaian kWh tagihan susulan per bulan.' : 'Tren nilai Rp TS (tagihan susulan) per bulan.' }}
        </p>
    </div>
</div>

{{--
    Urutan tab: "Presentase Pencapaian" ditaro paling depan karena itu
    yang paling sering dicek duluan ("udah sesuai target belum") —
    baru kalau perlu drill-down ke nilai aktualnya, pindah ke Trend
    kWh / Rp TS. Tab ini sebelumnya malah gak ada sama sekali di
    halaman index, sekarang ditambahin biar bisa pindah tab tanpa
    balik ke menu dulu.
--}}
<div class="trend-tabs">
    <a href="{{ route('trend.pencapaian', request()->only('tahun', 'ulp')) }}">Presentase Pencapaian</a>
    <a href="{{ route('trend.kwh', request()->only('tahun', 'ulp', 'mode')) }}" class="{{ $metric === 'kwh' ? 'active' : '' }}">Trend kWh</a>
    <a href="{{ route('trend.ts', request()->only('tahun', 'ulp', 'mode')) }}" class="{{ $metric === 'ts' ? 'active' : '' }}">Trend Rp TS</a>
</div>

<div class="card trend-filter-card">
    <div class="trend-filter-left">
        <div class="info-icon" style="width:34px;height:34px;background:rgba(255,255,255,.15);color:#ffce3a;">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="width:16px;height:16px;"><path d="M3 3v18h18"/><path d="M7 15l4-5 3 3 5-7"/></svg>
        </div>
        <div>
            <strong style="font-size:14px;color:#fff;">Filter Trend</strong>
            <p style="margin:2px 0 0;font-size:12px;color:rgba(255,255,255,.85);">
                Tahun {{ $tahunAktif ?: '-' }} &middot; {{ $ulpAktif === 'semua' ? 'Semua ULP' : ($daftarUlp->firstWhere('kode', $ulpAktif)['nama'] ?? $ulpAktif) }}
            </p>
        </div>
    </div>

    <div class="trend-filter-right">
        <form method="GET" class="trend-filter-form">
            <input type="hidden" name="mode" value="{{ $mode }}">

            <select name="tahun" onchange="this.form.submit()">
                @forelse ($daftarTahun as $t)
                    <option value="{{ $t }}" {{ (int) $tahunAktif === (int) $t ? 'selected' : '' }}>{{ $t }}</option>
                @empty
                    <option value="">Belum ada data</option>
                @endforelse
            </select>

            <select name="ulp" onchange="this.form.submit()">
                <option value="semua" {{ (string) $ulpAktif === 'semua' ? 'selected' : '' }}>Semua ULP</option>
                @foreach ($daftarUlp as $u)
                    <option value="{{ $u['kode'] }}" {{ (string) $ulpAktif === (string) $u['kode'] ? 'selected' : '' }}>{{ $u['kode'] }} - {{ $u['nama'] }}</option>
                @endforeach
            </select>
        </form>

        <div class="trend-mode-toggle">
            <a href="{{ request()->fullUrlWithQuery(['mode' => 'bulanan']) }}" class="{{ $mode === 'bulanan' ? 'active' : '' }}">Bulan</a>
            <a href="{{ request()->fullUrlWithQuery(['mode' => 'kumulatif']) }}" class="{{ $mode === 'kumulatif' ? 'active' : '' }}">Komulatif</a>
        </div>
    </div>
</div>

<div class="dash-stats">
    <div class="dash-stat-card tone-blue">
        <div class="dash-stat-top">
            <div class="dash-stat-icon">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M3 3v18h18"/><path d="M7 15l4-5 3 3 5-7"/></svg>
            </div>
            <h3>Total {{ $tahunAktif ?: '-' }}</h3>
        </div>
        <div class="dash-stat-value">
            {{ $metric === 'kwh' ? number_format($totalTahunIni, 0, ',', '.') . ' KWH' : 'Rp ' . number_format($totalTahunIni, 0, ',', '.') }}
        </div>
        <div class="dash-stat-sub">Sesuai filter tahun &amp; ULP terpilih</div>
    </div>

    <div class="dash-stat-card tone-yellow">
        <div class="dash-stat-top">
            <div class="dash-stat-icon">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="4" width="18" height="18" rx="2"/><path d="M16 2v4M8 2v4M3 10h18"/></svg>
            </div>
            <h3>Rata-rata / Bulan</h3>
        </div>
        <div class="dash-stat-value">
            {{ $metric === 'kwh' ? number_format($rataRataBulanan, 0, ',', '.') . ' KWH' : 'Rp ' . number_format($rataRataBulanan, 0, ',', '.') }}
        </div>
        <div class="dash-stat-sub">Rata-rata dari bulan yang ada datanya</div>
    </div>

    <div class="dash-stat-card tone-green">
        <div class="dash-stat-top">
            <div class="dash-stat-icon">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 2 3 7v6c0 5 4 8.5 9 9 5-.5 9-4 9-9V7l-9-5Z"/></svg>
            </div>
            <h3>Bulan Tertinggi</h3>
        </div>
        <div class="dash-stat-value">{{ $bulanTertinggiLabel ?? '-' }}</div>
        <div class="dash-stat-sub">
            {{ $bulanTertinggiLabel ? ($metric === 'kwh' ? number_format($bulanTertinggiNilai, 0, ',', '.') . ' KWH' : 'Rp ' . number_format($bulanTertinggiNilai, 0, ',', '.')) : 'Belum ada data' }}
        </div>
    </div>

    <div class="dash-stat-card {{ $totalTargetTahunIni == 0 ? 'tone-abu' : ($selisihTahunIni > 0 ? 'tone-pink' : 'tone-green') }}">
        <div class="dash-stat-top">
            <div class="dash-stat-icon">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M8 3v3m8-3v3M4 21V8a2 2 0 0 1 2-2h12a2 2 0 0 1 2 2v13M4 21h16M9 12h6M9 16h6"/></svg>
            </div>
            <h3>Selisih dari Target</h3>
        </div>
        @if ($totalTargetTahunIni == 0)
            <div class="dash-stat-value" style="color:#6b7690;">Target belum diisi</div>
            <div class="dash-stat-sub">Isi target dulu di Edit Target biar selisihnya bisa dihitung</div>
        @else
            <div class="dash-stat-value" style="color:{{ $selisihTahunIni > 0 ? '#d81b60' : '#1a9c4a' }};word-break:break-word;">
                {{ $selisihTahunIni > 0 ? '+' : '' }}{{ $metric === 'kwh' ? number_format($selisihTahunIni, 0, ',', '.') . ' KWH' : 'Rp ' . number_format($selisihTahunIni, 0, ',', '.') }}
            </div>
            <div class="dash-stat-sub">
                <a href="{{ route('trend.pencapaian', request()->only('tahun', 'ulp')) }}" class="dash-stat-detail-link">
                    Lihat detail pencapaian
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M5 12h14"/><path d="m12 5 7 7-7 7"/></svg>
                </a>
            </div>
        @endif
    </div>
</div>

<div class="card trend-chart-card">
    <div class="trend-chart-head">
        <div>
            <h3>{{ $mode === 'kumulatif' ? 'Trend Komulatif' : 'Trend Bulanan' }} — {{ $metric === 'kwh' ? 'kWh' : 'Rp TS' }}</h3>
            <p>{{ $mode === 'kumulatif' ? 'Akumulasi nilai dari Januari sampai bulan berjalan' : 'Nilai per bulan (tidak diakumulasi)' }} &mdash; Tahun {{ $tahunAktif ?: '-' }}</p>
        </div>
        <span class="chart-badge" style="background:#eaf0fb;color:#0b3d91;">{{ $ulpAktif === 'semua' ? 'Semua ULP' : (($daftarUlp->firstWhere('kode', $ulpAktif)['nama'] ?? null) ? $ulpAktif . ' - ' . $daftarUlp->firstWhere('kode', $ulpAktif)['nama'] : $ulpAktif) }}</span>
    </div>
    <div class="trend-chart-canvas-wrap">
        <canvas id="trendChart"></canvas>
    </div>
</div>

<div class="card trend-hz-table-wrap">
    <div style="padding:18px 22px;border-bottom:1px solid var(--border);">
        <strong style="font-size:14.5px;color:#1b2559;">Rincian per Bulan — Target vs Realisasi</strong>
    </div>
    <div class="table-scroll">
        <table class="trend-hz-table">
            <thead>
                <tr>
                    <th></th>
                    @foreach ($labels as $label)
                        <th>{{ $label }}</th>
                    @endforeach
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td>Target</td>
                    @foreach ($targetData as $nilaiTarget)
                        <td>{{ number_format($nilaiTarget, 2, ',', '.') }}</td>
                    @endforeach
                </tr>
                <tr>
                    <td>Realisasi</td>
                    @foreach ($data as $nilaiRealisasi)
                        <td>{{ number_format($nilaiRealisasi, 2, ',', '.') }}</td>
                    @endforeach
                </tr>
            </tbody>
        </table>
    </div>
</div>
@endsection

{{--
    CATATAN:
    1. Chart.js sudah di-load sekali di layouts/app.blade.php, gak di-load
       ulang di sini.
    2. Canvas dibungkus div ber-tinggi pasti (.trend-chart-canvas-wrap) +
       maintainAspectRatio:false, biar tingginya konsisten.
    3. Chart.getChart() dipakai buat destroy instance lama sebelum bikin
       yang baru — cegah error "Canvas is already in use".
    4. Semua string JS yang disisipkan dari Blade pakai @json() atau
       {!! !!}, BUKAN {{ }} — karena {{ }} otomatis nge-htmlspecialchars
       tanda kutip jadi &#039; dan bikin JS-nya gagal di-parse browser
       (Unexpected token '&').
--}}
@push('scripts')
<script>
(function () {
    var canvas = document.getElementById('trendChart');
    if (!canvas) return;

    if (typeof Chart === 'undefined') {
        console.error('Chart.js belum termuat — cek Network tab, kemungkinan CDN diblokir.');
        return;
    }

    var existing = Chart.getChart(canvas);
    if (existing) existing.destroy();

    new Chart(canvas, {
        type: 'line',
        data: {
            labels: @json($labels),
            datasets: [
                {
                    label: 'Target',
                    data: @json($targetData),
                    borderColor: '#d81b60',
                    backgroundColor: 'rgba(216,27,96,.08)',
                    borderWidth: 2,
                    fill: false,
                    tension: 0.3,
                    pointRadius: 0,
                    pointHoverRadius: 4,
                },
                {
                    label: {!! json_encode($metric === 'kwh' ? 'KWH' : 'Rp TS') !!},
                    data: @json($data),
                    borderColor: '#0b3d91',
                    backgroundColor: 'rgba(11,61,145,.1)',
                    borderWidth: 3,
                    fill: true,
                    tension: 0.3,
                    pointBackgroundColor: '#ffce3a',
                    pointRadius: 4,
                }
            ]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            interaction: { mode: 'index', intersect: false },
            plugins: {
                legend: {
                    display: true,
                    position: 'bottom',
                    labels: { usePointStyle: true, pointStyle: 'circle', boxWidth: 8, boxHeight: 8, padding: 18 }
                },
                tooltip: {
                    callbacks: {
                        label: function (ctx) {
                            var val = Number(ctx.raw).toLocaleString('id-ID');
                            var unit = {!! json_encode($metric === 'kwh' ? ' KWH' : '') !!};
                            var prefix = {!! json_encode($metric === 'ts' ? 'Rp ' : '') !!};
                            return ctx.dataset.label + ': ' + prefix + val + unit;
                        }
                    }
                }
            },
            scales: {
                x: { grid: { display: false } },
                y: {
                    beginAtZero: true,
                    grid: { color: '#eef0f6' },
                    ticks: {
                        callback: function (v) { return Number(v).toLocaleString('id-ID'); }
                    }
                }
            }
        }
    });
})();
</script>
@endpush