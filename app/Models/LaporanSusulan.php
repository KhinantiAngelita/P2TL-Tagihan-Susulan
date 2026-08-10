<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class LaporanSusulan extends Model
{
    protected $guarded = ['id'];

    public function details(): HasMany
    {
        return $this->hasMany(DetailTagihanSusulan::class);
    }

    /**
     * Hanya laporan versi aktif (dipakai di dashboard & daftar laporan).
     */
    public function scopeAktif(Builder $query): Builder
    {
        return $query->where('status', 'aktif');
    }

    /**
     * Semua versi (aktif + digantikan) untuk periode & unit yang sama dengan laporan ini,
     * diurutkan dari versi terbaru ke terlama.
     */
    public function semuaVersi()
    {
        return static::where('unit_up3', $this->unit_up3)
            ->where('bulan', $this->bulan)
            ->where('tahun', $this->tahun)
            ->orderByDesc('versi')
            ->get();
    }
}