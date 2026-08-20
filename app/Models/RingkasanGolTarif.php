<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class RingkasanGolTarif extends Model
{
    protected $fillable = ['tahun', 'tarif', 'gol', 'total_ts'];
}