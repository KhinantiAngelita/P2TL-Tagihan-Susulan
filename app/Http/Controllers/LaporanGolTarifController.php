<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\FilterPeriodeUlpTrait;
use App\Models\DetailTagihanSusulan;
use App\Models\LaporanSusulan;
use App\Models\TargetBulanan;
use Illuminate\Http\Request;

class LaporanGolTarifController extends Controller
{
    use FilterPeriodeUlpTrait;

    protected const TRIWULAN_BULAN = [
        1 => [1, 2, 3],
        2 => [4, 5, 6],
        3 => [7, 8, 9],
        4 => [10, 11, 12],
    ];

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

    /**
     * Sama persis dengan KODE_TARIF_PRABAYAR tapi sudah diberi akhiran
     * "T" — dipakai buat menyaring baris golongan Prabayar langsung dari
     * kolom `daya` mentah (format "{KODE}T/{angka_daya}", mis. "R1T/450"),
     * dipakai di pivotPerDaya() dan pivotTarifLive().
     */
    private const KODE_TARIF_PRABAYAR_T = [
        'S1T', 'S2T', 'S3T',
        'R1T', 'R1MT', 'R2T', 'R3T',
        'B1T', 'B2T', 'B3T',
        'I1T', 'I2T', 'I3T', 'I4T',
        'P1T', 'P2T', 'P3T',
        'LT', 'TT', 'CT',
    ];

    /**
     * Kode tarif Paskabayar sebagaimana muncul di kolom `daya` mentah
     * (format "{KODE}/{angka_daya}", mis. "R1/450" — TANPA akhiran "T",
     * beda dari Prabayar). Dipakai di pivotPerDaya() dan pivotTarifLive()
     * untuk memilah baris Paskabayar. "NON PLG" sengaja tidak dimasukkan
     * karena baris NON PLG biasanya tidak punya nilai daya yang bermakna
     * untuk dipivot per daya.
     */
    private const KODE_TARIF_PASKABAYAR_DAYA = [
        'S1', 'S2', 'S3',
        'R1', 'R1M', 'R2', 'R3',
        'B1', 'B2', 'B3',
        'I1', 'I2', 'I3', 'I4',
        'P1', 'P2', 'P3',
        'L', 'T', 'C',
    ];

    private const KOLOM_GOL = ['P1', 'P2', 'P3', 'P4', 'K1', 'K2', 'K3'];

    private const KOLOM_ULP_P = ['P1', 'P2', 'P3', 'P4'];
    private const KOLOM_ULP_K = ['K1', 'K2', 'K3'];

    private const RULES_FILTER = [
        'tahun'       => 'nullable|integer',
        'tw'          => 'nullable|array',
        'tw.*'        => 'integer|in:1,2,3,4',
        'bulan'       => 'nullable|array',
        'bulan.*'     => 'integer|between:1,12',
        'ulp'         => 'nullable|array',
        'ulp.*'       => 'string',
        'tgl_mulai'   => 'nullable|date',
        'tgl_selesai' => 'nullable|date|after_or_equal:tgl_mulai',
    ];

