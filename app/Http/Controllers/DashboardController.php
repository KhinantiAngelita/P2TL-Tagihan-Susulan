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

        // Query laporan sesuai filter (kalau ada) — cuma versi aktif,
        // supaya angka gak dobel kalau ada bulan/unit yang pernah diupload ulang.
        $laporanQuery = LaporanSusulan::query()->aktif();
        if ($bulan) $laporanQuery->where('bulan', $bulan);
        if ($tahun) $laporanQuery->where('tahun', $tahun);

        $totalLaporan    = (clone $laporanQuery)->count();
        $totalPendapatan = (clone $laporanQuery)->sum('total_keseluruhan');
        $totalTunai      = (clone $laporanQuery)->sum('total_tunai');
        $totalAngsuran   = (clone $laporanQuery)->sum('total_angsuran');

        $laporanIds = (clone $laporanQuery)->pluck('id');

        $perGol = DetailTagihanSusulan::select('gol', DB::raw('SUM(total) as total_rp'), DB::raw('COUNT(*) as jumlah'))
            ->whereIn('laporan_susulan_id', $laporanIds)
            ->groupBy('gol')->orderByDesc('total_rp')->get();

        // ------------------------------------------------------------------
        // Chart tren: kalau lagi difilter ke 1 periode spesifik, tampilin
        // tren HARIAN buat bulan itu (dari tanggal_register di data detail).
        // Kalau "Semua Bulan", tetap tren per-bulan seperti biasa (dari versi
        // aktif tiap periode, biar gak dobel hitung kalau ada upload ulang).
        // ------------------------------------------------------------------
        if ($bulan && $tahun) {
            $trenMode = 'harian';

            $perHari = DetailTagihanSusulan::select('tanggal_register', DB::raw('SUM(total) as total_rp'))
                ->whereIn('laporan_susulan_id', $laporanIds)
                ->whereNotNull('tanggal_register')
                ->groupBy('tanggal_register')
                ->orderBy('tanggal_register')
                ->get();

            $trenLabels = $perHari->pluck('tanggal_register')->map(fn ($t) => $t->format('d/m'));
            $trenData   = $perHari->pluck('total_rp');
        } else {
            $trenMode = 'bulanan';

            $perBulan = LaporanSusulan::aktif()
                ->select('bulan', 'tahun', DB::raw('SUM(total_keseluruhan) as total_rp'))
                ->whereNotNull('bulan')->whereNotNull('tahun')
                ->groupBy('bulan', 'tahun')->get()
                ->sortBy(fn ($p) => $p->tahun * 100 + (self::URUTAN_BULAN[$p->bulan] ?? 0))
                ->values();

            $trenLabels = $perBulan->map(fn ($p) => ucfirst(strtolower($p->bulan)) . ' ' . $p->tahun);
            $trenData   = $perBulan->pluck('total_rp');
        }

        $laporanTerbaru = (clone $laporanQuery)->latest()->limit(5)->get();

        // Ringkasan singkat data detail (isi Excel) buat ditampilin di dashboard.
        // Detail lengkap + pencarian ada di halaman "Data Detail" terpisah.
        // ->with('laporan:id,bulan,tahun') biar link ikon/kolom yang balik ke
        // laporan asal gak nge-trigger query N+1 tiap baris.
        $detailPreview = DetailTagihanSusulan::query()
            ->with('laporan:id,bulan,tahun')
            ->when($laporanIds->isNotEmpty() || $bulan || $tahun, fn ($query) => $query->whereIn('laporan_susulan_id', $laporanIds))
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