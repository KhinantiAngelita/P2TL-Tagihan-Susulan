<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <style>
        @page {
            margin: 18px 20px;
        }

        body {
            font-family: DejaVu Sans, sans-serif;
            font-size: 8px;
            color: #1b2559;
            margin: 0;
        }

        .header {
            padding-bottom: 9px;
            border-bottom: 2px solid #0b3d91;
            margin-bottom: 10px;
        }

        h1 {
            font-size: 18px;
            color: #0b3d91;
            margin: 0 0 3px;
        }

        h2 {
            font-size: 10px;
            color: #0b3d91;
            margin: 12px 0 6px;
            padding-bottom: 4px;
            border-bottom: 1px solid #dce3ef;
            text-transform: uppercase;
        }

        .subtitle {
            color: #6b7690;
            font-size: 7.5px;
            margin: 0;
        }

        .filter-note {
            font-size: 7px;
            color: #6b7690;
            margin: 0 0 8px;
            padding: 5px 7px;
            background: #f5f8fd;
            border-left: 3px solid #0f6bd9;
        }

        .info-card,
        .stat-card,
        .chart-card {
            border: 1px solid #e1e6ef;
            background: #fff;
            border-radius: 8px;
        }

        .info-table,
        .stat-table,
        .chart-grid,
        .detail-table {
            width: 100%;
            border-collapse: separate;
            border-spacing: 0;
        }

        .info-table {
            margin-bottom: 7px;
        }

        .info-table td {
            padding: 6px 7px;
            border-bottom: 1px solid #eef1f5;
        }

        .info-table tr:last-child td {
            border-bottom: 0;
        }

        .info-table .label {
            color: #6b7690;
            width: 80px;
            font-size: 7px;
        }

        .info-table .value {
            font-weight: bold;
            color: #1b2559;
        }

        .stat-table {
            margin-bottom: 4px;
        }

        .stat-table td {
            width: 33.33%;
            padding: 8px 6px;
            text-align: center;
            border: 1px solid #e1e6ef;
            background: #fff;
        }

        .stat-table td:first-child {
            border-radius: 8px 0 0 8px;
        }

        .stat-table td:last-child {
            border-radius: 0 8px 8px 0;
        }

        .stat-label {
            font-size: 6.5px;
            color: #6b7690;
            text-transform: uppercase;
            margin-bottom: 3px;
        }

        .stat-value {
            font-size: 11px;
            font-weight: bold;
            color: #0b3d91;
        }

        .stat-value.ts {
            color: #0f6bd9;
        }

        .stat-value.penetapan {
            color: #7048a8;
        }

        .chart-grid {
            table-layout: fixed;
            margin-bottom: 6px;
        }

        .chart-grid > tbody > tr > td {
            vertical-align: top;
            padding: 0 3px;
        }

        .chart-grid > tbody > tr > td:first-child {
            padding-left: 0;
        }

        .chart-grid > tbody > tr > td:last-child {
            padding-right: 0;
        }

        .chart-card {
            padding: 7px;
        }

        .chart-title {
            font-size: 7.5px;
            font-weight: bold;
            color: #1b2559;
            margin-bottom: 5px;
        }

        .chart-subtitle {
            font-size: 6px;
            color: #8a94a8;
            margin-bottom: 3px;
        }

        .legend {
            font-size: 6px;
            color: #6b7690;
            margin-top: 2px;
            text-align: center;
        }

        .legend span {
            margin-right: 8px;
        }

        .legend-p {
            color: #0b3d91;
        }

        .legend-k {
            color: #ffb800;
        }

        .mini-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 5px;
        }

        .mini-table th,
        .mini-table td {
            border: 1px solid #e1e6ef;
            padding: 3px 4px;
            font-size: 6.5px;
        }

        .mini-table th {
            background: #f1f5fb;
            color: #52617b;
            text-align: left;
        }

        .mini-table td:nth-child(n+2) {
            text-align: right;
        }

        .detail-wrap {
            margin-top: 5px;
        }

        .detail-title {
            font-size: 8px;
            font-weight: bold;
            color: #1b2559;
            margin-bottom: 4px;
        }

        .detail-count {
            color: #6b7690;
            font-weight: normal;
        }

        .data-table {
            width: 100%;
            table-layout: fixed;
            border-collapse: collapse;
        }

        .data-table th,
        .data-table td {
            border: 0.5px solid #bfc7d5;
            padding: 2px;
            font-size: 5px;
            line-height: 1.05;
            word-wrap: break-word;
            overflow-wrap: break-word;
        }

        .data-table th {
            background: #eaf0fb;
            color: #1b2559;
            text-align: center;
            font-size: 5px;
            font-weight: bold;
        }

        .data-table td {
            vertical-align: top;
        }

        .page-break {
            page-break-before: always;
        }

        .muted {
            color: #8a94a8;
        }
    </style>
