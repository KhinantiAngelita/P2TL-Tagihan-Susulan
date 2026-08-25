<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <style>
        body { font-family: sans-serif; font-size: 12px; color: #1b2559; }
        h1 { font-size: 19px; margin: 0 0 3px; color: #0b3d91; }
        .header-sub { font-size: 12px; color: #6b7690; margin: 0 0 14px; }

        /* ===== SAMPUL ===== */
        .cover {
            page-break-after: always;
            text-align: center;
            padding: 70px 40px 0;
            position: relative;
        }
        .cover::before {
            content: "";
            position: absolute;
            top: 0; left: -4cm; right: -3cm;
            height: 8px;
            background: #0b3d91;
        }
        .cover .cover-logo {
            font-size: 13px;
            font-weight: bold;
            color: #0b3d91;
            letter-spacing: 3px;
            margin: 40px 0 70px;
        }
        .cover .cover-kop {
            font-size: 11px;
            color: #6b7690;
            margin: 0 0 8px;
            letter-spacing: 2px;
            text-transform: uppercase;
        }
        .cover h1.cover-title {
            font-size: 30px;
            font-weight: 800;
            color: #0b3d91;
            margin: 6px 0 4px;
        }
        .cover .cover-subtitle {
            font-size: 15px;
            color: #1b2559;
            margin: 0 0 18px;
        }
        .cover .cover-line {
            width: 90px;
            height: 3px;
            background: #0b3d91;
            margin: 0 auto 50px;
        }
        .cover-info {
            width: 68%;
            margin: 0 auto;
            border-collapse: collapse;
            border-top: 1px solid #d5dbe9;
            border-bottom: 1px solid #d5dbe9;
        }
        .cover-info td {
            border: none;
            border-bottom: 1px solid #eef1f8;
            padding: 11px 12px;
            font-size: 12px;
            text-align: left;
        }
        .cover-info tr:last-child td { border-bottom: none; }
        .cover-info .ci-label {
            width: 160px;
            font-weight: bold;
            color: #0b3d91;
        }
        .cover-footer {
            margin-top: 90px;
            font-size: 10px;
            color: #9aa4c2;
            border-top: 1px solid #eef1f8;
            padding-top: 14px;
        }

        .filter-box { width: 100%; border-collapse: collapse; margin-bottom: 22px; background: #f4f7fd; border-radius: 8px; }
        .filter-box td { border: none; padding: 6px 10px; font-size: 11px; vertical-align: top; }
        .filter-box .fb-label { width: 130px; font-weight: bold; color: #0b3d91; }

        .section { page-break-inside: avoid; margin-bottom: 22px; }
        .section:not(:first-child) { page-break-before: always; }
        .section-title { font-size: 15px; font-weight: bold; color: #0b3d91; margin: 0 0 3px; border-bottom: 2px solid #0b3d91; padding-bottom: 5px; }
        .section-menu { font-size: 10px; color: #9aa4c2; font-style: italic; margin: 4px 0 10px; }
        .section-narasi { font-size: 11.5px; color: #333; background: #f8f9fc; border-left: 3px solid #0b3d91; padding: 9px 11px; margin: 0 0 12px; line-height: 1.65; text-align: justify; }

        table { width: 100%; border-collapse: collapse; margin-bottom: 6px; }
        th, td { border: 1px solid #d5dbe9; padding: 6px 8px; font-size: 11px; text-align: right; }
        th { background: #0b3d91; color: #fff; font-weight: bold; text-transform: uppercase; font-size: 9.5px; }
        td.label { text-align: left; font-weight: bold; color: #1b2559; }
        tbody tr:nth-child(even) td { background: #f7f9fd; }
        tfoot td { font-weight: bold; background: #eaf0fb; color: #0b3d91; }
    </style>
</head>
<body>

    {{-- ===== SAMPUL / COVER PAGE ===== --}}
    <div class="cover">
        <p class="cover-logo">PT PLN (PERSERO)</p>
        <p class="cover-kop">Unit Induk Distribusi Jawa Barat</p>
        <h1 class="cover-title">Laporan P2TL</h1>
        <p class="cover-subtitle">UID Jawa Barat</p>
        <div class="cover-line"></div>

        <table class="cover-info">
            <tr><td class="ci-label">Tahun</td><td>{{ $filterInfo['tahun'] }}</td></tr>
            <tr><td class="ci-label">Periode</td><td>{{ $filterInfo['periode'] }}</td></tr>
            <tr><td class="ci-label">ULP</td><td>{{ $filterInfo['ulp'] }}</td></tr>
            <tr><td class="ci-label">Bagian Laporan</td><td>{{ collect($sections)->map(fn ($s) => $sectionMeta[$s]['label'] ?? $s)->implode(', ') }}</td></tr>
        </table>

        <p class="cover-footer">Digenerate {{ now()->translatedFormat('d F Y, H:i') }} WIB</p>
    </div>

    <h1>Laporan P2TL &mdash; UID Jawa Barat</h1>
    <p class="header-sub">{{ $filterInfo['periode'] }} &middot; Digenerate {{ now()->translatedFormat('d F Y, H:i') }} WIB</p>

    <table class="filter-box">
        <tr>
            <td class="fb-label">Tahun</td>
            <td>{{ $filterInfo['tahun'] }}</td>
        </tr>
        <tr>
            <td class="fb-label">Periode</td>
            <td>{{ $filterInfo['periode'] }}</td>
        </tr>
        <tr>
            <td class="fb-label">ULP</td>
            <td>{{ $filterInfo['ulp'] }}</td>
        </tr>
        <tr>
            <td class="fb-label">Bagian Laporan</td>
            <td>{{ collect($sections)->map(fn ($s) => $sectionMeta[$s]['label'] ?? $s)->implode(', ') }}</td>
        </tr>
    </table>

    @foreach ($sections as $key)
        @php $d = $data[$key] ?? null; @endphp
        @if ($d)
        <div class="section">
            <div class="section-title">{{ $sectionMeta[$key]['label'] ?? $key }}</div>
            <p class="section-menu">Sumber data: {{ $sectionMeta[$key]['menu'] ?? '-' }}</p>

            @if (! empty($narasi[$key]))
                <p class="section-narasi">{{ $narasi[$key] }}</p>
            @endif

            {{-- ===== TARGET VS REALISASI ===== --}}
            @if ($key === 'target_realisasi')
                <table>
                    <thead><tr><th style="text-align:left;">Unit Pelaksana</th><th>Target</th><th>Realisasi</th><th>%</th></tr></thead>
                    <tbody>
                        @foreach ($d['rows'] as $r)
                            <tr>
                                <td class="label">{{ $r['nama'] }}</td>
                                <td>{{ number_format($r['target'], 0, ',', '.') }}</td>
                                <td>{{ number_format($r['realisasi'], 0, ',', '.') }}</td>
                                <td>{{ number_format($r['persen'], 2, ',', '.') }}%</td>
                            </tr>
                        @endforeach
                    </tbody>
                    <tfoot>
                        <tr>
                            <td class="label">UID JABAR</td>
                            <td>{{ number_format($d['totalTarget'], 0, ',', '.') }}</td>
                            <td>{{ number_format($d['totalRealisasi'], 0, ',', '.') }}</td>
                            <td>{{ number_format($d['totalPersen'], 2, ',', '.') }}%</td>
                        </tr>
                    </tfoot>
                </table>
            @endif

            {{-- ===== GOL TARIF ===== --}}
            @if ($key === 'gol_tarif')
                @foreach (['prabayar' => 'Prabayar', 'paskabayar' => 'Paskabayar'] as $tipe => $labelTipe)
                    <p style="font-weight:bold;color:#0b3d91;margin:10px 0 5px;">Gol Tarif {{ $labelTipe }}</p>
                    <table>
                        <thead><tr><th style="text-align:left;">Tarif</th><th>P1</th><th>P2</th><th>P3</th><th>P4</th><th>Total</th><th>%</th></tr></thead>
                        <tbody>
                            @foreach ($d[$tipe]['rows'] as $r)
                                @if ($r['total'] > 0)
                                <tr>
                                    <td class="label">{{ $r['label'] }}</td>
                                    <td>{{ number_format($r['nilai']['P1'], 0, ',', '.') }}</td>
                                    <td>{{ number_format($r['nilai']['P2'], 0, ',', '.') }}</td>
                                    <td>{{ number_format($r['nilai']['P3'], 0, ',', '.') }}</td>
                                    <td>{{ number_format($r['nilai']['P4'], 0, ',', '.') }}</td>
                                    <td>{{ number_format($r['total'], 0, ',', '.') }}</td>
                                    <td>{{ number_format($r['persen'], 2, ',', '.') }}%</td>
                                </tr>
                                @endif
                            @endforeach
                        </tbody>
                        <tfoot>
                            <tr>
                                <td class="label">TOTAL</td>
                                <td>{{ number_format($d[$tipe]['total']['P1'], 0, ',', '.') }}</td>
                                <td>{{ number_format($d[$tipe]['total']['P2'], 0, ',', '.') }}</td>
                                <td>{{ number_format($d[$tipe]['total']['P3'], 0, ',', '.') }}</td>
                                <td>{{ number_format($d[$tipe]['total']['P4'], 0, ',', '.') }}</td>
                                <td>{{ number_format($d[$tipe]['total']['grand_total'], 0, ',', '.') }}</td>
                                <td>100,00%</td>
                            </tr>
                        </tfoot>
                    </table>
                @endforeach
            @endif

            {{-- ===== REKAP KWH PER ULP ===== --}}
            @if ($key === 'rekap_ulp')
                @foreach (['p' => 'Golongan P', 'k' => 'Golongan K'] as $grp => $labelGrp)
                    <p style="font-weight:bold;color:#0b3d91;margin:10px 0 5px;">Rekap KWH per ULP &mdash; {{ $labelGrp }}</p>
                    <table>
                        <thead>
                            <tr>
                                <th style="text-align:left;">ULP</th>
                                @foreach ($d[$grp]['kolom'] as $g)<th>{{ $g }}</th>@endforeach
                                <th>Total</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($d[$grp]['rows'] as $r)
                                <tr>
                                    <td class="label">{{ $r['nama'] }}</td>
                                    @foreach ($d[$grp]['kolom'] as $g)<td>{{ number_format($r[strtolower($g)], 0, ',', '.') }}</td>@endforeach
                                    <td>{{ number_format($r['total'], 0, ',', '.') }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                        <tfoot>
                            <tr>
                                <td class="label">UID JABAR</td>
                                @foreach ($d[$grp]['kolom'] as $g)<td>{{ number_format($d[$grp]['totalGol'][$g], 0, ',', '.') }}</td>@endforeach
                                <td>{{ number_format($d[$grp]['grand'], 0, ',', '.') }}</td>
                            </tr>
                        </tfoot>
                    </table>
                @endforeach
            @endif

            {{-- ===== KOMPOSISI TEMUAN ===== --}}
            @if ($key === 'komposisi_temuan')
                <table>
                    <thead><tr><th style="text-align:left;">UP3</th><th>PLG P</th><th>KWH P</th><th>PLG K</th><th>KWH K</th><th>Total KWH</th><th>% P</th><th>% K</th></tr></thead>
                    <tbody>
                        @foreach ($d['rows'] as $r)
                            <tr>
                                <td class="label">{{ $r['nama'] }}</td>
                                <td>{{ number_format($r['p']['plg'], 0, ',', '.') }}</td>
                                <td>{{ number_format($r['p']['kwh'], 0, ',', '.') }}</td>
                                <td>{{ number_format($r['k']['plg'], 0, ',', '.') }}</td>
                                <td>{{ number_format($r['k']['kwh'], 0, ',', '.') }}</td>
                                <td>{{ number_format($r['total_kwh'], 0, ',', '.') }}</td>
                                <td>{{ number_format($r['persen_p'], 2, ',', '.') }}%</td>
                                <td>{{ number_format($r['persen_k'], 2, ',', '.') }}%</td>
                            </tr>
                        @endforeach
                    </tbody>
                    <tfoot>
                        <tr><td class="label" colspan="5">Total KWH Keseluruhan</td><td colspan="3">{{ number_format($d['totalKwh'], 0, ',', '.') }}</td></tr>
                    </tfoot>
                </table>
            @endif

            {{-- ===== TREND kWh / Rp TS ===== --}}
            @if ($key === 'trend_kwh' || $key === 'trend_ts')
                <table>
                    <thead>
                        <tr>
                            <th style="text-align:left;">Bulan</th>
                            @foreach ($d['rows'] as $r)<th>{{ $r['label'] }}</th>@endforeach
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td class="label">{{ $key === 'trend_kwh' ? 'KWH' : 'Rp TS' }}</td>
                            @foreach ($d['rows'] as $r)<td>{{ number_format($r['nilai'], 0, ',', '.') }}</td>@endforeach
                        </tr>
                    </tbody>
                </table>
                <p style="text-align:right;font-weight:bold;color:#0b3d91;">Total: {{ number_format($d['total'], 0, ',', '.') }}</p>
            @endif

            {{-- ===== PRESENTASE PENCAPAIAN ===== --}}
            @if ($key === 'pencapaian')
                <table>
                    <thead><tr><th style="text-align:left;">Bulan</th><th>Target</th><th>Aktual</th><th>%</th></tr></thead>
                    <tbody>
                        @foreach ($d['rows'] as $r)
                            <tr>
                                <td class="label">{{ $r['label'] }}</td>
                                <td>{{ number_format($r['target'], 0, ',', '.') }}</td>
                                <td>{{ number_format($r['aktual'], 0, ',', '.') }}</td>
                                <td>{{ $r['persen'] === null ? '-' : number_format($r['persen'], 1, ',', '.') . '%' }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                    <tfoot>
                        <tr>
                            <td class="label">TOTAL</td>
                            <td>{{ number_format($d['totalTarget'], 0, ',', '.') }}</td>
                            <td>{{ number_format($d['totalAktual'], 0, ',', '.') }}</td>
                            <td>{{ $d['persenTotal'] === null ? '-' : number_format($d['persenTotal'], 1, ',', '.') . '%' }}</td>
                        </tr>
                    </tfoot>
                </table>
            @endif

        </div>
        @endif
    @endforeach

</body>
</html>