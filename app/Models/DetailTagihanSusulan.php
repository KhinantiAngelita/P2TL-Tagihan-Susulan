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
}
