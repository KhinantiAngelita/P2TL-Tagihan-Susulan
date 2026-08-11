<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DetailTagihanSusulan extends Model
{
    protected $guarded = ['id'];

    protected $casts = [
        'tanggal_register' => 'date',
        'tanggal_sph' => 'date',
    ];

    public function laporan(): BelongsTo
    {
        return $this->belongsTo(LaporanSusulan::class, 'laporan_susulan_id');
    }

    /**
     * ULP diambil dari segmen ke-2 no_agenda.
     * Contoh: "P2TL/53831/20260502/00009" -> "53831"
     */
    public function getUlpAttribute(): ?string
    {
        $parts = explode('/', (string) $this->no_agenda);
        return $parts[1] ?? null;
    }

    /**
     * Tarif diambil dari segmen pertama kolom `daya`.
     * Contoh: "R1T/450" -> "R1T"
     */
    public function getTarifAttribute(): ?string
    {
        $parts = explode('/', (string) $this->daya);
        return trim($parts[0] ?? '') ?: null;
    }

    /**
     * Daya (VA) diambil dari segmen kedua kolom `daya`.
     * Contoh: "R1T/450" -> "450"
     */
    public function getDayaVaAttribute(): ?string
    {
        $parts = explode('/', (string) $this->daya);
        return isset($parts[1]) ? trim($parts[1]) : null;
    }
}