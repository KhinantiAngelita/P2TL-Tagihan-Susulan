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

    /**
     * Peta kode ULP -> nama ULP (referensi resmi, dari daftar Kode ULP).
     * Kalau ada ULP baru nanti, tinggal tambahin barisnya di sini.
     */
    public const PETA_NAMA_ULP = [
        '53811' => 'ULP Cipayung',
        '53821' => 'ULP Bogor Timur',
        '53825' => 'ULP Pakuan',
        '53831' => 'ULP Bogor Kota',
        '53841' => 'ULP Bogor Barat',
        '53851' => 'ULP Leuwiliang',
        '53853' => 'ULP Jasinga',
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
     * Nama ULP dari kode ULP, misal "53831" -> "ULP Bogor Kota".
     * Null kalau kodenya gak ada di peta (ULP baru yang belum didaftarin).
     */
    public function getUlpNamaAttribute(): ?string
    {
        return self::namaUlp($this->ulp);
    }

    public static function namaUlp(?string $kode): ?string
    {
        return $kode ? (self::PETA_NAMA_ULP[$kode] ?? null) : null;
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
}