</head>
<body>

    <div class="header">
        <h1>Detail Laporan</h1>
        <p class="subtitle">
            Rekap tagihan pelanggan — Bulan {{ $laporan->bulan }} {{ $laporan->tahun }} · {{ $laporan->unit_up3 }}
        </p>
    </div>

    @if ($tanggalDari || $tanggalSampai || $search || ($golonganAktif !== 'semua') || ($ulpAktif !== 'semua'))
        <div class="filter-note">
            <strong>Filter aktif:</strong>
            @if ($tanggalDari || $tanggalSampai)
                Tanggal {{ $tanggalDari ?: 'awal' }} s/d {{ $tanggalSampai ?: 'akhir' }};
            @endif
            @if ($search) Pencarian "{{ $search }}"; @endif
            @if ($golonganAktif !== 'semua') Golongan {{ $golonganAktif }}; @endif
            @if ($ulpAktif !== 'semua') ULP {{ $ulpAktif }}; @endif
        </div>
    @endif

    <h2>Ringkasan Utama</h2>

    <table class="info-table">
        <tr>
            <td class="label">Unit Induk</td>
            <td class="value">{{ $laporan->unit_induk ?? '-' }}</td>
            <td class="label">Unit UP3</td>
            <td class="value">{{ $laporan->unit_up3 ?? '-' }}</td>
        </tr>
        <tr>
            <td class="label">Bulan / Tahun</td>
            <td class="value">{{ $laporan->bulan }} {{ $laporan->tahun }}</td>
            <td class="label">Judul Laporan</td>
            <td class="value">{{ $laporan->judul_laporan ?? '-' }}</td>
        </tr>
    </table>

    <table class="stat-table">
        <tr>
            <td>
                <div class="stat-label">Total KWH</div>
                <div class="stat-value">{{ number_format((float) $totalKwh, 0, ',', '.') }}</div>
            </td>
            <td>
                <div class="stat-label">Rp. TS</div>
                <div class="stat-value ts">Rp {{ number_format((float) $totalTs, 0, ',', '.') }}</div>
            </td>
            <td>
                <div class="stat-label">Penetapan</div>
                <div class="stat-value penetapan">Rp {{ number_format((float) $totalPenetapan, 0, ',', '.') }}</div>
            </td>
        </tr>
    </table>

    <h2>Analisis Golongan Tarif</h2>

    @php
        $golongan = collect($distribusiGolonganDetail);
    @endphp

    <table class="chart-grid">
        <tr>
            <td width="57%">
                <div class="chart-card">
                    <div class="chart-title">Distribusi KWH per Golongan</div>
                    <img src="{{ $chartGolonganImg }}" style="width:100%;height:auto;">
                </div>
            </td>
            <td width="43%">
                <div class="chart-card">
                    <div class="chart-title">Komposisi Golongan P vs K</div>
                    <img src="{{ $chartKomposisiImg }}" style="width:100%;height:auto;">
                </div>
            </td>
        </tr>
    </table>

    <table class="mini-table">
        <thead>
            <tr>
                <th>Golongan</th>
                <th>Jumlah Pelanggan</th>
                <th>Total KWH</th>
                <th>% KWH</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($golongan as $g)
                <tr>
                    <td>{{ $g->gol }}</td>
                    <td>{{ number_format((float) $g->jumlah_pelanggan, 0, ',', '.') }}</td>
                    <td>{{ number_format((float) $g->total_kwh, 0, ',', '.') }}</td>
                    <td>{{ str_replace('.', ',', $g->persen_kwh) }}%</td>
                </tr>
            @endforeach
        </tbody>
    </table>

    <h2>Tren Harian</h2>

    <table class="chart-grid">
        <tr>
            <td width="50%">
                <div class="chart-card">
                    <div class="chart-title">Tren KWH Harian</div>
                    <img src="{{ $chartTrenKwhImg }}" style="width:100%;height:auto;">
                </div>
            </td>
            <td width="50%">
                <div class="chart-card">
                    <div class="chart-title">Tren TS Harian</div>
                    <img src="{{ $chartTrenTsImg }}" style="width:100%;height:auto;">
                </div>
            </td>
        </tr>
    </table>

    <div class="chart-card" style="margin-top: 6px;">
        <div class="chart-title">Tren Golongan P vs K</div>
        <img src="{{ $chartTrenPkImg }}" style="width:100%;height:auto;">
    </div>
    
</body>
</html>
