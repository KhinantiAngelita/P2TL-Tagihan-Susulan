<?php

namespace App\Http\Controllers;

use App\Models\DetailTagihanSusulan;
use App\Models\LaporanSusulan;
use App\Support\ChartImageGenerator;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;

class DetailDataController extends Controller
{
    /**
     * Ekspresi SQL buat "meniru" accessor getUlpAttribute() di level query,
     * karena kolom `ulp` bukan kolom asli di database — dia hasil parsing
     * dari segmen ke-2 `no_agenda` (P2TL/{ULP}/{tanggal}/{urut}).
     */
    private const ULP_SQL = "SUBSTRING_INDEX(SUBSTRING_INDEX(no_agenda, '/', 2), '/', -1)";

    /**
     * Ekspresi SQL buat "meniru" accessor getTanggalAgendaAttribute(),
     * ambil segmen ke-3 no_agenda (format YYYYMMDD) sebagai string.
     * Karena formatnya YYYYMMDD, perbandingan string >= / <= otomatis
     * setara perbandingan tanggal asli.
     */
    private const TANGGAL_AGENDA_SQL = "SUBSTRING_INDEX(SUBSTRING_INDEX(no_agenda, '/', 3), '/', -1)";

    /**
     * Entry point menu sidebar "Detail Laporan" (/data-detail) — TANPA
     * parameter laporan. Karena belum ada laporan spesifik yang dipilih,
     * arahkan ke laporan aktif paling baru. Kalau tidak ada laporan sama
     * sekali, lempar ke Daftar Laporan dengan pesan info.
     *
     * Route: GET /data-detail -> name('detail-data.index')
     */
    public function index()
    {
        $laporan = LaporanSusulan::aktif()->orderByDesc('id')->first();

        if (! $laporan) {
            return redirect()
                ->route('laporan.index')
                ->with('info', 'Belum ada laporan aktif. Upload Excel dulu untuk melihat detail data.');
        }

        return redirect()->route('laporan.show', $laporan);
    }

