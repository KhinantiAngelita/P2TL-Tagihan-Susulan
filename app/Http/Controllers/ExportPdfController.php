<?php

namespace App\Http\Controllers;

use App\Models\DetailTagihanSusulan;
use App\Models\LaporanSusulan;
use App\Models\RingkasanGolTarif;
use App\Models\TargetBulanan;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;

class ExportPdfController extends Controller
{
    private const KODE_TARIF_PRABAYAR = [
        'S1', 'S2', 'S3', 'R1', 'R1M', 'R2', 'R3',
        'B1', 'B2', 'B3', 'I1', 'I2', 'I3', 'I4',
        'P1', 'P2', 'P3', 'L', 'T', 'C',
    ];
    private const KODE_TARIF_PASKABAYAR = [
        'S1', 'S2', 'S3', 'R1', 'R1M', 'R2', 'R3',
        'B1', 'B2', 'B3', 'I1', 'I2', 'I3', 'I4',
        'P1', 'P2', 'P3', 'NON PLG', 'L', 'T', 'C',
    ];
    private const KOLOM_GOL   = ['P1', 'P2', 'P3', 'P4'];
    private const KOLOM_ULP_P = ['P1', 'P2', 'P3', 'P4'];
    private const KOLOM_ULP_K = ['K1', 'K2', 'K3'];

    /** Nama bulan (uppercase, sesuai format kolom laporan_susulans.bulan) per angka 1-12. */
    private const BULAN_NAMA = [
        1 => 'JANUARI', 2 => 'FEBRUARI', 3 => 'MARET', 4 => 'APRIL',
        5 => 'MEI', 6 => 'JUNI', 7 => 'JULI', 8 => 'AGUSTUS',
        9 => 'SEPTEMBER', 10 => 'OKTOBER', 11 => 'NOVEMBER', 12 => 'DESEMBER',
    ];

    /** Kebalikan dari BULAN_NAMA, untuk lookup cepat nama -> angka. */
    private const BULAN_ANGKA = [
        'JANUARI' => 1, 'FEBRUARI' => 2, 'MARET' => 3, 'APRIL' => 4,
        'MEI' => 5, 'JUNI' => 6, 'JULI' => 7, 'AGUSTUS' => 8,
        'SEPTEMBER' => 9, 'OKTOBER' => 10, 'NOVEMBER' => 11, 'DESEMBER' => 12,
    ];

    /** Label singkat bulan untuk tabel & dropdown. */
    private const BULAN_LABEL = [
        1 => 'Jan', 2 => 'Feb', 3 => 'Mar', 4 => 'Apr', 5 => 'Mei', 6 => 'Jun',
        7 => 'Jul', 8 => 'Agu', 9 => 'Sep', 10 => 'Okt', 11 => 'Nov', 12 => 'Des',
    ];

    /** Pemetaan triwulan -> daftar angka bulan. */
    private const TRIWULAN = [
        1 => [1, 2, 3],
        2 => [4, 5, 6],
        3 => [7, 8, 9],
        4 => [10, 11, 12],
    ];

    private const TRIWULAN_LABEL = [
        1 => 'Triwulan I (Jan–Mar)',
        2 => 'Triwulan II (Apr–Jun)',
        3 => 'Triwulan III (Jul–Sep)',
        4 => 'Triwulan IV (Okt–Des)',
    ];

