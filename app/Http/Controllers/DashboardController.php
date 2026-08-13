<?php

namespace App\Http\Controllers;

use App\Models\DetailTagihanSusulan;
use App\Models\LaporanSusulan;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    /**
     * Urutan bulan buat sorting dropdown & tren, karena kolom `bulan`
     * disimpan sebagai teks (hasil parsing dari file Excel), bukan angka.
     */
    private const URUTAN_BULAN = [
        'JANUARI' => 1, 'FEBRUARI' => 2, 'MARET' => 3, 'APRIL' => 4,
        'MEI' => 5, 'JUNI' => 6, 'JULI' => 7, 'AGUSTUS' => 8,
        'SEPTEMBER' => 9, 'OKTOBER' => 10, 'NOVEMBER' => 11, 'DESEMBER' => 12,
    ];

    public function index(Request $request)
    {
        $request->validate([
            'periode' => 'nullable|string',
        ]);

        $periode = $request->query('periode'); // format: "BULAN|TAHUN"
        $bulan = $tahun = null;
        if ($periode && str_contains($periode, '|')) {
            [$bulan, $tahun] = explode('|', $periode, 2);
        }

        // Daftar periode (bulan+tahun) yang tersedia buat opsi filter,
        // diambil dari laporan versi aktif aja (biar gak nampilin periode
        // yang sebenarnya cuma sisa versi lama yang udah digantikan).
        $periodeTersedia = LaporanSusulan::aktif()
            ->select('bulan', 'tahun')
            ->whereNotNull('bulan')->whereNotNull('tahun')
            ->distinct()->get()
            ->sortByDesc(fn ($p) => $p->tahun * 100 + (self::URUTAN_BULAN[$p->bulan] ?? 0))
            ->values();

        // Query laporan sesuai filter periode (kalau ada) — cuma versi aktif,
        // supaya angka gak dobel kalau ada bulan/unit yang pernah diupload ulang.
        $laporanQuery = LaporanSusulan::query()->aktif();
        if ($bulan) $laporanQuery->where('bulan', $bulan);
        if ($tahun) $laporanQuery->where('tahun', $tahun);

        $laporanIds = (clone $laporanQuery)->pluck('id');

        // Base query baris detail, dipakai bareng buat perGol, tren harian,
        // dan preview tabel — sudah kefilter periode.
        $detailFiltered = fn () => DetailTagihanSusulan::whereIn('laporan_susulan_id', $laporanIds);

        // Angka statistik tetap pakai kolom rekap laporan (lebih presisi &
        // sedikit query dibanding hitung ulang dari ribuan baris), karena
        // sekarang gak ada lagi filter di level baris detail (ULP/tanggal).
        $totalLaporan    = (clone $laporanQuery)->count();
        $totalPendapatan = (clone $laporanQuery)->sum('total_keseluruhan');
        $totalTunai      = (clone $laporanQuery)->sum('total_tunai');
        $totalAngsuran   = (clone $laporanQuery)->sum('total_angsuran');

        $perGol = (clone $detailFiltered())
            ->select('gol', DB::raw('SUM(total) as total_rp'), DB::raw('COUNT(*) as jumlah'))
            ->groupBy('gol')->orderByDesc('total_rp')->get();

        // ------------------------------------------------------------------
        // Chart tren: kalau lagi difilter ke 1 periode spesifik, tampilin
        // tren HARIAN buat bulan itu (dari tanggal_register di data detail).
        // Kalau "Semua Bulan", tetap tren per-bulan (join ke laporan_susulans
        // buat ambil bulan/tahun).
        // ------------------------------------------------------------------
        if ($bulan && $tahun) {
            $trenMode = 'harian';

            $perHari = (clone $detailFiltered())
                ->select('tanggal_register', DB::raw('SUM(total) as total_rp'))
                ->whereNotNull('tanggal_register')
                ->groupBy('tanggal_register')
                ->orderBy('tanggal_register')
                ->get();

            $trenLabels = $perHari->pluck('tanggal_register')->map(fn ($t) => $t->format('d/m'));
            $trenData   = $perHari->pluck('total_rp');
        } else {
            $trenMode = 'bulanan';

            $perBulan = DetailTagihanSusulan::query()
                ->join('laporan_susulans', 'laporan_susulans.id', '=', 'detail_tagihan_susulans.laporan_susulan_id')
                ->where('laporan_susulans.status', 'aktif')
                ->whereNotNull('laporan_susulans.bulan')->whereNotNull('laporan_susulans.tahun')
                ->select(
                    'laporan_susulans.bulan',
                    'laporan_susulans.tahun',
                    DB::raw('SUM(detail_tagihan_susulans.total) as total_rp')
                )
                ->groupBy('laporan_susulans.bulan', 'laporan_susulans.tahun')
                ->get()
                ->sortBy(fn ($p) => $p->tahun * 100 + (self::URUTAN_BULAN[$p->bulan] ?? 0))
                ->values();

            $trenLabels = $perBulan->map(fn ($p) => ucfirst(strtolower($p->bulan)) . ' ' . $p->tahun);
            $trenData   = $perBulan->pluck('total_rp');
        }

        $laporanTerbaru = (clone $laporanQuery)->latest()->limit(5)->get();

        // Ringkasan singkat data detail (isi Excel) buat ditampilin di dashboard,
        // ikut kefilter periode. Detail lengkap + pencarian/filter ULP ada di
        // halaman "Data Detail" terpisah.
        // ->with('laporan:id,bulan,tahun') biar link ikon/kolom yang balik ke
        // laporan asal gak nge-trigger query N+1 tiap baris.
        $detailPreview = (clone $detailFiltered())
            ->with('laporan:id,bulan,tahun')
            ->latest('tanggal_register')
            ->limit(8)->get();

        return view('dashboard', compact(
            'totalLaporan', 'totalPendapatan', 'totalTunai', 'totalAngsuran',
            'perGol', 'laporanTerbaru', 'periodeTersedia',
            'bulan', 'tahun', 'detailPreview',
            'trenMode', 'trenLabels', 'trenData'
        ));
    }
}