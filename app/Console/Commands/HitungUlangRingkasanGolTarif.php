<?php

namespace App\Console\Commands;

use App\Services\RingkasanGolTarifService;
use Illuminate\Console\Command;

class HitungUlangRingkasanGolTarif extends Command
{
    protected $signature = 'ringkasan:gol-tarif';
    protected $description = 'Hitung ulang tabel ringkasan gol tarif untuk semua tahun (backfill data lama)';

    public function handle(RingkasanGolTarifService $service): int
    {
        $this->info('Menghitung ulang ringkasan gol tarif...');
        $service->hitungUlangSemuaTahun();
        $this->info('Selesai.');

        return self::SUCCESS;
    }
}