    /**
     * Metadata tiap bagian laporan: label tampilan, asal menu (untuk konteks di form & PDF),
     * dan deskripsi singkat yang dipakai sebagai keterangan di form export.
     * SESUAIKAN kolom 'menu' dengan struktur sidebar asli kamu.
     */
    public const SECTION_META = [
        'target_realisasi' => [
            'label' => 'Target vs Realisasi',
            'menu'  => 'Laporan Gol Tarif › Target & Realisasi',
            'info'  => 'Perbandingan target kWh yang ditetapkan per ULP dengan realisasi hasil temuan P2TL.',
        ],
        'gol_tarif' => [
            'label' => 'Gol Tarif (Prabayar & Paskabayar)',
            'menu'  => 'Laporan Gol Tarif › Gol Tarif',
            'info'  => 'Rekap total tagihan susulan (Rp) per golongan tarif, dipecah antara pelanggan prabayar dan paskabayar. Bagian ini selalu tahunan dan tidak mengikuti filter triwulan/ULP.',
        ],
        'rekap_ulp' => [
            'label' => 'Rekap KWH per ULP (Golongan P & K)',
            'menu'  => 'Laporan › Rekap KWH per ULP',
            'info'  => 'Rekap total kWh temuan per Unit Layanan Pelanggan (ULP), dipisah golongan P dan K.',
        ],
        'komposisi_temuan' => [
            'label' => 'Komposisi Temuan',
            'menu'  => 'Laporan Gol Tarif › Komposisi Temuan',
            'info'  => 'Komposisi jumlah pelanggan dan kWh temuan per UP3, dibandingkan antara golongan P dan K.',
        ],
        'trend_kwh' => [
            'label' => 'Trend kWh',
            'menu'  => 'Trend › Trend kWh',
            'info'  => 'Pergerakan bulanan total kWh temuan P2TL sepanjang periode yang dipilih.',
        ],
        'trend_ts' => [
            'label' => 'Trend Rp TS',
            'menu'  => 'Trend › Trend Rp TS',
            'info'  => 'Pergerakan bulanan total tagihan susulan (Rp) sepanjang periode yang dipilih.',
        ],
        'pencapaian' => [
            'label' => 'Presentase Pencapaian (kWh)',
            'menu'  => 'Trend › Presentase Pencapaian',
            'info'  => 'Persentase pencapaian realisasi kWh terhadap target bulanan.',
        ],
    ];

    public function index(Request $request)
    {
        $daftarTahun = LaporanSusulan::aktif()
            ->whereNotNull('tahun')->distinct()
            ->orderByDesc('tahun')->pluck('tahun');

        $tahunAktif = (int) ($request->input('tahun') ?: $daftarTahun->first());

        return view('export-pdf.index', [
            'daftarTahun'   => $daftarTahun,
            'tahunAktif'    => $tahunAktif,
            'sectionMeta'   => self::SECTION_META,
            'daftarUlp'     => $tahunAktif ? $this->daftarUlp($tahunAktif) : [],
            'triwulanLabel' => self::TRIWULAN_LABEL,
            'bulanLabel'    => self::BULAN_LABEL,
        ]);
    }

    public function generate(Request $request)
    {
        $validated = $request->validate([
            'tahun'       => 'required|integer',
            'sections'    => 'required|array|min:1',
            'sections.*'  => 'string|in:' . implode(',', array_keys(self::SECTION_META)),
            'triwulan'    => 'nullable|integer|in:1,2,3,4',
            'bulan_awal'  => 'nullable|integer|min:1|max:12',
            'bulan_akhir' => 'nullable|integer|min:1|max:12',
            'ulp'         => 'nullable|array',
            'ulp.*'       => 'string',
        ]);

        $tahun     = (int) $validated['tahun'];
        $sections  = $validated['sections'];
        $ulpKode   = $validated['ulp'] ?? null;
        $bulanList = $this->resolveBulanList($validated);

        $data = [];
        $narasi = [];

        foreach ($sections as $section) {
            $d = match ($section) {
                'target_realisasi' => $this->dataTargetRealisasi($tahun, $bulanList, $ulpKode),
                'gol_tarif'         => $this->dataGolTarif($tahun),
                'rekap_ulp'         => $this->dataRekapUlp($tahun, $bulanList, $ulpKode),
                'komposisi_temuan'  => $this->dataKomposisiTemuan($tahun, $bulanList, $ulpKode),
                'trend_kwh'         => $this->dataTrend($tahun, 'kwh', $bulanList, $ulpKode),
                'trend_ts'          => $this->dataTrend($tahun, 'ts', $bulanList, $ulpKode),
                'pencapaian'        => $this->dataPencapaian($tahun, 'kwh', $bulanList, $ulpKode),
                default             => null,
            };

            $data[$section]   = $d;
            $narasi[$section] = $d ? $this->narasi($section, $d, $validated) : '';
        }

        $filterInfo = $this->ringkasanFilter($tahun, $validated, $ulpKode);

        $pdf = Pdf::loadView('export-pdf.pdf-document', [
            'tahun'       => $tahun,
            'sections'    => $sections,
            'data'        => $data,
            'narasi'      => $narasi,
            'sectionMeta' => self::SECTION_META,
            'filterInfo'  => $filterInfo,
        ])->setPaper('a4', 'portrait');

        $namaFile = 'laporan-p2tl-' . $tahun . '-' . now()->format('Ymd-His') . '.pdf';

        return $pdf->download($namaFile);
    }