    /**
     * Halaman "Detail Laporan" — overview 1 LaporanSusulan: kartu ringkasan,
     * 5 chart (filterable lewat rentang tanggal berdasarkan tanggal_register),
     * dan tabel semua baris tagihan (DetailTagihanSusulan) dengan pencarian
     * & filter golongan/ULP/tanggal-agenda. Dipanggil dari tombol "Lihat
     * Detail" di Daftar Laporan lewat route model binding.
     *
     * Catatan: kartu ringkasan (Total KWH, Rp. TS, Penetapan) SEKARANG ikut
     * filter rentang tanggal juga — sumbernya sama persis dengan yang dipakai
     * grafik ($chartBase, filter berdasarkan tanggal_register), bukan lagi
     * dari kolom rekap $laporan->total_keseluruhan yang selalu total
     * keseluruhan tanpa filter.
     *
     * View : resources/views/detail-data/index.blade.php
     * Route: GET /laporan/{laporan}/detail -> name('laporan.show')
     */
    public function show(Request $request, LaporanSusulan $laporan)
    {
        $request->validate([
            'search'         => 'nullable|string|max:100',
            'golongan'       => 'nullable|string|max:20',
            'ulp'            => 'nullable|string|max:20',
            'tanggal_dari'   => 'nullable|date',
            'tanggal_sampai' => 'nullable|date',
        ]);

        $search         = $request->input('search');
        $golongan       = $request->input('golongan', 'semua');
        $ulpFilter      = $request->input('ulp', 'semua');
        $tanggalDari    = $request->input('tanggal_dari');
        $tanggalSampai  = $request->input('tanggal_sampai');

        $detailBase = fn () => DetailTagihanSusulan::where('laporan_susulan_id', $laporan->id);

        // ---- Base query buat grafik & kartu ringkasan: kefilter tanggal_dari/
        //      tanggal_sampai berdasarkan kolom tanggal_register (kolom asli,
        //      bukan hasil parsing no_agenda seperti filter tabel di bawah).
        //      Dipakai oleh SEMUA 5 grafik + kartu ringkasan di halaman ini. ----
        $chartBase = fn () => DetailTagihanSusulan::where('laporan_susulan_id', $laporan->id)
            ->when($tanggalDari, fn ($q) => $q->whereDate('tanggal_register', '>=', $tanggalDari))
            ->when($tanggalSampai, fn ($q) => $q->whereDate('tanggal_register', '<=', $tanggalSampai));

        // ---- Chart 1: distribusi KWH per golongan tarif ----
        $distribusiGolongan = (clone $chartBase())
            ->selectRaw('gol, SUM(kwh) as kwh')
            ->groupBy('gol')
            ->orderBy('gol')
            ->pluck('kwh', 'gol');

        // ---- Chart 3, 4, 5: tren harian KWH, TS, dan Tunai vs Angsuran ----
        $trenHarian = (clone $chartBase())
            ->selectRaw('DATE(tanggal_register) as tanggal, SUM(kwh) as kwh, SUM(ts) as ts, SUM(tunai) as tunai, SUM(angsuran) as angsuran')
            ->whereNotNull('tanggal_register')
            ->groupBy('tanggal')
            ->orderBy('tanggal')
            ->get();

        // ---- Chart 2: donut Tunai vs Angsuran — dihitung dari baris detail
        //      yang sudah kefilter tanggal. Dipakai ulang juga buat kartu
        //      ringkasan "Penetapan" di bawah (total = tunai + angsuran). ----
        $totalTunaiChart    = (clone $chartBase())->sum('tunai');
        $totalAngsuranChart = (clone $chartBase())->sum('angsuran');

        // ---- Kartu statistik — SEKARANG ikut filter rentang tanggal (pakai
        //      $chartBase, sama seperti grafik), BUKAN lagi dari detailBase()
        //      atau kolom rekap $laporan->total_keseluruhan yang selalu total
        //      keseluruhan tanpa filter. ----
        $totalKwh       = (clone $chartBase())->sum('kwh');
        $totalTs        = (clone $chartBase())->sum('ts');
        $totalPenetapan = $totalTunaiChart + $totalAngsuranChart;

        $persenTunai    = $totalPenetapan > 0 ? round($totalTunaiChart / $totalPenetapan * 100) : 0;
        $persenAngsuran = $totalPenetapan > 0 ? 100 - $persenTunai : 0;

        // ---- Tabel "Semua Data Detail" (search/golongan/ulp/tanggal-agenda — terpisah dari filter grafik) ----
        $rows = (clone $detailBase())
            ->when($search, function ($q) use ($search) {
                $q->where(function ($sub) use ($search) {
                    $sub->where('idpel', 'like', "%{$search}%")
                        ->orWhere('nama', 'like', "%{$search}%");
                });
            })
            ->when($golongan && strtolower($golongan) !== 'semua', fn ($q) => $q->where('gol', $golongan))
            ->when($ulpFilter && strtolower($ulpFilter) !== 'semua', function ($q) use ($ulpFilter) {
                $q->whereRaw(self::ULP_SQL . ' = ?', [$ulpFilter]);
            })
            ->when($tanggalDari, function ($q) use ($tanggalDari) {
                $q->whereRaw(self::TANGGAL_AGENDA_SQL . ' >= ?', [\Carbon\Carbon::parse($tanggalDari)->format('Ymd')]);
            })
            ->when($tanggalSampai, function ($q) use ($tanggalSampai) {
                $q->whereRaw(self::TANGGAL_AGENDA_SQL . ' <= ?', [\Carbon\Carbon::parse($tanggalSampai)->format('Ymd')]);
            })
            ->orderBy('no')
            ->paginate(20)
            ->withQueryString();

        $daftarGolongan = (clone $detailBase())
            ->select('gol')
            ->distinct()
            ->orderBy('gol')
            ->pluck('gol');

        // Daftar kode ULP unik buat opsi dropdown filter (kode + nama).
        // Parsing di level PHP, satu sumber logic yang sama kayak accessor
        // getUlpAttribute() di model.
        $daftarUlp = (clone $detailBase())
            ->pluck('no_agenda')
            ->map(function ($noAgenda) {
                $parts = explode('/', (string) $noAgenda);
                return $parts[1] ?? null;
            })
            ->filter()
            ->unique()
            ->sort()
            ->values()
            ->map(fn ($kode) => [
                'kode' => $kode,
                'nama' => DetailTagihanSusulan::namaUlp($kode),
            ]);

        $daftarLaporanBulan = LaporanSusulan::aktif()
            ->where('unit_up3', $laporan->unit_up3)
            ->orderByDesc('tahun')->orderByDesc('bulan')
            ->get(['id', 'bulan', 'tahun']);

        // ---- Distribusi golongan dengan jumlah pelanggan, KWH, dan persentase ----
        // ---- Distribusi golongan dengan jumlah pelanggan, KWH, dan persentase ----
        // SEBELUM: (clone $detailBase())
        $distribusiGolonganDetail = (clone $chartBase())
            ->selectRaw('gol, COUNT(*) as jumlah_pelanggan, SUM(kwh) as total_kwh')
            ->groupBy('gol')
            ->orderBy('gol')
            ->get()
            ->map(function ($row) use ($totalKwh) {
                $row->persen_kwh = $totalKwh > 0 ? round($row->total_kwh / $totalKwh * 100, 1) : 0;
                return $row;
            });

        // ---- Komposisi golongan P vs K (berdasarkan huruf awal kolom `gol`) ----
        // SEBELUM: (clone $detailBase())
        $totalPelangganP = (clone $chartBase())->where('gol', 'like', 'P%')->count();
        $totalPelangganK = (clone $chartBase())->where('gol', 'like', 'K%')->count();
        $totalPelangganKeseluruhan = $totalPelangganP + $totalPelangganK;

        // ---- Tren harian jumlah pelanggan golongan P vs K ----
        // SEBELUM: (clone $detailBase())
        $trenPK = (clone $chartBase())
            ->selectRaw("DATE(tanggal_register) as tanggal,
                SUM(CASE WHEN gol LIKE 'P%' THEN 1 ELSE 0 END) as jumlah_p,
                SUM(CASE WHEN gol LIKE 'K%' THEN 1 ELSE 0 END) as jumlah_k")
            ->whereNotNull('tanggal_register')
            ->groupBy('tanggal')
            ->orderBy('tanggal')
            ->get();   

        return view('detail-data.index', [
            'laporan'            => $laporan,
            'rows'               => $rows,
            'search'             => $search,
            'golonganAktif'      => $golongan,
            'daftarGolongan'     => $daftarGolongan,
            'ulpAktif'           => $ulpFilter,
            'daftarUlp'          => $daftarUlp,
            'tanggalDari'        => $tanggalDari,
            'tanggalSampai'      => $tanggalSampai,
            'daftarLaporanBulan' => $daftarLaporanBulan,
            'persenTunai'        => $persenTunai,
            'persenAngsuran'     => $persenAngsuran,
            'distribusiGolongan' => $distribusiGolongan,
            'trenHarian'         => $trenHarian,
            'totalKwh'           => $totalKwh,
            'totalTs'            => $totalTs,
            'totalPenetapan'     => $totalPenetapan,
            'totalTunaiChart'    => $totalTunaiChart,
            'totalAngsuranChart' => $totalAngsuranChart,
            'distribusiGolonganDetail' => $distribusiGolonganDetail,
            'totalPelangganP'    => $totalPelangganP,
            'totalPelangganK'    => $totalPelangganK,
            'totalPelangganKeseluruhan' => $totalPelangganKeseluruhan,
            'trenPK'             => $trenPK,
        ]);
    }

    /**
     * Modal "Detail Data Pelanggan": tampilkan SEMUA kolom satu baris
     * DetailTagihanSusulan, read-only.
     *
     * View : resources/views/detail-data/show.blade.php
     * Route: GET /data-detail/{detail} -> name('detail-data.show')
     */
    public function showDetail(DetailTagihanSusulan $detail)
    {
        return response()->json($detail);
    }

    /**
     * Tanggal agenda diambil dari segmen ketiga no_agenda,
     * format: P2TL/{kode}/{YYYYMMDD}/{urut} -> contoh: P2TL/53853/20260602/00011
     */
    public function getTanggalAgendaAttribute(): ?\Carbon\Carbon
    {
        if (! $this->no_agenda) {
            return null;
        }

        $segmen = explode('/', $this->no_agenda);
        $tanggalStr = $segmen[2] ?? null;

        if (! $tanggalStr || ! preg_match('/^\d{8}$/', $tanggalStr)) {
            return null;
        }

        try {
            return \Carbon\Carbon::createFromFormat('Ymd', $tanggalStr);
        } catch (\Exception $e) {
            return null;
        }
    }

    /**
     * Hapus satu baris DetailTagihanSusulan.
     *
     * Route: DELETE /data-detail/{detail} -> name('detail-data.destroy')
     */
    public function destroy(DetailTagihanSusulan $detail)
    {
        $laporanId = $detail->laporan_susulan_id;
        $detail->delete();

        return redirect()
            ->route('detail-data.show', $laporanId)
            ->with('success', 'Data pelanggan berhasil dihapus.');
    }

    /**
     * Format angka rupiah jadi singkatan "Jt"/"M".
     * Contoh: 28400000 -> "28.4 Jt"
     */
    public static function formatRupiahJt($value): string
    {
        $value = (float) $value;

        if ($value >= 1_000_000_000) {
            return number_format($value / 1_000_000_000, 1) . ' M';
        }
        if ($value >= 1_000_000) {
            return number_format($value / 1_000_000, 1) . ' Jt';
        }

        return number_format($value, 0, ',', '.');
    }
    public function exportPdf(Request $request, LaporanSusulan $laporan)
    {
        ini_set('memory_limit', '2048M');
        set_time_limit(0);

        // =========================
        // FILTER
        // =========================
        $search        = $request->input('search');
        $golonganAktif = $request->input('golongan', 'semua');
        $ulpAktif      = $request->input('ulp', 'semua');
        $tanggalDari   = $request->input('tanggal_dari');
        $tanggalSampai = $request->input('tanggal_sampai');

                // =========================
        // BASE QUERY
        // =========================
        $base = function () use ($laporan) {
            return DetailTagihanSusulan::where(
                'laporan_susulan_id',
                $laporan->id
            );
        };

        // ---- Base khusus kartu ringkasan & grafik — filter tanggal_dari/
        //      tanggal_sampai berdasarkan tanggal_register, SAMA PERSIS
        //      dengan $chartBase di method show() (web). ----
        $chartBase = function () use ($laporan, $tanggalDari, $tanggalSampai) {
            return DetailTagihanSusulan::where('laporan_susulan_id', $laporan->id)
                ->when($tanggalDari, fn ($q) => $q->whereDate('tanggal_register', '>=', $tanggalDari))
                ->when($tanggalSampai, fn ($q) => $q->whereDate('tanggal_register', '<=', $tanggalSampai));
        };

        try {
            // =========================
            // DATA DETAIL PDF
            // =========================
            $rows = $base()
                ->when($search, function ($q) use ($search) {
                    $q->where(function ($sub) use ($search) {
                        $sub->where('idpel', 'like', "%{$search}%")
                            ->orWhere('nama', 'like', "%{$search}%");
                    });
                })
                ->when($golonganAktif && strtolower($golonganAktif) !== 'semua', function ($q) use ($golonganAktif) {
                    $q->where('gol', $golonganAktif);
                })
                ->when($ulpAktif && strtolower($ulpAktif) !== 'semua', function ($q) use ($ulpAktif) {
                    $q->whereRaw(self::ULP_SQL . ' = ?', [$ulpAktif]);
                })
                ->when($tanggalDari, function ($q) use ($tanggalDari) {
                    $q->whereRaw(self::TANGGAL_AGENDA_SQL . ' >= ?', [
                        \Carbon\Carbon::parse($tanggalDari)->format('Ymd'),
                    ]);
                })
                ->when($tanggalSampai, function ($q) use ($tanggalSampai) {
                    $q->whereRaw(self::TANGGAL_AGENDA_SQL . ' <= ?', [
                        \Carbon\Carbon::parse($tanggalSampai)->format('Ymd'),
                    ]);
                })
                ->orderBy('no')
                ->get();

            // =========================
            // TOTAL
            // =========================
            $totalKwh = (clone $chartBase())->sum('kwh');
            $totalTs = (clone $chartBase())->sum('ts');
            $totalTunai = (clone $chartBase())->sum('tunai');
            $totalAngsuran = (clone $chartBase())->sum('angsuran');
            $totalPenetapan = $totalTunai + $totalAngsuran;

            // =========================
            // DISTRIBUSI GOLONGAN
            // =========================
            $distribusiGolonganDetail = (clone $chartBase())
                ->selectRaw('
                    gol,
                    COUNT(*) as jumlah_pelanggan,
                    SUM(kwh) as total_kwh
                ')
                ->groupBy('gol')
                ->orderBy('gol')
                ->get();

            $totalKwhGolongan = $distribusiGolonganDetail->sum('total_kwh');

            foreach ($distribusiGolonganDetail as $g) {
                $g->persen_kwh = $totalKwhGolongan > 0
                    ? round(($g->total_kwh / $totalKwhGolongan) * 100, 1)
                    : 0;
            }

            // =========================
            // KOMPOSISI P VS K
            // =========================
            $totalPelangganP = (clone $chartBase())
                ->where('gol', 'like', 'P%')
                ->count();

            $totalPelangganK = (clone $chartBase())
                ->where('gol', 'like', 'K%')
                ->count();

            // =========================
            // TREN HARIAN
            // =========================
            $trenHarian = (clone $chartBase())
                ->selectRaw('
                    DATE(tanggal_register) as tanggal,
                    SUM(kwh) as kwh,
                    SUM(ts) as ts
                ')
                ->whereNotNull('tanggal_register')
                ->groupBy('tanggal')
                ->orderBy('tanggal')
                ->get();

            // =========================
            // TREN P VS K
            // =========================
            $trenPK = (clone $chartBase())
                ->selectRaw("
                    DATE(tanggal_register) as tanggal,
                    SUM(
                        CASE
                            WHEN gol LIKE 'P%' THEN 1
                            ELSE 0
                        END
                    ) as jumlah_p,
                    SUM(
                        CASE
                            WHEN gol LIKE 'K%' THEN 1
                            ELSE 0
                        END
                    ) as jumlah_k
                ")
                ->whereNotNull('tanggal_register')
                ->groupBy('tanggal')
                ->orderBy('tanggal')
                ->get();

                    // =========================
            // CHART IMAGES (PNG, biar render sama persis di PDF)
            // =========================
            $golonganColors = ['#ffce3a', '#0b3d91', '#3d63b8', '#6b8fd6', '#1a9c4a'];

            $chartGolonganImg = ChartImageGenerator::barChart(
                $distribusiGolonganDetail->pluck('gol')->toArray(),
                $distribusiGolonganDetail->pluck('total_kwh')->map(fn ($v) => (float) $v)->toArray(),
                $distribusiGolonganDetail->pluck('jumlah_pelanggan')->map(fn ($v) => $v . ' plg')->toArray(),
                $distribusiGolonganDetail->pluck('persen_kwh')->map(fn ($v) => str_replace('.', ',', $v) . '%')->toArray(),
                $golonganColors
            );

            $chartKomposisiImg = ChartImageGenerator::donutChart((int) $totalPelangganP, (int) $totalPelangganK, '#0b3d91', '#ffce3a', 500, 320);

            $trenLabels = $trenHarian->pluck('tanggal')->map(fn ($t) => \Carbon\Carbon::parse($t)->format('d/m'))->toArray();

            $chartTrenKwhImg = ChartImageGenerator::lineChart($trenLabels, [
                ['data' => $trenHarian->pluck('kwh')->map(fn ($v) => (float) $v)->toArray(), 'color' => '#0b3d91', 'label' => 'KWH'],
            ]);

            $chartTrenTsImg = ChartImageGenerator::lineChart($trenLabels, [
                ['data' => $trenHarian->pluck('ts')->map(fn ($v) => (float) $v)->toArray(), 'color' => '#ffce3a', 'label' => 'TS'],
            ], 700, 300, true);

            $trenPkLabels = $trenPK->pluck('tanggal')->map(fn ($t) => \Carbon\Carbon::parse($t)->format('d/m'))->toArray();

            $chartTrenPkImg = ChartImageGenerator::lineChart($trenPkLabels, [
                ['data' => $trenPK->pluck('jumlah_p')->map(fn ($v) => (float) $v)->toArray(), 'color' => '#0b3d91', 'label' => 'Golongan P'],
                ['data' => $trenPK->pluck('jumlah_k')->map(fn ($v) => (float) $v)->toArray(), 'color' => '#ffce3a', 'label' => 'Golongan K'],
            ], 700, 300);    

            // =========================
            // GENERATE PDF
            // =========================
            $pdf = Pdf::setOptions([
                    'isRemoteEnabled'      => false,
                    'isHtml5ParserEnabled' => true,
                    'defaultFont'          => 'sans-serif',
                    'dpi'                  => 96,
                ])
                ->loadView('detail-data.pdf', [
                    'laporan'       => $laporan,
                    'rows'          => $rows,
                    'search'        => $search,
                    'golonganAktif' => $golonganAktif,
                    'ulpAktif'      => $ulpAktif,
                    'tanggalDari'   => $tanggalDari,
                    'tanggalSampai' => $tanggalSampai,

                    'totalKwh'       => $totalKwh,
                    'totalTs'        => $totalTs,
                    'totalPenetapan' => $totalPenetapan,

                    'distribusiGolonganDetail' => $distribusiGolonganDetail,

                    'totalPelangganP' => $totalPelangganP,
                    'totalPelangganK' => $totalPelangganK,

                    'trenHarian' => $trenHarian,
                    'trenPK'     => $trenPK,

                    'chartGolonganImg'  => $chartGolonganImg,
                    'chartKomposisiImg' => $chartKomposisiImg,
                    'chartTrenKwhImg'   => $chartTrenKwhImg,
                    'chartTrenTsImg'    => $chartTrenTsImg,
                    'chartTrenPkImg'    => $chartTrenPkImg,
                ])
                ->setPaper('a4', 'landscape');

            return $pdf->download('Detail-Laporan-' . $laporan->bulan . '-' . $laporan->tahun . '.pdf');

        } catch (\Throwable $e) {
            \Log::error('Export PDF gagal: ' . $e->getMessage(), [
                'laporan_id' => $laporan->id,
                'trace'      => $e->getTraceAsString(),
            ]);

            return back()->with('error', 'Gagal membuat PDF: ' . $e->getMessage());
        }
    }
}