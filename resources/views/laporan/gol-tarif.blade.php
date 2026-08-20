@extends('layouts.app')
@section('title', 'Gol Tarif')

@section('breadcrumb')
    <a href="{{ route('dashboard') }}">Beranda</a>
    <span class="sep">›</span>
    <strong>Gol Tarif</strong>
@endsection

@push('styles')
<style>
    .goltarif-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(360px, 1fr));
        gap: 20px;
        margin-bottom: 22px;
        align-items: stretch;
    }

    /* Tablet: gap lebih kecil */
    @media (max-width: 1200px) {
        .goltarif-grid { gap: 14px; }
    }

    /* Mobile: 1 kolom, kartu auto-height */
    @media (max-width: 900px) {
        .goltarif-grid {
            grid-template-columns: 1fr;
            gap: 16px;
        }
        .goltarif-card { height: auto !important; }
    }

    .goltarif-card {
        padding: 24px;
        background: #fff;
        border: 1px solid var(--border);
        border-radius: 14px;
        box-sizing: border-box;
        min-width: 0; /* cegah card "ngotot" lebar minimum dari isi tabel */
        display: flex;
        flex-direction: column;
        height: 100%;
    }

    .goltarif-card-head {
        display: flex;
        align-items: center;
        justify-content: space-between;
        margin-bottom: 18px;
        padding-bottom: 14px;
        border-bottom: 1px solid #f1f3f9;
        flex-shrink: 0;
    }
    .goltarif-card h3 {
        margin: 0 0 3px;
        font-size: 15.5px;
        color: #1b2559;
        display: flex;
        align-items: center;
        gap: 8px;
    }
    .goltarif-card h3 .dot {
        width: 9px; height: 9px; border-radius: 50%; flex-shrink: 0;
    }
    .goltarif-card h3 .dot.prabayar   { background: #0b3d91; }
    .goltarif-card h3 .dot.paskabayar { background: #e07a1f; }
    .goltarif-card .sub { margin: 0; font-size: 12.5px; color: #6b7690; }

    .goltarif-year-badge {
        background: #eaf0fb;
        color: var(--blue-primary);
        font-size: 12px;
        font-weight: 700;
        padding: 5px 12px;
        border-radius: 999px;
        white-space: nowrap;
        flex-shrink: 0;
    }

    /* Chart: tinggi TETAP sama persis di kedua kartu, tidak ikut stretch */
    .goltarif-chart-wrap {
        position: relative;
        height: 300px;
        margin-bottom: 20px;
        flex-shrink: 0;
    }
    @media (max-width: 900px) {
        .goltarif-chart-wrap { height: 260px; }
    }

    /* Tabel: sisa ruang dibagi rata */
    .goltarif-table-scroll {
        overflow-x: auto;
        overflow-y: auto;
        border-radius: 10px;
        border: 1px solid var(--border);
        flex: 1 1 auto;
        min-height: 0;
    }
    @media (min-width: 901px) {
        .goltarif-table-scroll { max-height: 320px; }
    }

    .goltarif-table {
        width: 100%;
        border-collapse: collapse;
        font-size: 12.5px;
        min-width: 480px;
    }
    .goltarif-table th,
    .goltarif-table td {
        padding: 10px 12px;
        text-align: right;
        border-bottom: 1px solid #f1f3f9;
        white-space: nowrap;
    }

    /* Kolom pertama (Tarif) sticky dengan background solid + shadow pemisah */
    .goltarif-table th:first-child,
    .goltarif-table td:first-child {
        text-align: left;
        position: sticky;
        left: 0;
        z-index: 2;
        background: #fff;
        padding-right: 16px;
    }

    /* Garis pembatas sebagai elemen independen — selalu di atas, tidak ikut ketutup */
    .goltarif-table th:first-child::after,
    .goltarif-table td:first-child::after {
        content: '';
        position: absolute;
        top: 0;
        right: 0;
        bottom: 0;
        width: 2px;
        background: #b9c2de;
        z-index: 3;
    }

    .goltarif-table thead th:first-child::after,
    .goltarif-table tfoot td:first-child::after {
        background: rgba(255,255,255,0.4);
    }
    .goltarif-table tbody tr:nth-child(even) td:first-child {
        background: #f8f9fc;
    }
    .goltarif-table thead th:first-child {
        background: #0b3d91;
        z-index: 3;
    }
    .goltarif-table tfoot td:first-child {
        background: #0b3d91;
        z-index: 3;
    }

    .goltarif-table thead th {
        background: #0b3d91;
        color: #fff;
        font-weight: 700;
        font-size: 11px;
        text-transform: uppercase;
        letter-spacing: .03em;
        white-space: nowrap;
        position: sticky;
        top: 0;
        z-index: 1;
    }
    .goltarif-table tbody tr:nth-child(even) { background: #f8f9fc; }
    .goltarif-table tbody tr:hover { background: #eef2fb; }
    .goltarif-table tbody td:first-child { font-weight: 700; color: #1b2559; }
    .goltarif-table tfoot td {
        font-weight: 800;
        background: #0b3d91;
        color: #fff;
        border-bottom: none;
        position: sticky;
        bottom: 0;
    }

    .goltarif-empty {
        text-align: center;
        color: #9aa4c2;
        padding: 32px;
        font-size: 13px;
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
        min-width: 110px;
    }
    .ulp-card {
        padding: 24px;
        background: #fff;
        border: 1px solid var(--border);
        border-radius: 14px;
        box-sizing: border-box;
        margin-bottom: 20px;
    }
    .ulp-card-head {
        display: flex;
        align-items: center;
        justify-content: space-between;
        margin-bottom: 18px;
        padding-bottom: 14px;
        border-bottom: 1px solid #f1f3f9;
    }
    .ulp-card h3 { margin: 0 0 3px; font-size: 15.5px; color: #1b2559; }
    .ulp-card .sub { margin: 0; font-size: 12.5px; color: #6b7690; }

    .ulp-table-scroll {
        overflow-x: auto;
        border-radius: 10px;
        border: 1px solid var(--border);
    }

    .ulp-table {
        width: 100%;
        border-collapse: collapse;
        font-size: 12px;
        min-width: 980px;
    }
    .ulp-table th,
    .ulp-table td {
        padding: 8px 10px;
        text-align: center;
        border-bottom: 1px solid #eef0f6;
        border-right: 1px solid #eef0f6;
        white-space: nowrap;
    }

    .ulp-table thead th {
        color: #fff;
        font-weight: 700;
        font-size: 10.5px;
        text-transform: uppercase;
        letter-spacing: .02em;
    }
    .ulp-table thead tr:first-child th.col-no,
    .ulp-table thead tr:first-child th.col-ulp {
        background: #0b3d91;
        vertical-align: middle;
    }
    .ulp-table thead th.grp-p1 { background: #1a7a3c; }
    .ulp-table thead th.grp-p2 { background: #0b3d91; }
    .ulp-table thead th.grp-p3 { background: #8a3d1f; }
    .ulp-table thead th.grp-p4 { background: #b3001f; }
    .ulp-table thead th.grp-k1 { background: #6a4fe0; }
    .ulp-table thead th.grp-k2 { background: #0f6bd9; }
    .ulp-table thead th.grp-k3 { background: #17803c; }
    .ulp-table thead th.grp-k4 { background: #d99f00; }
    .ulp-table thead th.grp-total { background: #0b1f4d; }

    .ulp-table th.col-no, .ulp-table td.col-no { position: sticky; left: 0; z-index: 2; width: 42px; }
    .ulp-table th.col-ulp, .ulp-table td.col-ulp { position: sticky; left: 42px; z-index: 2; text-align: left; font-weight: 700; min-width: 150px; }

    .ulp-table td.col-ulp { color: #1b2559; }
    .ulp-table th.col-ulp { color: #fff; }
    .ulp-table tbody tr:nth-child(even) td.col-no,
    .ulp-table tbody tr:nth-child(even) td.col-ulp { background: #f8f9fc; }

    .ulp-table tbody tr:nth-child(even) { background: #f8f9fc; }
    .ulp-table tbody tr:hover { background: #eef2fb; }

    .ulp-table td.cell-persen {
        position: relative;
        text-align: right;
        padding-right: 10px;
    }
    .persen-bar {
        position: absolute;
        left: 4px; top: 4px; bottom: 4px;
        width: calc(var(--pct) * 0.01 * 30px);
        max-width: 30px;
        background: #34c77b;
        border-radius: 3px;
        opacity: .8;
    }
    .persen-text {
        position: relative;
        z-index: 1;
    }

    .ulp-table tfoot td {
        font-weight: 800;
        background: #0b3d91;
        color: #fff;
        border-bottom: none;
    }
    .ulp-table tfoot td:first-child,
    .ulp-table tfoot td:nth-child(2) {
        position: sticky;
        left: 0;
        z-index: 3;
    }
</style>
@endpush

@section('content')

<div style="display:flex;align-items:flex-start;justify-content:space-between;margin-bottom:22px;flex-wrap:wrap;gap:12px;">
    <div>
        <h2 style="margin:0 0 4px;font-size:22px;">Laporan Gol Tarif</h2>
        <p style="color:#6b7690;margin:0;font-size:14px;">
            Distribusi Rp TS berdasarkan golongan tarif &mdash; Prabayar vs Paskabayar
        </p>
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

<div class="goltarif-grid">

    {{-- ===== PRABAYAR ===== --}}
    <div class="goltarif-card">
        <div class="goltarif-card-head">
            <div>
                <h3><span class="dot prabayar"></span> Gol Tarif Prabayar</h3>
                <p class="sub">Distribusi Rp TS per golongan</p>
            </div>
            <span class="goltarif-year-badge">{{ $tahunAktif ?: '-' }}</span>
        </div>

        <div class="goltarif-chart-wrap">
            <canvas id="chartPrabayar"></canvas>
        </div>

        <div class="goltarif-table-scroll">
            @if (count($prabayar) > 0)
                <table class="goltarif-table">
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
                <p class="goltarif-empty">Belum ada data prabayar untuk tahun ini.</p>
            @endif
        </div>
    </div>

    {{-- ===== PASKABAYAR ===== --}}
    <div class="goltarif-card">
        <div class="goltarif-card-head">
            <div>
                <h3><span class="dot paskabayar"></span> Gol Tarif Paskabayar</h3>
                <p class="sub">Distribusi Rp TS per golongan</p>
            </div>
            <span class="goltarif-year-badge">{{ $tahunAktif ?: '-' }}</span>
        </div>

        <div class="goltarif-chart-wrap">
            <canvas id="chartPaskabayar"></canvas>
        </div>

        <div class="goltarif-table-scroll">
            @if (count($paskabayar) > 0)
                <table class="goltarif-table">
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
                <p class="goltarif-empty">Belum ada data paskabayar untuk tahun ini.</p>
            @endif
        </div>
    </div>
</div>

{{-- ===== TABEL PER ULP (KWH & % per Periode) ===== --}}
    {{-- ===== TABEL PER ULP — GOLONGAN P ===== --}}
<div class="ulp-card">
    <div class="ulp-card-head">
        <div>
            <h3>Rekap KWH per ULP — Golongan P</h3>
            <p class="sub">Breakdown KWH dan persentase per periode &mdash; UID Jawa Barat</p>
        </div>
        <span class="goltarif-year-badge">{{ $tahunAktif ?: '-' }}</span>
    </div>

    <div class="ulp-table-scroll">
        @if (count($ulpRowsP) > 0)
            <table class="ulp-table">
                <thead>
                    <tr>
                        <th rowspan="2" class="col-no">No</th>
                        <th rowspan="2" class="col-ulp">ULP</th>
                        @foreach ($kolomUlpP as $g)
                            <th colspan="2" class="grp-{{ strtolower($g) }}">{{ $g }}</th>
                        @endforeach
                        <th colspan="2" class="grp-total">Total</th>
                    </tr>
                    <tr>
                        @foreach ($kolomUlpP as $g)
                            <th class="grp-{{ strtolower($g) }}">KWH</th><th class="grp-{{ strtolower($g) }}">%</th>
                        @endforeach
                        <th class="grp-total">KWH</th><th class="grp-total">%</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($ulpRowsP as $i => $row)
                        <tr>
                            <td class="col-no">{{ $i + 1 }}</td>
                            <td class="col-ulp">{{ $row['nama'] }}</td>

                            @foreach ($kolomUlpP as $g)
                                @php $key = strtolower($g); @endphp
                                <td class="grp-{{ $key }}">{{ number_format($row[$key]['kwh'], 0, ',', '.') }}</td>
                                <td class="grp-{{ $key }} cell-persen">
                                    <span class="persen-bar" style="--pct: {{ $row[$key]['persen'] }}%"></span>
                                    <span class="persen-text">{{ number_format($row[$key]['persen'], 2, ',', '.') }}%</span>
                                </td>
                            @endforeach

                            <td class="grp-total">{{ number_format($row['total']['kwh'], 0, ',', '.') }}</td>
                            <td class="grp-total cell-persen">
                                <span class="persen-bar" style="--pct: {{ $row['total']['persen'] }}%"></span>
                                <span class="persen-text">{{ number_format($row['total']['persen'], 2, ',', '.') }}%</span>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
                <tfoot>
                    <tr>
                        <td colspan="2">UID JABAR</td>
                        @foreach ($kolomUlpP as $g)
                            @php $key = strtolower($g); @endphp
                            <td class="grp-{{ $key }}">{{ number_format($ulpTotalP[$key]['kwh'], 0, ',', '.') }}</td>
                            <td class="grp-{{ $key }}">{{ number_format($ulpTotalP[$key]['persen'], 2, ',', '.') }}%</td>
                        @endforeach
                        <td class="grp-total">{{ number_format($ulpTotalP['total']['kwh'], 0, ',', '.') }}</td>
                        <td class="grp-total">{{ number_format($ulpTotalP['total']['persen'], 2, ',', '.') }}%</td>
                    </tr>
                </tfoot>
            </table>
        @else
            <p class="goltarif-empty">Belum ada data KWH golongan P per ULP untuk tahun ini.</p>
        @endif
    </div>
</div>

{{-- ===== TABEL PER ULP — GOLONGAN K ===== --}}
<div class="ulp-card">
    <div class="ulp-card-head">
        <div>
            <h3>Rekap KWH per ULP — Golongan K</h3>
            <p class="sub">Breakdown KWH dan persentase per periode &mdash; UID Jawa Barat</p>
        </div>
        <span class="goltarif-year-badge">{{ $tahunAktif ?: '-' }}</span>
    </div>

    <div class="ulp-table-scroll">
        @if (count($ulpRowsK) > 0)
            <table class="ulp-table">
                <thead>
                    <tr>
                        <th rowspan="2" class="col-no">No</th>
                        <th rowspan="2" class="col-ulp">ULP</th>
                        @foreach ($kolomUlpK as $g)
                            <th colspan="2" class="grp-{{ strtolower($g) }}">{{ $g }}</th>
                        @endforeach
                        <th colspan="2" class="grp-total">Total</th>
                    </tr>
                    <tr>
                        @foreach ($kolomUlpK as $g)
                            <th class="grp-{{ strtolower($g) }}">KWH</th><th class="grp-{{ strtolower($g) }}">%</th>
                        @endforeach
                        <th class="grp-total">KWH</th><th class="grp-total">%</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($ulpRowsK as $i => $row)
                        <tr>
                            <td class="col-no">{{ $i + 1 }}</td>
                            <td class="col-ulp">{{ $row['nama'] }}</td>

                            @foreach ($kolomUlpK as $g)
                                @php $key = strtolower($g); @endphp
                                <td class="grp-{{ $key }}">{{ number_format($row[$key]['kwh'], 0, ',', '.') }}</td>
                                <td class="grp-{{ $key }} cell-persen">
                                    <span class="persen-bar" style="--pct: {{ $row[$key]['persen'] }}%"></span>
                                    <span class="persen-text">{{ number_format($row[$key]['persen'], 2, ',', '.') }}%</span>
                                </td>
                            @endforeach

                            <td class="grp-total">{{ number_format($row['total']['kwh'], 0, ',', '.') }}</td>
                            <td class="grp-total cell-persen">
                                <span class="persen-bar" style="--pct: {{ $row['total']['persen'] }}%"></span>
                                <span class="persen-text">{{ number_format($row['total']['persen'], 2, ',', '.') }}%</span>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
                <tfoot>
                    <tr>
                        <td colspan="2">UID JABAR</td>
                        @foreach ($kolomUlpK as $g)
                            @php $key = strtolower($g); @endphp
                            <td class="grp-{{ $key }}">{{ number_format($ulpTotalK[$key]['kwh'], 0, ',', '.') }}</td>
                            <td class="grp-{{ $key }}">{{ number_format($ulpTotalK[$key]['persen'], 2, ',', '.') }}%</td>
                        @endforeach
                        <td class="grp-total">{{ number_format($ulpTotalK['total']['kwh'], 0, ',', '.') }}</td>
                        <td class="grp-total">{{ number_format($ulpTotalK['total']['persen'], 2, ',', '.') }}%</td>
                    </tr>
                </tfoot>
            </table>
        @else
            <p class="goltarif-empty">Belum ada data KWH golongan K per ULP untuk tahun ini.</p>
        @endif
    </div>
</div>

@endsection

@push('scripts')
<script>
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
                    backgroundColor: ['#0b3d91', '#e07a1f', '#9aa4c2', '#ffce3a', '#1a9c4a', '#d81b60', '#3d63b8', '#6b8fd6'],
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

    var paskabayarLabels = @json(collect($paskabayar)->where('total', '>', 0)->pluck('label')->values());
    var paskabayarData   = @json(collect($paskabayar)->where('total', '>', 0)->pluck('total')->values());
    buatPie('chartPaskabayar', paskabayarLabels, paskabayarData);
})();
</script>
@endpush