    /** Ambil kode ULP dari no_agenda (P2TL/{ULP}/{tanggal}/{urut}). */
    private function kodeUlp(?string $noAgenda): ?string
    {
        $parts = explode('/', (string) $noAgenda);
        return $parts[1] ?? null;
    }

    /** Ubah pilihan triwulan / rentang bulan dari form jadi array angka bulan (null = seluruh tahun). */
    private function resolveBulanList(array $validated): ?array
    {
        if (! empty($validated['triwulan'])) {
            return self::TRIWULAN[$validated['triwulan']];
        }

        if (! empty($validated['bulan_awal']) && ! empty($validated['bulan_akhir'])) {
            $awal  = min($validated['bulan_awal'], $validated['bulan_akhir']);
            $akhir = max($validated['bulan_awal'], $validated['bulan_akhir']);
            return range($awal, $akhir);
        }

        return null;
    }

    /** Ringkasan filter yang dipakai user, untuk ditampilkan di form & di kop PDF. */
    private function ringkasanFilter(int $tahun, array $validated, ?array $ulpKode): array
    {
        $periode = 'Sepanjang Tahun ' . $tahun;

        if (! empty($validated['triwulan'])) {
            $periode = self::TRIWULAN_LABEL[$validated['triwulan']] . ' ' . $tahun;
        } elseif (! empty($validated['bulan_awal']) && ! empty($validated['bulan_akhir'])) {
            $awal  = min($validated['bulan_awal'], $validated['bulan_akhir']);
            $akhir = max($validated['bulan_awal'], $validated['bulan_akhir']);
            $periode = self::BULAN_LABEL[$awal] . ' – ' . self::BULAN_LABEL[$akhir] . ' ' . $tahun;
        }

        $ulpLabel = 'Seluruh ULP';
        if ($ulpKode) {
            $nama = array_map(fn ($k) => DetailTagihanSusulan::namaUlp($k) ?? $k, $ulpKode);
            $ulpLabel = implode(', ', $nama);
        }

        return ['tahun' => $tahun, 'periode' => $periode, 'ulp' => $ulpLabel];
    }

    /**
     * Query terpusat: ambil detail tagihan susulan untuk tahun tertentu,
     * join ke laporan_susulans (status aktif), opsional difilter bulan & golongan.
     * Filter ULP diterapkan setelahnya di PHP karena kode ULP diparsing dari no_agenda.
     */
    private function ambilDetail(int $tahun, ?array $bulanList, ?array $ulpKode, array $golFilter = []): Collection
    {
        $query = DetailTagihanSusulan::query()
            ->join('laporan_susulans', 'laporan_susulans.id', '=', 'detail_tagihan_susulans.laporan_susulan_id')
            ->where('laporan_susulans.status', 'aktif')
            ->where('laporan_susulans.tahun', $tahun)
            ->select(
                'detail_tagihan_susulans.no_agenda',
                'detail_tagihan_susulans.gol',
                'detail_tagihan_susulans.kwh',
                'detail_tagihan_susulans.ts',
                'laporan_susulans.bulan',
            );

        if ($bulanList) {
            $namaBulan = array_map(fn ($b) => self::BULAN_NAMA[$b], $bulanList);
            $query->whereIn('laporan_susulans.bulan', $namaBulan);
        }

        if ($golFilter) {
            $query->whereIn('detail_tagihan_susulans.gol', $golFilter);
        }

        $rows = $query->get();

        if ($ulpKode) {
            $rows = $rows->filter(fn ($r) => in_array($this->kodeUlp($r->no_agenda), $ulpKode, true))->values();
        }

        return $rows;
    }

