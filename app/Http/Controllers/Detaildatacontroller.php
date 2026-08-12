<?php

namespace App\Http\Controllers;

use App\Models\DetailTagihanSusulan;
use App\Models\LaporanSusulan;
use Illuminate\Http\Request;

class DetailDataController extends Controller
{
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
     * 3 chart, dan tabel semua baris tagihan (DetailTagihanSusulan) dengan
     * pencarian & filter golongan. Dipanggil dari tombol "Lihat Detail" di
     * Daftar Laporan lewat route model binding.
     *
     * View : resources/views/detail-data/index.blade.php
     * Route: GET /laporan/{laporan}/detail -> name('laporan.show')
     */
    public function show(Request $request, LaporanSusulan $laporan)
    {
        $request->validate([
            'search'   => 'nullable|string|max:100',
            'golongan' => 'nullable|string|max:20',
        ]);

        $search   = $request->input('search');
        $golongan = $request->input('golongan', 'semua');

        $detailBase = fn () => DetailTagihanSusulan::where('laporan_susulan_id', $laporan->id);

        // ---- Chart: distribusi KWH per golongan tarif ----
        $distribusiGolongan = (clone $detailBase())
            ->selectRaw('gol, SUM(kwh) as kwh')
            ->groupBy('gol')
            ->orderBy('gol')
            ->pluck('kwh', 'gol');

        // ---- Chart: tren harian KWH vs TS ----
        $trenHarian = (clone $detailBase())
            ->selectRaw('DATE(tanggal_register) as tanggal, SUM(kwh) as kwh, SUM(ts) as ts, SUM(tunai) as tunai, SUM(angsuran) as angsuran')
            ->whereNotNull('tanggal_register')
            ->groupBy('tanggal')
            ->orderBy('tanggal')
            ->get();

        // ---- Kartu statistik (dari kolom rekap di laporan_susulans) ----
        $totalBayar     = $laporan->total_tunai + $laporan->total_angsuran;
        $persenTunai    = $totalBayar > 0 ? round($laporan->total_tunai / $totalBayar * 100) : 0;
        $persenAngsuran = $totalBayar > 0 ? 100 - $persenTunai : 0;

        // ---- Total KWH & TS untuk kartu statistik ----
        $totalKwh = (clone $detailBase())->sum('kwh');
        $totalTs  = (clone $detailBase())->sum('ts');

        // ---- Tabel "Semua Data Detail" ----
        $rows = (clone $detailBase())
            ->when($search, function ($q) use ($search) {
                $q->where(function ($sub) use ($search) {
                    $sub->where('idpel', 'like', "%{$search}%")
                        ->orWhere('nama', 'like', "%{$search}%");
                });
            })
            ->when($golongan && strtolower($golongan) !== 'semua', fn ($q) => $q->where('gol', $golongan))
            ->orderBy('no')
            ->paginate(15)
            ->withQueryString();

        $daftarGolongan = (clone $detailBase())
            ->select('gol')
            ->distinct()
            ->orderBy('gol')
            ->pluck('gol');

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
            'daftarLaporanBulan' => $daftarLaporanBulan,
            'persenTunai'        => $persenTunai,
            'persenAngsuran'     => $persenAngsuran,
            'distribusiGolongan' => $distribusiGolongan,
            'trenHarian'         => $trenHarian,
            'totalKwh'           => $totalKwh,
            'totalTs'            => $totalTs,
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