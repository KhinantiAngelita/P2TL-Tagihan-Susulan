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

        $perBulan = DetailTagihanSusulan::query()
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

        // ===== TARGET =====
        // Sebelumnya method ini gak pernah query TargetBulanan sama
        // sekali, jadi $targetData selalu ke-fallback 0 semua di blade
        // (lihat trend/index.blade.php). Logic-nya disamain persis
        // kayak di pencapaian(): target per-ULP dipakai duluan, kalau
        // kosong/0 fallback ke target global (ulp = null).
        $ulpUntukTarget = ($ulpAktif && strtolower($ulpAktif) !== 'semua') ? $ulpAktif : null;

        $targetPerUlp = $ulpUntukTarget
            ? TargetBulanan::where('tahun', $tahunAktif)
                ->where('jenis', $jenis)
                ->where('ulp', $ulpUntukTarget)
                ->pluck('nilai_target', 'bulan')
            : collect();

        $targetGlobal = TargetBulanan::where('tahun', $tahunAktif)
            ->where('jenis', $jenis)
            ->whereNull('ulp')
            ->pluck('nilai_target', 'bulan');

        // Susun 12 bulan penuh (Jan-Des) supaya grafik & tabel tetap
        // konsisten bentuknya walau ada bulan yang belum ada laporannya,
        // sekalian hitung kumulatif, total, rata-rata, dan bulan tertinggi
        // dalam satu putaran.
        $labels = [];
        $data = [];
        $targetData = [];
        $tabelBulanan = [];
        $kumulatif = 0;
        $kumulatifTarget = 0;
        $totalTahunIni = 0;
        $jumlahBulanAdaData = 0;
        $bulanTertinggiLabel = null;
        $bulanTertinggiNilai = 0;

        foreach (self::NAMA_BULAN_SINGKAT as $angka => $label) {
            $nilaiBulanIni = (float) ($perBulan[$angka]->total ?? 0);

            $targetBulanIni = (float) ($targetPerUlp[$angka] ?? 0);
            if ($targetBulanIni <= 0) {
                $targetBulanIni = (float) ($targetGlobal[$angka] ?? 0);
            }

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

            $tabelBulanan[] = [
                'label'     => $label,
                'nilai'     => $nilaiBulanIni,
                'kumulatif' => $kumulatif,
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
     * Target, per bulan. Kalau ULP tertentu dipilih tapi targetnya untuk
     * bulan itu belum diisi (0), fallback ke target global ("Semua ULP").
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

        $ulpUntukTarget = ($ulpAktif && strtolower($ulpAktif) !== 'semua') ? $ulpAktif : null;

        // Target per-ULP (kalau ULP tertentu dipilih) & target global
        // (ulp = null). Per-ULP dipakai duluan; kalau kosong/0, fallback
        // ke global supaya bulan itu tetap ada pembandingnya.
        $targetPerUlp = $ulpUntukTarget
            ? TargetBulanan::where('tahun', $tahunAktif)
                ->where('jenis', $jenis)
                ->where('ulp', $ulpUntukTarget)
                ->pluck('nilai_target', 'bulan')
            : collect();

        $targetGlobal = TargetBulanan::where('tahun', $tahunAktif)
            ->where('jenis', $jenis)
            ->whereNull('ulp')
            ->pluck('nilai_target', 'bulan');

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

            $targetBulanIni = (float) ($targetPerUlp[$angka] ?? 0);
            if ($targetBulanIni <= 0) {
                $targetBulanIni = (float) ($targetGlobal[$angka] ?? 0);
            }

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