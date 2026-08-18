<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TargetBulanan extends Model
{
    protected $fillable = ['tahun', 'bulan', 'jenis', 'ulp', 'nilai_target'];

    public const NAMA_BULAN = [
        1 => 'Januari', 2 => 'Februari', 3 => 'Maret', 4 => 'April',
        5 => 'Mei', 6 => 'Juni', 7 => 'Juli', 8 => 'Agustus',
        9 => 'September', 10 => 'Oktober', 11 => 'November', 12 => 'Desember',
    ];

    public const JENIS = [
        'kwh' => 'Target kWh',
        'ts'  => 'Target Rp TS',
    ];
}