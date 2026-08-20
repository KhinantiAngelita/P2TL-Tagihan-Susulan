<?php

namespace App\Http\Controllers;

use App\Models\DetailTagihanSusulan;
use App\Models\LaporanSusulan;
use App\Models\RingkasanGolTarif;
use App\Models\TargetBulanan;
use Illuminate\Http\Request;

class LaporanGolTarifController extends Controller
    {
        private const TRIWULAN_BULAN = [
        1 => [1, 2, 3],
        2 => [4, 5, 6],
        3 => [7, 8, 9],
        4 => [10, 11, 12],
    ];

    public function targetRealisasi(Request $request)
    {
        $request->validate([
            'tahun' => 'nullable|integer',
            'tw'    => 'nullable|integer|in:1,2,3,4',
        ]);

        $daftarTahun = LaporanSusulan::aktif()
            ->whereNotNull('tahun')
            ->distinct()
            ->orderByDesc('tahun')
            ->pluck('tahun');

        $tahunAktif = (int) ($request->input('tahun') ?: $daftarTahun->first());
        $twAktif    = (int) $request->input('tw', 1);

        if (! array_key_exists($twAktif, self::TRIWULAN_BULAN)) {
            $twAktif = 1;
        }
        $bulanTw = self::TRIWULAN_BULAN[$twAktif];

        // ---- Target: jumlah nilai_target (jenis kwh) per ULP untuk 3 bulan di TW ini ----
        $targetRows = TargetBulanan::where('tahun', $tahunAktif)
            ->where('jenis', 'kwh')
            ->whereIn('bulan', $bulanTw)
            ->whereNotNull('ulp')
            ->get();

        $targetPerUlp = [];
        foreach ($targetRows as $t) {
            $targetPerUlp[$t->ulp] = ($targetPerUlp[$t->ulp] ?? 0) + (float) $t->nilai_target;
        }

        // ---- Realisasi: SUM(kwh) golongan P & K per ULP, difilter ke bulan di TW ini
        // (bulan diambil dari tanggal_agenda hasil parsing no_agenda, bukan tanggal_register) ----
        $laporanAktifIds = LaporanSusulan::aktif()
            ->where('tahun', $tahunAktif)
            ->pluck('id');

        $barisMentah = DetailTagihanSusulan::query()
            ->whereIn('laporan_susulan_id', $laporanAktifIds)
            ->whereIn('gol', array_merge(self::KOLOM_ULP_P, self::KOLOM_ULP_K))
            ->select('no_agenda', 'kwh')
            ->get();

        $realisasiPerUlp = [];
        foreach ($barisMentah as $baris) {
            $parts      = explode('/', (string) $baris->no_agenda);
            $kodeUlp    = $parts[1] ?? null;
            $tanggalStr = $parts[2] ?? null;

            if (! $kodeUlp || ! $tanggalStr || ! preg_match('/^\d{8}$/', $tanggalStr)) {
                continue;
            }

            $bulan = (int) substr($tanggalStr, 4, 2);
            if (! in_array($bulan, $bulanTw)) {
                continue;
            }

            $realisasiPerUlp[$kodeUlp] = ($realisasiPerUlp[$kodeUlp] ?? 0) + (float) $baris->kwh;
        }

        // ---- Gabungkan semua kode ULP yang muncul di Target ATAU Realisasi ----
        $semuaKodeUlp = collect(array_keys($targetPerUlp))
            ->merge(array_keys($realisasiPerUlp))
            ->unique();

        $rows = [];
        $totalTarget    = 0;
        $totalRealisasi = 0;

        foreach ($semuaKodeUlp as $kodeUlp) {
            $target    = $targetPerUlp[$kodeUlp] ?? 0;
            $realisasi = $realisasiPerUlp[$kodeUlp] ?? 0;
            $persen    = $target > 0 ? ($realisasi / $target * 100) : 0;

            $rows[] = [
                'kode'      => $kodeUlp,
                'nama'      => DetailTagihanSusulan::namaUlp($kodeUlp) ?? $kodeUlp,
                'target'    => $target,
                'realisasi' => $realisasi,
                'persen'    => $persen,
            ];

            $totalTarget    += $target;
            $totalRealisasi += $realisasi;
        }

        usort($rows, fn ($a, $b) => strcmp($a['nama'], $b['nama']));

        $totalPersen = $totalTarget > 0 ? ($totalRealisasi / $totalTarget * 100) : 0;

        return view('laporan.target-realisasi', [
            'daftarTahun'    => $daftarTahun,
            'tahunAktif'     => $tahunAktif,
            'twAktif'        => $twAktif,
            'rows'           => $rows,
            'totalTarget'    => $totalTarget,
            'totalRealisasi' => $totalRealisasi,
            'totalPersen'    => $totalPersen,
        ]);
    }
    private const KODE_TARIF_PRABAYAR = [
        'S1', 'S2', 'S3',
        'R1', 'R1M', 'R2', 'R3',
        'B1', 'B2', 'B3',
        'I1', 'I2', 'I3', 'I4',
        'P1', 'P2', 'P3',
        'L', 'T', 'C',
    ];

    private const KODE_TARIF_PASKABAYAR = [
        'S1', 'S2', 'S3',
        'R1', 'R1M', 'R2', 'R3',
        'B1', 'B2', 'B3',
        'I1', 'I2', 'I3', 'I4',
        'P1', 'P2', 'P3',
        'NON PLG',
        'L', 'T', 'C',
    ];

    private const KOLOM_GOL = ['P1', 'P2', 'P3', 'P4'];

    private const KOLOM_ULP_P = ['P1', 'P2', 'P3', 'P4'];
    private const KOLOM_ULP_K = ['K1', 'K2', 'K3', 'K4'];

    public function golTarif(Request $request)
    {
        $request->validate([
            'tahun' => 'nullable|integer',
        ]);

        $daftarTahun = LaporanSusulan::aktif()
            ->whereNotNull('tahun')
            ->distinct()
            ->orderByDesc('tahun')
            ->pluck('tahun');

        $tahunAktif = (int) ($request->input('tahun') ?: $daftarTahun->first());

        // ---- Baca langsung dari tabel ringkasan (precomputed), bukan
        // agregasi live. Data ringkasan diisi/di-update oleh
        // RingkasanGolTarifService setiap kali data laporan berubah
        // (lihat catatan integrasi di bawah).
        $ringkasan = RingkasanGolTarif::where('tahun', $tahunAktif)->get();

        $peta = [];
        foreach ($ringkasan as $row) {
            $peta[$row->tarif][$row->gol] = (float) $row->total_ts;
        }

        [$prabayar, $totalPrabayar] = $this->susunPivot($peta, self::KODE_TARIF_PRABAYAR, prabayar: true);
        [$paskabayar, $totalPaskabayar] = $this->susunPivot($peta, self::KODE_TARIF_PASKABAYAR, prabayar: false);

        // ---- Tabel Rekap KWH per ULP: dipisah 2 — golongan P (prabayar)
        // dan golongan K, masing-masing dihitung pakai helper yang sama. ----
        $laporanAktifIds = LaporanSusulan::aktif()
            ->where('tahun', $tahunAktif)
            ->pluck('id');

        [$ulpRowsP, $ulpTotalP] = $this->rekapKwhPerUlp($laporanAktifIds, self::KOLOM_ULP_P);
        [$ulpRowsK, $ulpTotalK] = $this->rekapKwhPerUlp($laporanAktifIds, self::KOLOM_ULP_K);

        return view('laporan.gol-tarif', [
            'daftarTahun'     => $daftarTahun,
            'tahunAktif'      => $tahunAktif,
            'kolomGol'        => self::KOLOM_GOL,
            'prabayar'        => $prabayar,
            'totalPrabayar'   => $totalPrabayar,
            'paskabayar'      => $paskabayar,
            'totalPaskabayar' => $totalPaskabayar,
            'kolomUlpP'       => self::KOLOM_ULP_P,
            'ulpRowsP'        => $ulpRowsP,
            'ulpTotalP'       => $ulpTotalP,
            'kolomUlpK'       => self::KOLOM_ULP_K,
            'ulpRowsK'        => $ulpRowsK,
            'ulpTotalK'       => $ulpTotalK,
        ]);
    }

    public function komposisiTemuan(Request $request)
    {
        $request->validate([
            'tahun' => 'nullable|integer',
        ]);

        $daftarTahun = LaporanSusulan::aktif()
            ->whereNotNull('tahun')
            ->distinct()
            ->orderByDesc('tahun')
            ->pluck('tahun');

        $tahunAktif = (int) ($request->input('tahun') ?: $daftarTahun->first());

        $laporanAktifIds = LaporanSusulan::aktif()
            ->where('tahun', $tahunAktif)
            ->pluck('id');

        $barisMentah = DetailTagihanSusulan::query()
            ->whereIn('laporan_susulan_id', $laporanAktifIds)
            ->whereIn('gol', array_merge(self::KOLOM_ULP_P, self::KOLOM_ULP_K))
            ->select('no_agenda', 'gol', 'kwh', 'ts')
            ->get();

        $petaUlp = [];
        foreach ($barisMentah as $baris) {
            $parts = explode('/', (string) $baris->no_agenda);
            $kodeUlp = $parts[1] ?? null;

            if (! $kodeUlp) {
                continue;
            }

            $kelompok = in_array($baris->gol, self::KOLOM_ULP_P) ? 'p' : 'k';

            if (! isset($petaUlp[$kodeUlp])) {
                $petaUlp[$kodeUlp] = [
                    'p' => ['plg' => 0, 'kwh' => 0, 'ts' => 0],
                    'k' => ['plg' => 0, 'kwh' => 0, 'ts' => 0],
                ];
            }

            $petaUlp[$kodeUlp][$kelompok]['plg'] += 1;
            $petaUlp[$kodeUlp][$kelompok]['kwh'] += (float) $baris->kwh;
            $petaUlp[$kodeUlp][$kelompok]['ts']  += (float) $baris->ts;
        }

        $rows = [];
        $totalP = ['plg' => 0, 'kwh' => 0, 'ts' => 0];
        $totalK = ['plg' => 0, 'kwh' => 0, 'ts' => 0];

        foreach ($petaUlp as $kodeUlp => $data) {
            $totalKwh = $data['p']['kwh'] + $data['k']['kwh'];
            $persenP  = $totalKwh > 0 ? ($data['p']['kwh'] / $totalKwh * 100) : 0;
            $persenK  = $totalKwh > 0 ? 100 - $persenP : 0;

            $rows[] = [
                'kode'       => $kodeUlp,
                'nama'       => DetailTagihanSusulan::namaUlp($kodeUlp) ?? $kodeUlp,
                'p'          => $data['p'],
                'k'          => $data['k'],
                'total_kwh'  => $totalKwh,
                'persen_p'   => $persenP,
                'persen_k'   => $persenK,
            ];

            $totalP['plg'] += $data['p']['plg'];
            $totalP['kwh'] += $data['p']['kwh'];
            $totalP['ts']  += $data['p']['ts'];
            $totalK['plg'] += $data['k']['plg'];
            $totalK['kwh'] += $data['k']['kwh'];
            $totalK['ts']  += $data['k']['ts'];
        }

        usort($rows, fn ($a, $b) => strcmp($a['nama'], $b['nama']));

        $grandTotalKwh = $totalP['kwh'] + $totalK['kwh'];
        $totalRingkasan = [
            'p'         => $totalP,
            'k'         => $totalK,
            'total_kwh' => $grandTotalKwh,
            'persen_p'  => $grandTotalKwh > 0 ? ($totalP['kwh'] / $grandTotalKwh * 100) : 0,
            'persen_k'  => $grandTotalKwh > 0 ? ($totalK['kwh'] / $grandTotalKwh * 100) : 0,
        ];

        return view('laporan.komposisi-temuan', [
            'daftarTahun'    => $daftarTahun,
            'tahunAktif'     => $tahunAktif,
            'rows'           => $rows,
            'totalRingkasan' => $totalRingkasan,
        ]);
    }

    /**
     * Hitung rekap KWH per ULP untuk sekumpulan kode golongan tertentu
     * (dipakai buat golongan P1-P4 dan K1-K4 secara terpisah).
     *
     * @return array{0: array, 1: array} [$rows, $total]
     */
    private function rekapKwhPerUlp($laporanAktifIds, array $daftarGol): array
    {
        $barisMentah = DetailTagihanSusulan::query()
            ->whereIn('laporan_susulan_id', $laporanAktifIds)
            ->whereIn('gol', $daftarGol)
            ->select('no_agenda', 'gol', 'kwh')
            ->get();

        $petaUlp = [];
        foreach ($barisMentah as $baris) {
            $parts = explode('/', (string) $baris->no_agenda);
            $kodeUlp = $parts[1] ?? null;

            if (! $kodeUlp) {
                continue;
            }

            if (! isset($petaUlp[$kodeUlp][$baris->gol])) {
                $petaUlp[$kodeUlp][$baris->gol] = 0;
            }
            $petaUlp[$kodeUlp][$baris->gol] += (float) $baris->kwh;
        }

        $rows = [];
        $totalPerGol = array_fill_keys($daftarGol, 0);
        $grandTotalKwh = 0;

        foreach ($petaUlp as $kodeUlp => $nilaiGol) {
            $row = [
                'kode' => $kodeUlp,
                'nama' => DetailTagihanSusulan::namaUlp($kodeUlp) ?? $kodeUlp,
            ];

            $totalBaris = 0;
            foreach ($daftarGol as $gol) {
                $kwh = $nilaiGol[$gol] ?? 0;
                $row[strtolower($gol)] = ['kwh' => $kwh, 'persen' => 0];
                $totalBaris += $kwh;
                $totalPerGol[$gol] += $kwh;
            }

            $row['total'] = ['kwh' => $totalBaris, 'persen' => 0];
            $grandTotalKwh += $totalBaris;
            $rows[] = $row;
        }

        usort($rows, fn ($a, $b) => strcmp($a['nama'], $b['nama']));

        $total = [];
        foreach ($daftarGol as $gol) {
            $total[strtolower($gol)] = ['kwh' => $totalPerGol[$gol], 'persen' => 0];
        }
        $total['total'] = ['kwh' => $grandTotalKwh, 'persen' => 0];

        return [$rows, $total];
    }

    private function susunPivot(array $peta, array $daftarKode, bool $prabayar): array
    {
        $pivot = [];
        $grandTotal = 0;
        $totalPerKolom = array_fill_keys(self::KOLOM_GOL, 0);

        foreach ($daftarKode as $kode) {
            $labelTarif = ($kode === 'NON PLG')
                ? 'NON PLG'
                : ($prabayar ? $kode . 'T' : $kode);

            $nilaiPerKolom = [];
            $totalBaris = 0;
            foreach (self::KOLOM_GOL as $kolomGol) {
                $sum = $peta[$labelTarif][$kolomGol] ?? 0;
                $nilaiPerKolom[$kolomGol] = $sum;
                $totalBaris += $sum;
                $totalPerKolom[$kolomGol] += $sum;
            }

            $grandTotal += $totalBaris;

            $pivot[] = [
                'label' => $labelTarif,
                'kode'  => $kode,
                'nilai' => $nilaiPerKolom,
                'total' => $totalBaris,
            ];
        }

        foreach ($pivot as &$baris) {
            $baris['persen'] = $grandTotal > 0 ? ($baris['total'] / $grandTotal * 100) : 0;
        }
        unset($baris);

        $totalPerKolom['grand_total'] = $grandTotal;

        return [$pivot, $totalPerKolom];
    }
}