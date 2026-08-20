<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ringkasan_gol_tarifs', function (Blueprint $table) {
            $table->id();
            $table->unsignedSmallInteger('tahun');
            $table->string('tarif', 20);
            $table->string('gol', 10);
            $table->decimal('total_ts', 18, 2)->default(0);
            $table->timestamps();

            $table->unique(['tahun', 'tarif', 'gol'], 'ringkasan_gol_tarif_unik');
            $table->index('tahun');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ringkasan_gol_tarifs');
    }
};