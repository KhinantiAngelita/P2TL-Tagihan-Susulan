<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class LaporanSusulan extends Model
{
    protected $guarded = ['id'];

    public function details(): HasMany
    {
        return $this->hasMany(DetailTagihanSusulan::class);
    }
}
