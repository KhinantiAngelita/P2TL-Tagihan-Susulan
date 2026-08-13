<?php

namespace App\Http\Controllers;

use App\Models\DetailTagihanSusulan;
use App\Models\LaporanSusulan;
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
            ->paginate(15)
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
     * Modal "Edit Data Pelanggan": form ubah SEMUA kolom satu baris
     * DetailTagihanSusulan.
     *
     * View : resources/views/detail-data/edit.blade.php
     * Route: GET /data-detail/{detail}/edit -> name('detail-data.edit')
     */
    public function edit(DetailTagihanSusulan $detail)
    {
        return view('detail-data.edit', [
            'detail' => $detail,
        ]);
    }

    /**
     * Simpan perubahan dari form edit. Kolom `total` dihitung ulang
     * otomatis dari tunai + angsuran, sesuai catatan "Dihitung otomatis"
     * di form.
     *
     * Route: PUT /data-detail/{detail} -> name('detail-data.update')
     */
    public function update(Request $request, DetailTagihanSusulan $detail)
    {
        $validated = $request->validate([
            'no_agenda'        => 'nullable|string|max:50',
            'idpel'            => 'required|string|max:20',
            'nama'             => 'required|string|max:150',
            'gol'              => 'nullable|string|max:10',
            'alamat'           => 'nullable|string|max:255',
            'daya'             => 'nullable|string|max:30',
            'kwh'              => 'nullable|numeric|min:0',
            'beban'            => 'nullable|numeric|min:0',
            'kwh_rupiah'       => 'nullable|numeric|min:0',
            'ts'               => 'nullable|numeric|min:0',
            'materai'          => 'nullable|numeric|min:0',
            'segel'            => 'nullable|numeric|min:0',
            'materia'          => 'nullable|numeric|min:0',
            'rpppj'            => 'nullable|numeric|min:0',
            'rpujl'            => 'nullable|numeric|min:0',
            'rpppn'            => 'nullable|numeric|min:0',
            'tunai'            => 'nullable|numeric|min:0',
            'angsuran'         => 'nullable|numeric|min:0',
            'tanggal_register' => 'nullable|date',
            'nomor_register'   => 'nullable|string|max:50',
            'tanggal_sph'      => 'nullable|date',
            'nomor_sph'        => 'nullable|string|max:50',
        ]);

        // Total = Tunai + Angsuran (dihitung otomatis, bukan input manual)
        $validated['total'] = (float) ($validated['tunai'] ?? 0) + (float) ($validated['angsuran'] ?? 0);

        $detail->update($validated);

        return redirect()
            ->route('detail-data.show', $detail->laporan_susulan_id)
            ->with('success', 'Data pelanggan berhasil diperbarui.');
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
}