    /** Daftar kode+nama ULP yang punya data pada tahun tsb, untuk checkbox filter di form. */
    private function daftarUlp(int $tahun): array
    {
        $peta = [];
        foreach ($this->ambilDetail($tahun, null, null) as $r) {
            $kode = $this->kodeUlp($r->no_agenda);
            if (! $kode || isset($peta[$kode])) continue;
            $peta[$kode] = DetailTagihanSusulan::namaUlp($kode) ?? $kode;
        }
        asort($peta);
        return $peta;
    }

    // ================= TARGET VS REALISASI =================
    private function dataTargetRealisasi(int $tahun, ?array $bulanList, ?array $ulpKode): array
    {
        $targetQuery = TargetBulanan::where('tahun', $tahun)->where('jenis', 'kwh')->whereNotNull('ulp');
        if ($bulanList) $targetQuery->whereIn('bulan', $bulanList);
        if ($ulpKode) $targetQuery->whereIn('ulp', $ulpKode);
        $targetPerUlp = $targetQuery->get()->groupBy('ulp')->map(fn ($rows) => $rows->sum('nilai_target'));

        $realisasiPerUlp = [];
        foreach ($this->ambilDetail($tahun, $bulanList, $ulpKode, array_merge(self::KOLOM_ULP_P, self::KOLOM_ULP_K)) as $b) {
            $kode = $this->kodeUlp($b->no_agenda);
            if (! $kode) continue;
            $realisasiPerUlp[$kode] = ($realisasiPerUlp[$kode] ?? 0) + (float) $b->kwh;
        }

        $semuaKode = collect($targetPerUlp->keys())->merge(array_keys($realisasiPerUlp))->unique();
        $rows = []; $totalTarget = 0; $totalRealisasi = 0;
        foreach ($semuaKode as $kode) {
            $target    = (float) ($targetPerUlp[$kode] ?? 0);
            $realisasi = $realisasiPerUlp[$kode] ?? 0;
            $rows[] = [
                'nama'      => DetailTagihanSusulan::namaUlp($kode) ?? $kode,
                'target'    => $target,
                'realisasi' => $realisasi,
                'persen'    => $target > 0 ? ($realisasi / $target * 100) : 0,
            ];
            $totalTarget += $target; $totalRealisasi += $realisasi;
        }
        usort($rows, fn ($a, $b) => strcmp($a['nama'], $b['nama']));

        return [
            'rows' => $rows, 'totalTarget' => $totalTarget, 'totalRealisasi' => $totalRealisasi,
            'totalPersen' => $totalTarget > 0 ? ($totalRealisasi / $totalTarget * 100) : 0,
        ];
    }

    // ================= GOL TARIF (Prabayar/Paskabayar) — tahunan, tidak difilter triwulan/ULP =================
    private function dataGolTarif(int $tahun): array
    {
        $peta = [];
        foreach (RingkasanGolTarif::where('tahun', $tahun)->get() as $r) {
            $peta[$r->tarif][$r->gol] = (float) $r->total_ts;
        }

        return [
            'prabayar'   => $this->susunPivot($peta, self::KODE_TARIF_PRABAYAR, true),
            'paskabayar' => $this->susunPivot($peta, self::KODE_TARIF_PASKABAYAR, false),
        ];
    }

