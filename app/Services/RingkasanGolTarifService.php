<?php

namespace App\Services;

use App\Models\DetailTagihanSusulan;
use App\Models\RingkasanGolTarif;
use Illuminate\Support\Facades\DB;

class RingkasanGolTarifService
{
    /**
     * Hitung ulang ringkasan gol tarif untuk satu tahun tertentu, lalu
     * timpa (replace) data lama di tabel ringkasan_gol_tarifs untuk
     * tahun itu. Dipanggil setiap kali data laporan tahun tsb berubah
     * (upload baru, update baris, hapus baris).
     */
    public function hitungUlang(int $tahun): void
    {
        $agregat = DB::table('detail_tagihan_susulans')
            ->join('laporan_susulans', 'laporan_susulans.id', '=', 'detail_tagihan_susulans.laporan_susulan_id')
            ->where('laporan_susulans.status', 'aktif')
            ->where('laporan_susulans.tahun', $tahun)
            ->whereNotNull('detail_tagihan_susulans.daya')
            ->where('detail_tagihan_susulans.daya', '!=', '')
            ->select([
                DB::raw("TRIM(SUBSTRING_INDEX(detail_tagihan_susulans.daya, '/', 1)) as tarif"),
                'detail_tagihan_susulans.gol',
                DB::raw('SUM(detail_tagihan_susulans.ts) as total_ts'),
            ])
            ->groupBy('tarif', 'detail_tagihan_susulans.gol')
            ->get();
            //dd($agregat->pluck('tarif', 'gol'));

        DB::transaction(function () use ($tahun, $agregat) {
            // Hapus ringkasan lama tahun ini, ganti dengan hasil hitung terbaru.
            RingkasanGolTarif::where('tahun', $tahun)->delete();

            $rows = $agregat->map(fn ($r) => [
                'tahun'      => $tahun,
                'tarif'      => $r->tarif,
                'gol'        => $r->gol,
                'total_ts'   => $r->total_ts,
                'created_at' => now(),
                'updated_at' => now(),
            ])->toArray();

            if (! empty($rows)) {
                RingkasanGolTarif::insert($rows);
            }
        });
    }

    /**
     * Hitung ulang untuk semua tahun yang ada di laporan_susulans.
     * Dipakai buat backfill data lama (jalankan sekali lewat artisan
     * command atau tinker setelah migration & fitur ini pertama kali
     * di-deploy).
     */
    public function hitungUlangSemuaTahun(): void
    {
        $tahunList = \App\Models\LaporanSusulan::aktif()
            ->whereNotNull('tahun')
            ->distinct()
            ->pluck('tahun');

        foreach ($tahunList as $tahun) {
            $this->hitungUlang((int) $tahun);
        }
    }
}