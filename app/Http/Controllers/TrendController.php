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
     * TODO: Belum ada sumber data Target di database (belum ada tabel/kolom
     * target sama sekali). Sementara di-hardcode 0 semua di sini biar chart
     * & tabel Target vs Realisasi tetap bisa tampil tanpa error.
     *
     * Kalau nanti target mau diambil dari tabel/config lain, cukup ganti
     * isi method hitungTargetBulanan() di bawah — sisa kode (view, chart,
     * tabel) gak perlu diubah lagi karena semuanya udah nerima $targetData
     * dalam bentuk array 12 nilai (Jan..Des) sesuai urutan self::NAMA_BULAN_SINGKAT.
     */
    private function hitungTargetBulanan(string $metric, int $tahunAktif, string $ulpAktif): array
    {
        $ulpUntukQuery = ($ulpAktif && strtolower($ulpAktif) !== 'semua') ? $ulpAktif : null;

        $targetSpesifik = TargetBulanan::where('tahun', $tahunAktif)
            ->where('jenis', $metric)
            ->where('ulp', $ulpUntukQuery)
            ->pluck('nilai_target', 'bulan');

        $targetGlobal = TargetBulanan::where('tahun', $tahunAktif)
            ->where('jenis', $metric)
            ->whereNull('ulp')
            ->pluck('nilai_target', 'bulan');

        $hasil = [];
        for ($bulan = 1; $bulan <= 12; $bulan++) {
            // Kalau lagi filter ULP tertentu dan target spesifik ULP itu ada, pakai itu.
            // Kalau enggak (baik karena filter "Semua ULP", atau ULP itu belum diisi
            // target-nya sendiri), fallback ke target global.
            $nilai = $ulpUntukQuery
                ? ($targetSpesifik->get($bulan) ?? $targetGlobal->get($bulan, 0))
                : $targetGlobal->get($bulan, 0);

            $hasil[] = (float) $nilai;
        }

        return $hasil;
    }

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

        // Nilai target per bulan (Jan..Des), sejajar sama self::NAMA_BULAN_SINGKAT.
        // Lihat catatan TODO di hitungTargetBulanan().
        $targetPerBulanMentah = $this->hitungTargetBulanan($metric, $tahunAktif, $ulpAktif);

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

        $i = 0;
        foreach (self::NAMA_BULAN_SINGKAT as $angka => $label) {
            $nilaiBulanIni = (float) ($perBulan[$angka]->total ?? 0);
            $targetBulanIni = (float) ($targetPerBulanMentah[$i] ?? 0);

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

            $data[] = $mode === 'kumulatif' ? $kumulatif : $nilaiBulanIni;
            $targetData[] = $mode === 'kumulatif' ? $kumulatifTarget : $targetBulanIni;

            $tabelBulanan[] = [
                'label'     => $label,
                'nilai'     => $nilaiBulanIni,
                'target'    => $targetBulanIni,
                'kumulatif' => $kumulatif,
            ];

            $i++;
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
}