    private function susunPivot(array $peta, array $daftarKode, bool $prabayar): array
    {
        $pivot = []; $grand = 0; $totalKolom = array_fill_keys(self::KOLOM_GOL, 0);
        foreach ($daftarKode as $kode) {
            $label = $kode === 'NON PLG' ? 'NON PLG' : ($prabayar ? $kode . 'T' : $kode);
            $nilai = []; $totalBaris = 0;
            foreach (self::KOLOM_GOL as $g) {
                $sum = $peta[$label][$g] ?? 0;
                $nilai[$g] = $sum; $totalBaris += $sum; $totalKolom[$g] += $sum;
            }
            $grand += $totalBaris;
            $pivot[] = ['label' => $label, 'nilai' => $nilai, 'total' => $totalBaris];
        }
        foreach ($pivot as &$b) { $b['persen'] = $grand > 0 ? ($b['total'] / $grand * 100) : 0; }
        $totalKolom['grand_total'] = $grand;
        return ['rows' => $pivot, 'total' => $totalKolom];
    }

    // ================= REKAP KWH PER ULP =================
    private function dataRekapUlp(int $tahun, ?array $bulanList, ?array $ulpKode): array
    {
        return [
            'p' => $this->rekapKwhPerUlp($tahun, $bulanList, $ulpKode, self::KOLOM_ULP_P),
            'k' => $this->rekapKwhPerUlp($tahun, $bulanList, $ulpKode, self::KOLOM_ULP_K),
        ];
    }

    private function rekapKwhPerUlp(int $tahun, ?array $bulanList, ?array $ulpKode, array $daftarGol): array
    {
        $peta = [];
        foreach ($this->ambilDetail($tahun, $bulanList, $ulpKode, $daftarGol) as $b) {
            $kode = $this->kodeUlp($b->no_agenda);
            if (! $kode) continue;
            $peta[$kode][$b->gol] = ($peta[$kode][$b->gol] ?? 0) + (float) $b->kwh;
        }

        $rows = []; $totalGol = array_fill_keys($daftarGol, 0); $grand = 0;
        foreach ($peta as $kode => $nilaiGol) {
            $row = ['nama' => DetailTagihanSusulan::namaUlp($kode) ?? $kode];
            $totalBaris = 0;
            foreach ($daftarGol as $g) {
                $kwh = $nilaiGol[$g] ?? 0;
                $row[strtolower($g)] = $kwh; $totalBaris += $kwh; $totalGol[$g] += $kwh;
            }
            $row['total'] = $totalBaris; $grand += $totalBaris;
            $rows[] = $row;
        }
        usort($rows, fn ($a, $b) => strcmp($a['nama'], $b['nama']));

        return ['kolom' => $daftarGol, 'rows' => $rows, 'totalGol' => $totalGol, 'grand' => $grand];
    }

    // ================= KOMPOSISI TEMUAN =================
    private function dataKomposisiTemuan(int $tahun, ?array $bulanList, ?array $ulpKode): array
    {
        $peta = [];
        foreach ($this->ambilDetail($tahun, $bulanList, $ulpKode, array_merge(self::KOLOM_ULP_P, self::KOLOM_ULP_K)) as $b) {
            $kode = $this->kodeUlp($b->no_agenda);
            if (! $kode) continue;
            $kel = in_array($b->gol, self::KOLOM_ULP_P) ? 'p' : 'k';
            $peta[$kode] ??= ['p' => ['plg' => 0, 'kwh' => 0, 'ts' => 0], 'k' => ['plg' => 0, 'kwh' => 0, 'ts' => 0]];
            $peta[$kode][$kel]['plg']++;
            $peta[$kode][$kel]['kwh'] += (float) $b->kwh;
            $peta[$kode][$kel]['ts']  += (float) $b->ts;
        }

        $rows = []; $totalKwh = 0;
        foreach ($peta as $kode => $d) {
            $tkwh = $d['p']['kwh'] + $d['k']['kwh'];
            $rows[] = [
                'nama' => DetailTagihanSusulan::namaUlp($kode) ?? $kode, 'p' => $d['p'], 'k' => $d['k'],
                'total_kwh' => $tkwh,
                'persen_p'  => $tkwh > 0 ? ($d['p']['kwh'] / $tkwh * 100) : 0,
                'persen_k'  => $tkwh > 0 ? ($d['k']['kwh'] / $tkwh * 100) : 0,
            ];
            $totalKwh += $tkwh;
        }
        usort($rows, fn ($a, $b) => strcmp($a['nama'], $b['nama']));

        return ['rows' => $rows, 'totalKwh' => $totalKwh];
    }

