<?php

namespace App\Console\Commands;

use App\Models\DetailTagihanSusulan;
use App\Models\TargetBulanan;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class MigrasiTargetGlobalKeUlp extends Command
{
    protected $signature = 'target:migrasi-global-ke-ulp {--hapus-lama : Hapus baris ulp=null lama setelah berhasil didistribusikan}';

    protected $description = 'Distribusikan baris TargetBulanan lama yang ulp=null (target global) ke setiap kode ULP yang dikenal sistem';

    public function handle(): int
    {
        $baris = TargetBulanan::whereNull('ulp')->get();

        if ($baris->isEmpty()) {
            $this->info('Tidak ada baris target global (ulp=null) yang perlu dimigrasi.');
            return self::SUCCESS;
        }

        $semuaKodeUlp = array_keys(DetailTagihanSusulan::PETA_NAMA_ULP);

        if (empty($semuaKodeUlp)) {
            $this->error('DetailTagihanSusulan::PETA_NAMA_ULP kosong — tidak ada ULP tujuan buat distribusi. Migrasi dibatalkan.');
            return self::FAILURE;
        }

        $this->info("Ditemukan {$baris->count()} baris target global. Mendistribusikan ke " . count($semuaKodeUlp) . ' ULP...');

        DB::transaction(function () use ($baris, $semuaKodeUlp) {
            foreach ($baris as $t) {
                foreach ($semuaKodeUlp as $kodeUlp) {
                    TargetBulanan::updateOrCreate(
                        [
                            'tahun' => $t->tahun,
                            'bulan' => $t->bulan,
                            'jenis' => $t->jenis,
                            'ulp'   => $kodeUlp,
                        ],
                        ['nilai_target' => $t->nilai_target]
                    );
                }
            }

            if ($this->option('hapus-lama')) {
                TargetBulanan::whereNull('ulp')->delete();
            }
        });

        $totalBaru = $baris->count() * count($semuaKodeUlp);
        $this->info("Selesai. {$totalBaru} baris target per-ULP dibuat/diperbarui.");

        if (! $this->option('hapus-lama')) {
            $this->warn('Baris ulp=null lama TIDAK dihapus (tambahkan --hapus-lama kalau mau dibersihkan setelah dicek hasilnya benar).');
        }

        return self::SUCCESS;
    }
}