<?php

namespace App\Http\Controllers;

use App\Models\DetailTagihanSusulan;
use App\Models\LaporanSusulan;
use App\Models\TargetBulanan;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class TrendController extends Controller
{
    /**
     * Urutan bulan buat sorting & mapping, karena kolom `bulan` di
     * laporan_susulans disimpan sebagai teks (JANUARI, FEBRUARI, dst),
     * bukan angka. Sama seperti di DashboardController.
     */
    private const URUTAN_BULAN = [
        'JANUARI' => 1, 'FEBRUARI' => 2, 'MARET' => 3, 'APRIL' => 4,
        'MEI' => 5, 'JUNI' => 6, 'JULI' => 7, 'AGUSTUS' => 8,
        'SEPTEMBER' => 9, 'OKTOBER' => 10, 'NOVEMBER' => 11, 'DESEMBER' => 12,
    ];

    /**
     * Label singkat buat sumbu chart & tabel.
     */
    private const NAMA_BULAN_SINGKAT = [
        1 => 'Jan', 2 => 'Feb', 3 => 'Mar', 4 => 'Apr', 5 => 'Mei', 6 => 'Jun',
        7 => 'Jul', 8 => 'Agu', 9 => 'Sep', 10 => 'Okt', 11 => 'Nov', 12 => 'Des',
    ];

    /**
     * Peta triwulan -> daftar bulan (angka), dipakai buat nerjemahin
     * filter "tw[]" dari panel Filter Periode & ULP jadi daftar bulan.
     */
    private const TW_KE_BULAN = [
        1 => [1, 2, 3],
        2 => [4, 5, 6],
        3 => [7, 8, 9],
        4 => [10, 11, 12],
    ];

    /**
     * Ekspresi SQL buat "meniru" accessor getUlpAttribute() di
     * DetailTagihanSusulan (kolom `ulp` bukan kolom asli di database —
     * hasil parsing segmen ke-2 no_agenda: P2TL/{ULP}/{tanggal}/{urut}).
     */
    private const ULP_SQL = "SUBSTRING_INDEX(SUBSTRING_INDEX(detail_tagihan_susulans.no_agenda, '/', 2), '/', -1)";

    /**
     * Ekspresi SQL buat narik TANGGAL asli dari segmen ke-3 no_agenda
     * (P2TL/{ULP}/{YYYYMMDD}/{urut}) jadi tipe DATE beneran — dipakai
     * khusus buat filter "Rentang Tanggal" di panel Filter Periode & ULP,
     * karena laporan_susulans sendiri cuma nyimpen nama bulan (teks),
     * gak ada tanggal presisi harian.
     */
    private const TANGGAL_SQL = "STR_TO_DATE(SUBSTRING_INDEX(SUBSTRING_INDEX(detail_tagihan_susulans.no_agenda, '/', 3), '/', -1), '%Y%m%d')";

    /**
     * Route: GET /trend/kwh -> name('trend.kwh')
     */
    public function kwh(Request $request)
    {
        return $this->render($request, 'kwh');
    }

    /**
     * Route: GET /trend/ts -> name('trend.ts')
     */
    public function ts(Request $request)
    {
        return $this->render($request, 'ts');
    }

    /**
     * Baca & normalisasi input dari panel Filter Periode & ULP
     * (partial laporan.partials.filter-periode-ulp) yang dipakai bersama
     * oleh render() dan pencapaian().
     *
     * PRIORITAS PENENTUAN "BULAN AKTIF" (bulan mana yang ditampilkan di
     * chart/tabel, BUKAN cuma dipakai buat query — sesuai kesepakatan,
     * kalau user filter ke bulan/triwulan tertentu, chart-nya beneran
     * jadi lebih pendek, bukan tetap 12 bulan dengan sisanya di-dim):
     * 1. Kalau "bulan[]" diisi manual -> pakai itu apa adanya.
     * 2. Kalau kosong tapi "tw[]" diisi -> gabungan bulan dari triwulan
     *    yang dipilih (mis. TW I & TW III -> Jan,Feb,Mar,Jul,Agu,Sep).
     * 3. Kalau keduanya kosong tapi rentang tanggal diisi -> bulan-bulan
     *    (dalam tahun aktif) yang overlap dengan rentang itu.
     * 4. Kalau semuanya kosong -> 12 bulan penuh (perilaku default/lama).
     *
     * @param  array<string,string>  $daftarUlpAssoc  [kode => nama], dipakai buat validasi & label ULP di filterInfoText.
     */
    private function bacaFilterPeriodeUlp(Request $request, int $tahunAktif, array $daftarUlpAssoc, ?string $jenisLabel = null): array
    {
        $twTerpilih    = array_values(array_unique(array_map('intval', (array) $request->input('tw', []))));
        $bulanTerpilih = array_values(array_unique(array_map('intval', (array) $request->input('bulan', []))));
        $ulpTerpilih   = array_values(array_intersect(
            array_map('strval', (array) $request->input('ulp', [])),
            array_keys($daftarUlpAssoc)
        ));
        $tglMulai   = $request->input('tgl_mulai') ?: null;
        $tglSelesai = $request->input('tgl_selesai') ?: null;

        sort($twTerpilih);
        sort($bulanTerpilih);

        // ---- Tentukan bulan aktif (lihat urutan prioritas di komentar atas). ----
        $bulanAktif = $bulanTerpilih;

        if (empty($bulanAktif) && ! empty($twTerpilih)) {
            foreach ($twTerpilih as $tw) {
                $bulanAktif = array_merge($bulanAktif, self::TW_KE_BULAN[$tw] ?? []);
            }
            $bulanAktif = array_values(array_unique($bulanAktif));
            sort($bulanAktif);
        }

        if (empty($bulanAktif) && ($tglMulai || $tglSelesai)) {
            try {
                $mulai   = $tglMulai ? Carbon::parse($tglMulai) : Carbon::create($tahunAktif, 1, 1)->startOfDay();
                $selesai = $tglSelesai ? Carbon::parse($tglSelesai) : Carbon::create($tahunAktif, 12, 31)->endOfDay();

                $bulanAktif = [];
                for ($b = 1; $b <= 12; $b++) {
                    $awalBulan  = Carbon::create($tahunAktif, $b, 1)->startOfMonth();
                    $akhirBulan = (clone $awalBulan)->endOfMonth();
                    if ($awalBulan->lte($selesai) && $akhirBulan->gte($mulai)) {
                        $bulanAktif[] = $b;
                    }
                }
            } catch (\Exception $e) {
                $bulanAktif = [];
            }
        }

        if (empty($bulanAktif)) {
            $bulanAktif = range(1, 12);
        }

        // ---- Susun teks ringkasan filter aktif (dipakai di badge panel,
        // dan ikut ke-capture di fitur Salin Gambar via #filter-info-text).
        // PERBAIKAN: Jenis (Target kWh/Rp TS) sebelumnya nggak pernah
        // dimasukkan ke sini sama sekali — kelewat waktu fungsi ini
        // ditulis, karena "Jenis" cuma konsep di halaman Data Pencapaian
        // (render() buat Trend kWh/Rp TS gak punya ini). Sekarang
        // ditambahin lewat parameter $jenisLabel (opsional, cuma dikirim
        // dari pencapaian()), ditaruh PALING DEPAN sebelum Tahun. ----
        $bagianInfo = [];
        if ($jenisLabel) {
            $bagianInfo[] = $jenisLabel;
        }
        $bagianInfo[] = "Tahun {$tahunAktif}";

        if (! empty($twTerpilih)) {
            $romawi = [1 => 'I', 2 => 'II', 3 => 'III', 4 => 'IV'];
            $bagianInfo[] = 'TW ' . collect($twTerpilih)->map(fn ($t) => $romawi[$t] ?? $t)->implode(', ');
        }
        if (! empty($bulanTerpilih)) {
            $bagianInfo[] = collect($bulanTerpilih)->map(fn ($b) => self::NAMA_BULAN_SINGKAT[$b] ?? $b)->implode(', ');
        }
        if ($tglMulai || $tglSelesai) {
            try {
                $teksMulai   = $tglMulai ? Carbon::parse($tglMulai)->format('d M Y') : '…';
                $teksSelesai = $tglSelesai ? Carbon::parse($tglSelesai)->format('d M Y') : '…';
                $bagianInfo[] = "{$teksMulai} – {$teksSelesai}";
            } catch (\Exception $e) {
                // abaikan kalau format tanggal tidak valid
            }
        }
        $bagianInfo[] = empty($ulpTerpilih)
            ? 'Semua ULP'
            : collect($ulpTerpilih)->map(fn ($kode) => $daftarUlpAssoc[$kode] ?? $kode)->implode(', ');

        return [
            'twTerpilih'     => $twTerpilih,
            'bulanTerpilih'  => $bulanTerpilih,
            'ulpTerpilih'    => $ulpTerpilih,
            'tglMulai'       => $tglMulai,
            'tglSelesai'     => $tglSelesai,
            'bulanAktif'     => $bulanAktif,
            'filterInfoText' => implode(' · ', $bagianInfo),
        ];
    }

    /**
     * Terapkan filter ULP (multi-pilih), bulan aktif, dan rentang tanggal
     * ke sebuah query builder DetailTagihanSusulan yang SUDAH di-join ke
     * laporan_susulans. Dipakai bersama oleh render() dan pencapaian().
     */
    private function terapkanFilter($query, array $filter, int $tahunAktif)
    {
        $namaBulanAktif = collect($filter['bulanAktif'])
            ->map(fn ($b) => array_flip(self::URUTAN_BULAN)[$b] ?? null)
            ->filter()
            ->values()
            ->all();

        return $query
            ->where('laporan_susulans.status', 'aktif')
            ->when($tahunAktif, fn ($q) => $q->where('laporan_susulans.tahun', $tahunAktif))
            ->when(count($filter['bulanAktif']) < 12, fn ($q) => $q->whereIn('laporan_susulans.bulan', $namaBulanAktif))
            ->when(! empty($filter['ulpTerpilih']), function ($q) use ($filter) {
                $placeholder = implode(',', array_fill(0, count($filter['ulpTerpilih']), '?'));
                $q->whereRaw(self::ULP_SQL . " in ({$placeholder})", $filter['ulpTerpilih']);
            })
            ->when($filter['tglMulai'], fn ($q) => $q->whereRaw(self::TANGGAL_SQL . ' >= ?', [$filter['tglMulai']]))
            ->when($filter['tglSelesai'], fn ($q) => $q->whereRaw(self::TANGGAL_SQL . ' <= ?', [$filter['tglSelesai']]));
    }

    /**
     * Logic bersama buat kedua submenu Trend — bedanya cuma kolom yang
     * di-SUM (kwh vs ts) dan teks/label yang ditampilkan di view.
     */
    private function render(Request $request, string $metric)
    {
        $request->validate([
            'tahun'        => 'nullable|integer',
            'ulp'          => 'nullable|array',
            'ulp.*'        => 'nullable|string|max:20',
            'tw'           => 'nullable|array',
            'tw.*'         => 'integer|min:1|max:4',
            'bulan'        => 'nullable|array',
            'bulan.*'      => 'integer|min:1|max:12',
            'tgl_mulai'    => 'nullable|date',
            'tgl_selesai'  => 'nullable|date',
            'mode'         => 'nullable|in:bulanan,kumulatif',
        ]);

        $daftarTahun = LaporanSusulan::aktif()
            ->whereNotNull('tahun')
            ->distinct()
            ->orderByDesc('tahun')
            ->pluck('tahun');

        $tahunAktif = (int) ($request->input('tahun') ?: $daftarTahun->first());
        $mode       = $request->input('mode', 'bulanan');

        // Daftar ULP buat dropdown/checklist filter — pakai peta resmi
        // yang sudah didefinisikan di model, satu sumber data yang sama
        // dengan filter ULP di halaman Detail Laporan.
        $daftarUlpAssoc = DetailTagihanSusulan::PETA_NAMA_ULP; // [kode => nama]
        $daftarUlp = collect($daftarUlpAssoc)
            ->map(fn ($nama, $kode) => ['kode' => $kode, 'nama' => $nama])
            ->values();

        $filter = $this->bacaFilterPeriodeUlp($request, $tahunAktif, $daftarUlpAssoc);
        $bulanAktif = $filter['bulanAktif'];

        $kolom = $metric === 'kwh' ? 'kwh' : 'ts';
        $jenis = $metric; // sama persis dengan key TargetBulanan::JENIS

        $baseQuery = fn () => $this->terapkanFilter(
            DetailTagihanSusulan::query()->join('laporan_susulans', 'laporan_susulans.id', '=', 'detail_tagihan_susulans.laporan_susulan_id'),
            $filter,
            $tahunAktif
        );

        $perBulan = $baseQuery()
            ->select('laporan_susulans.bulan', DB::raw("SUM(detail_tagihan_susulans.{$kolom}) as total"))
            ->groupBy('laporan_susulans.bulan')
            ->get()
            ->keyBy(fn ($row) => self::URUTAN_BULAN[$row->bulan] ?? 0);

        // ===== JUMLAH PELANGGAN =====
        // Dihitung dari idpel UNIK per bulan (bukan jumlah baris), biar
        // pelanggan yang muncul lebih dari sekali dalam bulan yang sama
        // (misal ada revisi/duplikat entri) tetap terhitung 1.
        $pelangganPerBulan = $baseQuery()
            ->select('laporan_susulans.bulan', DB::raw('COUNT(DISTINCT detail_tagihan_susulans.idpel) as jumlah'))
            ->groupBy('laporan_susulans.bulan')
            ->get()
            ->keyBy(fn ($row) => self::URUTAN_BULAN[$row->bulan] ?? 0);

        // ===== TARGET =====
        // Di-whereIn ULP kalau ada yang dipilih; kalau kosong (Semua ULP)
        // dijumlah dari SEMUA ULP yang ada per bulan.
        $targetPerBulan = TargetBulanan::where('tahun', $tahunAktif)
            ->where('jenis', $jenis)
            ->when(! empty($filter['ulpTerpilih']), fn ($q) => $q->whereIn('ulp', $filter['ulpTerpilih']))
            ->get()
            ->groupBy('bulan')
            ->map(fn ($rows) => $rows->sum('nilai_target'));

        // Susun bulan-bulan AKTIF saja (bukan selalu Jan-Des penuh —
        // lihat catatan "bulan aktif" di bacaFilterPeriodeUlp), sekalian
        // hitung kumulatif, total, rata-rata, dan bulan tertinggi dalam
        // satu putaran.
        $labels = [];
        $data = [];
        $targetData = [];
        $jumlahPelangganData = [];
        $tabelBulanan = [];
        $kumulatif = 0;
        $kumulatifTarget = 0;
        $totalTahunIni = 0;
        $jumlahBulanAdaData = 0;
        $bulanTertinggiLabel = null;
        $bulanTertinggiNilai = 0;

        foreach ($bulanAktif as $angka) {
            $label = self::NAMA_BULAN_SINGKAT[$angka];
            $nilaiBulanIni = (float) ($perBulan[$angka]->total ?? 0);
            $jumlahPelangganBulanIni = (int) ($pelangganPerBulan[$angka]->jumlah ?? 0);
            $targetBulanIni = (float) ($targetPerBulan[$angka] ?? 0);

            $labels[] = $label;
            $totalTahunIni += $nilaiBulanIni;

            if ($nilaiBulanIni > 0) {
                $jumlahBulanAdaData++;
            }
            if ($nilaiBulanIni > $bulanTertinggiNilai) {
                $bulanTertinggiNilai = $nilaiBulanIni;
                $bulanTertinggiLabel = $label;
            }

            $kumulatif += $nilaiBulanIni;
            $kumulatifTarget += $targetBulanIni;

            // $targetData ikut mode yang sama kayak $data (aktual), biar
            // pas mode "Komulatif" garis target-nya juga ke-akumulasi,
            // bukan cuma nilai per bulan mentah.
            $data[] = $mode === 'kumulatif' ? $kumulatif : $nilaiBulanIni;
            $targetData[] = $mode === 'kumulatif' ? $kumulatifTarget : $targetBulanIni;

            // Jumlah pelanggan SENGAJA gak ikut diakumulasi walau mode
            // "Komulatif" dipilih — angka pelanggan itu jumlah entitas per
            // bulan (snapshot), bukan nilai yang wajar dijumlah lintas
            // bulan.
            $jumlahPelangganData[] = $jumlahPelangganBulanIni;

            $tabelBulanan[] = [
                'label'     => $label,
                'nilai'     => $nilaiBulanIni,
                'kumulatif' => $kumulatif,
                'pelanggan' => $jumlahPelangganBulanIni,
            ];
        }

        $rataRataBulanan = $jumlahBulanAdaData > 0 ? $totalTahunIni / $jumlahBulanAdaData : 0;

        return view('trend.index', [
            'metric'              => $metric,
            'daftarTahun'         => $daftarTahun,
            'tahunAktif'          => $tahunAktif,
            'daftarUlp'           => $daftarUlp,
            'filter'              => $filter,
            'filterInfoText'      => $filter['filterInfoText'],
            'tampilkanTahunFilter' => true,
            'mode'                => $mode,
            'labels'              => $labels,
            'data'                => $data,
            'targetData'          => $targetData,
            'jumlahPelangganData' => $jumlahPelangganData,
            'tabelBulanan'        => $tabelBulanan,
            'totalTahunIni'       => $totalTahunIni,
            'rataRataBulanan'     => $rataRataBulanan,
            'bulanTertinggiLabel' => $bulanTertinggiLabel,
            'bulanTertinggiNilai' => $bulanTertinggiNilai,
        ]);
    }

    /**
     * Route: GET /trend/pencapaian -> name('trend.pencapaian')
     *
     * Bandingkan nilai aktual (dari laporan aktif, sumber sama seperti
     * render()) terhadap target manual yang diinput lewat halaman Edit
     * Target, per bulan.
     */
    public function pencapaian(Request $request)
    {
        $request->validate([
            'tahun'        => 'nullable|integer',
            'ulp'          => 'nullable|array',
            'ulp.*'        => 'nullable|string|max:20',
            'tw'           => 'nullable|array',
            'tw.*'         => 'integer|min:1|max:4',
            'bulan'        => 'nullable|array',
            'bulan.*'      => 'integer|min:1|max:12',
            'tgl_mulai'    => 'nullable|date',
            'tgl_selesai'  => 'nullable|date',
            'jenis'        => 'nullable|in:kwh,ts',
        ]);

        $daftarTahun = LaporanSusulan::aktif()
            ->whereNotNull('tahun')
            ->distinct()
            ->orderByDesc('tahun')
            ->pluck('tahun');

        $tahunAktif = (int) ($request->input('tahun') ?: $daftarTahun->first());
        $jenis      = $request->input('jenis', 'kwh');

        if (! array_key_exists($jenis, TargetBulanan::JENIS)) {
            $jenis = 'kwh';
        }

        $daftarUlpAssoc = DetailTagihanSusulan::PETA_NAMA_ULP;
        $daftarUlp = collect($daftarUlpAssoc)
            ->map(fn ($nama, $kode) => ['kode' => $kode, 'nama' => $nama])
            ->values();

        $filter = $this->bacaFilterPeriodeUlp($request, $tahunAktif, $daftarUlpAssoc, TargetBulanan::JENIS[$jenis]);
        $bulanAktif = $filter['bulanAktif'];

        $kolom = $jenis === 'kwh' ? 'kwh' : 'ts';

        // Nilai aktual per bulan — sumber & filter sama persis seperti render().
        $perBulanAktual = $this->terapkanFilter(
            DetailTagihanSusulan::query()->join('laporan_susulans', 'laporan_susulans.id', '=', 'detail_tagihan_susulans.laporan_susulan_id'),
            $filter,
            $tahunAktif
        )
            ->select('laporan_susulans.bulan', DB::raw("SUM(detail_tagihan_susulans.{$kolom}) as total"))
            ->groupBy('laporan_susulans.bulan')
            ->get()
            ->keyBy(fn ($row) => self::URUTAN_BULAN[$row->bulan] ?? 0);

        $targetPerBulan = TargetBulanan::where('tahun', $tahunAktif)
            ->where('jenis', $jenis)
            ->when(! empty($filter['ulpTerpilih']), fn ($q) => $q->whereIn('ulp', $filter['ulpTerpilih']))
            ->get()
            ->groupBy('bulan')
            ->map(fn ($rows) => $rows->sum('nilai_target'));

        $labels = [];
        $dataAktual = [];
        $dataTarget = [];
        $tabelBulanan = [];

        $totalAktual = 0;
        $totalTarget = 0;
        $jumlahBulanAdaTarget = 0;
        $jumlahPersen = 0;

        $bulanTertinggiLabel = null;
        $bulanTertinggiPersen = null;
        $bulanTerendahLabel = null;
        $bulanTerendahPersen = null;

        foreach ($bulanAktif as $angka) {
            $label = self::NAMA_BULAN_SINGKAT[$angka];
            $aktual = (float) ($perBulanAktual[$angka]->total ?? 0);
            $targetBulanIni = (float) ($targetPerBulan[$angka] ?? 0);

            $persen = $targetBulanIni > 0 ? round(($aktual / $targetBulanIni) * 100, 1) : null;

            $labels[] = $label;
            $dataAktual[] = $aktual;
            $dataTarget[] = $targetBulanIni;

            $totalAktual += $aktual;
            $totalTarget += $targetBulanIni;

            if ($persen !== null) {
                $jumlahBulanAdaTarget++;
                $jumlahPersen += $persen;

                if ($bulanTertinggiPersen === null || $persen > $bulanTertinggiPersen) {
                    $bulanTertinggiPersen = $persen;
                    $bulanTertinggiLabel = $label;
                }
                if ($bulanTerendahPersen === null || $persen < $bulanTerendahPersen) {
                    $bulanTerendahPersen = $persen;
                    $bulanTerendahLabel = $label;
                }
            }

            $tabelBulanan[] = [
                'label'   => $label,
                'target'  => $targetBulanIni,
                'aktual'  => $aktual,
                'selisih' => $aktual - $targetBulanIni,
                'persen'  => $persen,
            ];
        }

        $persenTotal    = $totalTarget > 0 ? round(($totalAktual / $totalTarget) * 100, 1) : null;
        $rataRataPersen = $jumlahBulanAdaTarget > 0 ? round($jumlahPersen / $jumlahBulanAdaTarget, 1) : null;

        return view('trend.pencapaian', [
            'jenis'                => $jenis,
            'jenisOptions'         => TargetBulanan::JENIS,
            'jenisAktif'           => $jenis,
            'daftarTahun'          => $daftarTahun,
            'tahunAktif'           => $tahunAktif,
            'daftarUlp'            => $daftarUlp,
            'filter'               => $filter,
            'filterInfoText'       => $filter['filterInfoText'],
            'tampilkanTahunFilter' => true,
            'labels'               => $labels,
            'dataAktual'           => $dataAktual,
            'dataTarget'           => $dataTarget,
            'tabelBulanan'         => $tabelBulanan,
            'totalAktual'          => $totalAktual,
            'totalTarget'          => $totalTarget,
            'persenTotal'          => $persenTotal,
            'rataRataPersen'       => $rataRataPersen,
            'bulanTertinggiLabel'  => $bulanTertinggiLabel,
            'bulanTertinggiPersen' => $bulanTertinggiPersen,
            'bulanTerendahLabel'   => $bulanTerendahLabel,
            'bulanTerendahPersen'  => $bulanTerendahPersen,
        ]);
    }
}