    // ================= TREND kWh / Rp TS =================
    private function dataTrend(int $tahun, string $metric, ?array $bulanList, ?array $ulpKode): array
    {
        $kolom = $metric === 'kwh' ? 'kwh' : 'ts';
        $totalPerBulan = array_fill_keys(range(1, 12), 0.0);

        foreach ($this->ambilDetail($tahun, $bulanList, $ulpKode) as $r) {
            $angka = self::BULAN_ANGKA[$r->bulan] ?? null;
            if (! $angka) continue;
            $totalPerBulan[$angka] += (float) $r->{$kolom};
        }

        $bulanTampil = $bulanList ?: range(1, 12);
        $rows = []; $totalTahun = 0;
        foreach ($bulanTampil as $angka) {
            $nilai = $totalPerBulan[$angka];
            $rows[] = ['label' => self::BULAN_LABEL[$angka], 'nilai' => $nilai];
            $totalTahun += $nilai;
        }

        return ['rows' => $rows, 'total' => $totalTahun];
    }

    // ================= PRESENTASE PENCAPAIAN =================
    private function dataPencapaian(int $tahun, string $jenis, ?array $bulanList, ?array $ulpKode): array
    {
        $kolom = $jenis === 'kwh' ? 'kwh' : 'ts';
        $aktualPerBulan = array_fill_keys(range(1, 12), 0.0);

        foreach ($this->ambilDetail($tahun, $bulanList, $ulpKode) as $r) {
            $angka = self::BULAN_ANGKA[$r->bulan] ?? null;
            if (! $angka) continue;
            $aktualPerBulan[$angka] += (float) $r->{$kolom};
        }

        // Catatan: target bulanan di sini diasumsikan tidak per-ULP (sesuai kode asli).
        // Kalau TargetBulanan kamu juga punya kolom ulp untuk jenis ini, tambahkan whereIn('ulp', $ulpKode).
        $targetPerBulan = TargetBulanan::where('tahun', $tahun)->where('jenis', $jenis)
            ->get()->groupBy('bulan')->map(fn ($rows) => $rows->sum('nilai_target'));

        $bulanTampil = $bulanList ?: range(1, 12);
        $rows = []; $totalAktual = 0; $totalTarget = 0;
        foreach ($bulanTampil as $angka) {
            $aktual = $aktualPerBulan[$angka];
            $target = (float) ($targetPerBulan[$angka] ?? 0);
            $rows[] = [
                'label' => self::BULAN_LABEL[$angka], 'aktual' => $aktual, 'target' => $target,
                'persen' => $target > 0 ? round($aktual / $target * 100, 1) : null,
            ];
            $totalAktual += $aktual; $totalTarget += $target;
        }

        return [
            'rows' => $rows, 'totalAktual' => $totalAktual, 'totalTarget' => $totalTarget,
            'persenTotal' => $totalTarget > 0 ? ($totalAktual / $totalTarget * 100) : null,
        ];
    }