    public function targetRealisasi(Request $request)
    {
        $request->validate(self::RULES_FILTER);

        $daftarTahun = LaporanSusulan::aktif()
            ->whereNotNull('tahun')
            ->distinct()
            ->orderByDesc('tahun')
            ->pluck('tahun');

        $tahunAktif = (int) ($request->input('tahun') ?: $daftarTahun->first());

        $filter = $this->ambilFilter($request, $tahunAktif);
        // Kalau tidak ada filter periode sama sekali, target dihitung
        // untuk satu tahun penuh (beda dari perilaku lama yang default
        // ke TW1 saja).
        $bulanUntukTarget = $filter['bulanEfektif'] ?? range(1, 12);

        // ---- Target per ULP: jumlah nilai_target (jenis kwh) untuk bulan-
        // bulan terpilih. Sejak "Semua ULP" di Edit Target mendistribusikan
        // nilainya ke SETIAP kode ULP, tidak ada lagi fallback ke target
        // "global" (ulp=null) di sini. ----
        $targetQuery = TargetBulanan::where('tahun', $tahunAktif)
            ->where('jenis', 'kwh')
            ->whereIn('bulan', $bulanUntukTarget)
            ->whereNotNull('ulp');

        if (! empty($filter['ulpTerpilih'])) {
            $targetQuery->whereIn('ulp', $filter['ulpTerpilih']);
        }

        $targetPerUlp = [];
        foreach ($targetQuery->get() as $t) {
            $targetPerUlp[$t->ulp] = ($targetPerUlp[$t->ulp] ?? 0) + (float) $t->nilai_target;
        }

        // ---- Realisasi: SUM(kwh) golongan P & K per ULP, difilter periode + ULP
        // (bulan diambil dari laporan_susulans.bulan — lihat PERBAIKAN di
        // lolosFilterBaris(); rentang tanggal presisi hari tetap dari
        // tanggal_agenda hasil parsing no_agenda) ----
        $laporanAktif      = LaporanSusulan::aktif()->where('tahun', $tahunAktif)->get(['id', 'bulan']);
        $laporanAktifIds   = $laporanAktif->pluck('id');
        $bulanPerLaporanId = $laporanAktif->pluck('bulan', 'id');

        $barisMentah = DetailTagihanSusulan::query()
            ->whereIn('laporan_susulan_id', $laporanAktifIds)
            ->whereIn('gol', array_merge(self::KOLOM_ULP_P, self::KOLOM_ULP_K))
            ->select('laporan_susulan_id', 'no_agenda', 'kwh')
            ->get();

        $realisasiPerUlp = [];
        foreach ($barisMentah as $baris) {
            $bulanLaporan = $bulanPerLaporanId[$baris->laporan_susulan_id] ?? null;
            $kodeUlp = $this->lolosFilterBaris($baris->no_agenda, $bulanLaporan, $filter);
            if (! $kodeUlp) {
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

        $daftarUlp  = $this->daftarUlpTahunIni($laporanAktifIds, array_merge(self::KOLOM_ULP_P, self::KOLOM_ULP_K));
        $namaUlpMap = collect($daftarUlp)->pluck('nama', 'kode')->all();

        return view('laporan.target-realisasi', [
            'daftarTahun'    => $daftarTahun,
            'tahunAktif'     => $tahunAktif,
            'rows'           => $rows,
            'totalTarget'    => $totalTarget,
            'totalRealisasi' => $totalRealisasi,
            'totalPersen'    => $totalPersen,
            'daftarUlp'      => $daftarUlp,
            'filter'         => $filter,
            'filterInfoText' => $this->teksFilterAktif($tahunAktif, $filter, $namaUlpMap),
        ]);
    }

    public function golTarif(Request $request)
    {
        $request->validate(self::RULES_FILTER);

        $daftarTahun = LaporanSusulan::aktif()
            ->whereNotNull('tahun')
            ->distinct()
            ->orderByDesc('tahun')
            ->pluck('tahun');

        $tahunAktif = (int) ($request->input('tahun') ?: $daftarTahun->first());
        $filter     = $this->ambilFilter($request, $tahunAktif);

        $laporanAktif      = LaporanSusulan::aktif()->where('tahun', $tahunAktif)->get(['id', 'bulan']);
        $laporanAktifIds   = $laporanAktif->pluck('id');
        $bulanPerLaporanId = $laporanAktif->pluck('bulan', 'id');

        // ---- Pivot Prabayar/Paskabayar: LIVE dari detail_tagihan_susulans,
        // jadi ikut filter periode + ULP (sebelumnya dibaca dari tabel
        // precomputed ringkasan_gol_tarifs yang cuma di-agregasi per
        // tahun tanpa filter — makanya dulu chart/tabel Gol Tarif &
        // Gabungan tidak berubah walau filter periode/ULP diterapkan).
        // pivotTarifLive() mereplikasi persis logika agregasi yang
        // dipakai RingkasanGolTarifService::hitungUlang() (kode tarif
        // diambil dari segmen sebelum '/' pada kolom `daya`), tapi
        // dihitung on-the-fly per request supaya lolosFilterBaris() bisa
        // diterapkan per baris. Nilainya SUM(kwh) — bukan Rp TS lagi.
        $peta = $this->pivotTarifLive($laporanAktifIds, $bulanPerLaporanId, $filter);

        [$prabayar, $totalPrabayar]     = $this->susunPivot($peta, self::KODE_TARIF_PRABAYAR, prabayar: true);
        [$paskabayar, $totalPaskabayar] = $this->susunPivot($peta, self::KODE_TARIF_PASKABAYAR, prabayar: false);

        // ---- Pivot "Gol per Daya" (Prabayar & Paskabayar): baris = string
        // daya lengkap (mis. "R1T/450" untuk Prabayar, "R1/450" untuk
        // Paskabayar), kolom = P1-P4 + K1-K3, nilainya SUM(kwh). LIVE dari
        // detail_tagihan_susulans, jadi ikut filter periode + ULP. ----
        [$prabayarPerDaya, $totalPrabayarPerDaya]     = $this->pivotPerDaya($laporanAktifIds, $bulanPerLaporanId, $filter, self::KODE_TARIF_PRABAYAR_T);
        [$paskabayarPerDaya, $totalPaskabayarPerDaya] = $this->pivotPerDaya($laporanAktifIds, $bulanPerLaporanId, $filter, self::KODE_TARIF_PASKABAYAR_DAYA);

        // ---- Tabel Rekap KWH per ULP: live & ikut filter periode + ULP penuh ----
        [$ulpRowsP, $ulpTotalP] = $this->rekapKwhPerUlp($laporanAktifIds, $bulanPerLaporanId, self::KOLOM_ULP_P, $filter);
        [$ulpRowsK, $ulpTotalK] = $this->rekapKwhPerUlp($laporanAktifIds, $bulanPerLaporanId, self::KOLOM_ULP_K, $filter);

        $daftarUlp  = $this->daftarUlpTahunIni($laporanAktifIds, array_merge(self::KOLOM_ULP_P, self::KOLOM_ULP_K));
        $namaUlpMap = collect($daftarUlp)->pluck('nama', 'kode')->all();

        return view('laporan.gol-tarif', [
            'daftarTahun'            => $daftarTahun,
            'tahunAktif'             => $tahunAktif,
            'kolomGol'               => self::KOLOM_GOL,
            'prabayar'               => $prabayar,
            'totalPrabayar'          => $totalPrabayar,
            'paskabayar'             => $paskabayar,
            'totalPaskabayar'        => $totalPaskabayar,
            'prabayarPerDaya'        => $prabayarPerDaya,
            'totalPrabayarPerDaya'   => $totalPrabayarPerDaya,
            'paskabayarPerDaya'      => $paskabayarPerDaya,
            'totalPaskabayarPerDaya' => $totalPaskabayarPerDaya,
            'kolomUlpP'              => self::KOLOM_ULP_P,
            'ulpRowsP'               => $ulpRowsP,
            'ulpTotalP'              => $ulpTotalP,
            'kolomUlpK'              => self::KOLOM_ULP_K,
            'ulpRowsK'               => $ulpRowsK,
            'ulpTotalK'              => $ulpTotalK,
            'daftarUlp'              => $daftarUlp,
            'filter'                 => $filter,
            'filterInfoText'         => $this->teksFilterAktif($tahunAktif, $filter, $namaUlpMap),
        ]);
    }

    public function komposisiTemuan(Request $request)
    {
        $request->validate(self::RULES_FILTER);

        $daftarTahun = LaporanSusulan::aktif()
            ->whereNotNull('tahun')
            ->distinct()
            ->orderByDesc('tahun')
            ->pluck('tahun');

        $tahunAktif = (int) ($request->input('tahun') ?: $daftarTahun->first());
        $filter     = $this->ambilFilter($request, $tahunAktif);

        $laporanAktif      = LaporanSusulan::aktif()->where('tahun', $tahunAktif)->get(['id', 'bulan']);
        $laporanAktifIds   = $laporanAktif->pluck('id');
        $bulanPerLaporanId = $laporanAktif->pluck('bulan', 'id');

        $barisMentah = DetailTagihanSusulan::query()
            ->whereIn('laporan_susulan_id', $laporanAktifIds)
            ->whereIn('gol', array_merge(self::KOLOM_ULP_P, self::KOLOM_ULP_K))
            ->select('laporan_susulan_id', 'no_agenda', 'gol', 'kwh', 'ts')
            ->get();

        $petaUlp = [];
        foreach ($barisMentah as $baris) {
            $bulanLaporan = $bulanPerLaporanId[$baris->laporan_susulan_id] ?? null;
            $kodeUlp = $this->lolosFilterBaris($baris->no_agenda, $bulanLaporan, $filter);
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
                'kode'      => $kodeUlp,
                'nama'      => DetailTagihanSusulan::namaUlp($kodeUlp) ?? $kodeUlp,
                'p'         => $data['p'],
                'k'         => $data['k'],
                'total_kwh' => $totalKwh,
                'persen_p'  => $persenP,
                'persen_k'  => $persenK,
            ];

            $totalP['plg'] += $data['p']['plg'];
            $totalP['kwh'] += $data['p']['kwh'];
            $totalP['ts']  += $data['p']['ts'];
            $totalK['plg'] += $data['k']['plg'];
            $totalK['kwh'] += $data['k']['kwh'];
            $totalK['ts']  += $data['k']['ts'];
        }

        usort($rows, fn ($a, $b) => strcmp($a['nama'], $b['nama']));

        $grandTotalKwh  = $totalP['kwh'] + $totalK['kwh'];
        $totalRingkasan = [
            'p'         => $totalP,
            'k'         => $totalK,
            'total_kwh' => $grandTotalKwh,
            'persen_p'  => $grandTotalKwh > 0 ? ($totalP['kwh'] / $grandTotalKwh * 100) : 0,
            'persen_k'  => $grandTotalKwh > 0 ? ($totalK['kwh'] / $grandTotalKwh * 100) : 0,
        ];

        $daftarUlp  = $this->daftarUlpTahunIni($laporanAktifIds, array_merge(self::KOLOM_ULP_P, self::KOLOM_ULP_K));
        $namaUlpMap = collect($daftarUlp)->pluck('nama', 'kode')->all();

        return view('laporan.komposisi-temuan', [
            'daftarTahun'    => $daftarTahun,
            'tahunAktif'     => $tahunAktif,
            'rows'           => $rows,
            'totalRingkasan' => $totalRingkasan,
            'daftarUlp'      => $daftarUlp,
            'filter'         => $filter,
            'filterInfoText' => $this->teksFilterAktif($tahunAktif, $filter, $namaUlpMap),
        ]);
    }

    /**
     * Pivot "per tarif" generik untuk card Prabayar/Paskabayar (view
     * "Gol Tarif") — LIVE dari detail_tagihan_susulans, mereplikasi
     * persis logika agregasi RingkasanGolTarifService::hitungUlang()
     * (kode tarif = segmen sebelum '/' pada kolom `daya`, mis. "R1T/450"
     * -> "R1T"), tapi dihitung per request dengan lolosFilterBaris()
     * diterapkan per baris supaya ikut filter periode + ULP.
     *
     * Hasilnya dipetakan ke $peta[$kodeTarif][$gol] = total_kwh, format
     * yang sama seperti dulu didapat dari tabel precomputed
     * ringkasan_gol_tarifs, supaya susunPivot() tidak perlu diubah.
     */
    private function pivotTarifLive($laporanAktifIds, $bulanPerLaporanId, array $filter): array
    {
        $barisMentah = DetailTagihanSusulan::query()
            ->whereIn('laporan_susulan_id', $laporanAktifIds)
            ->whereIn('gol', self::KOLOM_GOL)
            ->whereNotNull('daya')
            ->where('daya', '!=', '')
            ->select('laporan_susulan_id', 'no_agenda', 'gol', 'daya', 'kwh')
            ->get();

        $peta = [];
        foreach ($barisMentah as $baris) {
            $bulanLaporan = $bulanPerLaporanId[$baris->laporan_susulan_id] ?? null;
            if (! $this->lolosFilterBaris($baris->no_agenda, $bulanLaporan, $filter)) {
                continue;
            }

            $kodeTarif = trim(explode('/', trim($baris->daya))[0] ?? '');

            $peta[$kodeTarif][$baris->gol] = ($peta[$kodeTarif][$baris->gol] ?? 0) + (float) $baris->kwh;
        }

        return $peta;
    }

    /**
     * Pivot "Gol per Daya" generik: baris = string daya UTUH (kode tarif +
     * angka setelah "/", mis. "R1T/450" untuk Prabayar, "R1/450" untuk
     * Paskabayar) — beda angka daya untuk kode tarif yang sama TIDAK
     * digabung, jadi tiap kombinasi tarif+daya jadi baris sendiri. Kolom =
     * golongan P1-P4 + K1-K3, nilainya SUM(kwh) — bukan Rp TS lagi.
     * Sumbernya LIVE dari detail_tagihan_susulans (bukan tabel ringkasan
     * precomputed), jadi ikut filter periode + ULP seperti tabel Rekap
     * KWH per ULP.
     *
     * $daftarKodeValid menentukan set kode tarif yang boleh diproses —
     * pakai KODE_TARIF_PRABAYAR_T untuk Prabayar (kode berakhiran "T")
     * atau KODE_TARIF_PASKABAYAR_DAYA untuk Paskabayar (kode tanpa
     * akhiran "T"). Dengan begini fungsi yang sama dipakai ulang untuk
     * kedua jenis pembayaran tanpa duplikasi logika.
     *
     * @return array{0: array, 1: array} [$rows, $total]
     */
    private function pivotPerDaya($laporanAktifIds, $bulanPerLaporanId, array $filter, array $daftarKodeValid): array
    {
        $barisMentah = DetailTagihanSusulan::query()
            ->whereIn('laporan_susulan_id', $laporanAktifIds)
            ->whereIn('gol', self::KOLOM_GOL)
            ->whereNotNull('daya')
            ->where('daya', '!=', '')
            ->select('laporan_susulan_id', 'no_agenda', 'gol', 'daya', 'kwh')
            ->get();

        $peta          = [];
        $totalPerKolom = array_fill_keys(self::KOLOM_GOL, 0);
        $grandTotal    = 0;

        foreach ($barisMentah as $baris) {
            $dayaMentah = trim($baris->daya);
            $kodeTarif  = trim(explode('/', $dayaMentah)[0] ?? '');

            if (! in_array($kodeTarif, $daftarKodeValid, true)) {
                continue;
            }

            $bulanLaporan = $bulanPerLaporanId[$baris->laporan_susulan_id] ?? null;
            if (! $this->lolosFilterBaris($baris->no_agenda, $bulanLaporan, $filter)) {
                continue;
            }

            // Key pakai string daya UTUH (kode tarif + angka setelah "/"),
            // bukan cuma kode tarif — supaya angka dayanya ikut tampil,
            // tidak digabung/dijumlah jadi satu baris per kode tarif lagi.
            // Nilainya SUM(kwh).
            $peta[$dayaMentah][$baris->gol] = ($peta[$dayaMentah][$baris->gol] ?? 0) + (float) $baris->kwh;
        }

        ksort($peta);

        $rows = [];
        foreach ($peta as $labelDaya => $nilaiGol) {
            $nilaiPerKolom = [];
            $totalBaris    = 0;
            foreach (self::KOLOM_GOL as $g) {
                $sum = $nilaiGol[$g] ?? 0;
                $nilaiPerKolom[$g] = $sum;
                $totalBaris += $sum;
                $totalPerKolom[$g] += $sum;
            }
            $grandTotal += $totalBaris;

            $rows[] = ['label' => $labelDaya, 'nilai' => $nilaiPerKolom, 'total' => $totalBaris];
        }

        foreach ($rows as &$row) {
            $row['persen'] = $grandTotal > 0 ? ($row['total'] / $grandTotal * 100) : 0;
        }
        unset($row);

        $totalPerKolom['grand_total'] = $grandTotal;

        return [$rows, $totalPerKolom];
    }

    /**
     * Hitung rekap KWH + Jumlah Pelanggan per ULP untuk sekumpulan kode
     * golongan tertentu (dipakai buat golongan P1-P4 dan K1-K3 secara
     * terpisah), dengan filter periode + ULP diterapkan per baris lewat
     * lolosFilterBaris().
     *
     * 'plg' per kolom golongan = jumlah baris temuan (1 baris = 1 pelanggan)
     * untuk kombinasi ULP + golongan itu — pola yang sama dipakai di
     * komposisiTemuan(). Kolom 'persen' tetap dihitung dari KWH (bukan
     * jumlah pelanggan), konsisten dengan makna kolom % yang sudah ada.
     *
     * @return array{0: array, 1: array} [$rows, $total]
     */
    private function rekapKwhPerUlp($laporanAktifIds, $bulanPerLaporanId, array $daftarGol, array $filter): array
    {
        $barisMentah = DetailTagihanSusulan::query()
            ->whereIn('laporan_susulan_id', $laporanAktifIds)
            ->whereIn('gol', $daftarGol)
            ->select('laporan_susulan_id', 'no_agenda', 'gol', 'kwh')
            ->get();

        $petaUlp = [];
        foreach ($barisMentah as $baris) {
            $bulanLaporan = $bulanPerLaporanId[$baris->laporan_susulan_id] ?? null;
            $kodeUlp = $this->lolosFilterBaris($baris->no_agenda, $bulanLaporan, $filter);
            if (! $kodeUlp) {
                continue;
            }

            if (! isset($petaUlp[$kodeUlp][$baris->gol])) {
                $petaUlp[$kodeUlp][$baris->gol] = ['kwh' => 0, 'plg' => 0];
            }
            $petaUlp[$kodeUlp][$baris->gol]['kwh'] += (float) $baris->kwh;
            $petaUlp[$kodeUlp][$baris->gol]['plg'] += 1;
        }

        $rows          = [];
        $totalPerGol   = array_fill_keys($daftarGol, 0);
        $totalPlgPerGol = array_fill_keys($daftarGol, 0);
        $grandTotalKwh = 0;
        $grandTotalPlg = 0;

        foreach ($petaUlp as $kodeUlp => $nilaiGol) {
            $row = [
                'kode' => $kodeUlp,
                'nama' => DetailTagihanSusulan::namaUlp($kodeUlp) ?? $kodeUlp,
            ];

            $totalBarisKwh = 0;
            $totalBarisPlg = 0;
            foreach ($daftarGol as $gol) {
                $data = $nilaiGol[$gol] ?? ['kwh' => 0, 'plg' => 0];
                $row[strtolower($gol)] = ['kwh' => $data['kwh'], 'plg' => $data['plg'], 'persen' => 0];
                $totalBarisKwh += $data['kwh'];
                $totalBarisPlg += $data['plg'];
                $totalPerGol[$gol] += $data['kwh'];
                $totalPlgPerGol[$gol] += $data['plg'];
            }

            $row['total'] = ['kwh' => $totalBarisKwh, 'plg' => $totalBarisPlg, 'persen' => 0];
            $grandTotalKwh += $totalBarisKwh;
            $grandTotalPlg += $totalBarisPlg;
            $rows[] = $row;
        }

        usort($rows, fn ($a, $b) => strcmp($a['nama'], $b['nama']));

        foreach ($rows as &$row) {
            foreach ($daftarGol as $gol) {
                $key = strtolower($gol);
                $row[$key]['persen'] = $totalPerGol[$gol] > 0
                    ? ($row[$key]['kwh'] / $totalPerGol[$gol] * 100)
                    : 0;
            }
            $row['total']['persen'] = $grandTotalKwh > 0
                ? ($row['total']['kwh'] / $grandTotalKwh * 100)
                : 0;
        }
        unset($row);

        $total = [];
        foreach ($daftarGol as $gol) {
            $total[strtolower($gol)] = [
                'kwh'    => $totalPerGol[$gol],
                'plg'    => $totalPlgPerGol[$gol],
                'persen' => $totalPerGol[$gol] > 0 ? 100.0 : 0.0,
            ];
        }
        $total['total'] = [
            'kwh'    => $grandTotalKwh,
            'plg'    => $grandTotalPlg,
            'persen' => $grandTotalKwh > 0 ? 100.0 : 0.0,
        ];

        return [$rows, $total];
    }

    private function susunPivot(array $peta, array $daftarKode, bool $prabayar): array
    {
        $pivot         = [];
        $grandTotal    = 0;
        $totalPerKolom = array_fill_keys(self::KOLOM_GOL, 0);

        foreach ($daftarKode as $kode) {
            $labelTarif = ($kode === 'NON PLG')
                ? 'NON PLG'
                : ($prabayar ? $kode . 'T' : $kode);

            $nilaiPerKolom = [];
            $totalBaris    = 0;
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