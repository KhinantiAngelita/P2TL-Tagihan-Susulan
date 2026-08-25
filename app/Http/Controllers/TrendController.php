<?php

namespace App\Http\Controllers;

use App\Models\DetailTagihanSusulan;
use App\Models\LaporanSusulan;
use App\Models\TargetBulanan;
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
     * Label singkat buat sumbu chart & tabel, 1-12 selalu ditampilkan
     * penuh (biar bulan yang belum ada datanya tetap kelihatan di grafik).
     */
    private const NAMA_BULAN_SINGKAT = [
        1 => 'Jan', 2 => 'Feb', 3 => 'Mar', 4 => 'Apr', 5 => 'Mei', 6 => 'Jun',
        7 => 'Jul', 8 => 'Agu', 9 => 'Sep', 10 => 'Okt', 11 => 'Nov', 12 => 'Des',
    ];

    /**
     * Ekspresi SQL buat "meniru" accessor getUlpAttribute() di
     * DetailTagihanSusulan (kolom `ulp` bukan kolom asli di database —
     * hasil parsing segmen ke-2 no_agenda: P2TL/{ULP}/{tanggal}/{urut}).
     */
    private const ULP_SQL = "SUBSTRING_INDEX(SUBSTRING_INDEX(detail_tagihan_susulans.no_agenda, '/', 2), '/', -1)";

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
     * Logic bersama buat kedua submenu Trend — bedanya cuma kolom yang
     * di-SUM (kwh vs ts) dan teks/label yang ditampilkan di view.
     */
    private function render(Request $request, string $metric)
    {
        $request->validate([
            'tahun' => 'nullable|integer',
            'ulp'   => 'nullable|string|max:20',
            'mode'  => 'nullable|in:bulanan,kumulatif',
        ]);

        $daftarTahun = LaporanSusulan::aktif()
            ->whereNotNull('tahun')
            ->distinct()
            ->orderByDesc('tahun')
            ->pluck('tahun');

        $tahunAktif = (int) ($request->input('tahun') ?: $daftarTahun->first());
        $ulpAktif   = $request->input('ulp', 'semua');
        $mode       = $request->input('mode', 'bulanan');

        // Daftar ULP buat dropdown filter — pakai peta resmi yang sudah
        // didefinisikan di model, jadi satu sumber data yang sama dengan
        // filter ULP di halaman Detail Laporan.
        $daftarUlp = collect(DetailTagihanSusulan::PETA_NAMA_ULP)
            ->map(fn ($nama, $kode) => ['kode' => $kode, 'nama' => $nama])
            ->values();

        $kolom = $metric === 'kwh' ? 'kwh' : 'ts';

        // $metric ('kwh'/'ts') sama persis dengan key TargetBulanan::JENIS,
        // jadi bisa langsung dipakai buat filter target di bawah.
        $jenis = $metric;

        // Query dasar (join + filter status/tahun/ULP) dipakai ulang buat
        // dua agregasi terpisah di bawah (SUM metrik & COUNT pelanggan
        // unik), jadi filternya dijamin selalu sinkron kalau nanti ada
        // filter baru ditambahkan.
        $baseQuery = fn () => DetailTagihanSusulan::query()
            ->join('laporan_susulans', 'laporan_susulans.id', '=', 'detail_tagihan_susulans.laporan_susulan_id')
            ->where('laporan_susulans.status', 'aktif')
            ->when($tahunAktif, fn ($q) => $q->where('laporan_susulans.tahun', $tahunAktif))
            ->when($ulpAktif && strtolower($ulpAktif) !== 'semua', function ($q) use ($ulpAktif) {
                $q->whereRaw(self::ULP_SQL . ' = ?', [$ulpAktif]);
            });

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
        // Sejak "Semua ULP" di Edit Target mendistribusikan nilai yang
        // sama ke SETIAP kode ULP (bukan lagi tersimpan sebagai satu baris
        // ulp=null), setiap ULP seharusnya sudah punya target sendiri.
        // Jadi tidak ada lagi fallback ke target "global" (ulp=null) di
        // sini — kalau ULP tertentu belum diisi targetnya sama sekali,
        // target-nya memang 0 (bukan di-fallback ke angka lain). Kalau
        // filter "Semua ULP" dipilih di halaman ini (bukan satu ULP
        // spesifik), target dijumlah dari SEMUA ULP yang ada.
        $targetQuery = TargetBulanan::where('tahun', $tahunAktif)->where('jenis', $jenis);

        if ($ulpAktif && strtolower($ulpAktif) !== 'semua') {
            $targetQuery->where('ulp', $ulpAktif);
            $targetPerBulan = $targetQuery->pluck('nilai_target', 'bulan');
        } else {
            $targetPerBulan = $targetQuery->get()
                ->groupBy('bulan')
                ->map(fn ($rows) => $rows->sum('nilai_target'));
        }

        // Susun 12 bulan penuh (Jan-Des) supaya grafik & tabel tetap
        // konsisten bentuknya walau ada bulan yang belum ada laporannya,
        // sekalian hitung kumulatif, total, rata-rata, dan bulan tertinggi
        // dalam satu putaran.
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

        foreach (self::NAMA_BULAN_SINGKAT as $angka => $label) {
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
            // bulan (satu pelanggan bisa saja muncul di bulan berbeda,
            // jadi diakumulasi malah bikin dobel hitung & menyesatkan).
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
            'ulpAktif'            => $ulpAktif,
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
            'tahun' => 'nullable|integer',
            'ulp'   => 'nullable|string|max:20',
            'jenis' => 'nullable|in:kwh,ts',
        ]);

        $daftarTahun = LaporanSusulan::aktif()
            ->whereNotNull('tahun')
            ->distinct()
            ->orderByDesc('tahun')
            ->pluck('tahun');

        $tahunAktif = (int) ($request->input('tahun') ?: $daftarTahun->first());
        $ulpAktif   = $request->input('ulp', 'semua');
        $jenis      = $request->input('jenis', 'kwh');

        if (! array_key_exists($jenis, TargetBulanan::JENIS)) {
            $jenis = 'kwh';
        }

        $daftarUlp = collect(DetailTagihanSusulan::PETA_NAMA_ULP)
            ->map(fn ($nama, $kode) => ['kode' => $kode, 'nama' => $nama])
            ->values();

        $kolom = $jenis === 'kwh' ? 'kwh' : 'ts';

        // Nilai aktual per bulan — sumber & filter ULP sama persis
        // seperti di render() di atas.
        $perBulanAktual = DetailTagihanSusulan::query()
            ->join('laporan_susulans', 'laporan_susulans.id', '=', 'detail_tagihan_susulans.laporan_susulan_id')
            ->where('laporan_susulans.status', 'aktif')
            ->when($tahunAktif, fn ($q) => $q->where('laporan_susulans.tahun', $tahunAktif))
            ->when($ulpAktif && strtolower($ulpAktif) !== 'semua', function ($q) use ($ulpAktif) {
                $q->whereRaw(self::ULP_SQL . ' = ?', [$ulpAktif]);
            })
            ->select('laporan_susulans.bulan', DB::raw("SUM(detail_tagihan_susulans.{$kolom}) as total"))
            ->groupBy('laporan_susulans.bulan')
            ->get()
            ->keyBy(fn ($row) => self::URUTAN_BULAN[$row->bulan] ?? 0);

        // ---- Target: sejak "Semua ULP" mendistribusikan nilai ke setiap
        // ULP, tidak ada lagi fallback ke target "global" (ulp=null). Kalau
        // ULP spesifik dipilih, ambil target ULP itu saja. Kalau "Semua
        // ULP" dipilih, jumlahkan target dari SEMUA ULP per bulan. ----
        $targetQuery = TargetBulanan::where('tahun', $tahunAktif)->where('jenis', $jenis);

        if ($ulpAktif && strtolower($ulpAktif) !== 'semua') {
            $targetQuery->where('ulp', $ulpAktif);
            $targetPerBulan = $targetQuery->pluck('nilai_target', 'bulan');
        } else {
            $targetPerBulan = $targetQuery->get()
                ->groupBy('bulan')
                ->map(fn ($rows) => $rows->sum('nilai_target'));
        }

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

        foreach (self::NAMA_BULAN_SINGKAT as $angka => $label) {
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
            'daftarTahun'          => $daftarTahun,
            'tahunAktif'           => $tahunAktif,
            'daftarUlp'            => $daftarUlp,
            'ulpAktif'             => $ulpAktif,
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