    // ================= NARASI DESKRIPTIF PER BAGIAN =================
    /**
     * Susun narasi per bagian laporan. Ditulis lebih panjang dari versi sebelumnya,
     * tapi tetap dijaga supaya kalimatnya wajar dan enak dibaca (bukan gaya template kaku),
     * dengan variasi kalimat dan sedikit konteks tambahan di tiap bagian.
     */
    private function narasi(string $section, array $d, array $validated = []): string
    {
        $periodeTeks = $this->periodeTeksNarasi($validated);

        return match ($section) {
            'target_realisasi' => sprintf(
                'Selama %s, seluruh ULP mencatatkan realisasi sebesar %s kWh, sementara target yang ditetapkan berada di angka %s kWh — artinya pencapaian keseluruhan berada di kisaran %s%%. %s Angka ini disusun dari akumulasi temuan P2TL golongan P dan K yang sudah masuk dan terverifikasi pada periode berjalan, sehingga cukup mewakili kondisi lapangan secara umum. Kalau dilihat lebih detail per ULP pada tabel di bawah, akan terlihat unit mana yang sudah melampaui target dan mana yang realisasinya masih tertinggal, dan ini bisa jadi bahan diskusi lebih lanjut soal intensitas operasi maupun kewajaran target yang sudah dipasang di awal tahun.',
                $periodeTeks,
                number_format($d['totalRealisasi'], 0, ',', '.'),
                number_format($d['totalTarget'], 0, ',', '.'),
                number_format($d['totalPersen'], 1, ',', '.'),
                $d['totalPersen'] >= 100
                    ? 'Dengan kata lain, secara agregat realisasi sudah melampaui atau setidaknya memenuhi target yang dipasang di awal periode.'
                    : 'Dengan kata lain, secara agregat realisasi masih berada di bawah target yang dipasang di awal periode, dan masih ada ruang untuk dikejar sampai akhir periode.'
            ),
            'gol_tarif' => sprintf(
                'Rekap tagihan susulan untuk %s menunjukkan total sebesar Rp%s dari pelanggan prabayar dan Rp%s dari pelanggan paskabayar. Bagian ini memang selalu disusun secara tahunan dan tidak mengikuti filter triwulan atau ULP tertentu, karena sumber datanya adalah ringkasan golongan tarif yang direkap per tahun, bukan per temuan individual. Perbandingan antara kedua tipe pelanggan ini biasanya berguna untuk melihat segmen mana yang menyumbang nilai tagihan susulan paling besar, sekaligus jadi acuan kalau ada evaluasi kebijakan terkait golongan tarif tertentu ke depannya.',
                'tahun ' . ($periodeTeksTahun = $this->tahunDariValidated($validated)),
                number_format($d['prabayar']['total']['grand_total'], 0, ',', '.'),
                number_format($d['paskabayar']['total']['grand_total'], 0, ',', '.'),
            ),
            'rekap_ulp' => sprintf(
                'Pada %s, total temuan golongan P dari seluruh ULP tercatat sebesar %s kWh, sedangkan golongan K sebesar %s kWh. Selisih antara kedua golongan ini cukup mencerminkan pola pelanggaran yang lebih dominan di lapangan pada periode tersebut — apakah didominasi pelanggan daya besar (golongan P) atau justru menyebar di pelanggan rumah tangga dan kecil (golongan K). Rincian per ULP pada tabel berikut bisa dipakai untuk melihat unit mana yang kontribusinya paling besar terhadap total ini, sekaligus jadi dasar kalau ada rencana pemerataan fokus operasi P2TL antar wilayah.',
                $periodeTeks,
                number_format($d['p']['grand'], 0, ',', '.'),
                number_format($d['k']['grand'], 0, ',', '.'),
            ),
            'komposisi_temuan' => sprintf(
                'Selama %s, akumulasi kWh temuan dari seluruh UP3 mencapai %s kWh, gabungan dari golongan P dan golongan K. Komposisi ini penting untuk melihat imbangan antara jumlah pelanggan yang terjaring dengan besaran kWh yang dihasilkan, karena tidak selalu berbanding lurus — bisa saja satu UP3 punya jumlah pelanggan temuan sedikit tapi kontribusi kWh-nya besar, atau sebaliknya. Tabel di bawah merinci proporsi masing-masing UP3 beserta persentase golongan P dan K, yang bisa dipakai sebagai bahan pemetaan area prioritas untuk operasi selanjutnya.',
                $periodeTeks,
                number_format($d['totalKwh'], 0, ',', '.'),
            ),
            'trend_kwh' => sprintf(
                'Sepanjang %s, total kWh temuan P2TL yang berhasil dicatat mencapai %s kWh. Pergerakan bulanan pada tabel di bawah menggambarkan naik-turunnya intensitas temuan dari bulan ke bulan, yang biasanya dipengaruhi oleh jumlah operasi lapangan yang dijalankan maupun musim atau agenda tertentu di masing-masing ULP. Tren ini cukup berguna untuk melihat apakah ada bulan-bulan tertentu yang perlu jadi perhatian, baik karena lonjakan maupun penurunan yang cukup signifikan dibanding bulan lainnya.',
                $periodeTeks,
                number_format($d['total'], 0, ',', '.'),
            ),
            'trend_ts' => sprintf(
                'Total tagihan susulan yang tercatat sepanjang %s mencapai Rp%s. Sama seperti tren kWh, pergerakan nilai rupiah tagihan susulan per bulan pada tabel di bawah bisa mencerminkan pola operasi lapangan, meski nilainya tidak selalu sejalan satu-satu dengan tren kWh — karena tarif per golongan pelanggan yang berbeda-beda ikut memengaruhi besar kecilnya nominal tagihan. Data ini bisa dipakai sebagai salah satu indikator pendukung selain angka kWh murni saat mengevaluasi kinerja bulanan.',
                $periodeTeks,
                number_format($d['total'], 0, ',', '.'),
            ),
            'pencapaian' => sprintf(
                'Untuk %s, realisasi kWh yang tercapai adalah %s kWh dari target sebesar %s kWh, atau setara dengan pencapaian rata-rata sekitar %s%%. Angka persentase per bulan pada tabel di bawah menunjukkan bulan mana saja yang sudah memenuhi atau bahkan melampaui target, dan bulan mana yang masih di bawahnya. Perlu dicatat juga bahwa target bulanan pada bagian ini disusun secara umum dan belum tentu dipecah per ULP, jadi angka pencapaiannya sebaiknya dibaca sebagai gambaran keseluruhan, bukan evaluasi kinerja satu unit tertentu.',
                $periodeTeks,
                number_format($d['totalAktual'], 0, ',', '.'),
                number_format($d['totalTarget'], 0, ',', '.'),
                $d['persenTotal'] === null ? '-' : number_format($d['persenTotal'], 1, ',', '.'),
            ),
            default => '',
        };
    }

    /** Teks periode singkat (untuk disisipkan di awal kalimat narasi), tanpa perlu tahun eksplisit di semua tempat. */
    private function periodeTeksNarasi(array $validated): string
    {
        if (! empty($validated['triwulan'])) {
            return self::TRIWULAN_LABEL[$validated['triwulan']] . ' tahun ' . ($validated['tahun'] ?? '');
        }

        if (! empty($validated['bulan_awal']) && ! empty($validated['bulan_akhir'])) {
            $awal  = min($validated['bulan_awal'], $validated['bulan_akhir']);
            $akhir = max($validated['bulan_awal'], $validated['bulan_akhir']);
            return 'periode ' . self::BULAN_LABEL[$awal] . '–' . self::BULAN_LABEL[$akhir] . ' tahun ' . ($validated['tahun'] ?? '');
        }

        return 'tahun ' . ($validated['tahun'] ?? '');
    }

    private function tahunDariValidated(array $validated): string
    {
        return (string) ($validated['tahun'] ?? '');
    }
}