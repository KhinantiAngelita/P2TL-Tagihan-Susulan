<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class P2tlTransaksi extends Model
{
    use HasFactory;

    /**
     * Nama tabel (opsional, Laravel otomatis menebak "p2tl_transaksis",
     * tapi ditulis eksplisit biar jelas).
     */
    protected $table = 'p2tl_transaksis';

    /**
     * Kolom yang boleh diisi lewat mass-assignment (create()/new Model([...])).
     * Dipakai oleh P2tlImport saat proses import Excel.
     */
    protected $fillable = [
        'periode',
        'nomor_urut',
        'no_agenda',
        'kode_ulp',
        'idpel',
        'nama',
        'golongan',
        'alamat',
        'daya_tarif',
        'kwh_beban',
        'kwh_ts',
        'rupiah_ts',
        'rupiah_materai',
        'rupiah_segel',
        'rupiah_ppj',
        'rupiah_ujl',
        'rupiah_ppn',
        'rupiah_total',
        'rupiah_tunai',
        'rupiah_angsuran',
        'tanggal_register',
        'nomor_register',
        'tanggal_sph',
        'nomor_sph',
    ];

    /**
     * Casting otomatis tipe data.
     * - date        -> jadi objek Carbon, bisa langsung ->format('d/m/Y')
     * - decimal:2   -> angka rupiah/kwh selalu konsisten 2 desimal saat diakses
     */
    protected $casts = [
        'nomor_urut'        => 'integer',
        'kwh_beban'         => 'decimal:2',
        'kwh_ts'            => 'decimal:2',
        'rupiah_ts'         => 'decimal:2',
        'rupiah_materai'    => 'decimal:2',
        'rupiah_segel'      => 'decimal:2',
        'rupiah_ppj'        => 'decimal:2',
        'rupiah_ujl'        => 'decimal:2',
        'rupiah_ppn'        => 'decimal:2',
        'rupiah_total'      => 'decimal:2',
        'rupiah_tunai'      => 'decimal:2',
        'rupiah_angsuran'   => 'decimal:2',
        'tanggal_register'  => 'date',
        'tanggal_sph'       => 'date',
    ];

    /**
     * Accessor tambahan: status pembayaran (Lunas / Angsuran),
     * bisa dipanggil di Blade sebagai $transaksi->status_bayar
     */
    protected function statusBayar(): \Illuminate\Database\Eloquent\Casts\Attribute
    {
        return \Illuminate\Database\Eloquent\Casts\Attribute::make(
            get: fn () => $this->rupiah_angsuran > 0 ? 'Angsuran' : 'Lunas',
        );
    }

    /**
     * Scope untuk filter cepat berdasarkan periode.
     * Contoh pakai: P2tlTransaksi::periode('2026-06')->get();
     */
    public function scopePeriode($query, string $periode)
    {
        return $query->where('periode', $periode);
    }

    /**
     * Scope untuk filter berdasarkan kode ULP (dipakai kalau satu periode
     * punya banyak unit UP3/ULP dan laporan dipecah per unit).
     */
    public function scopeUlp($query, ?string $kodeUlp)
    {
        return $query->when($kodeUlp, fn ($q) => $q->where('kode_ulp', $kodeUlp));
    }

    /**
     * Scope pencarian bebas berdasarkan IDPEL atau Nama pelanggan.
     * Dipakai oleh kolom "Cari IDPEL atau nama..." di Detail Laporan.
     */
    public function scopeSearch($query, ?string $term)
    {
        return $query->when($term, function ($q) use ($term) {
            $q->where(function ($sub) use ($term) {
                $sub->where('idpel', 'like', "%{$term}%")
                    ->orWhere('nama', 'like', "%{$term}%");
            });
        });
    }

    /**
     * Scope filter golongan tarif (R3, B1, B2, B3, dst).
     * Nilai "semua" / null / kosong berarti tidak difilter.
     */
    public function scopeGolongan($query, ?string $golongan)
    {
        return $query->when(
            $golongan && strtolower($golongan) !== 'semua',
            fn ($q) => $q->where('golongan', $golongan)
        );
    }
}